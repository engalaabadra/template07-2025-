<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseController;
use App\Repositories\Dashboard\Order\OrderRepository;
use App\Services\Dashboard\Order\OrderService;
use App\Models\Order;
use App\Resources\OrderResource;
use App\Http\Requests\Dashboard\OrderRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;

/**
 * Class OrderController
 *
 * Handles order management operations for dashboard including:
 * CRUD actions, activation/deactivation, trash management.
 */
class OrderController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var OrderService
     * The service containing business logic related to orders.
     */
    protected $orderService;

    /**
     * @var Order
     * The Order model instance.
     */
    protected $order;

    /**
     * OrderController constructor.
     * Dependency Injection for Order model, OrderService.
     *
     * @param Order $order
     * @param OrderService $orderService
     */
    public function __construct(Order $order, OrderService $orderService)
    {
        $this->order = $order;
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of orders.
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function index()
    {
        $result = $this->orderService->getData($this->order);  // Fetch order data (may be paginated or collection)

        if (isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('orders'), 'url' => route('dashboard.orders.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('Order/Index', [
                 'rows' => $result,                 // Order list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }

        // For API requests, respond with data wrapped in OrderResource
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Show details of a specific order.
     *
     * @param int $id Order ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show($id)
    {
        $result = $this->orderService->show($id, $this->order); // Retrieve order details

        if (isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('orders'), 'url' => route('dashboard.orders.index')],
                ['label' => $result->ordername ?? __('Order')],
            ]);
        }

        // Respond with order data wrapped in OrderResource
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Store a new order or update an existing one.
     *
     * @param OrderRequest $request Validated request data for storing/updating order
     * @param int|null $id Order ID to update; null to create new
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(OrderRequest $request, $id = null)
    {
        $result = $this->orderService->store($request, $this->order, $id); // Create or update order via service
        // Respond with status 201 Created and redirect route 'dashboard.orders.index'
        return $this->respond($result, OrderResource::class);
        //return $this->respond($result, OrderResource::class, $message = null, 'dashboard.orders.index');
    }

    /**
     * Update an existing order.
     *
     * @param OrderRequest $request Validated request data
     * @param int $id Order ID to update
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(OrderRequest $request, $id)
    {
        // Update the order data by calling the OrderService
        $result = $this->orderService->update($request, $id, $this->order);
        // Return the response wrapped with OrderResource,
        // which formats the order data consistently for API or web responses
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Toggle activation status (activate/deactivate) for a specific order.
     *
     * @param int $id Order ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivate($id)
    {
        // Toggle activation status (activate/deactivate) for a specific order
        $result = $this->orderService->changeActivate($id, $this->order);
        // Return the result wrapped with OrderResource
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Activate or deactivate multiple orders at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivateMany(BulkActionRequest $request)
    {
        // Activate or deactivate multiple orders at once
        $result = $this->orderService->changeActivateMany($request, $this->order);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }

    /**
     * Soft delete an order (mark as deleted without removing from DB).
     *
     * @param int $id Order ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Soft delete an order (mark as deleted without removing from DB)
        $result = $this->orderService->destroy($id, $this->order);
        // Return deleted order data wrapped in OrderResource
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Soft delete multiple orders at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        // Soft delete multiple orders at once
        $result = $this->orderService->destroyMany($request, $this->order);
        // Return response directly (likely success message)
        return $this->respond($result);
    }

    /**
     * Permanently delete an order from the database.
     *
     * @param int $id Order ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        // Permanently delete an order from the database
        $result = $this->orderService->forceDelete($id, $this->order);
        // Return the result (often a success message or empty data)
        return $this->respond($result);
    }

    /**
     * Permanently delete multiple orders at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        // Permanently delete multiple orders at once
        $result = $this->orderService->forceDeleteMany($request, $this->order);
        // Return the response
        return $this->respond($result);
    }

    /**
     * Retrieve a list of orders who were soft deleted (in trash).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function trash()
    {
        // Retrieve a list of orders who were soft deleted (in trash)
        $result = $this->orderService->trash($this->order);
        // Return data wrapped in OrderResource for consistent formatting
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Restore a soft deleted order.
     *
     * @param int $id Order ID to restore
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        // Restore a soft deleted order
        $result = $this->orderService->restore($id, $this->order);
        // Return the restored order wrapped in OrderResource
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Restore multiple soft deleted orders at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreMany(BulkActionRequest $request)
    {
        // Restore multiple soft deleted orders at once
        $result = $this->orderService->restoreMany($request, $this->order);
        // Return response (usually success message)
        return $this->respond($result);
    }

}
