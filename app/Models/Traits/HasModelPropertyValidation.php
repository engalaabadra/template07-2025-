<?php

namespace App\Models\Traits;

use App\Services\ServiceResponse;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;

/**
 * Trait HasModelPropertyValidation
 *
 * This trait provides a helper method to validate that specific static properties exist
 * in the model using this trait. If any property is missing, it returns a standardized
 * JSON error response.
 */
trait HasModelPropertyValidation
{
    /**
     * Ensure that the given static properties exist on the model.
     *
     * @param array $requiredProperties An array of static property names to check for existence.
     * @return \Illuminate\Http\JsonResponse|null Returns an error response if a property is missing, otherwise null.
     */
    protected static function ensureModelPropertiesExist(array $requiredProperties)
    {
        // Loop through each required property
        foreach ($requiredProperties as $property) {
            // Check if the property does not exist in the current model class
            if (!property_exists(static::class, $property)) {
                // Return a standardized server error response indicating the missing property
                throw new ApiResponseException(ServiceResponseEnum::SERVER_ERROR, trans('messages.Missing required model property: \${$property}'));               
            }
        }

        // If all properties exist, return null (no error)
        return null;
    }
}
