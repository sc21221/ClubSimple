<?php /** @var \Clubmanager\Models\Member $member */ ?>
<?php /** @var \Clubmanager\Models\FeeClass[] $feeClasses */ ?>
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

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true"><?= __('member_tab_personal') ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false"><?= __('member_tab_contact') ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="membership-tab" data-bs-toggle="tab" data-bs-target="#membership" type="button" role="tab" aria-controls="membership" aria-selected="false"><?= __('member_tab_membership') ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab" aria-controls="bank" aria-selected="false"><?= __('member_tab_bank') ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees" type="button" role="tab" aria-controls="fees" aria-selected="false"><?= __('assigned_fees') ?></button></li>
</ul>

<div class="tab-content">
<form action="/members/<?= $member->id ?>" method="POST" id="member-edit-form" class="tab-content">
    <?= csrf_field() ?>

        <!-- Section 1: Personal Data -->
        <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab" tabindex="0">
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
        <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">
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
        <div class="tab-pane fade" id="membership" role="tabpanel" aria-labelledby="membership-tab" tabindex="0">
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
        <div class="tab-pane fade" id="bank" role="tabpanel" aria-labelledby="bank-tab" tabindex="0">
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
        <div class="text-end mt-4" id="member-edit-actions">
            <a href="/members/<?= $member->id ?>" class="btn btn-outline-secondary me-2"><?= __('cancel') ?></a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-check-lg me-1"></i> <?= __('save') ?>
            </button>
        </div>
</form>

<div class="tab-pane fade" id="fees" role="tabpanel" aria-labelledby="fees-tab" tabindex="0">
<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom py-3">
        <span class="fw-bold"><i class="bi bi-tags text-primary me-2"></i> <?= __('assigned_fees') ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($assignedFees)): ?>
            <p class="text-muted"><?= __('member_no_fee_classes') ?></p>
        <?php else: ?>
            <p class="text-muted small"><?= __('member_fee_save_separately') ?></p>
            <?php foreach ($assignedFees as $af): ?>
                <form action="/members/<?= (int) $member->id ?>/fee/<?= (int) $af['id'] ?>/update" method="POST" class="row g-2 align-items-end mb-3">
                    <?= csrf_field() ?>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold" for="fee_class_<?= (int) $af['id'] ?>"><?= __('field_fee_name') ?></label>
                        <select class="form-select" id="fee_class_<?= (int) $af['id'] ?>" name="fee_class_id" required>
                            <?php foreach ($feeClasses as $fc): ?>
                                <option value="<?= (int) $fc->id ?>" <?= (int) $fc->id === (int) $af['fee_class_id'] ? 'selected' : '' ?>><?= e($fc->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5 small text-muted">
                        <?= __('valid_from') ?>: <?= e($af['start_date']) ?>
                        <?php if ($af['end_date']): ?> &bull; <?= __('valid_until') ?>: <?= e($af['end_date']) ?><?php endif; ?>
                        <?php if ($af['custom_amount'] !== null && $af['custom_amount'] !== ''): ?> &bull; <?= __('custom_amount') ?>: <?= format_money($af['custom_amount']) ?><?php endif; ?>
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-outline-primary"><?= __('save') ?></button></div>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
        <a href="/members/<?= (int) $member->id ?>"><?= __('member_manage_fee_classes') ?></a>
    </div>
</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const actions = document.getElementById('member-edit-actions');
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', () => {
            actions.hidden = tab.id === 'fees-tab';
        });
    });
    document.getElementById('member-edit-form').addEventListener('invalid', event => {
        if (event.target !== event.currentTarget.querySelector(':invalid')) return;
        const pane = event.target.closest('.tab-pane');
        if (pane && !pane.classList.contains('active')) {
            bootstrap.Tab.getOrCreateInstance(document.querySelector(`[data-bs-target="#${pane.id}"]`)).show();
        }
    }, true);
});
</script>
