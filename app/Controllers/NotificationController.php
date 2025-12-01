<?php
namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\API\ResponseTrait;

class NotificationController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $model = new NotificationModel();
        $notifications = $model->where('user_id', user_id())->orderBy('created_at', 'DESC')->findAll();
        return $this->respond($notifications);
    }

    public function create()
    {
        $model = new NotificationModel();
        $id = $model->insert([
            'user_id' => $this->request->getPost('user_id'),
            'title' => $this->request->getPost('title'),
            'body' => $this->request->getPost('body'),
        ]);
        // TODO: push to queue worker
        return $this->respondCreated(['id' => $id]);
    }
}
