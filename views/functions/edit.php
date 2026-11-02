<?php /** @var object $function */ ?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-1"><?= __('functions_edit') ?>: <?= e($function->name) ?></h2>
    <a href="/functions" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> <?= __('back') ?></a>
</div>
<div class="card shadow-sm border-0 col-lg-8 mx-auto"><div class="card-body p-4">
    <form action="/functions/<?= (int) $function->id ?>/update" method="POST">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="name" class="form-label small fw-semibold"><?= __('function_name') ?> *</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= e($function->name) ?>" maxlength="100" required>
        </div>
        <div class="text-end"><a href="/functions" class="btn btn-outline-secondary me-2"><?= __('cancel') ?></a><button type="submit" class="btn btn-primary px-4 shadow-sm"><?= __('save') ?></button></div>
    </form>
</div></div>