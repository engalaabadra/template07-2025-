<?php
namespace App\Repositories\User\Profile;

// use App\Models\User;
 
use App\Repositories\User\Profile\ProfileRepositoryInterface;
use Illuminate\Support\Arr;

class ProfileRepository implements ProfileRepositoryInterface
{

    /**
     * show user profile.
     * @param User $model
     * @return object || @return int
     */
    public function show($model){
        $query = $model->withoutGlobalScopes();
        // Find the record or return 404 if not found
        $item = $query->find(userApi()->id);
        return $model->getEagerLoading() ? $item->load($model->getEagerLoading()) : $item;
    
    }
}
