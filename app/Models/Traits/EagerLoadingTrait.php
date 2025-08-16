<?php

namespace App\Models\Traits;

/**
 * Trait EagerLoadingTrait
 *
 * Provides dynamic handling of Eloquent eager loading relationships on models.
 *
 * This allows setting and retrieving a list of relationships that should be eager loaded
 * during model queries. Useful for customizing what relations to load at runtime or across base models.
 */
trait EagerLoadingTrait
{
    /**
     * Set the eager loading relationships for the model.
     *
     * @param array $relations
     *     An array of relationship names to be eager loaded.
     *
     * @return static
     *     Returns the current model instance for method chaining.
     *
     * @example
     *     $user->setEagerLoading(['profile', 'roles']);
     */
    public function setEagerLoading(array $relations): static
    {
        $this->eagerLoading = $relations;
        return $this;
    }

    /**
     * Get the currently defined eager loading relationships.
     *
     * @return array
     *     Returns an array of relationships to be eager loaded.
     *
     * @example
     *     $relations = $user->getEagerLoading(); // ['profile', 'roles']
     */
    public function getEagerLoading(): array
    {
        return $this->eagerLoading ?? [];
    }
}
