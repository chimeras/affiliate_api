<?php
namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Files\File;

class FileController extends BaseController
{
    use ResponseTrait;

    public function streamVideo($path)
    {
        // In production, generate signed URL from storage provider.
        $filePath = WRITEPATH . 'videos/' . $path;
        if (! is_file($filePath)) {
            return $this->failNotFound();
        }
        return $this->response->download($filePath, null)->setFileName(basename($filePath));
    }

    public function serveAttachment($path)
    {
        $filePath = WRITEPATH . 'attachments/' . $path;
        if (! is_file($filePath)) {
            return $this->failNotFound();
        }
        return $this->response->download($filePath, null)->setFileName(basename($filePath));
    }
}
