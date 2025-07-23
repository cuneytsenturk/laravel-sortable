<?php

namespace Akaunting\Sortable\Traits;

use Akaunting\Sortable\Exceptions\SortableException;
use Akaunting\Sortable\Support\SortableLink;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait Sortable
{
    /**
     * @throws SortableException
     */
    public function scopeSortable(Builder $query, array|null $defaultParameters = null): Builder
    {
        if (
            request()->allFilled(['sort', 'direction']) 
            && $this->columnExists($this, request()->get('sort'))
        ) { // allFilled() is macro
            return $this->queryOrderBuilder($query, request()->only(['sort', 'direction']));
        }

        if (is_null($defaultParameters)) {
            $defaultParameters = $this->getDefaultSortable();
        }

        if (! is_null($defaultParameters)) {
            $defaultSortArray = $this->formatToParameters($defaultParameters);

            if (
                config('sortable.allow_request_modification', true) 
                && ! empty($defaultSortArray)
            ) {
                request()->merge($defaultSortArray);
            }

            return $this->queryOrderBuilder($query, $defaultSortArray);
        }

        return $query;
    }

    /**
     * Returns the first element of defined sortable columns from the Model
     */
    private function getDefaultSortable(): array|null
    {
        if (config('sortable.default_first_column', false)) {
            $sortBy = Arr::first($this->sortable);

            if (! is_null($sortBy)) {
                return [$sortBy => config('sortable.default_direction', 'asc')];
            }
        }

        return null;
    }

    /**
     * @throws SortableException
     */
    private function queryOrderBuilder(Builder $query, array $sortParameters): Builder
    {
        $model = $this;

        [$column, $direction] = $this->parseParameters($sortParameters);

        if (is_null($column)) {
            return $query;
        }

        $explodeResult = SortableLink::explodeSortParameter($column);
        if (! empty($explodeResult)) {
            $relationName = $explodeResult[0];
            $column       = $explodeResult[1];

            try {
                $relation = $query->getRelation($relationName);
                $query    = $this->queryJoinBuilder($query, $relation);
            } catch (BadMethodCallException $e) {
                throw new SortableException($relationName, 1, $e);
            } catch (\Exception $e) {
                throw new SortableException($relationName, 2, $e);
            }

            $model = $relation->getRelated();
        }

        if (method_exists($model, Str::camel($column) . 'Sortable')) {
            return call_user_func_array([$model, Str::camel($column) . 'Sortable'], [$query, $direction]);
        }

        if (isset($model->sortableAs) && in_array($column, $model->sortableAs)) {
            $query = $query->orderBy($column, $direction);
        } elseif ($this->columnExists($model, $column)) {
            $column = $model->getTable() . '.' . $column;
            $query  = $query->orderBy($column, $direction);
        }

        return $query;
    }

    private function parseParameters(array $parameters): array
    {
        $column = Arr::get($parameters, 'sort');
        if (empty($column)) {
            return [null, null];
        }

        $direction = Arr::get($parameters, 'direction', []);
        if (! in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = config('sortable.default_direction', 'asc');
        }

        return [$column, $direction];
    }

    /**
     * @throws \Exception
     */
    private function queryJoinBuilder(Builder $query, BelongsTo|HasOne|MorphOne $relation): Builder
    {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable  = $relation->getParent()->getTable();

        if ($parentTable === $relatedTable) {
            $query       = $query->from($parentTable . ' as parent_' . $parentTable);
            $parentTable = 'parent_' . $parentTable;
            $relation->getParent()->setTable($parentTable);
        }

        if ($relation instanceof HasOne || $relation instanceof MorphOne) {
            $relatedPrimaryKey = $relation->getQualifiedForeignKeyName();
            $parentPrimaryKey  = $relation->getQualifiedParentKeyName();
        } elseif ($relation instanceof BelongsTo) {
            $relatedPrimaryKey = $relation->getQualifiedOwnerKeyName();
            $parentPrimaryKey  = $relation->getQualifiedForeignKeyName();
        } else {
            throw new \Exception();
        }

        return $this->formJoin(
            $query, 
            $parentTable, 
            $relatedTable, 
            $parentPrimaryKey, 
            $relatedPrimaryKey
        );
    }

    private function columnExists(mixed $model, string $column): bool
    {
        return isset($model->sortable)
            ? in_array($column, $model->sortable)
            : Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $column);
    }

    private function formatToParameters(array|string $array): array
    {
        if (empty($array)) {
            return [];
        }

        $defaultDirection = config('sortable.default_direction', 'asc');

        if (is_string($array)) {
            return ['sort' => $array, 'direction' => $defaultDirection];
        }

        return (key($array) === 0)
            ? ['sort' => $array[0], 'direction' => $defaultDirection]
            : ['sort' => key($array), 'direction' => reset($array)];
    }

    private function formJoin(
        Builder $query,
        string $parentTable,
        string $relatedTable,
        string $parentPrimaryKey,
        string $relatedPrimaryKey
    ): Builder {
        $joinType = config('sortable.join_type', 'leftJoin');

        return $query
            ->select($parentTable . '.*')
            ->{$joinType}($relatedTable, $parentPrimaryKey, '=', $relatedPrimaryKey);
    }
}
