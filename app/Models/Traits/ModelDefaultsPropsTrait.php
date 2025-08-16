<?php

namespace App\Models\Traits;

/**
 * Trait HasGeneralProperties
 *
 * Provides general reusable properties for models.
 * This trait can be included in any model to give it
 * common attributes without rewriting them each time.
 *
 * - If the model defines a property with the same name, 
 *   it overrides the one from the trait.
 * - If the property exists only in the model, it works normally.
 * - If the property exists only in the trait, the model can still use it.
 * - If the prperty not exist in the trait , will return null instead error
 * This ensures that there is no any bug , when calling any prop from a model , which is if exist -> return the props, if no -> return null JUST
 * 
 */

trait ModelDefaultsTrait
{
   
    /**
     * Dynamically retrieve a property value when accessed from an ( INSTANCE ).
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        // Instance property (not static)
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        // Static property
        if (property_exists(static::class, $name)) {
            return static::${$name};
        }

        return null;
    }

    /**
     * Dynamically retrieve a property value when called statically as a method, when accessed from a (CLASS)
     *
     * @param string $name
     * @param array $arguments
     * @return mixed|null
     */
    public static function __callStatic($name, $arguments)
    {
        // Static property
        if (property_exists(static::class, $name)) {
            return static::${$name};
        }

        // Instance property (create temp instance)
        $instance = new static();
        if (property_exists($instance, $name)) {
            return $instance->$name;
        }

        return null;
    }
}
