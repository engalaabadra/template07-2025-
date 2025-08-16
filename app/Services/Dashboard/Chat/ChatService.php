<?php

namespace App\Services\Dashboard\Chat;

use App\Services\Eloquent\EloquentService;
use App\Events\MessageCreated;
use App\GeneralClasses\MediaClass;
use App\Models\User;
use App\Repositories\Eloquent\EloquentRepository;
use App\Repositories\Base\BaseRepository;
use App\Services\ServiceResponse;
use App\Traits\Services\ChecksOwnershipTrait;
use App\Exceptions\ApiResponseException;
use App\Enums\ServiceResponseEnum;

/**
 * ChatService
 *
 * This service class implements ChatServiceInterface and handles
 * chat-related business logic including storing, updating, deleting,
 * force deleting, and bulk actions on chat records.
 */
class ChatService extends EloquentService implements ChatServiceInterface
{
    use ChecksOwnershipTrait;

    /** @var BaseRepository */
    protected $baseRepo;

    /** @var EloquentRepository */
    protected $eloquentRepo;
    
    /**
     * Constructor
     *
     * @param BaseRepository    $baseRepo
     * @param EloquentRepository    $eloquentRepo
     * 
     */
    public function __construct(BaseRepository $baseRepo, EloquentRepository $eloquentRepo)
    {
        $this->baseRepo = $baseRepo;
        $this->eloquentRepo    = $eloquentRepo;
    }

    #region ===================== Start CRUD Methods =====================
    /**
     * Store a new chat record.
     *
     * @param object $request Validated request object.
     * @param object $model   Chat model instance.
     * @return object         Newly created chat record with eager loaded relations.
     */
    protected function store($request, $model)
    {
        // Extract validated data and remove 'files' key
        $data = $request->validated();
        $enteredData = array_diff_key($data, array_flip(['files']));

        // Assign static user_id and client_id (replace with dynamic logic if needed)
        $enteredData['user_id'] = adminApi()?->id;

        $client = $this->baseRepo->findOrFailApi($enteredData['client_id'], User::class);

        // Create chat record
        $chat = $model->create($enteredData);

        // Broadcast new message event to others
        broadcast(new MessageCreated($chat))->toOthers();

        // Handle multiple file uploads if present
        $folder = modelName($model);

        // Check if files are provided
        if (isset($data['files'])) {
            // Upload multiple files to the media collection
            $newItem->uploadMultipleMedia($request->file('files'), 'file', $folder);
        }

        // Return created chat with eager loaded relationships if any
        return $model->getEagerLoading() ? $chat->load($model->getEagerLoading()) : $chat;
    }

    /**
     * Update a chat record.
     *
     * @param object $request Validated request object.
     * @param int    $id      ID of chat to update.
     * @param object $model   Chat model instance.
     * @return object         Updated chat record with eager loaded relations.
     */
    protected function update($request, $id, $model)
    {
        $data = $request->validated();

        // Find chat by ID
        $chat = $this->baseRepo->findOrFailApi($id, $model);


        // check if this chat for her
        $this->ensureOwnership($chat);

        // Filter out 'file' field from data
        $enteredData = array_diff_key($data, array_flip(['files']));

        // Update the chat record
        $chat->update($enteredData);

        // Handle multiple file uploads if present
        $folder = modelName($model);
        // Check if files are provided
        if (isset($data['files'])) {
            // Upload multiple files to the media collection
            $newItem->uploadMultipleMedia($request->file('files'), 'file', $folder);
        }

        // Return updated chat with eager loaded relations if any
        return $model->getEagerLoading() ? $chat->load($model->getEagerLoading()) : $chat;
    }

        /**
     * Force delete a single chat record permanently.
     *
     * @param int    $id    ID of chat to force delete.
     * @param object $model Chat model instance.
     * @return void|object  Void on success or ServiceResponse if not found.
     */
    public function forceDelete($id, $model)
    {
        // Get the trashed chat by ID without global scopes
        $chat = $this->baseRepo->findOnlyTrashedOrFail($id, $model);

        // check if this chat for her
        $this->ensureOwnership($chat);

        $chat->forceDelete();
    }

    /**
     * Force delete multiple chat records permanently.
     *
     * @param object $model Chat model instance.
     * @return void|object  Void on success or ServiceResponse on error.
     */
    public function forceDeleteMany($request, $model)
    {
        // Handle bulk deletion logic
        return $this->handleBulkRestoreAndDelete($request, $model, 'forceDelete');
    }

    #endregion ===================== End CRUD Methods =====================

    #region ===================== Start ACTIVATION Methods =====================
    /**
     * Activate multiple records.
     *
     * @param object $model  The model to query.
     * @return array         Activated records.
     */
    public function changeActivateMany($request, $model)
    {
        return $this->handleBulkActivation($request, $model, 'activate');
    }
    #endregion ===================== End ACTIVATION Methods =====================

    #region ===================== Start TRASH Methods =====================
    /**
     * Delete a chat record (soft or hard).
     *
     * @param int    $id    ID of chat to delete.
     * @param object $model Chat model instance.
     * @return object|null  Deleted chat or null if not found.
     */
    public function destroy($id, $model)
    {
        // Get the chat by ID, excluding trashed and global scopes
        $chat = $this->baseRepo->findWithoutTrashedOrFail($id, $model);


        // check if this chat for her
        $this->ensureOwnership($chat);

        $chat->delete();

        $data = $model->getEagerLoading()                 // Eager load relations if defined
            ? $chat->load($model->getEagerLoading())
            : $chat;

        return $data;

    }

