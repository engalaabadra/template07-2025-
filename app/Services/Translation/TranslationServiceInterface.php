<?php
namespace App\Services\Translation;

interface TranslationServiceInterface{
     
    public function createTranslations($translations, $newItem, $model);
    public function updateTranslations($translations, $newItem, $model);
    public function prepareStoringTranslation(array $transData, $newItem, $model);

}