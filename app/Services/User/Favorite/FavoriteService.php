<?php

namespace App\Services\User\Favorite;

use App\Services\Eloquent\EloquentService;
use App\Scopes\LanguageScope;

/**
 * Class FavoriteService
 *
 * This service handles business logic related to favorites.
 * Implements the FavoriteServiceInterface and provides functionality for:
 * - Storing a favorite (toggle: adds if not exists, removes if exists).
 *
 * @package App\Services\User\Favorite
 */
class FavoriteService extends EloquentService implements FavoriteServiceInterface
{
    #region ===================== Start CRUD Methods =====================

    /**
     * Store or toggle a favorite record for the authenticated user.
     *
     * If the favorite already exists for the given post by the user, it deletes it (toggle off).
     * If it doesn't exist, it creates a new favorite (toggle on).
     *
     * @param StoreFavoriteRequest $request  The validated request containing favorite data.
     * @param Favorite             $model    The Favorite model instance.
     *
     * @return object                        The created or deleted favorite record.
     */
    public function store($request, $model)
    {
        // Retrieve validated data from the request
        $data = $request->validated();
        // Assign the authenticated user's ID to the data
        $data['user_id'] = auth()->guard('api')->user()->id;

        // Check if the user has already favorited this post
        $favUserPost = $model->where([
            'user_id' => $data['user_id'],
            'post_id' => $data['post_id'],
        ])->first();

        // If favorite exists, delete it (toggle off)
        if ($favUserPost) {
            $favUserPost->delete();
            return $favUserPost;
        }
        // Otherwise, create a new favorite (toggle on)
        $item = $model->create($data);

        // Return the item with its eager-loaded relationships
        return $item->load($model->getEagerLoading());
    }
    #endregion ===================== End CRUD Methods =====================

    #region ===================== Start Protected & Private Methods =====================
    
    #endregion ===================== End Protected & Private Methods =====================

}
