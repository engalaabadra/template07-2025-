
---

# User & Eloquent Service Structure

This document explains the structure of the **User Service** and the base **Eloquent Service** it extends from.  
It describes how responsibilities are divided and what each part contains.

---

## 📂 Service Structure

App/
├── Services/
│ ├── Eloquent/
│ │ ├── EloquentService.php
│ │ ├── EloquentServiceCRUD.php
│ │ ├── EloquentServiceActivation.php
│ │ ├── EloquentServiceFile.php
│ │ └── ...
│ └── Dashboard/
│ └── Auth/
│ └── User/
│ ├── UserService.php
│ └── UserServiceInterface.php

## 🏛 Eloquent Service

The **Eloquent Service** acts as a **base service layer** for all models.  
It contains generic business logic that can be reused across multiple services.

### Responsibilities:
- **CRUD operations** (create, read, update, delete).
- **Activation / deactivation handling** (`is_active` logic).
- **File handling** (upload / delete files).
- **Translation management** (multi-language data support).
- **Service response wrapper** for consistent API responses.
- **Transaction handling** for safe database operations.

### Contents:
- `EloquentServiceCRUD.php` → Generic CRUD methods.
- `EloquentServiceActivation.php` → Handles activation toggling.
- `EloquentServiceFile.php` → File upload & delete logic.
- `EloquentService.php` → Base class that connects everything together.

---

## 👤 User Service

The **User Service** extends the base `EloquentService` and adds **user-specific business logic**.

### Responsibilities:
- Manage user creation & updates.
- Handle user profile & related models.
- Manage user roles & permissions.
- Handle user activation/deactivation with role constraints.
- Integrate translations for user-related data.

### Contents:
- `UserService.php` → Implements business logic for `User`.
- `UserServiceInterface.php` → Defines the contract for `UserService`.

---

## 🔗 Connection Between User & Eloquent Service

- `UserService` **extends** `EloquentService`.
- Gains access to:
  - CRUD
  - Activation
  - File handling
  - Translations
- Adds **extra user-related logic** (roles, permissions, profile).

This way, **common logic** stays in `EloquentService`,  
while **custom user logic** stays in `UserService`.

---

## ✅ Benefits of This Structure

- **Reusability** → Common logic shared across all services.
- **Maintainability** → Clear separation between generic logic and user-specific logic.
- **Scalability** → Easy to add new services for other models (e.g., `RoleService`, `BannerService`).
- **Consistency** → Unified structure for all service layers.


---
# User & Eloquent Service Architecture

This document explains the **service layer architecture** for the `User` module and the underlying **Eloquent base services**.  
The goal of this structure is to keep the code **clean, reusable, and modular**, separating common logic (generic CRUD, file handling, translations, etc.) from module-specific business rules (User service).

---

## 📂 Folder Structure

app/
│
├── Services/
│ ├── Eloquent/
│ │ ├── EloquentService.php
│ │ ├── EloquentServiceCRUD.php
│ │ ├── EloquentServiceActivation.php
│ │ ├── EloquentServiceFile.php
│ │ └── ...
│ │
│ └── Dashboard/
│ └── Auth/
│ └── User/
│ ├── UserService.php
│ └── UserServiceInterface.php


---

## 🏗️ Eloquent Services (Base Layer)

These services act as the **foundation layer** for any model-related business logic.  
They contain **reusable operations** that can be shared across all modules.

- **`EloquentService.php`**  
  Base abstract service containing shared utilities and helpers.

- **`EloquentServiceCRUD.php`**  
  Provides **generic CRUD operations** (create, update, delete, restore, etc.).

- **`EloquentServiceActivation.php`**  
  Handles **activation / deactivation logic**, toggling `is_active` states.

- **`EloquentServiceFile.php`**  
  Handles **file and media operations**, e.g. uploading, attaching, and deleting files.

---

## 👤 User Service (Module Layer)

The `UserService` builds on top of the **Eloquent base services**.  
It contains **user-specific business rules** while still reusing the common logic.

- **`UserServiceInterface.php`**  
  Defines the **contract** for the `UserService`.  
  Ensures that the implementation remains consistent and testable.

