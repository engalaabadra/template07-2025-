<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;

/**
 * Trait ActivateModelTrait
 *
 * Provides a reusable method to safely activate soft-deleted records (e.g. via `is_active` flag),
 * while handling potential conflicts in unique fields based on a selected policy.
 */
trait ActivateModelTrait
{
    /**
     * Safely activate a soft-deleted record by handling unique field conflicts.
     *
     * This method checks whether any active record already exists with the same values
     * in the provided unique fields. Depending on the selected policy, it will:
     * 
     * - `prevent`: Cancel activation if a conflict exists.
     * - `modify`: Modify the conflicting field(s) (e.g., append a timestamp) before activation.
     * - `replace`: Delete conflicting record(s) before activating the requested one.
     *
     * @param string $model            Fully qualified model class (e.g., App\Models\User).
     * @param mixed $item              The instance to be activated.
     * @param string $policy           Conflict resolution policy: 'prevent', 'modify', or 'replace'. Default is 'prevent'.
     * 
     * @return array{
     *     status: bool,
     *     message: string
     * }                                  The result of the activation attempt.
     */
    public function safeActivateById(string $model, $item, string $policy = 'prevent'): array
    {
        // Define a query to find any active records with the same unique field values
        $query = $model::query()
                       ->where('is_active', IsActiveEnum::ACTIVE->value);

        foreach ($uniqueFields as $field) {
            $query->where($field, $item->$field);
        }

        // Check if a conflicting record already exists
        $conflictExists = $query->exists();

        // If there is a conflict, handle based on the selected policy
        if ($conflictExists) {
            switch ($policy) {

                case 'modify':
                    // Append timestamp to conflicting fields to make them unique
                    foreach ($uniqueFields as $field) {
                        $item->$field = $item->$field . '_restored_' . now()->format('YmdHis');
                    }

                    // Save the modified record before activation
                    $item->save();

                    // Activate the record
                    $item->update(['is_active' => IsActiveEnum::ACTIVE->value]);
                    break;

                case 'replace':
                    // Delete the conflicting active record(s)
                    $query->delete();

                    // Activate the record
                    $item->update(['is_active' => IsActiveEnum::ACTIVE->value]);
                    break;

                case 'prevent':
                default:
                    // Abort if conflict exists and policy is to prevent
                    throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.Data conflict, activation not possible.'));
            }
        }

        // If no conflict exists, activate the record
        $item->update(['is_active' => IsActiveEnum::ACTIVE->value]);

        return $data;
    }
}
