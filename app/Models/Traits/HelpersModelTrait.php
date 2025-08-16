<?php

namespace App\Models\Traits;

use App\Classes\DateHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Trait HelpersModelTrait
 *
 * A reusable set of query builder helper methods commonly used in Eloquent models.
 * Provides shortcuts for filtering, searching, scoping, and dynamic where conditions.
 *
 * @mixin Model
 */
trait HelpersModelTrait
{
    
    /**
     * Filter by a date range on the `created_at` column.
     *
     * Example:
     * `$query->createdAtRange('2024-01-01,2024-01-31');`
     *
     * @param string|array|null $dateRange
     * @param string $column
     * @return static
     */
    final public function createdAtRange(string|array|null $dateRange, $column = 'created_at'): static
    {
        return $this->rangeDateFilter($dateRange, $column);
    }

    /**
     * Apply a `where is_active = ?` filter only if `$active` is not null.
     *
     * Example:
     * `$query->isActive(true); // where is_active = 1`
     *
     * @param bool|null $active
     * @return static
     */
    final public function isActive(?bool $active = null): static
    {
        return $this->when($active !== null, function (Builder $q) use ($active) {
            $q->where('is_active', $active);
        });
    }

    
    /**
     * Apply a `where lang = ?` filter only if `$lang` is not null.
     *
     * Example:
     * `$query->lang(lang()); // where lang = ar`
     *
     * @param string $lang
     * @return static
     */
    final public function lang(?string $lang): static
    {
        if (!Schema::hasColumn($this->getModel()->getTable(), 'lang')) {
            return $this;
        }
        return $this->when($lang, function (Builder $q) use ($lang) {
           // $q->whereRaw("TRIM(lang) = ?", [$lang]);

            $q->where('lang', $lang);
        });
    }

    /**
     * Filter by a date range on the `updated_at` column.
     *
     * Example:
     * `$query->updatedAtRange('2024-01-01,2024-01-31');`
     *
     * @param string|null $dateRange
     * @return static
     */
    final public function updatedAtRange(?string $dateRange): static
    {
        return $this->rangeDateFilter($dateRange, 'updated_at');
    }

    /**
     * Filter a model based on a date range, ignoring time.
     *
     * Example:
     * `$query->rangeDateFilter('2024-01-01,2024-01-10', 'published_at');`
     *
     * @param string|array|null $date_range
     * @param string $column
     * @return static
     */
    public function rangeDateFilter($date_range, $column = 'created_at'): static
    {
        $date = DateHelper::getRangeFromRequestPeriod($date_range);
        return $this->when($date !== null,
            fn(Builder $q) => $q->whereDate($column, '>=', Arr::first($date))
                ->when(Arr::last($date) !== null, fn(Builder $q) => $q->whereDate($column, '<=', Arr::last($date)))
        );
    }

