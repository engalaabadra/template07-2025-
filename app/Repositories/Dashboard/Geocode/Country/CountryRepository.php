<?php
namespace App\Repositories\Dashboard\Geocode\Country;

use App\Repositories\Eloquent\EloquentRepository;
use App\Repositories\Dashboard\Geocode\Country\CountryRepositoryInterface;

/**
 * ContryRepository
 *
 * This is a base Repository class implementing the ContryRepositoryInterface.
 * It provides methods such as : getData, show, trash, report
 */
class CountryRepository extends EloquentRepository implements CountryRepositoryInterface
{

}
