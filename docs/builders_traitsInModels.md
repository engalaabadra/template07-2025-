### Builders

#### App\Models\Builders\BaseBuilder
The `BaseBuilder` class extends Laravel's Eloquent `Builder` to add **custom filtering capabilities**.  
It uses dedicated filter classes (`ActiveFilter`, `LangFilter`, `CreatedAtDateRangeFilter`) that apply conditions to the query based on provided input.

##### Features
- **ActiveFilter** – Filter results by the `is_active` status.
- **LangFilter** – Filter results by the `lang` field.
- **CreatedAtDateRangeFilter** – Filter results by a specific `created_at` date range.

***Example***
```
// Filter active users created between 2024-01-01 and 2024-01-31
$users = User::query()
    ->isActive(true)
    ->createdAtRange(['2024-01-01', '2024-01-31'])
    ->get();
```

#### App\Models\Builders\UserBuilder

The `UserBuilder` class is a **custom Eloquent query builder** for the `User` model.  
It extends [`BaseBuilder`] to inherit common filters and uses the `UseFilter` trait to apply dynamic, reusable filter pipelines.

##### Features
- Inherits all filters from `BaseBuilder`:
  - **ActiveFilter** – Filter users by `is_active` status.
  - **CreatedAtDateRangeFilter** – Filter users by a specific creation date range.
- Easily extendable to add user-specific filters.

---------------------------------------
## Traits

### Accessors

#### App\Models\Traits\Accessors\AutoEnumCastTrait

The `AutoEnumCastTrait` is a Laravel model trait that **automatically casts attributes to Enum classes** without manually defining them in `$casts`.  
It checks if a column exists in the table schema and applies the cast dynamically.

##### Key Features
- Automatically applies Enum casting based on the `$autoEnumCasts` array.
- Works dynamically without modifying the `$casts` array in every model.
- Uses cached table column lookups to improve performance.
- Hooks into model lifecycle events (`retrieved`, `creating`, `updating`).

***Example***

### 1. Adding the Trait to a Model
```
class User extends Model
{
    use AutoEnumCastTrait;

    protected array $autoEnumCasts = [
        'is_active' => \App\Enums\IsActiveEnum::class,
    ];
}
```

#### App\Models\Traits\Accessors\AutoTextAccessorsTrait

The `AutoTextAccessorsTrait` is a Laravel trait that **automatically appends `*_text` accessors** for model attributes that use **custom casts** (excluding enums).  
It’s useful for retrieving a human-readable or formatted version of a casted attribute without manually defining accessor methods.

#### App\Models\Traits\Accessors\EnumTextAccessorsTrait
* getAppends() -> adds support for automatically appending `_text` accessors for enum-casted attributes.
 * __get() -> get for any attribute contain (_text) like accessors 
 
 ***example***
  
  if a model has an enum cast for `status`, this trait will expose `status_text` via getAppends() & get value this attr , via accessors __get()
 

 that returns the readable version of the enum (e.g. `Active`, `Inactive`, etc.).

### Summery AutoEnumCastTrait & EnumTextAccessorsTrait

- AutoEnumCastTrait
--- if i need enum class use in all models casts, will put this enum class in this array in this file :
```
protected array $autoEnumCasts = [
        'is_active' => \App\Enums\IsActiveEnum::class,
    ];
```
this will add this automaticlly in every casts arr in models , but sure column this enum class exist in table this model , like 'is_active'

- EnumTextAccessorsTrait
--- getAppends() : these enum casts will adding into it '_text' , like : is_active , will be 'is_active_text' and put it automaticlly in appends arr in every model , in this time became  need to method accessor to call this attr and get value it via __get()

#### App\Models\Traits\Accessors\ModelDateTextTrait
Human-Readable Date Accessors for Laravel Models
# `ModelDateTextTrait` — Human-Readable Date Accessors for Laravel Models

##### Overview
`ModelDateTextTrait` automatically adds and formats `*_text` attributes for your model’s main date fields:

