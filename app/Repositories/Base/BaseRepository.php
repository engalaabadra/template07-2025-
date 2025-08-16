<?php
namespace App\Repositories\Base;

use App\Scopes\ActiveScope;
use App\Repositories\Base\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExcelExport;
use App\Services\ServiceResponse;
use App\Exceptions\ApiResponseException;
use App\Enums\ServiceResponseEnum;

/**
 * BaseRepository
 *
 * This is a Base Repository class implementing the BaseRepositoryInterface.
 * It provides methods for using in whole project such as : 
 */
class BaseRepository  implements BaseRepositoryInterface
{

     /**
     * Finds a model by ID or throws custom not found exception.
     */
    public function findOrFailApi($id, $model)
    {
        $item = $model::find($id);

        return $item ?? throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);

    }

    /**
     * Find a model by ID (excluding trashed) or throw not found (not soft-deleted) -> using in activate & destroy(temporary deleting)
     *
     * @param  int|string  $id
     * @param  string      $model  Model class name.
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \App\Exceptions\ApiResponseException
     */
    public function findWithoutTrashedOrFail($id, $model)
    {
        $item = $model::withoutTrashed()->find($id);

        return $item ?? throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
    }


    /**
     * Find a soft-deleted model by ID or throw not found -> using in restore , force delete
     *
     * @param  int|string  $id
     * @param  string      $model
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \App\Exceptions\ApiResponseException
     */
    public function findOnlyTrashedOrFail($id, $model)
    {
        $item = $model::onlyTrashed()->find($id);

        return $item ?? throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
    }


    ////////////////////////////////////////////
    protected function findOrFail(object $row): Model|ServiceResponseEnum
    {
        $item = $this->model->find($id);
        return $item ?? ServiceResponseEnum::NOT_FOUND;
    }

    /**
     * Try deleting a record and execute an optional callback.
     *
     * @param object $row
     * @param \Closure|null $callback
     * @return bool
     */
    public function tryDelete(object $row, ?\Closure $callback = NULL): bool
    {
        return DB::transaction(function () use ($row, $callback) {
            if ($row->delete()) {
                if ($callback) {
                    $callback($row);
                }
                $this->makeSuccessSessionMessage();
                return true;
            }
            $this->makeErrorSessionMessage(__('message.cant_delete'));
            return false;
        });
    }

    /**
     * Try force deleting a record.
     *
     * @param object $row
     * @return bool
     */
    public function tryForceDelete(object $row): bool
    {
        return DB::transaction(function () use ($row) {
            if ($row->forceDelete()) {
                $this->makeSuccessSessionMessage();
                return true;
            }
            $this->makeErrorSessionMessage(__('message.cant_delete'));
            return false;
        });
    }

    /**
     * Check if the current request expects JSON.
     *
     * @return bool
     */
    public function requestExpectJson(): bool
    {
        return request()->expectsJson();
    }

    /**
     * Try deleting or force deleting a record by ID.
     *
     * @param string $model
     * @param int $id
     * @param bool $makeMessageSession
     * @return bool
     */
    public function tryDeleteForceDelete(string $model, int $id, bool $makeMessageSession = false): bool
    {
        return DB::transaction(function () use ($model, $id, $makeMessageSession) {
            $row = $model::withTrashed()->findOrFail($id);
            if ($row->forceDelete()) {
                if ($makeMessageSession) {
                    $this->makeSuccessSessionMessage();
                }
                return true;
            }
            if ($makeMessageSession) {
                $this->makeErrorSessionMessage(__('message.cant_delete'));
            }
            return false;
        });
    }

    /**
     * Try restoring a soft-deleted record by ID.
     *
     * @param string $model
     * @param int $id
     * @param bool $makeMessageSession
     * @return void
     */
    public function tryRestore(string $model, int $id, bool $makeMessageSession = false): void
    {
        DB::transaction(function () use ($model, $id, $makeMessageSession) {
            $row = $model::withTrashed()->findOrFail($id);
            $row->restore();
            $row->update(['deleted_by_id' => NULL]);
            if ($makeMessageSession) {
                $this->makeSuccessSessionMessage();
            }
        });
    }

    /**
     * Create a success session message.
     *
     * @param string|null $message
     * @return void
     */
    public function makeSuccessSessionMessage(?string $message = NULL): void
    {
        $this->createToaster('success', '', $message ?? __('service_responses.success'));
    }

    /**
     * Refresh the DOM by setting a session key.
     *
     * @return void
     */
    public function refreshDom(): void
    {
        Session::flash('refresh_dom_key', time());
    }

    public function flashShareData($data = []): void
    {
        Session::flash('el_flash_temp_data', $data);
    }

    /**
     * Create a toaster flash message.
     *
     * @param string $type
     * @param string $title
     * @param string $message
     * @return void
     */
    private function createToaster(string $type, string $title, string $message): void
    {
        if (!$this->requestExpectJson()) {
            Session::flash('toastr', [['type' => $type, 'title' => $title, 'message' => $message]]);
        }
    }

    /**
     * Create an error session message.
     *
     * @param string|null $message
     * @return void
     */
    public function makeErrorSessionMessage(?string $message = NULL): void
    {
        $this->createToaster('error', '', $message ?? __('message.error_response_message'));
    }

    /**
     * Export model data to an Excel file and either download it (web) or return a download URL (API).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Fully qualified model class name.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|string
     *
     * - For **web requests**, triggers file download of the Excel export.
     * - For **API requests**, returns a public URL to the exported file.
     *
     * The exported file:
     * - Uses the model’s query to fetch all records.
     * - Uses the model’s `$columnsToExport` to determine which columns to include.
     * - Is named using the translated module name and current date.
     */

    public function exportToExcel($model, $query)
    {
        $data = $query->get();
       // $fileName = ModuleNameEnum::getTrans(ModuleNameEnum::modelNameUpperCase($model));
        $fileName = class_basename($model);
        $fileNameWithDate = $fileName . ' ' . Carbon::now()->toDateString() . '.xlsx';
        $filePath = 'exports/' . $fileNameWithDate;

        // store file in folder in storage
        if(isset($model::$columnsToExport)){
            Excel::store(new ExcelExport($data, $model::$columnsToExport), $filePath, 'public');
            return request()->expectsJson()
                ? url('storage/' . $filePath) // API: return download URL
                : Excel::download(new ExcelExport($data, $map), $fileNameWithDate); // Web: trigger file download
        }else{
            throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);
        }


    }

    /**
     * Share filters with Inertia.
     *
     * @param array|Collection|null $filters
     * @return void
     */
    public function useFilter(null|array|Collection $filters = NULL): void
    {
        if ($filters) {
            Inertia::share(['filters' => $filters]);
        }
    }

    public function addElFileCard($collection, $label, $archives = null, $el_file_card_type = 'archive_card'): array
    {
        return [
            'el_file_card_type' => $el_file_card_type,
            'collection' => $collection,
            'label' => $label,
            'archives' => $archives,
        ];
    }

    /**
     * Share transparency setting with Inertia.
     *
     * @param bool $transparent
     * @return void
     */
    public function useTransparent(bool $transparent = true): void
    {
        Inertia::share(['isTransparent' => $transparent]);
    }

    /**
     * Generate a statistic card.
     *
     * @param string|null $title
     * @param string|null $value
     * @param string|null $icon
     * @return array
     */
    public function makeStatisticCard(?string $title, ?string $value, string $icon = 'pi pi-chart-line', bool $is_price = false): array
    {
        return [
            'title' => $title,
            'value' => $value ?? 0,
            'icon' => $icon,
            'is_price' => $is_price,
        ];
    }

    /**
     * Get all frontend-ready filters from a given model.
     * render all elements for filters in front like(min , max & dropdown options from Enum:[ ['id' => 1, 'name' => 'Active'], ['id' => 0, 'name' => 'Not Active'] ])
     * This method fetches the filters defined in the model's custom query builder,
     * converts each filter into an array using its `toArray()` method, and returns
     * the result as a plain array suitable for frontend consumption.
     *
     * Each filter object may internally call `getData()` to retrieve options
     * this getData() : call getOptionsData() that is exist in any enum class to show like:  dropdown options from Enum:[ ['id' => 1, 'name' => 'Active'], ['id' => 0, 'name' => 'Not Active'] ]
     * (for example from an Enum) and include properties like min, max, label, etc.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     *         The Eloquent model class or instance to get filters from.
     * 
     * @return array
     *         An array of filters formatted for frontend display.
     *
     * @example
     * $filters = $this->getModelFilters(User::class);
     * // Returns something like:
     * // [
     * //     ['name' => 'Status', 'type' => 'dropdown', 'options' => [[ ['id' => 1, 'name' => 'Active'], ['id' => 0, 'name' => 'Not Active']]],
     * //     ['name' => 'Created At', 'type' => 'range', 'min' => ..., 'max' => ...],
     * // ]
     */
    protected function getModelFilters($model): array
    {
        //render all elements for filters in front like(min , max & dropdown options from Enum:[ ['id' => 1, 'name' => 'Active'], ['id' => 0, 'name' => 'Not Active'] ])
        $filters = collect($model::query()->filters()) // 1. Get the filters method from the model's custom BaseBuilder
           // 2. each filter extends from Filer class , this class contain on toArray() : contain all elements for filter for front (min , max , label)
           //  and calls getData() internally that it exist in every filter class like IsActiveFilter 
           //  this getData() : call getOptionsData() that is exist in any enum class to show like:  dropdown options from Enum:[ ['id' => 1, 'name' => 'Active'], ['id' => 0, 'name' => 'Not Active'] ]
            ->map(fn($filter) => $filter->toArray())   
            ->toArray();                               // 3. Convert the Collection back to a plain array ready for frontend

        return $filters;
    }


    //  /**
    //  * Retrieve items by IDs or all items if $isAll is true.
    //  *
    //  * @param  \Illuminate\Database\Eloquent\Builder  $query
    //  * @param  bool                                   $isAll
    //  * @param  array|string                            $inputIds
    //  * @return \Illuminate\Support\Collection
    //  */
    // public function fetchItemsByIdsOrAll($query, bool $isAll, $inputIds)
    // {
    //     if ($isAll) {
    //         // Fetch all items (no filtering by ID)
    //         $items = $query->get();

    //         $ids = $items->pluck('id')->toArray(); // Set $ids for comparison later
    //     } else {
    //         $ids = $inputIds; // Already validated array of integers
    //         // Fetch only items with those IDs
    //         $items = $query->whereIn('id', $ids)->get();
    //     }
    //     // Return 404 response if no items were found
    //     if ($items->isEmpty() && !$isAll) {
    //         throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);           // throw 404 if SoftDeletes not used
    //     }
    //     return $items;
    // }


}
