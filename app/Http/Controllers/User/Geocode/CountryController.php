<?php

namespace App\Http\Controllers\User\Geocode;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Repositories\User\Geocode\Country\CountryRepository;
use App\Services\User\Geocode\Country\CountryService;
use App\Models\Geocodes\Country;
use App\Resources\Geocode\CountryResource;
use App\Http\Requests\User\Geocode\Country\StoreCountryRequest;
use App\Http\Requests\User\Geocode\Country\UpdateCountryRequest;

/**
 * CountryController handles country-related CRUD operations.
 * It supports both API and Web requests using Inertia.
 */
class CountryController extends BaseController
{
    /**
     * @var CountryService Handles business logic for countries
     */
    protected $countryService;

    /**
     * @var CountryRepository Handles country data access layer
     */
    protected $countryRepo;

    /**
     * @var Country The Country model instance
     */
    protected $country;

    /**
     * CountryController constructor.
     *
     * @param Country $country
     * @param CountryRepository $countryRepo
     * @param CountryService $countryService
     */
    public function __construct(Country $country, CountryRepository $countryRepo, CountryService $countryService)
    {
        $this->country = $country;
        $this->countryRepo = $countryRepo;
        $this->countryService = $countryService;
    }

    /**
     * Display a list of countries.
     * Supports both API (JSON) and Web (Inertia) responses.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all countries (optionally paginated) using service logic
        $result = $this->countryService->getData($this->country);

        // If the request is for the web (Inertia)
        if ($this->isWebRequest()) {
            // Set breadcrumb for frontend navigation
            $this->breadcrumb([
                ['label' => __('countries'), 'url' => route('countries.index')],
            ]);

            // Render Inertia view for listing countries
            return $this->renderWebIndexPage('Country/Index', [
                'rows' => $result,
                'form_data' => $this->getCreateUpdateData(), // Data needed for form inputs
            ]);
        }

        // Otherwise, return API response with optional transformation
        return $this->respond($result, CountryResource::class);
    }
}
