# CodeIgniter 4 LMS Blueprint

## (A) Architecture & Security Overview
- **Tech stack**: CodeIgniter 4, PHP 8.2, MySQL 8 (PostgreSQL alt), Redis queue, Bootstrap 5, Stimulus/Alpine for light interactivity, PHPUnit for tests, nginx/php-fpm.
- **Layers**: 
  - Web (nginx) ➜ CI4 controllers ➜ Services (auth, courses, quizzes, notifications) ➜ Repositories/Models ➜ MySQL.
  - Background worker (CLI command) consumes Redis queue for email/notification dispatch.
  - Object storage (S3-compatible) for videos/attachments; CDN + signed HLS URLs.
- **Security**:
  - Passwords via `password_hash` (bcrypt/argon2id), CSRF enabled, HTTPS-only cookies, SameSite=Lax.
  - Role-based filters: `admin`, `instructor`, `student`; instructor permissions stored in JSON.
  - Attachments served through controller with permission checks; signed URLs for HLS video, range-limited responses.
  - Input validation via CI4 Validation; file upload whitelist (PDF, docx, mp4/hls manifests).
  - Rate limiting login/reset via `Filters` + cache.

## (B) Database Schema (MySQL DDL)
```sql
CREATE TABLE roles (
  id TINYINT UNSIGNED PRIMARY KEY,
  name VARCHAR(32) UNIQUE NOT NULL
);
INSERT INTO roles (id, name) VALUES (1,'admin'),(2,'instructor'),(3,'student');

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id TINYINT UNSIGNED NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(80),
  last_name VARCHAR(80),
  is_active TINYINT(1) DEFAULT 0,
  email_verified_at DATETIME NULL,
  reset_token VARCHAR(191) NULL,
  remember_token VARCHAR(100) NULL,
  instructor_permissions JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
);
CREATE INDEX idx_users_email_active ON users(email,is_active);

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(150) UNIQUE NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id)
);
CREATE INDEX idx_categories_parent ON categories(parent_id);

CREATE TABLE courses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NULL,
  instructor_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  summary TEXT,
  benefits TEXT,
  topics TEXT,
  eligibility TEXT,
  prerequisites TEXT,
  level ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
  price DECIMAL(10,2) NULL,
  thumbnail VARCHAR(255) NULL,
  tags JSON NULL,
  visibility ENUM('draft','published') DEFAULT 'draft',
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_courses_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_courses_instructor FOREIGN KEY (instructor_id) REFERENCES users(id)
);
CREATE INDEX idx_courses_visibility ON courses(visibility,is_active);

CREATE TABLE course_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  position INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sections_course FOREIGN KEY (course_id) REFERENCES courses(id)
);
CREATE INDEX idx_sections_course_position ON course_sections(course_id, position);

CREATE TABLE lessons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  content LONGTEXT NULL,
  video_url VARCHAR(255) NULL,
  attachment_path VARCHAR(255) NULL,
  chart_json JSON NULL,
  type ENUM('video','reading','quiz','assignment') DEFAULT 'reading',
  position INT UNSIGNED NOT NULL,
  is_preview TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lessons_section FOREIGN KEY (section_id) REFERENCES course_sections(id)
);
CREATE INDEX idx_lessons_section_position ON lessons(section_id, position);

CREATE TABLE quizzes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id BIGINT UNSIGNED NOT NULL,
  lesson_id BIGINT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  time_limit INT NULL,
  passing_score INT DEFAULT 70,
  is_randomized TINYINT(1) DEFAULT 0,
  pool_size INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_quiz_course FOREIGN KEY (course_id) REFERENCES courses(id),
  CONSTRAINT fk_quiz_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id)
);

CREATE TABLE question_banks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  topic VARCHAR(150) NULL,
  level VARCHAR(50) NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_qbank_user FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE questions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_id BIGINT UNSIGNED NULL,
  quiz_id BIGINT UNSIGNED NULL,
  type ENUM('mcq_single','mcq_multi','true_false','short','essay') NOT NULL,
  prompt TEXT NOT NULL,
  options JSON NULL,
  correct_answers JSON NULL,
  points INT DEFAULT 1,
  topic VARCHAR(150) NULL,
  level VARCHAR(50) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_questions_bank FOREIGN KEY (bank_id) REFERENCES question_banks(id),
  CONSTRAINT fk_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);
CREATE INDEX idx_questions_topic_level ON questions(topic, level);

CREATE TABLE enrollments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active','completed','dropped') DEFAULT 'active',
  progress DECIMAL(5,2) DEFAULT 0,
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  UNIQUE KEY uq_enrollment (course_id, user_id),
  CONSTRAINT fk_enroll_course FOREIGN KEY (course_id) REFERENCES courses(id),
  CONSTRAINT fk_enroll_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE lesson_progress (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  lesson_id BIGINT UNSIGNED NOT NULL,
  status ENUM('not_started','in_progress','completed') DEFAULT 'not_started',
  time_spent INT DEFAULT 0,
  completed_at DATETIME NULL,
  UNIQUE KEY uq_lesson_progress (enrollment_id, lesson_id),
  CONSTRAINT fk_lp_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id),
  CONSTRAINT fk_lp_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id)
);

CREATE TABLE quiz_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id BIGINT UNSIGNED NOT NULL,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  score INT DEFAULT 0,
  max_score INT DEFAULT 0,
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  submitted_at DATETIME NULL,
  duration_seconds INT NULL,
  status ENUM('in_progress','submitted','graded') DEFAULT 'in_progress',
  CONSTRAINT fk_attempt_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
  CONSTRAINT fk_attempt_enroll FOREIGN KEY (enrollment_id) REFERENCES enrollments(id)
);
CREATE INDEX idx_attempts_quiz_enroll ON quiz_attempts(quiz_id, enrollment_id);

CREATE TABLE quiz_attempt_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  answer JSON NULL,
  score INT DEFAULT 0,
  CONSTRAINT fk_qaa_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id),
  CONSTRAINT fk_qaa_question FOREIGN KEY (question_id) REFERENCES questions(id)
);

CREATE TABLE notes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_notes_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id),
  CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX idx_notes_user_lesson ON notes(user_id, lesson_id);

CREATE TABLE glossaries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id BIGINT UNSIGNED NULL,
  term VARCHAR(200) NOT NULL,
  definition TEXT NOT NULL,
  is_public TINYINT(1) DEFAULT 1,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_glossary_course FOREIGN KEY (course_id) REFERENCES courses(id),
  CONSTRAINT fk_glossary_user FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE FULLTEXT INDEX ft_glossaries_term ON glossaries(term);

CREATE TABLE expression_banks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic VARCHAR(150) NOT NULL,
  level VARCHAR(50) NOT NULL,
  expression TEXT NOT NULL,
  translation TEXT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_expr_user FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(100) NOT NULL,
  data JSON NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

CREATE TABLE reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  is_moderated TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_course FOREIGN KEY (course_id) REFERENCES courses(id),
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX idx_reviews_course ON reviews(course_id);
```

