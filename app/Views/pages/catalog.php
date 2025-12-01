<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<h2>Catalog</h2>
<div class="row mb-3">
    <div class="col">
        <select class="form-select">
            <?php foreach ($categories as $category): ?>
                <option><?= esc($category['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="row">
    <?php foreach ($courses as $course): ?>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <img src="<?= esc($course['thumbnail'] ?? 'https://placehold.co/600x300'); ?>" class="card-img-top" alt="thumbnail">
                <div class="card-body">
                    <h5 class="card-title"><?= esc($course['title']); ?></h5>
                    <p class="card-text"><?= esc($course['summary']); ?></p>
                    <a href="/courses/<?= $course['id']; ?>" class="btn btn-outline-primary">View</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?= $this->endSection(); ?>
