<?php

namespace Dixie\LaravelModelFuture;

use Illuminate\Database\Eloquent\Model;
use Dixie\LaravelModelFuture\Models\Future;
use Dixie\LaravelModelFuture\Contracts\ModelFuture;
use Carbon\Carbon;

class FuturePlanner
{
    protected $model;

    protected $attributes;

    protected $futures;

    public function __construct(ModelFuture $model)
    {
        $this->model = $model;
        $this->futures = $this->model->uncommittedFutures();
    }

    public function plan(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function for(Carbon $futureDate)
    {
        $future = new Future([
            'data' => $this->attributes,
            'commit_at' => $futureDate,
        ]);

        $future->futureable()
            ->associate($this->model)
            ->save();

        return $future;
    }

    public function getPlans()
    {
        return $this->futures->get();
    }

    public function getPlansFor(Carbon $futureDate)
    {
        return $this->futures->forDate($futureDate)->get();
    }

    public function getPlansUntil(Carbon $futureDate)
    {
        return $this->futures->untilDate($futureDate)->get();
    }

    public function hasAnyPlans()
    {
        return (bool) $this->getPlans()->count();
    }

    public function hasAnyPlansFor(Carbon $futureDate)
    {
        return (bool) $this->getPlansFor($futureDate)->count();
    }

    public function hasAnyPlansUntil(Carbon $futureDate)
    {
        return (bool) $this->getPlansUntil($futureDate)->count();
    }

    public function see(Carbon $futureDate)
    {
        return $this->getPlansFor($futureDate)->result();
    }

}
