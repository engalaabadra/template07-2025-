<?php
namespace App\Services\Dashboard\Auth\User;

use App\Services\Eloquent\EloquentService;
use App\Repositories\Base\BaseRepository;
use App\Services\Dashboard\Auth\User\UserServiceInterface;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Scopes\ActiveScope;
use App\Services\Translation\TranslationService;
use App\Models\Profile;
use App\Repositories\Dashboard\Auth\User\UserRepository;
use App\Services\ServiceResponse;
use App\Enums\IsActiveEnum;
use App\Repositories\Eloquent\EloquentRepository;
use App\Helpers\RoleHelper;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;
use App\Traits\Services\HandlesServiceTransactions;
use App\Traits\Services\HandlesActivationTrait;
use App\Traits\Services\HandlesBulkOperationsTrait;
use App\Traits\Services\HandlesTranslationsAndFilesTrait;

/**
 * Class UserService
 *
 * Provides business logic for user-related operations such as
 * creating, updating, deleting, restoring, and managing user activation.
 */
class UserService extends EloquentService implements UserServiceInterface
{
    use HandlesServiceTransactions, HandlesActivationTrait, HandlesBulkOperationsTrait, HandlesTranslationsAndFilesTrait;

    /** @var EloquentRepository */
    protected $eloquentRepo;

    /** @var BaseRepository */
    protected $baseRepo;

    /** @var TranslationService */
    protected $translationService;

    /** @var Profile */
    protected $profile;

    /**
     * Constructor
     *
     * @param EloquentRepository    $eloquentRepo
     * @param BaseRepository    $baseRepo
     * @param UserRepository        $userRepo
     * @param Profile               $profile
     * @param TranslationService    $translationService
     */
    public function __construct( EloquentRepository $eloquentRepo, BaseRepository $baseRepo, UserRepository $userRepo, Profile $profile, TranslationService $translationService)
    {
        $this->eloquentRepo = $eloquentRepo;
        $this->baseRepo = $baseRepo;
        $this->userRepo = $userRepo;
        $this->profile = $profile;
        $this->translationService = $translationService;
    }
     
    #region ===================== Start CRUD Methods =====================
    
    /**
     * Store a new record.
     * @param object $request The request object containing validated data.
     * @param object $model The model to query.
     * @return object Created record with optional eager loading.
     */
    protected function store($request, $model)
    {
        // Get validated data and filter out 'roles' and 'image'
        $data        = $request->validated();
        $enteredData = array_diff_key($data, array_flip(['roles', 'image', 'files']));

        // Generate random password
        $randomStr = Str::random(8);
        $enteredData['password'] = $randomStr;

        // Create the model item
        $newUser = $model->create($enteredData);

        // Assign roles to user
        $newUser->roles()->attach($data['roles']);

        // Create profile for this user
        $newUser->profile()->create($enteredData);

        //Handle translations and file uploads.
        $this->handleTranslationsAndFiles($request, $model, $newItem, 'store');

        // Load related models if eager loading is defined
        return $model->getEagerLoading() ? $newUser->load($model->getEagerLoading()) : $newUser;
    }

    /**
     * Update a specific record.
     * @param object $request The request object containing validated data.
     * @param int $id The ID of the record to update.
     * @param object $model The model to query.
     * @return object Updated record.
     */
    protected function update($request, $id, $model)
    {
        // Get validated data and find the record
        $data = $request->validated();
        $user = $this->baseRepo->findOrFailApi($id, $model);

        // // Check for protected user IDs
        // $resultProtectedIds = $model::getProtectedUserIds([$id]);
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        // Filter out 'roles' and 'files'
        $enteredData = array_diff_key($data, array_flip(['roles'. 'image', 'files']));

        // Update user
        $user->update($enteredData);

        // Sync roles
        $user->roles()->sync($data['roles']);

        //Handle translations and file uploads.
        $this->handleTranslationsAndFiles($request, $model, $item, 'update');

        // Load related models if eager loading is defined
        return $model->getEagerLoading() ? $user->load($model->getEagerLoading()) : $user;
    }

        /**
     * Force delete a single user.
     * @param object $model The model to query.
     */
    protected function forceDelete($id, $model)
    {
        // Get the trashed user by ID without global scopes
        $user = $this->baseRepo->findOnlyTrashedOrFail($id, $model);

        // $resultProtectedIds = $model::getProtectedUserIds([$user->id]);
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        $user->forceDelete();
    }

