<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Resources\BaseResource;

/**
 * Class BoardResource
 *
 * Resource class used to transform Board model data for JSON responses.
 * It extends the BaseResource to include default fillable fields and 
 * optionally adds extra attributes like image paths or accessors.
 *
 * @property \App\Models\Board $resource
 */
class BoardResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by extending BaseResource
     * with additional attributes such as image paths or accessors.
     * 
     * @param  \Illuminate\Http\Request  $request  The current HTTP request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [

            // Include default fillable fields from BaseResource
            ...parent::toArray($request),

            // Add extra attributes or accessors specific to the Board model
            'image' => $this->whenLoaded('image', function () {
                return new FileResource($this->image);
            }),
        ];
    }
}
