<?php

namespace Optimus\Bruno;

use DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait EloquentBuilderTrait
{
    /**
     * Array of joins
     * @var array
     */
    protected $brunoJoins = [];

    /**
     * Apply resource options to a query builder
     * @param  Builder $queryBuilder
     * @param  array  $options
     * @return Builder
     */
    protected function applyResourceOptions(Builder $queryBuilder, array $options = [])
    {
        if (empty($options)) {
            return $queryBuilder;
        }

        extract($options);

        if (isset($includes)) {
            if (!is_array($includes)) {
                throw new InvalidArgumentException('Includes should be an array.');
            }

            $queryBuilder->with($includes);
        }

        if (isset($filter_groups)) {
            $filterJoins = $this->applyFilterGroups($queryBuilder, $filter_groups);
        }

        if (isset($sort)) {
            if (!is_array($sort)) {
                throw new InvalidArgumentException('Sort should be an array.');
            }

            if (!isset($filterJoins)) {
                $filterJoins = [];
            }

            $sortingJoins = $this->applySorting($queryBuilder, $sort, $filterJoins);
        }

        if (isset($limit)) {
            $queryBuilder->limit($limit);
        }

        if (isset($start)) {
            $queryBuilder->offset($start);
        } else if (isset($page)) {
            if (!isset($limit)) {
                throw new InvalidArgumentException('A limit is required when using page.');
            }

            $queryBuilder->offset($page * $limit);
        }

        if (isset($distinct)) {
            $queryBuilder->distinct();
        }

        return $queryBuilder;
    }

    /**
     * @param Builder $queryBuilder
     * @param array $filterGroups
     * @param array $previouslyJoined
     * @return array
     */
    protected function applyFilterGroups(Builder $queryBuilder, array $filterGroups = [], array $previouslyJoined = [])
    {
        $joins = [];
        foreach ($filterGroups as $group) {
            $or = $group['or'];
            $filters = $group['filters'];

            $queryBuilder->where(function (Builder $query) use ($filters, $or, &$joins) {
                foreach ($filters as $filter) {
                    $this->applyFilter($query, $filter, $or, $joins);
                }
            });
        }

        foreach(array_diff($joins, $previouslyJoined) as $join) {
            $this->joinRelatedModelIfExists($queryBuilder, $join);
        }

        return $joins;
    }

    /**
     * @param Builder $queryBuilder
     * @param array $filter
     * @param bool|false $or
     * @param array $joins
     */
    protected function applyFilter(Builder $queryBuilder, array $filter, $or = false, array &$joins)
    {
        // Destructure Shorthand Filtering Syntax if filter is Shorthand
        if (! array_key_exists('key', $filter) && count($filter) >= 3) {
            $filter = [
                'key'      => (array_key_exists(0, $filter) ? $filter[0] : null),
                'operator' => (array_key_exists(1, $filter) ? $filter[1] : null),
                'value'    => (array_key_exists(2, $filter) ? $filter[2] : null),
                'not'      => (array_key_exists(3, $filter) ? $filter[3] : null),
            ];
        }

        // $value, $not, $key, $operator
        extract($filter);

        $dbType = $queryBuilder->getConnection()->getDriverName();

        $table = $queryBuilder->getModel()->getTable();

        if ($value === 'null' || $value === '') {
            $method = $not ? 'WhereNotNull' : 'WhereNull';

            call_user_func([$queryBuilder, $method], sprintf('%s.%s', $table, $key));
        } else {
            $method = filter_var($or, FILTER_VALIDATE_BOOLEAN) ? 'orWhere' : 'where';
            $clauseOperator = null;
            $databaseField = null;

            switch($operator) {
                case 'ct':
                case 'sw':
                case 'ew':
                    $valueString = [
                        'ct' => '%'.$value.'%', // contains
                        'ew' => '%'.$value, // ends with
                        'sw' => $value.'%' // starts with
                    ];

                    $castToText = (($dbType === 'pgsql') ? 'TEXT' : 'CHAR');
                    $databaseField = DB::raw(sprintf('CAST(%s.%s AS ' . $castToText . ')', $table, $key));
                    $clauseOperator = ($not ? 'NOT':'') . (($dbType === 'pgsql') ? 'ILIKE' : 'LIKE');
                    $value = $valueString[$operator];
                    break;
                case 'eq':
                default:
                    $clauseOperator = $not ? '!=' : '=';
                    break;
                case 'gt':
                    $clauseOperator = $not ? '<' : '>';
                    break;
                case 'gte':
                    $clauseOperator = $not ? '<' : '>=';
                    break;
                case 'lte':
                    $clauseOperator = $not ? '>' : '<=';
                    break;
                case 'lt':
                    $clauseOperator = $not ? '>' : '<';
                    break;
                case 'in':
                    if ($or === true) {
                        $method = $not === true ? 'orWhereNotIn' : 'orWhereIn';
                    } else {
                        $method = $not === true ? 'whereNotIn' : 'whereIn';
                    }
                    $clauseOperator = false;
                    break;
                case 'bt':
                    if ($or === true) {
                        $method = $not === true ? 'orWhereNotBetween' : 'orWhereBetween';
                    } else {
                        $method = $not === true ? 'whereNotBetween' : 'whereBetween';
                    }
                    $clauseOperator = false;
                    break;
            }

            // If we do not assign database field, the customer filter method
            // will fail when we execute it with parameters such as CAST(%s AS TEXT)
            // key needs to be reserved
            if (is_null($databaseField)) {
                $databaseField = sprintf('%s.%s', $table, $key);
            }

            $customFilterMethod = $this->hasCustomMethod('filter', $key);
            if ($customFilterMethod) {
                call_user_func_array([$this, $customFilterMethod], [
                    $queryBuilder,
                    $method,
                    $clauseOperator,
                    $value,
                    $clauseOperator // @deprecated. Here for backwards compatibility
                ]);

                // column to join.
                // trying to join within a nested where will get the join ignored.
                $joins[] = $key;
            } else {
                // In operations do not have an operator
                if (in_array($operator, ['in', 'bt'])) {
                    call_user_func_array([$queryBuilder, $method], [
                        $databaseField, $value
                    ]);
                } else {
                    call_user_func_array([$queryBuilder, $method], [
                        $databaseField, $clauseOperator, $value
                    ]);
                }
            }
        }
    }

    /**
     * @param Builder $queryBuilder
     * @param array $sorting
     * @param array $previouslyJoined
     * @return array
     */
    protected function applySorting(Builder $queryBuilder, array $sorting, array $previouslyJoined = [])
    {
        $joins = [];
        foreach($sorting as $sortRule) {
            if (is_array($sortRule)) {
                $key = $sortRule['key'];
                $direction = mb_strtolower($sortRule['direction']) === 'asc' ? 'ASC' : 'DESC';
            } else {
                $key = $sortRule;
                $direction = 'ASC';
            }

            $customSortMethod = $this->hasCustomMethod('sort', $key);
            if ($customSortMethod) {
                $joins[] = $key;

                call_user_func([$this, $customSortMethod], $queryBuilder, $direction);
            } else {
                $queryBuilder->orderBy($key, $direction);
            }
        }

        foreach(array_diff($joins, $previouslyJoined) as $join) {
            $this->joinRelatedModelIfExists($queryBuilder, $join);
        }

        return $joins;
    }

    /**
     * @param $type
     * @param $key
     * @return bool|string
     */
    private function hasCustomMethod($type, $key)
    {
        $methodName = sprintf('%s%s', $type, Str::studly($key));
        if (method_exists($this, $methodName)) {
            return $methodName;
        }

        return false;
    }

    /**
     * @param Builder $queryBuilder
     * @param $key
     */
    private function joinRelatedModelIfExists(Builder $queryBuilder, $key)
    {
        $model = $queryBuilder->getModel();

        // relationship exists, join to make special sort
        if (method_exists($model, $key)) {
            $relation = $model->$key();
            $type = 'inner';

            if ($relation instanceof BelongsTo) {
                $this->joinRelatedModelIfNotJoined(
                    $queryBuilder,
                    $relation->getRelated()->getTable(),
                    $relation->getQualifiedForeignKeyName(),
                    '=',
                    $relation->getQualifiedOwnerKeyName(),
                    $type
                );
            } elseif ($relation instanceof BelongsToMany) {
                $this->joinRelatedModelIfNotJoined(
                    $queryBuilder,
                    $relation->getTable(),
                    $relation->getQualifiedParentKeyName(),
                    '=',
                    $relation->getQualifiedForeignPivotKeyName(),
                    $type
                );
                $this->joinRelatedModelIfNotJoined(
                    $queryBuilder,
                    $relation->getRelated()->getTable(),
                    $relation->getRelated()->getTable().'.'.$relation->getRelated()->getKeyName(),
                    '=',
                    $relation->getQualifiedRelatedPivotKeyName(),
                    $type
                );
            } else {
                $this->joinRelatedModelIfNotJoined(
                    $queryBuilder,
                    $relation->getRelated()->getTable(),
                    $relation->getQualifiedParentKeyName(),
                    '=',
                    $relation->getQualifiedForeignKeyName(),
                    $type
                );
            }

            $table = $model->getTable();
            $queryBuilder->select(sprintf('%s.*', $table));
        }
    }

    /**
     * Join related model if not joined
     *
     * @param Builder $queryBuilder
     * @param string $relatedTable
     * @param string $foreignKeyName
     * @param string $operator
     * @param string $ownerKeyName
     * @param string $type
     */
    private function joinRelatedModelIfNotJoined(
        Builder $queryBuilder,
        string $relatedTable,
        string $foreignKeyName,
        string $operator,
        string $ownerKeyName,
        string $type
    ) {
        $key = sprintf('%s%s%s%s%s', $relatedTable, $foreignKeyName, $operator, $ownerKeyName, $type);

        if (array_key_exists($key, $this->brunoJoins)) {
            return;
        }

        $this->brunoJoins[$key] = true;

        $queryBuilder->join($relatedTable, $foreignKeyName, $operator, $ownerKeyName, $type);
    }
}
