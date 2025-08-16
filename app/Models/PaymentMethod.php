<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use App\Models\Builders\PaymentBuilder;
use App\Models\Traits\Relations\TranslationRelations;
 
/**
 * Attributes
 * @property int id
 * @property string lang
 * @property int translate_id
 * @property string name
 * @property boolean is_active
 * 
 * Relations
 * @property-read
 * 
 * Accessors
 * @property-read string|null $_text
 *
 * Methods
 * @method static PaymentBuilder query()
 * @method PaymentBuilder newEloquentBuilder($query)
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

class PaymentMethod extends BaseModel
{
    use  SoftDeletes, TranslationRelations;
        
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
        'name',
        'is_active'
    ];
    // Accessors that should be appended to the model's array and JSON form
    protected $appends = [];

    // List of relationships to eager load dynamically when needed
    public array $eagerLoading = ['file'];

    // Fields that are excluded from translation when inserting a new record
    public static $excludedFields = [];

    // Fields that are translatable; used for adding dynamic validation rules for translations in form requests
    public static $translationFields = ['name'];

    //field to use in dynamicTranslationRules to validation nullable or required in fields translations
    public static $requiredFields = ['name'];
    
    // Fields used for search functionality (e.g., in filtering, search bars, etc.)
    public static $columnsSearch = ['name'];

    // Fields to include when exporting model data (e.g., to Excel or CSV)
    public static $columnsToExport = ['name'];

    // Relations to be force deleted with the model
    protected array $forceCascadeDelete = ['translations'];

    // Cast definitions for model attributes (e.g., enum, date, boolean, etc.)
    protected $casts = [
        'is_active' => \App\Enums\IsActiveEnum::class,
        'payment_response' => 'json'
        
    ];
   
  /**
     * @return PaymentBuilder
     */
    public static function query(): PaymentBuilder
    {
        return parent::query();
    }

    /**
     * @param $query
     * @return PaymentBuilder
     */
    public function newEloquentBuilder($query): PaymentBuilder
    {
        return new PaymentBuilder($query, $this);
    }
}