- `created_at_text` → Human-friendly format, shows relative time if less than 24h ago.
- `created_at_text2` → Full date & time format `Y-m-d h:i A`.
- `updated_at_text` → Same formatting logic as `created_at_text`.
- `deleted_at_text` → Same formatting logic as `created_at_text`.

It also automatically translates **AM/PM** according to the current app locale (`ar` or `en` by default).

---

##### Features
- **Auto-appends** date text attributes when the model is retrieved.
- **Adaptive formatting**:
  - `< 24 hours` → `"x hours ago"`
  - `< 7 days` → `"Y-m-d h:i A"`
  - Otherwise → `"Y-m-d"`
- **Locale-aware AM/PM replacement** (e.g., `AM` → `ص` in Arabic).

---

***Example***
```
$post = Post::find(1);

echo $post->created_at_text;  // "3 hours ago"
echo $post->created_at_text2; // "2025-08-01 09:45 AM"
echo $post->updated_at_text;  // "2025-07-30"
echo $post->deleted_at_text;  // "---" if null
```
### Relations

#### App\Models\Traits\Relations\Media
Contain Relations for media -> files() , file() , images() , image()
in : HasFilesRelationTrait, HasFileRelationTrait, HasImagesRelationTrait, HasImageRelationTrait

#### App\Models\Traits\Relations\TranslationRelations

This trait provides **automatic locale-based filtering** and **helper methods** for Eloquent models that use `lang` and `translate_id` fields for translations.

## Features
- **Automatic Global Scope** → Queries automatically filter by the current app locale (`localeLang()`).
- **Identify Translations** → `isTranslation()` to check if a record is a translated version.
- **Relationships**:
  - `original()` → Get the original record for a translation.
  - `translations()` → Get all translations of a record.
- **Query Scopes**:
  - `scopeOriginals()` → Filter only original records.
  - `scopeInLang($locale)` → Fetch records in a specific language.

---

## Example Usage

```php
use App\Models\Post;
use App\Models\Traits\Relations\TranslationRelations;

class Post extends Model
{
    use TranslationRelations;
}

// Example: Fetch posts in current app locale (auto-applied)
$posts = Post::all();

// Example: Get translations of a post
$post = Post::find(1);
$translations = $post->translations;

// Example: Check if a post is a translation
if ($post->isTranslation()) {
    echo "This is a translated version.";
}

// Example: Get only original posts
$originalPosts = Post::originals()->get();

// Example: Fetch posts in French (ignores global scope)
$frenchPosts = Post::inLang('fr')->get();
```

#### App\Models\Traits\BaseModelTrait

The `BaseModelTrait` is a collection of reusable Laravel Eloquent traits that enhance models with **common features** and **application-wide behaviors**.  
It is recommended to be used in base model (e.g., `BaseModel`) so all models can inherit its capabilities.

---

##### Included Traits & Features

- **EnumSupportTrait**
  - Handles enum-related operations.
  - Includes:
    - **AutoEnumCastTrait** → Automatically adds enum casts (e.g., `is_active`) to `$casts`.
    - **EnumTextAccessorsTrait** → Adds `_text` accessors for enums and manages `$appends`.
    - **EnumOptionsTrait** → Provides utilities to retrieve enum option lists.

- **ModelDateTextTrait**
  - Adds formatted date accessors like `created_at_text` and `updated_at_text`.

- **MorphModelTriggerTrait**
  - Handles polymorphic model callbacks (useful for logging or related media actions).

- **ModelRemoveAttributesTrait**
  - Hides specific attributes from JSON/array serialization (e.g., `access_token`, `pivot`).

- **HasGeneralAttributeAndScopes**
  - Common scopes: `active()`, `inactive()`, `whereLang()`.
  - Optional global language filtering.

- **EagerLoadingTrait**
  - Dynamically eager-loads relationships defined in `$withOnIndex`.

- **ReportableTrait**
  - Adds reporting functionality to models.

- **RestoresSoftDeletedModelTrait**
  - Enables restoring of soft-deleted related models.

- **ForceCascadeDeleteTrait**
  - Forces cascade delete on related models.

---

***Example***

