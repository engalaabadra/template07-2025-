<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseController;
use App\Repositories\Dashboard\Chat\ChatRepository;
use App\Services\Dashboard\Chat\ChatService;
use App\Models\Chat;
use App\Resources\ChatResource;
use App\Http\Requests\File\UploadFilesRequest;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Requests\Dashboard\ChatRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;
use App\Http\Requests\File\DeleteFilesRequest;

/**
 * Class ChatController
 *
 * Handles chat management operations for dashboard including:
 * CRUD actions, activation/deactivation, trash management and file uploads.
 */
class ChatController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var ChatService
     * The service containing business logic related to chats.
     */
    protected $chatService;

    /**
     * @var Chat
     * The Chat model instance.
     */
    protected $chat;

    /**
     * ChatController constructor.
     * Dependency Injection for Chat model, ChatService.
     *
     * @param Chat $chat
     * @param ChatService $chatService
     */
    public function __construct(Chat $chat, ChatService $chatService)
    {
        $this->chat = $chat;
        $this->chatService = $chatService;
    }

    /**
     * Display a listing of chats.
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function index()
    {
        $result = $this->chatService->getData($this->chat);  // Fetch chat data (may be paginated or collection)

        if (isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('chats'), 'url' => route('dashboard.chats.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('Chat/Index', [
                 'rows' => $result,                 // Chat list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }

        // For API requests, respond with data wrapped in ChatResource
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Show details of a specific chat.
     *
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show($id)
    {
        $result = $this->chatService->show($id, $this->chat); // Retrieve chat details

        if (isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('chats'), 'url' => route('dashboard.chats.index')],
                ['label' => $result->chatname ?? __('Chat')],
            ]);
        }

        // Respond with chat data wrapped in ChatResource
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Store a new chat or update an existing one.
     *
     * @param ChatRequest $request Validated chat creation/update request
     * @param int|null $id Chat ID to update, or null to create new
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(ChatRequest $request, $id = null)
    {
        $result = $this->chatService->store($request, $this->chat, $id); // Create or update chat via service
        // Respond with status 201 Created and redirect route 'dashboard.chats.index'
        return $this->respond($result, ChatResource::class);
        //return $this->respond($result, ChatResource::class, $message = null, 'dashboard.chats.index');
    }

    /**
     * Update an existing chat.
     *
     * @param ChatRequest $request Validated chat update request
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(ChatRequest $request, $id)
    {
        // Update the chat data by calling the ChatService
        $result = $this->chatService->update($request, $id, $this->chat);
        // Return the response wrapped with ChatResource,
        // which formats the chat data consistently for API or web responses
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Toggle activation status (activate/deactivate) for a specific chat.
     *
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivate($id)
    {
        $result = $this->chatService->changeActivate($id, $this->chat);
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Activate or deactivate multiple chats at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivateMany(BulkActionRequest $request)
    {
        $result = $this->chatService->changeActivateMany($request, $this->chat);
        return $this->respond($result);
    }

    /**
     * Soft delete a chat (mark as deleted without removing from DB).
     *
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $result = $this->chatService->destroy($id, $this->chat);
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Soft delete multiple chats at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        $result = $this->chatService->destroyMany($request, $this->chat);
        return $this->respond($result);
    }

    /**
     * Permanently delete a chat from the database.
     *
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        $result = $this->chatService->forceDelete($id, $this->chat);
        return $this->respond($result);
    }

    /**
     * Permanently delete multiple chats at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        $result = $this->chatService->forceDeleteMany($request, $this->chat);
        return $this->respond($result);
    }

    /**
     * Retrieve a list of chats who were soft deleted (in trash).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function trash()
    {
        $result = $this->chatService->trash($this->chat);
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Restore a soft deleted chat.
     *
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $result = $this->chatService->restore($id, $this->chat);
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Restore multiple soft deleted chats at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreMany(BulkActionRequest $request)
    {
        $result = $this->chatService->restoreMany($request, $this->chat);
        return $this->respond($result);
    }

    //==================== Files ====================//

    /**
     * Upload a single file (image or other) related to a chat.
     *
     * @param UploadImageRequest $request Validated image upload request
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function uploadFile(UploadImageRequest $request, $id)
    {
        $result = $this->chatService->uploadFile($request, $id, $this->chat);
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Upload multiple files related to a chat.
     *
     * @param UploadFilesRequest $request Validated files upload request
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function uploadFiles(UploadFilesRequest $request, $id)
    {
        $this->chat->setEagerLoading(['files']);
        $result = $this->chatService->uploadFiles($request, $id, $this->chat);
        return $this->respond($result, ChatResource::class);
    }

    /**
     * Delete a single file associated with a chat.
     *
     * @param int $id File ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteFile($id)
    {
        $result = $this->chatService->deleteFile($id, $this->chat);
        return $this->respond($result);
    }

    /**
     * Delete multiple files associated with a chat.
     *
     * @param int $id Chat ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteFiles(DeleteFilesRequest $request, $id)
    {
        $result = $this->chatService->deleteFiles($request, $id, $this->chat);
        return $this->respond($result);
    }
    
}
