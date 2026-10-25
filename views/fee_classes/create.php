<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('fee_classes_create') ?></h2>
        <p class="text-muted mb-0">Beitragssatz und Altersgrenzen festlegen</p>
    </div>
    <a href="/fee-classes" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> <?= __('back') ?>
    </a>
</div>

<div class="card shadow-sm border-0 col-lg-8 mx-auto">
    <div class="card-body p-4">
        <form action="/fee-classes" method="POST">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label small fw-semibold"><?= __('field_fee_name') ?> *</label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="z.B. Jugend / Schüler">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label small fw-semibold"><?= __('field_fee_description') ?></label>
                <textarea class="form-control" id="description" name="description" rows="2" placeholder="Beschreibung der Zielgruppe"></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="base_amount" class="form-label small fw-semibold"><?= __('field_fee_base_amount') ?> *</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" class="form-control" id="base_amount" name="base_amount" required placeholder="84.00">
                        <span class="input-group-text">€</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="billing_cycle" class="form-label small fw-semibold"><?= __('field_fee_cycle') ?> *</label>
                    <select class="form-select" id="billing_cycle" name="billing_cycle" required>
                        <option value="Jährlich"><?= __('cycle_yearly') ?></option>
                        <option value="Halbjährlich"><?= __('cycle_half_yearly') ?></option>
                        <option value="Vierteljährlich"><?= __('cycle_quarterly') ?></option>
                        <option value="Monatlich"><?= __('cycle_monthly') ?></option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="min_age" class="form-label small fw-semibold"><?= __('field_fee_min_age') ?></label>
                    <input type="number" min="0" max="999" class="form-control" id="min_age" name="min_age" value="0">
                    <div class="form-text small">Mindestalter für automatisierte Zuweisung.</div>
                </div>
                <div class="col-md-6">
                    <label for="max_age" class="form-label small fw-semibold"><?= __('field_fee_max_age') ?></label>
                    <input type="number" min="0" max="999" class="form-control" id="max_age" name="max_age" value="999">
                    <div class="form-text small">Höchstalter (z.B. 17 für Jugend, 999 für offen).</div>
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                <label class="form-check-label small" for="is_active"><?= __('field_fee_is_active') ?></label>
            </div>

            <div class="text-end">
                <a href="/fee-classes" class="btn btn-outline-secondary me-2"><?= __('cancel') ?></a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm"><?= __('save') ?></button>
            </div>
        </form>
    </div>
</div>