    /**
     * Force delete all users.
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
     * @param int $id The ID of the record to toggle activation.
     * @param object $model The model to query.
     * @return object Updated record with toggled activation status.
     */
    public function changeActivate($request, $id, $model)
    {
        // Find the record without applying global scopes
        $user = $this->baseRepo->findWithoutTrashedOrFail($id, $model); // find user only in table not in trash to activate it

        // Check for protected user IDs
        // $resultProtectedIds = $model::getProtectedUserIds([$id]);
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        // 📝 Get action, default to 'toggle' if not provided
        $action = $request->input('action', 'toggle');

        // 🔁 Determine the new status based on the requested action
        $newStatus = $this->getNewStatusByAction($action, $user);

        // Update the item's status
        $user->update(['is_active' => $newStatus->value]);

        // 📦 Always return fresh data (with eager loading if defined)
        return $model->getEagerLoading()
            ? $user->load($model->getEagerLoading())
            : $user;
    }

    /**
     * Activate multiple records.
     * @param object $model The model to query.
     * @return array Activated records.
     */
    public function changeActivateMany($request, $model)
    {
        return $this->handleBulkActivation($request, $model, 'activate');
    }
    #endregion ===================== End ACTIVATION Methods =====================

    #region ===================== Start TRASH Methods =====================

    /**
     * Delete one user.
     * @param object $model The model to query.
     * @return object Deleted record.
     */
    protected function destroy($id, $model)
    {
        $user = $this->baseRepo->findOrFailApi($id, $model);

        // $resultProtectedIds = $model::getProtectedUserIds([$user->id]);
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        $user->delete();
        $data = $model->getEagerLoading()                 // Eager load relations if defined
            ? $user->load($model->getEagerLoading())
            : $user;

        return $data;
            
    }

    /**
     * Delete all users.
     * @param object $model The model to query.
     * @return object Deleted record.
     */
    protected function destroyMany($request, $model)
    {
        return $this->handleBulkRestoreAndDelete($request, $model, 'destroy');
    }

    /**
     * Restore a trashed record.
     * @param int $id The ID of the trashed record to restore.
     * @param object $model The model to query.
     * @return object Restored record.
     */
    public function restore($id, $model)
    {
        // Find the trashed record by ID or return 404
        $user = $this->baseRepo->findOnlyTrashedOrFail($id, $model);

        // $resultProtectedIds = $model::getProtectedUserIds([$user->id]);
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        $user->restore();
        $data = $model->getEagerLoading() ? $user->load($model->getEagerLoading()) : $user;

        return $data;
    }

    #endregion ===================== End TRASH Methods =====================

    
        #region ===================== Start File Handling Methods =====================
    
