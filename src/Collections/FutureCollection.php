<?php

namespace Dixie\EloquentModelFuture\Collections;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class FutureCollection extends EloquentCollection
{
    /**
     * Get the original model state
     * 
     * @return Dixie\EloquentModelFuture\Contracts\ModelFuture
     */
    public function original()
    {
        $model = $this->first()->futureable;
        return $model;
    }

    /**
     * Get the models for each change.
     * @return Dixie\EloquentModelFuture\Contracts\ModelFuture
     */
    public function models($include_org=false)
    {
        $res = $this->map(function ($item) {
            $model = (clone $item->futureable)->forceFill($item->data);
            return $model;
        });
        if($include_org) {
            $res->prepend($this->original());
        }
        return $res;
    }

    /**
     * Gets the model back with all the future data filled.
     *
     * @return Dixie\EloquentModelFuture\Contracts\ModelFuture
     */
    public function result()
    {
        $model = $this->first()->futureable;

        return $this->reduce(function ($carry, $item) {
            return $carry->forceFill($item->data);
        }, $model);
    }

    /**
     * Gets a list of all fields that would change, with both before and after.
     *
     * @return Illuminate\Support\Collection
     */
    public function resultDiff()
    {
        return $this->map(function ($item) {
            $before = $item->futureable->first(array_keys($item->data));

            return [
                'before' => json_encode($before->toArray()),
                'after' => json_encode($item->data),
                'commit_at' => $item->commit_at,
            ];
        });
    }
}
