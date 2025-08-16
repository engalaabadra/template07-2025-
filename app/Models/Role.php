<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use App\Models\BaseModel; // Assuming this extends Model
use App\Models\User;
use App\Models\Traits\Relations\TranslationRelations;
use App\Models\Builders\RoleBuilder;
use App\Services\ServiceResponse;
use App\Exceptions\MainRoleModificationException;
use App\Models\Traits\ProtectsMainRoles;
use App\Helpers\ReportHelper;
use App\Enums\ServiceResponseEnum;
use App\Exceptions\ApiResponseException;

/**
 * Attributes
 * @property int id
 * @property string lang
 * @property int translate_id
 * @property string guard_name
 * @property string name
 * @property string display_name
 * @property boolean is_active
 * 
 * Accessors
 * @property-read string|null $_text
 *
 * Relations
 * @property-read User[] users
 * @property-read Permission[] Permissions
 *
 * Methods
 * @method static RoleBuilder query()
 * @method RoleBuilder newEloquentBuilder($query) 
 * @method bool rolesToCheck
 * 
 * Configuration & Metadata
 * @property array $appends                   List of accessors to append to model's array form.
 * @property array $eagerLoading              List of relations to eager load dynamically.
 * @property static array $excludedFields     Fields not requiring translation during insert.
 * @property static array $translationFields  Translatable fields used in validation.
 * @property static array $columnsSearch      Fields used for search functionality.
 * @property static array $columnsToExport    Fields exported to Excel or other formats.
 * @property array $casts                     Attribute casting definitions (e.g., enums, dates).
 * @property static array $mainRoles          Stores default/main role names (e.g., from config).
 * 
 **/
class Role extends BaseModel
{
    use SoftDeletes,  TranslationRelations, ProtectsMainRoles;

    /** Configuration & Metadata */

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $fillable = [
        'id',
        "lang",
        "translate_id",
        'guard_name',
        'name',
        'display_name',
        'is_active'
    ];

    // Accessors that should be appended to the model's array and JSON form
    protected $appends = [];

    // List of relationships to eager load dynamically when needed
    public array $eagerLoading = ['permissions'];

    // Fields that are excluded from translation when inserting a new record
    public static $excludedFields = [];

    // Fields that are translatable; used for adding dynamic validation rules for translations in form requests
    public static $translationFields = ['guard_name', 'name', 'display_name'];

    //field to use in dynamicTranslationRules to validation nullable or required in fields translations
    public static $requiredFields = ['name', 'display_name'];
    
    // Fields used for search functionality (e.g., in filtering, search bars, etc.)
    public static $columnsSearch = ['guard_name', 'name', 'display_name'];

    // Fields to include when exporting model data (e.g., to Excel or CSV)
    public static $columnsToExport = ['guard_name', 'name', 'display_name'];

    // fields for restore
    public static $uniqueFields = ['name', 'guard_name'];
    // Cast definitions for model attributes (e.g., enum, date, boolean, etc.)
    protected $casts = [
        'is_active' => \App\Enums\IsActiveEnum::class,
    ];


     // Relations to be force deleted with the model
    protected array $forceCascadeDelete = ['users', 'permissions', 'translations'];
    
    protected static $mainRoleNames = [];
    protected static $mainRolesIds = [];

    /** Relations */

    /**
     * Get the users that are assigned to this role.
     *
     * Defines a many-to-many relationship using the `model_has_roles` pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'model_id');
    }

    /**
     * Get the permissions that belong to this role.
     *
     * Defines a many-to-many relationship using the `role_has_permissions` pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id');
    }

    /** Methods */

        /**
     * Get the name of the main role from cache.
     * Falls back to config if cache is not set.
     *
     * @return string|null
     */
    public static function getMainRoleName(): ?string
    {
        return Cache::get('main_role_name', Config::get('spatie_seeder.main_role'));
    }

    /**
     * Get all main roles (from roles_structure config).
     * Used to identify top-level roles like admin, super_admin, etc.
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
     * @param string $role
     * @return bool
     */
    public static function isMainRole(string $role): bool
    {
        return $role === self::getMainRoleName();
    }
    
