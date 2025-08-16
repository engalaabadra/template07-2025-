<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Rules\SmallTextRule;
use App\Rules\UniqueTranslationValue;
use App\Enums\IsActiveEnum;
use Illuminate\Validation\Rules\Enum;
use App\Traits\Requests\JsonArrayFieldsHandlerTrait;

/**
 * Class BaseRequest
 *
 * This base form request extends Laravel's FormRequest and adds:
 * - Custom validation failure response (JSON).
 * - JSON decoding for 'translations' input.
 * - Helper methods to get model from route.
 * - Dynamic validation rules for multilingual fields.
 * 
 * A base request class to handle shared validation logic across all form requests.
 * It preserves the original rules() method in child classes and allows merging
 * additional rules (like 'is_active') without requiring any changes in child requests.
 * 
 */
class BaseRequest extends FormRequest
{
    use JsonArrayFieldsHandlerTrait;

    protected string $modelClass;

    /**
     * Get the model class name passed from child request.
     */
    protected function getModelInstance(): \Illuminate\Database\Eloquent\Model
    {
        return new $this->modelClass;
    }
   
    /**
     * Conditionally add shared 'is_active' rule if the request is under a dashboard route.
     *
     * @return array
     */
    protected function getIsActiveRuleIfDashboard(): array
    {
        // Check if the request is targeting a api/dashboard route
        if (request()->is('api/dashboard/*')) {
            return [
                // Add a nullable 'is_active' field with enum validation
                'is_active' => ['nullable', new Enum(IsActiveEnum::class)],
            ];
        }

        // Return empty array if no additional rules are needed
        return [];
    }

   
    /**
     * Override default validation failure response to return JSON instead of redirect.
     *
     * @param  Validator  $validator
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422));
    }

    /**
     * Returns the list of fields that should be auto-decoded from JSON strings to arrays.
     *
     * @return array
     */
    protected function jsonArrayFields(): array
    {
        return ['roles', 'permissions', 'translations', 'ids']; // Add any general field that should be sent as a JSON array
    }

    /**
     * Automatically decode JSON string fields to PHP arrays before validation.
     */
    protected function prepareForValidation()
    {
        // foreach ($this->all() as $field => $value) {

        foreach ($this->jsonArrayFields() as $field) {
            //** when send data in form data , but if in row -> all cases is pass */
            //if in req ->  ids[] : 1,2 , will $value : array:1 [ 0 => "1,2"] -> this ok in queries laravel
            //if in req ->  ids : [1,2] , will $value : "[1,2]" -> this not ok in queries laravel , so will make json_decode on this value
            // i made combine between 2 cases to flex with front , which is front if send and formatting for any data in array will pass
            $value = $this->input($field);
            // Decode if JSON string
            if (is_string($value) && str_starts_with($value, '[')) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $value = $decoded;
                }
            }
            /*
                // Special handling for "ids" field:
                // If the current field is "ids" and it's already an array,
                // 1. Remove any null or empty string values (array_filter)
                // 2. Convert all remaining values to integers (array_map)
                // This ensures we only keep valid numeric IDs in a clean format.
            */
            // if ($field === 'ids' && is_array($value)) {
            //     // Case: ["1,2"] => explode to [1,2]
            //     if (count($value) === 1 && is_string($value[0]) && str_contains($value[0], ',')) {
            //         $value = explode(',', $value[0]);
            //     }
            //     $value = array_map('intval', array_filter($value, fn($id) => $id !== null && $id !== ''));
            // }

             // Special handling for "ids" field
            // if ($field === 'ids') {
            //     // Case: string "all" -> keep as string
            //     if ($value === 'all') {
            //         $this->merge([$field => 'all']);
            //         continue;
            //     }

            //     // Case: array
            //     if (is_array($value)) {
            //         // Case: ["1,2"] -> explode to [1,2]
            //         if (count($value) === 1 && is_string($value[0]) && str_contains($value[0], ',')) {
            //             $value = explode(',', $value[0]);
            //         }

            //         // Remove null/empty and convert to integers
            //         $value = array_map('intval', array_filter($value, fn($id) => $id !== null && $id !== ''));
            //     }
            // }

            if ($field === 'ids') {
                if (is_array($value) && count($value) === 1 && $value[0] === 'all') {
                    $value = 'all';
                } elseif ($value === 'all') {
                    // already string "all", لا تعديل
                } elseif (is_array($value)) {
                    // Case: ["1,2"] => explode to [1,2]
                    if (count($value) === 1 && is_string($value[0]) && str_contains($value[0], ',')) {
                        $value = explode(',', $value[0]);
                    }
                    $value = array_map('intval', array_filter($value, fn($id) => $id !== null && $id !== ''));
                }
            }
                        // Merge normalized value back into request
            $this->merge([$field => $value]);
        }
    }


    /**
     * Add dynamic validation rules for multilingual/translatable fields.
     *
     * This method ensures:
     * - The main `lang` field is validated against allowed system languages.
     * - Each `translations` array item is validated per field and language.
     *
     * @param  array  $rules             Base validation rules.
     * @param  array  $translationFields List of fields that require translation.
     * @param  array  $requiredFields    Optional list of required fields.
     * @return array Merged validation rules with multilingual translation field validation.
     */
    public function dynamicTranslationRules(array $rules, array $translationFields, array $requiredFields = []): array
    {
        $modelInstance = $this->getModelInstance();

        // 1. Validate 'lang' (main input item) against system languages
        $rules['lang'] = [
            'string',
            'in:' . implode(',', supportedLanguages()),
        ];

        // 2. Ensure 'translations' is an array , like : [{"lang": "ar","title": "موظف1"},{"lang": "fr","title": "Nom de l'enseignant"}]
        $rules['translations'] = ['nullable', 'array'];

        // Validate each translation's 'lang' key
        $rules['translations.*.lang'] = [
            'required',
            'string',
            'distinct',
            'in:' . implode(',', supportedLanguages()),
        ];

        // 3. Get model from route or fallback to new instance to use in UniqueTranslationValue rule
        $modelName = rtrim(modelName($this->modelClass), 's');
        $tableName = (new $modelInstance)?->getTable();
        $item = $this->getRouteModel($modelName);
      
        $ignoreId = null;
        $translateId = null;

        if ($item) {
            $ignoreId = is_numeric($item) ? $item : $item->id;
            $translateId = $ignoreId;
        }


        // 4. validate on fields translations Loop through each translatable field
        foreach ($translationFields as $field) {
            $isRequired = in_array($field, $requiredFields);

            $rules["translations.*.$field"] = [
                $isRequired ? 'required' : 'nullable',
                'string',
                'max:255',
                new SmallTextRule(),
                new UniqueTranslationValue($tableName, $field, $ignoreId, $translateId),
            ];
        }

        return $rules;
    }

    /**
     * Get the model from the current route.
     *
     * Checks if the route contains either a model instance or numeric ID.
     *
     * to easy deal with param in route -> model item or id item , which if model item(user) will take id from it , if id item will take this id
     * @param  string  $key  The route parameter key (e.g. 'banner', 'user', or 'id').
     * @return int|null      The resolved ID or null if not found.
     */
    protected function getRouteModel(string $key = 'id'): ?int
    {
        $routeItem = $this->route($key);

        return $routeItem instanceof \Illuminate\Database\Eloquent\Model
            ? $routeItem->id
            : (is_numeric($routeItem) ? (int) $routeItem : null);
    }



}
