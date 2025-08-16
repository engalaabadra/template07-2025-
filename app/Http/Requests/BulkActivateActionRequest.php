<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
* Class BulkActivateActionRequest
* Handle validation for bulk activation actions.
 * 
 * Optional `action` field accepts: 'activate', 'deactivate', or 'toggle'.
 * Authorization always returns true by default.
 */
class BulkActivateActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always returns true, but can be modified to apply permission logic.
     */
    public function authorize(): bool
    {
        return true; // Allow all requests for now; modify if needed for authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        dd(7);
        return [
            'ids' => [
                'required', // The `ids` field must be present
                function ($attribute, $value, $fail) {
                    // If the value is exactly "all", it's valid
                    if ($value === 'all') {
                        return true;
                    }

                    // If the value is an array, validate each ID
                    if (is_array($value)) {
                        foreach ($value as $id) {
                            dd($id);
                            // Each ID must be a numeric integer
                            if (!is_numeric($id) || (int)$id != $id) {
                                return $fail(__('messages.Each ID must be an integer.'));
                            }
                        }

                        // The array must not be empty
                        if (count($value) === 0) {
                            return $fail(__('messages.IDs array cannot be empty.'));
                        }

                        return true; // All checks passed
                    }

                    // If not "all" or a valid array, it's invalid
                    return $fail(__('messages.IDs must be either "all" or a non-empty array of integers.'));
                }
            ],
            // Optional: toggle (default), activate, or deactivate
            'action'   => ['nullable', 'in:activate,deactivate,toggle'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            
        ];
    }
}
