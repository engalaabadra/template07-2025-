<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use App\Models\Role;
use App\Models\Permission;
use App\Scopes\ActiveScope;
use App\Models\Geocodes\Country;
use Laratrust\Contracts\LaratrustUser;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Profile;
use App\Models\File;
use App\Models\Traits\BaseModelTrait;
use App\Scopes\LanguageScope;
use App\Models\Builders\UserBuilder;
use App\Models\Traits\Relations\Media\HasImageRelationTrait;
use App\Models\Traits\Relations\Media\HasFilesRelationTrait;
use App\Services\ServiceResponse;
use App\Models\Traits\HasMediaTrait;
use App\Models\Traits\ProtectsMainUsers;
use App\Helpers\ReportHelper;

/**
 * Attributes
 * @property int id
 * @property string fcm_token
 * @property string username
 * @property string email
 * @property string password
 * @property string phone_no
 * @property int country_id
 * 
 * Relations
 * @property-read User[] user
 * @property-read Profile[] profile
 * 
 * Methods
 * @property-read deleteRelatedItemsUser
 * @property-read checkLastUserWithRole
 * 
 * Configuration & Metadata
 * @property array $appends                   List of accessors to append to model's array form.
 * @property array $eagerLoading              List of relations to eager load dynamically.
 * @property static array $columnsSearch      Fields used for search functionality.
 * @property static array $columnsToExport    Fields exported to Excel or other formats.
 * @property array $casts                     Attribute casting definitions (e.g., enums, dates).
 * @property static array $mainUsers          Stores default/main user names (e.g., from config).
 * 
 **/
class User extends Authenticatable
{
    use BaseModelTrait, HasRoles, HasApiTokens,  HasFactory, Notifiable,SoftDeletes, ProtectsMainUsers, HasImageRelationTrait, HasFilesRelationTrait ;
    protected $fillable = [
        'id',
        'fcm_token',
        'email',
        'password',
        'phone_no',
        'country_id',
        'email_verified_at',
        'phone_verified_at',
        'is_active'
    ];
    protected $appends = [];

    public array $eagerLoading = ['profile', 'country', 'roles', 'image'];
    public static $columnsSearch = ['email', 'phone_no', 'country_id', 'profile.full_name', 'profile.nick_name'];//fields for search
    public static $columnsToExport = ['email', 'phone_no', 'country_id', 'profile.full_name', 'profile.nick_name'];//fields for export

    // List of main user names (e.g., Admin, Super Admin) usually loaded from config at boot time
    public static array $mainUsers = [];

    // Relations to be force deleted with the model
    protected array $forceCascadeDelete = ['profile', 'files', 'image', 'translations'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];
 
    protected $casts = [
'email_verified_at' => 'datetime',// Automatically converts to datetime
            'password' => 'hashed',// Automatically store password hashed
           //'is_active' => \App\Enums\IsActiveEnum::class,
               ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // protected function casts(): array
    // {
    //     return [
    //         'email_verified_at' => 'datetime',// Automatically converts to datetime
    //         'password' => 'hashed',// Automatically store password hashed
    //        //'is_active' => \App\Enums\IsActiveEnum::class,

    //     ];
    // }

    /*** Relations ***/

    /**
     * Get the profile associated with the user.
     *
     * Defines a one-to-one relationship between the User and Profile models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get the country that the user belongs to.
     *
     * Defines an inverse one-to-many (belongsTo) relationship between the User and Country models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }


    /*** Methods ***/
    

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
    // public static function getProtectedUserIds(int|array|null $checkIds = null): array|ServiceResponse
    // {
    //     // Get all superadmins
    //     $superAdminIds = self::whereHas('roles', function ($query) {
    //         $query->where('name', 'superadmin');
    //     })->withTrashed()->withoutGlobalScopes()->pluck('id')->toArray();

    //     // Get first admin (based on role)
    //     $firstAdminId = self::whereHas('roles', function ($query) {
    //         $query->where('name', 'admin');
    //     })->withTrashed()->withoutGlobalScopes()->orderBy('id')->value('id');

    //     // Get first user (based on role)
    //     $firstUserId = self::whereHas('roles', function ($query) {
    //         $query->where('name', 'user');
    //     })->withTrashed()->withoutGlobalScopes()->orderBy('id')->value('id');

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
                    //throw new ApiResponseException(ServiceResponseEnum::BAD_REQUEST, trans('messages.This user is protected and cannot be modified') . ' (IDs: ' . implode(', ', $conflicted) . ')');
                
    //         }
    //     }

    //     return $protectedIds;
    // }
    
    // public static function getReportConfig(string $type): array
    // {
    //     return match ($type) {
    //         'by_role' => [
    //             'relation' => 'roles',
    //             'raw' => 'roles.name as role_name, COUNT(users.id) as users_count',
    //             'groupBy' => ['roles.name'],
    //         ],
    //         'by_active' => [
    //             'raw' => 'is_active, COUNT(id) as users_count',
    //             'groupBy' => ['is_active'],
    //         ],
    //         // Number of users by creation date (daily)
    //         'by_day' => [
    //             'raw' => 'DATE(created_at) as user_date, COUNT(id) as users_count',
    //             'groupBy' => ['user_date'],
    //         ],
    //         default => [
    //             'raw' => 'COUNT(id) as users_count',
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
        if ($type === 'by_role') {
            return [

                // Raw SQL: join roles and count users under each role
                'raw' => 'roles.name as role_name, COUNT(users.id) as users_count',

                // Group results by role name
                'groupBy' => ['roles.name'],

                // Define required joins to connect users with roles
                'join' => [
                    ['model_has_roles', 'model_has_roles.model_id', '=', 'users.id'], // Join model_has_roles to users
                    ['roles', 'roles.id', '=', 'model_has_roles.role_id'], // Join roles to model_has_roles
                ],

                // Add a condition to the join to limit to current model type
                'joinConditions' => fn($query) => $query->where('model_has_roles.model_type', self::class),
            ];
        }

        // If the requested type exists in common reports, return it
        if (isset($commonReports[$type])) {
            return $commonReports[$type];
        }

        // Default configuration: simple count without any grouping
        return [
            'raw' => 'COUNT(id) as ' . $model_count, // Raw SQL to count total records
            'groupBy' => [], // No grouping applied
        ];
    }



