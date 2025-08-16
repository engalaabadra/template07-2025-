<?php

namespace App\Traits\Requests;

/**
 * Trait JsonArrayFieldsHandlerTrait
 *
 * This trait provides a helper method to decode specific fields from JSON string format
 * into native PHP arrays during request preparation. It is especially useful when
 * working with multipart/form-data requests (e.g. when uploading files), where array fields
 * are sent as JSON strings (like "[1, 2, 3]") instead of traditional repeated keys.
 *
 * Usage:
 * In your FormRequest class, use this trait and call `decodeJsonArrayFields(['roles', 'tags'])`
 * inside `prepareForValidation()` method.
 */
trait JsonArrayFieldsHandlerTrait
{
    /**
     * Convert specific JSON-encoded fields from string to PHP array, if needed.
     *
     * This method checks if any of the given fields contains a JSON string (e.g. "[1,2,3]"),
     * and if so, decodes it into a PHP array and merges it back into the request.
     * It's useful for handling array inputs sent via form-data when they arrive as JSON strings.
     *
     * @param array $fields An array of field names to decode if they are JSON strings.
     *
     * @return void
     */
    protected function decodeJsonArrayFields(array $fields): void
    {
        dd($fields);
        // Loop through each field name provided
        foreach ($fields as $field) {
            // Get the field value from the request input
            $value = $this->input($field);

            // Check if the value is a string that looks like a JSON array (starts with “[”)
            if (is_string($value) && str_starts_with($value, '[')) {
                // Attempt to decode the JSON string into a PHP array
                $decoded = json_decode($value, true);

                // If decoding was successful (no JSON error), merge the array into the request
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([
                        $field => $decoded,
                    ]);
                }
            }
        }
    }
}
