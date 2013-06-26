<?php
class SpecialCssModelTest extends WikiaBaseTest {
	const FULL_URL_EXAMPLE = 'http://www.wikia.com/wiki/Special:CSS';
	const LOCAL_URL_EXAMPLE = '/wiki/Special:CSS';
	
	protected function setUp () {
		require_once( dirname(__FILE__) . '/../SpecialCssModel.class.php');
		parent::setUp();
	}

	/**
	 * @dataProvider testGetSpecialCssDataProvider
	 */
	public function testGetSpecialCssUrl($fullUrl, $expected, $params) {
		$titleMock = $this->getMock('Title', array('getLocalURL', 'getFullUrl'));
		$titleMock->expects($this->any())
			->method('getLocalURL')
			->with($params, false)
			->will($this->returnValue(self::LOCAL_URL_EXAMPLE));
		$titleMock->expects($this->any())
			->method('getFullUrl')
			->with($params, false)
			->will($this->returnValue(self::FULL_URL_EXAMPLE));
		
		$specialCssModelMock = $this->getMock('SpecialCssModel', array('getSpecialCssTitle'));
		$specialCssModelMock->expects($this->once())
			->method('getSpecialCssTitle')
			->will($this->returnValue($titleMock));

		$result = $specialCssModelMock->getSpecialCssUrl($fullUrl, $params);
		$this->assertEquals($expected, $result);
	}

	public function testGetSpecialCssDataProvider() {
		return [
			[true, self::FULL_URL_EXAMPLE, null],
			[false, self::LOCAL_URL_EXAMPLE, null],
			[true, self::FULL_URL_EXAMPLE, array( 'oldid' => 1)],
			[false, self::LOCAL_URL_EXAMPLE, array( 'oldid' => 1)]
		];
	}

	/**
	 * @dataProvider testGetCssFileContentDataProvider
	 */
	public function testGetCssFileContent($expected, $articleContent) {
		if (isset($articleContent)) {
			$fileArticle = $this->getMock('Article', array('getContent'), [new Title()]);
			$fileArticle->expects($this->any())
				->method('getContent')
				->will($this->returnValue($articleContent));
		} else {
			$fileArticle = null;
		}

		$specialCssModelMock = $this->getMock('SpecialCssModel', array('getCssFileArticle'));
		$specialCssModelMock->expects($this->any())
			->method('getCssFileArticle')
			->will($this->returnValue($fileArticle));

		$this->assertEquals($expected, $specialCssModelMock->getCssFileContent());
	}

	public function testGetCssFileContentDataProvider() {
		return [
			['', null],
			['test content', 'test content']
		];
	}

	/**
	 * @param String $title
	 * @param String $expected
	 * 
	 * @dataProvider testGetAfterLastSlashTextDataProvider
	 */
	public function testGetAfterLastSlashText($title, $expected) {
		$getAfterLastSlashTextMethod = new ReflectionMethod('SpecialCssModel', 'getAfterLastSlashText');
		$getAfterLastSlashTextMethod->setAccessible(true);
		
		$this->assertEquals( $expected, $getAfterLastSlashTextMethod->invoke( new SpecialCssModel(), $title ) );
	}
	
	public function testGetAfterLastSlashTextDataProvider() {
		return [
			[
				'title' => '',
				'expected' => '',
			],
			[
				'title' => 'Technical_Update:_November_20,_2012',
				'expected' => 'Technical_Update:_November_20,_2012',
			],
			[
				'title' => 'DaNASCAT/Technical_Update:_November_20,_2012',
				'expected' => 'Technical_Update:_November_20,_2012',
			],
			[
				'title' => 'DaNASCAT/Technical_Update:_November_20,_2012#Major_Bugs_Fixed',
				'expected' => 'Technical_Update:_November_20,_2012#Major_Bugs_Fixed',
			],
			[
				'title' => 'User_blog:DaNASCAT/Technical_Update:_November_21,_2012',
				'expected' => 'Technical_Update:_November_21,_2012',
			],
			[
				'title' => 'Rappy 4187/Technical_Update:_November_22,_2012',
				'expected' => 'Technical_Update:_November_22,_2012',
			],
			[
				'title' => 'Test page/Rappy 4187/Technical_Update:_November_22,_2012',
				'expected' => 'Technical_Update:_November_22,_2012',
			],
		];
	}
	
