<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\User\Order\OrderRepository;
use App\Services\User\Order\OrderService;
use App\Models\Order;
use App\Resources\OrderResource;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Http\Requests\User\OrderRequest;
use App\Http\Requests\Image\UploadImagesRequest;
use App\Traits\Controllers\UIHelpersTrait;
use App\Traits\Controllers\WebApiResponseTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;

/**
 * Class OrderController
 *
 * Handles all operations related to orders for the user interface.
 * Supports both Web (Inertia) and API responses using shared methods.
 */
class OrderController extends BaseController
{
    use UIHelpersTrait, WebApiResponseTrait;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var Order
     */
    protected $order;

    /**
     * OrderController constructor.
     *
     * @param Order         $order
     * @param OrderService  $orderService
     */
    public function __construct(Order $order, OrderService $orderService)
    {
        $this->order = $order;
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of orders.
     *
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function index()
    {
        $result = $this->orderService->getData($this->order);  // Fetch order data (may be paginated or collection)

        if ($this->isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('orders'), 'url' => route('dashboard.orders.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('Banner/Index', [
                 'rows' => $result,                 // Banner list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }

        // For API requests, respond with data wrapped in BannerResource
        return $this->respond($result, BannerResource::class);
    }

    /**
     * Display a specific order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Get specific order details
        $result = $this->orderService->show($id, $this->order);
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Create or update an order.
     *
     * @param OrderRequest $request
     * @param int|null $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(OrderRequest $request, $id = null)
    {
        // Store or update the order via service
        $result = $this->orderService->store($request, $this->order, $id);
        return $this->respond($result, OrderResource::class, 201, 'dashboard.orders.index');
    }

    /**
     * Update an existing order.
     *
     * @param OrderRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(OrderRequest $request, $id)
    {
        // Update order data
        $result = $this->orderService->update($request, $id, $this->order);
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Soft delete a specific order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Soft delete the order
        $result = $this->orderService->destroy($id, $this->order);
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Soft delete multiple orders.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        // Soft delete many orders
        $result = $this->orderService->destroyMany($request, $this->order);
        return $this->respond();
    }

    /**
     * Permanently delete a specific order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        // Force delete (permanent delete) an order
        $result = $this->orderService->forceDelete($id, $this->order);
        return $this->respond();
    }

    /**
     * Permanently delete multiple orders.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        // Force delete multiple orders
        $result = $this->orderService->forceDeleteMany($request, $this->order);
        return $this->respond();
    }

    /**
     * Display a list of soft-deleted orders.
     *
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response
     */
    public function trash()
    {
        // Get all trashed (soft-deleted) orders
        $result = $this->orderService->trash($this->order);
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Restore a specific soft-deleted order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        // Restore single order
        $result = $this->orderService->restore($id, $this->order);
        return $this->respond($result, OrderResource::class);
    }

    /**
     * Restore all soft-deleted orders.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreAll()
    {
        // Restore all trashed orders
        $result = $this->orderService->restoreAll($this->order);
        return $this->respond($result, OrderResource::class);
    }
}
