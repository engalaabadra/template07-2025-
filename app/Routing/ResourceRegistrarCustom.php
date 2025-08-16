<?php

namespace App\Routing;

use Illuminate\Routing\ResourceRegistrar as OriginalRegistrar;
use App\Routing\PendingCustomResourceRegistration;

/**
 * Class ResourceRegistrarCustom
 *
 * This class extends Laravel's default ResourceRegistrar
 * to add support for custom resourceful routes such as
 * trash, restoreMany, forceDeleteMany, etc.
 */
class ResourceRegistrarCustom extends OriginalRegistrar
{
    /**
     * The default actions for a resourceful controller.
     * Includes Laravel defaults plus custom actions.
     *
     * @var array
     */
    public $resourceDefaults = [
        'index',             // List resources
        'create',            // Show form for creating resource
        'store',             // Store new resource
        'changeActivate',    // Activate/deactivate a single resource
        'changeActivateMany',// Activate/deactivate multiple resources
        'restoreMany',       // Restore multiple soft-deleted resources
        'restore',           // Restore a single soft-deleted resource
        'show',              // Show a single resource
        'edit',              // Show form for editing resource
        'update',            // Update resource
        'destroyMany',       // Soft-delete multiple resources
        'forceDelete',       // Permanently delete a single resource
        'forceDeleteMany',   // Permanently delete multiple resources
        'destroy',           // Soft-delete a single resource
    ];

    /**
     * Register custom resource routes with custom methods and paths.
     *
     * @param  string  $name        Resource name (e.g. 'users')
     * @param  string  $controller  Controller class name handling the resource
     * @param  array   $options     Additional options, supports custom_only and custom_except keys
     * @return \App\Routing\PendingCustomResourceRegistration
     */
    public function registerCustomResource(string $name, string $controller, array $options = []): PendingCustomResourceRegistration
    {
        // Default Laravel resource route options like index, show, store, update, destroy
        $defaultOptions = $options;

        // Extract only custom route filtering options
        $customOnly   = $options['custom_only']   ?? null;  // Only include these custom routes
        $customExcept = $options['custom_except'] ?? null;  // Exclude these custom routes

        // Remove custom_only and custom_except so they don't affect Laravel's core resource routes
        unset($defaultOptions['custom_only'], $defaultOptions['custom_except']);

        // Register default Laravel resource routes with filtered options
        parent::register($name, $controller, $defaultOptions);

        // Define the custom routes config: HTTP methods and URI suffixes
        $customRoutesConfig = [
            'changeActivateMany'  => ['method' => 'patch',  'path' => '/activate'],
            'restoreMany'         => ['method' => 'patch',  'path' => '/restore'],
            'restore'             => ['method' => 'patch',  'path' => '/{id}/restore'],
            'changeActivate'      => ['method' => 'patch',  'path' => '/{id}/activate'],
            'destroyMany'         => ['method' => 'delete', 'path' => ''],
            'forceDelete'         => ['method' => 'delete', 'path' => '/{id}/force'],
            'forceDeleteMany'     => ['method' => 'delete', 'path' => '/force'],
        ];

        // Determine active custom methods based on only/except filters
        $customMethods = array_keys($customRoutesConfig);

        if ($customOnly) {
            // Keep only methods specified in custom_only
            $customMethods = array_intersect($customMethods, $customOnly);
        } elseif ($customExcept) {
            // Remove methods specified in custom_except
            $customMethods = array_diff($customMethods, $customExcept);
        }

        // Build the routes array for custom methods
        $routes = [];

        foreach ($customMethods as $key) {
            $data = $customRoutesConfig[$key];

            $routes[$key] = [
                'method' => $data['method'],                        // HTTP method (patch/delete)
                'uri'    => $this->getResourceUri($name) . $data['path'],  // Full URI path
                'action' => $this->getResourceAction($name, $controller, $key, $options), // Controller@method
                'name'   => "{$name}.{$key}",                       // Named route key
            ];
        }

        // Return pending registration with router and constructed routes
        return new PendingCustomResourceRegistration($this->router, $routes);
    }

