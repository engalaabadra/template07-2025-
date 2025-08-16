<?php

// App\Models\Traits\OverridesQueryTrait.php
namespace App\Models\Traits;

trait OverridesQueryTrait
{
    public static function query()
    {
        return parent::query();
    }
}
