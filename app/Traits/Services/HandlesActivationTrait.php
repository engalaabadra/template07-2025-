<?php
namespace App\Traits\Services;

trait HandlesActivationTrait{
     
    /**
     * Get the new active status based on the given action.
     *
     * @param  string  $action The action to perform: 'activate', 'deactivate', or 'toggle'.
     * @param  object  $item   The item instance which contains the current is_active status.
     * 
     * @return \App\Enums\IsActiveEnum The new status enum value.
     */
    protected function getNewStatusByAction(string $action, $item): IsActiveEnum
    {
        // 🔁 Determine the new status based on the requested action
        switch ($action) {
            case 'activate':
                return IsActiveEnum::ACTIVE;
                break;
            case 'deactivate':
                return IsActiveEnum::NOT_ACTIVE;
                break;
            case 'toggle':
            default:
                return $item->is_active === IsActiveEnum::ACTIVE
                    ? IsActiveEnum::NOT_ACTIVE
                    : IsActiveEnum::ACTIVE;
            }
    }

    /**
     * Safely activate a record by checking for unique conflicts.
     *
     * @param object $item The item to activate.
     * @param string $policy The policy for handling conflicts: modify, replace, prevent.
     * @return \Illuminate\Http\JsonResponse|null
     */
    protected function safeActivateById($item, string $policy = 'modify')
    {
        // Build a query to check if another active item has the same unique fields
        $query = Banner::query()->where('is_active', IsActiveEnum::ACTIVE->value);
        $uniqueFields = ['title'];

        // Check for conflicts by comparing unique fields
        foreach ($uniqueFields as $field) {
            $query->where($field, $item->$field);
        }

        $conflictExists = $query->exists();

        if ($conflictExists) {
            switch ($policy) {
                case 'modify':
                    // Modify unique fields by appending timestamp
                    foreach ($uniqueFields as $field) {
                        $item->$field = $item->$field . '_activated_' . now()->format('Ymd');
                    }

                    // Save modified item
                    $item->save();

                    // Toggle the activation status
                    $isActiveEnum = $item->is_active === IsActiveEnum::ACTIVE
                        ? IsActiveEnum::NOT_ACTIVE
                        : IsActiveEnum::ACTIVE;

                    // Update the status
                    $item->update([
                        'is_active' => $isActiveEnum->value,
                    ]);
                    break;

                case 'replace':
                    // Delete existing conflicting active item
                    $query->delete();

                    // Toggle the activation status
                    $isActiveEnum = $item->is_active === IsActiveEnum::ACTIVE
                        ? IsActiveEnum::NOT_ACTIVE
                        : IsActiveEnum::ACTIVE;

                    // Update the status
                    $item->update([
                        'is_active' => $isActiveEnum->value,
                    ]);
                    break;

                case 'prevent':
                default:
                    // Prevent activation and return error
                    throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('يوجد تعارض في البيانات، لا يمكن التفعيل.' . $item->id));
            }
        }
    }

    /**
     * Safely restore a trashed item by checking for conflicts.
     *
     * @param object $item The trashed item to restore.
     * @param string $policy The policy for handling conflicts: modify, replace, prevent.
     * @return \Illuminate\Http\JsonResponse|null
     */
    protected function safeRestoreById($item, string $policy = 'prevent')
    {
        // Build a query to check if another non-deleted item has the same unique fields
        $query = Banner::query()->whereNull('deleted_at');
        $uniqueFields = ['title'];

        foreach ($uniqueFields as $field) {
            $query->where($field, $item->$field);
        }

        // Check for conflicts
        $conflictExists = $query->get();

        if ($conflictExists) {
            switch ($policy) {
                case 'modify':
                    // Modify unique fields by appending timestamp
                    foreach ($uniqueFields as $field) {
                        $item->$field = $item->$field . '_restored_' . now()->format('Ymd');
                    }

                    // Save the modified item
                    $item->save();

                    // Restore the item from trash
                    $item->restore();
                    break;

                case 'replace':
                    // Delete conflicting item
                    $query->delete();

                    // Restore the item
                    $item->restore();
                    break;

                case 'prevent':
                default:
                    // Prevent restore and return error
                    throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, 'يوجد تعارض في البيانات، لا يمكن الاسترجاع.');
            }
        }
    }
}