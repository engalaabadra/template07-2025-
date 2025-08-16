<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;

/**
 * Trait RestoresSoftDeletedModelTrait
 *
 * Provides a method to safely restore soft-deleted models while
 * handling unique field conflicts according to a given policy.
 */
trait RestoresSoftDeletedModelTrait
{
    /**
     * Safely restore a soft-deleted record by model class and item instance,
     * resolving uniqueness conflicts based on the selected policy.
     *
     * @param string $model               The fully qualified class name of the model (e.g. App\Models\User).
     * @param \Illuminate\Database\Eloquent\Model $item  The soft-deleted model instance to be restored.
     * @param string $policy             Conflict resolution policy: 'prevent', 'modify', or 'replace'. Default is 'prevent'.
     *                                   - prevent: Do not restore if conflict exists.
     *                                   - modify: Change conflicting fields (e.g., append timestamp), then restore.
     *                                   - replace: Delete conflicting records, then restore.
     *
     * @return array{
     *     status: bool,
     *     message: string
     * }  Result of the restore attempt.
     */
    public function safeRestoreById(string $model, $item, string $policy = 'prevent'): array
    {
        // Start a query to find records that may conflict (not soft-deleted)
        $query = $model::query()->whereNull('deleted_at');

        // Define the fields that should be checked for uniqueness
        $uniqueFields = ['name'];

        // Add where conditions for each unique field
        foreach ($uniqueFields as $field) {
            $query->where($field, $item->$field);
        }

        // Check if a conflicting record exists
        $conflictExists = $query->exists();

        // If conflict exists, handle based on selected policy
        if ($conflictExists) {
            switch ($policy) {

                case 'modify':
                    // Modify each unique field to avoid conflict (append timestamp)
                    foreach ($uniqueFields as $field) {
                        $item->$field = $item->$field . '_restored_' . now()->format('YmdHis');
                    }

                    // Save the updated model values
                    $item->save();

                    // Restore the soft-deleted record
                    $item->restore();
                    break;

                case 'replace':
                    // Delete conflicting active records
                    $query->delete();

                    // Restore the soft-deleted record
                    $item->restore();
                    break;

                case 'prevent':
                default:
                    // Conflict found, and policy is to prevent restore
                    throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.Data discrepancy, unrecoverable.'));
            }
        } else {
            // No conflict, safe to restore
            $item->restore();
        }

        return $item;
    }
}
