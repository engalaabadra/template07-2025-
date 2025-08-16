<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Resources\BaseResource;
use App\Resources\UserBasicResource;

/**
 * Class ReviewResource
 *
 * A resource class used to transform the Review model for API responses.
 * Inherits from BaseResource and includes additional fields such as related user data.
 *
 * @property \App\Models\Review $resource
 */
class ReviewResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by extending BaseResource 
     * and adding extra fields like related user information.
     * 
     * @param  \Illuminate\Http\Request  $request  The current HTTP request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [

            // Include default fields from BaseResource (e.g., fillable attributes)
            ...parent::toArray($request),

            // Append related user’s  if 'user' relation is loaded
            'user' => new UserBasicResource($this->whenLoaded('user')),

        ];
    }
}
