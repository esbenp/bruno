<?php

namespace Optimus\Api\Controller;

use DB;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

trait EloquentBuilderTrait
{
    /**
     * Apply resource options to a query builder
     * @param  Builder $query
     * @param  array  $options
     * @return Builder
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

            if (isset($filter_groups)) {
                $this->applyFilterGroups($query, $filter_groups);
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

    private function applyFilterGroups(Builder $query, array $filterGroups = [])
    {
        foreach ($filterGroups as $group) {
            $filters = $group['filters'];

            $query->where(function (Builder $query) use ($filters) {
                foreach ($filters as $filter) {
                    $this->applyFilter($query, $filter);
                }
            });
        }
    }

    private function applyFilter(Builder $query, array $filter)
    {
        if ($filter['value'] === 'null' || $filter['value'] === '') {
            $method = $filter['not'] ? 'NotNull' : 'Null';

            call_user_func([$query, $method], $filter['key']);
        } else {
            switch($filter['operator']) {
                case 'ct':
                    $query->where(
                        $filter['key'],
                        $filter['not'] ? 'NOT LIKE' : 'LIKE',
                        '%'.$filter['value'].'%'
                    );
                    break;
                case 'eq':
                default:
                    $operator = $filter['not'] ? '!=' : '=';
                    $query->where($filter['key'], $operator, $filter['value']);
                    break;
            }
        }
    }
}
