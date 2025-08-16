<?php
namespace App\Repositories\User\Payment;

use App\Repositories\User\Payment\PaymentMethodRepositoryInterface;
use App\Repositories\Eloquent\EloquentRepository;
use Illuminate\Support\Facades\Session;

/**
 * PaymentRepository
 *
 * This is a base Repository class implementing the PaymentRepositoryInterface.
 * It provides methods such as : getData, search, show, trash
 */
class PaymentMethodRepository extends EloquentRepository implements PaymentMethodRepositoryInterface
{

}
