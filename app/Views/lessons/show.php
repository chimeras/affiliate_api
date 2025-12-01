<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<div class="row">
    <div class="col-md-8">
        <h3><?= esc($lesson['title']); ?></h3>
        <?php if ($lesson['video_url']): ?>
            <div class="ratio ratio-16x9 mb-3">
                <iframe src="<?= esc($lesson['video_url']); ?>" allowfullscreen></iframe>
            </div>
        <?php endif; ?>
        <article class="prose"><?= esc($lesson['content']); ?></article>
        <?php if ($lesson['attachment_path']): ?>
            <a class="btn btn-outline-secondary mt-3" href="/files/attachment/<?= $lesson['attachment_path']; ?>">Download attachment</a>
        <?php endif; ?>
    </div>
    <div class="col-md-4">
        <form method="post" action="/lessons/<?= $lesson['id']; ?>/complete">
            <button class="btn btn-success w-100">Mark complete</button>
        </form>
        <form class="mt-3" method="post" action="/lessons/<?= $lesson['id']; ?>/notes">
            <label class="form-label">Your notes</label>
            <textarea name="body" class="form-control" rows="4"></textarea>
            <button class="btn btn-outline-primary mt-2 w-100">Save Note</button>
        </form>
    </div>
</div>
<?= $this->endSection(); ?>
