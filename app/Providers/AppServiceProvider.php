<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

/**
 * AppServiceProvider bootstraps and binds core services and commands.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Reserved for future service bindings
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Improve Vite prefetch performance
        Vite::prefetch(concurrency: 3);


        // Bind custom resource registrar
        $this->app->bind(
            'Illuminate\Routing\ResourceRegistrar',
            \App\Routing\ResourceRegistrarCustom::class
        );

        app('router')->macro('customResource', function ($name, $controller, $options = []) {
            $registrar = new \App\Routing\ResourceRegistrarCustom(app('router'));

            // Laravel default methods
            if (isset($options['only'])) {
                $options['except'] = array_diff($registrar->getResourceMethods(), $options['only']);
                unset($options['only']);
            }

            // ✅ Handle custom_only to custom_except
            if (isset($options['custom_only'])) {
                $customMethods = [
                    'restoreMany',
                    'restore',
                    'changeActivate',
                    'changeActivateMany',
                    'destroyMany',
                    'forceDelete',
                    'forceDeleteMany',
                ];

                $options['custom_except'] = array_diff($customMethods, $options['custom_only']);
                unset($options['custom_only']);
            }

            return $registrar->registerCustomResource($name, $controller, $options);
        });


        // Add custom resource macro for file-based controllers
        app('router')->macro('customResourceFiles', function ($name, $controller, $options = []) {
            $registrar = new \App\Routing\ResourceRegistrarFiles(app('router'));
            return $registrar->registerCustomResource($name, $controller, $options);
        });
        
        Route::macro('customResourceWithFiles', function ($name, $controller, $resourceOptions = [], $fileOptions = []) {
            Route::customResource($name, $controller, $resourceOptions);

            // Parse fileOptions for `only` or `except`
            $fileRoutes = [
                'uploadFile'  => ['method' => 'post',   'path' => '/{id}/file'],
                'uploadFiles' => ['method' => 'post',   'path' => '/{id}/files'],
                'deleteFile'  => ['method' => 'delete', 'path' => '/{id}/file'],
                'deleteFiles' => ['method' => 'delete', 'path' => '/{id}/files'],
            ];

            // Always include restore unless explicitly excluded
            $alwaysInclude = ['restore'];

            if (isset($fileOptions['only'])) {
                $fileRoutes = array_merge(
                    \Illuminate\Support\Arr::only($fileRoutes, array_unique(array_merge($fileOptions['only'], $alwaysInclude)))
                );
            } elseif (isset($fileOptions['except'])) {
                $fileRoutes = \Illuminate\Support\Arr::except($fileRoutes, $fileOptions['except']);

            }
            foreach ($fileRoutes as $key => $route) {
                Route::{$route['method']}("$name{$route['path']}", [$controller, $key])->name("$name.$key");
            }
        });

        // If config file has been modified, reset cached main roles
        if (filemtime(config_path('spatie_seeder.php')) > cache()->get('main_roles_config_updated_at', 0)) {
            cache()->forget('main_roles_config');
            cache()->put('main_roles_config_updated_at', filemtime(config_path('spatie_seeder.php')));
        }

        /** */

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

        /** */
        // Trigger role verification only if not running in console and not yet verified today
        if (!app()->runningInConsole()) {
             // ❌ أوقف النظام لو الكاش غير موجود (خلل محتمل)
            if (!cache()->has('main_roles_config')) {
                abort(500, 'Invalid role configuration or missing roles.');
            }
            if (!cache()->has('roles_verified_today')) {
              //  Artisan::call('verify:main-roles');
                cache()->put('roles_verified_today', true, now()->addHours(24));
            }
        }
    }
}
