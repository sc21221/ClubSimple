<!-- SEPA Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('sepa_title') ?></h2>
        <p class="text-muted mb-0"><?= __('sepa_subtitle') ?></p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="/sepa/history" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-clock-history me-1"></i> <?= __('nav_sepa_history') ?>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Creditor Credentials & Execution Form -->
    <div class="col-lg-7">
        <!-- New SEPA Run Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-custom py-3">
                <span class="fw-bold"><i class="bi bi-play-circle-fill text-primary me-2"></i> <?= __('sepa_execute') ?></span>
            </div>
            <div class="card-body p-4">
                <form action="/sepa/preview" method="GET">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="due_date" class="form-label small fw-semibold"><?= __('sepa_due_date') ?> *</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="<?= e($defaultDueDate) ?>" min="<?= date('Y-m-d') ?>" required>
                            <div class="form-text small">Empfehlung: Mindestens 2 Bankarbeitstage in der Zukunft.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="remittance" class="form-label small fw-semibold"><?= __('sepa_remittance_text') ?> *</label>
                            <input type="text" class="form-control" id="remittance" name="remittance" value="<?= e($defaultRemittance) ?>" maxlength="140" required>
                            <div class="form-text small">Platzhalter: <code>:year</code>, <code>:id</code>, <code>:name</code></div>
                        </div>
                    </div>

                    <!-- Automated Age Recategorization Notice Box -->
                    <div class="alert alert-info border-info-subtle shadow-sm mb-4">
                        <div class="d-flex">
                            <i class="bi bi-lightbulb-fill text-info fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1"><?= __('sepa_recat_heading') ?></h6>
                                <p class="small mb-0 text-muted"><?= __('sepa_recat_info') ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            <i class="bi bi-search me-2"></i> Zur Transaktions- & Altersprüfung &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent SEPA Runs -->
        <div class="card shadow-sm border-0">
            <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-clock-history text-secondary me-2"></i> Letzte SEPA-Läufe</span>
                <a href="/sepa/history" class="small text-decoration-none">Alle anzeigen &rarr;</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fälligkeit</th>
                                <th>Ausgeführt am</th>
                                <th>Transaktionen</th>
                                <th>Gesamtbetrag</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentRuns)): ?>
                                <?php foreach (array_slice($recentRuns, 0, 5) as $run): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= format_date($run['due_date']) ?></td>
                                        <td class="small text-muted"><?= format_date($run['run_date'], 'd.m.Y H:i') ?></td>
                                        <td><?= $run['transaction_count'] ?> Buchungen (<?= $run['member_count'] ?> Mitgl.)</td>
                                        <td class="fw-bold text-primary"><?= format_money($run['total_amount']) ?></td>
                                        <td><span class="badge bg-success-subtle text-success border"><?= e($run['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">
                                        <?= __('dashboard_no_sepa_yet') ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Club Creditor Identification -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-building-check text-primary me-2"></i> Gläubiger-Stammdaten</span>
                <a href="/club" class="btn btn-sm btn-outline-secondary">Bearbeiten</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between py-3 px-3">
                        <span class="text-muted small">Gläubiger-Name:</span>
                        <span class="fw-bold"><?= e($club->name) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-3 px-3">
                        <span class="text-muted small"><?= __('sepa_creditor_id') ?>:</span>
                        <span class="font-monospace fw-bold text-primary"><?= e($club->bank_creditor_id) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-3 px-3">
                        <span class="text-muted small"><?= __('sepa_creditor_iban') ?>:</span>
                        <span class="font-monospace fw-semibold"><?= format_iban($club->bank_iban) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-3 px-3">
                        <span class="text-muted small"><?= __('sepa_creditor_bic') ?>:</span>
                        <span class="font-monospace"><?= e($club->bank_bic) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-3 px-3">
                        <span class="text-muted small">Kreditinstitut:</span>
                        <span><?= e($club->bank_name ?: '-') ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Technical ISO 20022 Information -->
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-shield-lock-fill me-1"></i> SEPA-Konformität</h6>
                <p class="small text-muted mb-0">
                    Clubmanager erzeugt standardkonforme <strong>ISO 20022 SEPA Direct Debit XML-Dateien</strong> nach Schema <code>pain.008.001.02</code>. 
                    Die generierten Dateien werden von allen deutschen Banken und Sparkassen (Sparkassen-Finanzgruppe, Volksbanken Raiffeisenbanken, Deutsche Bank, Commerzbank) im Online-Banking und SFirm/StarMoney unterstützt.
                </p>
            </div>
        </div>
    </div>
</div>
