<?php
namespace App\Controllers;

use App\Models\CourseModel;
use App\Models\CourseCategoryModel;
use App\Models\SectionModel;
use CodeIgniter\API\ResponseTrait;

class CourseController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $model = new CourseModel();
        return $this->respond($model->where('visibility', 'published')->findAll());
    }

    public function catalog()
    {
        $categories = (new CourseCategoryModel())->findAll();
        $courses = (new CourseModel())->where('visibility', 'published')->findAll();
        return view('pages/catalog', ['categories' => $categories, 'courses' => $courses]);
    }

    public function create()
    {
        $rules = [
            'title' => 'required',
            'summary' => 'required',
            'visibility' => 'required|in_list[draft,published]'
        ];
        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $model = new CourseModel();
        $data = $this->request->getPost([
            'title', 'summary', 'benefits', 'topics', 'eligibility', 'prerequisites',
            'level', 'price', 'thumbnail', 'tags', 'visibility'
        ]);
        $data['instructor_id'] = user_id();
        $id = $model->insert($data);
        return $this->respondCreated(['id' => $id]);
    }

    public function show($id)
    {
        $course = (new CourseModel())->withSections()->find($id);
        if (! $course) {
            return $this->failNotFound();
        }
        return view('courses/show', ['course' => $course]);
    }

    public function update($id)
    {
        $model = new CourseModel();
        if (! $model->find($id)) {
            return $this->failNotFound();
        }
        $model->update($id, $this->request->getRawInput());
        return $this->respondUpdated(['id' => $id]);
    }

    public function publish($id)
    {
        $model = new CourseModel();
        if (! $model->find($id)) {
            return $this->failNotFound();
        }
        $model->update($id, ['visibility' => 'published']);
        return $this->respond(['message' => 'Published']);
    }

    public function delete($id)
    {
        $model = new CourseModel();
        $model->delete($id);
        return $this->respondDeleted(['id' => $id]);
    }
}
