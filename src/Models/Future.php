<?php

namespace Dixie\EloquentModelFuture\Models;

use Illuminate\Database\Eloquent\Model;
use Dixie\EloquentModelFuture\Collections\FutureCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class Future extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * The attributes that should be casted to Carbon dates.
     *
     * @var array
     */
    protected $dates = [
        'commit_at',
        'committed_at',
        'deleted_at',
        'approved_at',
    ];

    /**
     * Mass-assignable fields.
     *
     * @var array
     */
    protected $fillable = [
        'futureable_id', 'futureable_type',
        'commit_at', 'data', 'committed_at',
        'needs_approval'
    ];

    /**
     * Override the original Eloquent collection.
     *
     * @param Dixie\EloquentModelFuture\Collections\FutureCollection
     */
    public function newCollection(array $models = [])
    {
        return new FutureCollection($models);
    }

    /**
     * Get the relationship to the associated model,
     * for which the future has been planned.
     *
     * @return Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function futureable()
    {
        return $this->morphTo()
            ->with('futures');
    }

    /**
     * Get the relationship to user who created the future plan.
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(
            config('auth.providers.users.model'),
            'createe_user_id'
        );
    }

    /**
     * Get the relationship to user who created the future plan.
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver()
    {
        return $this->belongsTo(
            config('auth.providers.users.model'),
            'approvee_user_id'
        );
    }

    /**
     * Narrow the scope of a query to only include futures for given date.
     *
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param Carbon\Carbon $date
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate(Builder $query, Carbon $date)
    {
        return $query->whereDate('commit_at', $date->toDateString());
    }

    /**
     * Narrow the scope of a query to only include futures,
     * ranging from today to the given date.
     *
     * @param Builder $query
     * @param Carbon $date
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeUntilDate(Builder $query, Carbon $date)
    {
        return $query->whereDate('commit_at', '<=', $date->toDateString());
    }

    /**
     * Narrow the scope of a query to only include uncommitted futures.
     *
     * @param Builder $query
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeUncommitted(Builder $query)
    {
        return $query->whereNull('committed_at');
    }

    /**
     * Narrow the scope of a query to only include uncommitted futures.
     *
     * @param Builder $query
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommitted(Builder $query)
    {
        return $query->whereNotNull('committed_at');
    }

    /**
     * Narrow the scope of a query to only include unapproved futures.
     *
     * @param Builder $query
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnapproved(Builder $query)
    {
        return $query->whereNotNull('needs_approval')->whereNull('approvee_user_id');
    }

    /**
     * Narrow the scope of a query to only include approved futures.
     *
     * @param Builder $query
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved(Builder $query)
    {
        return $query->whereNotNull('approvee_user_id')->orWhereNull('needs_approval');
    }
}
