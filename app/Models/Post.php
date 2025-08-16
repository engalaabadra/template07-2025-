<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Attributes
 * @property int id
 * @property string lang
 * @property int translate_id
 * @property boolean is_active
 *
 * Accessors
 * @property-read string|null $_text
 *
 * Relations
 * @property-read
 * 
 * Methods
 * @method static PostBuilder query()
 * @method PostBuilder newEloquentBuilder($query)
 *
 * Configuration & Metadata
 * @property array $appends                   List of accessors to append to model's array form.
 * @property array $eagerLoading              List of relations to eager load dynamically.
 * @property static array $excludedFields     Fields not requiring translation during insert.
 * @property static array $columnsSearch      Fields used for search functionality.
 * @property static array $columnsToExport    Fields exported to Excel or other formats.
 * @property array $casts                     Attribute casting definitions (e.g., enums, dates).
 **/
class Post extends BaseModel
{
    use SoftDeletes;
    //

    /** Configuration & Metadata */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'body',
        'is_active'
    ];
    // Accessors that should be appended to the model's array and JSON form
    protected $appends = [];

    // List of relationships to eager load dynamically when needed
    public array $eagerLoading = [];

    // Fields that are excluded from translation when inserting a new record
    public static $excludedFields = [];

    // Fields used for search functionality (e.g., in filtering, search bars, etc.)
    public static $columnsSearch = [];

    // Fields to include when exporting model data (e.g., to Excel or CSV)
    public static $columnsToExport = [];

    // Relations to be force deleted with the model
    protected array $forceCascadeDelete = [];

    // Cast definitions for model attributes (e.g., enum, date, boolean, etc.)
    protected $casts = [

    ];
 // fields for restore
    public static $uniqueFields = ['name', 'guard_name'];
    /** Relations */


    /** Accessors */

    /** Methods */

    /**
     * @return PostBuilder
     */
    public static function query(): PostBuilder
    {
        return parent::query();
    }

    /**
     * @param $query
     * @return PostBuilder
     */
    public function newEloquentBuilder($query): PostBuilder
    {
        return new PostBuilder($query, $this);
    }
}
