<?php
namespace App\Services\User\Notification;

use App\Services\Eloquent\EloquentService;


/**
 * NotificationService class handles all business logic related to Notifications in the dashNotification.
 * 
 * This class extends the generic EloquentService and implements NotificationServiceInterface
 * to ensure contract compliance and reuse of common service logic.
 * 
 * @package App\Services\User\Notification
 * 
 */
class NotificationService extends EloquentService implements NotificationServiceInterface
{
    
    #region Constructor
    public function __construct() {
        parent::__construct();
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
        return parent::getData($model);
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
        return parent::show($user, $model);
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
        return parent::report($modelClass, $filters);
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
        return parent::store($request, $model);
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
        return parent::update($request, $id, $model);

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
        return parent::forceDelete($id, $model);
    }

    /**
     * Permanently delete multiple trashed items.
     *
     * @param object $model The model to query.
     * @return mixed
     */
    protected function forceDeleteMany($request, $model)
    {
        return parent::forceDeleteMany($request, $model);
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
        return parent::trash($model);
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
        return parent::destroy($id, $model);
    }

    /**
     * Bulk delete items.
     *
     * @param object $model The model to query.
     * @return mixed
     */
    protected function destroyMany($request, $model)
    {
        return parent::destroyMany($request, $model);
        
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
        return parent::restore($id, $model);
    }

    /**
     * Restore multiple trashed records.
     *
     * @param object $model The model to query.
     * @return array Restored records.
     */
    protected function restoreMany($request, $model)
    {
        return parent::restoreMany($request, $model);

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
        return parent::changeActivate($request, $id, $model);   
    }

    /**
     * Activate multiple records.
     *
     * @param object $model  The model to query.
     * @return array         Activated records.
     */
    public function changeActivateMany($request, $model)
    {
        return parent::changeActivateMany($request, $model);   
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
        return parent::uploadFile($request, $id, $model);
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
        return parent::uploadFiles($request, $id, $model);   
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
       return parent::deleteFile($id, $model);
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
        return parent::deleteFiles($request, $id, $model);
    }
    #endregion ===================== End File Handling Methods =====================
   
    #region ===================== Start Protected & Private Methods =====================
    
    #endregion ===================== End Protected & Private Methods =====================

    
}