	public function testRemoveFirstH3() {
		$removeFirstH3Method = new ReflectionMethod('SpecialCssModel', 'removeFirstH3');
		$removeFirstH3Method->setAccessible(true);

		$text = '= Headline 1=\nText text text\n==Headline 2==\nText text text\n==== Headline 4 ====\nText text text\n=== Headlin e   ===\nLorem ipsum dolor sit amet, consectetur adipiscing elit. === Sed sodales ===, nisi eu 
				sagittis vulputate, erat lectus adipiscing dui, a rutrum nunc nisi non lorem. 
				===Nam ullamcorper ===nibh at justo === lacinia mattis===. ====Nulla====vulputate nulla at orci rhoncus, non eleifend ante porttitor.';
		
		$expected = '= Headline 1=\nText text text\n==Headline 2==\nText text text\n==== Headline 4 ====\nText text text\n\nLorem ipsum dolor sit amet, consectetur adipiscing elit. === Sed sodales ===, nisi eu 
				sagittis vulputate, erat lectus adipiscing dui, a rutrum nunc nisi non lorem. 
				===Nam ullamcorper ===nibh at justo === lacinia mattis===. ====Nulla====vulputate nulla at orci rhoncus, non eleifend ante porttitor.';
		
		$this->assertEquals( $expected, $removeFirstH3Method->invoke( new SpecialCssModel(), $text ) );
	}

	/**
	 * @dataProvider testAddAnchorToPostUrlDataProvider
	 */
	public function testAddAnchorToPostUrl( $wikitext, $expected ) {
		$addAnchorToPostUrlMethod = new ReflectionMethod('SpecialCssModel', 'getAnchorFromWikitext');
		$addAnchorToPostUrlMethod->setAccessible(true);
		
		$this->assertEquals( $expected, $addAnchorToPostUrlMethod->invoke( new SpecialCssModel(), $wikitext ) );
	}
	
	public function testAddAnchorToPostUrlDataProvider() {
		return [
			// short headline
			[
				'wikitext' => '= Headline 1=\nText text text\n==Headline 2==\nText text text\n==== Headline 4 ====\nText text text\n=== Headlin e   ===\nLorem ipsum dolor sit amet, consectetur adipiscing elit. === Sed sodales ===, nisi eu 
				sagittis vulputate, erat lectus adipiscing dui, a rutrum nunc nisi non lorem. 
				===Nam ullamcorper ===nibh at justo === lacinia mattis===. ====Nulla====vulputate nulla at orci rhoncus, non eleifend ante porttitor.',
				'exptected' => '#_Headlin_e___'
			],
			// headline with spaces
			[
				'wikitext' => 'Consectetur adipiscing elit\n\n===Headline with more text===\nLorem ipsum dolor sit amet, consectetur adipiscing elit. === Sed sodales ===, nisi eu 
				sagittis vulputate, erat lectus adipiscing dui, a rutrum nunc nisi non lorem. 
				===Nam ullamcorper ===nibh at justo === lacinia mattis===. ====Nulla====vulputate nulla at orci rhoncus, non eleifend ante porttitor.', 
				'exptected' => '#Headline_with_more_text'
			],
			// headline at the begining of string
			[
				'wikitext' => '===Headline 1===\nText text text\n==Headline 2==\nText text text\n==== Headline 4 ====\nText text text\n=== Headlin e   ===\nLorem ipsum dolor sit amet, consectetur adipiscing elit. === Sed sodales ===, nisi eu 
				sagittis vulputate, erat lectus adipiscing dui, a rutrum nunc nisi non lorem. 
				===Nam ullamcorper ===nibh at justo === lacinia mattis===. ====Nulla====vulputate nulla at orci rhoncus, non eleifend ante porttitor.',
				'exptected' => '#Headline_1'
			],
			// headline at the end of string
			[
				'wikitext' => '= Headline 1=\nText text text\n==Headline 2==\nText text text\n==== Headline 4 ====\nText text text\n== Headlin e   ==\nLorem ipsum dolor sit amet, consectetur adipiscing elit. = Sed sodales =, nisi eu 
				sagittis vulputate, erat lectus adipiscing dui, a rutrum nunc nisi non lorem. 
				==== Nam ullamcorper ====nibh at justo = lacinia mattis=. ====Nulla====vulputate nulla at orci rhoncus, non eleifend ante porttitor.===Headline at the end===',
				'exptected' => '#Headline_at_the_end'
			],
			// no h3 tag
			[
				'wikitext' => ' = Donec dapibus =\nMetus id mi sodales dictum. Donec nec condimentum ligula. Duis molestie sagittis leo, ut porttitor neque dapibus non.\n == Nulla vitae ==\nante eros. Maecenas in nisl a justo placerat dignissim. = Suspendisse ipsum =\nAnte, fermentum vel odio eget, euismod ultrices erat. == Nulla volutpat ==\nligula nec tortor aliquam, eu hendrerit mi egestas. Duis adipiscing odio sit amet enim porta sollicitudin. Nullam euismod massa vitae tellus fermentum ullamcorper. Donec at sagittis dolor. Phasellus ac malesuada tellus. Nunc at mauris et tortor suscipit aliquam. Nam eget ipsum cursus elit eleifend tempus eu iaculis leo. Nulla consectetur tellus ut imperdiet hendrerit. Nullam eu varius justo, in viverra justo. Proin pulvinar rhoncus odio ac bibendum. Curabitur pretium enim eget adipiscing malesuada. Fusce vehicula ligula libero, eget interdum massa laoreet eget. Aenean a ligula nec nunc dictum suscipit. Sed vel turpis mauris. ', 
				'exptected' => ''
			],
		];
	}