    /**
     * Bulk delete items.
     *
     * @param object $model The model to query.
     * @return mixed
     */
    public function destroyMany($request, $model)
    {
        // Handle bulk deletion logic
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
        // Find the trashed record by ID or return 404
        $chat = $this->baseRepo->findOnlyTrashedOrFail($id, $model);

        // check if this chat for her
        $this->ensureOwnership($chat);

        // If the model has defined unique fields
        // if (!empty($model::$uniqueFields)) {
        //     // Check for conflict with existing (non-deleted) records
        //     $conflict = $model::query()
        //         ->where('title', $chat->title) // Adjust this to match your unique fields
        //         ->whereNull('deleted_at')
        //         ->when($model->getKeyName(), fn($q) => $q->where($model->getKeyName(), '!=', $chat->getKey()))
        //         ->exists();

        //     // If conflict exists, handle it using safeRestoreById
        //     if ($conflict) {
        //         $actionRestore = request()->input('action_restore') ?? 'modify';
        //         $resultSafeRestore = $this->safeRestoreById($chat, $actionRestore);
        //         if ($resultSafeRestore instanceof ServiceRespone) {
        //             return $resultSafeRestore;
        //         }
        //     } else {
        //         // No conflict, perform normal restore
        //         $chat->restore();
        //     }
        // }

        // Perform restore again (in case previous block was skipped)
        $chat->restore();

        // Eager load relations if defined
        $data = $model->getEagerLoading() ? $chat->load($model->getEagerLoading()) : $chat;

        return $data;

    }
    /**
     * Restore multiple trashed records.
     *
     * @param object $model The model to query.
     * @return array Restored records.
     */
    public function restoreMany($request, $model)
    {
        return $this->handleBulkRestoreAndDelete($request, $model, 'restore');
    }
    #endregion ===================== End TRASH Methods =====================

    #region ===================== Start Protected & Private Methods =====================

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

        // Check if the operation applies to all chats
        $isAll = $inputIds === 'all';

        // Prepare base query: soft-deleted for restore/delete, or active
        $query = in_array($action, ['restore', 'forceDelete'])
            ? $model->onlyTrashed()
            : $model->withoutTrashed();

        // Fetch chats based on whether 'all' is selected or specific IDs are provided
        $chats = $this->fetchItemsByIdsOrAll($query, $isAll, $inputIds);
       
        // Initialize tracking arrays
        $processedIds     = []; // Successfully processed
        $unauthorizedIds  = []; // Items current user doesn't own
        $conflictIds      = []; // Optional: handle uniqueness/conflicts later
        $notFoundIds   = []; // IDs that were not found in DB
        $failedIds     = []; // Failed to update due to errors

        // Loop through items
        foreach ($chats as $chat) {
            try {
                // check if this chat for her
                $this->ensureOwnership($chat);
            } catch (ApiResponseException $e) {
                $unauthorizedIds[] = $chat->id;
            }

            try {
                // Perform the requested action
                match ($action) {
                    'restore'     => $chat->restore(),
                    'forceDelete' => $chat->forceDelete(),
                    'destroy'     => $chat->delete(),
                    default       => throw new \InvalidArgumentException("Invalid action: {$action}"),
                };

                // Track success
                $processedIds[] = $chat->id;
            } catch (\Throwable $e) {
                $failedIds[] = $chat->id;
            }
        }

        // Determine which requested IDs weren't found in DB (if not "all")
        $notFoundIds = $isAll ? [] : array_values(array_diff($ids, $chats->pluck('id')->toArray()));

        $data = [
            'processed_ids'    => $processedIds,
            'not_found_ids'    => $notFoundIds,
            'unauthorized_ids' => $unauthorizedIds,
            'conflict_ids'     => $conflictIds,
            'failed_ids'  => $failedIds,

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
     * - IDs that the chat is unauthorized to modify
     *
     * @param \Illuminate\Database\Eloquent\Model $model The chat model instance
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
        $unauthorizedIds  = [];

        foreach ($chats as $chat) {
            // Check ownership
            $ownershipResponse = $this->ensureOwnership($chat);
            if ($ownershipResponse instanceof ServiceResponse) {
                $unauthorizedIds[] = $chat->id;
                continue;
            }

            try {
                // Determine the new status based on the requested action
                $newStatus = $this->getNewStatusByAction($action, $chat);

                // If status is already set correctly, skip update
                if ($chat->is_active === $newStatus) {
                    $processedIds[] = $chat->id;
                    continue;
                }

                // Update the chat's status
                $chat->update(['is_active' => $newStatus->value]);
                $processedIds[] = $chat->id;

            } catch (\Throwable $e) {
                // Something went wrong updating this chat
                $failedIds[] = $chat->id;
            }
        }

        
        // Determine which requested IDs were not found in the database
        $notFoundIds = $isAll ? [] : array_values(array_diff($ids, $processedIds));

        // 🔄 Fetch updated chats again for response
        $updatedChats = $model->whereIn('id', $processedIds)->get();

        // Load relationships if defined in model
        if (method_exists($model, 'getEagerLoading') && $model->getEagerLoading()) {
            $updatedChats->load($model->getEagerLoading());
        }

        $data = [
            'updated_chats'     => $updatedChats,
            'failed_ids'        => array_values($failedIds),
            'not_found_ids'     => array_values($notFoundIds),
            'unauthorized_ids'  => array_values($unauthorizedIds),
        ];
        
        return $data;

    }

    #endregion ===================== End Protected & Private Methods =====================

}
