<?php

use \Wikia\Tasks\AsyncTaskList;
use \Wikia\Logger\WikiaLogger;

class GlobalWatchlistBot {

	const MAX_ARTICLES_PER_WIKI = 50;

	public function __construct() {
		global $wgExtensionMessagesFiles;
		$wgExtensionMessagesFiles['GlobalWatchlist'] = dirname( __FILE__ ) . '/GlobalWatchlist.i18n.php';
	}

	/**
	 * send email to user
	 */
	public function sendDigestToUser( $userID ) {
		global $wgExternalDatawareDB;

		if ( $this->shouldNotSendDigest( $userID, $sendLogging = true ) ) {
			$this->clearUserFromGlobalWatchlist( $userID );
			return;
		}

		$dbr = wfGetDB( DB_SLAVE, array(), $wgExternalDatawareDB );

		$oResource = $dbr->select(
			array ( "global_watchlist" ),
			array ( "gwa_id", "gwa_user_id", "gwa_city_id", "gwa_namespace", "gwa_title", "gwa_rev_id", "gwa_timestamp" ),
			array (
				"gwa_user_id" => intval( $userID ),
			),
			__METHOD__,
			array (
				"ORDER BY" => "gwa_timestamp, gwa_city_id"
			)
		);

		$records = $dbr->numRows( $oResource );
		$bTooManyPages = ( $records > self::MAX_ARTICLES_PER_WIKI ) ? true : false;
		$iWikiId = $loop = 0;
		$aDigestData = array();
		$aWikiDigest = array( 'pages' => array() );
		$aRemove = array();
		while ( $oResultRow = $dbr->fetchObject( $oResource ) ) {
			# ---
			if ( $loop >= self::MAX_ARTICLES_PER_WIKI ) {
				break;
			}

			$oWikia = WikiFactory::getWikiByID( $oResultRow->gwa_city_id );
			if ( empty( $oWikia ) || empty( $oWikia->city_public ) ) {
				continue;
			}

			if ( $iWikiId != $oResultRow->gwa_city_id ) {

				if ( count( $aWikiDigest['pages'] ) ) {
					$aDigestData[ $iWikiId ] = $aWikiDigest;
				}

				$iWikiId = $oResultRow->gwa_city_id;

				if ( isset( $aDigestData[ $iWikiId ] ) ) {
					$aWikiDigest = $aDigestData[ $iWikiId ];
				} else {
					$aWikiDigest = array(
						'wikiName' => $oWikia->city_title,
						'wikiLangCode' => $oWikia->city_lang,
						'pages' => array()
					);
				}
			} // if

			if ( in_array( $oResultRow->gwa_namespace, array( NS_BLOG_ARTICLE_TALK, NS_BLOG_ARTICLE ) ) ) {
				# blogs
				$aWikiBlogs[$iWikiId][] = $oResultRow;
				$this->makeBlogsList( $aWikiDigest, $iWikiId, $oResultRow );
			} else {
				$oGlobalTitle = GlobalTitle::newFromText( $oResultRow->gwa_title, $oResultRow->gwa_namespace, $iWikiId );
				if ( $oGlobalTitle->exists() ) {
					$aWikiDigest[ 'pages' ][] = array(
						'title' => GlobalTitle::newFromText( $oResultRow->gwa_title, $oResultRow->gwa_namespace, $iWikiId ),
						'revisionId' => $oResultRow->gwa_rev_id
					);
				} else {
					$aRemove[] = $oResultRow->gwa_id;
				}
			}

			$loop++;

		} // while
		$dbr->freeResult( $oResource );

		$cnt = count( $aWikiDigest['pages'] );
		if ( isset( $aWikiDigest['blogs'] ) ) {
			$cnt += count( $aWikiDigest['blogs'] );
		}
		if ( !empty( $cnt ) ) {
			$aDigestData[ $iWikiId ] = $aWikiDigest;
		}

		if ( count( $aDigestData ) ) {
			$this->sendMail( $userID, $aDigestData, $bTooManyPages );
		}

		if ( count( $aRemove ) ) {
			$dbs = wfGetDB( DB_MASTER, array(), $wgExternalDatawareDB );
			foreach ( $aRemove as $gwa_id ) {
				$dbs->delete( 'global_watchlist', array( 'gwa_user_id' => $userID, 'gwa_id' => $gwa_id ), __METHOD__ );
			}
			$dbs->commit();
		}
	}

