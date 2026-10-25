<!-- SEPA Preview Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('sepa_preview_summary') ?></h2>
        <p class="text-muted mb-0">
            Fälligkeitsdatum: <strong><?= format_date($dueDate) ?></strong> &bull; Gläubiger: <strong><?= e($club->name) ?></strong>
        </p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="/sepa" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> <?= __('back') ?>
        </a>
    </div>
</div>

<!-- Section 1: Automated Age-Based Recategorization Alert & Table -->
<?php if (!empty($recategorizations)): ?>
    <div class="card shadow-sm border-0 border-start border-4 border-warning mb-4">
        <div class="card-header bg-warning bg-opacity-10 py-3">
            <h5 class="fw-bold mb-1 text-dark">
                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                <?= __('sepa_recat_found', ['count' => count($recategorizations)]) ?>
            </h5>
            <small class="text-muted">
                Diese Mitglieder haben das Alterslimit ihrer aktuellen Beitragsklasse überschritten. Mit Ausführung des SEPA-Laufs werden sie automatisch in die für ihr Alter passende Beitragsklasse überführt.
            </small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mitglied</th>
                        <th>Alter am Einzugstag</th>
                        <th><?= __('sepa_recat_old_class') ?></th>
                        <th>&rarr;</th>
                        <th><?= __('sepa_recat_new_class') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recategorizations as $recat): ?>
                        <tr>
                            <td>
                                <a href="/members/<?= $recat['member']->id ?>" class="fw-bold text-dark text-decoration-none">
                                    <?= e($recat['member']->getFullName()) ?>
                                </a>
                                <small class="text-muted d-block"><?= format_date($recat['member']->birth_date) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark fs-6 px-3"><?= $recat['age'] ?> Jahre</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-muted border">
                                    <?= e($recat['current_fee_class']->name) ?> (<?= format_money($recat['current_fee_class']->base_amount) ?>)
                                </span>
                            </td>
                            <td class="text-primary fw-bold">&rarr;</td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                                    <?= e($recat['new_fee_class']->name) ?> (<?= format_money($recat['new_fee_class']->base_amount) ?>)
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success border-success-subtle shadow-sm mb-4 d-flex align-items-center">
        <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
        <div>
            <h6 class="fw-bold mb-0"><?= __('sepa_recat_none') ?></h6>
            <small class="text-muted">Alle aktiven Mitglieder sind der für ihr Alter optimalen Beitragsklasse zugeordnet.</small>
        </div>
    </div>
<?php endif; ?>

<!-- Section 2: Direct Debit Transactions Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-list-check text-primary me-2"></i> Lastschrift-Positionen (<?= $totalCount ?>)</span>
        <span class="fs-5 fw-bold text-primary">Gesamt: <?= format_money($totalAmount) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Zahlungspflichtiger / Kontoinhaber</th>
                    <th>IBAN & BIC</th>
                    <th>Beitragsklassen</th>
                    <th class="text-end">Einzugsbetrag</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transactions)): ?>
                    <?php $idx = 1; foreach ($transactions as $tx): ?>
                        <tr>
                            <td class="text-muted small"><?= $idx++ ?></td>
                            <td>
                                <div class="fw-bold"><?= e($tx['holder']) ?></div>
                                <small class="text-muted">
                                    Mitglied: <?= e($tx['member']->getFullName()) ?> (ID #<?= $tx['member']->id ?>)
                                </small>
                            </td>
                            <td>
                                <div class="font-monospace fw-semibold"><?= format_iban($tx['iban']) ?></div>
                                <div class="font-monospace text-muted small"><?= e($tx['bic'] ?: 'Ohne BIC') ?></div>
                            </td>
                            <td>
                                <?php foreach ($tx['fee_classes'] as $fc): ?>
                                    <span class="badge bg-light text-dark border me-1">
                                        <?= e($fc['fee_class_name']) ?>: <?= format_money($fc['custom_amount'] ?? $fc['base_amount']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-end fw-bold fs-6 text-primary">
                                <?= format_money($tx['amount']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-exclamation-circle fs-2 text-warning d-block mb-2"></i>
                            <?= __('sepa_no_members_to_debit') ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end">Summe aller <?= $totalCount ?> Lastschriften:</th>
                    <th class="text-end text-primary fs-5 fw-bold"><?= format_money($totalAmount) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Section 3: Final Execution Form -->
<?php if (!empty($transactions)): ?>
    <div class="card shadow-sm border-0 border-top border-4 border-primary">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1">SEPA-Lauf jetzt ausführen & XML generieren</h5>
                <p class="text-muted small mb-0">
                    Verwendungszweck: <code><?= e($remittanceText) ?></code> &bull; Fälligkeit: <code><?= e($dueDate) ?></code>
                </p>
            </div>
            <form action="/sepa/execute" method="POST" class="mt-3 mt-md-0">
                <?= csrf_field() ?>
                <input type="hidden" name="due_date" value="<?= e($dueDate) ?>">
                <input type="hidden" name="remittance" value="<?= e($remittanceText) ?>">
                <input type="hidden" name="auto_recategorize" value="1">

                <a href="/sepa" class="btn btn-outline-secondary me-2"><?= __('cancel') ?></a>
                <button type="submit" class="btn btn-success btn-lg px-4 shadow-sm" onclick="return confirm('Möchten Sie den SEPA-Lauf jetzt verbindlich ausführen und die XML-Datei generieren?');">
                    <i class="bi bi-check2-circle me-1"></i> SEPA XML generieren & buchen
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>
