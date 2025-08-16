<?php

namespace App\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use App\Resources\BaseResource;
use App\Resources\Auth\PermissionResource;

/**
 * Class RoleResource
 *
 * Transforms a Role model into a structured JSON response.
 * Dynamically includes fillable attributes, permissions, and translations.
 */
class RoleResource extends BaseResource
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
            // Get main fields from model fillable from BaseResource
            ...parent::toArray($request),
            // Add permissions if the relationship is loaded
            'permissions' => $this->whenLoaded('permissions', function () {
                return PermissionResource::collection($this->permissions);
            }),
        ];
    }
}