## (C) Key CodeIgniter 4 Snippets

### Folder Structure (excerpt)
```
app/
  Controllers/
    Auth.php
    Courses.php
    Lessons.php
    Quizzes.php
    Enrollment.php
    Files.php
  Models/
    UserModel.php
    CourseModel.php
    SectionModel.php
    LessonModel.php
    QuizModel.php
    QuestionModel.php
    EnrollmentModel.php
    QuizAttemptModel.php
  Filters/
    AuthFilter.php
    RoleFilter.php
  Commands/
    QueueWorker.php
  Views/
    auth/
    courses/
    lessons/
    quizzes/
```

### Migration Examples
`app/Database/Migrations/2024-01-01-000000_CreateUsers.php`
```php
<?php namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'BIGINT', 'unsigned'=>true, 'auto_increment'=>true],
            'role_id' => ['type'=>'TINYINT', 'unsigned'=>true],
            'email' => ['type'=>'VARCHAR', 'constraint'=>191, 'unique'=>true],
            'password_hash' => ['type'=>'VARCHAR', 'constraint'=>255],
            'first_name' => ['type'=>'VARCHAR', 'constraint'=>80, 'null'=>true],
            'last_name' => ['type'=>'VARCHAR', 'constraint'=>80, 'null'=>true],
            'is_active' => ['type'=>'TINYINT', 'default'=>0],
            'email_verified_at' => ['type'=>'DATETIME', 'null'=>true],
            'reset_token' => ['type'=>'VARCHAR', 'constraint'=>191, 'null'=>true],
            'remember_token' => ['type'=>'VARCHAR', 'constraint'=>100, 'null'=>true],
            'instructor_permissions' => ['type'=>'JSON', 'null'=>true],
            'created_at' => ['type'=>'DATETIME', 'null'=>true],
            'updated_at' => ['type'=>'DATETIME', 'null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('role_id','roles','id','CASCADE','CASCADE');
        $this->forge->createTable('users');
    }
    public function down()
    { $this->forge->dropTable('users'); }
}
```

