<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('members_create') ?></h2>
        <p class="text-muted mb-0">Neues Mitglied für Musikverein Denkendorf erfassen</p>
    </div>
    <a href="/members" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> <?= __('back') ?>
    </a>
</div>

<form action="/members" method="POST">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Section 1: Personal Data -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header card-header-custom py-3">
                    <span class="fw-bold"><i class="bi bi-person me-2 text-primary"></i> 1. Persönliche Stammdaten</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="salutation" class="form-label small fw-semibold"><?= __('field_salutation') ?></label>
                            <select class="form-select" id="salutation" name="salutation">
                                <option value="Herr"><?= __('field_salutation_mr') ?></option>
                                <option value="Frau"><?= __('field_salutation_mrs') ?></option>
                                <option value="Divers"><?= __('field_salutation_diverse') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="sex" class="form-label small fw-semibold"><?= __('field_sex') ?></label>
                            <select class="form-select" id="sex" name="sex">
                                <option value="Männlich"><?= __('field_sex_male') ?></option>
                                <option value="Weiblich"><?= __('field_sex_female') ?></option>
                                <option value="Divers"><?= __('field_sex_diverse') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="first_name" class="form-label small fw-semibold"><?= __('field_first_name') ?> *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label small fw-semibold"><?= __('field_last_name') ?> *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="birth_date" class="form-label small fw-semibold"><?= __('field_birth_date') ?> *</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Contact & Address -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header card-header-custom py-3">
                    <span class="fw-bold"><i class="bi bi-geo-alt me-2 text-primary"></i> 2. Anschrift & Kontakt</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold"><?= __('field_email') ?></label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@domain.de">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold"><?= __('field_phone') ?></label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="+49 ...">
                        </div>
                        <div class="col-12">
                            <label for="street" class="form-label small fw-semibold"><?= __('field_street') ?></label>
                            <input type="text" class="form-control" id="street" name="street" placeholder="Straße und Hausnummer">
                        </div>
                        <div class="col-md-4">
                            <label for="zip" class="form-label small fw-semibold"><?= __('field_zip') ?></label>
                            <input type="text" class="form-control" id="zip" name="zip" value="73770">
                        </div>
                        <div class="col-md-5">
                            <label for="city" class="form-label small fw-semibold"><?= __('field_city') ?></label>
                            <input type="text" class="form-control" id="city" name="city" value="Denkendorf">
                        </div>
                        <div class="col-md-3">
                            <label for="country" class="form-label small fw-semibold"><?= __('field_country') ?></label>
                            <input type="text" class="form-control" id="country" name="country" value="Deutschland">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Membership Status -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header card-header-custom py-3">
                    <span class="fw-bold"><i class="bi bi-card-checklist me-2 text-primary"></i> 3. Vereinszugehörigkeit</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="membership_type" class="form-label small fw-semibold"><?= __('field_membership_type') ?> *</label>
                            <select class="form-select" id="membership_type" name="membership_type" required>
                                <option value="Vollmitglied"><?= __('type_full') ?></option>
                                <option value="Fördermitglied"><?= __('type_sponsor') ?></option>
                                <option value="Jugendmitglied"><?= __('type_youth') ?></option>
                                <option value="Ehrenmitglied"><?= __('type_honorary') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label small fw-semibold"><?= __('field_status') ?> *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Aktiv"><?= __('status_active') ?></option>
                                <option value="Inaktiv"><?= __('status_inactive') ?></option>
                                <option value="Gesperrt"><?= __('status_locked') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="join_date" class="form-label small fw-semibold"><?= __('field_join_date') ?> *</label>
                            <input type="date" class="form-control" id="join_date" name="join_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="leave_date" class="form-label small fw-semibold"><?= __('field_leave_date') ?></label>
                            <input type="date" class="form-control" id="leave_date" name="leave_date">
                        </div>
                        <div class="col-12">
                            <label for="initial_fee_class_id" class="form-label small fw-semibold"><?= __('assign_fee_class') ?></label>
                            <select class="form-select" id="initial_fee_class_id" name="initial_fee_class_id">
                                <option value="">-- Keine Beitragsklasse zuweisen --</option>
                                <?php foreach ($feeClasses as $fc): ?>
                                    <option value="<?= $fc->id ?>">
                                        <?= e($fc->name) ?> (<?= format_money($fc->base_amount) ?> - <?= e($fc->billing_cycle) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="invoice_marker" name="invoice_marker" value="1">
                                <label class="form-check-label small" for="invoice_marker">
                                    <?= __('field_invoice_marker') ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Bank & SEPA Direct Debit Mandate -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header card-header-custom py-3">
                    <span class="fw-bold"><i class="bi bi-bank me-2 text-primary"></i> 4. Bankverbindung & SEPA-Mandat</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="bank_holder" class="form-label small fw-semibold"><?= __('field_bank_holder') ?></label>
                            <input type="text" class="form-control" id="bank_holder" name="bank_holder" placeholder="Kontoinhaber (falls abweichend)">
                        </div>
                        <div class="col-md-6">
                            <label for="bank_name" class="form-label small fw-semibold"><?= __('field_bank_name') ?></label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" placeholder="z.B. Kreissparkasse Esslingen">
                        </div>
                        <div class="col-md-6">
                            <label for="bank_bic" class="form-label small fw-semibold"><?= __('field_bank_bic') ?></label>
                            <input type="text" class="form-control" id="bank_bic" name="bank_bic" placeholder="z.B. ESSLDE66XXX">
                        </div>
                        <div class="col-12">
                            <label for="bank_iban" class="form-label small fw-semibold"><?= __('field_bank_iban') ?></label>
                            <input type="text" class="form-control font-monospace" id="bank_iban" name="bank_iban" placeholder="DE00 0000 0000 0000 0000 00">
                            <div class="form-text small text-muted">IBAN wird automatisch nach ISO 7064 Modulo 97-10 validiert.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Buttons -->
        <div class="col-12 text-end">
            <a href="/members" class="btn btn-outline-secondary me-2"><?= __('cancel') ?></a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-check-lg me-1"></i> <?= __('save') ?>
            </button>
        </div>
    </div>
</form>
