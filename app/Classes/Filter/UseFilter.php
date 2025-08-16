<?php

namespace App\Classes\Filter;

use Illuminate\Support\Collection;

/**
 * Trait UseFilter
 *
 * Provides filter handling for classes, including:
 * - Defining filters
 * - Applying filters to queries
 * - Returning filter metadata for the frontend
 *
 * Example:
 * ```php
 * $this->filters(); // Returns array of filter instances
 * $this->filter();  // Applies filters from request
 * $this->getFilters(); // Returns filter metadata
 * ```
 */
trait UseFilter
{
    /**
     * Get an array of filter instances.
     *
     * @return array<Filter>
     */
    public function filters(): array
    {
        return []; // Default: no filters, override in class
    }

    /**
     * Apply filters to the current instance based on request values.
     *
     * @return $this
     */
    public function filter(): static
    {
        foreach ($this->filters() as $filter) { // Loop through defined filters
            $value = request($filter->key); // Get value from request
            if ($value !== null) { // Apply only if value exists
                data_get($filter, 'callback')($value); // Execute filter callback
            }
        }
        return $this; // For method chaining
    }

}
