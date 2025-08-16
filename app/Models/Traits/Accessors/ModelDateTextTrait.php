<?php

namespace App\Models\Traits\Accessors;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait ModelDateTextTrait
 *
 * Provides formatted date accessors like `*_text` for `created_at`, `updated_at`, and `deleted_at`.
 * Automatically appends the following attributes when the model is retrieved:
 * - created_at_text     → formatted as "Y-m-d" or relative time (e.g., "2 days ago")
 * - created_at_text2    → full datetime format "Y-m-d h:i A"
 * - updated_at_text     → same formatting as created_at_text
 * - deleted_at_text     → same formatting as created_at_text
 *
 * The formatting adapts based on how old the date is (hours, days, etc.).
 * Additionally, localized replacements are made for AM/PM based on current app locale.
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property-read string $created_at_text
 * @property-read string $created_at_text2
 * @property-read string $updated_at_text
 * @property-read string $deleted_at_text
 */
/**
 * Trait ModelDateTextTrait
 *
 * Automatically adds and formats *_text attributes for model datetime fields.
 * Appends attributes like `created_at_text`, `updated_at_text`, etc., with human-readable formats.
 */
trait ModelDateTextTrait
{
    /**
     * Boot method for the trait.
     * Automatically adds *_text attributes to appends when the model is retrieved.
     */
    protected static function bootModelDateTextTrait(): void
    {
        // Trigger appending *_text attributes on model retrieved event
        self::modelDateTextTraitUseAppend();
    }

    /**
     * Appends formatted date accessors after retrieving the model.
     * Adds the following virtual attributes to the model appends:
     * - created_at_text
     * - created_at_text2
     * - updated_at_text
     * - deleted_at_text
     */
    private static function modelDateTextTraitUseAppend(): void
    {
        static::retrieved(function ($model) {
            // Define virtual attributes to append
            $attributesToAppend = [
                'created_at_text',
                // 'created_at_text2',
                // 'updated_at_text',
                // 'deleted_at_text',
            ];

            // Merge the new attributes with the existing appends array
            $model->appends = array_unique(array_merge($model->appends, $attributesToAppend));
        });
    }

    /** ───── Accessors ───── */

    /**
     * Accessor: Returns formatted created_at value (e.g. "3 hours ago" or "2025-07-30")
     *
     * @return string|null
     */
    public function getCreatedAtTextAttribute(): ?string
    {
        return $this->formatDateTime($this->created_at);
    }

    /**
     * Accessor: Returns fully formatted created_at value (e.g. "2025-07-30 09:45 AM")
     *
     * @return string|null
     */
    public function getCreatedAtText2Attribute(): ?string
    {
        return $this->formatFullDateTime($this->created_at);
    }

    /**
     * Accessor: Returns formatted updated_at value (same format logic as created_at_text)
     *
     * @return string|null
     */
    public function getUpdatedAtTextAttribute(): ?string
    {
        return $this->formatDateTime($this->updated_at);
    }

    /**
     * Accessor: Returns formatted deleted_at value (same format logic as created_at_text)
     *
     * @return string|null
     */
    public function getDeletedAtTextAttribute(): ?string
    {
        return $this->formatDateTime($this->deleted_at);
    }

    /** ───── Helpers ───── */

    /**
     * Format a datetime into a readable string.
     * 
     * - If less than 24 hours ago: returns "x hours ago"
     * - If less than 7 days: returns "Y-m-d h:i A"
     * - Otherwise: returns "Y-m-d"
     *
     * @param mixed $date  The datetime value to format
     * @return string|null
     */
    public function formatDateTime($date): ?string
    {
        if (!$date) return '---';

        // Parse the datetime using Carbon
        $carbon = !is_string($date) && get_class($date) === Carbon::class
            ? $date
            : Carbon::parse($date);

        // Less than 24 hours: show relative time (e.g., "2 hours ago")
        if ($carbon->diffInHours() < 24) {
            return self::translateDate($carbon->diffForHumans());
        }

        // Less than 7 days: show full date and time
        if ($carbon->diffInDays() < 7) {
            return self::translateDate($carbon->format('Y-m-d h:i A'));
        }

        // Otherwise: show only date
        return self::translateDate($carbon->format('Y-m-d'));
    }

    /**
     * Format a datetime into full format "Y-m-d h:i A"
     *
     * @param mixed $date  The datetime value to format
     * @return string|null
     */
    public function formatFullDateTime($date): ?string
    {
        if (!$date) return '---';

        // Parse the datetime using Carbon
        $carbon = !is_string($date) && get_class($date) === Carbon::class
            ? $date
            : Carbon::parse($date);

        // Return formatted date string
        return self::translateDate($carbon->format('Y-m-d h:i A'));
    }

    /**
     * Translate AM/PM part of the date string to match the current app locale.
     * For example, "AM" becomes "ص" in Arabic.
     *
     * @param string $formattedDateTime  The formatted datetime string
     * @return string
     */
    public static function translateDate(string $formattedDateTime): string
    {
        // Get the current application locale
        $locale = app()->getLocale();

        // Define AM/PM replacements for supported locales
        $replacements = [
            'ar' => ['AM' => 'ص', 'PM' => 'م'],
            'en' => ['AM' => 'AM', 'PM' => 'PM'],
        ];

        // Get the replacement array or fall back to English
        $replacement = $replacements[$locale] ?? $replacements['en'];

        // Replace the AM/PM values
        return str_replace(array_keys($replacement), array_values($replacement), $formattedDateTime);
    }
}
