<?php

namespace App\Http\Controllers\Dashboard\Geocode;

use App\Http\Controllers\BaseController;
use App\Repositories\Dashboard\Geocode\Country\CountryRepository;
use App\Services\Dashboard\Geocode\Country\CountryService;
use App\Models\Geocodes\Country;
use App\Resources\Geocode\CountryResource;
use App\Http\Requests\File\UploadFilesRequest;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Requests\Dashboard\Geocode\CountryRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;

/**
 * Class CountryController
 *
 * Handles country management operations for dashboard including:
 * CRUD actions, activation/deactivation, trash management and file uploads.
 */
class CountryController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var CountryService
     * The service containing business logic related to countrys.
     */
    protected $countryService;

    /**
     * @var Country
     * The Country model instance.
     */
    protected $country;

    /**
     * CountryController constructor.
     * Dependency Injection for Country model, CountryService.
     *
     * @param Country $country
     * @param CountryService $countryService
     */
    public function __construct(Country $country, CountryService $countryService,)
    {
        $this->country = $country;
        $this->countryService = $countryService;
    }

    /**
     * Display a listing of countrys.
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function index()
    {
        $result = $this->countryService->getData($this->country);  // Fetch country data (may be paginated or collection)

        if (isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('countrys'), 'url' => route('dashboard.countrys.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('Country/Index', [
                 'rows' => $result,                 // Country list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }

        // For API requests, respond with data wrapped in CountryResource
        return $this->respond($result, CountryResource::class);
    }

    /**
     * Show details of a specific country.
     *
     * @param int $id Country ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show($id)
    {
        $result = $this->countryService->show($id, $this->country); // Retrieve country details

        if (isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('countrys'), 'url' => route('dashboard.countrys.index')],
                ['label' => $result->countryname ?? __('Country')],
            ]);
        }

        // Respond with country data wrapped in CountryResource
        return $this->respond($result, CountryResource::class);
    }

    /**
     * Store a new country or update an existing one.
     *
     * @param CountryRequest $request Validated country creation/update request
     * @param int|null $id Country ID to update, or null to create new
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(CountryRequest $request, $id = null)
    {
        $result = $this->countryService->store($request, $this->country, $id); // Create or update country via service
        // Respond with status 201 Created and redirect route 'dashboard.countrys.index'
        return $this->respond($result, CountryResource::class);
        //return $this->respond($result, CountryResource::class, $message = null, 'dashboard.countrys.index');
    }

    /**
     * Update an existing country.
     *
     * @param CountryRequest $request Validated country update request
     * @param int $id Country ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(CountryRequest $request, $id)
    {
        // Update the country data by calling the CountryService
        $result = $this->countryService->update($request, $id, $this->country);
        // Return the response wrapped with CountryResource,
        // which formats the country data consistently for API or web responses
        return $this->respond($result, CountryResource::class);
    }

    /**
     * Toggle activation status (activate/deactivate) for a specific country.
     *
     * @param int $id Country ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivate($id)
    {
        $result = $this->countryService->changeActivate($id, $this->country);
        return $this->respond($result, CountryResource::class);
    }

    /**
     * Activate or deactivate multiple countrys at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivateMany(BulkActionRequest $request)
    {
        $result = $this->countryService->changeActivateMany($request, $this->country);
        return $this->respond($result);
    }

    /**
     * Soft delete a country (mark as deleted without removing from DB).
     *
     * @param int $id Country ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $result = $this->countryService->destroy($id, $this->country);
        return $this->respond($result, CountryResource::class);
    }

    /**
     * Soft delete multiple countrys at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        $result = $this->countryService->destroyMany($request, $this->country);
        return $this->respond($result);
    }

    /**
     * Permanently delete a country from the database.
     *
     * @param int $id Country ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        $result = $this->countryService->forceDelete($id, $this->country);
        return $this->respond($result);
    }

    /**
     * Permanently delete multiple countrys at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        $result = $this->countryService->forceDeleteMany($request, $this->country);
        return $this->respond($result);
    }

    /**
     * Retrieve a list of countrys who were soft deleted (in trash).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function trash()
    {
        $result = $this->countryService->trash($this->country);
        return $this->respond($result, CountryResource::class);
    }

    /**
     * Restore a soft deleted country.
     *
     * @param int $id Country ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $result = $this->countryService->restore($id, $this->country);
        return $this->respond($result, CountryResource::class);
    }

    /**
     * Restore multiple soft deleted countrys at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreMany(BulkActionRequest $request)
    {
        $result = $this->countryService->restoreMany($request, $this->country);
        return $this->respond($result);
    }

}