### Models
`app/Models/CourseModel.php`
```php
<?php namespace App\Models;
use CodeIgniter\Model;
class CourseModel extends Model
{
    protected $table = 'courses';
    protected $primaryKey = 'id';
    protected $allowedFields = ['category_id','instructor_id','title','summary','benefits','topics','eligibility','prerequisites','level','price','thumbnail','tags','visibility','is_active'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
```

`app/Models/QuizAttemptModel.php`
```php
<?php namespace App\Models;
use CodeIgniter\Model;
class QuizAttemptModel extends Model
{
    protected $table = 'quiz_attempts';
    protected $allowedFields = ['quiz_id','enrollment_id','score','max_score','started_at','submitted_at','duration_seconds','status'];
    protected $useTimestamps = false;
}
```

### Filters for Auth/Role
`app/Filters/AuthFilter.php`
```php
<?php namespace App\Filters;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('user_id')) {
            return redirect()->to('/login');
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null){}
}
```

`app/Filters/RoleFilter.php`
```php
<?php namespace App\Filters;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = session()->get('role');
        if (!$role || !in_array($role, $arguments ?? [])) {
            return redirect()->to('/forbidden');
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null){}
}
```

### Auth Controller (excerpt)
`app/Controllers/Auth.php`
```php
<?php namespace App\Controllers;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
class Auth extends ResourceController
{
    public function register()
    {
        $rules = [
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'role_id' => 'required|in_list[2,3]'
        ];
        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $userModel = new UserModel();
        $id = $userModel->insert([
            'email' => $this->request->getPost('email'),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role_id' => $this->request->getPost('role_id')
        ]);
        // TODO: dispatch email verification job
        return $this->respondCreated(['id'=>$id]);
    }

    public function login()
    {
        $userModel = new UserModel();
        $user = $userModel->where('email',$this->request->getPost('email'))->first();
        if(!$user || !password_verify($this->request->getPost('password'), $user['password_hash'])) {
            return $this->fail('Invalid credentials');
        }
        if(!$user['is_active']) return $this->fail('Account inactive');
        session()->set(['user_id'=>$user['id'], 'role'=>$user['role_id']]);
        return $this->respond(['message'=>'logged in']);
    }
}
```

