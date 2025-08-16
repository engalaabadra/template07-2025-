<?php
namespace App\Repositories\Dashboard\Notification;

use App\Repositories\Eloquent\EloquentRepository;
use App\Repositories\Dashboard\Notification\NotifictionRepositoryInterface;

/**
 * NotificationRepository
 *
 * This is a base Repository class implementing the NotificationRepositoryInterface.
 * It provides methods such as : getData, search, show, trash
 */
class NotificationRepository extends EloquentRepository implements NotifictionRepositoryInterface
{
    

}
