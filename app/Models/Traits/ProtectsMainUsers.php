<?php

namespace App\Models\Traits;

use App\Exceptions\MainUserModificationException;
use App\Models\Traits\LoadsMainRoles;

trait ProtectsMainUsers
{
    use LoadsMainRoles;

    protected static function bootProtectsMainUsers(): void
    {
        static::updating(function ($user) {
            static::blockIfMainUser($user, 'updated');
        });

        static::deleting(function ($user) {
            static::blockIfMainUser($user, 'deleted');
        });

        static::saving(function ($user) {
            if ($user->isDirty('is_active')) {
                static::blockIfMainUser($user, 'activated/deactivated');
            }
        });
    }

    protected static function blockIfMainUser($user, string $action): void
    {
        // if ($user->roles()->whereIn('name', static::getMainRoleNames())->exists()) {
        //     throw new MainUserModificationException("This user has a protected role and cannot be {$action}.");
        // }
    }
}
