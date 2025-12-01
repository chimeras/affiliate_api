<?php
namespace App\Models;

class QuizModel extends BaseModel
{
    protected $table = 'quizzes';
    protected $allowedFields = ['course_id','title','time_limit','passing_score','is_randomized'];

    public function withQuestions()
    {
        $questions = (new QuestionModel());
        $records = $this->findAll();
        foreach ($records as &$quiz) {
            $quiz['questions'] = $questions->where('quiz_id', $quiz['id'])->findAll();
        }
        return $records;
    }
}
