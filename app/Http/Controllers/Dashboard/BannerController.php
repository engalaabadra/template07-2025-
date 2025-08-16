<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseController;
use App\Repositories\Dashboard\Banner\BannerRepository;
use App\Services\Dashboard\Banner\BannerService;
use App\Models\Banner;
use App\Resources\BannerResource;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Requests\Dashboard\BannerRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;

/**
 * Class BannerController
 *
 * Handles banner management operations for Dashboard including:
 * CRUD actions, activation/deactivation, trash management and file uploads.
 */
class BannerController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var BannerService
     * The service containing business logic related to banners.
     */
    protected $bannerService;

    /**
     * @var Banner
     * The Banner model instance.
     */
    protected $banner;

    /**
     * BannerController constructor.
     * Dependency Injection for Banner model, BannerService, and .
     *
     * @param Banner $banner
     * @param BannerService $bannerService
     */
    public function __construct(Banner $banner, BannerService $bannerService)
    {
        $this->banner = $banner;
        $this->bannerService = $bannerService;
    }

    /**
     * Display a listing of banners.
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function index()
    {
        $result = $this->bannerService->getData($this->banner);  // Fetch banner data (may be paginated or collection)

        if (isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('banners'), 'url' => route('dashboard.banners.index')],
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
     * Show details of a specific banner.
     *
     * @param int $id Banner ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show($id)
    {
        $result = $this->bannerService->show($id, $this->banner); // Retrieve banner details

        if (isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('banners'), 'url' => route('dashboard.banners.index')],
                ['label' => $result->bannername ?? __('Banner')],
            ]);
        }

        // Respond with banner data wrapped in BannerResource
        return $this->respond($result, BannerResource::class);
    }

    /**
     * Store a new banner or update an existing one.
     *
     * @param BannerRequest $request Validated banner creation/update request
     * @param int|null $id Banner ID to update, or null to create new
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(BannerRequest $request, $id = null)
    {
        $result = $this->bannerService->store($request, $this->banner, $id); // Create or update banner via service
        // Respond with status 201 Created and redirect route 'Dashboard.banners.index'
        return $this->respond($result, BannerResource::class);
        //return $this->respond($result, BannerResource::class, $message = null, 'Dashboard.banners.index');
    }

    /**
     * Update an existing banner.
     *
     * @param BannerRequest $request Validated banner update request
     * @param int $id Banner ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(BannerRequest $request, $id)
    {
        // Update the banner data by calling the BannerService
        $result = $this->bannerService->update($request, $id, $this->banner);
        // Return the response wrapped with BannerResource,
        // which formats the banner data consistently for API or web responses
        return $this->respond($result, BannerResource::class);
    }

    /**
     * Toggle activation status (activate/deactivate) for a specific banner.
     *
     * @param int $id Banner ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivate($id)
    {
        $result = $this->bannerService->changeActivate($id, $this->banner);
        return $this->respond($result, BannerResource::class);
    }

    /**
     * Activate or deactivate multiple banners at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivateMany(BulkActionRequest $request)
    {
        $result = $this->bannerService->changeActivateMany($request, $this->banner);
        return $this->respond($result);
    }

    /**
     * Soft delete a banner (mark as deleted without removing from DB).
     *
     * @param int $id Banner ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $result = $this->bannerService->destroy($id, $this->banner);
        return $this->respond($result, BannerResource::class);
    }

    /**
     * Soft delete multiple banners at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        $result = $this->bannerService->destroyMany($request, $this->banner);
        return $this->respond($result);
    }

    /**
     * Permanently delete a banner from the database.
     *
     * @param int $id Banner ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        $result = $this->bannerService->forceDelete($id, $this->banner);
        return $this->respond($result);
    }

    /**
     * Permanently delete multiple banners at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        $result = $this->bannerService->forceDeleteMany($request, $this->banner);
        return $this->respond($result);
    }

    /**
     * Retrieve a list of banners who were soft deleted (in trash).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function trash()
    {
        $result = $this->bannerService->trash($this->banner);
        return $this->respond($result, BannerResource::class);
    }

    /**
     * Restore a soft deleted banner.
     *
     * @param int $id Banner ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $result = $this->bannerService->restore($id, $this->banner);
        return $this->respond($result, BannerResource::class);
    }

    /**
     * Restore multiple soft deleted banners at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreMany(BulkActionRequest $request)
    {
        $result = $this->bannerService->restoreMany($request, $this->banner);
        return $this->respond($result);
    }

    //==================== Files ====================//

    /**
     * Upload a single file (image or other) related to a banner.
     *
     * @param UploadImageRequest $request Validated image upload request
     * @param int $id Banner ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function uploadFile(UploadImageRequest $request, $id)
    {
        $result = $this->bannerService->uploadFile($request, $id, $this->banner);
        return $this->respond($result, BannerResource::class);
    }

    /**
     * Delete a single file associated with a banner.
     *
     * @param int $id File ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteFile($id)
    {
        $result = $this->bannerService->deleteFile($id, $this->banner);
        return $this->respond($result);
    }

    
}