	/**
	 * @param $userID
	 * @param $sendLogging
	 * @return bool
	 */
	public function shouldNotSendDigest( $userID, $sendLogging = false ) {
		$user = $this->getUserObject( $userID );
		try {
			$this->checkIfValidUser( $user );
			$this->checkIfEmailUnSubscribed( $user );
			$this->checkIfEmailConfirmed( $user );
			$this->checkIfSubscribedToWeeklyDigest( $user );
		} catch ( Exception $e ) {
			if ( $sendLogging ) {
				WikiaLogger::instance()->info( 'Skipped Weekly Digest', [
					'reason' => $e->getMessage(),
					'userID' => $userID
				]);
			}
			return true;
		}
		return false;
	}

	/**
	 * @param $userID
	 * @return null|User
	 */
	private function getUserObject( $userID ) {
		global $wgExternalAuthType;

		if ( $wgExternalAuthType ) {
			$mExtUser = ExternalUser::newFromId( $userID );
			if ( is_object( $mExtUser ) && ( 0 != $mExtUser->getId() ) ) {
				$mExtUser->linkToLocal( $mExtUser->getId() );
				$user = $mExtUser->getLocalUser();
			} else {
				$user = null;
			}
		} else {
			$user = User::newFromId ( $userID );
		}

		return $user;
	}

	/**
	 * @param $user
	 * @throws Exception
	 */
	private function checkIfValidUser( $user ) {
		if ( !$user instanceof User ) {
			throw new Exception( 'Invalid user object.' );
		}
	}

	/**
	 * @param $user User
	 * @throws Exception
	 */
	private function checkIfEmailUnSubscribed( $user ) {
		if ( $user->getBoolOption( 'unsubscribed' ) ) {
			throw new Exception( 'Email is unsubscribed.' );
		}
	}

	/**
	 * @param $user User
	 * @throws Exception
	 */
	private function checkIfEmailConfirmed( $user ) {
		if ( !$user->isEmailConfirmed() ) {
			throw new Exception( 'Email is not confirmed.' );
		}
	}

	/**
	 * @param $user User
	 * @throws Exception
	 */
	private function checkIfSubscribedToWeeklyDigest( $user ) {
		if ( !$user->getBoolOption( 'watchlistdigest' ) ) {
			throw new Exception( 'Not subscribed to weekly digest' );
		}
	}

	/**
	 * @param $userID
	 */
	private function clearUserFromGlobalWatchlist( $userID ) {
		$task = new GlobalWatchlistTask();
		( new AsyncTaskList() )
			->wikiId( F::app()->wg->CityId )
			->add( $task->call( 'clearGlobalWatchlistAll', $userID ) )
			->queue();
	}

	/**
	 * send email
	 */
	private function sendMail( $iUserId, $aDigestData, $isDigestLimited ) {
		$oUser = User::newFromId( $iUserId );
		$oUser->load();

		$sEmailSubject = $this->getLocalizedMsg( 'globalwatchlist-digest-email-subject', $oUser->getOption( 'language' ) );
		list( $sEmailBody, $sEmailBodyHTML ) = $this->composeMail( $oUser, $aDigestData, $isDigestLimited );

		$sFrom = 'Wikia <community@wikia.com>';
		// yes this needs to be a MA object, not string (the docs for sendMail are wrong)
		$oReply = new MailAddress( 'noreply@wikia.com' );

		$oUser->sendMail( $sEmailSubject, $sEmailBody, $sFrom, $oReply, 'GlobalWatchlist', $sEmailBodyHTML );

		WikiaLogger::instance()->info( 'Sent Weekly Digest', [ 'userID' => $iUserId ] );
	}

