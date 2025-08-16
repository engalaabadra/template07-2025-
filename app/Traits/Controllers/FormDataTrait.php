<?php

namespace App\Traits\Controllers;

use App\Enums\IsActiveEnum;

trait FormDataTrait
{
    public function getCreateUpdateData(): array
    {
        return [
            'form_data' => [
                'is_active' => IsActiveEnum::getOptionsData()
            ],
        ];

    }
}