    public function uploadFile($request, $id, $model)
    {
        $data = $request->validated();

        $mainRole = RoleHelper::getMainRoleName();
        $user = $model->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', [$mainRole]);
        })->find($id);

        if (!$user) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

        $folder = modelName($model);

        if (isset($data['image'])) {
            $user->uploadSingleMedia($request->file('image'), 'image', $folder);
        }

        $data = $model->getEagerLoading() ? $user->load($model->getEagerLoading()) : $user;

        return $data;
    }

    public function uploadFiles($request, $id, $model)
    {
        $data = $request->validated();

        $mainRole = RoleHelper::getMainRoleName();
        $user = $model->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', [$mainRole]);
        })->find($id);

        if (!$user) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

        $folder = modelName($model);

        if (isset($data['files'])) {
            $user->uploadMultipleMedia($request->file('files'), 'file', $folder);
        }

        $data = $model->getEagerLoading() ? $user->load($model->getEagerLoading()) : $user;

        return $data;

    }

    public function deleteFile($id, $model)
    {
        $mainRole = RoleHelper::getMainRoleName();
        $user = $model->whereDoesntHave('roles', function ($query) use ($mainRole) {
            $query->whereIn('name', [$mainRole]);
        })->find($id);

        if (!$user) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

        if (method_exists($user, 'image')) {
            $user->deleteSingleMedia('image');
        }

        $data = $model->getEagerLoading() ? $user->load($model->getEagerLoading()) : $user;

        return $data;

    }

    public function deleteFiles($request, $id, $model)
    {
        $mainRole = RoleHelper::getMainRoleName();

        // if (!adminApi()?->hasRole($mainRole)) {
        //     throw new ApiResponseException(ServiceResponseEnum::FORBIDDEN);
        // }

        $user = $model->whereDoesntHave('roles', function ($query) use($mainRole) {
            $query->whereIn('name', [$mainRole]);
        })->find($id);

        if (!$user) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

        $inputIds = $request->input('ids', []);
        $isAll = $inputIds === "all";

        $query = $model;
        // Fetch items based on whether 'all' is selected or specific IDs are provided
        $items = $this->fetchItemsByIdsOrAll($query, $isAll, $inputIds);


        $user->deleteMediaByIds($inputIds, 'files');

        // Load eager relationships if defined
        $eagerLoading = $model->getEagerLoading();

        return $eagerLoading ? $user->load($eagerLoading) : $user;
    }
    #endregion ===================== End File Handling Methods =====================

    
    #region ===================== Start Private and Protected Methods =====================

    /**
     * Handle bulk actions (restore, force delete, or soft delete) on a given Eloquent model.
     *
     * This method processes multiple records based on the provided IDs. 
     * It supports:
     *  - Restoring soft-deleted records.
     *  - Permanently deleting soft-deleted records.
     *  - Soft deleting active records.
     * It can also operate on all records by passing "all" as the input.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model   The Eloquent model instance.
     * @param  string                                $action  The action to perform: 'restore', 'forceDelete', or 'delete'.
     * @return \Illuminate\Http\JsonResponse                 Standardized API response.
     */
    protected function handleBulkRestoreAndDelete($request, $model, string $action)
    {
        // Get the validated 'ids' input from the request
        $inputIds = $request->input('ids', []);

        // Check if the operation applies to all items
        $isAll = $inputIds === 'all';

        // $resultProtectedIds = $model::getProtectedUserIds(json_decode($inputIds));
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        // $query = $model::whereNotIn('id', $resultProtectedIds);
        
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
        // Get 'ids' input from the request (can be "all", array, or JSON string)
        $inputIds = $request->input('ids', []);

        // Check if the operation applies to all items
        $isAll = $inputIds === 'all';

        // $resultProtectedIds = $model::getProtectedUserIds(json_decode($inputIds));
        // if ($resultProtectedIds instanceof ServiceResponse) {
        //     return $resultProtectedIds;
        // }

        // Get the action to perform: 'activate', 'deactivate', or 'toggle' (default: toggle)
        $action = $request->input('action', 'toggle');
        
        // Base query excluding soft-deleted records
        $query = $model->withoutTrashed();
        
        // Fetch users based on whether 'all' is selected or specific IDs are provided
        $users = $this->fetchItemsByIdsOrAll($query, $isAll, $inputIds);

        // Initialize result arrays
        $processedIds  = []; // Successfully updated
        $failedIds     = []; // Failed to update due to errors
        $notFoundIds   = []; // IDs that were not found in DB

        // Process each user in the collection
        foreach ($users as $user) {
            try {
                // 🔁 Determine the new status based on the requested action
                $newStatus = $this->getNewStatusByAction($action, $user);

                // 🔍 If status is already set correctly, skip update
                if ($user->is_active === $newStatus) {
                    $processedIds[] = $user->id;
                    continue;
                }

                // 💾 Update the user's status
                $user->update(['is_active' => $newStatus->value]);
                $processedIds[] = $user->id;

            } catch (\Throwable $e) {
                // Something went wrong updating this user
                $failedIds[] = $user->id;
            }
        }

            // 🔍 Determine which requested IDs were not found in the database
            $notFoundIds = $isAll ? [] : array_values(array_diff($inputIds, $processedIds));

        // 🔄 Fetch updated users again for response
        $updatedUsers = $model->whereIn('id', $processedIds)->get();

        // 🔁 Load relationships if defined in model
        if (method_exists($model, 'getEagerLoading') && $model->getEagerLoading()) {
            $updatedUsers->load($model->getEagerLoading());
        }

        $data = [
            'updated_items'   => $updatedUsers,
            'failed_ids'      => array_values($failedIds),
            'not_found_ids'   => array_values($notFoundIds),
        ];

        return $data;
    }
    #endregion ===================== End Private and Protected Methods =====================

}
