<?php

class CommunityPageSpecialHooks {

	/**
	 * Cache key invalidation when an article is edited
	 *
	 * @param $article
	 * @param User $user
	 * @param $text
	 * @param $summary
	 * @param $minoredit
	 * @param $watchthis
	 * @param $sectionanchor
	 * @param $flags
	 * @param $revision
	 * @param $status
	 * @param $baseRevId
	 * @return bool
	 */
	public static function onArticleSaveComplete(
		$article, User $user, $text, $summary, $minoredit, $watchthis,
		$sectionanchor, $flags, $revision, $status, $baseRevId
	) {
		// Early exit for edits that do not affect any cached item
		if ( $user->isAnon() ) {
			return true;
		}

		// Purge Top Contributors list
		WikiaDataAccess::cachePurge( wfMemcKey( CommunityPageSpecialUsersModel::TOP_CONTRIB_MCACHE_KEY ) );
		CommunityPageSpecialUsersModel::logUserModelPerformanceData( 'purge', 'top_contributors' );

		// Purge All Members List
		WikiaDataAccess::cachePurge( wfMemcKey( CommunityPageSpecialUsersModel::ALL_MEMBERS_MCACHE_KEY ) );
		CommunityPageSpecialUsersModel::logUserModelPerformanceData( 'purge', 'all_contributors' );

		// Purge all admins list
		if ( self::isAdmin( $user->getId() ) ) {
			WikiaDataAccess::cachePurge( wfMemcKey( CommunityPageSpecialUsersModel::ALL_ADMINS_MCACHE_KEY ) );
			CommunityPageSpecialUsersModel::logUserModelPerformanceData( 'purge', 'all_admins' );
		}

		return true;
	}

	/**
	 * Add community page entry point to article page right rail module
	 *
	 * @param array $railModuleList
	 * @return bool
	 */
	public static function onGetRailModuleList( array &$railModuleList ) {
		global $wgTitle;

		if ( $wgTitle->inNamespace( NS_MAIN ) || $wgTitle->isSpecial( 'WikiActivity' ) ) {
			$railModuleList[1342] = [ 'CommunityPageEntryPoint', 'Index', null ];
		}

		return true;
	}

	/**
	 * Purge admins list on user rights change
	 * @param User $user
	 * @param array $validGroupsToAdd
	 * @param array $validGroupsToRemove
	 * @return bool
	 */
	public static function onUserRights( User $user, array $validGroupsToAdd, array $validGroupsToRemove ) {
		if ( self::hasAdminGroup( $validGroupsToAdd ) || self::hasAdminGroup( $validGroupsToRemove ) ) {
			WikiaDataAccess::cachePurge( wfMemcKey( CommunityPageSpecialUsersModel::ALL_ADMINS_MCACHE_KEY ) );
			CommunityPageSpecialUsersModel::logUserModelPerformanceData( 'purge', 'all_admins' );
		}

		return true;
	}

	public static function onUserFirstEditOnLocalWiki( $userId, $wikiId ) {
		WikiaDataAccess::cachePurge( wfMemcKey( CommunityPageSpecialUsersModel::RECENTLY_JOINED_MCACHE_KEY, 14 ) );
		CommunityPageSpecialUsersModel::logUserModelPerformanceData( 'purge', 'recently_joined' );

		return true;
	}

	private static function hasAdminGroup( $userGroups ) {
		return !empty( array_intersect( WikiService::ADMIN_GROUPS, $userGroups ) );
	}

	private static function isAdmin( $userId ) {
		return in_array( $userId, ( new CommunityPageSpecialUsersModel() )->getAdmins() );
	}
}
