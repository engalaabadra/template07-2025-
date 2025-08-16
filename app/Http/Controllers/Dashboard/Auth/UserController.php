<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\BaseController;
use App\Repositories\Dashboard\Auth\User\UserRepository;
use App\Services\Dashboard\Auth\User\UserService;
use App\Models\User;
use App\Resources\UserResource;
use App\Resources\Auth\RoleResource;
use App\Resources\Auth\PermissionResource;
use App\Http\Requests\File\UploadFilesRequest;
use App\Http\Requests\File\DeleteFilesRequest;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Requests\Dashboard\Auth\UserRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;
use App\Http\Requests\ActivateRequest;

/**
 * Class UserController
 *
 * Handles user management operations for dashboard including:
 * CRUD actions, activation/deactivation, trash management,
 * role/permission assignment, and file uploads.
 */
class UserController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait


    /**
     * @var UserService
     * The service containing business logic related to users.
     */
    protected $userService;


    /**
     * @var User
     * The User model instance.
     */
    protected $user;

    /**
     * UserController constructor.
     * Dependency Injection for User model, UserService.
     */
    public function __construct(User $user, UserService $userService)
    {
        $this->user = $user;
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index()
    {
        $result = $this->userService->getData($this->user);  // Fetch user data (may be paginated or collection)

        if (isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('users'), 'url' => route('dashboard.users.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('User/Index', [
                 'rows' => $result,                 // User list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }
        // For API requests, respond with data wrapped in UserResource
        return $this->respond($result, UserResource::class);
    }

    /**
     * Show details of a specific user.
     */
    public function show($id)
    {
        $result = $this->userService->show($id, $this->user); // Retrieve user details

        if (isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('users'), 'url' => route('dashboard.users.index')],
                ['label' => $result->username ?? __('User')],
            ]);
        }

        // Respond with user data wrapped in UserResource
        return $this->respond($result, UserResource::class);
    }

    /**
     * Get roles assigned to a user.
     */
    public function getRolesUser($userId)
    {
        $result = $this->userService->getRolesUser($this->user, $userId); // Fetch user's roles
        return $this->respond($result, RoleResource::class);             // Respond with roles wrapped in RoleResource
    }

    /**
     * Get permissions associated with a user's role.
     */
    public function getPermissionsRoleUser($roleUserId)
    {
        $result = $this->userService->getPermissionsRoleUser($this->user, $roleUserId); // Fetch permissions by role
        return $this->respond($result, PermissionResource::class);                      // Respond wrapped in PermissionResource
    }

    /**
     * Store a new user or update an existing one.
     */
    public function store(UserRequest $request, $id = null)
    {
        $result = $this->userService->store($request, $this->user, $id); // Create or update user via service
        
        // Respond with user data wrapped in UserResource , which formats the user data consistently for API or web responses
        return $this->respond($result, UserResource::class);
        // Respond with status 201 Created and redirect route 'dashboard.users.index'
        //return $this->respond($result, UserResource::class, $message = null, 'dashboard.users.index');
    }

    /**
     * Update an existing user.
     */
    public function update(UserRequest $request, $id)
    {
        // Update the user data by calling the UserService
        $result = $this->userService->update($request, $id, $this->user);
        // Return the response wrapped with UserResource, which formats the user data consistently for API or web responses
        return $this->respond($result, UserResource::class);
    }
    public function changeActivate(ActivateRequest $request, $id)
    {
        // Toggle activation status (activate/deactivate) for a specific user
        $result = $this->userService->changeActivate($request, $id, $this->user);
        // Return the response wrapped with UserResource, which formats the user data consistently for API or web responses
        return $this->respond($result, UserResource::class);
    }

    public function changeActivateMany(BulkActionRequest $request)
    {
        // Activate or deactivate multiple users at once
        $result = $this->userService->changeActivateMany($request, $this->user);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }

    public function destroy($id)
    {
        // Soft delete a user (mark as deleted without removing from DB)
        $result = $this->userService->destroy($id, $this->user);
        // Return the response wrapped with UserResource, which formats the user data consistently for API or web responses
        return $this->respond($result, UserResource::class);
    }

    public function destroyMany(BulkActionRequest $request)
    {
        // Soft delete multiple users at once
        $result = $this->userService->destroyMany($request, $this->user);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }

    public function forceDelete($id)
    {
        // Permanently delete a user from the database
        $result = $this->userService->forceDelete($id, $this->user);
        // Return the response directly (no resource wrapping, likely a simple success message , with empty data)
        return $this->respond($result);
    }

    public function forceDeleteMany(BulkActionRequest $request)
    {
        // Permanently delete multiple users at once
        $result = $this->userService->forceDeleteMany($request, $this->user);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }

    public function trash()
    {
        // Retrieve a list of users who were soft deleted (in trash)
        $result = $this->userService->trash($this->user);
        // Return the response wrapped with UserResource, which formats the user data consistently for API or web responses
        return $this->respond($result, UserResource::class);
    }

    public function restore($id)
    {
        // Restore a soft deleted user
        $result = $this->userService->restore($id, $this->user);
        // Return the response wrapped with UserResource, which formats the user data consistently for API or web responses
        return $this->respond($result, UserResource::class);
    }

    public function restoreMany(BulkActionRequest $request)
    {
        // Restore multiple soft deleted users at once
        $result = $this->userService->restoreMany($request, $this->user);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }

    //==================== Files ====================//

    public function uploadFile(UploadImageRequest $request, $id)
    {
        // Upload a single file (image or other) related to a user
        $result = $this->userService->uploadFile($request, $id, $this->user);
        // Return user data wrapped in UserResource with updated file this user
        return $this->respond($result, UserResource::class);
    }

    public function uploadFiles(UploadFilesRequest $request, $id)
    {
        // Prepare eager loading of 'files' relation to optimize queries
        $this->user->setEagerLoading(['files']);
        // Upload multiple files related to a user
        $result = $this->userService->uploadFiles($request, $id, $this->user);
        // Return user data wrapped in UserResource with updated files this user
        return $this->respond($result, UserResource::class);
    }

    public function deleteFile($id)
    {
        // Delete a single file associated with a user
        $result = $this->userService->deleteFile($id, $this->user);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }

    public function deleteFiles(DeleteFilesRequest $request, $id)
    {
        // Delete multiple files associated with a user
        $result = $this->userService->deleteFiles($request, $id, $this->user);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }
    
}

