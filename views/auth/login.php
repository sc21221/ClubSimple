<div class="card shadow border-0 rounded-4 overflow-hidden">
    <!-- Header -->
    <div class="card-header bg-primary text-white text-center py-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%) !important;">
        <div class="d-inline-flex p-3 rounded-circle bg-white text-primary mb-2 shadow-sm">
            <i class="bi bi-music-note-beamed fs-2 text-primary"></i>
        </div>
        <h4 class="fw-bold mb-1">Clubmanager</h4>
        <div class="text-white-50 small">Musikverein Denkendorf e.V.</div>
    </div>

    <!-- Body -->
    <div class="card-body p-4 p-md-5">
        <h5 class="fw-bold mb-3 text-center"><?= __('login_title') ?></h5>
        <p class="text-muted small text-center mb-4"><?= __('login_subtitle') ?></p>

        <form action="/login" method="POST">
            <?= csrf_field() ?>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold small"><?= __('login_email') ?></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email', 'admin@musikverein-denkendorf.de')) ?>" required autofocus placeholder="name@domain.de">
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold small"><?= __('login_password') ?></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control" id="password" name="password" value="admin" required placeholder="••••••••">
                </div>
            </div>

            <!-- Language Selection -->
            <div class="mb-3">
                <label for="locale" class="form-label fw-semibold small"><?= __('login_language') ?></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-translate text-muted"></i></span>
                    <select class="form-select" id="locale" name="locale" onchange="window.location.href='/login?lang=' + this.value">
                        <option value="de" <?= ($locale ?? 'de') === 'de' ? 'selected' : '' ?>>🇩🇪 Deutsch (German)</option>
                        <option value="en" <?= ($locale ?? 'de') === 'en' ? 'selected' : '' ?>>🇬🇧 English (Englisch)</option>
                    </select>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" value="1" checked>
                <label class="form-check-label small" for="remember_me"><?= __('login_remember_me') ?></label>
            </div>

            <!-- Submit Button -->
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                    <i class="bi bi-box-arrow-in-right me-2"></i> <?= __('login_submit') ?>
                </button>
            </div>

            <!-- Demo hints -->
            <div class="p-3 bg-light rounded-3 small border">
                <div class="fw-bold mb-1 text-secondary"><i class="bi bi-info-circle me-1"></i> Demo-Zugangsdaten:</div>
                <div><strong>Admin:</strong> <code>admin@musikverein-denkendorf.de</code> / <code>admin</code></div>
                <div><strong>Kassenwart:</strong> <code>kasse@musikverein-denkendorf.de</code> / <code>kasse</code></div>
            </div>
        </form>
    </div>
</div>
