<?php

namespace App\Traits\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Traits\Controllers\FormDataTrait;

trait WebIndexRenderTrait
{
    use FormDataTrait;

    /**
     * Render a unified Inertia web page response for index or detail views.
     *
     * This method automatically detects whether the provided `$rows` is a collection (for index view)
     * or a single model instance (for detail view), and structures the response data accordingly.
     *
     * It also automatically includes:
     * - `form_data` from `getCreateUpdateData()` for create/update forms (only for index).
     * - A fallback `pageTitle` if not explicitly provided via `$extra`, using the last two
     *   breadcrumb items shared via Inertia.
     *
     * @param string $viewPath The Inertia view path to render (e.g., 'User/Index', 'Project/Show').
     * @param mixed $rows A collection or paginator for index views, or a single model instance for detail views.
     * @param array $extra Additional data to merge into the response (e.g., breadcrumb, abilities, pageTitle).
     *
     * @return \Inertia\Response
     */
    protected function renderWebIndexPage(string $viewPath, $rows, array $extra = []): \Inertia\Response
    {
        // Auto-generate page title from breadcrumb if not provided
        if (!array_key_exists('pageTitle', $extra)) {
            $breadcrumb = Inertia::getShared('breadcrumb') ?? [];
            $title = implode(' - ', array_column(array_slice($breadcrumb, -2), 'label'));
            $extra['pageTitle'] = $title ?: __('message.page_title');
        }

        // Final response payload (under "data" key)
        return Inertia::render($viewPath, [
            'data' => array_merge($data, $extra),
        ]);
    }

}
