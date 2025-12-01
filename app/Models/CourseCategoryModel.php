<?php
namespace App\Models;

class CourseCategoryModel extends BaseModel
{
    protected $table = 'course_categories';
    protected $allowedFields = ['name', 'slug', 'description'];
}
