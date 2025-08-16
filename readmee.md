

## Summary

This Laravel project architecture focuses on:

- Extending Laravel's routing to cover bulk operations and custom resource methods.
- Enforcing advanced multilingual and soft delete-aware validations.
- Applying global query scopes for active status and locale filtering.
- Standardizing API/service responses for consistency and ease of error handling.
- Using traits, repositories, filters, and builders for modular, reusable, and maintainable code.

This structure and codebase suit applications needing complex resource handling, multilingual support, and clean separation of concerns.

---

## How to Use

- Register your routes using the custom resource registrars to enable bulk actions and file handling routes.
- Apply the validation rules in your form requests to handle multilingual uniqueness and soft delete-aware uniqueness.
- Attach global scopes like `ActiveScope` and `LanguageScope` to your models to automatically filter queries.
- Return responses from your services using the `ServiceResponse` class for consistent API output.
- Organize additional business logic into traits, repositories, filters, and builders as needed.

---

# Laravel Project Comprehensive Overview

This Laravel project contains several custom enhancements to Laravel's default behavior including resource routing, validation, global scopes, service responses, traits, filters, builders, and models. Below is a detailed summary of each component and the file organization.

---

## 1. Routing Enhancements

- **ResourceRegistrarCustom**: Extends Laravel's resource routing to support bulk operations and soft delete workflows:
  - restoreMany, restore, changeActivate, changeActivateMany
  - destroyMany, forceDelete, forceDeleteMany
- **ResourceRegistrarFiles**: Adds resourceful routes for file operations, allowing uploading and deleting single or multiple files related to resources.
- **PendingCustomResourceRegistration**: Helper class for deferred registration of custom routes with filtering capabilities (`only`, `except`).

## 2. Validation Rules

- **UniqueTranslationValue**: Validates uniqueness of translated values per language in multilingual tables that use `lang` and `translate_id` fields.
- **UniqueWithoutSoftDeletes**: Ensures uniqueness in database ignoring soft-deleted rows (`deleted_at` is null).
- **SmallTextRule**: Validates string length constraints (between 2 and 100 characters) for small text fields like names or titles.

## 3. Global Eloquent Scopes

- **ActiveScope**: Applies a global query filter to only retrieve records where `is_active = true`.
- **LanguageScope**: Applies a global filter to return only records matching the current application locale (`lang` column).

## 4. Service Layer

- **ServiceResponse**: A unified response wrapper to standardize API/service responses with status types, messages, HTTP codes, and payload data. Provides static helpers like `successResponse()`, `notFound()`, `forbidden()`, `badRequest()`, `unauthorized()`, and `serverError()`.

## 5. Additional Components (Based on Your Descriptions)

- **Traits**: (Assumed from your mentions)
  - Traits for model relationships, general methods, or resource-specific logic to promote code reuse and separation of concerns.
- **Repositories**: (Common pattern for data access)
  - Abstracted data retrieval/manipulation methods, keeping controllers and services clean.
- **Filters & Builders**:
  - Query filters and custom Eloquent builders to apply reusable query logic, such as filtering by status, date, or relationships.
- **Models**:
  - Eloquent models with the above global scopes and traits applied to handle database interaction and business logic.

## Suggested Project File Structure

app/
├── Enums/
│ └── ServiceResponseEnum.php
├── Http/
│ ├── Requests/
│ │ ├── Rules/
│ │ │ ├── UniqueTranslationValue.php
│ │ │ ├── UniqueWithoutSoftDeletes.php
│ │ │ └── SmallTextRule.php
│ │ └── FormRequests.php (your custom form requests)
├── Models/
│ ├── Scopes/
│ │ ├── ActiveScope.php
│ │ └── LanguageScope.php
│ ├── Traits/
│ │ └── (your traits here)
│ └── (your Eloquent models)
├── Repositories/
│ └── (repository classes)
├── Routing/
│ ├── PendingCustomResourceRegistration.php
│ ├── ResourceRegistrarCustom.php
│ └── ResourceRegistrarFiles.php
├── Services/
│ └── ServiceResponse.php
├── Filters/
│ └── (custom filters for queries)
├── Builders/
│ └── (custom Eloquent query builders)


# Laravel Project Overview

This project follows best practices by organizing code into Services, Traits, and Repositories to achieve clean, maintainable, and testable code.

---

## Services

Services encapsulate business logic and provide a clean API for the application.  
Example:  
- `ServiceResponse` is a standardized response wrapper that manages consistent API/service responses including status, messages, HTTP codes, and data payload.

**Benefits:**  
- Centralizes response formatting.  
- Simplifies controller logic.  
- Facilitates scalability and maintenance.

---

## Traits

Traits are reusable method groups shared across models or classes to promote code reuse and separation of concerns.

**Usage:**  
- Defined inside the `Traits` folder.  
- Included in models or classes using `use`.

**Benefits:**  
- Reduces code duplication.  
- Organizes related functionalities logically.  
- Simplifies updates by centralizing shared methods.

---

## Repositories

Repositories abstract data access from controllers and services, handling queries and database interactions.

**Usage:**  
- Contains methods like `findById()`, `store()`, `update()`, `delete()`.  
- Called by services or controllers to access data.

**Benefits:**  
- Keeps controllers and services focused on their roles.  
- Eases maintenance and testing.  
- Adds flexibility for future data source changes.

---

## Typical Flow & Structure

```plaintext
Controller -> Service -> Repository -> Model/Database
                    -> ServiceResponse (uniform API response)

Model -> uses Traits (e.g., relationships, general methods)



## Eloquent Service, Eloquent Repository, and Traits

### Eloquent Service

The Eloquent Service acts as the **business logic layer** in the application. It handles complex operations that often involve multiple repositories or additional business rules beyond simple data access.

- **Responsibilities:**
  - Orchestrate data operations using one or more repositories.
  - Apply business rules and validation logic.
  - Prepare data for controllers or API responses.
  - Use ServiceResponse for standardized results.

- **Benefits:**
  - Keeps controllers slim and focused on HTTP concerns.
  - Centralizes business logic for easier testing and maintenance.
  - Improves separation of concerns between data access and business logic.

---

### Eloquent Repository

The Eloquent Repository is a **data access layer** abstracting direct interaction with Eloquent models and the database.

- **Responsibilities:**
  - Perform CRUD operations.
  - Build queries and filters specific to the model.
  - Hide implementation details of data retrieval and persistence.
  - Provide reusable data methods to the service layer.

- **Benefits:**
  - Decouples database logic from services and controllers.
  - Makes the codebase easier to maintain and test.
  - Allows swapping underlying data sources with minimal impact on the rest of the app.

---

### Traits

Traits are reusable sets of methods that can be included inside models or other classes to share functionality.

- **Common uses in this project:**
  - Defining model relationships (e.g., `RelationsTrait`).
  - Adding general utility methods to models (`GeneralMethodsTrait`).
  - Handling repeated logic like custom casts, accessors, or scopes.

- **Benefits:**
  - Avoids code duplication.
  - Helps keep models and classes clean and focused.
  - Promotes modular design and easier maintenance.

---

### Typical Interaction

