<?php
namespace App\Models;

class QuestionModel extends BaseModel
{
    protected $table = 'questions';
    protected $allowedFields = ['quiz_id','type','prompt','options','correct_answer','points'];
}