    // public static function getReportConfig($model, string $type): array
    // {
    //     // جلب التقارير العامة من الدالة الأصلية في الترايت
    //     $baseReports = parent::getReportConfig($model, $type);
    //     // إذا كان التقرير المطلوب هو by_role، رجّع التقرير الخاص
    //     // if ($type === 'by_role') {
    //     //     return [
    //     //         'raw' => 'roles.name as role_name, COUNT(users.id) as users_count',
    //     //         'groupBy' => ['roles.name'],
    //     //         'join' => [
    //     //             ['model_has_roles', 'model_has_roles.model_id', '=', 'users.id'],
    //     //             ['roles', 'roles.id', '=', 'model_has_roles.role_id'],
    //     //         ],
    //     //         'joinConditions' => fn($query) => $query->where('model_has_roles.model_type', self::class),
    //     //     ];
    //     // }

    //     // إذا التقرير موجود بالـ baseReports (مثل by_active أو by_day)
    //     if (in_array($type, ['by_active', 'by_day'])) {
    //         return $baseReports;
    //     }
    //     dd(8);

    //     // إذا مش موجود، رجّع الـ default
    //     return [
    //         'raw' => 'COUNT(id) as users_count',
    //         'groupBy' => [],
    //     ];
    // }




    // public static function getReportConfig($model, string $type = null): array
    // {
    //     $model_count = modelName($model) . '_count';

    //     return match ($type) {

    //         'by_role' => [
    //             'raw' => 'roles.name as role_name, COUNT(users.id) as users_count',
    //             'groupBy' => ['roles.name'],
    //             'join' => [
    //                 ['model_has_roles', 'model_has_roles.model_id', '=', 'users.id'],
    //                 ['roles', 'roles.id', '=', 'model_has_roles.role_id'],
    //             ],
    //             'joinConditions' => fn($query) => $query->where('model_has_roles.model_type', User::class),
    //         ],
    //         // Default report configuration
    //         default => [
    //             'raw' => 'COUNT(id) as '. $model_count,
    //             'groupBy' => [],
    //         ],
    //     };
    // }


    // public static function getReportConfig(string $countAlias = 'records_count'): array
    // {
        
    //     // Get default reports from the trait
    //     $defaultReports = parent::getCommonReports($countAlias);

    //     // Override or add specific reports
    //     $defaultReports['by_active'] = [
    //         'raw'     => "is_active, COUNT(id) as users_count",
    //         'groupBy' => ['is_active'],
    //     ];
    //     $defaultReports['by_role'] = [
    //         'raw' => 'roles.name as role_name, COUNT(users.id) as users_count',
    //         'groupBy' => ['roles.name'],
    //         'join' => [
    //             ['model_has_roles', 'model_has_roles.model_id', '=', 'users.id'],
    //             ['roles', 'roles.id', '=', 'model_has_roles.role_id'],
    //         ],
    //         'joinConditions' => fn($query) => $query->where('model_has_roles.model_type', User::class),
    //     ];

    //     return $defaultReports;
    // }

    /**
     * Retrieve a merged list of eager load relationships from both the user and their profile.
     *
     * This is useful when you want to load all relations defined in both the User model
     * and the related Profile model using eager loading in a single query.
     *
     * @return array The combined list of relationship names to eager load.
     */
    public function getEagerLoadingUserProfile()
    {
        $eagerLoadingUser = $this->getEagerLoading(); 
        $eagerLoadingProfile = $this?->profile?->getEagerLoading();

        return array_merge($eagerLoadingUser, $eagerLoadingProfile);
    }

    /**
     * Check if the current user is the last one assigned to a specific role.
     *
     * This can be used to prevent removing the last user from a critical role (e.g., Admin).
     *
     * @param int $roleId The ID of the role to check.
     * @return bool True if this is the last user with the given role, false otherwise.
     */
    public function checkLastUserWithRole($roleId)
    {
        // Count the number of users with the given role
        $userCountWithRole = \DB::table('model_has_roles')
                                ->where('role_id', $roleId)
                                ->where('model_type', $this)
                                ->count();

        // Return true if this is the last user with that role
        return $userCountWithRole <= 1;
    }

    /**
     * @return UserBuilder
     */
    public static function query(): UserBuilder
    {
        return parent::query();
    }

    /**
     * @param $query
     * @return UserBuilder
     */
    public function newEloquentBuilder($query): UserBuilder
    {
        return new UserBuilder($query, $this);
    }
}
