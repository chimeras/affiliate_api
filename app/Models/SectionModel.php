<?php
namespace App\Models;

class SectionModel extends BaseModel
{
    protected $table = 'sections';
    protected $allowedFields = ['course_id','title','position'];

    public function withLessons()
    {
        $lessons = (new LessonModel());
        $sections = $this->findAll();
        foreach ($sections as &$section) {
            $section['lessons'] = $lessons->where('section_id', $section['id'])->orderBy('position')->findAll();
        }
        return $sections;
    }
}
