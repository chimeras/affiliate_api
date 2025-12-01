<?php
namespace App\Models;

class LessonModel extends BaseModel
{
    protected $table = 'lessons';
    protected $allowedFields = [
        'section_id','title','content','video_url','attachment_path','position','type','duration_seconds'
    ];
}
