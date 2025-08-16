<?php

namespace App\Models;

use App\Scopes\LanguageScope;
use App\Models\Traits\Relations\TranslationRelations;
use Illuminate\Database\Eloquent\Model;
use App\Enums\UserGender;
use Carbon\Carbon;
use App\Enums\ProfileGenderEnum;
use App\Models\BaseModel;
use App\Models\Builders\ProfileBuilder;
use App\Models\Traits\HasMediaTrait;

/**
 * Attributes
 * @property int id
 * @property string lang
 * @property int translate_id
 * @property int user_id
 * @property string username
 * @property string full_name
 * @property string nick_name
 * @property string address
 * @property string contact_details
 * @property ProfileGenderEnum gender
 * @property-read string gender_text
 * @property Carbon birth_date
 * 
 * Accessors
 * @property-read string|null $_text
 *
 * Relations
 * @property-read
 * 
 * Methods
 * @method static ProfileBuilder query()
 * @method ProfileBuilder newEloquentBuilder($query) 
 * 
 * Configuration & Metadata
 * @property array $appends                   List of accessors to append to model's array form.
 * @property array $eagerLoading              List of relations to eager load dynamically.
 * @property static array $excludedFields     Fields not requiring translation during insert.
 * @property static array $translationFields  Translatable fields used in validation.
 * @property static array $columnsSearch      Fields used for search functionality.
 * @property static array $columnsToExport    Fields exported to Excel or other formats.
 * @property array $casts                     Attribute casting definitions (e.g., enums, dates).
 * 
 **/

class Profile extends BaseModel
{
    use TranslationRelations, HasMediaTrait;
        
    /** Configuration & Metadata */

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'lang',
        'translate_id',
        'user_id',
        'username',
        'full_name',
        'nick_name',
        'address',
        'contact_details',
        'gender',
        'birth_date'
    ];
    // Accessors that should be appended to the model's array and JSON form
    protected $appends = [];

    // List of relationships to eager load dynamically when needed
    public array $eagerLoading = ['user.profile', 'translations'];

    // Fields that are excluded from translation when inserting a new record
    public static $excludedFields = ['gender', 'birth_date', 'hire_date']; // these fields to insert data that dont need translation for a record

    // Fields that are translatable; used for adding dynamic validation rules for translations in form requests
    public static $translationFields = ['username', 'full_name', 'nick_name', 'address']; // these fields to use it in request file to Add dynamic validation rules for each field in the translations in request

    //field to use in dynamicTranslationRules to validation nullable or required in fields translations
    public static $requiredFields = ['username'];
    
    // Fields used for search functionality (e.g., in filtering, search bars, etc.)
    public static $columnsSearch = ['username', 'full_name', 'nick_name', 'address', 'birth_date', 'gender'];//fields for search

    // Fields to include when exporting model data (e.g., to Excel or CSV)
    public static $columnsToExport = [];

    // Relations to be force deleted with the model
    protected array $forceCascadeDelete = ['translations'];

    // Cast definitions for model attributes (e.g., enum, date, boolean, etc.)
    protected $casts = [
        'is_active' => \App\Enums\IsActiveEnum::class,
        'gender' => ProfileGenderEnum::class

    ];
    
    /** Relations */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    

    /** Accessors */
    public function getGenderTextAttribute(): string|null
    {
        return $this->gender?->text(); // this returns null if gender is null
    }

    /** Methods */

    /**
     * @return ProfileBuilder
     */
    public static function query(): ProfileBuilder
    {
        return parent::query();
    }

    /**
     * @param $query
     * @return ProfileBuilder
     */
    public function newEloquentBuilder($query): ProfileBuilder
    {
        return new ProfileBuilder($query, $this);
    }

}
