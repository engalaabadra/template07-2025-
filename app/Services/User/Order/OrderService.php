<?php

namespace App\Services\User\Order;

use App\Services\Eloquent\EloquentService;
use App\Repositories\Eloquent\EloquentRepository;
use App\Services\ServiceResponse;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;
use App\Traits\Services\ChecksOwnershipTrait;

/**
 * Class OrderService
 *
 * This service class handles all order-related business logic.
 * It implements the OrderServiceInterface and provides core methods such as store and update.
 * 
 * @package App\Services\User\Order
 * 
 */
class OrderService extends EloquentService implements OrderServiceInterface
{
    use ChecksOwnershipTrait;

    /** @var EloquentRepository */
    protected $eloquentRepo;

    
    /**
     * Constructor
     *
     * @param EloquentRepository    $eloquentRepo
     */
    public function __construct(EloquentRepository $eloquentRepo)
    {
        $this->eloquentRepo    = $eloquentRepo;
    }

    #region ===================== Start CRUD Methods =====================
    /**
     * Store a new order record.
     *
     * @param object $request The validated request object.
     * @param object $model The order model instance.
     * @return object JSON service response with the newly created order.
     */
    public function store($request, $model)
    {
        // Get validated data from request
        $data = $request->validated();

        // Manually assign user ID (should be from auth, but hardcoded here)
        $data['user_id'] = userApi()?->id;

        // Optionally: calculate and assign 'total' based on order items

        // Create a new order record in the database
        $newItem = $model->create($data);

        // Automatically refresh the model if 'is_active' field is missing
        refreshIfMissing($data, $newItem);

        // Load relationships if eager loading is defined in model
        $data = $model->getEagerLoading()
            ? $newItem->load($model->getEagerLoading())
            : $newItem;

        return $data;
        
    }

    /**
     * Update an existing order.
     *
     * @param object $request The validated request object.
     * @param int $id The ID of the order to update.
     * @param object $model The order model instance.
     * @return object Updated order data or not found response.
     */
    public function update($request, $id, $model)
    {
        // Get validated data from request
        $data = $request->validated();

        // Find the order by its ID
        $item = $this->baseRepo->findOrFailApi($id, $model);

        // check if this chat for her
        $this->ensureOwnership($item);

        // Update the order record with new data
        $item->update($data);

        // Load relationships if eager loading is defined
        return $model->getEagerLoading()
            ? $item->load($model->getEagerLoading())
            : $item;
    }
    #endregion ===================== End CRUD Methods =====================

}
