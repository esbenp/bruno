<?php

namespace Optimus\Api\Controller;

use Illuminate\Database\Eloquent\Builder;

trait EloquentBuilderTrait {

    private function applyResourceOptions(Builder $query, array $options = [])
    {
        if (!empty($options)) {
            $query->with($options['includes']);

            if (isset($options['sort'])) {
                $query->orderBy($options['sort']);
            }
            if (isset($options['limit'])) {
                $query->limit($options['limit']);
            }
            if (isset($options['page'])) {
                $query->offset($options['page']*$options['limit']);
            }
        }

        return $query;
    }

}
