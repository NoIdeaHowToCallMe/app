<?php

class PortableInfoboxParserTagController extends WikiaController {
	const PARSER_TAG_NAME = 'infobox';

	/**
	 * @desc Parser hook: used to register parser tag in MW
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function parserTagInit( Parser $parser ) {
		$parser->setHook( self::PARSER_TAG_NAME, [ new static(), 'renderInfobox' ] );
		return true;
	}

	/**
	 * @desc Renders Infobox
	 *
	 * @param String $text
	 * @param Array $params
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @returns String $html
	 */
	public function renderInfobox( $text, $params, $parser, $frame ) {

		$markup = '<' . self::PARSER_TAG_NAME . '>' . $text . '</' . self::PARSER_TAG_NAME . '>';

		$infoboxParser = new Wikia\PortableInfobox\Parser\XmlParser( $frame->getNamedArguments() );
		$infoboxParser->setExternalParser( (new Wikia\PortableInfobox\Parser\MediaWikiParserService( $parser, $frame ) ) );
		$data = $infoboxParser->getDataFromXmlString( $markup );

		$renderer = new PortableInfoboxRenderService();
		$renderedValue = $renderer->renderInfobox( $data );

		return [ $renderedValue, 'markerType' => 'nowiki' ];
	}

}