```
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BaseModelTrait;

class BaseModel extends Model
{
    use BaseModelTrait;
}

class Post extends BaseModel
{
    // Post model now has all BaseModelTrait features
}
```

#### App\Models\Traits\EagerLoadingTrait

The `EagerLoadingTrait` provides a simple way to **dynamically set and retrieve Eloquent eager-loading relationships** for a model.  
It is useful when you want to control which relations are loaded at runtime, instead of hardcoding them in `$with`.

---

## Features
- **Set eager-loading relations** at runtime.
- **Retrieve currently defined eager-loading relations**.
- Supports **method chaining** for cleaner code.

---

## Methods

| Method | Description | Example |
|--------|-------------|---------|
| `setEagerLoading(array $relations): static` | Sets the list of relationships to eager load. | `$user->setEagerLoading(['files']);` |
| `getEagerLoading(): array` | Retrieves the current list of eager-loaded relationships. | `$user->getEagerLoading(); // ['files']` |

***Explainiation setEagerLoading***
we use setEagerLoading -> when exist relations make more loading -> in this case dont put it in eagerloading this model , we put in eagerloading via setEagerLoading -> put this relation in this case only

we use setEagerLoading . because usually dont need all relations load in eagerLoading in all cases , in some cases we need some relattions , like files relation not need in all times in eagerLoading , or we need in some cases load in eagerloading only files relation in eagerLoading 
in this case we use setEagerLoading to put this relation in eagerLoading instead old eagerloading this model 
so in this route when request it -> will load this eagerloading has been setting
        
such as : $this->chat->setEagerLoading(['files']);

$this->chat->getEagerLoading(); -> became eagerLoading in chat = ['files'] instead ['client.profile','user.profile']

---

## Example Usage

```php
use App\Models\Traits\EagerLoadingTrait;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use EagerLoadingTrait;
}

// Example:
$user = new User();

// Set relations to eager load
$user->setEagerLoading(['profile', 'roles']);

// Retrieve relations
$relations = $user->getEagerLoading(); 
// Output: ['profile', 'roles']
```

#### EnumSupportTrait

The `EnumSupportTrait` is a **centralized helper** for working with PHP Enums inside Laravel Eloquent models.  
It combines multiple enum-related traits to make handling enum fields easier, more readable, and ready for **front-end consumption**.

---

#### Includes

### 1. **AutoEnumCastTrait**
- Automatically applies enum casts for specific model attributes (e.g., `is_active`, `status`, `gender`).
- Uses **schema caching** for better performance in large applications.
- Requires adding enum casts in the model's `$casts` property.

---

### 2. **EnumTextAccessorsTrait**
- Provides automatic *accessors* for enum values, such as:
  - `status_text` → Returns translated name of the `status` enum.
  - `gender_text` → Returns translated name of the `gender` enum.
- Dynamically adds these accessors to the model's `$appends` array for JSON responses.
- Works with enums defined in the `$casts` array.

---

### 3. **EnumOptionsTrait**
- Helper methods for **dropdowns** and **filters**:
The `EnumOptionsTrait` provides a set of helper methods for working with **PHP Enums**,  
especially when generating **UI dropdowns**, handling **translations**

used to transform language codes into collections with IDs, codes, and translated names.
---

## Features
- Generate **translated** enum options for dropdowns and APIs.
---

## Example Usage

```php
use App\Traits\EnumOptionsTrait;

$langs = ['ar', 'en'];
$options = YourClass::getLangOptions($langs);

// Result:
// [
//   ['id' => 1, 'code' => 'ar', 'name' => 'العربية'],
//   ['id' => 2, 'code' => 'en', 'name' => 'English']
// ]

```

#### ForceCascadeDeleteTrait

Automatically deletes related records when deleting a model.  
You define in your model a property `$forceCascadeDelete` with an array of relationship method names to cascade delete or detach.

## Usage Example

```php
class Post extends Model {
    use ForceCascadeDeleteTrait;

    protected array $forceCascadeDelete = ['comments', 'tags'];

    public function comments() {
        return $this->hasMany(Comment::class);
    }

    public function tags() {
        return $this->belongsToMany(Tag::class);
    }
}

$post->handleForceCascadeDelete($post);
// This deletes all comments and detaches tags related to the post
```

