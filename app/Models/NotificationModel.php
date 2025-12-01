<?php
namespace App\Models;

class NotificationModel extends BaseModel
{
    protected $table = 'notifications';
    protected $allowedFields = ['user_id','title','body','read_at'];
}
