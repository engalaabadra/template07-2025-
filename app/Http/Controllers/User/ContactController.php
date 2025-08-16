<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Repositories\User\Contact\ContactRepository;
use App\Services\User\Contact\ContactService;
use App\Models\Contact;
use App\Resources\ContactResource;
use App\Http\Requests\File\UploadFilesRequest;
use App\Http\Requests\User\ContactRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;

/**
 * Class ContactController
 *
 * Handles contact management operations for dashboard including:
 * CRUD actions, activation/deactivation, trash management and file uploads.
 */
class ContactController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var ContactService
     * The service containing business logic related to contacts.
     */
    protected $contact;

    /**
     * @var Contact
     * The Contact model instance.
     */
    protected $contactService;

    /**
     * ContactController constructor.
     * Dependency Injection for Contact model, ContactService, and GeneralService.
     *
     * @param Contact $contact
     * @param ContactService $contactService
     * @param GeneralService $generalService
     */
    public function __construct(Contact $contact, ContactService $contactService, GeneralService $generalService)
    {
        $this->contact = $contact;
        $this->contactService = $contactService;
        $this->generalService = $generalService;
    }

    /**
     * Display a listing of contacts.
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function index()
    {
        $result = $this->contactService->getData($this->contact);  // Fetch contact data (may be paginated or collection)

        if ($this->isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('contacts'), 'url' => route('dashboard.contacts.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('Contact/Index', [
                 'rows' => $result,                 // Contact list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }

        // For API requests, respond with data wrapped in ContactResource
        return $this->respond($result, ContactResource::class);
    }

    /**
     * Show details of a specific contact.
     *
     * @param int $id Contact ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show($id)
    {
        $result = $this->contactService->show($id, $this->contact); // Retrieve contact details

        if ($this->isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('contacts'), 'url' => route('dashboard.contacts.index')],
                ['label' => $result->contactname ?? __('Contact')],
            ]);
        }

        // Respond with contact data wrapped in ContactResource
        return $this->respond($result, ContactResource::class);
    }

    /**
     * Store a new contact or update an existing one.
     *
     * @param ContactRequest $request Validated request data for storing/updating contact
     * @param int|null $id Contact ID to update; null to create new
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(ContactRequest $request, $id = null)
    {
        $result = $this->contactService->store($request, $this->contact, $id); // Create or update contact via service
        // Respond with status 201 Created and redirect route 'dashboard.contacts.index'
        return $this->respond($result, ContactResource::class);
        //return $this->respond($result, ContactResource::class, $message = null, 'dashboard.contacts.index');
    }

    /**
     * Update an existing contact.
     *
     * @param ContactRequest $request Validated request data
     * @param int $id Contact ID to update
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(ContactRequest $request, $id)
    {
        // Update the contact data by calling the ContactService
        $result = $this->contactService->update($request, $id, $this->contact);
        // Return the response wrapped with ContactResource,
        // which formats the contact data consistently for API or web responses
        return $this->respond($result, ContactResource::class);
    }

    /**
     * Toggle activation status (activate/deactivate) for a specific contact.
     *
     * @param int $id Contact ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivate($id)
    {
        // Toggle activation status (activate/deactivate) for a specific contact
        $result = $this->contactService->changeActivate($id, $this->contact);
        // Return the result wrapped with ContactResource
        return $this->respond($result, ContactResource::class);
    }

    /**
     * Activate or deactivate multiple contacts at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivateMany(BulkActionRequest $request)
    {
        // Activate or deactivate multiple contacts at once
        $result = $this->contactService->changeActivateMany($request, $this->contact);
        // Return the response directly (no resource wrapping, likely a simple success message)
        return $this->respond($result);
    }

    /**
     * Soft delete a contact (mark as deleted without removing from DB).
     *
     * @param int $id Contact ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Soft delete a contact (mark as deleted without removing from DB)
        $result = $this->contactService->destroy($id, $this->contact);
        // Return deleted contact data wrapped in ContactResource
        return $this->respond($result, ContactResource::class);
    }

    /**
     * Soft delete multiple contacts at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        // Soft delete multiple contacts at once
        $result = $this->contactService->destroyMany($request, $this->contact);
        // Return response directly (likely success message)
        return $this->respond($result);
    }

    /**
     * Permanently delete a contact from the database.
     *
     * @param int $id Contact ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        // Permanently delete a contact from the database
        $result = $this->contactService->forceDelete($id, $this->contact);
        // Return the result (often a success message or empty data)
        return $this->respond($result);
    }

    /**
     * Permanently delete multiple contacts at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        // Permanently delete multiple contacts at once
        $result = $this->contactService->forceDeleteMany($request, $this->contact);
        // Return the response
        return $this->respond($result);
    }

    /**
     * Retrieve a list of contacts who were soft deleted (in trash).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function trash()
    {
        // Retrieve a list of contacts who were soft deleted (in trash)
        $result = $this->contactService->trash($this->contact);
        // Return data wrapped in ContactResource for consistent formatting
        return $this->respond($result, ContactResource::class);
    }

    /**
     * Restore a soft deleted contact.
     *
     * @param int $id Contact ID to restore
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        // Restore a soft deleted contact
        $result = $this->contactService->restore($id, $this->contact);
        // Return the restored contact wrapped in ContactResource
        return $this->respond($result, ContactResource::class);
    }

    /**
     * Restore multiple soft deleted contacts at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreMany(BulkActionRequest $request)
    {
        // Restore multiple soft deleted contacts at once
        $result = $this->contactService->restoreMany($request, $this->contact);
        // Return response (usually success message)
        return $this->respond($result);
    }

    //==================== Files ====================//

    /**
     * Upload multiple files related to a contact.
     *
     * @param UploadFilesRequest $request Validated files upload request
     * @param int $id Contact ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function uploadFiles(UploadFilesRequest $request, $id)
    {
        // Prepare eager loading of 'files' relation to optimize queries
        $this->contact->setEagerLoading(['files']);
        // Upload multiple files related to a contact
        $result = $this->contactService->uploadFiles($request, $id, $this->contact);
        // Return contact data wrapped in ContactResource with updated files this contact
        return $this->respond($result, ContactResource::class);
    }

    /**
     * Delete multiple files associated with a contact.
     *
     * @param int $id Contact ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteFiles($id)
    {
        // Delete multiple files associated with a contact
        $result = $this->contactService->deleteFiles($id, $this->contact);
        // Return response, usually a simple success message, no resource wrapping needed
        return $this->respond($result);
    }
    
}
