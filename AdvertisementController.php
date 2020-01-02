<?php

namespace App\Http\Controllers;

use App\ApplyOnline\AdvertisementProcessor;
use App\Channel;
use App\Competition;
use App\Http\Requests\AdvertisementRequest;
use App\Advertisement;
use App\Tenant;
use Carbon\Carbon;
use Log;
use CWS\Glow\FlashMessenger\FlashMessenger;
use DB;
use App\Http\Resources\AdvertisementResource;
use Illuminate\Http\Request;
use TenantManager;

class AdvertisementController extends Controller {

	/**
	 * Return the view for the display of resource in a listing.
	 *
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index( Request $request ) {
		$view   = array_key_exists( 'view', $request->all() ) ? request( 'view' ) : 'all';
		$sort   = array_key_exists( 'sort', $request->all() ) ? request( 'sort' ) : 1;
		$order  = array_key_exists( 'order', $request->all() ) ? request( 'order' ) : 'desc';
		$search = array_key_exists( 'search', $request->all() ) ? request( 'search' ) : '';

		return view( 'advertisements.index', compact( 'view', 'sort', 'order', 'search' ) );
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		$categories = Tenant::current()->advertisementCategories();
		$scopes     = Tenant::current()->advertisementScopes();
		$channels   = Channel::whereSelectable( true )->get()->sort();

		return view( 'advertisements.create', compact( 'categories', 'scopes', 'channels' ) );
	}

	/**
	 * Fetch a listing of the resource to be used via an ajax request
	 * Generates a formatted JSON object array
	 *
	 * @param Request $request
	 *
	 * @param array   $advertisements
	 *
	 * @return AdvertisementResource
	 */
	public function fetch( Request $request, $advertisements = [] ) {
		if ( array_key_exists( 'all', $request->all() ) ) {
			$advertisements = Advertisement::with( 'competition' )->get();
		}
		if ( array_key_exists( 'open', $request->all() ) ) {
			$advertisements = Advertisement::with( 'competition' )->open()->get();
		}
		if ( array_key_exists( 'upcoming', $request->all() ) ) {
			$advertisements = Advertisement::with( 'competition' )->upcoming()->get();
		}
		if ( array_key_exists( 'closed', $request->all() ) ) {
			$days           = ( array_key_exists( 'days', $request->all() ) )
				? $request->get( 'days' )
				: config( 'careers.advertisement.scope.closed' );
			$advertisements = Advertisement::with( 'competition' )->closed( $days )->get();
		}
		if ( array_key_exists( 'archive', $request->all() ) ) {
			$advertisements = Advertisement::archive( $request->all() );
		}
		foreach ( $advertisements as $advert ) {
			$advert['is_closeable']    = $advert->isCloseable();
			$advert['is_html']         = $advert->isHTML();
			$advert['is_pdf']          = $advert->isPDF();
			$advert['is_open']         = $advert->isOpen();
			$advert['status']          = $advert->status();
			$advert['public_url']      = $advert->public_url;
			$advert['apply_url']       = $advert->apply_url;
			$advert['type']            = $advert->type;
			$advert['show']            = route( 'advertisements.show', $advert );
			$advert['show_file']       = route( 'advertisements.show.file', $advert );
			$advert['edit']            = route( 'advertisements.edit', $advert );
			$advert['duplicate']       = route( 'advertisements.duplicate', $advert );
			$advert['destroy']         = route( 'advertisements.destroy', $advert );
			$advert['close']           = route( 'advertisements.close', $advert );
			$advert['proxy_apply']     = route( 'apply.proxy.application.draft', $advert );
			$advert['open_date_extra'] = [
				'datetime'          => $advert->open_date,
				'timestamp'         => Carbon::parse( $advert->open_date )->timestamp,
				'formatted'         => Carbon::parse( $advert->open_date )->toFormattedDateString(),
				'dayDateTimeString' => Carbon::parse( $advert->open_date )->toDayDateTimeString(),
				'pretty'            => Carbon::parse( $advert->open_date )->format( 'j F Y<\b\r>g:i A' )
			];
			if ( $advert->close_date !== null ) {
				$advert['close_date_extra'] = [
					'datetime'          => $advert->close_date,
					'timestamp'         => Carbon::parse( $advert->close_date )->timestamp,
					'formatted'         => Carbon::parse( $advert->close_date )->toFormattedDateString(),
					'dayDateTimeString' => Carbon::parse( $advert->close_date )->toDayDateTimeString(),
					'pretty'            => Carbon::parse( $advert->close_date )->format( 'j F Y<\b\r>g:i A' )
				];
			} else {
				$advert['close_date_extra'] = [
					'datetime'          => null,
					'timestamp'         => null,
					'formatted'         => null,
					'dayDateTimeString' => null,
					'pretty'            => '<em>remains open <br /> until closed</em>',
				];
			}
		}

		return new AdvertisementResource( $advertisements );

	}