	/**
	 * compose digest email for user
	 */
	function composeMail ( $oUser, $aDigestsData, $isDigestLimited ) {

		$sDigests = "";
		$sDigestsHTML = "";
		$sDigestsBlogs = "";
		$sDigestsBlogsHTML = "";
		$iPagesCount = 0; $iBlogsCount = 0;

		$sBodyHTML = null;
		$usehtmlemail = false;
		if ( $oUser->isAnon() || $oUser->getOption( 'htmlemails' ) ) {
			$usehtmlemail = true;
		}
		$oUserLanguage = $oUser->getOption( 'language' ); // get this once, since its used 10 times in this func
		foreach ( $aDigestsData as $aDigest ) {
			$wikiname = $aDigest['wikiName'] . ( $aDigest['wikiLangCode'] != 'en' ?  " (" . $aDigest['wikiLangCode'] . ")": "" ) . ':';

			$sDigests .=  $wikiname . "\n";
			if ( $usehtmlemail ) {
				$sDigestsHTML .= "<b>" . $wikiname . "</b><br/>\n";
			}

			if ( !empty( $aDigest['pages'] ) ) {
				if ( $usehtmlemail ) {
					$sDigestsHTML .= "<ul>\n";
				}

				foreach ( $aDigest['pages'] as $aPageData ) {
					// watchlist tracking, rt#33913
					$url = $aPageData['title']->getFullURL( 's=dgdiff' . ( $aPageData['revisionId'] ? "&diff=" . $aPageData['revisionId'] . "&oldid=prev" : "" ) );

					// plain email
					$sDigests .= $url . "\n";

					// html email
					if ( $usehtmlemail ) {
						$pagename = $aPageData['title']->getArticleName();
						$pagename = str_replace( '_', ' ', rawurldecode( $pagename ) );
						$sDigestsHTML .= '<li><a href="' . $url . '">' . $pagename . "</a></li>\n";
					}

					$iPagesCount++;
				}

				if ( $usehtmlemail ) {
					$sDigestsHTML .= "</ul>\n<br/>\n";
				}
			}

			# blog comments
			if ( !empty( $aDigest['blogs'] ) ) {
				foreach ( $aDigest['blogs'] as $blogTitle => $blogComments ) {
					# $countComments = ($blogComments['comments'] >= $blogComments['own_comments']) ? intval($blogComments['comments'] - $blogComments['own_comments']) : $blogComments['comments'];
					$countComments = $blogComments['comments'];

					$tracking_url = $blogComments['blogpage']->getFullURL( 's=dg' ); // watchlist tracking, rt#33913

					$message = wfMsgReplaceArgs(
						( $countComments != 0 ) ? $this->getLocalizedMsg( 'globalwatchlist-blog-page-title-comment', $oUserLanguage ) : "$1",
						array (
							0 => $tracking_url, // send the ugly tracking url to the plain emails
							1 => $countComments
						)
					);
					$sDigestsBlogs .= $message . "\n";

					if ( $usehtmlemail ) {
						// for html emails, remake some things
						$clean_url = $blogComments['blogpage']->getFullURL();
						$clean_url = str_replace( '_', ' ', rawurldecode( $clean_url ) );
						$message = wfMsgReplaceArgs(
							( $countComments != 0 ) ? $this->getLocalizedMsg( 'globalwatchlist-blog-page-title-comment', $oUserLanguage ) : "$1",
							array (
								0 => "<a href=\"{$tracking_url}\">" . $clean_url . "</a>", // but use the non-tracking one for html display
								1 => $countComments
							)
						);
						$sDigestsBlogsHTML .= $message . "<br/>\n";
					}

					$iBlogsCount++;
				}
				$sDigestsBlogs .= "\n";
			}

			$sDigests .= "\n";
		}
		if ( $isDigestLimited ) {
			$sDigests .= $this->getLocalizedMsg( 'globalwatchlist-see-more', $oUserLanguage ) . "\n";
		}
		$aEmailArgs = array(
			0 => ucfirst( $oUser->getName() ),
			1 => ( $iPagesCount > 0 ) ? $sDigests : $this->getLocalizedMsg( 'globalwatchlist-no-page-found', $oUserLanguage ),
			2 => ( $iBlogsCount > 0 ) ? $sDigestsBlogs : "",
		);

		$sMessage = $this->getLocalizedMsg( 'globalwatchlist-digest-email-body', $oUserLanguage ) . "\n";
		if (empty($aEmailArgs[2])) $sMessage = $this->cutOutPart($sMessage, '$2', '$3');
		$sBody = wfMsgReplaceArgs( $sMessage, $aEmailArgs );
		if ( $usehtmlemail ) {
			// rebuild the $ args using the HTML text we've built
			$aEmailArgs = array(
				0 => ucfirst( $oUser->getName() ),
				1 => ( $iPagesCount > 0 ) ? $sDigestsHTML : $this->getLocalizedMsg( 'globalwatchlist-no-page-found', $oUserLanguage ),
				2 => ( $iBlogsCount > 0 ) ? $sDigestsBlogsHTML : "",
			);

			$sMessageHTML = $this->getLocalizedMsg( 'globalwatchlist-digest-email-body-html', $oUserLanguage );
			if ( !wfEmptyMsg( 'globalwatchlist-digest-email-body-html', $sMessageHTML ) ) {
				if (empty($aEmailArgs[2])) $sMessageHTML = $this->cutOutPart($sMessageHTML, '$2', '$3');
				$sBodyHTML = wfMsgReplaceArgs( $sMessageHTML, $aEmailArgs );
			}
		}

		return array( $sBody, $sBodyHTML );
	}

