<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Models\Banner;
use App\Services\User\Banner\BannerService;
use App\Resources\BannerResource;
use Inertia\Inertia;

/**
 * Class BannerController
 *
 * This controller handles retrieving and listing banner records
 * for both API and web (Inertia) responses.
 */
class BannerController extends BaseController
{

    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var BannerService
     * Service that contains business logic for banners.
     */
    protected $bannerService;

    /**
     * @var Banner
     * Banner model instance.
     */
    protected $banner;

    /**
     * BannerController constructor.
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
     *
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function index()
    {
        $result = $this->bannerService->getData($this->banner);  // Fetch banner data (may be paginated or collection)

        if ($this->isWebRequest()) { // Check if request is from web (not API)
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
}
