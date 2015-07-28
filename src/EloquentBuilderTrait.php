<?php

namespace Optimus\Api\Controller;

use Illuminate\Database\Eloquent\Builder;

trait EloquentBuilderTrait {

    private function applyResourceOptions(Builder $query, array $options = [])
    {
        if (!empty($options)) {
            $query->with($options['includes']['includes']);

            if (!is_null($options['sort'])) {
                $query->orderBy($options['sort']);
            }
            if (!is_null($options['limit'])) {
                $query->limit($options['limit']);
            }
            if (!is_null($options['page'])) {
                $query->offset($options['page']*$options['limit']);
            }
        }

        return $query;
    }

}
