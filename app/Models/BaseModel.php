<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\ActiveScope;
use App\Scopes\LanguageScope;
use App\Models\Traits\BaseModelTrait;
use App\Models\User;
use App\Enums\IsActiveEnum;
use App\GeneralClasses\MediaClass;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

/**
 * Attributes
 * @property-read User $createdBy
 * @property-read User $updatedBy
 * @property-read User $deletedBy
 * @property-read int $created_by_id
 * @property-read string $created_by_type
 * @property-read int $updated_by_id
 * @property-read string $updated_by_type
 * @property-read int $deleted_by_id
 * @property-read string $deleted_by_type
 * 
 *  Accessors
 * @property-read string|null $is_active_text
 *
 * Relations
 * @property-read
 * 
 * Methods
 * @method
 * 
 * Configuration & Metadata
 * @property array $appends                   List of accessors to append to model's array form.
 * @property array $eagerLoading              List of relations to eager load dynamically.
 * @property static array $excludedFields     Fields not requiring translation during insert.
 * @property static array $translationFields  Translatable fields used in validation.
 * @property static array $columnsSearch      Fields used for search functionality.
 * @property static array $columnsToExport    Fields exported to Excel or other formats.
 * @property array $casts                     Attribute casting definitions (e.g., enums, dates).
 */

class BaseModel extends Model
{
    use BaseModelTrait;

    /** Configuration & Metadata */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'created_by_id', 'created_by_type', 'updated_by_id', 'updated_by_type', 'deleted_by_id', 'deleted_by_type',
    ];

    // Accessors that should be appended to the model's array and JSON form
    // protected $appends = ['is_active_text'];
    protected $appends = [];

    // List of relationships to eager load dynamically when needed
    public array $eagerLoading = [];

    // Fields that are excluded from translation when inserting a new record
    public static $excludedFields = [];

    // Fields that are translatable; used for adding dynamic validation rules for translations in form requests
    public static $translationFields = [];

    // Fields used for search functionality (e.g., in filtering, search bars, etc.)
    public static $columnsSearch = [];

    // Fields to include when exporting model data (e.g., to Excel or CSV)
    public static $columnsToExport = [];

    // Cast definitions for model attributes (e.g., enum, date, boolean, etc.)
    protected $casts = [
        'is_active' => IsActiveEnum::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static array $mainRoles = [];

    // public function getCasts()
    // {
    //     return array_merge(parent::getCasts(), $this->casts ?? []);
    // }

    /**
     * Boot the model and define model event listeners.
     *
     * This method sets up a listener for the "deleting" event.
     * If the model is being force deleted (i.e., not soft deleted),
     * it loops through the defined `forceCascadeDelete` relationships on the model
     * and deletes the related models accordingly.
     */
    protected static function booted(): void
    {
        parent::booted();
        // static::addGlobalScope(new ActiveScope);
        // static::addGlobalScope(new LanguageScope);
        // موديلات تريدين تجاهل تطبيق كود force delete فيها
            // $excludedModels = [
            //     \App\Models\SomeModuleModel::class,
            //     \App\Models\AnotherModuleModel::class,
            // ];

        self::$mainRoles = array_keys(Config::get('spatie_seeder.roles_structure', []));
        Cache::put('supported_languages', config('app.supported_languages'));

        // Register a deleting event handler for the model
        static::deleting(function (Model $model) {

            // لو متلا انا بموديول معين لما بدي احدفو دائم ما بدي علاقاته تنحدف
            // ✅ لو الموديل من ضمن المستثنى، تجاهلي
            // if (in_array(get_class($model), $excludedModels)) {
            //     return;
            // }
            // Check if the model supports force deletion and it's being force deleted or this model dont contain softdelete(permanent deleting)
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting() || !isSoftDeletes($model)) {

                $model->handleForceCascadeDelete($model);
            }
        });
    }

}