	private function cutOutPart($message, $startMarker, $endMarker, $replacement = " ") {
		// this is a quick way to skip some parts of email message without remaking all the i18n messages.
		$startPos = strpos($message, $startMarker);
		$endPos = strpos($message, $endMarker);
		if ($startPos !== FALSE && $endPos !== FALSE) {
			$message = substr($message, 0, $startPos + strlen($startMarker)) . $replacement . substr($message, $endPos);
		}
		return $message;
	}

	private function getLocalizedMsg( $sMsgKey, $sLangCode ) {
		$sBody = null;

		if ( ( $sLangCode != 'en' ) && !empty( $sLangCode ) ) {
			// custom lang translation
			$sBody = wfMsgExt( $sMsgKey, array( 'language' => $sLangCode ) );
		}

		if ( $sBody == null ) {
			$sBody = wfMsg( $sMsgKey );
		}

		return $sBody;
	}

	/**
	 * blogs
	 */
	private function makeBlogsList( &$aWikiDigest, $iWikiId, $oResultRow ) {
		$blogTitle = $oResultRow->gwa_title;

		if ( $oResultRow->gwa_namespace == NS_BLOG_ARTICLE_TALK ) {
			$parts = ArticleComment::explode( $oResultRow->gwa_title );
			$blogTitle = $parts['title'];
		}
		
		if ( empty( $blogTitle ) ) {
			return false;
		}		

		if ( empty( $aWikiDigest[ 'blogs' ][ $blogTitle ] ) ) {
			$wikiDB = WikiFactory::IDtoDB( $oResultRow->gwa_city_id );
			if ( $wikiDB ) {
				$db_wiki = wfGetDB( DB_SLAVE, 'stats', $wikiDB );
				$like_title = $db_wiki->buildLike( $oResultRow->gwa_title, $db_wiki->anyString() );
				if ( $db_wiki && $like_title ) {
					$oRow = $db_wiki->selectRow(
						array( "watchlist" ),
						array( "count(*) as cnt" ),
						array(
							"wl_namespace = '" . NS_BLOG_ARTICLE_TALK . "'",
							"wl_title $like_title",
							"wl_notificationtimestamp is not null",
							"wl_notificationtimestamp >= '" . $oResultRow->gwa_timestamp . "'",
							"wl_user > 0",
						),
						__METHOD__
					);
					$aWikiDigest[ 'blogs' ][ $blogTitle ] = array (
						'comments' => intval( $oRow->cnt ),
						'blogpage' => GlobalTitle::newFromText( $blogTitle, NS_BLOG_ARTICLE, $iWikiId ),
						'own_comments' => 0
					);
					
					if ( !in_array( $wikiDB, array( 'wikicities', 'messaging' ) ) ) {
						$db_wiki->close();
					}		
				}	
			}		
		}

		if (
			( $oResultRow->gwa_namespace == NS_BLOG_ARTICLE_TALK ) &&
			isset( $aWikiDigest[ 'blogs' ][ $blogTitle ] )
		) {
			$aWikiDigest[ 'blogs' ][ $blogTitle ]['own_comments']++;
		}
	}
}
