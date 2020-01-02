<?php

namespace App\Http\Controllers;

use App\Application;
use App\Http\Requests\ApplyRequest;
use App\Advertisement;
use App\Mail\ApplicationReceived;
use App\Profile;
use App\Opportunity;
use App\User;
use CWS\Glow\FlashMessenger\FlashMessenger;
use Gate;
use Mail;

class ApplyController extends Controller {

	/**
	 * Apply on an Advertisement
	 *
	 * @param Advertisement $advertisement
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function apply( Advertisement $advertisement ) {

		/** @var User $user */
		$user = auth()->user();

		abort_if( $advertisement->isClosed(), 403, 'Sorry, that competition is now closed.' );

		if ( $user->hasAlreadyApplied( $advertisement ) ) {
			FlashMessenger::warning(
				'Application already exists!',
				'<br />You may only submit one application per competition.'
			);

			return redirect()->route( 'my-applications.index' );
		}

		abort_unless( Gate::allows( 'apply', $advertisement ),
			403,
			'Sorry, only ' . $advertisement->category . ' applicants may apply.'
		);

		$application = $this->generateDraftApplication( $advertisement );

		return redirect()->route( 'apply.application', compact( 'application' ) );

	}

	/**
	 * Generate a draft application
	 *
	 * Associate with a User
	 * Associate with an Advertisement
	 *
	 * If user profile exists, replicate any Profile Attachments and associate with draft Application
	 * https://laravel.com/docs/5.5/eloquent-relationships#updating-belongs-to-relationships
	 *
	 * Redirect to apply form and load draft Application.
	 *
	 * @param Advertisement $advertisement
	 *
	 * @return Application
	 */
	protected function generateDraftApplication( Advertisement $advertisement ) {
		/* @var $user User */
		$user = auth()->user();

		$application = new Application( [
			'status' => 'draft',
		] );
		$application->advertisement()->associate( $advertisement );
		$application->user()->associate( $user );
		$application->save();

		if ( $user->profile()->exists() ) {
			$application->applicant = $user->profile->profile;
			$application->save();

			AttachmentController::useProfileAttachmentsOnApplication( $application );
		}

		return $application;

	}

	/**
	 * Show the application form page
	 *
	 * @param Application $application
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function application( Application $application ) {

		$this->authorize( 'edit', $application );
		$this->authorize( 'submit', $application );

		/* @var $user User */
		$user = auth()->user();
		$selected_opportunities = collect();
		if ( $user->profile()->exists() ) {
			$selected_opportunities = $user->profile->opportunities;
		}
		$opportunities = Opportunity::whereSelectable( true )->get()->sort();

		return view( 'apply.self.form', compact( 'application', 'opportunities', 'selected_opportunities' ) );

	}

	/**
	 * Submit the Application
	 *
	 * @param Application  $application
	 * @param ApplyRequest $request
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function submit( Application $application, ApplyRequest $request ) {

		$this->authorize( 'submit', $application );

		/* @var $user User */
		$user = auth()->user();

		$application->update( [
			'status'      => 'under-review',
			'email'       => $request->get( 'email' ),
			'applicant'   => $request->except( [
				'_token',
				'certification',
				'skills'
			] ),
			'acquisition' => 'online',
		] );

		if ( $user->profile()->doesntExist() ) {
			MyProfileController::generateFromApplication( $application );
		}
		if ( $user->profile()->exists() ) {

			/* @var $profile Profile */
			$profile = $user->profile;

			$profile->update( [
				'profile' => $request->except( [
					'_token',
					'certification',
					'opportunities',
				] )
			] );

			$profile->opportunities()->sync( $request->get( 'opportunities' ) );
		}

		AttachmentController::submitApplicationAttachments( $application );

		Mail::to( $application->applicant['email'] )->send( new ApplicationReceived( $application ) );

		return redirect()->route( 'apply.received' );

	}

}
