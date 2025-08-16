<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class BaseResource
 *
 * This resource acts as a base class for other API resources.
 * It provides dynamic handling for:
 * - Eloquent model fillable attributes.
 * - Optional appended accessors like `is_active_text`.
 * - Handling translation relationships.
 * - Returning plain arrays when the resource is not a model.
 */
class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array for JSON responses.
     *
     * - If the resource is already an array, return it as-is.
     * - If it's an Eloquent model with fillable fields, include only those fields in the output.
     * - Optionally append the 'is_active_text' accessor if available.
     * - If translations are loaded, format and include them.
     * - If none of the above applies, cast the resource to a plain array.
     *
     * @param  \Illuminate\Http\Request  $request  The current HTTP request instance
     * @return array  The formatted resource array
     */
    public function toArray($request)
    {
        // If the resource is an array like: ['token' => ..., 'user' => ProfileResource]
        if (is_array($this->resource)) {
            return $this->resource; // Return it directly without further processing
        }

        // Check if it's a model object before calling getFillable()
        if (is_object($this->resource) && method_exists($this->resource, 'getFillable')) {
            // Get fillable attributes
            // $data = collect($this->resource->getFillable())
            //     ->mapWithKeys(fn ($field) => [$field => $this->{$field}])
            //     ->toArray();

            // Retrieve an array of attributes from the current model
            // but only include those attributes that are defined as "fillable" (mass assignable).
            // This ensures only the allowed fields and their current values are returned.
            $data = $this->only($this->getFillable());

            // Automatically append any appended attributes , such as -> is_active_text and so on ...
            if (method_exists($this->resource, 'getAppends')) {
                foreach ($this->resource->getAppends() as $key) {
                    $data[$key] = $this->{$key};
                }
            }

            // Append translations if the 'translations' relationship is loaded
            if ($this->resource->relationLoaded('translations')) {
                $translationData = $this->getTranslationData($this->translations, get_class($this->resource));

                if (!empty($translationData)) {
                    $data['translations'] = $translationData;
                }
            }

            // Append created_at
            $data['created_at_text'] = $this->created_at_text;

            // Append deleted_at if the model supports soft deletes and it's set
            if (isset($this->deleted_at)) {
                $data['deleted_at_text'] = $this->deleted_at_text;
            }

            return $data;
        }

        // Fallback in case it's not a model or array
        return (array) $this->resource;
    }

    /**
     * Fetch all translations for this item to use in rendering the resource.
     *
     * @param  \Illuminate\Support\Collection|null  $translations
     * @param  string  $model
     * @return array  Array of translated fields like:
     * [
     *     'lang' => $translation->lang,
     *     'username' => $translation->username,
     *     'full_name' => $translation->full_name,
     *     'nick_name' => $translation->nick_name,
     *     'address' => $translation->address,
     * ]
     */
    public function getTranslationData($translations, $model)
    {
        // Return empty array if no translations are provided
        if (!$translations) {
            return [];
        }

        // Map each translation to include only the defined translation fields
        return $translations->map(function ($translation) use ($model) {
            // Get translatable field names from the model (access $translationFields statically)
            $fields = $model::$translationFields;
            
            // Build the translation array
            $translationFields = [];
            
            // Append lang, id, and translate_id
            $translationFields['id'] = $translation->id;
            $translationFields['translate_id'] = $translation->translate_id;
            $translationFields['lang'] = $translation->lang;

            foreach ($fields as $field) {
                $translationFields[$field] = $translation->{$field}; // Add translated value for each field
            }

            
            return $translationFields; // Return the mapped translation record
        })->toArray();
    }
}
