<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use Psr\Log\LoggerInterface;

class BaseController extends Controller
{
    protected $helpers = ['form', 'url'];

    public function initController($request, $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }
}
