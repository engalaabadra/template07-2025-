<?php
namespace App\Models\Traits;

use App\Models\Traits\Accessors\EnumTextAccessorsTrait;
use App\Models\Traits\Accessors\AutoEnumCastTrait;
use App\Models\Traits\EnumOptionsTrait;

/**
 * Trait EnumSupportTrait
 *
 * Centralized trait for working with Enum-related logic in Eloquent models.
 *
 * This trait combines multiple helper traits to simplify handling of enum fields:
 *
 * - **AutoEnumCastTrait**
 * THE FIRST : put general attributes casts (is_active) in casts array the model
 *   - Dynamically applies enum casts to models that contain certain columns like `is_active`.
 *   - Uses schema caching for better performance in large systems.
 *
 * - **EnumTextAccessorsTrait**  
 * THE SECOND :contains (2 methods : getArrayableAppends -> any attribute in casts the model will put in appends the mode to show with json, getAttribute -> to get value any attribute ends _text from casts enum class via casts array the model)
 *   Provides dynamic accessors like `status_text` or `gender_text`, automatically returning
 *   a human-readable translation of enum values based on enums defined in the model.
 *
 * - **EnumOptionsTrait**  
 *   Provides utility methods like `getEnumOptions($field)` to retrieve enum options,
 *   which can be used for form dropdowns or filters.
 *
 *
 * Intended to be included in models that utilize Enums heavily for clarity, localization,
 * and front-end rendering support.
 *
 * @package App\Models\Traits
 */

trait EnumSupportTrait
{
    use EnumOptionsTrait;
    use AutoEnumCastTrait;
    use EnumTextAccessorsTrait;
}