	/**
	 * @param String $titleText
	 * @param String $fallbackUser
	 * @param String $expected
	 *
	 * @dataProvider testGetUserFromTitleTextDataProvider
	 */
	public function testGetUserFromTitleText($titleText, $fallbackUser, $expected) {
		$getUserFromTitleTextMethod = new ReflectionMethod('SpecialCssModel', 'getUserFromTitleText');
		$getUserFromTitleTextMethod->setAccessible(true);

		$this->assertEquals( $expected, $getUserFromTitleTextMethod->invoke( new SpecialCssModel(), $titleText, $fallbackUser ) );
	}
	
	public function testGetUserFromTitleTextDataProvider() {
		return [
			[
				'titleText' => '',
				'fallbackUser' => 'User',
				'expected' => 'User',
			],
			[
				'titleText' => 'Technical_Update:_November_20,_2012',
				'fallbackUser' => 'User',
				'expected' => 'User',
			],
			[
				'titleText' => 'DaNASCAT/Technical_Update:_November_20,_2012',
				'fallbackUser' => 'User',
				'expected' => 'DaNASCAT',
			],
			[
				'titleText' => 'DaNASCAT/Technical_Update:_November_20,_2012#Major_Bugs_Fixed',
				'fallbackUser' => 'User',
				'expected' => 'DaNASCAT',
			],
			[
				'titleText' => 'User_blog:DaNASCAT/Technical_Update:_November_21,_2012',
				'fallbackUser' => 'User',
				'expected' => 'DaNASCAT',
			],
			[
				'titleText' => 'Rappy 4187/Technical_Update:_November_22,_2012',
				'fallbackUser' => 'User',
				'expected' => 'Rappy 4187',
			],
			[
				'titleText' => 'Test page/Rappy 4187/Technical_Update:_November_22,_2012',
				'fallbackUser' => 'User',
				'expected' => 'Rappy 4187',
			],
		];
	}

	/**
	 * @param $langCode
	 * @param $expectedDbName
	 *
	 * @dataProvider testGetCommunityDbNameDataProvider
	 */
	public function testGetCommunityDbName($langCode, $expectedDbName) {
		$this->mockGlobalVariable('wgCssUpdatesLangMap', $this->getCssUpdateLangMap());
		$this->mockGlobalVariable('wgDevelEnvironment', false);
		$specialCssModelMock = $this->getMock('SpecialCssModel', array('getCssUpdateLang'));
		$specialCssModelMock->expects($this->any())
			->method('getCssUpdateLang')
			->will($this->returnValue($langCode));

		$dbName = $specialCssModelMock->getCommunityDbName();
		$this->assertEquals($expectedDbName, $dbName);
	}


	public function testGetCommunityDbNameDataProvider() {
		return [
			[
				'langCode' => 'en',
				'dbName' => 'wikia'
			],
			[
				'langCode' => 'pl',
				'dbName' => 'plwikia'
			],
			[
				'langCode' => 'es',
				'dbName' => 'es'
			],
			[
				'langCode' => 'fr',
				'dbName' => 'frfr'
			],
			[
				'langCode' => 'it',
				'dbName' => 'it'
			],
			[
				'langCode' => 'de',
				'dbName' => 'de'
			],
			[
				'langCode' => 'ru',
				'dbName' => 'ruwikia'
			],
		];
	}


	/**
	 * @param $userLang
	 * @param $expectedLang
	 *
	 * @dataProvider testGetCssUpdateLangDataProvider
	 */
	public function testGetCssUpdateLang($userLang, $expectedLang) {
		$this->mockGlobalVariable('wgCssUpdatesLangMap', $this->getCssUpdateLangMap());
		$langMock = $this->getMock('Language', array('getCode'));
		$langMock->expects($this->any())
			->method('getCode')
			->will($this->returnValue($userLang));
		$specialCssModelMock = $this->getMock('SpecialCssModel', null);
		$this->mockGlobalVariable('wgLang', $langMock);

		$cssLang = $specialCssModelMock->getCssUpdateLang();
		$this->assertEquals($expectedLang, $cssLang);
	}

	public function testGetCssUpdateLangDataProvider() {
		return [
			[
				'userLang' => 'de',
				'expectedLang' => 'de'
			],
			[
				'userLang' => 'pl',
				'expectedLang' => 'pl'
			],
			[
				'userLang' => 'lt',
				'expectedLang' => 'en'
			],
			[
				'userLang' => 'en',
				'expectedLang' => 'en'
			],
			[
				'userLang' => 'cs',
				'expectedLang' => 'en'
			],
			[
				'userLang' => 'fr',
				'expectedLang' => 'fr'
			]
		];
	}

	private function getCssUpdateLangMap() {
		require( dirname(__FILE__) . '/../SpecialCss.setup.php');
		return $wgCssUpdatesLangMap;
	}
}
