<?php
namespace App\Services\User\Chat;

use App\Services\Eloquent\EloquentService;
use App\Events\MessageCreated;
use App\GeneralClasses\MediaClass;
use App\Models\User;
use App\Repositories\Eloquent\EloquentRepository;
use App\Services\ServiceResponse;
use App\Traits\ChecksOwnershipTrait;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;


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

    /** @var EloquentRepository */
    protected $eloquentRepo;
    
    /**
     * Constructor
     *
     * @param EloquentRepository    $eloquentRepo
     * 
     */
    public function __construct(EloquentRepository $eloquentRepo)
    {
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
    public function store($request, $model)
    {
        // Extract validated data and remove 'files' key
        $data = $request->validated();
        $enteredData = array_diff_key($data, array_flip(['files']));

        // Get authenticated user and client ID
        $user = adminApi();
        $clientId = clientId();

        // Validate presence of clientId
        if (!$clientId) {
            throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.validation_failed'), [
                'client_id' => 'validation.You must enter client_id in param'
            ]);

        }

        // Find client user
        $client = User::find($clientId);
        if (!$client) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }
        
        // Assign static user_id and client_id (replace with dynamic logic if needed)
        $enteredData['user_id'] = adminApi()?->id;
        $enteredData['client_id'] = $clientId;

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
    public function update($request, $id, $model)
    {
        $data = $request->validated();

        // Find chat by ID
        $chat = $model->find($id);
        if (!$chat) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

        // check if this chat for her
        $this->ensureOwnership($chat);

        // Filter out 'file' field from data
        $enteredData = array_diff_key($data, array_flip(['file']));

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
        $chat = $model->onlyTrashed()->where('id', $id)->first();

        if (!$chat) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

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
    public function forceDeleteMany($model)
    {
        // Handle bulk deletion logic
        return $this->handleBulkRestoreAndDelete($model, 'forceDelete');
    }

    #endregion ===================== End CRUD Methods =====================

    #region ===================== Start ACTIVATION Methods =====================
    
    /**
     * Toggle activation status for a record.
     *
     * @param int    $id     The ID of the record to toggle activation.
     * @param object $model  The model to query.
     * @return object        Updated record with toggled activation status.
     */
    public function changeActivate($id, $model)
    {
        // Find the record without applying global scopes
        $item = $model->whereNull('deleted_at')->find($id); // find item only in table not in trash to activate it

        if (!$item) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

        // Toggle the activation status (active ↔ inactive)
        $isActiveEnum = $item->is_active === IsActiveEnum::ACTIVE
            ? IsActiveEnum::NOT_ACTIVE
            : IsActiveEnum::ACTIVE;

        $item->update([
            'is_active' => $isActiveEnum->value, // Use enum value for database
        ]);

        // Load related models if eager loading is defined
        $data = $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;

        return $data;
    }


    /**
     * Activate multiple records.
     *
     * @param object $model  The model to query.
     * @return array         Activated records.
     */
    public function changeActivateMany($model)
    {
        return $this->handleBulkActivation($model, 'activate');
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
        $chat = $model->where('id', $id)->first();

        if (!$chat || $chat->deleted_at !== null) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

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
    public function destroyMany($model)
    {
        // Handle bulk deletion logic
        return $this->handleBulkRestoreAndDelete($model, 'destroy');
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
        $chat = $model->onlyTrashed()->find($id);
        if (!$chat) throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);

        // check if this chat for her
        $this->ensureOwnership($chat);

        // If the model has defined unique fields
        if (!empty($model::$uniqueFields)) {
            // Check for conflict with existing (non-deleted) records
            $conflict = $model::query()
                ->where('title', $chat->title) // Adjust this to match your unique fields
                ->whereNull('deleted_at')
                ->when($model->getKeyName(), fn($q) => $q->where($model->getKeyName(), '!=', $chat->getKey()))
                ->exists();

            // If conflict exists, handle it using safeRestoreById
            if ($conflict) {
                $actionRestore = request()->input('action_restore') ?? 'modify';
                $resultSafeRestore = $this->safeRestoreById($chat, $actionRestore);
                if ($resultSafeRestore instanceof ServiceRespone) {
                    return $resultSafeRestore;
                }
            } else {
                // No conflict, perform normal restore
                $chat->restore();
            }
        }

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
    public function restoreMany($model)
    {
        return $this->handleBulkRestoreAndDelete($model, 'restore');
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
    protected function handleBulkRestoreAndDelete($model, string $action)
    {
        // ✅ Get the validated 'ids' input from the request
        $inputIds = request('ids');

        // ✅ Determine if the operation applies to all items
        $isAll = $inputIds === 'all';

        // ✅ Prepare base query: soft-deleted for restore/delete, or active
        $query = in_array($action, ['restore', 'forceDelete'])
            ? $model->onlyTrashed()
            : $model->withoutTrashed();

        // ✅ Fetch the items to process
        if ($isAll) {
            $chats = $query->get();
            $ids = $chats->pluck('id')->toArray(); // Used for tracking not found
        } else {
            $ids = $inputIds;
            $chats = $query->whereIn('id', $ids)->get();
        }

        // ✅ Return 404 if no matching records found (unless it's 'all')
        if ($chats->isEmpty() && !$isAll) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }

        // ✅ Initialize tracking arrays
        $processedIds     = []; // Successfully processed
        $unauthorizedIds  = []; // Items current user doesn't own
        $conflictIds      = []; // Optional: handle uniqueness/conflicts later

        // ✅ Loop through items
        foreach ($chats as $chat) {
            // ✅ Check ownership
            $ownershipResponse = $this->ensureOwnership($chat);
            if ($ownershipResponse instanceof ServiceResponse) {
                $unauthorizedIds[] = $chat->id;
                continue; // Skip unauthorized item
            }

            try {
                // ✅ Perform the requested action
                match ($action) {
                    'restore'     => $chat->restore(),
                    'forceDelete' => $chat->forceDelete(),
                    'destroy'     => $chat->delete(),
                    default       => throw new \InvalidArgumentException("Invalid action: {$action}"),
                };

                // ✅ Track success
                $processedIds[] = $chat->id;
            } catch (\Throwable $e) {
                // Log or handle error if needed
            }
        }

        // ✅ Determine which requested IDs weren't found in DB (if not "all")
        $notFoundIds = $isAll ? [] : array_values(array_diff($ids, $chats->pluck('id')->toArray()));

        $data = [
            'processed_ids'    => $processedIds,
            'not_found_ids'    => $notFoundIds,
            'unauthorized_ids' => $unauthorizedIds,
            'conflict_ids'     => $conflictIds,
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
    protected function handleBulkActivation($model)
    {
        $ids = request()->input('ids');
        $action = request()->input('action', 'toggle');

        if (!$ids || (!is_array($ids) && $ids !== 'all')) {
            throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.validation_failed'), [
                'ids' => 'Invalid IDs'
            ]);
        }

        $query = $model->newQuery()->withoutTrashed();

        if ($ids === 'all') {
            $chats = $query->get();
            $ids = $chats->pluck('id')->toArray();
        } else {
            $ids = is_array($ids) ? $ids : json_decode($ids, true);
            $chats = $query->whereIn('id', $ids)->get();
        }

        $processedIds     = [];
        $failedIds        = [];
        $notFoundIds      = [];
        $unauthorizedIds  = [];

        foreach ($chats as $chat) {
            // ✅ Check ownership
            $ownershipResponse = $this->ensureOwnership($chat);
            if ($ownershipResponse instanceof ServiceResponse) {
                $unauthorizedIds[] = $chat->id;
                continue;
            }

            try {
                switch ($action) {
                    case 'activate':
                        $newStatus = \App\Enums\IsActiveEnum::ACTIVE;
                        break;
                    case 'deactivate':
                        $newStatus = \App\Enums\IsActiveEnum::NOT_ACTIVE;
                        break;
                    case 'toggle':
                    default:
                        $newStatus = $chat->is_active === \App\Enums\IsActiveEnum::ACTIVE
                            ? \App\Enums\IsActiveEnum::NOT_ACTIVE
                            : \App\Enums\IsActiveEnum::ACTIVE;
                        break;
                }

                if ($chat->is_active === $newStatus) {
                    $processedIds[] = $chat->id;
                    continue;
                }

                $chat->update(['is_active' => $newStatus->value]);
                $processedIds[] = $chat->id;
            } catch (\Throwable $e) {
                $failedIds[] = $chat->id;
            }
        }

        $foundIds = $chats->pluck('id')->toArray();
        $notFoundIds = array_diff($ids, $foundIds);

        $updatedChats = $model->whereIn('id', $processedIds)->get();

        if (method_exists($model, 'getEagerLoading') && $model->getEagerLoading()) {
            $updatedChats->load($model->getEagerLoading());
        }

        $data = [
            'updated_items'     => $updatedChats,
            'failed_ids'        => array_values($failedIds),
            'not_found_ids'     => array_values($notFoundIds),
            'unauthorized_ids'  => array_values($unauthorizedIds),
        ];

        return $data;
        
    }
    #endregion ===================== End Private and Protected Methods =====================

}