### Courses Controller (REST excerpt)
`app/Controllers/Courses.php`
```php
<?php namespace App\Controllers;
use App\Models\CourseModel;
use CodeIgniter\RESTful\ResourceController;
class Courses extends ResourceController
{
    protected $modelName = CourseModel::class;
    protected $format = 'json';

    protected function authorizePublish($course)
    {
        $role = session()->get('role');
        $perms = session()->get('instructor_permissions') ?? [];
        return $role == 1 || ($role == 2 && ($perms['can_publish'] ?? false) && $course['instructor_id']==session()->get('user_id'));
    }

    public function create()
    {
        $data = $this->request->getPost();
        $data['instructor_id'] = session()->get('user_id');
        if (! $this->validate([
            'title'=>'required|min_length[3]',
            'visibility'=>'permit_empty|in_list[draft,published]'
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $id = $this->model->insert($data);
        return $this->respondCreated(['id'=>$id]);
    }

    public function publish($id)
    {
        $course = $this->model->find($id);
        if(!$course || ! $this->authorizePublish($course)) return $this->failForbidden();
        $this->model->update($id,['visibility'=>'published']);
        return $this->respond(['status'=>'published']);
    }
}
```

### Lessons + File Serving
`app/Controllers/Files.php`
```php
<?php namespace App\Controllers;
use CodeIgniter\Controller;
use CodeIgniter\Files\File;
class Files extends Controller
{
    public function attachment($lessonId)
    {
        // confirm enrollment
        $enrolled = model('LessonModel')->userHasAccess($lessonId, session()->get('user_id'));
        if(!$enrolled) return redirect()->to('/forbidden');
        $lesson = model('LessonModel')->find($lessonId);
        $path = WRITEPATH.'uploads/'.$lesson['attachment_path'];
        return $this->response->download($path, null)->setFileName(basename($path));
    }
}
```

### Quiz Auto-Grading Service (excerpt)
`app/Services/QuizGrader.php`
```php
<?php namespace App\Services;
class QuizGrader
{
    public function grade(array $questions, array $answers): array
    {
        $score = 0; $max = 0; $details = [];
        foreach ($questions as $q) {
            $max += (int)$q['points'];
            $userAnswer = $answers[$q['id']] ?? null;
            $earned = 0;
            switch ($q['type']) {
                case 'mcq_single':
                case 'true_false':
                    $earned = ($userAnswer == $q['correct_answers'][0]) ? $q['points'] : 0; break;
                case 'mcq_multi':
                    $correct = $q['correct_answers']; sort($correct);
                    $user = $userAnswer ?? []; sort($user);
                    $earned = ($correct == $user) ? $q['points'] : 0; break;
                case 'short':
                    $earned = (strtolower(trim($userAnswer)) == strtolower(trim($q['correct_answers'][0] ?? ''))) ? $q['points'] : 0; break;
                case 'essay':
                    $earned = 0; // manual grading later
                    break;
            }
            $score += $earned;
            $details[] = ['question_id'=>$q['id'],'earned'=>$earned];
        }
        return ['score'=>$score,'max'=>$max,'details'=>$details];
    }
}
```

### Enrollment/Progress Controller (excerpt)
`app/Controllers/Enrollment.php`
```php
<?php namespace App\Controllers;
use App\Models\EnrollmentModel;
use App\Models\LessonProgressModel;
use CodeIgniter\RESTful\ResourceController;
class Enrollment extends ResourceController
{
    public function enroll($courseId)
    {
        $enroll = new EnrollmentModel();
        $id = $enroll->insert(['course_id'=>$courseId,'user_id'=>session()->get('user_id')]);
        // queue notification
        service('queue')->push('email', ['type'=>'enrolled','user_id'=>session()->get('user_id'),'course_id'=>$courseId]);
        return $this->respondCreated(['enrollment_id'=>$id]);
    }

    public function completeLesson($lessonId)
    {
        $lp = new LessonProgressModel();
        $progress = $lp->markCompleted($lessonId, session()->get('user_id'));
        return $this->respond($progress);
    }
}
```

