<?php
/**
 * @author Jeffrey Simms
 * @copyright (c) 2017, Memorial University of Newfoundland
 */

namespace App\ApplyOnline;

use Illuminate\Http\Request;
use App\Http\Controllers\AdvertisementFileController;
use App\Advertisement;

class AdvertisementProcessor {

	/**
	 * @var array
	 */
	public static $dataTemplate = [ 'department' => null, 'content' => null ];

	/**
	 * @var array
	 */
	public static $processors = [ 'pdf' => 'pdfProcessor', 'html' => 'htmlProcessor' ];

	/**
	 * Determines which processor to use based on type (html or pdf).
	 *
	 * @param Request            $request
	 * @param Advertisement|null $advertisement
	 *
	 * @return Request
	 */
	public static function handleJsonStorage( Request $request, Advertisement $advertisement = null ) {

		if ( array_key_exists( $request->get( 'type' ), self::$processors ) ) {
			$processor = self::$processors[ $request->get( 'type' ) ];
			$request   = self::$processor( $request, $advertisement );
		}

		return $request;
	}

	/**
	 * Processor for handling the storage of a html (text) advertisement.
	 *
	 * @param Request       $request
	 * @param Advertisement $advertisement
	 *
	 * @return Request
	 */
	public static function htmlProcessor( Request $request, Advertisement $advertisement ) {

		AdvertisementFileController::destroy( $advertisement );

		$request->merge( [
			'body' => [
				'html' => $request->get( 'html' ),
			],
		] );

		return $request;
	}

	/**
	 * Processor for handling the storage of a pdf (file) advertisement.
	 *
	 * @param Request       $request
	 * @param Advertisement $advertisement
	 *
	 * @return Request
	 */
	public static function pdfProcessor( Request $request, Advertisement $advertisement ) {
		if ( $request->file( 'file_upload' ) !== null ) {

			/**
			 * Delete the previously saved file from storage (if one exists)
			 */
			AdvertisementFileController::destroy( $advertisement );

			$request->merge( [
				'body' => [
					'pdf' => [
						'name'     => $request->file( 'file_upload' )->getClientOriginalName(),
						'storage'  => AdvertisementFileController::store( $request, $advertisement ),
						'size'     => $request->file( 'file_upload' )->getSize(),
						'ext'      => $request->file( 'file_upload' )->getClientOriginalExtension(),
						'mimeType' => $request->file( 'file_upload' )->getMimeType()
					]
				]
			] );
		} else {
			$request->merge( [
				'body' => $advertisement->body
			] );
		}

		return $request;
	}

}