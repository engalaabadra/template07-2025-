<?php
namespace App\Repositories\Dashboard\Review;

use App\Repositories\Eloquent\EloquentRepository;

/**
 * ReviewRepository
 *
 * This is a base Repository class implementing the ReviewRepositoryInterface.
 * It provides methods such as : getData, search, show, trash
 */
class ReviewRepository extends EloquentRepository implements ReviewRepositoryInterface
{

}
