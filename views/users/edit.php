<?php /** @var \Clubmanager\Models\User $user */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('users_edit') ?>: <?= e($user->name) ?></h2>
        <p class="text-muted mb-0">Benutzer #<?= $user->id ?> &bull; <?= e($user->email) ?></p>
    </div>
    <a href="/users" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> <?= __('back') ?>
    </a>
</div>

<div class="card shadow-sm border-0 col-lg-7 mx-auto">
    <div class="card-body p-4">
        <form action="/users/<?= $user->id ?>" method="POST">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label small fw-semibold"><?= __('field_user_name') ?> *</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= e($user->name) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold"><?= __('field_user_email') ?> *</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($user->email) ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label small fw-semibold"><?= __('field_user_password') ?></label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8" placeholder="••••••••">
                    <div class="form-text small"><?= __('field_user_password_help') ?></div>
                    <div class="form-text small"><?= e(__('user_password_min_length')) ?></div>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label small fw-semibold"><?= __('field_user_phone') ?></label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= e($user->phone) ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="locale" class="form-label small fw-semibold"><?= __('field_user_locale') ?></label>
                    <select class="form-select" id="locale" name="locale">
                        <option value="de" <?= $user->locale === 'de' ? 'selected' : '' ?>>Deutsch (de)</option>
                        <option value="en" <?= $user->locale === 'en' ? 'selected' : '' ?>>English (en)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="role" class="form-label small fw-semibold"><?= __('field_user_role') ?></label>
                    <select class="form-select" id="role" name="role" required>
                        <?php foreach (\Clubmanager\Models\User::ROLES as $role): ?>
                            <option value="<?= e($role) ?>" <?= $user->getRole() === $role ? 'selected' : '' ?>><?= __('role_' . $role) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="text-end">
                <a href="/users" class="btn btn-outline-secondary me-2"><?= __('cancel') ?></a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm"><?= __('save') ?></button>
            </div>
        </form>
    </div>
</div>
