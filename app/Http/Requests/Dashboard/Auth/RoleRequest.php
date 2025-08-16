<?php

namespace App\Http\Requests\Dashboard\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use App\Enums\IsActiveEnum;

/**
 * Class RoleRequest
 *
 * This request handles validation for creating or updating a Role in the dashboard.
 */
class RoleRequest extends BaseRequest
{
    /**
     * The model class this request is associated with.
     *
     * @var string
     */
    protected string $modelClass = \App\Models\Role::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Allow all users to make this request (can be customized later).
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Get the role ID from route model binding, only available in update routes
        $roleId = $this->getRouteModel('role');

        // Define base validation rules
        $rules = [
            // Name is required, max 100 characters, must be unique for the same guard name (except for current role if updating)
            'name' => [
                'required',
                'max:100',
                Rule::unique('roles')
                    ->ignore($roleId), // ignoring current role during update
            ],

            // Display name is required, max 100, unique where not soft-deleted and active (except for current role)
            'display_name' => [
                'required',
                'max:100',
                Rule::unique('roles')
                    ->whereNull('deleted_at')
                    ->where('is_active', IsActiveEnum::ACTIVE->value)
                    ->ignore($roleId),
            ],

            // Permissions must be an array (if present)
            'permissions'     => ['sometimes', 'array'],

            // Each permission must exist in permissions table
            'permissions.*'   => ['exists:permissions,id'],

            // Example test field for translations (for demonstration or placeholder)
            'translations.*.test' => 'required',
        ];


        // This line is unreachable due to return above. Uncomment below if using dynamic translation rules.
        return $this->dynamicTranslationRules(
                    array_merge(
                        $rules,
                        $this->getIsActiveRuleIfDashboard()
                    ),
                    $this->modelClass::$translationFields,
                    $this->modelClass::$requiredFields
                );
    }

    /**
     * Custom validation error messages (optional).
     *
     * @return array
     */
    public function messages()
    {
        return [
            // You can define custom error messages here if needed
        ];
    }
}
