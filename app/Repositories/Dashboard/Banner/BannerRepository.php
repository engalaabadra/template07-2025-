<?php
namespace App\Repositories\Dashboard\Banner;

use App\Repositories\Eloquent\EloquentRepository;

/**
 * BannerRepository
 *
 * This is a base Repository class implementing the BannerRepositoryInterface.
 * It provides methods such as : getData, search, show, trash
 */
class BannerRepository extends EloquentRepository implements BannerRepositoryInterface
{

}
