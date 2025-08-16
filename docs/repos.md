
# BaseRepository

A reusable, foundational repository class implementing common data access and utility methods for Laravel Eloquent models.

---

## Overview

This base repository class provides core methods and helpers used throughout the application to interact with Eloquent models, manage soft deletes, handle sessions and UI flash messages, support exporting, and share data with frontend (Inertia).

---

## Key Functionalities

### Basic Model Retrieval

- **findOrFailApi($id, $model): Model**  (normal find)
  Finds a model by ID or throws a custom API exception if not found.

- **findWithoutTrashedOrFail($id, $model): Model**  (find only item not in trash)
  Finds a model by ID excluding soft-deleted records, or throws if not found.

- **findOnlyTrashedOrFail($id, $model): Model**  (find only item in trash)
  Finds a soft-deleted model by ID or throws if not found.

---

### Soft Delete & Force Delete with Transactions

- **tryDelete(object $row, ?Closure $callback = null): bool**  
  Attempts to soft-delete a model instance within a transaction and executes an optional callback.

- **tryForceDelete(object $row): bool**  
  Attempts to permanently delete a model instance within a transaction.

- **tryDeleteForceDelete(string $model, int $id, bool $makeMessageSession = false): bool**  
  Attempts to force delete a record by ID.

- **tryRestore(string $model, int $id, bool $makeMessageSession = false): void**  
  Restores a soft-deleted record by ID within a transaction and clears `deleted_by_id`.

---

### Request and Session Helpers

- **requestExpectJson(): bool**  
  Returns whether the current request expects a JSON response.

- **makeSuccessSessionMessage(?string $message = null): void**  
  Sets a success toast flash message for the session (non-JSON requests only).

- **makeErrorSessionMessage(?string $message = null): void**  
  Sets an error toast flash message for the session (non-JSON requests only).

- **refreshDom(): void**  
  Sets a session flash to trigger frontend DOM refresh.

- **flashShareData(array $data = []): void**  
  Stores temporary flash data to share with the frontend.

---

### UI Sharing Helpers (Inertia)

- **breadcrumb(?array $items = null): void**  
  Shares breadcrumb navigation data with Inertia and sets the page title.

- **useBreadcrumb(array $append_breadcrumb = []): void**  
  Stub to initialize breadcrumb with appended items.

- **allowSearch(): void**  
  Shares a flag with Inertia to enable search functionality.

- **pageTitle(string $title): void**  
  Shares the current page title with Inertia.

- **useFilter(array|Collection|null $filters = null): void**  
  Shares filter options with Inertia.

- **useTransparent(bool $transparent = true): void**  
  Shares a transparency flag for UI styling.

---

### Export to Excel

- **exportToExcel($model, $query): mixed**  
  Exports model data to Excel using a query and the model's `$columnsToExport` property.  
  - For web requests: triggers direct file download.  
  - For API requests: returns a public URL to the stored file.

---

### UI Components Helpers

- **addElFileCard(Collection $collection, string $label, $archives = null, string $el_file_card_type = 'archive_card'): array**  
  Helper to structure file card UI data.

- **makeStatisticCard(?string $title, ?string $value, string $icon = 'pi pi-chart-line', bool $is_price = false): array**  
  Helper to generate a statistic card data structure for UI display.

---

## Exceptions & Errors

- Throws `ApiResponseException` with `ServiceResponseEnum::NOT_FOUND` when records are not found.

---

## Usage Notes

- Designed for use with Laravel Eloquent models.
- Supports soft deletes and restores.
- Integrates with Inertia.js for frontend state sharing.
- Handles session-based flash messages with toastr-style notifications.
- Uses database transactions to ensure data integrity during destructive operations.
- Designed to work with both JSON API and traditional web requests.

---

## Example

```php
$repo = app(\App\Repositories\Base\BaseRepository::class);

// Find user or throw
$user = $repo->findOrFailApi($userId, User::class);

// Soft delete with callback
$repo->tryDelete($user, function($deletedUser) {
    Log::info("User deleted: {$deletedUser->id}");
});

// Export all users to Excel
$urlOrDownload = $repo->exportToExcel(User::class, User::query());

// Share breadcrumb
$repo->breadcrumb([
    ['label' => 'Users', 'url' => route('dashboard.users.index')],
    ['label' => 'Edit User'],
]);

