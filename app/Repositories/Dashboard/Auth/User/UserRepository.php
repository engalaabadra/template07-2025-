<?php
namespace App\Repositories\Dashboard\Auth\User;

use App\Repositories\Eloquent\EloquentRepository;
use App\Repositories\Dashboard\Auth\User\UserRepositoryInterface;

/**
 * UserRepository
 *
 * This is a base Repository class implementing the UserRepositoryInterface.
 * It provides methods such as : getData, show, trash, report
 */
class UserRepository extends EloquentRepository implements UserRepositoryInterface
{

    /**
     * Show a specific record.
     *
     * @param int $id The ID of the record to show.
     * @param object $model The model to query.
     * @return object The requested record.
     */
    public function show($id, $model)
    {   
        $item = $this->findOrFailApi($id, $model);
        if (!adminApi() && $item->hasRole(cache()->get('mainRole'))) // this person has auth not have auth admin api && need to show this user (hasRole-> superadmin) -> forrbidden
        {
            throw new ApiResponseException(ServiceResponseEnum::FORRBIDDEN, trans('messages.you cannt make any thing here'));
        }
        $data = $model->getEagerLoading()                 // Eager load relations if defined
            ? $item->load($model->getEagerLoading())
            : $item;

        return $data;
    }
}
