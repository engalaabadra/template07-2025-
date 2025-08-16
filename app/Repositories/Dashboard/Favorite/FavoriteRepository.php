<?php
namespace App\Repositories\Dashboard\Favorite;

use App\Repositories\Eloquent\EloquentRepository;
use App\Scopes\LanguageScope;

/**
 * FavoriteRepository
 *
 * This is a base Repository class implementing the FavoriteRepositoryInterface.
 * It provides methods such as : getData, search, show, trash
 */
class FavoriteRepository extends EloquentRepository implements FavoriteRepositoryInterface
{

}
