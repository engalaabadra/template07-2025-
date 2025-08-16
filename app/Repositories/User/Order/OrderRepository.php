<?php
namespace App\Repositories\User\Order;

use App\Repositories\Eloquent\EloquentRepository;

/**
 * OrderRepository
 *
 * This is a base Repository class implementing the OrderRepositoryInterface.
 * It provides methods such as : getData, search, show, trash
 */
class OrderRepository extends EloquentRepository implements OrderRepositoryInterface
{

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
            ->where('user_id', userApi()->id)
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
     * Search for records with optional eager loading and multiple column support.
     * 
     * @param object $model The model to query.
     * @return object Paginated or full collection of results.
     */
    public function search($model)
    {
        // Get the search term from the query string
        $word = query();
        $columnsSearch = $model::$columnsSearch;
        // Initialize the query with optional eager loading
        $query = $model->when($model::$eagerLoading, fn($q) => $q->with($model::$eagerLoading));
        // Add the search conditions for multiple columns
        $query->where(function ($q) use ($columnsSearch, $word) {
            foreach ($columnsSearch as $col) {
                $q->orWhere($col, 'like', '%' . $word . '%');
            }
        });
        // Return paginated or full results
        //return page() ? $query->where('user_id', userApi()->id)->paginate(total()) : $query->where('user_id', userApi()->id)->get();
        return page() ? $query->where('user_id', 3)->paginate(total()) : $query->where('user_id', 3)->get();
    }

    /**
     * Show a specific record.
     * @param int $id The ID of the record to show.
     * @param object $model The model to query.
     * @return object The requested record.
     */
    public function show($id, $model)
    {
        $query = $model->where('user_id', userApi()->id)->withoutGlobalScopes();
        // Find the record or return 404 if not found
        $item = $query->find($id);
        if(!$item) return 404;
        return $model::$eagerLoading ? $item->load($model::$eagerLoading) : $item;
    }
    
    public function report($model)
    {
        return $model::selectRaw($model::$raw)
                    ->where('user_id', userApi()->id)
                   ->groupBy($model::$groupBy)
                   ->get();
    }
    //for trash
    /**
     * Get trashed records with optional eager loading (pagination).
     * @param object $model The model to query.
     * @return array Paginated or full collection of trashed records.
     */
    public function trash($model)
    {
        // Check if the model uses SoftDeletes
        if (!in_array(SoftDeletes::class, class_uses($model))) return 404;
        $query = $model->where('user_id', userApi()->id)->withoutGlobalScopes();
        // Apply eager loading & get data
        $query = $query->onlyTrashed()->when($model::$eagerLoading, fn($q) => $q->with($model::$eagerLoading));
        if (empty($items)) return 404;
        // Return paginated or full results
        return page() ? $query->paginate(total()) : $query->get();
    }

}
