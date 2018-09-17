<?php
/**
 * File containing the ParameterProvider class
 *
 * @copyright (C) 2016 - 2017, Stephan Gambke
 * @license       GNU General Public License, version 2 (or any later version)
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup       SimpleBatchUpload
 */

namespace SimpleBatchUpload;

use Parser;
use PPFrame;


/**
 * Class UploadButtonRenderer
 * @package SimpleBatchUpload
 */
class UploadButtonRenderer {

	/**
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 *
	 * @return array
	 */
	public function renderParserFunction( Parser $parser, PPFrame $frame, $args ) {
		$args = array_map( [ $frame, 'expand' ], $args );
		$output = $parser->getOutput();

		$html = $this->renderUploadButton( $args, $output );

		return [ $html, 'isHTML' => true, 'noparse' => true, 'nowiki' => false ];
	}

	/**
	 * @param string[] $args
	 * @param \ParserOutput | \OutputPage $output
	 *
	 * @return string
	 */
	protected function renderUploadButton( $args, $output ) {
		$paramProvider = $this->prepareParameterProvider( $args );

		$this->addModulesToOutput( $output );

		if ( method_exists( $output, 'setPageTitle' ) ) {
			$output->setPageTitle( $paramProvider->getSpecialPageTitle() );
		}

		return $this->getHtml( $paramProvider );
	}

	/**
	 * @param string[] $args
	 *
	 * @return ParameterProvider
	 */
	protected function prepareParameterProvider( $args ) {
		$templateName = $args[0];

		$paramProvider = new ParameterProvider( $templateName );

		if ( $templateName !== '' ) {
			array_shift( $args );
			foreach ( $args as $node ) {
				$paramProvider->addTemplateParameter( $node );
			}
		}
		return $paramProvider;
	}

	/**
	 * @param \ParserOutput | \OutputPage $output
	 */
	protected function addModulesToOutput( $output ) {
		$output->addModules( 'ext.SimpleBatchUpload' );
		$output->addModuleStyles( [ 'ext.SimpleBatchUpload', 'ext.SimpleBatchUpload.jquery-file-upload' ] );
	}

	/**
	 * @param ParameterProvider $paramProvider
	 *
	 * @return string
	 */
	protected function getHtml( ParameterProvider $paramProvider ) {
		$escapedUploadComment = $paramProvider->getEscapedUploadComment();
		$escapedUploadPageText = $paramProvider->getEscapedUploadPageText();

		return '<span class="fileupload-container"> ' . '<span class="fileupload-dropzone fileinput-button"> ' .
			'<i class="glyphicon glyphicon-plus"></i> ' . '<span>' .
			\Message::newFromKey( 'simplebatchupload-buttonlabel' )->escaped() . '</span> ' .
			'<!-- The file input field used as target for the file upload widget -->' .
			'<input class="fileupload" type="file" name="file" multiple ' . '    data-url="' . wfScript( 'api' ) .
			'" ' . '    data-comment="' . $escapedUploadComment . '" ' . '    data-text="' . $escapedUploadPageText .
			'" ' . '> ' . '</span><ul class="fileupload-results"></ul> ' . '</span>';
	}

	/**
	 * @param SpecialBatchUpload $specialPage
	 * @param string $templateName
	 */
	public function renderSpecialPage( SpecialBatchUpload $specialPage, $templateName ) {
		$args = [ $templateName ];
		$output = $specialPage->getOutput();

		$html = $this->renderUploadButton( $args, $output );

		$output->addHTML( $html );
	}

}
