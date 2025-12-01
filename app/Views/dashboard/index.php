<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>
<h2>Dashboard</h2>
<div class="row">
    <div class="col-md-4">
        <div class="card text-bg-light mb-3">
            <div class="card-body">
                <h5 class="card-title">Enrolled Courses</h5>
                <p class="display-6">3</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-light mb-3">
            <div class="card-body">
                <h5 class="card-title">Certificates</h5>
                <p class="display-6">1</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-light mb-3">
            <div class="card-body">
                <h5 class="card-title">Notifications</h5>
                <p class="display-6">4</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>
