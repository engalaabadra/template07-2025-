TranslationService

Handles the creation and update of translated records.

Key Methods:

createTranslations(array $translations, $mainItem, $model)

updateTranslations(array $translations, $mainItem, $model)

prepareStoringTranslation(array $transData, $mainItem, $model)

Each translated item:

Is skipped if the lang is the default.

Is created by copying non-translatable fields and combining them with the translated fields.