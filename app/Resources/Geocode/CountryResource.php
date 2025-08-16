<?php

namespace App\Resources\Geocode;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Resources\BaseResource;

/**
 * Class CountryResource
 *
 * Transforms a Country model into a structured JSON response.
 * Inherits main logic from BaseResource and allows for appending
 * additional attributes such as accessors or custom image paths.
 *
 * @property \App\Models\Country $resource
 */
class CountryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by extending BaseResource,
     * and allows the inclusion of extra fields like image paths or accessors.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            // Include the main fields dynamically from BaseResource (using $fillable)
            ...parent::toArray($request),

            // Add additional custom fields below if needed
        ];
    }
}
