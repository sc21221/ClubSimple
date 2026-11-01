<?php /** @var \Clubmanager\Models\Member $member */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('members_edit') ?>: <?= e($member->getFullName()) ?></h2>
        <p class="text-muted mb-0">Mitgliedsnummer #<?= $member->id ?> &bull; Musikverein Denkendorf</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/members/<?= $member->id ?>" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> <?= __('action_view') ?>
        </a>
        <a href="/members" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> <?= __('back') ?>
        </a>
    </div>
</div>

<form action="/members/<?= $member->id ?>" method="POST">
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
                                <option value="Herr" <?= $member->salutation === 'Herr' ? 'selected' : '' ?>><?= __('field_salutation_mr') ?></option>
                                <option value="Frau" <?= $member->salutation === 'Frau' ? 'selected' : '' ?>><?= __('field_salutation_mrs') ?></option>
                                <option value="Divers" <?= $member->salutation === 'Divers' ? 'selected' : '' ?>><?= __('field_salutation_diverse') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="sex" class="form-label small fw-semibold"><?= __('field_sex') ?></label>
                            <select class="form-select" id="sex" name="sex">
                                <option value="Männlich" <?= $member->sex === 'Männlich' ? 'selected' : '' ?>><?= __('field_sex_male') ?></option>
                                <option value="Weiblich" <?= $member->sex === 'Weiblich' ? 'selected' : '' ?>><?= __('field_sex_female') ?></option>
                                <option value="Divers" <?= $member->sex === 'Divers' ? 'selected' : '' ?>><?= __('field_sex_diverse') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="first_name" class="form-label small fw-semibold"><?= __('field_first_name') ?> *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($member->first_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label small fw-semibold"><?= __('field_last_name') ?> *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($member->last_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="birth_date" class="form-label small fw-semibold"><?= __('field_birth_date') ?> *</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?= e($member->birth_date) ?>" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <span class="text-muted small mt-4">Aktuelles Alter: <strong><?= $member->getAge() ?> Jahre</strong></span>
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
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($member->email) ?>" placeholder="name@domain.de">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold"><?= __('field_phone') ?></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= e($member->phone) ?>" placeholder="+49 ...">
                        </div>
                        <div class="col-12">
                            <label for="street" class="form-label small fw-semibold"><?= __('field_street') ?></label>
                            <input type="text" class="form-control" id="street" name="street" value="<?= e($member->street) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="zip" class="form-label small fw-semibold"><?= __('field_zip') ?></label>
                            <input type="text" class="form-control" id="zip" name="zip" value="<?= e($member->zip) ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="city" class="form-label small fw-semibold"><?= __('field_city') ?></label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= e($member->city) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="country" class="form-label small fw-semibold"><?= __('field_country') ?></label>
                            <input type="text" class="form-control" id="country" name="country" value="<?= e($member->country ?: 'Deutschland') ?>">
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
                                <option value="Vollmitglied" <?= $member->membership_type === 'Vollmitglied' ? 'selected' : '' ?>><?= __('type_full') ?></option>
                                <option value="Fördermitglied" <?= $member->membership_type === 'Fördermitglied' ? 'selected' : '' ?>><?= __('type_sponsor') ?></option>
                                <option value="Jugendmitglied" <?= $member->membership_type === 'Jugendmitglied' ? 'selected' : '' ?>><?= __('type_youth') ?></option>
                                <option value="Ehrenmitglied" <?= $member->membership_type === 'Ehrenmitglied' ? 'selected' : '' ?>><?= __('type_honorary') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label small fw-semibold"><?= __('field_status') ?> *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Aktiv" <?= $member->status === 'Aktiv' ? 'selected' : '' ?>><?= __('status_active') ?></option>
                                <option value="Inaktiv" <?= $member->status === 'Inaktiv' ? 'selected' : '' ?>><?= __('status_inactive') ?></option>
                                <option value="Gesperrt" <?= $member->status === 'Gesperrt' ? 'selected' : '' ?>><?= __('status_locked') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="join_date" class="form-label small fw-semibold"><?= __('field_join_date') ?> *</label>
                            <input type="date" class="form-control" id="join_date" name="join_date" value="<?= e($member->join_date) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="leave_date" class="form-label small fw-semibold"><?= __('field_leave_date') ?></label>
                            <input type="date" class="form-control" id="leave_date" name="leave_date" value="<?= e($member->leave_date) ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="invoice_marker" name="invoice_marker" value="1" <?= $member->invoice_marker ? 'checked' : '' ?>>
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
                            <input type="text" class="form-control" id="bank_holder" name="bank_holder" value="<?= e($member->bank_holder) ?>" placeholder="Kontoinhaber (falls abweichend)">
                        </div>
                        <div class="col-md-6">
                            <label for="bank_name" class="form-label small fw-semibold"><?= __('field_bank_name') ?></label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= e($member->bank_name) ?>" placeholder="z.B. Raiffeisenbank Bayern Mitte">
                        </div>
                        <div class="col-md-6">
                            <label for="bank_bic" class="form-label small fw-semibold"><?= __('field_bank_bic') ?></label>
                            <input type="text" class="form-control" id="bank_bic" name="bank_bic" value="<?= e($member->bank_bic) ?>" placeholder="z.B. BYLADEMxxx">
                        </div>
                        <div class="col-12">
                            <label for="bank_iban" class="form-label small fw-semibold"><?= __('field_bank_iban') ?></label>
                            <input type="text" class="form-control font-monospace" id="bank_iban" name="bank_iban" value="<?= e($member->bank_iban) ?>" placeholder="DE00 0000 0000 0000 0000 00">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Buttons -->
        <div class="col-12 text-end">
            <a href="/members/<?= $member->id ?>" class="btn btn-outline-secondary me-2"><?= __('cancel') ?></a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-check-lg me-1"></i> <?= __('save') ?>
            </button>
        </div>
    </div>
</form>
