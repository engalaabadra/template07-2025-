<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Resources\UserBasicResource;

/**
 * Class FavoriteResource
 *
 * A resource class responsible for formatting Favorite model data for API responses.
 * Extends JsonResource and leverages the base transformation while allowing the
 * inclusion of related data such as the associated user's username.
 *
 * @property \App\Models\Favorite $resource
 */
class FavoriteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by extending the parent transformation,
     * and optionally includes extra fields such as image paths, accessors, or relationships.
     * 
     * @param  \Illuminate\Http\Request  $request  The current HTTP request instance
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [

            // Include the default fields from the parent resource (e.g., fillable model attributes)
            ...parent::toArray($request),

            // Append related user’s  if 'user' relation is loaded
            'user' => $this->whenLoaded('user', function () {
                return new UserBasicResource($this->user);
            }),

        ];
    }
}
