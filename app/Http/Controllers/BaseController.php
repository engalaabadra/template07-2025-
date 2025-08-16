<?php

namespace App\Http\Controllers;

use App\Traits\Controllers\WebApiSuccessResponseTrait;
use App\Http\Controllers\Controller;
use App\Services\ServiceResponse;

/**
 * Class BaseController
 *
 * Acts as a shared base controller for other controllers in the application.
 * Includes reusable traits and common logic for consistent response formatting.
 */
abstract class BaseController extends Controller
{
    // Include a trait that provides helper methods for API and web responses
    use WebApiSuccessResponseTrait;
}