#### App\Models\Traits\HasGeneralAttributeAndScopes

Automatically applies global scopes to Eloquent models based on the existence of specific columns in the database table.

## Features

- **ActiveScope**:  
  Automatically applied if the model's table has an `is_active` column, filtering records by active status.  
  *Note:* This scope is **not** applied on routes starting with `dashboard/*` or `api/dashboard/*` to allow showing all data in admin panels.

- **LanguageScope**:  
  Automatically applied if the model's table has a `lang` column, filtering records by the current application language.  
  Also skipped on dashboard routes for the same reason.

## Usage

Simply add the trait to your model to enable automatic scoping without manual configuration:

```php
use App\Models\Traits\HasGeneralAttributeAndScopes;

class Post extends Model
{
    use HasGeneralAttributeAndScopes;

    // No need to define scopes manually here
}
```

#### App\Models\Traits\HasMediaTrait

This trait provides common methods to handle media files (images, files) attached to Eloquent models, including uploading, storing, retrieving URLs, and deleting files both from database and disk.

---

## Features

- **Upload single media** (image or file) and attach/update related record.
- **Upload multiple media files** and create related records.
- **Delete media by IDs** safely from any media relation (e.g., `images`, `files`).
- **Delete all media** for a given relation.
- **Delete single media** record and its physical file.
- Handles file naming with timestamp suffix and stores files under `uploads/{folder}` on `public` disk.
- Relies on media relationships defined via `MediaRelationsTrait` (e.g., `image()`, `images()`, `file()`, `files()`).

---

## Usage Example

```php
use App\Models\Traits\HasMediaTrait;

class Post extends Model
{
    use HasMediaTrait;

    // Define media relationships (e.g. via MediaRelationsTrait)
    public function images() { /* ... */ }
    public function image() { /* ... */ }
    public function files() { /* ... */ }
    public function file() { /* ... */ }
}

// Upload a single image
$url = $post->uploadSingleMedia($request->file('image'), 'image', 'posts');

// Upload multiple files
$uploadedFiles = $post->uploadMultipleMedia($request->file('attachments'), 'file', 'attachments');

// Delete media by IDs
$post->deleteMediaByIds([1,2,3], 'images');

// Delete all files of a type
$post->deleteAllMedia('files');

// Delete a single media relation
$post->deleteSingleMedia('image');
```

#### HasModelPropertyValidation Trait

This trait provides a helper method to validate the presence of specific static properties in a model. It helps ensure that required configuration properties are defined, returning a standardized JSON error response if any are missing.

---

## Features

- Checks if given static properties exist on the model using this trait.
- Returns a JSON error response via `ServiceResponse::serverError` if a required property is missing.
- Returns `null` if all properties exist (no error).

---

## Usage Example

```php
use App\Models\Traits\HasModelPropertyValidation;

class Post extends Model
{
    use HasModelPropertyValidation;

    protected static function boot()
    {
        // Validate required static properties on boot
        $error = static::ensureModelPropertiesExist(['someStaticProperty', 'anotherProperty']);
        if ($error) {
            // Handle error, e.g., abort or log
            abort(500, $error->getData()->message);
        }
    }

    protected static $someStaticProperty = 'value';
    protected static $anotherProperty = 'value2';
}
```

#### HelpersModelTrait

A reusable trait providing common query builder helper methods for Laravel Eloquent models.  
It includes convenient filters, search, and dynamic conditional where clauses for efficient model querying.

---

## Features

- **Date range filtering** on `created_at` and `updated_at` columns:
  - `$query->createdAtRange('2024-01-01,2024-01-31');`
  - `$query->updatedAtRange('2024-01-01,2024-01-31');`

- **Boolean filtering** on `is_active` column (only if value is not null):
  - `$query->isActive(true);`

- **Language filtering** on `lang` column (only if column exists and value is provided):
  - `$query->lang('ar');`

