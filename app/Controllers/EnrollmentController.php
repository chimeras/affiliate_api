<?php
namespace App\Controllers;

use App\Models\EnrollmentModel;
use App\Models\CourseModel;
use CodeIgniter\API\ResponseTrait;

class EnrollmentController extends BaseController
{
    use ResponseTrait;

    public function enroll($courseId)
    {
        $course = (new CourseModel())->find($courseId);
        if (! $course) {
            return $this->failNotFound();
        }
        $enrollments = new EnrollmentModel();
        $existing = $enrollments->where(['course_id' => $courseId, 'user_id' => user_id()])->first();
        if ($existing) {
            return $this->respond(['message' => 'Already enrolled']);
        }
        $enrollments->insert([
            'course_id' => $courseId,
            'user_id' => user_id(),
            'status' => 'active'
        ]);
        return $this->respondCreated(['message' => 'Enrolled']);
    }

    public function progress($courseId)
    {
        $enrollments = new EnrollmentModel();
        $enrollment = $enrollments->where(['course_id' => $courseId, 'user_id' => user_id()])->first();
        if (! $enrollment) {
            return $this->failNotFound('Not enrolled');
        }
        return $this->respond(['progress_percent' => $enrollment['progress_percent']]);
    }
}
