<?php

namespace App\Traits\Services;

use Illuminate\Database\Eloquent\Model;
use App\Services\ServiceResponse;
use App\Exceptions\ApiResponseException;
use App\Enums\ServiceResponseEnum;

trait ChecksOwnershipTrait
{
    /**
     * Ensure the authenticated user owns the given model.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function ensureOwnership($item, string $ownerKey = 'user_id')
    {
        if (request()->is('api/dashboard/*')) {
            $user = adminApi();
        } elseif (request()->is('api/*')) {
            $user = userApi();
        }
        if ($item->{$ownerKey} !== $user->id) {
            throw new ApiResponseException(ServiceResponseEnum::FORBIDDEN);
        }
    }
}
