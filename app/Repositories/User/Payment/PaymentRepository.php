<?php
namespace App\Repositories\User\Payment;

use App\Repositories\PaymentMethodRepositoryInterface;
use App\Repositories\Eloquent\EloquentRepository;
 
use Illuminate\Support\Facades\Session;
use  App\Entities\PaymentMethod;
use App\Services\Payment\PaymentGateways\PaymentGatewayFactory\PaymentGatewayFactory;

/**
 * PaymentRepository
 *
 * This is a base Repository class implementing the PaymentRepositoryInterface.
 * It provides methods such as : getData, search, show, trash
 */
class PaymentRepository extends EloquentRepository implements PaymentMethodRepositoryInterface
{
    use  PaymentMethod;

    public function callback(){
        //will get gateway from payment id in session that put it in create meth 
        $gateway = PaymentMethod::where('id',Session::get('payment_method_id'))->first();
        return $gateway->verify($gateway);//return object payment
    }

    

}