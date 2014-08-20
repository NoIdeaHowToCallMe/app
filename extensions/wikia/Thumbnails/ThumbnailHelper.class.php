<?php

/**
 * Thumbnail Helper
 * @author Saipetch
 */
class ThumbnailHelper extends WikiaModel {

	/**
	 * Get attributes for mustache template
	 * Don't use this for values that need to be escaped.
	 * Wrap attributes in three curly braces so quote marks don't get escaped.
	 * Ex: {{# attrs }}{{{ . }}} {{/ attrs }}
	 * @param array $attrs [ array( key => value ) ]
	 * @return array [ array( 'key="value"' ) ]
	 */
	public static function getAttribs( array $attrs ) {
		$attribs = [];
		foreach ( $attrs as $key => $value ) {
			$str = $key;
			if ( !empty( $value ) ) {
				$str .= '="' . $value . '"';
			}
			$attribs[] = $str;
		}

		return $attribs;
	}

	/**
	 * Get thumbnail size. Mainly used for the class name that determines the size of the play button.
	 * @param integer $width
	 * @return string $size
	 */
	public static function getThumbnailSize( $width = 0 ) {
		if ( $width < 100 ) {
			$size = 'xxsmall';
		} else if ( $width < 200 ) {
			$size = 'xsmall';
		} else if ( $width < 270 ) {
			$size = 'small';
		} else if ( $width < 470 ) {
			$size = 'medium';
		} else if ( $width < 720 ) {
			$size = 'large';
		} else {
			$size = 'xlarge';
		}

		return $size;
	}

	/**
	 * Get data-params attribute (for video on mobile)
	 * @param File $file
	 * @param string $imgSrc
	 * @param array $options
	 * @return string
	 */
	public static function getDataParams( $file, $imgSrc, $options ) {
		if ( is_callable( [ $file, 'getProviderName' ] ) ) {
			$provider = $file->getProviderName();
		} else {
			$provider = '';
		}

		$dataParams = [
			'type'     => 'video',
			'name'     => htmlspecialchars( $file->getTitle()->getDBKey() ),
			'full'     => $imgSrc,
			'provider' => $provider,
		];

		if ( !empty( $options['caption'] ) ) {
			$dataParams['capt'] = 1;
		}

		return htmlentities( json_encode( [ $dataParams ] ) , ENT_QUOTES );
	}

	/**
	 * Collect the img tag attributes from $options
	 * @param MediaTransformOutput $thumb
	 * @param array $options
	 * @return array
	 */
	public static function setImageAttribs( &$controller, MediaTransformOutput $thumb, array $options ) {
		/** @var Title $title */
		$title = $thumb->file->getTitle();
		$titleText = '';

		if ( $title instanceof Title ) {
			$titleText = $title->getText();
			$controller->mediaKey = htmlspecialchars( urlencode( $title->getDBKey() ) );
			$controller->mediaName = htmlspecialchars( $titleText );
		}

		$controller->alt = Sanitizer::encodeAttribute(
			empty( $options['alt'] ) ? $titleText : $options['alt']
		);

		$controller->imgSrc = $thumb->url;

		// Check fluid
		if ( empty( $options[ 'fluid' ] ) ) {
			$controller->imgWidth = $thumb->width;
			$controller->imgHeight = $thumb->height;
		}

		if ( !empty( $options['valign'] ) ) {
			$controller->style = "vertical-align: {$options['valign']}";
		}

		$controller->imgClass = empty( $options['img-class'] ) ? [] : explode( ' ', $options['img-class'] );
	}

	/**
	 * Get anchor tag attributes for an image
	 *
	 * @param MediaTransformOutput $thumb
	 * @param array $options
	 * @return array|bool
	 */
	public static function setImageLinkAttribs( &$controller, MediaTransformOutput $thumb, array $options ) {
		$href = false;
		$title = false;
		$target = false;

		// If we have the details icon enabled, have the anchor wrapping the image link to the
		// raw file.  If not, keep previous behavior and link to the file page
		if ( F::app()->wg->ShowArticleThumbDetailsIcon && !F::app()->checkSkin( 'monobook' ) ) {
			$defaultHref = $thumb->file->getUrl();
		} else {
			$defaultHref = $thumb->file->getTitle()->getLocalURL();
		}

		if ( !empty( $options['custom-url-link'] ) ) {
			$href = $options['custom-url-link'];
			if ( !empty( $options['title'] ) ) {
				$title = Sanitizer::encodeAttribute( $options['title'] );
			}
			if ( !empty( $options['custom-target-link'] ) ) {
				$target = $options['custom-target-link'];
			}

		} elseif ( !empty( $options['custom-title-link'] ) ) {
			/** @var Title $title */
			$titleObj = $options['custom-title-link'];
			$href = $titleObj->getLinkURL();
			$title = Sanitizer::encodeAttribute(
				empty( $options['title'] ) ? $titleObj->getFullText() : $options['title']
			);

		} elseif ( !empty( $options['desc-link'] ) ) {
			$href = $defaultHref;
			if ( !empty( $options['title'] ) ) {
				$title = Sanitizer::encodeAttribute( $options['title'] );
			}

		} elseif ( !empty( $options['file-link'] ) ) {
			$href = $defaultHref;
		}

		$controller->href = $href;
		$controller->title = $title;
		$controller->target = $target;
	}

