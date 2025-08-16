<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Schema;
use App\Scopes\ActiveScope;
use App\Scopes\LanguageScope;

/**
 * Trait HasGeneralAttributeAndScopes
 *
 * Automatically applies global scopes to models that use this trait, based on column existence.
 * 
 *  Features:
 * - Applies `ActiveScope` if the model has an `is_active` column.
 * - Applies `LanguageScope` if the model has a `lang` column.
 * - Skips applying `ActiveScope` in `dashboard` and `api/dashboard` routes to avoid backend filtering.
 *
 *  Use Case:
 * Add this trait to any model that needs dynamic `is_active` and `lang` scoping,
 * without having to manually define them in each model.
 */
trait HasGeneralAttributeAndScopes
{
    /**
     * Boot the trait and register global scopes conditionally.
     *
     * - Adds the `ActiveScope` if:
     *   - The current route is not part of `dashboard/*` or `api/dashboard/*`.
     *   - The model has an `is_active` column.
     *
     * - Adds the `LanguageScope` if:
     *   - The model has a `lang` column.
     *
     * @return void
     */
    protected static function bootHasGeneralAttributeAndScopes(): void
    {
        // Avoid applying the ActiveScope in dashboard routes
        // to add these scopes only in routes not dashboard , because in dasboard need to show all data without any scopes
        if (!request()->is('dashboard/*') && !request()->is('api/dashboard/*')) {
            // Apply ActiveScope only if the model has an is_active column
            if (Schema::hasColumn((new static)->getTable(), 'is_active')) {
                static::addGlobalScope(new ActiveScope);
            }
        }

        // Apply LanguageScope if the model has a lang column
        if (!request()->is('dashboard/*') && !request()->is('api/dashboard/*')) {

            if (Schema::hasColumn((new static)->getTable(), 'lang')) {
                static::addGlobalScope(new LanguageScope);
            }
        }
    }

    
}
