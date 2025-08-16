<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\BaseRequest;
use App\Rules\IdsRule;

/**
 * Class BulkActionRequest
 *
 * This form request handles validation for bulk actions (like delete or restore).
 * It ensures that the `ids` field is either the string `"all"` or a non-empty array of integers.
 *
 * Example valid inputs:
 * - { "ids": "all" }
 * - { "ids": [1, 2, 3] }
 *
 * Example invalid inputs:
 * - { "ids": null }
 * - { "ids": [] }
 * - { "ids": ["a", "b"] }
 */
class BulkActionRequest extends BaseRequest
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
            'ids' => [
                'required', // The `ids` field must be present
                new IdsRule()
            ],

            // Optional: toggle (default), activate, or deactivate
            'action_activation'   => ['nullable', 'in:activate,deactivate,toggle'],

            // Optional conflict strategy (e.g. modify, skip, abort)
            'strategy' => ['nullable', 'in:modify,skip,abort'],
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
            // Custom message if 'ids' is missing
            'ids.required' => __('messages.Pls, enter ids'),
        ];
    }
}
