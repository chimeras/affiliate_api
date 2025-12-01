<?php
namespace App\Controllers;

use App\Models\LessonModel;
use App\Models\SectionModel;
use App\Models\NoteModel;
use CodeIgniter\API\ResponseTrait;

class LessonController extends BaseController
{
    use ResponseTrait;

    public function index($courseId)
    {
        $sections = (new SectionModel())->where('course_id', $courseId)->withLessons()->findAll();
        return $this->respond($sections);
    }

    public function create($courseId)
    {
        $sectionModel = new SectionModel();
        $sectionId = $sectionModel->insert([
            'course_id' => $courseId,
            'title' => $this->request->getPost('title'),
            'position' => $this->request->getPost('position') ?? 0,
        ]);
        return $this->respondCreated(['id' => $sectionId]);
    }

    public function show($lessonId)
    {
        $lesson = (new LessonModel())->find($lessonId);
        if (! $lesson) {
            return $this->failNotFound();
        }
        return view('lessons/show', ['lesson' => $lesson]);
    }

    public function update($id)
    {
        $model = new LessonModel();
        $model->update($id, $this->request->getRawInput());
        return $this->respondUpdated(['id' => $id]);
    }

    public function delete($id)
    {
        $model = new LessonModel();
        $model->delete($id);
        return $this->respondDeleted();
    }

    public function complete($lessonId)
    {
        // TODO: update progress via EnrollmentModel
        return $this->respond(['message' => 'Marked complete']);
    }

    public function storeNote($lessonId)
    {
        $noteModel = new NoteModel();
        $noteModel->insert([
            'lesson_id' => $lessonId,
            'user_id' => user_id(),
            'body' => $this->request->getPost('body')
        ]);
        return $this->respondCreated();
    }
}