- **`UserService.php`**  
  Implements the interface and extends `EloquentService`.  
  Responsibilities include:
  - Managing **user profiles**  
  - Handling **roles & permissions**  
  - Connecting with **translation services** for multilingual data  
  - Coordinating **file uploads** for user profile images  
  - Using `EloquentServiceActivation` to manage user activation/deactivation  
  - Using `EloquentServiceCRUD` for core create/update/delete logic  

---

## 🔗 How They Work Together

1. **Controller Layer**  
   Calls `UserService` instead of directly calling the repository or model.

2. **UserService**  
   Uses `EloquentServiceCRUD`, `EloquentServiceFile`, and `EloquentServiceActivation` for common logic.  
   Adds **user-specific rules** (e.g., assigning roles, syncing permissions, managing profiles).

3. **Eloquent Base Services**  
   Provide reusable building blocks shared across **all modules** (not only User).

---

## ✅ Benefits of This Architecture

- **Reusability** → Common logic for CRUD, files, translations, and activation is centralized.  
- **Maintainability** → Module-specific rules stay inside their respective services.  
- **Flexibility** → Easy to extend functionality for new modules (e.g., Admin, Banner, Member).  
- **Clean Separation** → Controller → Service → Repository → Model layers are clearly separated.  

---


---

## 📖 Explanation of Components

### 🔹 EloquentService
- Base abstract service providing shared logic.
- Defines **common patterns** like CRUD handling, scoping, translation, and transactions.
- Uses traits (e.g., `HandlesServiceTransactions`) to wrap operations in DB transactions.

### 🔹 EloquentServiceCRUD
- Extends the base Eloquent Service.
- Handles **generic CRUD operations**:
  - `create()`
  - `update()`
  - `delete()`
  - `restore()`
- Coordinates translations and media when creating/updating records.

### 🔹 EloquentServiceActivation
- Provides methods for handling **activation & deactivation** of models.
- Uses enums (`IsActiveEnum`) for clarity.
- Example: activating/deactivating a user, role, or banner.

### 🔹 EloquentServiceFile
- Manages **file & media operations** (uploading, deleting).
- Ensures files are linked to models using **polymorphic relations**.

### 🔹 UserService
- A **feature service** specific to the `User` module.
- Extends the `EloquentService` for access to generic CRUD/activation/file handling.
- Handles **user-specific business logic**, including:
  - Creating users with profiles
  - Assigning roles & permissions
  - Handling authentication-related actions
  - Managing associated files (avatars, documents)
  - Handling translations if needed

### 🔹 UserServiceInterface
- Defines the **contract** for `UserService`.
- Ensures consistency when injected into controllers or other services.

### 🔹 TranslationService
- Handles **multi-language support** for translatable models.
- Works with `EloquentServiceCRUD` during create/update.

