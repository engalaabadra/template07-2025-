<?php

namespace App\Routing;

use Illuminate\Routing\Router;

/**
 * Class PendingCustomResourceRegistration
 *
 * Handles the registration of custom resource routes with optional filtering using `only` and `except`.
 * Routes are automatically registered upon object destruction.
 */
class PendingCustomResourceRegistration
{
    /**
     * The Laravel router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected Router $router;

    /**
     * The list of routes to register.
     *
     * @var array
     */
    protected array $routes;

    /**
     * Create a new PendingCustomResourceRegistration instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  array  $routes
     */
    public function __construct(Router $router, array $routes)
    {
        $this->router = $router;
        $this->routes = $routes;
    }

    /**
     * Remove the given methods from the list of routes.
     *
     * @param  array  $methods
     * @return $this
     */
    public function except(array $methods): self
    {
        foreach ($methods as $method) {
            unset($this->routes[$method]); // Remove the method if it exists
        }

        return $this;
    }

    /**
     * Keep only the given methods in the list of routes.
     *
     * @param  array  $methods
     * @return $this
     */
    public function only(array $methods): self
    {
        // Keep only keys that exist in the given methods array
        $this->routes = array_intersect_key($this->routes, array_flip($methods));

        return $this;
    }

    /**
     * Register the defined routes using the router.
     *
     * @return void
     */
    public function register(): void
    {
        foreach ($this->routes as $route) {
            // Dynamically call the HTTP method (e.g., post, delete)
            $this->router
                ->{$route['method']}($route['uri'], $route['action']) // Define the route with URI and controller action
                ->name($route['name']); // Assign a route name
        }
    }

    /**
     * Automatically register routes when the object is destroyed.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->register(); // Register routes upon destruction of this object
    }
}
