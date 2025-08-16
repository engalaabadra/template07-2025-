<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Models\Board;
use App\Services\User\Board\BoardService;
use App\Resources\BoardResource;
use Inertia\Inertia;

/**
 * Class BoardController
 *
 * This controller handles retrieving and listing board records
 * for both API and web (Inertia) responses.
 */
class BoardController extends BaseController
{

    use UIHelpersTrait; // Include UI helper functions trait

    /**
     * @var BoardService
     * Service that contains business logic for boards.
     */
    protected $boardService;

    /**
     * @var Board
     * Board model instance.
     */
    protected $board;

    /**
     * BoardController constructor.
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
     *
     * Handles both Web and API responses.
     * - Web: returns an Inertia view or downloadable file.
     * - API: returns JSON or downloadable file.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
     public function index()
    {
        $result = $this->boardService->getData($this->board);  // Fetch board data (may be paginated or collection)

        if ($this->isWebRequest()) { // Check if request is from web (not API)
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
}
