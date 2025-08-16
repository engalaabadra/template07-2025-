<?php
namespace App\Traits\Services;

trait HandlesTranslationsAndFilesTrait{
    /**
     * Handle translations and file uploads.
     *
     * @param object $request  The request object containing validated data.
     * @param object $model    The model to query.
     * @param object $item     The item being processed.
     * @param string $typeOperation     The type of operation ('store' or 'update').
     */
    protected function handleTranslationsAndFiles($request, $model, $item, $typeOperation)
    {
        // Handle translations
        if ($request->filled('translations')) {
            $this->translationService->handleTranslations($model, $item, $request->get('translations'), $typeOperation);
        }

        // Handle file uploads
        $folder = modelName($model);

        // Upload single or multiple media based on provided input
        foreach (['file' => 'file', 'image' => 'image'] as $key => $type) {
            if (isset($data[$key])) {
                $item->uploadSingleMedia($request->file($key), $type, $folder);
            }
        }

        foreach (['files' => 'file', 'images' => 'image'] as $key => $type) {
            if (isset($data[$key])) {
                $item->uploadMultipleMedia($request->file($key), $type, $folder);
            }
        }

    }
}