<?php
namespace App\Repositories\User\Chat;

use App\Repositories\Eloquent\EloquentRepository;
use App\Events\MessageCreated;
use App\GeneralClasses\MediaClass;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use App\Scopes\LanguageScope;

/**
 * ChatRepository
 *
 * This is a base Repository class implementing the ChatRepositoryInterface.
 * It provides methods such as : getData
 */
class ChatRepository extends EloquentRepository implements ChatRepositoryInterface
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
            ->where(['user_id' => adminApi()?->id, 'client_id' => clientId()])
            ->orWhere(['client_id' => adminApi()?->id, 'user_id' => clientId()])
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
    
}

