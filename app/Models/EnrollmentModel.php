<?php
namespace App\Models;

class EnrollmentModel extends BaseModel
{
    protected $table = 'enrollments';
    protected $allowedFields = ['course_id','user_id','status','progress_percent'];
}
