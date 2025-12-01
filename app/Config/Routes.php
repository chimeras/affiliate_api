<?php
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('auth', static function(RouteCollection $routes) {
    $routes->post('register', 'AuthController::register');
    $routes->post('login', 'AuthController::login');
    $routes->get('verify/(:alphanum)', 'AuthController::verify/$1');
    $routes->post('password/forgot', 'AuthController::forgot');
    $routes->post('password/reset', 'AuthController::reset');
    $routes->post('logout', 'AuthController::logout');
});

$routes->group('admin', ['filter' => 'role:admin'], static function(RouteCollection $routes) {
    $routes->resource('users', ['controller' => 'Admin\UserController']);
    $routes->resource('settings', ['controller' => 'Admin\SettingController']);
});

$routes->group('courses', static function(RouteCollection $routes) {
    $routes->get('/', 'CourseController::index');
    $routes->get('catalog', 'CourseController::catalog');
    $routes->post('/', 'CourseController::create', ['filter' => 'permission:create-course']);
    $routes->get('(:num)', 'CourseController::show/$1');
    $routes->put('(:num)', 'CourseController::update/$1', ['filter' => 'permission:edit-course']);
    $routes->delete('(:num)', 'CourseController::delete/$1', ['filter' => 'permission:delete-course']);

    $routes->post('(:num)/enroll', 'EnrollmentController::enroll/$1', ['filter' => 'role:student,instructor']);
    $routes->get('(:num)/progress', 'EnrollmentController::progress/$1', ['filter' => 'role:student']);
    $routes->post('(:num)/publish', 'CourseController::publish/$1', ['filter' => 'permission:publish-course']);

    $routes->resource('(:num)/sections', ['controller' => 'LessonController', 'placeholder' => 'courseId']);
});

$routes->group('lessons', static function(RouteCollection $routes) {
    $routes->get('(:num)', 'LessonController::show/$1');
    $routes->post('(:num)/complete', 'LessonController::complete/$1', ['filter' => 'role:student']);
    $routes->post('(:num)/notes', 'LessonController::storeNote/$1', ['filter' => 'role:student']);
});

$routes->group('quizzes', static function(RouteCollection $routes) {
    $routes->post('/', 'QuizController::create', ['filter' => 'permission:create-quiz']);
    $routes->get('(:num)', 'QuizController::show/$1');
    $routes->put('(:num)', 'QuizController::update/$1', ['filter' => 'permission:edit-quiz']);
    $routes->delete('(:num)', 'QuizController::delete/$1', ['filter' => 'permission:delete-quiz']);
    $routes->post('(:num)/attempt', 'QuizController::attempt/$1', ['filter' => 'role:student']);
});

$routes->group('files', static function(RouteCollection $routes) {
    $routes->get('video/(:segment)', 'FileController::streamVideo/$1');
    $routes->get('attachment/(:segment)', 'FileController::serveAttachment/$1');
});

$routes->group('notifications', ['filter' => 'role:student,instructor'], static function(RouteCollection $routes) {
    $routes->get('/', 'NotificationController::index');
    $routes->post('/', 'NotificationController::create', ['filter' => 'permission:send-notification']);
});
