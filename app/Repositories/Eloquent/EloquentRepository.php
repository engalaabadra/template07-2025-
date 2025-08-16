<?php

namespace App\Repositories\Eloquent;

use App\Scopes\ActiveScope;
use App\Repositories\Eloquent\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use App\Repositories\Base\BaseRepository;
use App\Services\ServiceResponse;
use App\Traits\Controllers\UIHelpersTrait;

/**
 * EloquentRepository
 *
 * This is a Eloquent Repository class implementing the EloquentRepositoryInterface.
 * It provides methods for using in whole project such as : getData, show, trash, report
 */
class EloquentRepository extends BaseRepository implements EloquentRepositoryInterface
{
    use UIHelpersTrait;

    /**
     * Get Data (all, pagination) -> Taking into consideration language.
     *
     * @param object $model The model to query.
     * @return array Paginated or full collection of results.
     */
    public function getData($model)
    {
        // === Front-End UI Helpers ===
        $this->allowSearch();                              // Render search input
        $this->useBreadcrumb();                           // Render breadcrumb

        // === Filters === 
        //Retrieve all filters from a model as an array for frontend use. to use in api
        $filters = $this->getModelFilters($model);//from base repo
       
        // === Base Query ===
        $query = $model::query()
            ->when($model->getEagerLoading(), fn($q) => $q->with($model->getEagerLoading()))
            ->filter()
            ->search($model::$columnsSearch)
            ->latest('id');

        /** Handle REPORT request */
        if (request()->boolean('report')) {
            // Get filters excluding report params
            $filters = request()->except(['report', 'page', 'export', 'only_trashed', 'report_types']);

            // Get report types from request, or default to ['default']
            $types = request()->input('report_types');
            $types = is_array($types) && count($types) > 0 ? $types : ['default'];

            $result = [];
            foreach ($types as $type) {
                $result[$type] = $model::generateReport($model, $filters, $type);
            }
            $data = [
                'reports' => $result
            ];
        }

        /** Handle TRASH request */
        if (request()->boolean('only_trashed')) {
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($model))) {
                $query = $model::onlyTrashed()
                    ->when($model->getEagerLoading(), fn($q) => $q->with($model->getEagerLoading()))
                    ->filter()
                    ->search($model::$columnsSearch)
                    ->latest('id');
            }
        }

        // === Export to Excel ===
        if (request()->boolean('export')) {
            return $this->exportToExcel($model, $query);
        }

        // === Fetch Data ===
        // Share filters with Inertia for web
        $this->useFilter($filters);

        // Fetch data (paginated or all)
        $data = page() ? $query->paginate() : $query->get();

        // Return
        return isWebRequest() ? $data : ['data' => $data, 'filters' => $filters];

    }

    /**
     * Show a specific record.
     *
     * @param int $id The ID of the record to show.
     * @param object $model The model to query.
     * @return object The requested record.
     */
    public function show($id, $model)
    {
        $item = $this->findOrFailApi($id, $model);

        $data = $model->getEagerLoading()                 // Eager load relations if defined
            ? $item->load($model->getEagerLoading())
            : $item;

        return $data;
    }

    /**
     * Generate a grouped report with optional filters, for both API and Web usage.
     *
     * @param string $model Fully qualified class name of the model.
     * @param array<string,mixed> $filters Optional filters (e.g: ['status' => ['is_active', 'pending']])
     *
     * @return \Illuminate\Support\Collection|\Illuminate\Http\JsonResponse
     */
    public function report($model, $filters = [])
    {
        $query = $model::query();                         // Start base query

        // Apply filters
        foreach ($filters as $column => $value) {
            $query->when(
                is_array($value),
                fn($q) => $q->whereIn($column, $value),
                fn($q) => $q->where($column, $value)
            );
        }

        // Group and select data
        $data = $query
            ->selectRaw($model::$raw)                     // Select raw fields like COUNT(*) as total
            ->groupBy($model::$groupBy)                   // Group by defined fields
            ->get();

        return $data;
    }

    /**
     * Get trashed records with optional eager loading (pagination).
     *
     * @param object $model The model to query.
     * @return array Paginated or full collection of trashed records.
     */
    public function trash($model)
    {
        if (!in_array(SoftDeletes::class, class_uses($model))) {
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);           // throw 404 if SoftDeletes not used
        }

        $query = $model;           // Remove global scopes
        $query = $query->onlyTrashed()                    // Get only soft-deleted records
            ->when($model->getEagerLoading(), fn($q) => $q->with($model->getEagerLoading()));

        $data = page() ? $query->paginate() : $query->get();  // Paginate or get all
        
        return $data;

    }

}


