<?php

namespace App\Http\Controllers;

use App\Application;
use App\ApplyOnline\Report;
use App\Assignment;
use App\Competition;
use App\Exports\ApplicationsExport;
use App\Http\Resources\CompetitionResource;
use App\Tenant;
use App\User;
use Carbon\Carbon;
use CWS\Glow\FlashMessenger\FlashMessenger;
use Gate;
use Illuminate\Http\Request;
use Log;
use Maatwebsite\Excel\Facades\Excel;

class CompetitionController extends Controller {

	/**
	 * Competition Listing
	 * Get all Competitions with their associated Advertisements and Applications.
	 *
	 * Conditional Clauses are used because different results are shown to users of different roles.
	 * A competition assignee sees filtered results compared to a competition manager.
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {

		$competitions = Competition::with( [
			'advertisements',
			'applications' => function ( $query ) {
				/** @var Application $query */
				$query->submitted()
				      ->when( Gate::denies( 'manage', Competition::class ), function ( $query ) {
					      /** @var Application $query */
					      $query->excludeFlagged();
				      } );
			}
		] )->when( Gate::denies( 'manage', Competition::class ), function ( $query ) {
			/** @var Application $query */
			$query->whereHas( 'assignments', function ( $query ) {
				/** @var Assignment $query */
				$query->where( 'user_id', auth()->user()->id );
			} );
		} )->get();

		return view( 'competitions.index', compact( 'competitions' ) );
	}

	/**
	 * Get the Applications on the Competition
	 * If a user has assignment role of Application Reviewer - exclude flagged applications
	 *
	 * Get all the application sums for the various statuses.
	 *
	 * https://laravel.com/docs/5.5/eloquent-relationships#constraining-eager-loads
	 * @param Competition $competition
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function applications( Competition $competition ) {

		$this->authorize( 'accessCompetitionApplications', $competition );

		/** @var User $user */
		$user = auth()->user();

		$competitionApplications = $competition
			->advertisements()
			->with( [
				'applications' => function ( $query ) use ( $user ) {
					/** @var Application $query */
					$query->when( ! $user->hasRole( 'competition_manager' ), static function ( $query ) {
						/** @var Application $query */
						return $query->excludeFlagged();
					} )->submitted();
				}
			] )
			->get();

		$statusOptions = Application::getStatusOptions();
		$flagOptions   = Tenant::current()->applicationFlags();
		$leaderboard   = Report::getCompetitionLeaderboard( $competition );

		return view( 'applications.index', compact( 'competitionApplications', 'competition', 'statusOptions', 'flagOptions', 'leaderboard' ) );

	}

	/**
	 * @param Competition $competition
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function edit( Competition $competition ) {

		$resultOptions = Competition::getResultOptions();
		$statusOptions = Competition::getStatusOptions();

		return view( 'competitions.edit', compact( 'competition', 'resultOptions', 'statusOptions' ) );
	}

	/**
	 * @param Competition $competition
	 * @param Request     $request
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update( Competition $competition, Request $request ) {

		$resultOptions = Competition::getResultOptions();
		$statusOptions = Competition::getStatusOptions();

		if ( request( 'status' ) !== 'open' && $competition->advertisements()->where( 'close_date', '>', Carbon::now() )->exists() ) {
			FlashMessenger::error( 'Prevented Status Change', '<br />Competition has open advertisement(s).' );

			return redirect()->route( 'competitions.edit', compact( 'competition', 'resultOptions', 'statusOptions' ) );
		}

		$competition->fill( $request->all() );
		$competition->save();

		FlashMessenger::success( 'Complete!', "Competition <span id='create-competition-number'>$competition->number</span> updated. ", true );

		return redirect()->route( 'competitions.index' );
	}

	/**
	 * @param Request $request
	 *
	 * @return CompetitionResource
	 */
	public function fetch( Request $request ) {
		$competition_number = $request->get( 'comp_no' );

		$competitions = Competition::whereNumber( $competition_number )->get();

		return new CompetitionResource( $competitions );
	}

	/**
	 * @param Competition $competition
	 *
	 * @param Request     $request
	 *
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function close( Competition $competition, Request $request ) {
		$this->authorize( 'manage', Competition::class );

		try {
			$competition->status    = 'closed';
			$competition->closed_at = $request->get( 'closed_at' );
			$competition->result    = $request->get( 'result' );
			$competition->save();
		} catch ( \Exception $e ) {
			Log::error( $e->getMessage() );

			return response( '<strong>Something went wrong!</strong> Unable to close competition.', 500 );
		}

		return response( '<strong>Complete!</strong> The competition is now closed.' );

	}

	/**
	 * View Competition index showing only ACTIVE (OPEN) Competitions
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function active() {
		$competitions = Competition::active();

		$competitions->each( function ( $item ) {
			$item->load( [
				'applications' => function ( $query ) {
					$query->submitted();
				}
			] );
		} );

		return view( 'competitions.index', compact( 'competitions' ) );
	}

	/**
	 * View Competition index showing only INACTIVE (OPEN) Competitions
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function inactive() {
		$competitions = Competition::inactive();

		$competitions->each( function ( $item ) {
			$item->load( [
				'advertisements',
				'applications' => function ( $query ) {
					$query->submitted();
				}
			] );
		} );

		return view( 'competitions.index', compact( 'competitions' ) );
	}

	/**
	 * View Competition index showing only PENDING Competitions
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function pending() {
		$competitions = Competition::pending();

		$competitions->each( function ( $item ) {
			$item->load( [
				'applications' => function ( $query ) {
					$query->submitted();
				}
			] );
		} );

		return view( 'competitions.index', compact( 'competitions' ) );
	}

	/**
	 * Export Applicants to Excel File
	 *
	 * @param Competition $competition
	 *
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	public function exportApplications( Competition $competition ) {

		$this->authorize( 'export', $competition );

		return Excel::download( new ApplicationsExport( $competition ), $competition->number . '_applicants.xlsx' );
	}


}
