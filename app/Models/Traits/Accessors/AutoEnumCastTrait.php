<?php


namespace App\Models\Traits\Accessors;

use Illuminate\Support\Facades\Schema;

/**
 * Trait AutoEnumCastTrait
 *
 * Automatically casts model attributes to their corresponding Enum classes if defined.
 * It works by checking if the column exists in the table and applies the Enum casting
 * dynamically without needing to manually define it in the `$casts` array.
 */
trait AutoEnumCastTrait
{
    /**
     * List of attributes to automatically cast to Enum classes.
     * You can define additional attribute => EnumClass mappings here.
     *
     * @var array
     */
    protected array $autoEnumCasts = [
        'is_active' => \App\Enums\IsActiveEnum::class,
    ];

    /**
     * Cache of table columns to avoid repeated schema lookups.
     *
     * @var array
     */
    protected static array $columnCache = [];

    /**
     * Initialize the trait when the model instance is created.
     * This will apply enum casts based on defined `$autoEnumCasts`.
     *
     * @return void
     */
    public function initializeAutoEnumCastTrait(): void
    {
        $this->applyAutoEnumCasts();
    }

    /**
     * Boot method for the trait. Hooks into model lifecycle events.
     * Ensures enum casts are applied during `retrieved`, `creating`, and `updating` events.
     *
     * @return void
     */
    public static function bootAutoEnumCastTrait(): void
    {
        static::retrieved(fn($model) => $model->applyAutoEnumCasts());
        static::creating(fn($model) => $model->applyAutoEnumCasts());
        static::updating(fn($model) => $model->applyAutoEnumCasts());
    }

    /**
     * Applies the enum casts to the model attributes dynamically (put it automaticly in casts model).
     * Only applies if the attribute exists in the table schema and not already casted.
     *
     * @return void
     */
    protected function applyAutoEnumCasts(): void
    {
        foreach ($this->autoEnumCasts as $column => $enumClass) {

            // Skip if the cast is already defined
            if (isset($this->casts[$column])) {
                continue;
            }

            // Check if the column exists in the model's table and apply cast
            if ($this->hasCachedColumn($column)) {
                $this->casts[$column] = $enumClass;
            }
        }
    }

    /**
     * Checks if the given column exists in the table (using cache).
     *
     * @param string $column
     * @return bool
     */
    protected function hasCachedColumn(string $column): bool
    {
        $table = $this->getTable();

        // If table columns not cached yet, retrieve and store them
        if (!isset(static::$columnCache[$table])) {
            try {
                static::$columnCache[$table] = Schema::getColumnListing($table);
            } catch (\Throwable) {
                static::$columnCache[$table] = [];
            }
        }

        // Check if the column exists in the cached column list
        return in_array($column, static::$columnCache[$table], true);
    }
}
