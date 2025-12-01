<?php
namespace App\Models;

class CourseModel extends BaseModel
{
    protected $table = 'courses';
    protected $allowedFields = [
        'category_id','instructor_id','title','summary','benefits','topics','eligibility','prerequisites','level','price','thumbnail','tags','visibility'
    ];

    public function withSections()
    {
        $sections = (new SectionModel());
        $lessons = (new LessonModel());
        $courses = $this->findAll();
        foreach ($courses as &$course) {
            $course['sections'] = $sections->where('course_id', $course['id'])->orderBy('position')->findAll();
            foreach ($course['sections'] as &$section) {
                $section['lessons'] = $lessons->where('section_id', $section['id'])->orderBy('position')->findAll();
            }
        }
        return $courses;
    }
}
