<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\FilesRule;
use App\Http\Requests\BaseRequest;
use App\Rules\IdsRule;

/**
 * Class DeleteFilesRequest
 *
 * This request class handles validation for Deleteing multiple files.
 * It ensures each file is required, of a valid type, and within the size limit.
 */
class DeleteFilesRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Allow all users to perform this request
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'ids' => [
                'required', // The `ids` field must be present
                new IdsRule()
                // function ($attribute, $value, $fail) {
                //     // If the value is exactly "all", it's valid
                //     if ($value === 'all') {
                //         return true;
                //     }

                //     // If the value is an array, validate each ID
                //     if (is_array($value)) {
                //         foreach ($value as $id) {
                //             // Each ID must be a numeric integer
                //             if (!is_numeric($id) || (int)$id != $id) {
                //                 return $fail(__('messages.Each ID must be an integer.'));
                //             }
                //         }

                //         // The array must not be empty
                //         if (count($value) === 0) {
                //             return $fail(__('messages.IDs array cannot be empty.'));
                //         }

                //         return true; // All checks passed
                //     }

                //     // If not "all" or a valid array, it's invalid
                //     // return $fail(__('messages.IDs must be either "all" or a non-empty array of integers.'));
                // }
            ]
        ];
    }

    /**
     * Custom error messages (optional).
     *
     * @return array<string, string>
     */
    public function messages()
    {
        // No custom messages defined
        return [];
    }
}
