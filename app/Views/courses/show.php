<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<div class="row">
    <div class="col-md-8">
        <h2><?= esc($course['title']); ?></h2>
        <p><?= esc($course['summary']); ?></p>
        <div class="accordion" id="sections">
            <?php foreach ($course['sections'] ?? [] as $index => $section): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $section['id']; ?>">
                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $section['id']; ?>">
                            <?= esc($section['title']); ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $section['id']; ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : ''; ?>">
                        <div class="accordion-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($section['lessons'] ?? [] as $lesson): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= esc($lesson['title']); ?>
                                        <a href="/lessons/<?= $lesson['id']; ?>" class="btn btn-sm btn-outline-primary">Open</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="mb-1">Level: <?= esc($course['level']); ?></p>
                <p class="mb-1">Price: <?= $course['price'] ? '$' . $course['price'] : 'Free'; ?></p>
                <form method="post" action="/courses/<?= $course['id']; ?>/enroll">
                    <button class="btn btn-success w-100">Enroll</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>
