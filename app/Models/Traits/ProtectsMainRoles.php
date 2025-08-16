<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Config;
use App\Exceptions\MainRoleModificationException;
use App\Models\Traits\LoadsMainRoles;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\ApiResponseException;
use App\Enums\ServiceResponseEnum;

trait ProtectsMainRoles
{

    protected static function bootProtectsMainRoles(): void
    {
        // Prevent updating main roles
        static::updating(function ($role) {
            static::blockIfMainRole($role, 'updated');
        });

        // Prevent deleting main roles
        static::deleting(function ($role) {
            static::blockIfMainRole($role, 'deleted');
        });

        // Prevent restoring main roles
        static::restoring(function ($role) {
            static::blockIfMainRole($role, 'restored');
        });

        // Prevent activating/deactivating main roles
        static::saving(function ($role) {
            
            if ($role->isDirty('is_active')) {
                static::blockIfMainRole($role, 'activated/deactivated');
            }
        });
    }

     /**
     * Abort the action if the role is protected.
     *
     * @param \App\Models\Role $role
     * @param string $action
     * @return void
     */
    protected static function blockIfMainRole($role, string $action): void
    {
        if (in_array($role->name, static::getMainRoleNames())) {
            throw new MainRoleModificationException("This role is protected and cannot be {$action}.");
        }
    }

    /**
     * Load the main role names from the config (once).
     */
    protected static function getMainRoleNames(): array
    {
        if (empty(static::$mainRoleNames)) {
            static::$mainRoleNames = Cache::get('main_roles_config', array_keys(Config::get('spatie_seeder.roles_structure', [])));
        }

        return static::$mainRoleNames;
    }

    /**
     * Load the IDs of the main roles by querying the database.
     */
    public static function getmainRolesIds(): array
    {
        if (empty(static::$mainRolesIds)) {
            $names = static::getMainRoleNames();
            static::$mainRolesIds = self::withTrashed()
                ->whereIn('name', $names)
                ->pluck('id')
                ->toArray();
        }
        return static::$mainRolesIds;
    }

    /**
     * Find the role by ID with ignore protected main roles
     */
    public function findRoleExceptMain($id, $model)
    {
        $role = $model->whereNotIn('id', static::$mainRolesIds)->where('id', $id)->first();

        return $role ?? throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);

    }
    /**
     * Find the role by ID with ignore protected main roles
     */
    public function findRoleExceptMainTrash($id, $model)
    {
        $role = $model->OnlyTrashed()->whereNotIn('id', static::$mainRolesIds)->where('id', $id)->first();

        return $role ?? throw new ApiResponseException(ServiceResponseEnum::NOT_FOUND);

    }
}
