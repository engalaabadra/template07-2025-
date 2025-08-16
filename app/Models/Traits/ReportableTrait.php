<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Schema;
use App\Helpers\ReportHelper;

/**
 * Trait ReportableTrait
 *
 * Provides a flexible way to generate aggregated reports with optional filtering and joining.
 * This trait relies on a method `getReportConfig($type)` that should be implemented in the model using the trait.
 */
trait ReportableTrait
{

    /**
     * Generate an aggregated report based on dynamic filters and configuration.
     *
     * @param array $filters Filters to apply to the base model table (e.g., ['is_active' => true]).
     * @param string $type The report type key to load the appropriate configuration.
     * @return \Illuminate\Support\Collection The result of the query.
     */
    public static function generateReport($model, array $filters = [], string $type = '')
    {
        // Ensure the model provides a report configuration method
        if (!method_exists(static::class, 'getReportConfig')) {
            return collect(); // Return empty collection
        }

        // Get report configuration
        $config = static::getReportConfig($model, $type);

        // Start building the query from the specified table or the model's default table
        $query = \DB::table($config['from'] ?? (new static)->getTable());

        // Get the model's table name for column validation
        $table = (new static)->getTable();

        // Apply filters only if the column exists in the table
        foreach ($filters as $column => $value) {
            if (!Schema::hasColumn($table, $column)) {
                continue; // Skip invalid columns
            }

            $query->when(
                is_array($value),
                fn ($q) => $q->whereIn($column, $value),
                fn ($q) => $q->where($column, $value)
            );
        }

        // Apply where conditions if defined
        if (!empty($config['where'])) {
            foreach ($config['where'] as $condition) {
                $query->where(...$condition);
            }
        }

        // Apply join clauses if defined
        if (!empty($config['join'])) {
            foreach ($config['join'] as $join) {
                $query->join(...$join);
            }
        }

        // Apply any additional join conditions using closures
        if (!empty($config['joinConditions']) && is_callable($config['joinConditions'])) {
            $query = $query->where($config['joinConditions']);
        }

        // Apply relation filtering and eager loading if defined (useful when using with Eloquent, not Query Builder)
        if (!empty($config['relation'])) {
            $query->whereHas($config['relation']);
            $query->with($config['relation']);
        }

        // Select specified columns and group the results
        return $query
            ->selectRaw($config['raw'])
            ->groupBy(...($config['groupBy'] ?? []))
            ->get();
    }


    public static function getReportConfig($model, string $type): array
    {
        $model_count = modelName($model) . '_count';
        
        $commonReports = ReportHelper::getCommonReports($model_count);

         // If the requested type exists in common reports, return it
        if (isset($commonReports[$type])) {
            return $commonReports[$type];
        }
        return [
            'raw' => 'COUNT(id) as ' . $model_count, // Raw SQL to count total records
            'groupBy' => [], // No grouping applied
        ];
    }
}