    /**
     * Perform a flexible full-text search across multiple columns.
     *
     * Supports:
     * - Regular columns
     * - JSON-translatable columns
     * - Related model columns using dot notation
     * - ID-based search using prefix `#123`
     *
     * Example:
     * `$query->search(['name', 'email', 'department.name']);`
     *
     * @param array $columns
     * @param string|null $search_key
     * @return static
     */
    public function search(array $columns = [], ?string $search_key = null): static
    {
        // to add created_at_text in all proccess export without need pass from export col. model
        if (!in_array('created_at', $columns)) {
            $columns[] = 'created_at';
        }

        $search_key = $search_key ?? request()->get('search') ?? '';

        if (!$search_key) return $this;

        $search_key = trim($search_key);

        if (empty($search_key) || empty($columns)) {
            return $this;
        }

        if (str_starts_with($search_key, '#')) {
            return $this->where('id', substr($search_key, 1));
        }

        $this->where(function ($query) use ($search_key, $columns) {
             // نفترض أن $this->model::$translationFields يحتوي على أسماء الحقول التي تترجم
            $translatable_columns = $this->model::$translationFields && is_array($this->model::$translationFields)
                ? $this->model::$translationFields
                : [];

            foreach ($columns as $column_name) {
                // بحث في علاقة الترجمة إذا الحقل قابل للترجمة
                if (in_array($column_name, $translatable_columns)) {
                $query->orWhereHas('translations', function ($q) use ($column_name, $search_pattern) {
                        if (request()->is('api/dashboard/*')) {
                            $q->where($column_name, 'like', $search_pattern);
                        } else {
                            $q->where('lang', app()->getLocale())
                            ->where($column_name, 'like', $search_pattern);
                        }
                    });
                    continue;
                }
                    // if (count($relation_parts) === 2) {
                    //     [$relation, $relation_column] = $relation_parts;

                    //     $query->orWhereHas($relation, function ($subquery) use ($relation_column, $search_pattern) {
                    //         $subquery->where($relation_column, 'like', $search_pattern);
                    //     });
                    //     continue;
                    // }
                // Remove and get the first element from the relation parts array.
                // This is the current relation name we will query on.
                $relation = array_shift($relation_parts);

                // Join the remaining parts back into a string separated by dots.
                // This represents the nested relation column or further relations.
                $relation_column = implode('.', $relation_parts);

                // Add an OR condition that checks if the relation exists and matches the given search.
                // The callback $subquery allows querying inside the related model.
                $query->orWhereHas($relation, function ($subquery) use ($relation_column, $search_pattern) {

                    // Split the nested relation column string into parts by dot notation.
                    // This will help determine if there are more nested relations.
                    $relation_parts = explode('.', $relation_column);

                    // If there is more than one part, it means there are further nested relations.
                    if (count($relation_parts) > 1) {

                        // Extract the next nested relation name from relation_parts.
                        $subRelation = array_shift($relation_parts);

                        // Join the remaining relation_parts back to a dot-notated string for deeper nested relations.
                        $nestedRelationColumn = implode('.', $relation_parts);

                        // Recursively add a whereHas condition on the nested relation,
                        // allowing searching within deeper nested relations.
                        $subquery->whereHas($subRelation, function ($nestedQuery) use ($nestedRelationColumn, $search_pattern) {

                            // Apply the search pattern on the deepest relation column.
                            $nestedQuery->where($nestedRelationColumn, 'like', $search_pattern);
                        });
                    } else {
                        // If there is only one part left, it means we reached the actual column to search.

                        // Add a where condition on this column using a LIKE search.
                        $subquery->where($relation_column, 'like', $search_pattern);
                    }
                });

                // Continue to the next iteration of the loop after handling this relation.
                continue;


                $query->orWhere($column_name, 'like', $search_pattern);
            }
        });

        return $this;
    }

   

    /**
     * Smart helper to apply `where`, `whereIn`, or skip conditionally.
     *
     * Example:
     * `$query->whereOrWhereIn('status', ['pending', 'approved']);`
     *
     * @param string $column
     * @param mixed $values
     * @param bool $search_for_null
     * @return static
     */
    public function whereOrWhereIn($column, $values = null, $search_for_null = false)
    {
        if (!$search_for_null) {
            if (!$values || (is_array($values) && count($values) === 0)) {
                return $this;
            }
        }

        if (!is_array($values))
            return $this->where($column, $values);

        if (count($values) == 1)
            return $this->where($column, $values[0]);

        return $this->whereIn($column, $values);
    }

    /**
     * Filter by one or more status values (uses `whereOrWhereIn`).
     *
     * Example:
     * `$query->filterStatus(['active', 'suspended']);`
     *
     * @param array|string $values
     * @param string $column_name
     * @return static
     */
    public function filterStatus($values = [], $column_name = 'status')
    {
        return $this->whereOrWhereIn($column_name, $values);
    }

    /**
     * Generic method to apply `whereOrWhereIn` for any column.
     *
     * Example:
     * `$query->columnWhereOrWhereIn('type', ['admin', 'user']);`
     *
     * @param string $column_name
     * @param array|string $values
     * @return static
     */
    public function columnWhereOrWhereIn($column_name, $values = [])
    {
        return $this->whereOrWhereIn($column_name, $values);
    }

    /**
     * Apply `whereOrWhereIn` filter to a column in a related model.
     *
     * Example:
     * `$query->relationColumnWhereOrWhereIn('department', 'type', ['main', 'support']);`
     *
     * @param string $relation
     * @param string $column_name
     * @param array|string $values
     * @return static
     */
    public function relationColumnWhereOrWhereIn($relation, $column_name, $values = [])
    {
        return $this->whereHas($relation, fn($z) => $z->whereOrWhereIn($column_name, $values));
    }
}

