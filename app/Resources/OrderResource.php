<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Resources\BaseResource;
use App\Resources\UserBasicResource;
use App\Resources\PaymentMethodResource;

/**
 * Class OrderResource
 *
 * A resource class used to transform the Order model for API responses.
 * It extends the BaseResource to include the model’s base fields and adds
 * any additional relationships or computed attributes as needed.
 *
 * @property \App\Models\Order $resource
 */
class OrderResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by merging the default
     * BaseResource fields with extra fields such as related usernames or payment methods.
     * 
     * @param  \Illuminate\Http\Request  $request  The current HTTP request instance
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [

            // Include the default fields defined in BaseResource (typically model fillables)
            ...parent::toArray($request),

            // Append related user’s  if 'user' relation is loaded
            'user' => $this->whenLoaded('user', function () {
                return new UserBasicResource($this->user);
            }),

            // Append payment method name if 'paymentMethod' relation is loaded
            'paymentMethod' => $this->whenLoaded('paymentMethod', function () {
                return new PaymentMethodResource($this->paymentMethod);
            }),

        ];
    }
}
