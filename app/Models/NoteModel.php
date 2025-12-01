<?php
namespace App\Models;

class NoteModel extends BaseModel
{
    protected $table = 'notes';
    protected $allowedFields = ['lesson_id','user_id','body'];
}
