<?php

namespace App\PR;

use App\Helper;
use App\Traits\PolicyTraits;
use Cache;
use Config;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MemorialPolicy
 *
 * @package App\PR
 * @author Jeffrey Simms <jmsimms@mun.ca>
 * @property int                                                                           $id
 * @property int                                                                           $tenant_id
 * @property int|null                                                                      $parent_id
 * @property int|null                                                                      $category_id
 * @property string|null                                                                   $title
 * @property array|null                                                                    $data
 * @property int|null                                                                      $updated_by
 * @property int|null                                                                      $was_published
 * @property int|null                                                                      $version
 * @property string|null                                                                   $status
 * @property \Illuminate\Support\Carbon|null                                               $approved_date
 * @property \Illuminate\Support\Carbon|null                                               $effective_date
 * @property \Illuminate\Support\Carbon|null                                               $review_date
 * @property \Illuminate\Support\Carbon|null                                               $revoke_date
 * @property \Illuminate\Support\Carbon|null                                               $created_at
 * @property \Illuminate\Support\Carbon|null                                               $updated_at
 * @property int|null                                                                      $sponsor_id
 * @property-read \App\PR\Category|null                                                    $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PR\MemorialPolicy[]        $children
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PR\MemorialDefinition[]    $definitions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PR\MemorialPolicyHistory[] $history
 * @property-read \App\PR\MemorialPolicy|null                                              $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PR\MemorialProcedure[]     $procedures
 * @property-read \App\PR\Sponsor|null                                                     $sponsor
 * @property-read \App\Tenant                                                              $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PR\MemorialPolicy[]        $workingCopies
 * @property-read \App\PR\MemorialPolicy                                                   $workingCopy
 * @method static MemorialPolicy whereApprovedDate( $value )
 * @method static MemorialPolicy whereCategoryId( $value )
 * @method static MemorialPolicy whereCreatedAt( $value )
 * @method static MemorialPolicy whereData( $value )
 * @method static MemorialPolicy whereEffectiveDate( $value )
 * @method static MemorialPolicy whereId( $value )
 * @method static MemorialPolicy whereParentId( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereReviewDate( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereRevokeDate( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereSponsorId( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereStatus( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereTenantId( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereTitle( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereUpdatedAt( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereUpdatedBy( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereVersion( $value )
 * @method static \Illuminate\Database\Eloquent\Builder|MemorialPolicy whereWasPublished( $value )
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PR\MemorialPolicy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PR\MemorialPolicy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PR\MemorialPolicy published()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PR\MemorialPolicy query()
 */
class MemorialPolicy extends Model implements PolicyInterface {

	use PolicyTraits;

	/**
	 * @var array
	 */
	protected $dates = [ 'approved_date', 'effective_date', 'review_date', 'revoke_date' ];

	/**
	 * @var array
	 */
	protected $fillable = [
		'category_id',
		'title',
		'approved_date',
		'effective_date',
		'review_date',
		'revoke_date',
		'sponsor_id'
	];

	/**
	 * @var array
	 */
	protected $casts = [ 'data' => 'array' ];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function history(): \Illuminate\Database\Eloquent\Relations\hasMany {
		return $this->hasMany( MemorialPolicyHistory::class, 'policy_id' )->orderBy( 'created_at' );
	}

	/**
	 * @return mixed
	 */
	public function category() {
		return $this->belongsTo( Category::class );
	}

	/**
	 * @return mixed
	 */
	public function sponsor() {
		return $this->belongsTo( Sponsor::class );
	}

	/**
	 * A policy may use many definitions
	 *
	 * @return mixed
	 */
	public function definitions() {
		return $this->hasMany( MemorialDefinition::class, 'policy_id' )->where( 'type', 'policy' )->orderBy( 'term' );
	}



	/**
	 * This method will store the MemorialPolicy data to the database.
	 *
	 * @param array $formData
	 *
	 * @return $this
	 */
	public function saveIt( Array $formData ) {
		$this->fill( $formData );
		$this->data = [
			'authority'          => $formData['authority'],
			'contact'            => $formData['contact'],
			'principle'          => $formData['principle'],
			'purpose'            => $formData['purpose'],
			'scope'              => $formData['scope'],
			'body'               => $formData['body'],
			'manual_definitions' => $formData['manual_definitions'],
			'documents'          => $formData['documents'],
			'approved_date'      => $formData['approved_date'],
			'review_date'        => $formData['review_date'],
			'effective_date'     => $formData['effective_date'],
			'category_id'        => $formData['category_id'],
			'sponsor_id'         => $formData['sponsor_id']
		];

		$this->save();

		return $this;
	}

	/**
	 * This method will return a tenant's policies matching the specified status.
	 *
	 * @param $status string|array
	 * @param $sort string
	 * @param $order string
	 *
	 * @return mixed
	 */
	public function getPolicies( $status, $sort = 'title', $order = 'asc' ) {
		if ( is_array( $status ) ) {
			$i = implode( '-', $status ) . '-' . $sort . '-' . $order;

			return Cache::remember( 'memorial-policies-' . $i, Config::get( 'cache.policy-index' ), function () use ( $status, $sort, $order ) {
				return self::whereIn( 'status', $status )->with( [
					'workingCopies',
					'procedures',
					'sponsor',
					'category'
				] )->orderBy( $sort, $order )->get();
			} );
		}

		return Cache::remember( 'memorial-policies-' . $status . '-' . $sort . '-' . $order, Config::get( 'cache.policy-index' ), function () use ( $status, $sort, $order ) {
			return self::where( 'status', $status )->with( [
				'workingCopies',
				'procedures',
				'sponsor',
				'category'
			] )->orderBy( $sort, $order )->get();
		} );
	}

	/**
	 * A helper function to determine if a policy is a working copy.
	 *
	 * @return bool
	 */
	public function isWorkingCopy() {
		return ( $this->status == 'working-copy' );
	}

	/**
	 * A helper function to determine if a policy is a draft.
	 *
	 * @return bool
	 */
	public function isDraft() {
		return ( $this->status == 'draft' );
	}

	/**
	 * A helper function to determine if a policy has any working copies.
	 *
	 * @return bool
	 */
	public function hasWorkingCopy() {
		return count( $this->workingCopies ) > 0;
	}

	/**
	 * A helper function to see if working copy can be created for a policy.
	 *
	 * @return bool
	 */
	public function canCreateWorkingCopy() {
		return ( $this->status === 'published' && count( $this->workingCopies ) === 0 );
	}

	/**
	 * A helper function to determine if a policy can be updated.
	 *
	 * @return bool
	 */
	public function canUpdate() {
		return ( $this->status === 'working-copy' || $this->status === 'draft' );
	}

	/**
	 * This method is to create a working copy of a policy.  A working copy can be a full copy or an empty copy, but
	 * each is related to the parent policy.
	 *
	 * @param bool $blank
	 * @param null $details
	 *
	 * @return MemorialPolicy|bool
	 */
	public function createWorkingCopy( $blank = false, $details = null ) {
		if ( $blank ) {
			$policy            = new self;
			$policy->parent_id = $this->id;
			$policy->status    = 'working-copy';
			$policy->save();

		} else {
			$policy            = $this->replicate();
			$policy->parent_id = $this->id;
			$policy->status    = 'working-copy';
			$policy->save();

			foreach ( $this->procedures as $procedure ) {
				$policy->procedures()->attach( $procedure );
			}

			foreach ( $this->definitions as $definition ) {
				$newDefinition            = $definition->replicate();
				$newDefinition->policy_id = $policy->id;
				$newDefinition->save();
			}
		}

		return $policy ?? false;
	}

	/**
	 * This method is to replace a policy with the details of another policy (generally this will only be a working
	 * copy).
	 *
	 * @param $policy
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function replaceWith( $policy ) {
		$this->title          = $policy->title;
		$this->data           = $policy->data;
		$this->approved_date  = $policy->approved_date;
		$this->review_date    = $policy->review_date;
		$this->effective_date = $policy->effective_date;
		$this->revoke_date    = $policy->revoke_date;
		$this->category_id    = $policy->category_id;
		$this->sponsor_id     = $policy->sponsor_id;
		$this->procedures()->detach();
		foreach ( $policy->procedures as $procedure ) {
			$this->procedures()->attach( $procedure );
		}
		if ( count( $this->definitions ) > 0 ) {
			foreach ( $this->definitions as $definition ) {
				$definition->delete();
			}
		}
		foreach ( $policy->definitions as $definition ) {
			$definition->replicate();
			$definition->policy_id = $this->id;
			$definition->save();
		}
		$this->save();

		return $this;

	}

	public function procedures() {
		return $this->belongsToMany( MemorialProcedure::class, 'memorial_procedure_for_policy', 'policy_id', 'procedure_id' )->orderBy( 'title' );
	}

	public function trackChanges( Model $compare ) {
		$checked = [];
		foreach ( $this->data as $i => $element ) {
			if ( isset( $compare->data[ $i ] ) && ! is_null( $compare->data[ $i ] ) ) {
				if ( $this->data[ $i ] != $compare->data[ $i ] ) {
					$checked[ $i ] = [
						'type' => 'change',
						'text' => Helper::htmlDiff( $this->data[ $i ], $compare->data[ $i ] )
					];
				}
			} else {
				$checked[ $i ] = [ 'type' => 'added', 'text' => $this->data[ $i ] ];
			}
		}

		return $checked;
	}

	public function validateForPublish() {
		$missingFields = [];
		if ( trim( $this->title ) == '' ) {
			$missingFields[] = [
				'field'   => 'title',
				'message' => 'Your policy must have a title'
			];
		}

		if ( ! array_key_exists( 'purpose', $this->data ) || trim( $this->data['purpose'] ) == '' ) {
			$missingFields[] = [
				'field'   => 'purpose',
				'message' => 'Your policy must have a documented purpose'
			];
		}

		if ( ! array_key_exists( 'scope', $this->data ) || trim( $this->data['scope'] ) == '' ) {
			$missingFields[] = [
				'field'   => 'scope',
				'message' => 'Your policy must have a documented scope'
			];
		}

		if ( ! array_key_exists( 'body', $this->data ) || trim( $this->data['body'] ) == '' ) {
			$missingFields[] = [
				'field'   => 'body',
				'message' => 'Your policy must have a documented body'
			];
		}

		if ( is_null( $this->category ) ) {
			$missingFields[] = [
				'field'   => 'category',
				'message' => 'Your policy must have a category selected'
			];
		}

		if ( ! array_key_exists( 'authority', $this->data ) || trim( $this->data['authority'] ) == '' ) {
			$missingFields[] = [
				'field'   => 'authority',
				'message' => 'Your policy must have a authority specified'
			];
		}

		if ( is_null( $this->sponsor ) ) {
			$missingFields[] = [
				'field'   => 'sponsor',
				'message' => 'Your policy must have a sponsor selected'
			];
		}

		if ( ! array_key_exists( 'contact', $this->data ) || trim( $this->data['contact'] ) == '' ) {
			$missingFields[] = [
				'field'   => 'contact',
				'message' => 'Your policy must have a contact specified'
			];
		}

		return $missingFields;

	}

	public function canRevoke() {
		return ( $this->status == 'published' && count( $this->workingCopies ) == 0 );
	}

	public function scopePublished( $query ) {
		return $query->where( 'status', 'published' );
	}

}