    /**
     * The "booting" method of the model.
     *
     * This method is automatically called when the model is initialized.
     * It loads the list of main roles from the configuration file `spatie_seeder.roles_structure`
     * and stores their names in the static `$mainRoles` property for later use.
     *
     * @return void
     */

    protected static function booted(): void
    {
        // When a role is created
        static::created(function () {
            // Invalidate the daily verification cache
            cache()->forget('roles_verified_today');
        });

        // When a role is updated
        static::updated(function () {
            // Invalidate the daily verification cache
            cache()->forget('roles_verified_today');
        });

        // When a role is deleted
        static::deleted(function () {
            // Invalidate the daily verification cache
            cache()->forget('roles_verified_today');
        });

        // Optional: When a role is restored (if using soft deletes)
        static::restored(function () {
            cache()->forget('roles_verified_today');
        });

        // Optional: When a role is permanently deleted
        static::forceDeleted(function () {
            cache()->forget('roles_verified_today');
        });
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     self::$mainRoles = array_keys(Config::get('spatie_seeder.roles_structure', []));

    //     static::updating(function ($role) {
    //         static::blockIfMainRole($role, 'updated');
    //     });

    //     static::deleting(function ($role) {
    //         static::blockIfMainRole($role, 'deleted');
    //     });

    //     static::restoring(function ($role) {
    //         static::blockIfMainRole($role, 'restored');
    //     });

    //     static::saving(function ($role) {
    //         //when activate, deactivate
    //         if ($role->isDirty('is_active')) {
    //             static::blockIfMainRole($role, 'activated/deactivated');
    //         }
    //     });
    // }

    // /**
    //  * Abort the action if the role is protected.
    //  *
    //  * @param \App\Models\Role $role
    //  * @param string $action
    //  * @return void
    //  */
    // protected static function blockIfMainRole($role, string $action): void
    // {
    //     if (in_array($role->name, static::$mainRoles)) {
    //         throw new MainRoleModificationException("This role is protected and cannot be {$action}.");
    //     }
    // }

    /**
     * check this role -> to avoid this action if this role exist in main roles for my website.
     * 
     * @return boolean
     */
    public function rolesToCheck(){
        $rolesToCheck = array_keys(Config::get('spatie_seeder.roles_structure'));
        if (in_array($this->name, $rolesToCheck)) {
            $roleCount = $this->where('name', $this->name)->withTrashed()->count();
            if ($roleCount == 1) return true;
        }
    }

    public function findAccessibleRole($role){
        if (in_array($role->name, self::$mainRoles)) 
        {
            throw new ApiResponseException(ServiceResponseEnum::FORBIDDEN);    
        }
    }


    /**
     * Get the list of protected user IDs (superadmins, first admin, first user),
     * These IDs can be used to prevent critical actions (like delete or deactivate) 
     * on system-critical users.
     * and optionally validate a given list of IDs against them.
     *
     * @param  int|array|null  $checkIds  Optional ID(s) to validate. If any exist in protected list, throws error.
     * @return array|ServiceResponse The list of protected user IDs .
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // public static function getProtectedRolesIds(int|array|null $checkIds = null): array|ServiceResponse
    // {
    //     return static::whereIn('name', static::$mainRoles)
    //         ->withoutGlobalScopes()
    //         ->pluck('id')
    //         ->toArray();

    //     // Get all superadmins
    //     $superAdminIds = self::where('name', 'superadmin')->withTrashed()->withoutGlobalScopes()->pluck('id')->toArray();

    //     // Get first admin (based on role)
    //     $firstAdminId = self::where('name', 'admin')->withTrashed()->withoutGlobalScopes()->orderBy('id')->value('id');

    //     // Get first user (based on role)
    //     $firstUserId = self::where('name', 'user')->withTrashed()->withoutGlobalScopes()->orderBy('id')->value('id');

    //     // Merge and clean list
    //     $protectedIds = array_filter(array_merge(
    //         $superAdminIds,
    //         [$firstAdminId, $firstUserId]
    //     ));

    //     // If we're checking specific IDs
    //     if ($checkIds !== null) {
    //         $checkIds = (array) $checkIds;

    //         $conflicted = array_intersect($checkIds, $protectedIds);

    //         if (!empty($conflicted)) {
            //throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.This role is protected and cannot be modified') . ' (IDs: ' . implode(', ', $conflicted) );

                
    //         }
    //     }

    //     return $protectedIds;
    // }


    public static function checkProtectedRolesIds(array|int|null $checkIds = null): array|ServiceResponse
    {
        if (is_null($checkIds)) {
            return [];
        }

        $checkIds = (array) $checkIds;

        $protectedIds = static::whereIn('name', static::$mainRoles)
            ->whereIn('id', $checkIds)
            ->withoutGlobalScopes()
            ->pluck('id')
            ->toArray();

        if (!empty($protectedIds)) {
           // throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.This role is protected and cannot be modified') . ' (IDs: ' . implode(', ', $protectedIds) . ')');

        }

        return [];
    }


    


    // public static function getReportConfig(string $type): array
    // {
    //     $shared = static::getCommonReports('roles_count');

    //     return match ($type) {
    //         // users count for every role
    //         // 'users_count_per_role' => [
    //         //     'relation' => 'users',
    //         //     'raw' => 'roles.name as role_name, COUNT(users.id) as users_count',
    //         //     'groupBy' => ['roles.name'],
    //         // ],
    //         'users_count_per_role' => [

               
    //              'raw' => 'roles.name as role_name, COUNT(users.id) as users_count',
    //                 'from' => 'roles',
                
    //              'join' => [
    //                 ['model_has_roles', 'roles.id', '=', 'model_has_roles.role_id'],
    //                 ['users', 'users.id', '=', 'model_has_roles.model_id'],
    //             ],
    //             'where' => [
    //                 ['model_has_roles.model_type', '=', \App\Models\User::class],
    //             ],
    //             'groupBy' => ['roles.name'],
    //         ],
    //         // Default report configuration
    //         default => [
    //             'raw' => 'COUNT(id) as roles_count',
    //             'groupBy' => [],
    //         ],
  
            
    //     };
    // }

    /**
     * Get report configuration for a specific model and report type.
     *
     * @param mixed $model The model instance or class name for which the report is being generated.
     * @param string $type The type of report required (e.g., 'by_active', 'by_day', 'by_role').
     * @return array The configuration for the requested report type.
     */
    public static function getReportConfig($model, string $type): array
    {
        // Generate the count alias based on the model name (e.g., 'user_count')
        $model_count = modelName($model) . '_count';

        // Get the shared report configurations (like 'by_active', 'by_day')
        $commonReports = ReportHelper::getCommonReports($model_count);

        // Custom configuration for report type: 'by_role'
        if ($type === 'users_count_per_role') {
            return [
                'raw' => 'roles.name as role_name, COUNT(users.id) as users_count',
                'from' => 'roles',
                'join' => [
                ['model_has_roles', 'roles.id', '=', 'model_has_roles.role_id'],
                ['users', 'users.id', '=', 'model_has_roles.model_id'],
                ],
                'where' => [
                    ['model_has_roles.model_type', '=', \App\Models\User::class],
                ],
                'groupBy' => ['roles.name'],
            ];
        }

        // If the requested type exists in common reports, return it
        if (isset($commonReports[$type])) {
            return $commonReports[$type];
        }
        return [
            'raw' => 'COUNT(id) as ' . $model_count, // Raw SQL to count total records
            'groupBy' => [], // No grouping applied
        ];
    }


    /**
     * @return RoleBuilder
     */
    public static function query(): RoleBuilder
    {
        return parent::query();
    }

    /**
     * @param $query
     * @return RoleBuilder
     */
    public function newEloquentBuilder($query): RoleBuilder
    {
        return new RoleBuilder($query, $this);
    }

}
