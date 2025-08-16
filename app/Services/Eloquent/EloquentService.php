<?php

namespace App\Services\Eloquent;

use App\Repositories\Eloquent\EloquentRepository;
use App\Repositories\Base\BaseRepository;
use App\Services\Translation\TranslationService;
use App\Enums\IsActiveEnum;
use App\Services\ServiceResponse;
use App\Models\Banner;
use DB;
use App\Traits\Services\HandlesServiceTransactions;
use App\Traits\Services\HandlesActivationTrait;
use App\Traits\Services\HandlesBulkOperationsTrait;
use App\Traits\Services\HandlesTranslationsAndFilesTrait;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;

/**
 * Class EloquentService
 *
 * Base service class for handling Eloquent model logic and business rules.
 * Provides generic CRUD functionality with support for translation and scoping.
 */
class EloquentService 
{
    use HandlesServiceTransactions, HandlesActivationTrait, HandlesBulkOperationsTrait, HandlesTranslationsAndFilesTrait;

    #region Constructor
    /** @var BaseRepository */
    protected $baseRepo;

   /** @var EloquentRepository */
    protected $eloquentRepo;

    /**
     * Constructor
     *
     * @param BaseRepository    $baseRepo
     * @param EloquentRepository    $eloquentRepo
     * @param TranslationService    $translationService
     */

    public function __construct(BaseRepository $baseRepo, EloquentRepository $eloquentRepo, TranslationService $translationService) {
        $this->baseRepo = $baseRepo;
        $this->eloquentRepo = $eloquentRepo;
        $this->translationService = $translationService;
    }
    #endregion Constructor


    #region ===================== Start CRUD Methods =====================

    /**
     * Get Data (all, pagination) -> Taking into consideration language.
     * @param object $model The model to query.
     * 
     * @return array Paginated or full collection of results.
     */
    public function getData($model)
    {
        return $this->eloquentRepo->getData($model);
    }

    /**
     * Show a specific record.
     * @param int $id The ID of the record to show.
     * @param object $model The model to query.
     * 
     * @return object The requested record.
     */
    public function show($user, $model) : object
    {
        return $this->eloquentRepo->show($user, $model);
    }

    /**
     * Generate a grouped report with optional filters, for both API and Web usage.
     *
     * @param  string               $modelClass   Fully qualified class name of the model.
     * @param  array<string,mixed> $filters       Optional filters (column => value or array of values) (e.g: ['status' => ['is_active', 'pending'], 'payment_method' => 'bank'])
     *
     * @return \Illuminate\Support\Collection|\Illuminate\Http\JsonResponse
     * 
     */
    public function report(string $modelClass, array $filters = []) : \Illuminate\Support\Collection|\Illuminate\Http\JsonResponse
    {
        return $this->eloquentRepo->report($modelClass, $filters);
    }

    /**
     * Store a new record.
     *
     * @param object $request  The request object containing validated data.
     * @param object $model    The model to query.
     * @return object          Created record with optional eager loading.
     */
    protected function store($request, $model)
    {
        // Get validated data and filter out 'file' and 'image'
        $data = $request->validated();
        $enteredData = array_diff_key($data, array_flip(['file', 'image', 'files', 'images']));


        // Create the new model record
        $newItem = $model->create($enteredData);

        // Refresh the model if 'is_active' was not part of the input
        refreshIfMissing($enteredData, $newItem);

        $this->handleTranslationsAndFiles($request, $model, $newItem, 'store');

        // Load related models if eager loading is defined
        $data = $model->getEagerLoading() ? $newItem->load($model->getEagerLoading()) : $newItem;

        return $data;

    }


    /**
     * Update a specific record.
     *
     * @param object $request  The request object containing validated data.
     * @param int    $id       The ID of the record to update.
     * @param object $model    The model to query.
     * @return object          Updated record.
     */
    protected function update($request, $id, $model)
    {
        // Get validated data and find the record
        $data = $request->validated();

        $item = $this->baseRepo->findOrFailApi($id, $model);

        // Remove 'file' and 'image' keys from input
        $enteredData = array_diff_key($data, array_flip(['file', 'image', 'files', 'images']));

        // Update the record
        $item->update($enteredData);

        $this->handleTranslationsAndFiles($request, $model, $item, 'update');

        // Load related models if eager loading is defined
        $data = $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;
       
        return $data;

    }
        
    /**
     * Permanently delete a single trashed item.
     *
     * @param mixed $id The ID of the item to force delete.
     * @param object $model The model to query.
     * @return \Illuminate\Http\JsonResponse|null
     */
    protected function forceDelete($id, $model)
    {
        // Get the trashed item by ID without global scopes
        $item = $this->baseRepo->findOnlyTrashedOrFail($id, $model);

        // Permanently delete the item
        $item->forceDelete();
    }

    /**
     * Permanently delete multiple trashed items.
     *
     * @param object $model The model to query.
     * @return mixed
     */
    protected function forceDeleteMany($request, $model)
    {
        // Handle bulk force deletion logic
        return $this->handleBulkRestoreAndDelete($request, $model, 'forceDelete');
    }

    #endregion ===================== End CRUD Methods =====================

    #region ===================== Start TRASH Methods =====================

