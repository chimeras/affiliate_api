<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<h3><?= esc($quiz['title']); ?></h3>
<form id="quiz-form" method="post" action="/quizzes/<?= $quiz['id']; ?>/attempt">
    <?php foreach ($quiz['questions'] ?? [] as $index => $question): ?>
        <div class="mb-3">
            <p class="fw-semibold">Q<?= $index + 1; ?>. <?= esc($question['prompt']); ?></p>
            <?php if (in_array($question['type'], ['mcq_single','mcq_multi','true_false'])): ?>
                <?php $options = json_decode($question['options'] ?? '[]', true); ?>
                <?php foreach ($options as $opt): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="<?= $question['type'] === 'mcq_multi' ? 'checkbox' : 'radio'; ?>" name="answers[<?= $question['id']; ?>][]" value="<?= esc($opt); ?>">
                        <label class="form-check-label"><?= esc($opt); ?></label>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <input type="text" class="form-control" name="answers[<?= $question['id']; ?>]">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <button class="btn btn-primary">Submit</button>
</form>
<?= $this->endSection(); ?>
