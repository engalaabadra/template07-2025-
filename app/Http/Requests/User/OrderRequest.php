<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class OrderRequest
 *
 * This request handles validation for placing an order,
 * ensuring the payment method is valid and provided.
 */
class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Allow all users to submit this order request
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The payment_method_id field is required and must exist in the payment_methods table
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ];
    }

    /**
     * Get custom validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Add custom messages here if needed
        ];
    }
}
