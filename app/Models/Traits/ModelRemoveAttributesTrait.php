<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait ModelRemoveAttributesTrait
 *
 * This trait provides a utility method to remove any model attributes
 * that are not explicitly defined in the model's `$fillable` array.
 * 
 * This is useful when cleaning up model data before serialization
 * or restricting what attributes are exposed externally (e.g., in API responses).
 *
 * Notes:
 * - The `id` field is always retained, even if not in `$fillable`.
 * - The method expects the model to have a `resource` property that points to an instance of the same model.
 *   This is typically used when decorating or cloning the model.
 *
 * Usage Example:
 * ```php
 * $model->removeAttributeNotInFillable();
 * ```
 *
 * @mixin Model
 */
trait ModelRemoveAttributesTrait
{
    /**
     * Removes all attributes from the model instance that are not listed in the `$fillable` property.
     *
     * Keeps the `id` attribute intact even if it's not fillable.
     * 
     * This method relies on a `resource` property pointing to the original model, 
     * from which it fetches the `$fillable` array.
     * 
     * @return void
     */
    public function removeAttributeNotInFillable(): void
    {
        if (!$this instanceof Model) {
            return;
        }

        $fillable = $this->resource->getFillable();

        foreach ($this->attributesToArray() as $key => $value) {
            if ($key == 'id') {
                continue;
            }
            if (!in_array($key, $fillable)) {
                unset($this->$key);
            }
        }
    }
}
