<?php

namespace App\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

/**
 * Class PermissionResource
 *
 * Transforms a permission model into a structured JSON response,
 * dynamically including fillable attributes and translations.
 */
class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
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