	public static function setVideoImgAttribs( &$controller, $thumb, array $options ) {
		// get alt for img tag
		$file = $thumb->file;
		$title = $file->getTitle();
		$controller->alt = empty( $options['alt'] ) ? $title->getText() : $options['alt'];

		// set image attributes
		$controller->imgSrc = $options['src'];
		$controller->mediaKey = htmlspecialchars( $title->getDBKey() );
		$controller->mediaName = htmlspecialchars( $title->getText() );
		$controller->imgClass = empty( $options['img-class'] ) ? [] : explode( ' ', $options['img-class'] );

		// check fluid
		if ( empty( $options['fluid'] ) ) {
			$controller->imgWidth = $thumb->width;
			$controller->imgHeight = $thumb->height;
		}

		// Prefer the src given in options over what's passed in directly.
		// @TODO there is no reason to pass two versions of image source.  See if both are actually used and pick one
		// @TODO Mobile passes via options['source'] - see if that's necessary
		$imgSrc = empty( $options['src'] ) ? $thumb->url : $options['src'];
		$controller->imgSrc = $imgSrc;

		// set data-params for img tag on mobile
		// TODO: only used on mobile, could be made into separate template
		if ( !empty( $options['dataParams'] ) ) {
			$controller->dataParams = self::getDataParams( $file, $imgSrc, $options );
		}
	}

	public static function setVideoLinkAttribs( &$controller, $thumb, array $options ) {
		// Get href for a tag
		$file = $thumb->file;
		$title = $file->getTitle();
		$linkHref = $title->getFullURL();

		// Get timestamp for older versions of files (used on file page history tab)
		if ( $file instanceof OldLocalFile ) {
			$archive_name = $file->getArchiveName();
			if ( !empty( $archive_name ) ) {
				$linkHref .= '?t='.$file->getTimestamp();
			}
		}
		$controller->linkHref = $linkHref;

		// Get the id parameter for a tag
		if ( !empty( $options['id'] ) ) {
			$controller->linkId = $options['id'];
		}
	}

	/**
	 * Create an array of needed classes for video thumbs anchors.
	 *
	 * @param array $options The thumbnail options passed to toHTML.  This method cares about:
	 *
	 * - $options[ 'noLightbox' ]
	 * - $options[ 'linkAttribs' ][ 'class' ]
	 * - $options[ 'hidePlayButton' ]
	 * - $options[ 'fluid' ]
	 *
	 * @return array
	 */
	public static function setVideoLinkClasses( WikiaController &$controller, MediaTransformOutput $thumb, array &$options ) {
		$linkClasses = [];
		if ( empty( $options['noLightbox'] ) ) {
			$linkClasses[] = 'image';
			$linkClasses[] = 'lightbox';
		}

		// Pull out any classes found in the linkAttribs parameter
		if ( !empty( $options['linkAttribs']['class'] ) ) {
			$classes = $options['linkAttribs']['class'];

			// If we got a string, treat it like space separated values and turn it into an array
			// TODO: we might want to check for arrays as opposed to strings in other places too
			if ( !is_array( $classes ) ) {
				$classes = explode( ' ', $classes );
			}

			$linkClasses = array_merge( $linkClasses, $classes );
			unset( $options['linkAttribs']['class'] );
		}

		// Hide the play button
		if ( !empty( $options['hidePlayButton'] ) ) {
			$linkClasses[] = 'hide-play';
		}

		// Check for fluid
		if ( ! empty( $options[ 'fluid' ] ) ) {
			$linkClasses[] = 'fluid';
		}

		if ( !empty( $options['forceSize'] ) ) {
			$linkClasses[] = $options['forceSize'];
		} else {
			$linkClasses[] = self::getThumbnailSize( $thumb->width );
		}

		$controller->linkClasses = array_unique( $linkClasses );
	}

	/**
	 * Create an array of needed classes for image thumbs anchors.
	 *
	 * @param array $options The thumbnail options passed to toHTML.
	 * @return array
	 */
	public static function setImageLinkClasses( &$controller, $thumb, array $options ) {
		$classes = [];

		if ( !empty( $options['custom-title-link'] ) ) {
			$classes[] = 'link-internal';
		} elseif ( !empty( $options['custom-url-link'] ) ) {
			$classes[] = 'link-external';
		}

		$controller->linkClasses = $classes;
	}

	/**
	 * Create array of any image attributes that are sent in by extensions
	 * All values MUST BE SANITIZED before reaching this point
	 * @param $controller
	 * @param $thumb
	 * @param $options
	 */
	public static function setExtraImgAttribs( &$controller, $thumb, $options ) {
		// Let extensions add any link attributes
		if ( isset( $options['imgAttribs'] ) && is_array( $options['imgAttribs'] ) ) {
			$controller->extraImgAttrs = self::getAttribs( $options['imgAttribs'] );
			//lizbug($controller->extraImgAttrs);
		}
	}

	/**
	 * Create array of any link attributes that are sent in by extensions
	 * All values MUST BE SANITIZED before reaching this point
	 * @param $controller
	 * @param $thumb
	 * @param $options
	 */
	public static function setExtraLinkAttribs( &$controller, $thumb, $options ) {
		// Let extensions add any link attributes
		if ( isset( $options['linkAttribs'] ) && is_array( $options['linkAttribs'] ) ) {
			$controller->extraLinkAttrs = self::getAttribs( $options['linkAttribs'] );
		}
	}

	/**
	 * Determines by options if image should be lazyloaded
	 * @param array $options
	 * @return bool
	 */
	public static function shouldLazyLoad( $controller, array $options ) {
		return (
			empty( $options['noLazyLoad'] )
			&& isset( $controller->imgSrc )
			&& ImageLazyLoad::isValidLazyLoadedImage( $controller->imgSrc )
		);
	}
}
