<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use App\Resources\BaseResource;
use App\Resources\FileResource;
use App\Resources\ProfileResource;
use App\Resources\Auth\RoleResource;
use App\Resources\Geocode\CountryResource;

/**
 * Class UserResource
 *
 * A resource class responsible for transforming the User model 
 * and its related data (like profile, country, roles, etc.) 
 * into a structured API response.
 *
 * @property \App\Models\User $resource
 */
class UserResource extends BaseResource
{
    /*
        Example Output Structure:
        {
            "lang": "ar",
            "email": "employee@example.com",
            "username": "موظف1",
            "translations": [
                {
                    "lang": "en",
                    "username": "employee Name"
                },
                {
                    "lang": "fr",
                    "username": "Nom de l'enseignant"
                }
            ]
        }
    */

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
        // Get only the fillable attributes from the profile as an array
       // $profile = $this->profile?->only($this->profile?->getFillable());

        // Load translation data for profile if profile relation is loaded
        $translationData = $this->whenLoaded('profile', function () {
            return $this->getTranslationData($this->profile?->translations, \App\Models\Profile::class);
        });

        return [

            // Get main fields from model fillable from BaseResource
            ...parent::toArray($request),
            
            'email' => $this->email,
            // Map profile fillable fields if profile is loaded
            'profile' => $this->whenLoaded('profile', function () {
                return new ProfileResource($this->profile);
            }),

            // Load country name if country relation is loaded
            'country' => $this->whenLoaded('country', function () {
                return new CountryResource($this->country);
            }),

            // Indicates whether the related country is soft-deleted.
            // This will only be included if the 'country' relationship is loaded.
            // 'is_deleted_country' => $this->whenLoaded('country', fn () => $this->country?->trashed()),

            // Load assigned role names if roles relation is loaded
            'roles' => $this->whenLoaded('roles', function () {
                return RoleResource::collection($this->roles);
            }),

            // Include related files if the relation is loaded
            'files' => $this->whenLoaded('files', function () {
                return FileResource::collection($this->files);
            }),
            // Include image if image relation is loaded
            'image' => $this->whenLoaded('image', function () {
                return new FileResource($this->image);
            }),

            // Load translations from profile if available
            'translations' => $translationData,
        ];
    }
}
