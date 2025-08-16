<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Resources\UserBasicResource;

/**
 * Class ProfileResource
 *
 * A resource class used to transform the Profile model for API responses.
 * Extends JsonResource to return structured and customizable output,
 * including related user data and appended attributes like the profile image.
 *
 * @property \App\Models\Profile $resource
 */
class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by including default model data
     * from the base JsonResource and appending related or computed fields.
     * 
     * @param  \Illuminate\Http\Request  $request  The current HTTP request instance
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [

            // Include default fields from the parent JsonResource (usually fillable fields)
            ...parent::toArray($request),

            // Append related user’s  if 'user' relation is loaded
            'user' => $this->whenLoaded('user', function () {
                return new UserBasicResource($this->user);
            }),
        ];
    }
}
