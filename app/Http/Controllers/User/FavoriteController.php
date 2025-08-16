<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Models\Favorite;
use App\Services\User\Favorite\FavoriteService;
use App\Http\Requests\User\FavoriteRequest;
use App\Resources\FavoriteResource;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Requests\Image\UploadImagesRequest;
use App\Traits\Controllers\UIHelpersTrait;
use App\Traits\Controllers\WebApiResponseTrait;
use Inertia\Inertia;

/**
 * Class FavoriteController
 *
 * This controller handles all operations related to favorites for the user,
 * including listing and storing favorites. It supports both API and Web responses.
 */
class FavoriteController extends BaseController
{
    use UIHelpersTrait, WebApiResponseTrait;

    /**
     * FavoriteService instance to handle business logic.
     *
     * @var FavoriteService
     */
    protected $favoriteService;

    /**
     * Favorite model instance.
     *
     * @var Favorite
     */
    protected $favorite;

    /**
     * FavoriteController constructor.
     *
     * @param Favorite         $favorite
     * @param FavoriteService  $favoriteService
     */
    public function __construct(Favorite $favorite, FavoriteService $favoriteService)
    {
        $this->favorite         = $favorite;
        $this->favoriteService  = $favoriteService;
    }

    /**
     * Display a listing of favorites.
     *
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function index()
    {
        $result = $this->favoriteService->getData($this->favorite);  // Fetch favorite data (may be paginated or collection)

        if ($this->isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('favorites'), 'url' => route('dashboard.favorites.index')],
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
     * Store a newly created favorite or update an existing one if $id is provided.
     *
     * @param FavoriteRequest $request
     * @param int|null $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(FavoriteRequest $request, $id = null)
    {
        // Store or update the favorite using the service
        $result = $this->favoriteService->store($request, $this->favorite, $id);

        // Return the response as a resource with status 201 and redirect if web
        return $this->respond($result, FavoriteResource::class, 201, 'dashboard.favorites.index');
    }
}
