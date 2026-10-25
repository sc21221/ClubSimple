<?php
    $statusClass = match($member->status) {
        'Aktiv' => 'badge-status-aktiv',
        'Inaktiv' => 'badge-status-inaktiv',
        'Gesperrt' => 'badge-status-gesperrt',
        default => 'bg-secondary'
    };
?>

<!-- Member Details Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="h3 fw-bold mb-0"><?= e($member->getFullName()) ?></h2>
            <span class="badge <?= $statusClass ?> fs-6 px-3 py-1"><?= e($member->status) ?></span>
            <span class="badge bg-light text-dark border"><?= e($member->membership_type) ?></span>
        </div>
        <p class="text-muted mb-0">Mitglieds-ID #<?= $member->id ?> &bull; Musikverein Denkendorf &bull; <?= $member->getYearsOfMembership() ?> Jahre Vereinszugehörigkeit</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="/members/<?= $member->id ?>/edit" class="btn btn-outline-primary shadow-sm">
            <i class="bi bi-pencil me-1"></i> <?= __('action_edit') ?>
        </a>
        <a href="/members" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> <?= __('nav_members_list') ?>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Left Column: Master Data Profile -->
    <div class="col-lg-6">
        <!-- Card 1: Core Details -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-person-badge text-primary me-2"></i> Stammdaten & Kontakt</span>
                <span class="badge bg-light text-muted border"><?= e($member->salutation) ?> &bull; <?= e($member->sex) ?></span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_birth_date') ?>:</span>
                        <span class="fw-semibold"><?= format_date($member->birth_date) ?> (<?= $member->getAge() ?> Jahre alt)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_email') ?>:</span>
                        <span><?= $member->email ? '<a href="mailto:' . e($member->email) . '">' . e($member->email) . '</a>' : '<span class="text-muted">-</span>' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_phone') ?>:</span>
                        <span><?= e($member->phone ?: '-') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_address') ?>:</span>
                        <span class="text-end">
                            <?= e($member->street ?: '-') ?><br>
                            <?= e($member->zip ?: '') ?> <?= e($member->city ?: '') ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_join_date') ?>:</span>
                        <span class="fw-semibold"><?= format_date($member->join_date) ?></span>
                    </li>
                    <?php if (!empty($member->leave_date)): ?>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_leave_date') ?>:</span>
                        <span class="text-danger fw-semibold"><?= format_date($member->leave_date) ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Card 2: SEPA Bank Mandate -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-bank text-primary me-2"></i> SEPA-Lastschrift & Bankdaten</span>
                <?php if ($member->isDirectDebitEligible()): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i> SEPA-fähig</span>
                <?php else: ?>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="bi bi-exclamation-triangle me-1"></i> Keine Lastschrift</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_bank_holder') ?>:</span>
                        <span class="fw-semibold"><?= e($member->bank_holder ?: $member->getFullName()) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_bank_name') ?>:</span>
                        <span><?= e($member->bank_name ?: '-') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_bank_iban') ?>:</span>
                        <span class="font-monospace fw-semibold"><?= format_iban($member->bank_iban) ?: '-' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small"><?= __('field_bank_bic') ?>:</span>
                        <span class="font-monospace"><?= e($member->bank_bic ?: '-') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2 px-3">
                        <span class="text-muted small">Mandatsreferenz:</span>
                        <span class="font-monospace">MND-MBR-<?= $member->id ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Card 3: GDPR Compliance Actions -->
        <div class="card shadow-sm border-0 border-start border-4 border-info">
            <div class="card-header card-header-custom py-3">
                <span class="fw-bold"><i class="bi bi-shield-check text-info me-2"></i> <?= __('gdpr_section') ?></span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3"><?= __('gdpr_anonymize_warning') ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="/members/<?= $member->id ?>/export/pdf" class="btn btn-sm btn-outline-danger shadow-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> <?= __('gdpr_export_data') ?> (PDF)
                    </a>
                    <a href="/members/<?= $member->id ?>/export/json" class="btn btn-sm btn-outline-secondary shadow-sm">
                        <i class="bi bi-file-code me-1"></i> <?= __('gdpr_export_data') ?> (JSON)
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-warning shadow-sm" data-bs-toggle="modal" data-bs-target="#anonymizeModal">
                        <i class="bi bi-person-x me-1"></i> <?= __('gdpr_anonymize') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Assigned Fee Classes & Fee History -->
    <div class="col-lg-6">
        <!-- Assigned Fee Classes -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-tags text-primary me-2"></i> <?= __('assigned_fees') ?></span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignFeeModal">
                    <i class="bi bi-plus-lg me-1"></i> <?= __('assign_fee_class') ?>
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('field_fee_name') ?></th>
                                <th>Betrag</th>
                                <th>Gültigkeit</th>
                                <th class="text-end">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($assignedFees)): ?>
                                <?php foreach ($assignedFees as $af): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($af['fee_class_name']) ?></div>
                                            <small class="text-muted"><?= e($af['billing_cycle']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($af['custom_amount'] !== null && $af['custom_amount'] !== ''): ?>
                                                <span class="fw-bold text-primary"><?= format_money($af['custom_amount']) ?></span>
                                                <small class="text-muted d-block">(Abweichend)</small>
                                            <?php else: ?>
                                                <span class="fw-bold"><?= format_money($af['base_amount']) ?></span>
                                                <small class="text-muted d-block">(Standard)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            ab <?= format_date($af['start_date']) ?><br>
                                            <?= $af['end_date'] ? ('bis ' . format_date($af['end_date'])) : '<span class="text-success">unbefristet</span>' ?>
                                        </td>
                                        <td class="text-end">
                                            <form action="/members/<?= $member->id ?>/fee/<?= $af['id'] ?>/delete" method="POST" onsubmit="return confirm('Beitragsklassen-Zuweisung wirklich entfernen?');" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Zuweisung löschen">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-tag fs-3 text-muted d-block mb-1"></i>
                                        Diesem Mitglied ist aktuell keine Beitragsklasse zugewiesen.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="1">Gesamtbetrag (pro Zyklus):</th>
                                <th colspan="3" class="text-primary fs-6 fw-bold">
                                    <?= format_money($member->getTotalActiveFeeAmount()) ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- SEPA Payment / Debit History -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-custom py-3">
                <span class="fw-bold"><i class="bi bi-clock-history text-primary me-2"></i> Buchungshistorie & Lastschriften</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fälligkeitsdatum</th>
                                <th>Beitragsklasse</th>
                                <th>Betrag</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($feeHistory)): ?>
                                <?php foreach ($feeHistory as $fh): ?>
                                    <tr>
                                        <td><?= format_date($fh['due_date']) ?></td>
                                        <td><?= e($fh['fee_class_name']) ?></td>
                                        <td class="fw-semibold"><?= format_money($fh['amount']) ?></td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border"><?= e($fh['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted small">
                                        Bisher keine Buchungen in der Historie erfasst.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Audit Log History for this Member -->
        <div class="card shadow-sm border-0">
            <div class="card-header card-header-custom py-3">
                <span class="fw-bold"><i class="bi bi-journal-text text-secondary me-2"></i> Änderungsprotokoll (Audit-Trail)</span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($auditLogs)): ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($auditLogs as $log): ?>
                            <li class="list-group-item py-2 px-3">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold"><?= e($log->action) ?></span>
                                    <span class="text-muted"><?= format_date($log->timestamp, 'd.m.Y H:i') ?></span>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    Benutzer: <?= e($log->getUserName()) ?> &bull; IP: <?= e($log->ip_address ?: '127.0.0.1') ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="p-3 text-center text-muted small">Keine Protokolleinträge vorhanden.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Assign Fee Class -->
