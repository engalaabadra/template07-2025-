<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Resources\BaseResource;
use App\Resources\UserBasicResource;

/**
 * Class ChatResource
 *
 * Resource class responsible for transforming Chat model data into a JSON-friendly format.
 * This class utilizes Laravel's JsonResource and provides a clear structure for API responses,
 * including optional related data such as user, client, and files.
 *
 * @property \App\Models\Chat $resource
 */
class ChatResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * This method customizes the API representation by adding specific fields
     * or related data to the base resource response.
     * 
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request instance
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [

            // Include default attributes from the parent resource class
            ...parent::toArray($request),

            // Append related user’s  if 'user' relation is loaded
            'user' => $this->whenLoaded('user', fn() => $this->user?->username),

            // 'user' => $this->whenLoaded('user', function () {
            //     return new UserBasicResource($this->user);
            // }),
            
            // Append related client’s  if 'client' relation is loaded
           'client' => $this->whenLoaded('client', function () {
                return new UserBasicResource($this->client);
            }),

            // Include related files if the relation is loaded
            'files' => $this->whenLoaded('files', function () {
                return FileResource::collection($this->files);
            }),
            // Include the created_at timestamp
            'time' => $this->created_at,
        ];
    }
}
