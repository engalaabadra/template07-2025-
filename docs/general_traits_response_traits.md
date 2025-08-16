

## Traits Use In Front

#### UIHelpersTrait

A Laravel trait that consolidates common UI-related helper traits for controllers.

---

## Included Traits

### FormDataTrait

The `FormDataTrait` provides a simple reusable method for controllers to prepare common form data used in create and update views.

Provides shared form data such as select options, enums, and other form-related helpers
---

***Purpose***

- Supplies default form data arrays, especially for select options or enums.
- Helps keep controllers clean by centralizing form-related data.

---

***Usage***

The trait defines one method:

***`getCreateUpdateData(): array`***

Returns an array containing:

- `form_data`: An associative array of form fields and their related data.
- For example, it provides options for the `is_active` field using the `IsActiveEnum` enum helper method.

---

***Example***

```php
use App\Enums\IsActiveEnum;
use App\Traits\Controllers\FormDataTrait;

class SomeController
{
    use FormDataTrait;

    public function create()
    {
        $data = $this->getCreateUpdateData();
        // Pass $data to view
        return view('some.create', $data);
    }
}
```
### InertiaShareTrait

This trait provides convenient methods to share common UI data with Inertia.js front-end components in Laravel applications.

Handles sharing common data with Inertia front-end, including breadcrumbs, page titles, and search flags.

---

***Purpose***

- Share breadcrumb navigation data with Inertia.
- Share page title dynamically.
- Share flags like `allowSearch` for UI behavior.

---

***Methods***

***`breadcrumb(?array $items = null): void`***

- Accepts an optional array of breadcrumb items.
- Each item must have a `'label'` and optionally a `'url'`.
- Filters out invalid items (those without a label).
- Shares a default breadcrumb starting with the home link plus the provided items via `Inertia::share`.
- Sets the page title by combining the last two breadcrumb labels with " - ".
- If `$items` is empty or null, no sharing happens.

### `useBreadcrumb($append_breadcrumb = []): void`

- Placeholder method for custom breadcrumb logic (currently empty).

### `allowSearch(): void`

- Shares a flag `allowSearch` with Inertia set to `true`.
- Useful for enabling search UI features on the frontend.

### `pageTitle(string $title): void`

- Shares the given page title with Inertia to be used in frontend layouts or components.

---

***Usage Example***

```php
use App\Traits\Controllers\InertiaShareTrait;

class SomeController
{
    use InertiaShareTrait;

    public function index()
    {
        $this->breadcrumb([
            ['label' => 'Section', 'url' => route('dashboard.section.index')],
            ['label' => 'Subsection']
        ]);

        $this->allowSearch();

        // Proceed with returning an Inertia response...
    }
}
```

### WebIndexRenderTrait
Provides standardized rendering for index pages and lists.
via : renderIndexPage()

## Usage

Simply include this trait in your controller to gain access to all three UI helper functionalities:

```php
use App\Traits\Controllers\UIHelpersTrait;

class SomeController extends Controller
{
    use UIHelpersTrait;

    public function index()
    {
        $this->breadcrumb([
            ['label' => 'Dashboard', 'url' => route('dashboard.dashboard')],
            ['label' => 'Users'],
        ]);

        $data = $this->getCreateUpdateData();

        return $this->renderIndexPage('Users/Index', $data);
    }
}
```
---------------------------------
## Response Traits

## WebApiSuccessResponseTrait
This trait provides a **unified response** to handle **successful responses** for both **API (JSON)** and **Web (Redirect)** requests in Laravel controllers.  

It supports:
- Pagination results
- Collections
- Single model instances
- Optional Laravel Resource wrapping
- Custom success messages
- Redirects with flash messages (for Web requests)

---

## Methods Overview

---

#### `respond($data = null, $message = null, string $resourceClass = null, ?string $redirectRoute = null)`

Returns a unified response depending on the request type (API or Web).

**Parameters:**
- `$data` : Data to return.
- `$message` : Optional success message.
- `$resourceClass` : Optional resource class name for formatting.
- `$redirectRoute` : Route name to redirect to for web responses.

**Returns:**
- `JsonResponse` for API requests.
- `RedirectResponse` for Web requests.

---

#### `handleApi($data, $message = null, ?string $resourceClass = null)`

Formats and returns a structured API JSON response.

**Supported cases:**
- Paginated data (with or without Resource).
- Laravel Collections (with or without Resource).
- Single model or data item (with or without Resource).
- Direct string/URL responses.

**Returns:**  
`JsonResponse` with a standardized success format.

---

#### `handleWeb($data, $message = null, ?string $redirectRoute = null)`

Handles Web responses via redirect with a flash success message.

**Returns:**  
`RedirectResponse` to a specific route or back to the previous page.

---

#### `jsonSuccessResponse($data = null, string $message = null)`

Creates a standardized JSON success response.

