<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('users_create') ?></h2>
        <p class="text-muted mb-0">Neuen Systembenutzer mit Rollenrechten anlegen</p>
    </div>
    <a href="/users" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> <?= __('back') ?>
    </a>
</div>

<div class="card shadow-sm border-0 col-lg-7 mx-auto">
    <div class="card-body p-4">
        <form action="/users" method="POST">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label small fw-semibold"><?= __('field_user_name') ?> *</label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="Vor- und Nachname">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold"><?= __('field_user_email') ?> *</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="benutzer@musikverein-denkendorf.de">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label small fw-semibold"><?= __('field_user_password') ?> *</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Mindestens 6 Zeichen">
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label small fw-semibold"><?= __('field_user_phone') ?></label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="+49 ...">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="locale" class="form-label small fw-semibold"><?= __('field_user_locale') ?></label>
                    <select class="form-select" id="locale" name="locale">
                        <option value="de">Deutsch (de)</option>
                        <option value="en">English (en)</option>
                    </select>
                </div>
                <div class="col-md-6 pt-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="admin" name="admin" value="1">
                        <label class="form-check-label small fw-semibold" for="admin">
                            <?= __('field_user_is_admin') ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="/users" class="btn btn-outline-secondary me-2"><?= __('cancel') ?></a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm"><?= __('save') ?></button>
            </div>
        </form>
    </div>
</div>
