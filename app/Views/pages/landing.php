<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<div class="row align-items-center">
    <div class="col-md-6">
        <h1 class="display-5 fw-bold">Learn anything, anywhere</h1>
        <p class="lead">Browse instructor-led courses and track your progress.</p>
        <a href="/courses/catalog" class="btn btn-primary btn-lg">Browse Catalog</a>
    </div>
    <div class="col-md-6 text-center">
        <img class="img-fluid" src="https://placehold.co/500x300" alt="Hero">
    </div>
</div>
<?= $this->endSection(); ?>
