<?php
namespace App\Models;

class AttemptModel extends BaseModel
{
    protected $table = 'quiz_attempts';
    protected $allowedFields = ['quiz_id','user_id','score','status'];
}
