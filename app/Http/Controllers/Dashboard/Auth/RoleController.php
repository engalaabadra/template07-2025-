<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\BaseController;
use App\Repositories\Dashboard\Auth\Role\RoleRepository;
use App\Services\Dashboard\Auth\Role\RoleService;
use App\Models\Role;
use App\Resources\Auth\RoleResource;
use App\Http\Requests\File\UploadFilesRequest;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Requests\Dashboard\Auth\RoleRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;
use App\Http\Requests\ActivateRequest;

/**
 * Class RoleController
 *
 * Handles role management operations for dashboard including:
 * CRUD actions, activation/deactivation, trash management and file uploads.
 */
class RoleController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var RoleService
     * The service containing business logic related to roles.
     */
    protected $roleService;

    /**
     * @var Role
     * The Role model instance.
     */
    protected $role;

    /**
     * RoleController constructor.
     * Dependency Injection for Role model, RoleService.
     *
     * @param Role $role
     * @param RoleService $roleService
     */
    public function __construct(Role $role, RoleService $roleService)
    {
        $this->role = $role;
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of roles.
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function index()
    {
        $result = $this->roleService->getData($this->role);  // Fetch role data (may be paginated or collection)

        if (isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('roles'), 'url' => route('dashboard.roles.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('Role/Index', [
                 'rows' => $result,                 // Role list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }

        // For API requests, respond with data wrapped in RoleResource
        return $this->respond($result, RoleResource::class);
    }

    /**
     * Show details of a specific role.
     *
     * @param int $id Role ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show($id)
    {
        $result = $this->roleService->show($id, $this->role); // Retrieve role details

        if (isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('roles'), 'url' => route('dashboard.roles.index')],
                ['label' => $result->rolename ?? __('Role')],
            ]);
        }

        // Respond with role data wrapped in RoleResource
        return $this->respond($result, RoleResource::class);
    }

    /**
     * Store a new role or update an existing one.
     *
     * @param RoleRequest $request Validated role creation/update request
     * @param int|null $id Role ID to update, or null to create new
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(RoleRequest $request, $id = null)
    {
        $result = $this->roleService->store($request, $this->role, $id); // Create or update role via service
        // Respond with status 201 Created and redirect route 'dashboard.roles.index'
        return $this->respond($result, RoleResource::class);
        //return $this->respond($result, RoleResource::class, $message = null, 'dashboard.roles.index');
    }

    /**
     * Update an existing role.
     *
     * @param RoleRequest $request Validated role update request
     * @param int $id Role ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(RoleRequest $request, $id)
    {
        // Update the role data by calling the RoleService
        $result = $this->roleService->update($request, $id, $this->role);
        // Return the response wrapped with RoleResource,
        // which formats the role data consistently for API or web responses
        return $this->respond($result, RoleResource::class);
    }

    /**
     * Toggle activation status (activate/deactivate) for a specific role.
     *
     * @param int $id Role ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivate(ActivateRequest $request, $id)
    {
        $result = $this->roleService->changeActivate($request, $id, $this->role);
        return $this->respond($result, RoleResource::class);
    }

    /**
     * Activate or deactivate multiple roles at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivateMany(BulkActionRequest $request)
    {
        $result = $this->roleService->changeActivateMany($request, $this->role);
        return $this->respond($result);
    }

    /**
     * Soft delete a role (mark as deleted without removing from DB).
     *
     * @param int $id Role ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $result = $this->roleService->destroy($id, $this->role);
        return $this->respond($result, RoleResource::class);
    }

    /**
     * Soft delete multiple roles at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        $result = $this->roleService->destroyMany($request, $this->role);
        return $this->respond($result);
    }

    /**
     * Permanently delete a role from the database.
     *
     * @param int $id Role ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        $result = $this->roleService->forceDelete($id, $this->role);
        return $this->respond($result);
    }

    /**
     * Permanently delete multiple roles at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        $result = $this->roleService->forceDeleteMany($request, $this->role);
        return $this->respond($result);
    }

    /**
     * Retrieve a list of roles who were soft deleted (in trash).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function trash()
    {
        $result = $this->roleService->trash($this->role);
        return $this->respond($result, RoleResource::class);
    }

    /**
     * Restore a soft deleted role.
     *
     * @param int $id Role ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $result = $this->roleService->restore($id, $this->role);
        return $this->respond($result, RoleResource::class);
    }

    /**
     * Restore multiple soft deleted roles at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreMany(BulkActionRequest $request)
    {
        $result = $this->roleService->restoreMany($request, $this->role);
        return $this->respond($result);
    }

}
