<?php
namespace App\Models;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $allowedFields = ['email', 'password_hash', 'role', 'status', 'verification_token', 'reset_token'];
}
