/**
 * File containing the SimpleBatchUpload class
 *
 * @copyright (C) 2016 - 2017, Stephan Gambke
 * @license   GNU General Public License, version 2 (or any later version)
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup SimpleBatchUpload
 */

/** global: mediaWiki */
/** global: jQuery */

( function ( $, mw ) {

	'use strict';

	$( function () {
		$( 'span.fileupload-container' ).each( function () {

			var container = this;

			$( 'input.fileupload', container )

				.on( 'change drop', function ( /* e, data */ ) {
					$( 'ul.fileupload-results', container ).empty();
				} )

				.fileupload( {
					dataType: 'json',
					dropZone: $( '.fileupload-dropzone', container ),
					progressInterval: 100,

					add: function ( e, data ) {

						var status = $( '<li>' ),
							that = this,
							filesLimitPerBatch = mw.config.get( 'wgSimpleBatchUploadMaxFilesPerBatch' ),
							api = new mw.Api(),
							tokenType = 'csrf';

						if ( filesLimitPerBatch ) {
							if ( data.originalFiles.length > filesLimitPerBatch ) {
								alert( mw.msg( 'simplebatchupload-max-files-alert', filesLimitPerBatch ) );
								return false;
							}
						}

						data.id = Date.now();

						status = $( '<li>' )
							.attr( 'id', data.id )
							.text( data.files[ 0 ].name );

						$( 'ul.fileupload-results', container ).append( status );

						if ( mw.config.get( 'wgVersion' ) < '1.27.0' ) {
							tokenType = 'edit';
						}

						// invalidate cached token; always request a new one
						api.badToken( tokenType );

						api.getToken( tokenType )
							.then(
								function ( token ) {

									data.formData = {
										format: 'json',
										action: 'upload',
										token: token,
										ignorewarnings: 1,
										text: $( that ).fileupload( 'option', 'text' ),
										comment: $( that ).fileupload( 'option', 'comment' ),
										filename: data.files[ 0 ].name
									};

									data.submit()
										.success( function ( result /* , textStatus, jqXHR */ ) {

											var link;

											if ( result.error !== undefined ) {
												status.text( status.text() + ' ERROR: ' + result.error.info ).addClass( 'ful-error api-error' );
											} else {
												link = $( '<a>' );
												link
													.attr( 'href', mw.Title.newFromFileName( result.upload.filename ).getUrl() )
													.text( result.upload.filename );

												status
													.addClass( 'ful-success' )
													.text( ' OK' )
													.prepend( link );
											}

										} )
										.error( function ( /* jqXHR, textStatus, errorThrown */ ) {
											status.text( status.text() + ' ERROR: Server communication failed.' ).addClass( 'ful-error server-error' );
											// console.log( JSON.stringify( arguments ) );
										} );
								},
								function () {
									status.text( status.text() + ' ERROR: Could not get token.' ).addClass( 'ful-error token-error' );
									// console.log( JSON.stringify( arguments ) );
								}
							);

					},

					progress: function ( e, data ) {
						if ( data.loaded !== data.total ) {
							$( '#' + data.id )
								.text( data.files[ 0 ].name + ' ' + parseInt( data.loaded / data.total * 100, 10 ) + '%' );
						}
					}
				} );
		} );

		$( document ).bind( 'drop dragover', function ( e ) {
			e.preventDefault();
		} );
	} );

}( jQuery, mediaWiki ) );
