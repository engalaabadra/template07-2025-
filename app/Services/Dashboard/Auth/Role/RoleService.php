<?php

namespace App\Services\Dashboard\Auth\Role;
use App\Services\Eloquent\EloquentService;
use App\Repositories\Eloquent\EloquentRepository;
use App\Services\Translation\TranslationService;
use Illuminate\Support\Facades\Config;
use App\Models\Role;
use App\Services\ServiceResponse;
use App\Enums\IsActiveEnum;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;

/**
 * Class RoleService
 *
 * This service handles all business logic related to Role management.
 * Including storing, updating, activating, deleting, and restoring roles.
 */
class RoleService extends EloquentService implements RoleServiceInterface
{
    /** @var EloquentRepository */
    protected $eloquentRepo;

    /** @var TranslationService */
    protected $translationService;

    /** @var array */
    protected $mainRoleNames;

    /** @var array */
    protected $mainRolesIds;

    
    /**
     * Constructor
     *
     * @param EloquentRepository    $eloquentRepo
     * @param TranslationService    $translationService
     * @param   $mainRoleNames
     */
    public function __construct(EloquentRepository $eloquentRepo, TranslationService $translationService)
    {
        $this->eloquentRepo       = $eloquentRepo;
        $this->translationService = $translationService;
        $this->mainRoleNames      = Role::getMainRoleNames();
        $this->mainRolesIds      = Role::getmainRolesIds();
    }

    #region ===================== Start CRUD Methods =====================

    /**
     * Store a new record.
     *
     * @param object $request The request object containing validated data.
     * @param object $model   The model to be created.
     * @return object         Created record with optional eager loading.
     */
    protected function store($request, $model)
    {
        // Get validated data from the request
        $data = $request->validated();

        // Remove 'permissions' key from the data to prepare for model creation
        $enteredData = array_diff_key($data, array_flip(['permissions']));

        // Check if a soft-deleted record with the same name already exists
        $existingSoftDeleted = $model->onlyTrashed()
                                    ->where('name', $enteredData['name'])
                                    ->first();

        if ($existingSoftDeleted) {
            throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.A previously deleted matching record was found. Would you like to restore it if you have permission to do so, or create this under a different name?'));
        }

        // Check if a deactivated (not active) record with the same name exists
        $existingDeactivate = $model->where('is_active', IsActiveEnum::NOT_ACTIVE->value)
                                    ->where('name', $enteredData['name'])
                                    ->first();

        if ($existingDeactivate) {
        throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.A matching record was found but is not activated. Would you like to activate it if you have permission to do so, or create this under a different name?'));

        }

        // Create the new record
        $newRole = $model->create($enteredData);

        // Assign permissions to the new role if provided
        if (isset($data['permissions'])) {
            $newRole->permissions()->attach($data['permissions']);
        }

        // Handle translations if provided
        if ($request->has('translations')) {
            $this->translationService->handleTranslations($model, $newRole, $request->get('translations'), $type = 'store');
        }

        // Return the newly created record with eager loading if defined
        return $model->getEagerLoading() ? $newRole->load($model->getEagerLoading()) : $newRole;

    }

    /**
     * Update a specific record.
     *
     * @param object     $request The request object containing validated data.
     * @param int        $id      The ID of the record to update.
     * @param object     $model   The model to update.
     * @return object             Updated record with optional eager loading.
     */
    protected function update($request, $id, $model)
    {
        // Get validated data from the request
        $data = $request->validated();

        // Find the role by ID with ignore protected main roles
        $role = $model->findRoleExceptMain($id, $model);

        // // Check if the ID is protected from updates
        // $resultProtectedIds = $model::checkProtectedRolesIds([$id]);
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        // Remove 'permissions' key from the data to prepare for model update
        $enteredData = array_diff_key($data, array_flip(['permissions']));

        // Check if a soft-deleted record with the same name (but different ID) exists
        // $existingSoftDeleted = $model->whereNot('id', $id)
        //                             ->onlyTrashed()
        //                             ->where('name', $enteredData['name'])
        //                             ->first();

        // if ($existingSoftDeleted) {
        //     throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.A previously deleted matching record was found. Would you like to restore it if you have permission to do so, or modify it under a different name?'));
        // }

        // // Check if a deactivated record with the same name (but different ID) exists
        // $existingDeactivate = $model->whereNot('id', $id)
        //                             ->where('is_active', IsActiveEnum::NOT_ACTIVE->value)
        //                             ->where('name', $enteredData['name'])
        //                             ->first();

        // if ($existingDeactivate) {
        //     throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.A matching record was found but is not activated. Would you like to activate it if you have permission to do so, or change this to a different name?'));
        // }

        // Update the role with new data
        $role->update($enteredData);

        // Sync permissions if provided
        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        // Handle translations if provided
        if ($request->filled('translations')) {
            $this->translationService->handleTranslations($model, $role, $request->get('translations'), $type = 'update');
        }

        // Return the updated role with eager loading if defined
        return $model->getEagerLoading() ? $role->load($model->getEagerLoading()) : $role;
    }