	/**
	 * Close the Job Posting
	 *
	 * Fired via ajax request (from listing page)
	 *
	 * @param Advertisement $advertisement
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function close( Advertisement $advertisement ) {
		try {
			$advertisement->close_date = Carbon::now();
			$advertisement->save();
		} catch ( \Exception $e ) {
			Log::error( $e->getMessage() );

			return response( '<strong>Something went wrong!</strong> Unable to close advertisement.', 500 );
		}

		return response( '<strong>Complete!</strong> The advertisement is now closed.' );
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param AdvertisementRequest $request
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \Exception
	 */
	public function store( AdvertisementRequest $request ) {
		/** @var $advertisement Advertisement */
		DB::beginTransaction();
		try {
			if ( $request->exists( 'competition_number' ) ) {
				$competition = Competition::whereNumber( request( 'competition_number' ) )->first();
				if ( $competition === null ) {
					FlashMessenger::error( 'Something went wrong!', 'Competition does not exist.', false );

					return redirect()->back()->withInput( request()->all() );
				}
			} else {
				$competition = new Competition();
				$competition->save();
				$competition->update( [
					'number' => strtoupper( TenantManager::getName() ) . str_pad( $competition->id, 5, 0, STR_PAD_LEFT ),
					'status' => 'open'
				] );
			}

			$advertisement = new Advertisement();
			$advertisement->competition()->associate( $competition );
			$advertisement->save();

			self::save( $request, $advertisement );
		} catch ( \Exception $e ) {
			DB::rollback();
			Log::error( $e->getMessage() );
			FlashMessenger::error( 'Something went wrong!', 'Unable to create advertisement.', false );

			return redirect()->back()->withInput( request()->all() );
		}
		DB::commit();

		FlashMessenger::success( 'Complete!', "Advertisement created and assigned to competition $advertisement->competition_number.", false );

		return redirect()->route( 'advertisements.index', [ 'search' => $advertisement->competition_number ] );
	}

	/**
	 * Save Advertisement
	 * Runs on create and edit
	 *
	 * @param AdvertisementRequest $request
	 * @param Advertisement        $advertisement
	 */
	protected static function save( AdvertisementRequest $request, Advertisement $advertisement ) {
		$request = AdvertisementProcessor::handleJsonStorage( $request, $advertisement );
		$advertisement->fill( $request->all() );
		$advertisement->channels()->sync( $request->get( 'channels' ) );
		$advertisement->save();
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param AdvertisementRequest $request
	 * @param Advertisement        $advertisement
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \Exception
	 */
	public function update( AdvertisementRequest $request, Advertisement $advertisement ) {
		DB::beginTransaction();
		try {

			if ( $request->exists( 'competition_number' ) ) {
				$competition = Competition::whereNumber( request( 'competition_number' ) )->first();
				if ( $competition === null ) {
					FlashMessenger::error( 'Something went wrong!', 'Competition does not exist.', false );

					return redirect()->back()->withInput( request()->all() );
				}
			} else {
				$competition = new Competition();
				$competition->save();
				$competition->update( [
					'number' => strtoupper( TenantManager::getName() ) . str_pad( $competition->id, 5, 0, STR_PAD_LEFT )
				] );
			}

			$advertisement->competition()->associate( $competition );
			self::save( $request, $advertisement );
		} catch ( \Exception $e ) {
			DB::rollback();
			Log::error( $e->getMessage() );
			FlashMessenger::error( 'Something went wrong!', 'Unable to modify advertisement.', false );

			return redirect()->back()->withInput( request()->all() );
		}
		DB::commit();

		FlashMessenger::success( 'Complete!', "Advertisement modified.", false );

		return redirect()->route( 'advertisements.edit', $advertisement );

	}

	/**
	 * Display the specified resource.
	 *
	 * @param \App\Advertisement $advertisement
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function show( Advertisement $advertisement ) {
		if ( $advertisement->type !== 'html' ) {
			return redirect()->route( 'advertisements.show.file', $advertisement );
		}

		return view( 'advertisements.show', compact( 'advertisement' ) );
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param \App\Advertisement $advertisement
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function edit( Advertisement $advertisement ) {
		$categories = Tenant::current()->advertisementCategories();
		$scopes     = Tenant::current()->advertisementScopes();
		$channels   = Channel::whereSelectable( true )->get()->sort();

		return view( 'advertisements.edit', compact( 'advertisement', 'categories', 'scopes', 'channels' ) );
	}

	/**
	 * @param Advertisement $advertisement
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function duplicate( Advertisement $advertisement ) {
		$categories = Tenant::current()->advertisementCategories();
		$scopes     = Tenant::current()->advertisementScopes();
		$channels   = Channel::whereSelectable( true )->get()->sort();

		return view( 'advertisements.duplicate', compact( 'advertisement', 'categories', 'scopes', 'channels' ) );
	}

	/**
	 * Destroy the specified resource
	 *
	 * A Competition must have at least one Advertisement associated with it.
	 *      Delete any assignments to the competition
	 *      Delete the competition
	 *
	 * Delete the associated file directory from storage (advertisement + applications)
	 *
	 * @param Advertisement $advertisement
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function destroy( Advertisement $advertisement ) {
		try {
			DB::beginTransaction();
			$advertisement->delete();
			$advertisement->channels()->detach();

			AdvertisementFileController::destroyJobFiles( $advertisement );

		} catch ( \Exception $e ) {
			DB::rollback();
			Log::error( $e->getMessage() );

			return response( '<strong>Something went wrong!</strong> Unable to delete advertisement.', 500 );
		}
		DB::commit();

		return response( '<strong>Task Completed!</strong> You have deleted the advertisement.' );
	}

	/**
	 * Archive section
	 * Uses Query Scope (scopeFilter) on Job Model
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function archive() {
		$archiveMenu = ( new Advertisement )->archiveMenu();

		return view( 'advertisements.archive', compact( 'archiveMenu' ) );
	}

	/**
	 * Redirect careers route to advertisements route
	 *
	 * @param $advertisement
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function careersRouteRedirect( $advertisement ) {
		return redirect()->route( 'advertisements.show', $advertisement );
	}
}
