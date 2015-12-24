<?php

namespace Optimus\Api\Controller;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

trait EloquentBuilderTrait
{
    /**
     * Apply resource options to a query builder
     * @param  Builder $query
     * @param  array  $options
     * @return Illuminate\Database\Eloquent\Builder
     */
    private function applyResourceOptions(Builder $query, array $options = [])
    {
        if (!empty($options)) {
            extract($options);

            if (isset($includes)) {
                if (!is_array($includes)) {
                    throw InvalidArgumentException('Includes should be an array.');
                }

                $query->with($includes);
            }

            if (isset($sort)) {
                $query->orderBy($sort);
            }

            if (isset($limit)) {
                $query->limit($limit);
            }

            if (isset($page)) {
                $query->offset($page*$limit);
            }
        }

        return $query;
    }
}
