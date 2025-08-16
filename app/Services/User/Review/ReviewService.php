<?php

namespace App\Services\User\Review;

use App\Services\Eloquent\EloquentService;
use App\Services\General\GeneralMethods\GeneralService;
use App\Repositories\Eloquent\EloquentRepository;
use App\Services\ServiceResponse;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;
use App\Traits\Services\ChecksOwnershipTrait;

/**
 * Class ReviewService
 *
 * This service handles operations related to the Review module,
 * including storing, updating, and deleting reviews.
 * 
 * @package App\Services\User\Review
 * 
 */

class ReviewService extends EloquentService implements ReviewServiceInterface
{
    use ChecksOwnershipTrait;

    /** @var EloquentRepository */
    protected $eloquentRepo;

    /** @var GeneralService */
    protected $generalService;
    
    /**
     * Constructor
     *
     * @param EloquentRepository    $eloquentRepo
     * @param GeneralService        $generalService
     */
    public function __construct(EloquentRepository $eloquentRepo, GeneralService $generalService)
    {
        $this->eloquentRepo    = $eloquentRepo;
        $this->generalService  = $generalService;
    }

    #region ===================== Start CRUD Methods =====================
    
    /**
     * Store a new review in the database.
     *
     * @param object $request The request object containing validated data.
     * @param object $model The review model instance.
     * @return object The created review with loaded relations.
     */
    public function store($request, $model)
    {
        // Get validated input data from request
        $data = $request->validated();

        // Assign static user_id (for testing; replace with userApi()->id)
        $data['user_id'] = userApi()?->id;

        // Create the review item
        $item = $model->create($data);

        // Load and return any eager loaded relations
        return $item->load($model->getEagerLoading());
    }

    /**
     * Update an existing review record.
     *
     * @param object $request The request object containing validated data.
     * @param int $id The ID of the review to update.
     * @param object $model The review model instance.
     * @return object JSON response with updated review or error.
     */
    public function update($request, $id, $model)
    {
        // Get validated input data
        $data = $request->validated();

        // Find the review item by ID
        $item = $this->baseRepo->findOrFailApi($id, $model);

        // Remove file/image fields from update payload
        $enteredData = array_diff_key($data, array_flip(['file', 'image']));

        // Assign static user_id (for testing; replace with userApi()->id)
        $enteredData['user_id'] = userApi()?->id;

        // Update the item
        $item->update($enteredData);

        // Load and return any eager loaded relations
        $data = $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;
        
        return $data;

    }

    #endregion ===================== End CRUD Methods =====================

    #region ===================== Start TRASH Methods =====================

    /**
     * Delete a review item (soft delete or force delete depending on model).
     *
     * @param int $id The ID of the review to delete.
     * @param object $model The review model instance.
     * @return object Deleted review item with optional loaded relations.
     */
    public function destroy($id, $model)
    {
        // Find the review item
        $item = $this->baseRepo->findWithoutTrashedOrFail($id, $model);

        // ownership check
        $this->ensureOwnership($item);

        // Delete the item
        $item->delete();

        // Handle soft delete vs. permanent delete
        if (isSoftDeletes($model)) {
            return $eagerLoading ? $item->load($eagerLoading) : $item;
        } else {
            $this->mediaClass->handleFileDeletion($item);
        }

        return $item;
    }
    #endregion ===================== Start TRASH Methods =====================

}