### Queue Worker Command (email/notification)
`app/Commands/QueueWorker.php`
```php
<?php namespace App\Commands;
use CodeIgniter\CLI\BaseCommand;
class QueueWorker extends BaseCommand
{
    protected $group = 'Queue';
    protected $name = 'queue:work';
    protected $description = 'Process notification/email queue';

    public function run(array $params)
    {
        $queue = service('queue');
        while (true) {
            if ($job = $queue->pop()) {
                try { $this->handle($job); }
                catch (\Throwable $e) { log_message('error',$e->getMessage()); }
            }
            sleep(1);
        }
    }
    private function handle($job)
    {
        if ($job['type']==='email') { service('email')->send($job['to'],$job['subject'],$job['view'],$job['data']); }
    }
}
```

### Unit Test Examples
`tests/app/AuthTest.php`
```php
<?php
use CodeIgniter\Test\FeatureTestTrait;
class AuthTest extends \Tests\Support\TestCase
{
    use FeatureTestTrait;
    public function testRegister()
    {
        $result = $this->post('/register',['email'=>'a@b.com','password'=>'secret123','role_id'=>3]);
        $result->assertStatus(201);
    }
}
```

`tests/app/QuizGraderTest.php`
```php
<?php
use App\Services\QuizGrader;
class QuizGraderTest extends \PHPUnit\Framework\TestCase
{
    public function testGradesMultiSelect()
    {
        $grader = new QuizGrader();
        $res = $grader->grade([
            ['id'=>1,'type'=>'mcq_multi','points'=>2,'correct_answers'=>['A','C']],
        ], [1=>['A','C']]);
        $this->assertEquals(2, $res['score']);
    }
}
```

## (D) Bootstrap Templates (snippets)
`app/Views/layouts/base.php`
```html
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <title><?= esc($title ?? 'LMS'); ?></title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="/">LMS</a></div></nav>
<div class="container py-4">
  <?= $this->renderSection('content'); ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

`app/Views/courses/catalog.php`
```html
<?= $this->extend('layouts/base'); ?>
<?= $this->section('content'); ?>
<h1>Courses</h1>
<div class="row row-cols-1 row-cols-md-3 g-4">
  <?php foreach($courses as $course): ?>
    <div class="col"><div class="card h-100">
      <img src="<?= esc($course['thumbnail']); ?>" class="card-img-top" alt="thumb">
      <div class="card-body">
        <h5 class="card-title"><?= esc($course['title']); ?></h5>
        <p class="card-text"><?= esc($course['summary']); ?></p>
        <a href="/courses/<?= $course['id']; ?>" class="btn btn-primary">View</a>
      </div>
    </div></div>
  <?php endforeach; ?>
</div>
<?= $this->endSection(); ?>
```

`app/Views/lessons/player.php`
```html
<?= $this->extend('layouts/base'); ?>
<?= $this->section('content'); ?>
<div class="row">
  <div class="col-lg-8">
    <div class="ratio ratio-16x9 mb-3">
      <iframe src="<?= esc($signedHlsUrl); ?>" allowfullscreen></iframe>
    </div>
    <div><?= $lesson['content']; ?></div>
    <a href="/files/attachment/<?= $lesson['id']; ?>" class="btn btn-outline-secondary">Download PDF</a>
  </div>
  <div class="col-lg-4">
    <h5>Progress</h5>
    <div class="progress"><div class="progress-bar" style="width:<?= $progress; ?>%"></div></div>
  </div>
</div>
<?= $this->endSection(); ?>
```

`app/Views/quizzes/take.php`
```html
<?= $this->extend('layouts/base'); ?>
<?= $this->section('content'); ?>
<h3><?= esc($quiz['title']); ?></h3>
<form method="post" action="/quizzes/<?= $quiz['id']; ?>/submit">
  <?php foreach($questions as $q): ?>
    <div class="mb-3">
      <p><strong><?= esc($q['prompt']); ?></strong></p>
      <?php if($q['type']==='mcq_single'): ?>
        <?php foreach($q['options'] as $opt): ?>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="answers[<?= $q['id']; ?>]" value="<?= esc($opt); ?>">
            <label class="form-check-label"><?= esc($opt); ?></label>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <!-- Extend for other types -->
    </div>
  <?php endforeach; ?>
  <button class="btn btn-success">Submit</button>
