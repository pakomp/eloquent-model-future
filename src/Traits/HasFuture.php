<?php

namespace Dixie\EloquentModelFuture\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Dixie\EloquentModelFuture\Models\Future;
use Dixie\EloquentModelFuture\FuturePlanner;

trait HasFuture
{
    /**
     * Defines the relationship between the model and its futures.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function futures()
    {
        return $this->morphMany(
            Future::class,
            'futures',
            'futureable_type',
            'futureable_id'
        );
    }

    /**
     * Defines the relationship between the model and its uncommitted futures.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function uncommittedFutures()
    {
        return $this->futures()->whereNull('committed_at');
    }

    /**
     * Defines the relationship between the model and its unapproved futures.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function unapprovedFutures()
    {
        return $this->futures()->whereNotNull('needs_approval')->whereNull('approvee_user_id');
    }

    /**
     * Start planning the future of a model
     *
     * @return \Dixie\EloquentModelFuture\FuturePlanner
     */
    public function future()
    {
        return new FuturePlanner($this);
    }

    /**
     * Commit to the presented result of the model
     *
     * @return boolean
     */
    public function commit()
    {
        $this->future()->getPlansUntil(Carbon::now())
            ->each([$this, 'commitFuturePlan']);

        return $this->save();
    }

    /**
     * Commit the given future.
     *
     * @param boolean
     */
    public function commitFuturePlan(Future $futurePlan)
    {
        $futurePlan->committed_at = Carbon::now();

        return $futurePlan->save();
    }

    /**
     * Approve to the presented result of the model
     *
     * @return boolean
     */
    public function approve()
    {
        $this->future()->getPlansUntil(Carbon::now())
            ->each([$this, 'approveFuturePlan']);

        return $this->save();
    }

    /**
     * Approve the given future.
     *
     * @param boolean
     */
    public function approveFuturePlan(Future $futurePlan)
    {
        $futurePlan->approved_at = Carbon::now();
        $futurePlan->approver()->associate(Auth::user());

        return $futurePlan->save();
    }
}