- **Flexible full-text search** across multiple columns, supporting:
  - Normal columns
  - JSON translatable columns
  - Related model columns via dot notation
  - ID search using prefix `#` (e.g., `#123` searches by id)
  - `$query->search(['name', 'email', 'department.name']);`

- **Smart conditional where** helper:
  - `$query->whereOrWhereIn('status', ['pending', 'approved']);`
  - Handles single value, multiple values, or skips if empty.

- **Status filtering helper**:
  - `$query->filterStatus(['active', 'suspended']);`

- **Generic column filtering** with `whereOrWhereIn`:
  - `$query->columnWhereOrWhereIn('type', ['admin', 'user']);`

- **Relation column filtering** with `whereOrWhereIn`:
  - `$query->relationColumnWhereOrWhereIn('department', 'type', ['main']);`

---

## Example Usage

```php
// Filter posts created in January 2024
Post::query()->createdAtRange('2024-01-01,2024-01-31')->get();

// Get active users only
User::query()->isActive(true)->get();

// Search by name or email (supports translation columns and related model)
User::query()->search(['name', 'email', 'department.name'], 'john')->get();

// Filter orders by status or multiple statuses
Order::query()->filterStatus(['pending', 'completed'])->get();
```

#### MorphModelTriggerTrait

A Laravel trait to automatically handle polymorphic auditing fields (`created_by`, `updated_by`, `deleted_by`) and soft delete pruning.

---

***Features***

- Automatically fills `created_by_id`, `created_by_type`, `updated_by_id`, `updated_by_type`, `deleted_by_id`, and `deleted_by_type` fields on model events.
- Uses polymorphic relations to link these fields to the responsible user or model.
- Supports soft deletes and tracks who deleted the record.
- Implements pruning to permanently delete soft-deleted records older than 30 days.
- Requires columns:  
  `created_by_id`, `created_by_type`,  
  `updated_by_id`, `updated_by_type`,  
  `deleted_by_id`, `deleted_by_type`.
- Authenticated user retrieved from `auth('api')->user()`.

---

***Relationships***

- `createdBy()`: MorphTo relation to the user/model who created the record.
- `updatedBy()`: MorphTo relation to the user/model who last updated the record.
- `deletedBy()`: MorphTo relation to the user/model who soft deleted the record.

---

***Example***

```php
class Post extends Model
{
    use MorphModelTriggerTrait;

    // Define the necessary columns in your migration
    // and the trait automatically fills auditing info on create/update/delete.
}
```
#### ReportableTrait

A Laravel trait to generate flexible aggregated reports with dynamic filtering, joining, and grouping based on a configurable report setup.

---

## Features

- Dynamically builds aggregate queries using filters, joins, and grouping.
- Uses a report configuration method (`getReportConfig`) to define how reports are generated.
- Supports filtering by columns that exist in the model's table.
- Supports joins and additional join conditions.
- Supports eager loading and relation filtering when used with Eloquent models.
- Returns results as a Laravel Collection.

---

## Requirements

- The model using this trait **must implement** a static method:
  ```php
  public static function getReportConfig($model, string $type): array;

#### RestoresSoftDeletedModelTrait

This trait provides a safe way to restore soft-deleted Eloquent models while handling potential unique field conflicts according to a defined policy.

---

## Features

- Checks for conflicts with existing non-deleted records based on unique fields before restoring.
- Supports three conflict resolution policies:
  - **prevent**: Do not restore if a conflict exists.
  - **modify**: Modify conflicting unique fields (e.g., append timestamp) before restoring.
  - **replace**: Delete conflicting active records before restoring.
- Returns standardized success or error responses.

---

## Usage

Call the method `safeRestoreById` with:

- The model's fully qualified class name (e.g., `App\Models\User`).
- The soft-deleted model instance to restore.
- The conflict resolution policy (optional, defaults to `'prevent'`).

Example:

```php
$response = $this->safeRestoreById(App\Models\User::class, $deletedUser, 'modify');

if ($response['status']) {
    echo $response['message']; // Success message
} else {
    echo $response['message']; // Error message
}
```
