<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Repositories\User\Review\ReviewRepository;
use App\Services\User\Review\ReviewService;
use App\Models\Review;
use App\Resources\ReviewResource;
use App\Http\Requests\User\ReviewRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;

/**
 * Class ReviewController
 *
 * Handles review management operations for dashboard including:
 * CRUD actions, activation/deactivation, trash management.
 */
class ReviewController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var ReviewService
     * The service containing business logic related to reviews.
     */
    protected $reviewService;

    /**
     * @var Review
     * The Review model instance.
     */
    protected $review;

    /**
     * ReviewController constructor.
     * Dependency Injection for Review model, ReviewService, and GeneralService.
     *
     * @param Review $review
     * @param ReviewService $reviewService
     * @param GeneralService $generalService
     */
    public function __construct(Review $review, ReviewService $reviewService, GeneralService $generalService)
    {
        $this->review = $review;
        $this->reviewService = $reviewService;
        $this->generalService = $generalService;
    }

    /**
     * Display a listing of reviews.
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function index()
    {
        $result = $this->reviewService->getData($this->review);  // Fetch review data (may be paginated or collection)

        if ($this->isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('reviews'), 'url' => route('dashboard.reviews.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('Review/Index', [
                 'rows' => $result,                 // Review list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }

        // For API requests, respond with data wrapped in ReviewResource
        return $this->respond($result, ReviewResource::class);
    }

    /**
     * Show details of a specific review.
     *
     * @param int $id Review ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show($id)
    {
        $result = $this->reviewService->show($id, $this->review); // Retrieve review details

        if ($this->isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('reviews'), 'url' => route('dashboard.reviews.index')],
                ['label' => $result->reviewname ?? __('Review')],
            ]);
        }

        // Respond with review data wrapped in ReviewResource
        return $this->respond($result, ReviewResource::class);
    }

    /**
     * Store a new review or update an existing one.
     *
     * @param ReviewRequest $request Validated request data for storing/updating review
     * @param int|null $id Review ID to update; null to create new
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(ReviewRequest $request, $id = null)
    {
        $result = $this->reviewService->store($request, $this->review, $id); // Create or update review via service
        // Respond with status 201 Created and redirect route 'dashboard.reviews.index'
        return $this->respond($result, ReviewResource::class);
        //return $this->respond($result, ReviewResource::class, $message = null, 'dashboard.reviews.index');
    }

    /**
     * Update an existing review.
     *
     * @param ReviewRequest $request Validated request data
     * @param int $id Review ID to update
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(ReviewRequest $request, $id)
    {
        // Update the review data by calling the ReviewService
        $result = $this->reviewService->update($request, $id, $this->review);
        // Return the response wrapped with ReviewResource,
        // which formats the review data consistently for API or web responses
        return $this->respond($result, ReviewResource::class);
    }

    /**
     * Toggle activation status (activate/deactivate) for a specific review.
     *
     * @param int $id Review ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivate($id)
    {
        // Toggle activation status (activate/deactivate) for a specific review
        $result = $this->reviewService->changeActivate($id, $this->review);
        // Return the result wrapped with ReviewResource
        return $this->respond($result, ReviewResource::class);
    }

    /**
     * Activate or deactivate multiple reviews at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivateMany(BulkActionRequest $request)
    {
        // Activate or deactivate multiple reviews at once
        $result = $this->reviewService->changeActivateMany($request, $this->review);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }

    /**
     * Soft delete a review (mark as deleted without removing from DB).
     *
     * @param int $id Review ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Soft delete a review (mark as deleted without removing from DB)
        $result = $this->reviewService->destroy($id, $this->review);
        // Return deleted review data wrapped in ReviewResource
        return $this->respond($result, ReviewResource::class);
    }

    /**
     * Soft delete multiple reviews at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        // Soft delete multiple reviews at once
        $result = $this->reviewService->destroyMany($request, $this->review);
        // Return response directly (likely success message)
        return $this->respond($result);
    }

    /**
     * Permanently delete a review from the database.
     *
     * @param int $id Review ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        // Permanently delete a review from the database
        $result = $this->reviewService->forceDelete($id, $this->review);
        // Return the result (often a success message or empty data)
        return $this->respond($result);
    }

    /**
     * Permanently delete multiple reviews at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        // Permanently delete multiple reviews at once
        $result = $this->reviewService->forceDeleteMany($request, $this->review);
        // Return the response
        return $this->respond($result);
    }

    /**
     * Retrieve a list of reviews who were soft deleted (in trash).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function trash()
    {
        // Retrieve a list of reviews who were soft deleted (in trash)
        $result = $this->reviewService->trash($this->review);
        // Return data wrapped in ReviewResource for consistent formatting
        return $this->respond($result, ReviewResource::class);
    }

    /**
     * Restore a soft deleted review.
     *
     * @param int $id Review ID to restore
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        // Restore a soft deleted review
        $result = $this->reviewService->restore($id, $this->review);
        // Return the restored review wrapped in ReviewResource
        return $this->respond($result, ReviewResource::class);
    }

    /**
     * Restore multiple soft deleted reviews at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreMany(BulkActionRequest $request)
    {
        // Restore multiple soft deleted reviews at once
        $result = $this->reviewService->restoreMany($request, $this->review);
        // Return response (usually success message)
        return $this->respond($result);
    }

}
