<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseController;
use App\Repositories\Dashboard\Board\BoardRepository;
use App\Services\Dashboard\Board\BoardService;
use App\Models\Board;
use App\Resources\BoardResource;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Requests\Dashboard\BoardRequest;
use App\Traits\Controllers\UIHelpersTrait;
use Inertia\Inertia;
use App\Http\Requests\BulkActionRequest;

/**
 * Class BoardController
 *
 * Handles board management operations for dashboard including:
 * CRUD actions, activation/deactivation, trash management and file uploads.
 */
class BoardController extends BaseController
{
    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var BoardService
     * The service containing business logic related to boards.
     */
    protected $boardService;

    /**
     * @var Board
     * The Board model instance.
     */
    protected $board;

    /**
     * BoardController constructor.
     * Dependency Injection for Board model, BoardService.
     *
     * @param Board $board
     * @param BoardService $boardService
     */
    public function __construct(Board $board, BoardService $boardService)
    {
        $this->board = $board;
        $this->boardService = $boardService;
    }

    /**
     * Display a listing of boards.
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function index()
    {
        $result = $this->boardService->getData($this->board);  // Fetch board data (may be paginated or collection)

        if (isWebRequest()) { // Check if request is from web (not API)
            $this->breadcrumb([
                ['label' => __('boards'), 'url' => route('dashboard.boards.index')],
            ]);  // Setup breadcrumb navigation

             // Render the web page with Inertia and pass necessary data
            return $this->renderWebIndexPage('Board/Index', [
                 'rows' => $result,                 // Board list data
                'form_data' => $this->getCreateUpdateData(), // Form data for create/update
            ]);
        }

        // For API requests, respond with data wrapped in BoardResource
        return $this->respond($result, BoardResource::class);
    }

    /**
     * Show details of a specific board.
     *
     * @param int $id Board ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show($id)
    {
        $result = $this->boardService->show($id, $this->board); // Retrieve board details

        if (isWebRequest()) { // If web request, setup breadcrumb navigation
            $this->breadcrumb([
                ['label' => __('boards'), 'url' => route('dashboard.boards.index')],
                ['label' => $result->boardname ?? __('Board')],
            ]);
        }

        // Respond with board data wrapped in BoardResource
        return $this->respond($result, BoardResource::class);
    }

    /**
     * Store a new board or update an existing one.
     *
     * @param BoardRequest $request Validated board creation/update request
     * @param int|null $id Board ID to update, or null to create new
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(BoardRequest $request, $id = null)
    {
        $result = $this->boardService->store($request, $this->board, $id); // Create or update board via service
        // Respond with status 201 Created and redirect route 'dashboard.boards.index'
        return $this->respond($result, BoardResource::class);
        //return $this->respond($result, BoardResource::class, $message = null, 'dashboard.boards.index');
    }

    /**
     * Update an existing board.
     *
     * @param BoardRequest $request Validated board update request
     * @param int $id Board ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(BoardRequest $request, $id)
    {
        // Update the board data by calling the BoardService
        $result = $this->boardService->update($request, $id, $this->board);
        // Return the response wrapped with BoardResource,
        // which formats the board data consistently for API or web responses
        return $this->respond($result, BoardResource::class);
    }

    /**
     * Toggle activation status (activate/deactivate) for a specific board.
     *
     * @param int $id Board ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivate($id)
    {
        $result = $this->boardService->changeActivate($id, $this->board);
        return $this->respond($result, BoardResource::class);
    }

    /**
     * Activate or deactivate multiple boards at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function changeActivateMany(BulkActionRequest $request)
    {
        $result = $this->boardService->changeActivateMany($request, $this->board);
        return $this->respond($result);
    }

    /**
     * Soft delete a board (mark as deleted without removing from DB).
     *
     * @param int $id Board ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $result = $this->boardService->destroy($id, $this->board);
        return $this->respond($result, BoardResource::class);
    }

    /**
     * Soft delete multiple boards at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyMany(BulkActionRequest $request)
    {
        $result = $this->boardService->destroyMany($request, $this->board);
        return $this->respond($result);
    }

    /**
     * Permanently delete a board from the database.
     *
     * @param int $id Board ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        $result = $this->boardService->forceDelete($id, $this->board);
        return $this->respond($result);
    }

    /**
     * Permanently delete multiple boards at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceDeleteMany(BulkActionRequest $request)
    {
        $result = $this->boardService->forceDeleteMany($request, $this->board);
        return $this->respond($result);
    }

    /**
     * Retrieve a list of boards who were soft deleted (in trash).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function trash()
    {
        $result = $this->boardService->trash($this->board);
        return $this->respond($result, BoardResource::class);
    }

    /**
     * Restore a soft deleted board.
     *
     * @param int $id Board ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $result = $this->boardService->restore($id, $this->board);
        return $this->respond($result, BoardResource::class);
    }

    /**
     * Restore multiple soft deleted boards at once.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function restoreMany(BulkActionRequest $request)
    {
        $result = $this->boardService->restoreMany($request, $this->board);
        return $this->respond($result);
    }

    //==================== Files ====================//

    /**
     * Upload a single file (image or other) related to a board.
     *
     * @param UploadImageRequest $request Validated image upload request
     * @param int $id Board ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function uploadFile(UploadImageRequest $request, $id)
    {
        $result = $this->boardService->uploadFile($request, $id, $this->board);
        return $this->respond($result, BoardResource::class);
    }

    /**
     * Delete a single file associated with a board.
     *
     * @param int $id File ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteFile($id)
    {
        $result = $this->boardService->deleteFile($id, $this->board);
        return $this->respond($result);
    }

    
}
