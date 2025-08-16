<?php

namespace App\Traits\Controllers;

use Inertia\Inertia;

trait InertiaShareTrait
{
    public function breadcrumb(?array $items = null): void
    {
        if (!$items || count($items) == 0) return;

        $items = array_filter($items, fn($z) => data_get($z, 'label'));

        Inertia::share([
            'breadcrumb' => [
                ['label' => __('message.home'), 'url' => route('home')],
                ...$items,
            ],
        ]);

        $this->pageTitle(implode(' - ', array_column(array_slice($items, -2, 2, true), 'label')));
    }

    public function useBreadcrumb($append_breadcrumb = []): void
    {
        // You can customize or uncomment later
    }

    public function allowSearch(): void
    {
        Inertia::share(['allowSearch' => true]);
    }

    public function pageTitle(string $title): void
    {
        Inertia::share(['pageTitle' => $title]);
    }
}