     /**
     * Force delete - single role OR all roles - a trashed record.
     *
     * @param int $id
     * @param object $model The model to query.
     */
    protected function forceDelete($id, $model)
    {
        $role = $model->findRoleExceptMainTrash($id, $model);

        // $resultProtectedIds = $model::checkProtectedRolesIds([$id]);
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        $role->forceDelete();
    }

    /**
     * Force delete many trashed records.
     *
     * @param object $model The model to query.
     */
    protected function forceDeleteMany($request, $model)
    {
        return $this->handleBulkRestoreAndDelete($request, $model, 'forceDelete');
    }


    #endregion ===================== End CRUD Methods =====================

    #region ===================== Start ACTIVATION Methods =====================

    /**
     * Toggle activation status for a record.
     *
     * @param int $id The ID of the record to toggle activation.
     * @param object $model The model to query.
     * @return object Updated record with toggled activation status.
     */
    public function changeActivate($request, $id, $model)
    {
        // Find the record without applying global scopes
        $role = $this->baseRepo->findWithoutTrashedOrFail($id, $model); // find role only in table not in trash to activate it

        // Get protected role IDs to prevent unauthorized actions
        // $resultProtectedIds = $model::checkProtectedRolesIds([$id]);

        // // If the record is protected, return the response
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }


        // Get the action to perform: 'activate', 'deactivate', or 'toggle' (default: toggle)
        $action = request()->input('action', 'toggle');

        // Determine the new status based on the requested action
        $newStatus = $this->getNewStatusByAction($action, $role);
        
        // Update the role's status
        $role->update(['is_active' => $newStatus->value]);

        // Load related models if eager loading is defined
        $data = $model->getEagerLoading() ? $role->load($model->getEagerLoading()) : $role;

