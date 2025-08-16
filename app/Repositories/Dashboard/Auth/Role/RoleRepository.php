<?php
namespace App\Repositories\Dashboard\Auth\Role;

use App\Repositories\Eloquent\EloquentRepository;

/**
 * RoleRepository
 *
 * This is a base Repository class implementing the RoleRepositoryInterface.
 * It provides methods such as : getData, export, report search, show, trash
 */
class RoleRepository extends EloquentRepository implements RoleRepositoryInterface
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
        if (!adminApi() && $item->name == cache()->get('mainRole')) // this person has auth not have auth admin api && need to show this user (hasRole-> superadmin) -> forrbidden
        {
            throw new ApiResponseException(ServiceResponseEnum::FORRBIDDEN, trans('messages.you cannt make any thing here'));
        }
        $data = $model->getEagerLoading()                 // Eager load relations if defined
            ? $item->load($model->getEagerLoading())
            : $item;

        return $data;
    }
}