**Returns:**  
`JsonResponse` containing:
```json
{
  "status": true,
  "message": "Success message",
  "data": [...]
}
```

# WebApiSuccessResponseTrait

This trait provides a **unified response** handler for both **API JSON responses** and **Web redirects** in Laravel controllers.

It supports:
- Pagination results
- Collections
- Single model instances
- Optional Laravel Resource wrapping
- Custom success messages
- Redirects with flash messages (for Web requests)

---

## Methods Overview

### respond($data = null, $message = null, string $resourceClass = null, ?string $redirectRoute = null)

Returns either JSON API response or Web redirect based on the request type.

### handleApi($data, $message = null, ?string $resourceClass = null)

Formats and returns structured JSON API success response, handling pagination, collections, and single models with optional resource wrapping.

### handleWeb($data, $message = null, ?string $redirectRoute = null)

Handles Web requests by redirecting with a flash message.

### jsonSuccessResponse($data = null, string $message = null)

Returns standardized JSON response with `status: true`, message, and data. Uses HTTP status 201 if data is empty, otherwise 200.

### isWebRequest()

Detects if current request is a Web request (expects HTML) or API request (expects JSON).

---

## Example Usage in Controller

```php
use App\Traits\Controllers\WebApiSuccessResponseTrait;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends Controller
{
    use WebApiSuccessResponseTrait;

    public function index()
    {
        $users = User::paginate(10);
        return $this->respond($users, 'Users fetched successfully', UserResource::class);
    }

    public function store(Request $request)
    {
        $user = User::create($request->all());
        return $this->respond($user, 'User created successfully', UserResource::class);
    }

    public function update(Request $request, User $user)
    {
        $user->update($request->all());
        // Redirect to users.index with flash success message (for web requests)
        return $this->respond(null, 'User updated successfully', null, 'users.index');
    }
}
```

---

## General Traits

### JsonArrayFieldsHandlerTrait

This trait helps decode JSON-encoded string fields into PHP arrays during request validation preparation, especially useful for handling array inputs sent via multipart/form-data requests (like file uploads), where array fields arrive as JSON strings (e.g., `"[1,2,3]"`) instead of traditional repeated keys.

---

***Usage***

- Include this trait in your FormRequest class.
- Call `decodeJsonArrayFields(['field1', 'field2'])` inside the `prepareForValidation()` method.
- It will automatically detect if the specified fields are JSON strings representing arrays and decode them to native PHP arrays before validation.

---

***Example***

```php
use App\Traits\Requests\JsonArrayFieldsHandlerTrait;

class UserRequest extends FormRequest
{
    use JsonArrayFieldsHandlerTrait;

    protected function prepareForValidation()
    {
        $this->decodeJsonArrayFields(['roles', 'tags']);
    }
}

```
### HandlesServiceTransactions Trait

This trait enables automatic wrapping of specified service methods inside a database transaction. It intercepts calls to methods listed in `$transactionalMethods` and executes them within a `DB::transaction()` to ensure atomic operations.

---

***Features***

- Defines a list of method names (`store`, `update`, `destroy`, `restore` by default) that should run inside a database transaction.
- Uses PHP’s magic `__call` method to intercept calls to these methods, when these methods be protected not public to be not visicle in class , in this time eill calling this magic method __call() , to excute DB::transaction in these method (`store`, `update`, `destroy`, `restore`)
- Wraps the intercepted method execution inside a transaction using Laravel’s `DB::transaction()`.
- For other methods not listed, it calls them normally without a transaction.

---

***Usage***

Simply include the trait in your service class:

```php
use App\Traits\Services\HandlesServiceTransactions;

class UserService
{
    use HandlesServiceTransactions;

    protected array $transactionalMethods = ['store', 'update'];

    protected function store(array $data)
    {
        // Your store logic here, automatically wrapped in a transaction
    }

    protected function update(int $id, array $data)
    {
        // Your update logic here, automatically wrapped in a transaction
    }
}
```
### ChecksOwnershipTrait

This trait provides a reusable method to verify that the currently authenticated user owns a given Eloquent model instance. It is useful for authorization checks to ensure users can only access or modify resources they own.

---

***Features***

- Determines the authenticated user based on the request path:
  - Uses `admin-api` guard for dashboard API routes (`api/dashboard/*`)
  - Uses `api` guard for other API routes (`api/*`)
- Checks if the model's ownership attribute (default `user_id`) matches the authenticated user's ID.
- Throws an `ApiResponseException` with a forbidden error if ownership validation fails.

---

***Usage***

Include this trait in any service or controller where ownership checks are required:

```php
use App\Traits\Services\ChecksOwnershipTrait;

class SomeService
{
    use ChecksOwnershipTrait;

    public function updateItem($item, array $data)
    {
        $this->ensureOwnership($item); // Throws exception if not owner
        // Proceed with update...
    }
}

```