    /**
     * Add the restoreMany method route for the resource.
     *
     * @param string $name Resource name
     * @param string $controller Controller class
     * @param array $options Route options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceRestoreMany($name, $controller, $options)
    {
        // Compose URI for restoreMany
        $uri    = $this->getResourceUri($name) . '/restore';
        // Compose controller action for restoreMany
        $action = $this->getResourceAction($name, $controller, 'restoreMany', $options);

        // Register PATCH route for restoreMany
        return $this->router->patch($uri, $action);
    }

    /**
     * Add the restore method route for the resource.
     *
     * @param string $name Resource name
     * @param string $controller Controller class
     * @param array $options Route options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceRestore($name, $controller, $options)
    {
        // Compose URI for restore single resource
        $uri    = $this->getResourceUri($name) . '/{id}/restore';
        // Compose controller action for restore
        $action = $this->getResourceAction($name, $controller, 'restore', $options);

        // Register PATCH route for restore
        return $this->router->patch($uri, $action);
    }

    /**
     * Add the changeActivate method route for the resource.
     *
     * @param string $name Resource name
     * @param string $controller Controller class
     * @param array $options Route options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceChangeActivate($name, $controller, $options)
    {
        // Compose URI for activating/deactivating single resource
        $uri    = $this->getResourceUri($name) . '/{id}/activate';
        // Compose controller action for changeActivate
        $action = $this->getResourceAction($name, $controller, 'changeActivate', $options);

        // Register PATCH route for changeActivate
        return $this->router->patch($uri, $action);
    }

    /**
     * Add the changeActivateMany method route for the resource.
     *
     * @param string $name Resource name
     * @param string $controller Controller class
     * @param array $options Route options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceChangeActivateMany($name, $controller, $options)
    {
        // Compose URI for activating/deactivating multiple resources
        $uri    = $this->getResourceUri($name) . '/activate';
        // Compose controller action for changeActivateMany
        $action = $this->getResourceAction($name, $controller, 'changeActivateMany', $options);

        // Register PATCH route for changeActivateMany
        return $this->router->patch($uri, $action);
    }

    /**
     * Add the destroyMany method route for the resource.
     *
     * @param string $name Resource name
     * @param string $controller Controller class
     * @param array $options Route options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceDestroyMany($name, $controller, $options)
    {
        // Compose URI for deleting multiple resources (soft delete)
        $uri    = $this->getResourceUri($name);
        // Compose controller action for destroyMany
        $action = $this->getResourceAction($name, $controller, 'destroyMany', $options);

        // Register DELETE route for destroyMany
        return $this->router->delete($uri, $action);
    }

    /**
     * Add the forceDelete method route for the resource.
     *
     * @param string $name Resource name
     * @param string $controller Controller class
     * @param array $options Route options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceForceDelete($name, $controller, $options)
    {
        // Compose URI for force deleting single resource
        $uri    = $this->getResourceUri($name) . '/{id}/force';
        // Compose controller action for forceDelete
        $action = $this->getResourceAction($name, $controller, 'forceDelete', $options);

        // Register DELETE route for forceDelete
        return $this->router->delete($uri, $action);
    }

    /**
     * Add the forceDeleteMany method route for the resource.
     *
     * @param string $name Resource name
     * @param string $controller Controller class
     * @param array $options Route options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceForceDeleteMany($name, $controller, $options)
    {
        // Compose URI for force deleting multiple resources
        $uri    = $this->getResourceUri($name) . '/force';
        // Compose controller action for forceDeleteMany
        $action = $this->getResourceAction($name, $controller, 'forceDeleteMany', $options);

        // Register DELETE route for forceDeleteMany
        return $this->router->delete($uri, $action);
    }
}
