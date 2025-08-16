<?php

use Illuminate\Database\Eloquent\Model;

/**
 * ===========================================
 *  MODEL HELPERS
 * ===========================================
 */

if (!function_exists('isSoftDeletes')) {
    /**
     * Check if a model uses SoftDeletes trait.
     *
     * @param object $model
     * @return bool
     */
    function isSoftDeletes($model)
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model));
    }
}

if (!function_exists('modelName')) {
    /**
     * Get plural lowercase model name.
     *
     * @param object $model
     * @return string
     */
    function modelName($model)
    {
        return strtolower(class_basename($model)) . 's';
    }
}

if (!function_exists('getModelClass')) {
    /**
     * Get fully qualified model class name.
     *
     * @param string $modelName
     * @return string|null
     */
    function getModelClass($modelName)
    {
        $modelClass = 'App\\Models\\' . ucfirst($modelName);
        return class_exists($modelClass) ? $modelClass : null;
    }
}

if (!function_exists('refreshIfMissing')) {
    /**
     * Refresh the model if a key is missing from request data.
     *
     * @param array $data
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @return void
     */
    function refreshIfMissing(array $data, Model $model, string $key = 'is_active'): void
    {
        if (!array_key_exists($key, $data)) {
            $model->refresh();
        }
    }
}