<div class="modal fade" id="assignFeeModal" tabindex="-1" aria-labelledby="assignFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/members/<?= $member->id ?>/fee" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="assignFeeModalLabel"><?= __('assign_fee_class') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="fee_class_id" class="form-label small fw-semibold"><?= __('field_fee_name') ?> *</label>
                    <select class="form-select" id="fee_class_id" name="fee_class_id" required>
                        <?php foreach ($allFeeClasses as $fc): ?>
                            <option value="<?= $fc->id ?>">
                                <?= e($fc->name) ?> (<?= format_money($fc->base_amount) ?> - <?= e($fc->billing_cycle) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="custom_amount" class="form-label small fw-semibold"><?= __('custom_amount') ?></label>
                    <input type="number" step="0.01" class="form-control" id="custom_amount" name="custom_amount" placeholder="z.B. 40.00">
                    <div class="form-text small"><?= __('custom_amount_help') ?></div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label for="start_date" class="form-label small fw-semibold"><?= __('valid_from') ?> *</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-6">
                        <label for="end_date" class="form-label small fw-semibold"><?= __('valid_until') ?></label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: GDPR Anonymize Confirmation -->
<div class="modal fade" id="anonymizeModal" tabindex="-1" aria-labelledby="anonymizeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/members/<?= $member->id ?>/anonymize" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="anonymizeModalLabel"><i class="bi bi-exclamation-triangle me-1"></i> <?= __('gdpr_anonymize') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Sind Sie sicher, dass Sie die personenbezogenen Daten von <strong><?= e($member->getFullName()) ?></strong> unwiderruflich anonymisieren möchten?</p>
                <div class="alert alert-danger small">
                    <?= __('gdpr_anonymize_warning') ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="submit" class="btn btn-danger"><?= __('gdpr_anonymize') ?></button>
            </div>
        </form>
    </div>
</div>
