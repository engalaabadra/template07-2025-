<?php

namespace App\Services\Dashboard\Review;

use App\Services\General\GeneralMethods\GeneralService;
use App\Services\Eloquent\EloquentService;
use App\Repositories\Eloquent\EloquentRepository;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;
use App\Traits\Services\ChecksOwnershipTrait;
use App\Repositories\Base\BaseRepository;

/**
 * ReviewService
 *
 * This service class handles the business logic for managing reviews in the dashboard.
 * It supports operations such as storing, updating, and deleting review records.
 */

class ReviewService extends EloquentService implements ReviewServiceInterface
{
    use ChecksOwnershipTrait;

    /** @var BaseRepository */
    protected $baseRepo;

    /** @var EloquentRepository */
    protected $eloquentRepo;

    /**
     * Constructor to initialize dependencies.
     *
     * @param BaseRepository    $baseRepo
     * @param EloquentRepository  $eloquentRepo    Handles generic Eloquent repository logic.
     */
    public function __construct(BaseRepository $baseRepo, EloquentRepository $eloquentRepo)
    {
        $this->baseRepo = $baseRepo;
        $this->eloquentRepo   = $eloquentRepo;
    }

    #region ===================== Start TRASH Methods =====================

    /**
     * Delete a review record (soft or force delete).
     *
     * @param  int     $id             The ID of the review to delete.
     * @param  object  $model          The Review model instance.
     * @param  array   $eagerLoading   Optional relationships to eager load.
     * @return object|null             The deleted review record or null if not found.
     */
    public function destroy($id, $model, $eagerLoading = null)
    {
        // Find the review item
        $item = $this->baseRepo->findWithoutTrashedOrFail($id, $model);
        
        // ownership check
        $this->ensureOwnership($item);

        // Soft or force delete the item
        $item->delete();

        // If model uses soft deletes, return the soft-deleted item
        if (isSoftDeletes($model)) {
            return $eagerLoading ? $item->load($eagerLoading) : $item;
        }

        return $item;
    }
    #endregion ===================== End TRASH Methods =====================

}
