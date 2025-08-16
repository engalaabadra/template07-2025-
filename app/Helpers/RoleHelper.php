<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class RoleHelper
{
    /**
     * Get the main role name from cache or config.
     *
     * Example:
     * ```php
     * $mainRole = RoleHelper::getMainRoleName(); // e.g. "admin"
     * ```
     *
     * @return string|null
     */
    public static function getMainRoleName(): ?string
    {
        return Cache::get('main_role_name', Config::get('spatie_seeder.main_role'));
    }

    /**
     * Get all main roles from cache or config.
     *
     * Example:
     * ```php
     * $roles = RoleHelper::getMainRoles(); // e.g. ["admin", "super_admin"]
     * ```
     *
     * @return array
     */
    public static function getMainRoles(): array
    {
        return Cache::get('main_roles_config', array_keys(Config::get('spatie_seeder.roles_structure', [])));
    }

    /**
     * Check if a given role is the main role.
     *
     * Example:
     * ```php
     * if (RoleHelper::isMainRole('admin')) {
     *     // true if 'admin' is the main role
     * }
     * ```
     *
     * @param string $role
     * @return bool
     */
    public static function isMainRole(string $role): bool
    {
        return $role === self::getMainRoleName();
    }
}