</form>
<?= $this->endSection(); ?>
```

## (E) Explanations
- **Video protection**: store mp4 in S3; transcode to HLS; generate short-lived signed URL per request. Player iframe uses signed URL; no direct bucket access. Use `Range` support and disable CORS hotlinking. For on-prem, use nginx secure link module.
- **Auto-grading logic**: see `QuizGrader::grade`—compares answers by type, accumulates `score` and `max`. Essay questions return zero and flagged for manual grading; teacher dashboard lists pending attempts for manual scoring then updates `quiz_attempt_answers`.
- **Progress calculation**: `progress = (completed_lessons / total_lessons) * 100`. Update on lesson completion and quiz completion. Course completion when `progress == 100` and passing quizzes; set `enrollments.completed_at`.
- **Notifications**: controller pushes jobs to queue service with type (email, in-app). Worker reads jobs, persists `notifications` rows and dispatches SMTP using CI4 Email class. In-app notifications fetched via `/api/notifications`.

## (F) REST Endpoint Examples
- `POST /api/register` `{email,password,role_id}`
- `POST /api/login` `{email,password}`
- `POST /api/courses` (instructor) create course.
- `POST /api/courses/{id}/publish` publish with permission check.
- `POST /api/courses/{id}/sections` create section.
- `POST /api/lessons` create lesson with upload.
- `GET /api/courses/{id}` course detail with sections/lessons.
- `POST /api/enroll/{courseId}` enroll student.
- `POST /api/lessons/{id}/complete` mark completion.
- `POST /api/quizzes/{id}/attempt` start quiz; `POST /api/quizzes/{id}/submit` for submission.
- `GET /api/notifications` list notifications for current user.

Example cURL (token via session/cookie omitted for brevity):
```bash
curl -X POST https://lms.test/api/register -d 'email=a@b.com&password=Secret123&role_id=3'
curl -X POST https://lms.test/api/courses -d 'title=Algebra 101&visibility=draft'
curl -X POST https://lms.test/api/quizzes/5/submit -H 'Content-Type: application/json' -d '{"answers":{"1":"A","2":["B","C"]}}'
```

## (G) Deployment & Security Notes
- ENV: `DATABASE_URL`, `EMAIL_SMTP_HOST/USER/PASS`, `APP_ENCRYPTION_KEY`, `QUEUE_REDIS_URL`, `S3_KEY/SECRET/REGION/BUCKET`, `APP_BASEURL`.
- Server: nginx + php-fpm; enable HTTPS (HSTS), set `upload_max_filesize` and `post_max_size` for videos; `opcache` on.
- Storage: writeable `writable/uploads` for temporary files; offload to S3 on completion; restrict public bucket access.
- Logging/Monitoring: CI4 logs, fail2ban for auth endpoints; rate-limit login/reset via cache limiter.
- Backup: daily DB snapshot + S3 lifecycle.

## (H) Milestone Backlog
1. **Foundation**: auth with roles, migrations, queue service, email verification/reset.
2. **Course Management**: categories, courses CRUD, uploads, signed video links.
3. **Content & Delivery**: sections/lessons, attachments via protected endpoints, player UI.
4. **Assessment**: quizzes, question banks, auto-grader, manual grading UI.
5. **Tracking & Notifications**: enrollments, progress, certificates, notifications worker.
6. **UX & Dashboards**: instructor/student dashboards, analytics charts, reviews.
7. **API & Mobile support**: REST endpoints hardened with rate limits and tokens.
8. **Reporting & Exports**: CSV/PDF exports, audit logs.

