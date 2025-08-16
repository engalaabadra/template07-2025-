<?php

namespace App\Traits\Controllers;

use App\Traits\Controllers\FormDataTrait;
use App\Traits\Controllers\InertiaShareTrait;
use App\Traits\Controllers\WebIndexRenderTrait;
/**
 * Trait for UI rendering helpers (Inertia, breadcrumbs, etc.)
 *
 * Includes:
 * - FormDataTrait (shared form options)
 * - InertiaShareTrait (sharing props with frontend -> breadcrumb , pageTitle, allowSearch)
 * - WebIndexRenderTrait (standard response for index pages)
 */
trait UIHelpersTrait
{
    use FormDataTrait;
    use InertiaShareTrait;
    use WebIndexRenderTrait;
}

