<?php
namespace App\Repositories\Dashboard\Board;

use App\Repositories\Eloquent\EloquentRepository;

/**
 * BoardRepository
 *
 * This is a base Repository class implementing the BoardRepositoryInterface.
 * It provides methods such as : getData, search, show, trash
 */
class BoardRepository extends EloquentRepository implements BoardRepositoryInterface
{

}
