<?php
namespace App\Services\Translation;

use App\Scopes\LanguageScope;

class TranslationService{
    /**
     * Create translations for the given model.
     *
    * @param array  $translations Translation records (each with lang + translated fields)
     * @param object $mainItem     The original saved model (default language).
     * @param object $model        The model class (e.g., User::class).
     * @return void
     * example:
     * $data = [
     *   "lang" => "ar"
     *   "username" => "يوزر1"
     *   "full_name" => "يوزر"
     *   "translate_id" => 90
     *   "email" => "student@nnn.5585000"
     *   "phone_no" => "71115534813410"
     *   "country_id" => "63"
     *   "gender" => null
     *   "birth_date" => null
     *   ]
     */
    public function createTranslations(array $translations, object $mainItem, object $model): void
    {
        $lang = null;

        foreach ($translations as $transData) {
            // Extract the language if available
            if (isset($transData['lang'])) {
                $lang = $transData['lang'];
            }

            // Skip if the language is the default one (main record already covers it)
            if (isDefaultLocale($lang)) {
                continue;
            }

            // Check if the translation already exists to prevent duplication
            $existingTranslation = $model->withoutGlobalScope(\App\Scopes\LanguageScope::class)
                                        ->where('translate_id', $mainItem->id)
                                        ->where('lang', $lang)
                                        ->first();

            if ($existingTranslation) {
                continue;
            }

            // Prepare translation data for storage
            $data = $this->prepareStoringTranslation($transData, $mainItem, $model);

            // Create the new translation record
            $model->create($data);
        }
    }

    /**
     * Update translations: delete old and re-create from scratch.
     *
     * @param array $translations List of new translations.
     * @param object $mainItem The main record.
     * @param object $model The model to update/create translations for.
     * @return void
     */
    public function updateTranslations(array $translations, object $mainItem, object $model): void
    {
        foreach ($translations as $transData) {
            $lang = $transData['lang'];

            // Skip default language
            if (isDefaultLocale($lang)) {
                continue;
            }

            // Prepare translation data for update
            $data = $this->prepareStoringTranslation($transData, $mainItem, $model);

            // Update existing translation or create a new one
            $model->withoutGlobalScope(LanguageScope::class)
                ->updateOrCreate(
                    [
                        'translate_id' => $mainItem->id,
                        'lang'         => $data['lang'],
                    ],
                    $data
                );
        }
    }

    /**
     * Prepare translation data before saving.
     *
     * @param array $transData Data for one translation entry.
     * @param object $mainItem Main record to copy non-translated fields from.
     * @param object $model The model instance to use for structure.
     * @return array Prepared data ready to insert/update.
     */
    private function prepareStoringTranslation(array $transData, object $mainItem, object $model): array
    {
        // Start with basic fields
        $translationData = [
            'translate_id' => $mainItem->id,
            'lang'         => $transData['lang'] ?? null,
        ];

        // Add translated fields
        foreach ($model::$translationFields as $field) {
            if (isset($transData[$field])) {
                $translationData[$field] = $transData[$field];
            }
        }

        // Add excluded (copied) fields from main record
        foreach ($model::$excludedFields as $field) {
            $translationData[$field] = $mainItem->$field;
        }

        return $translationData;
    }

    /**
     * Handle translation logic depending on store/update type.
     *
     * @param object $model The model to act on.
     * @param object $item The main item being translated.
     * @param array|string $translations Translation data array or JSON string.
     * @param string $type Either 'store' or 'update'.
     * @return void
     */
    public function handleTranslations(object $model, object $item, array|string $translations, string $type): void
    {
        // Convert from JSON string to array if needed
        if (is_string($translations)) {
            $translations = json_decode($translations, true);
        }

        // Exit if still not a valid array
        if (!is_array($translations)) {
            return;
        }

        // Ensure all items are treated as arrays
        $convertedArray = array_map(fn($item) => (array) $item, $translations);

        // Call appropriate translation handler
        match ($type) {
            'store'  => $this->createTranslations($convertedArray, $item, $model),
            'update' => $this->updateTranslations($convertedArray, $item, $model),
            default  => null,
        };
    }

}
