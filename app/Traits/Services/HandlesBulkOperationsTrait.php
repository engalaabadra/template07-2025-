<?php
namespace App\Traits\Services;


trait HandlesBulkOperationsTrait{
    use FetchesItemsTrait;

     /**
     * Handle bulk restore, soft delete, or force delete operations on a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model   The model class to operate on
     * @param  string  $action                                The action to perform (restore, destroy, forceDelete)
     * @return \App\Helpers\ServiceResponse                    Standardized service response
     *
     * @throws \InvalidArgumentException                      If an invalid action is passed
     */
    protected function handleBulkRestoreAndDelete($request, $model, string $action)
    {
        // Get the validated 'ids' input from the request
        $inputIds = $request->input('ids', []);

        // Check if the operation applies to all items
        $isAll = $inputIds === 'all';

        // Base query depending on the action (use onlyTrashed or withoutTrashed)
        $query = in_array($action, ['restore', 'forceDelete'])
            ? $model->onlyTrashed()
            : $model->withoutTrashed();

        // Fetch items based on whether 'all' is selected or specific IDs are provided
        $items = $this->fetchItemsByIdsOrAll($query, $isAll, $inputIds);
       
        $processedIds = []; // Will store IDs of successfully processed items
        $conflictIds  = []; // Optional: IDs that had conflicts (for restore with uniqueness)
        $failedIds     = []; // Failed to update due to errors
        $notFoundIds   = []; // IDs that were not found in DB

        // Loop through each item and apply the requested action
        foreach ($items as $item) {
            try {
            // Perform the requested action using PHP 8 match expression
            match ($action) {
                'restore'     => $item->restore(),
                'forceDelete' => $item->forceDelete(),
                'destroy'     => $item->delete(),
                default       => throw new \InvalidArgumentException("Invalid action: {$action}"),
            };

            $processedIds[] = $item->id;
            } catch (\Throwable $e) {
                // Something went wrong updating this item
                $failedIds[] = $item->id;
            }
        }

        // Determine which IDs were not found (only relevant if not "all")
        $notFoundIds = $isAll ? [] : array_values(array_diff($ids, $processedIds));

        $data = [
            'processed_ids' => $processedIds,   // Successfully processed
            'not_found_ids' => $notFoundIds,    // Items that were not found in DB
            'conflict_ids'  => $conflictIds,    // Optional: used if you enable conflict handling
            'failed_ids'  => $failedIds,    // Optional: used if you enable failed handling
       
        ];

        return $data;

    }
    /**
     * Handle bulk activation, deactivation, or toggling of users.
     *
     * This method supports two input formats for IDs:
     * - A specific list of IDs (array or JSON string)
     * - The string "all" to apply the action to all users
     *
     * It also detects and returns:
     * - IDs that failed during update
     * - IDs that were not found in the database
     *
     * @param \Illuminate\Database\Eloquent\Model $model The User model instance
     * @return \App\GeneralClasses\ServiceResponse
     */
    protected function handleBulkActivation($request, $model)
    {
        // Get the validated 'ids' input from the request
        $inputIds = $request->input('ids', []);

        // Check if the operation applies to all items
        $isAll = $inputIds === 'all';

        // Get the action to perform: 'activate', 'deactivate', or 'toggle' (default: toggle)
        $action = $request->input('action', 'toggle');

        // Base query excluding soft-deleted records
        $query = $model->withoutTrashed();

        // Fetch items based on whether 'all' is selected or specific IDs are provided
        $items = $this->fetchItemsByIdsOrAll($query, $isAll, $inputIds);

        // Initialize result arrays
        $processedIds  = []; // Successfully updated
        $failedIds     = []; // Failed to update due to errors
        $notFoundIds   = []; // IDs that were not found in DB

        // Process each item in the collection
        foreach ($items as $item) {
            try {
                // Determine the new status based on the requested action
                $newStatus = $this->getNewStatusByAction($action, $item);

                // If status is already set correctly, skip update
                if ($item->is_active === $newStatus) {
                    $processedIds[] = $item->id;
                    continue;
                }

                // Update the item's status
                $item->update(['is_active' => $newStatus->value]);
                $processedIds[] = $item->id;

            } catch (\Throwable $e) {
                // Something went wrong updating this item
                $failedIds[] = $item->id;
            }
        }

        // Determine which requested IDs were not found in the database
        $notFoundIds = $isAll ? [] : array_values(array_diff($ids, $processedIds));

        // 🔄 Fetch updated items again for response
        $updatedItems = $model->whereIn('id', $processedIds)->get();

        // Load relationships if defined in model
        if (method_exists($model, 'getEagerLoading') && $model->getEagerLoading()) {
            $updatedItems->load($model->getEagerLoading());
        }

        $data = [
            'updated_items'   => $updatedItems,
            'failed_ids'      => array_values($failedIds),
            'not_found_ids'   => array_values($notFoundIds),
        ];

        return $data;
        
    }
}