    /**
     * Get trashed records with optional eager loading (pagination).
     * @param object $model The model to query.
     * 
     * @return array Paginated or full collection of trashed records.
     */
    public function trash($model) : array
    {
        return $this->eloquentRepo->trash($model);
    }

    /**
     * Delete a single item by ID.
     *
     * @param mixed $id The ID of the item to delete.
     * @param object $model The model to query.
     * @return object|\Illuminate\Http\JsonResponse The deleted item or 404 response.
     */
    protected function destroy($id, $model)
    {
        // Get the item by ID, excluding trashed and global scopes
        $item = $this->baseRepo->findWithoutTrashedOrFail($id, $model);

        // Soft delete the item (if model uses SoftDeletes), or permanently delete it
        $item->delete();

        $data = $model->getEagerLoading()                 // Eager load relations if defined
            ? $item->load($model->getEagerLoading())
            : $item;

        return $data;

    }

    /**
     * Bulk delete items.
     *
     * @param object $model The model to query.
     * @return mixed
     */
    protected function destroyMany($request, $model)
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
    protected function restore($id, $model)
    {
        // Find the trashed record by ID or return 404
        $item = $this->baseRepo->findOnlyTrashedOrFail($id, $model);

        // Perform restore again (in case previous block was skipped)
        $item->restore();

        // Eager load relations if defined
        $data = $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;

        return $data;

    }

    /**
     * Restore multiple trashed records.
     *
     * @param object $model The model to query.
     * @return array Restored records.
     */
    protected function restoreMany($request, $model)
    {
        return $this->handleBulkRestoreAndDelete($request, $model, 'restore');
    }

    #endregion ===================== End TRASH Methods =====================

    #region ===================== Start ACTIVATION Methods =====================

    /**
     * Toggle activation status for a record.
     *
     * @param int    $id     The ID of the record to toggle activation.
     * @param object $model  The model to query.
     * @return object        Updated record with toggled activation status.
     */
    public function changeActivate($request, $id, $model)
    {
        // Find the record without applying global scopes
        $item = $this->baseRepo->findWithoutTrashedOrFail($id, $model); // find item only in table not in trash to activate it

        // Get the action to perform: 'activate', 'deactivate', or 'toggle' (default: toggle)
        $action = $request->input('action', 'toggle');

        // Determine the new status based on the requested action
        $newStatus = $this->getNewStatusByAction($action, $item);
        
        // Update the item's status
        $item->update(['is_active' => $newStatus->value]);

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
    public function changeActivateMany($request, $model)
    {
        return $this->handleBulkActivation($request, $model, 'activate');
    }

    #endregion ===================== End ACTIVATION Methods =====================

    #region ===================== Start File Handling Methods =====================

    /**
     * Upload single file or image for a model.
     *
     * @param  Request $request
     * @param  int     $id
     * @param  Model   $model
     * @return JsonResponse
     */
    public function uploadFile($request, $id, $model)
    {
        // Get validated data from request
        $data = $request->validated();

        // Find the model item without global scopes
        $item = $this->baseRepo->findOrFailApi($id, $model);

        // Get folder name from model class name
        $folder = modelName($model);

        // Upload file or image if provided and supported by the model
        foreach (['file', 'image'] as $type) {
            if (isset($data[$type]) && method_exists($item, $type)) {
                $item->uploadSingleMedia($request->file($type), $type, $folder);
            }
        }

        $data = $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;

        return $data;

    }
    /**
     * Upload multiple images or files for a specific model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  mixed  $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFiles($request, $id, $model)
    {
        // Get validated data from request
        $data = $request->validated();

        // Find the model item without global scopes
        $item = $this->baseRepo->findOrFailApi($id, $model);

        // Get folder name from model class name
        $folder = modelName($model);

        // Upload files or images if provided and supported by the model
        foreach (['files', 'images'] as $type) {
            if (isset($data[$type]) && method_exists($item, $type)) {
                $item->uploadSingleMedia($request->file($type), $type, $folder);
            }
        }

        // Load eager relationships if defined on the model
        $data = $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;

        return $data;
        
    }

    /**
     * Delete a single image or file from the model.
     *
     * @param  int  $id
     * @param  mixed  $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFile($id, $model)
    {
        // Find the model item without global scopes
        $item = $this->baseRepo->findOrFailApi($id, $model);

        // Delete image or file if supported by the model  
        foreach (['image', 'file'] as $type) {  
            if (method_exists($item, $type)) {  
                $item->deleteSingleMedia($type);  
                break;  
            }  
        }
        // Load eager relationships if defined
        $data = $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;

        return $data;
                
    }

    /**
     * Delete multiple images and/or files from a model.
     *
     * @param  int  $id
     * @param  mixed  $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFiles($request, $id, $model)
    {
        // Find the model item without global scopes
        $item = $this->baseRepo->findOrFailApi($id, $model);

        $inputIds = $request->input('ids', []);
        $isAll = $inputIds === "all";

        $query = $model;
        // Fetch items based on whether 'all' is selected or specific IDs are provided
        $items = $this->fetchItemsByIdsOrAll($query, $isAll, $inputIds);

        $item->deleteMediaByIds($inputIds, 'files');

        // Load eager relationships if defined
        $eagerLoading = $model->getEagerLoading();

        return $eagerLoading ? $user->load($eagerLoading) : $user;
        
    }
    #endregion ===================== End File Handling Methods =====================
   
}

