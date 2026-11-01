<?php /** @var \Clubmanager\Models\Club $club */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('club_title') ?></h2>
        <p class="text-muted mb-0"><?= __('club_subtitle') ?></p>
    </div>
</div>

<form action="/club" method="POST">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Club Master Information -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header card-header-custom py-3">
                    <span class="fw-bold"><i class="bi bi-building text-primary me-2"></i> Vereinsidentität & Vertretung</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label small fw-semibold"><?= __('field_club_name') ?> *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($club->name) ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="name2" class="form-label small fw-semibold"><?= __('field_club_name2') ?></label>
                            <input type="text" class="form-control" id="name2" name="name2" value="<?= e($club->name2) ?>" placeholder="Untertitel, z.B. Blasmusik & Jugendarbeit">
                        </div>
                        <div class="col-12">
                            <label for="boss" class="form-label small fw-semibold"><?= __('field_club_boss') ?> *</label>
                            <input type="text" class="form-control" id="boss" name="boss" value="<?= e($club->boss) ?>" required placeholder="Vorstand / Vertretungsberechtigter">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold"><?= __('field_email') ?></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($club->email) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold"><?= __('field_phone') ?></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= e($club->phone) ?>">
                        </div>
                        <div class="col-12">
                            <label for="street" class="form-label small fw-semibold"><?= __('field_street') ?></label>
                            <input type="text" class="form-control" id="street" name="street" value="<?= e($club->street) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="zip" class="form-label small fw-semibold"><?= __('field_zip') ?></label>
                            <input type="text" class="form-control" id="zip" name="zip" value="<?= e($club->zip) ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="city" class="form-label small fw-semibold"><?= __('field_city') ?></label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= e($club->city) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="country" class="form-label small fw-semibold"><?= __('field_country') ?></label>
                            <input type="text" class="form-control" id="country" name="country" value="<?= e($club->country ?: 'Deutschland') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEPA Creditor Credentials -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header card-header-custom py-3">
                    <span class="fw-bold"><i class="bi bi-bank text-primary me-2"></i> SEPA-Gläubiger & Bankverbindung</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="bank_name" class="form-label small fw-semibold"><?= __('field_bank_name') ?></label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" maxlength="100" value="<?= e($club->bank_name) ?>" placeholder="z.B. Raiffeisenbank Bayern Mitte">
                        </div>
                        <div class="col-12">
                            <label for="bank_creditor_id" class="form-label small fw-semibold"><?= __('field_club_creditor_id') ?> *</label>
                            <input type="text" class="form-control font-monospace fw-bold" id="bank_creditor_id" name="bank_creditor_id" value="<?= e($club->bank_creditor_id) ?>" required placeholder="DE98ZZZ09999999999">
                            <div class="form-text small">Gläubiger-ID des Vereins von der Deutschen Bundesbank.</div>
                        </div>
                        <div class="col-12">
                            <label for="bank_iban" class="form-label small fw-semibold"><?= __('field_bank_iban') ?> *</label>
                            <input type="text" class="form-control font-monospace fw-semibold" id="bank_iban" name="bank_iban" value="<?= e($club->bank_iban) ?>" required placeholder="DE00 0000 0000 0000 0000 00">
                        </div>
                        <div class="col-12">
                            <label for="bank_bic" class="form-label small fw-semibold"><?= __('field_bank_bic') ?> *</label>
                            <input type="text" class="form-control font-monospace" id="bank_bic" name="bank_bic" value="<?= e($club->bank_bic) ?>" required placeholder="BYLADEMxxx">
                        </div>
                    </div>

                    <div class="alert alert-light border mt-4 p-3 small text-muted">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Diese Daten werden zwingend in den ISO 20022 SEPA XML-Headern (<code>&lt;Cdtr&gt;</code>, <code>&lt;CdtrAcct&gt;</code>, <code>&lt;CdtrSchmeId&gt;</code>) benötigt.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                <i class="bi bi-check-lg me-1"></i> <?= __('save') ?>
            </button>
        </div>
    </div>
</form>
