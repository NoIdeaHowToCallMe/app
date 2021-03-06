<?php

class ShareButtonTwitter extends ShareButton {

	/**
	 * AssetsManager compliant path to assets
	 * @var array
	 */
	protected static $assets = array( '//extensions/wikia/ShareButtons/js/ShareButtonTwitter.js' );

	/**
	 * Return HTML rendering share box (with votes count)
	 *
	 * @see http://twitter.com/goodies/tweetbutton
	 * @return string
	 */
	public function getShareBox() {
		global $wgNoExternals;
		if (!empty($wgNoExternals)) {
			return '';
		}

		$html = Xml::element('a', array(
			'href' => 'https://twitter.com/share',
			'class' => 'twitter-share-button',
			'data-count' => 'vertical',
			'data-url' => $this->getUrl(),
		), 'Tweet');

		return $html;
	}

}
