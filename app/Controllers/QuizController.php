<?php
namespace App\Controllers;

use App\Models\QuizModel;
use App\Models\QuestionModel;
use App\Models\AttemptModel;
use CodeIgniter\API\ResponseTrait;

class QuizController extends BaseController
{
    use ResponseTrait;

    public function show($id)
    {
        $quiz = (new QuizModel())->withQuestions()->find($id);
        if (! $quiz) {
            return $this->failNotFound();
        }
        return view('quizzes/show', ['quiz' => $quiz]);
    }

    public function create()
    {
        $quizModel = new QuizModel();
        $data = $this->request->getPost([
            'course_id', 'title', 'time_limit', 'passing_score', 'is_randomized'
        ]);
        $quizId = $quizModel->insert($data);

        $questions = $this->request->getPost('questions') ?? [];
        $questionModel = new QuestionModel();
        foreach ($questions as $question) {
            $question['quiz_id'] = $quizId;
            $questionModel->insert($question);
        }
        return $this->respondCreated(['id' => $quizId]);
    }

    public function update($id)
    {
        $quizModel = new QuizModel();
        $quizModel->update($id, $this->request->getRawInput());
        return $this->respondUpdated(['id' => $id]);
    }

    public function delete($id)
    {
        (new QuizModel())->delete($id);
        return $this->respondDeleted();
    }

    public function attempt($quizId)
    {
        $quizModel = new QuizModel();
        $quiz = $quizModel->withQuestions()->find($quizId);
        if (! $quiz) {
            return $this->failNotFound();
        }
        $answers = $this->request->getJSON(true)['answers'] ?? [];
        $score = 0;
        $total = count($quiz['questions']);
        foreach ($quiz['questions'] as $question) {
            $questionId = $question['id'];
            $given = $answers[$questionId] ?? null;
            if ($question['type'] === 'mcq_single' && $given === $question['correct_answer']) {
                $score++;
            }
            if ($question['type'] === 'mcq_multi' && is_array($given)) {
                sort($given);
                $correct = explode(',', $question['correct_answer']);
                sort($correct);
                if ($correct === $given) {
                    $score++;
                }
            }
            if ($question['type'] === 'true_false' && (string)$given === (string)$question['correct_answer']) {
                $score++;
            }
        }
        $percentage = $total ? ($score / $total) * 100 : 0;
        $pass = $percentage >= ($quiz['passing_score'] ?? 0);
        $attemptModel = new AttemptModel();
        $attemptModel->insert([
            'quiz_id' => $quizId,
            'user_id' => user_id(),
            'score' => $percentage,
            'status' => $pass ? 'passed' : 'failed'
        ]);
        return $this->respond(['score' => $percentage, 'passed' => $pass]);
    }
}
