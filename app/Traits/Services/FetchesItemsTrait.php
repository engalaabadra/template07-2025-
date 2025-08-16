<?php
namespace App\Traits\Services;

trait FetchesItemsTrait{
    
     /**
     * Retrieve items by IDs or all items if $isAll is true.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool                                   $isAll
     * @param  array|string                            $inputIds
     * @return \Illuminate\Support\Collection
     */
    protected function fetchItemsByIdsOrAll($query, bool $isAll, $inputIds)
    {
        if ($isAll) {
            // Fetch all items (no filtering by ID)
            $items = $query->get();

            $ids = $items->pluck('id')->toArray(); // Set $ids for comparison later
        } else {
            $ids = $inputIds; // Already validated array of integers
            // Fetch only items with those IDs
            $items = $query->whereIn('id', $ids)->get();
        }
        // Return 404 response if no items were found
        if ($items->isEmpty() && !$isAll) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);           // throw 404 if SoftDeletes not used
        }
        return $items;
    }

}