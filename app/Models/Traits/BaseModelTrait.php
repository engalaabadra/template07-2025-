<?php

namespace App\Models\Traits;

use App\Models\Traits\PaginatableTrait;
use App\Models\Traits\EnumSupportTrait;
use App\Models\Traits\Accessors\ModelDateTextTrait;
use App\Models\Traits\MorphModelTriggerTrait;
use App\Models\Traits\ModelRemoveAttributesTrait;
use App\Models\Traits\HasGeneralAttributeAndScopes;
use App\Models\Traits\EagerLoadingTrait;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Accessors\AutoEnumCastTrait;
use App\Models\Traits\ReportableTrait;
use App\Models\Traits\RestoresSoftDeletedModelTrait;
use App\Models\Traits\OverridesQueryTrait;
use App\Models\Traits\ForceCascadeDeleteTrait;

/**
 * Trait BaseModelTrait
 *
 * Combines a powerful set of reusable traits that enhance Laravel Eloquent models with
 * commonly used features and application-wide behaviors.
 *
 * Recommended for all base models via inheritance from a `BaseModel`.
 *
 * Included Traits:
 * ----------------
 *
 * @mixin \App\Models\Traits\EnumSupportTrait
 * @mixin \App\Models\Traits\ModelDateTextTrait
 * @mixin \App\Models\Traits\MorphModelTriggerTrait
 * @mixin \App\Models\Traits\ModelRemoveAttributesTrait
 * @mixin \App\Models\Traits\HasGeneralAttributeAndScopes
 * @mixin \App\Models\Traits\EagerLoadingTrait
 * @mixin \App\Models\Traits\AutoEnumCastTrait
 *
 * Breakdown:
 * ----------
 *
 * EnumSupportTrait
 *  - Specifically handles enum-related operations.
 *  - Combines:
 *  -- AutoEnumCastTrait – Automatically put general attributes casts (is_active) in casts array the model.
 *  -- EnumTextAccessorsTrait – Adds _text accessors and manages appends.
 *      - contains (2 methods : getArrayableAppends -> any attribute in casts the model will put in appends the mode to show with json, getAttribute -> to get value any attribute ends _text from casts enum class via casts array the model)
 *  -- EnumOptionsTrait – Provides utilities for retrieving enum option lists.


 * ModelDateTextTrait
 *   - Adds formatted accessors like `created_at_text` and `updated_at_text`.
 *
 * MorphModelTriggerTrait
 *   - Handles polymorphic callbacks, useful for logging or media relations.
 *
 * ModelRemoveAttributesTrait
 *   - Automatically hides defined attributes during serialization (e.g. `access_token`, `pivot`).
 *
 * HasGeneralAttributeAndScopes
 *   - Provides shared scopes like `->active()`, `->inactive()`, and `->whereLang()`.
 *   - Adds a global scope for language filtering if enabled.
 *
 * EagerLoadingTrait
 *   - Allows dynamic eager loading of relationships defined in a `$withOnIndex` property.
 *
*/

trait BaseModelTrait
{
    use EnumSupportTrait;
    use ModelDateTextTrait;
    use MorphModelTriggerTrait;
    use ModelRemoveAttributesTrait;
    use HasGeneralAttributeAndScopes;
    use EagerLoadingTrait;
    use HasModelPropertyValidation;
    use ReportableTrait;
    use RestoresSoftDeletedModelTrait;
    use ForceCascadeDeleteTrait;
}