        return $data;
    }

    /**
     * Activate multiple records.
     *
     * @param object $model The model to query.
     * @return array Activated records.
     */
    public function changeActivateMany($request, $model)
    {
        // Handle bulk activation using shared method
        return $this->handleBulkActivation($request, $model, 'activate');
    }
    #endregion ===================== End ACTIVATION Methods =====================

    #region ===================== Start TRASH Methods =====================
    /**
     * Soft delete or permanently delete a record.
     *
     * @param int $id The ID of the record to delete.
     * @param object $model The model to query.
     * @param array|null $eagerLoading Relations to eager load.
     * @return object Deleted record.
     */
    protected function destroy($id, $model)
    {
        // Retrieve the role record without global scopes
        $role = $model->withoutGlobalScopes()->find($id);
        if (!$role) throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);

        // // Get protected role IDs to prevent unauthorized actions
        // $resultProtectedIds = $model::checkProtectedRolesIds([$id]);

        // // If the record is protected, return the response
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        // If the record is already deleted, return not found
        if ($role->deleted_at !== null) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

        // If model does NOT use soft deletes (permanent deletion)
        if (!isSoftDeletes($model)) {
            // Permanently delete the role
            $role->delete();

            // Detach related users and permissions
            $role->users()->detach();
            $role->permissions()->detach();
        } else {
            // Soft delete the role
            $role->delete();

            $data = $model->getEagerLoading()                 // Eager load relations if defined
            ? $role->load($model->getEagerLoading())
            : $role;

            return $data;
        }
    }

    /**
     * Delete multiple records (soft or permanent).
     *
     * @param object $model The model to query.
     * @return object Result of deletion with deleted and not found IDs.
     */
    protected function destroyMany($request, $model)
    {
        // Handle bulk deletion using shared method
        return $this->handleBulkRestoreAndDelete($request, $model, 'destroy');
    }

    /**
     * Restore a trashed record.
     *
     * @param int $id The ID of the trashed record to restore.
     * @param object $model The model to query.
     * @return object Restored record.
     */
    public function restore($id, $model)
    {
        $item = $model->withoutGlobalScopes()->onlyTrashed()->find($id);
        if (!$item) throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);

        // $resultProtectedIds = $model::checkProtectedRolesIds([$id]);
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        $item->restore();
        $data = $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;

        return $data;
    }
    #endregion ===================== End TRASH Methods =====================

    
    #region ===================== Start Private and Protected Methods =====================
    
    /**
     * Handle bulk actions (restore, force delete, or soft delete) on a given Eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string $action Action to perform: 'restore', 'forceDelete', 'destroy'.
     * @return \Illuminate\Http\JsonResponse
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
        // ✅ Get 'ids' input from the request (can be "all", array, or JSON string)
        $ids = request()->input('ids');

        // ✅ Get the action to perform: 'activate', 'deactivate', or 'toggle' (default: toggle)
        $action = request()->input('action', 'toggle');

        // ❌ Validate that 'ids' is either "all" or an array/valid JSON string
        if (!$ids || (!is_array($ids) && $ids !== 'all')) {
            throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.validation_failed'), [
                'ids' => 'Invalid IDs'
            ]);

        }

        // ✅ Base query excluding soft-deleted records
        $query = $model->newQuery()->withoutTrashed();

        if ($ids === 'all') {
            // ✅ Fetch all roles (no filtering by ID)
            $roles = $query->get();
            $ids = $roles->pluck('id')->toArray(); // Set $ids for comparison later
        } else {
            if (empty($ids) || !is_array($ids)) {
                throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.Validation failed'), [
                    'ids' => ['validation.No valid IDs provided']
                ]);
            }

            $roles = $query->whereIn('id', $ids)->get();
        }

            // $resultProtectedIds = $model::checkProtectedRolesIds(json_decode($inputIds));
            // if ($resultProtectedIds instanceof ServiceResponse) {
            //     return $resultProtectedIds;
            // }
        // ✅ Initialize result arrays
        $processedIds  = []; // Successfully updated
        $failedIds     = []; // Failed to update due to errors
        $notFoundIds   = []; // IDs that were not found in DB

        // ✅ Process each role in the collection
        foreach ($roles as $role) {
            try {
                // 🔁 Determine the new status based on the requested action
                switch ($action) {
                    case 'activate':
                        $newStatus = \App\Enums\IsActiveEnum::ACTIVE;
                        break;
                    case 'deactivate':
                        $newStatus = \App\Enums\IsActiveEnum::NOT_ACTIVE;
                        break;
                    case 'toggle':
                    default:
                        $newStatus = $role->is_active === \App\Enums\IsActiveEnum::ACTIVE
                            ? \App\Enums\IsActiveEnum::NOT_ACTIVE
                            : \App\Enums\IsActiveEnum::ACTIVE;
                        break;
                }

                // 🔍 If status is already set correctly, skip update
                if ($role->is_active === $newStatus) {
                    $processedIds[] = $role->id;
                    continue;
                }

                // 💾 Update the role's status
                $role->update(['is_active' => $newStatus->value]);
                $processedIds[] = $role->id;

            } catch (\Throwable $e) {
                // ❌ Something went wrong updating this role
                $failedIds[] = $role->id;
            }
        }

        // 🔍 Determine which requested IDs were not found in the database
        $foundIds     = $roles->pluck('id')->toArray();
        $notFoundIds  = array_diff($ids, $foundIds);

        // 🔄 Fetch updated roles again for response
        $updatedRoles = $model->whereIn('id', $processedIds)->get();

        // 🔁 Load relationships if defined in model
        if (method_exists($model, 'getEagerLoading') && $model->getEagerLoading()) {
            $updatedRoles->load($model->getEagerLoading());
        }

        $data = [
            'updated_items'   => $updatedRoles,
            'failed_ids'      => array_values($failedIds),
            'not_found_ids'   => array_values($notFoundIds),
        ];

        return $data;
    }

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
            $items = $query->whereNotIn('id', $this->mainRolesIds)->get();

            $ids = $items->pluck('id')->toArray(); // Set $ids for comparison later
        } else {
            $ids = $inputIds; // Already validated array of integers
            // Fetch only items with those IDs
            $items = $query->whereNotIn('id', $this->mainRolesIds)->whereIn('id', $ids)->get();
        }
        // Return 404 response if no items were found
        if ($items->isEmpty() && !$isAll) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);           // throw 404 if SoftDeletes not used
        }
        return $items;
    }
    #endregion ===================== End Private and Protected Methods =====================

}
