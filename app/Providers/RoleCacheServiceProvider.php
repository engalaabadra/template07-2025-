<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class RoleCacheServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cache the roles only if they are not already cached
        if (!Cache::has('main_roles_config')) {
            // Get the roles structure and main role from config
            $rolesStructure = Config::get('spatie_seeder.roles_structure', []);
            $mainRole = Config::get('spatie_seeder.main_role');

            // Ensure the configured main role actually exists in the roles structure
            if (!array_key_exists($mainRole, $rolesStructure)) {
                if (app()->environment('production')) {
                    abort(500, "Invalid config: The 'main_role' ($mainRole) is not defined in 'roles_structure'.");
                } else {
                    logger()->warning("Config issue: 'main_role' ($mainRole) not in 'roles_structure'");
                }
            }

            // Extract all role keys from the structure
            $mainRoles = array_keys($rolesStructure);

            // Cache the roles and the main role name permanently
            Cache::forever('main_roles_config', $mainRoles);
            Cache::forever('main_role_name', $mainRole);
        }
    }
}
