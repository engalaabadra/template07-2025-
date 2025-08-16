<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Resources\BaseResource;
use App\Resources\FileResource;

/**
 * Class BannerResource
 *
 * Transforms a Banner model into a structured JSON response.
 * Inherits field rendering logic from BaseResource and appends
 * additional fields like image URLs.
 *
 * @property \App\Models\Banner $resource
 */
class BannerResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by including fields
     * from BaseResource (based on fillable attributes), and additional
     * fields such as related media or accessors.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            // Load main fields dynamically from BaseResource using fillable properties
            ...parent::toArray($request),

            // Load image URL if the 'image' relationship is loaded
            'image' => $this->whenLoaded('image', function () {
                return new FileResource($this->image);
            }),

        ];
    }
}
