<?php

namespace App\Models\Traits\Relations;

/**
 * Trait TranslationRelations
 *
 * Provides translation handling for models using `lang` and `translate_id` fields.
 * Automatically applies a global scope for the app's current locale,
 * and provides helpers for identifying original records, translations, and language-based queries.
 */
trait TranslationRelations
{
    /**
     * Boot the trait and apply a global scope to filter by application locale.
     *
     * This ensures all queries on the model are scoped to the currently active app language.
     */
    protected static function bootTranslatableByRecordTrait(): void
    {
        static::addGlobalScope('lang', function (Builder $builder) {
            // Apply a where condition to automatically filter records by app locale
            $builder->where('lang', localeLang());
        });
    }

    /**
     * Check if the current record is a translation.
     *
     * @return bool
     */
    public function isTranslation(): bool
    {
        // Translation records have a non-null translate_id
        return !is_null($this->translate_id);
    }

    /**
     * Define a relationship to the original record of this translation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function original()
    {
        // A translation belongs to its original record
        return $this->belongsTo(static::class, 'translate_id');
    }

    /**
     * Define a relationship to fetch all translations for the current record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        // A record can have many translations
        return $this->hasMany(static::class, 'translate_id', 'id')->withoutGlobalScopes();
    }

    /**
     * Query scope to filter only original records (not translations).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOriginals(Builder $query): Builder
    {
        // Filter records that are not translations
        return $query->whereNull('translate_id');
    }

    /**
     * Query scope to filter records by specific locale.
     *
     * @param Builder $query
     * @param string $locale
     * @return Builder
     */
    public function scopeInLang(Builder $query, string $locale): Builder
    {
        // Remove the global lang scope and apply a manual where condition
        return $query->withoutGlobalScope('lang')->where('lang', $locale);
    }
}