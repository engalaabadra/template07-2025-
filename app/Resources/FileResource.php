<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use App\Resources\BaseResource;

/**
 * Class FileResource
 *
 * A resource class responsible for transforming the User model 
 * and its related data (like profile, country, roles, etc.) 
 * into a structured API response.
 *
 * @property \App\Models\User $resource
 */
class FileResource extends BaseResource
{
   
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by extending BaseResource 
     * and adding extra attributes such as profile data, translations, roles, etc.
     * 
     * @param  \Illuminate\Http\Request  $request  The current HTTP request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'fileable_id' => $this->fileable_id,
            'fileable_type' => $this->fileable_type,
            'type' => $this->type,
            'created_at_text' => $this->created_at_text

        ];
    }
}
