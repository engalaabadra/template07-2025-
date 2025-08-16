<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
* Class ActivateRequest
* Handle validation for  activation.
 * 
 * Optional `` field accepts: 'activate', 'deactivate', or 'toggle'.
 * Authorization always returns true by default.
 */
class ActivateRequest extends FormRequest
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
        return [
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