### 🔹 ServiceResponse
- Standardized response object for services.
- Ensures **consistent API responses** across modules.
- Example structure:
  ```php
  return new ServiceResponse(
      success: true,
      message: 'User created successfully',
      data: $user
  );
---


# User Service Overview

This document explains the structure and responsibilities of the **UserService**, including its related CRUD, File, and Activation services.

---

## 📂 Folder Structure

app/
└── Services/
└── Dashboard/
└── Auth/
└── User/
├── UserService.php
├── UserServiceInterface.php
├── UserCRUDService.php
├── UserFileService.php
└── UserActivationService.php


---

## 👤 UserService

The `UserService` is the main service for managing **users**.  
It coordinates all user-related operations by delegating to the **CRUD**, **File**, and **Activation** services.

### Responsibilities:
- Handles **user-specific business logic**:
  - Creating and updating users
  - Managing user profiles
  - Assigning roles and permissions
- Delegates **common operations** to the sub-services:
  - `UserCRUDService` → generic CRUD
  - `UserFileService` → file uploads and deletions
  - `UserActivationService` → activation/deactivation logic
- Provides a **clean interface** for controllers and other services to interact with users.

---

## 🔹 UserCRUDService

Handles all **database CRUD operations** for users.

### Responsibilities:
- `store()` → create a new user with profile, roles, translations, and files.
- `update()` → update an existing user and related data.
- `destroy()` → soft-delete a user.
- `destroyMany()` → soft-delete multiple users.
- `restore()` → restore a soft-deleted user.
- `forceDelete()` → permanently delete a user.
- `forceDeleteMany()` → permanently delete multiple users.
- Handles **bulk operations** with proper tracking of processed, failed, and not found IDs.

### Notes:
- Filters out unrelated fields like `roles`, `files`, and `image` from the request before saving.
- Uses **eager loading** if defined in the model.
- Coordinates with **TranslationService** for multi-language data.

---

## 🔹 UserFileService

Handles **file and media operations** for users.

### Responsibilities:
- `uploadFile()` → upload a single file for a user.
- `uploadFiles()` → upload multiple files at once.
- `deleteFile()` → remove a single file.
- `deleteFiles()` → remove multiple files.
- Ensures files are correctly linked to the user using **polymorphic relationships**.
- Works seamlessly with **UserCRUDService** for attaching files during create/update.

---

## 🔹 UserActivationService

Handles **activation and deactivation** logic for users.

### Responsibilities:
- `changeActivate()` → toggle activation status of a single user.
- `changeActivateMany()` → activate or deactivate multiple users at once.
- Ensures proper validation and business rules are applied before changing status.
- Can be easily extended for roles or other module-specific activation rules.

---

## 🔗 How It Works Together

Controller → UserService → [UserCRUDService | UserFileService | UserActivationService] → Repository → Model



1. Controller calls the **UserService**.
2. `UserService` delegates operations:
   - CRUD operations → `UserCRUDService`
   - File management → `UserFileService`
   - Activation → `UserActivationService`
3. Base **Eloquent logic** (from `EloquentService`) is used in CRUD/File/Activation for consistency.
4. Final results are returned via **ServiceResponse** or **API Resources** for controllers.

---

## ✅ Benefits

- **Separation of concerns** → CRUD, File, and Activation are separated into sub-services.
- **Reusability** → Common logic in sub-services can be reused across other modules.
- **Maintainability** → Easy to update or extend any user-related functionality.
- **Scalability** → New features like bulk operations or additional user fields can be added without breaking existing code.


---

## 💻 Short Code Example (Controller)

```php
use App\Services\Dashboard\Auth\User\UserService;
use App\Http\Requests\Dashboard\Auth\UserRequest;
use App\Http\Requests\ActivateRequest;
use App\Http\Requests\File\UploadFilesRequest;

class UserController extends BaseController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function store(UserRequest $request)
    {
        // Create new user
        return $this->respond($this->userService->crud()->store($request, new User));
    }

    public function uploadFiles(UploadFilesRequest $request, $id)
    {
        // Upload multiple files for a user
        return $this->respond($this->userService->file()->uploadFiles($request, $id, new User));
    }

    public function changeActivate(ActivateRequest $request, $id)
    {
        // Activate or deactivate a user
        return $this->respond($this->userService->activation()->changeActivate($request, $id, new User));
    }
}




-------------------------------
+------------------+
|   UserService    |
+------------------+
| - store()        |
| - update()       |
| - changeActivate()|
| - destroy()      |
| - restore()      |
| - forceDelete()  |
+------------------+
         |
         | uses
         v
+--------------------------+
| UserBulkOperationsTrait  |
+--------------------------+
| - handleBulkRestoreAndDelete() |
| - handleBulkActivation()       |
+--------------------------+
         |
         | uses
         v
+--------------------------+
| UserFileOperationsTrait  |
+--------------------------+
| - uploadFile()           |
| - uploadFiles()          |
| - deleteFile()           |
| - deleteFiles()          |
+--------------------------+


                ┌─────────────────────┐
                │ EloquentService     │
                │────────────────────│
                │ + baseRepo          │
                │ + eloquentRepo      │
                │ + translationService│
                │ + crudService       │
                │ + activationService │
                │ + fileService       │
                │────────────────────│
                │ use EloquentService │
                │      CRUDTrait      │
                │ use EloquentService │
                │      ActivationTrait│
                │ use EloquentService │
                │      FileTrait      │
                └─────────┬──────────┘
                          │
                          │ inherits
                          ▼
                ┌─────────────────────┐
                │ UserService         │
                │────────────────────│
                │ // يمكن إضافة      │
                │ // دوال خاصة باليوزر │
                └─────────────────────┘
