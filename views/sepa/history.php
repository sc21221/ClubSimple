<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('sepa_history_title') ?></h2>
        <p class="text-muted mb-0">Übersicht aller durchgeführten SEPA-Lastschriftläufe</p>
        <p class="text-muted small mb-0">Ältere Buchungen ohne Laufkennung sind unter „Offene Posten“ sichtbar, sofern sie noch offen sind.</p>
    </div>
    <a href="/sepa" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> <?= __('back') ?>
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fälligkeitsdatum (Einzug)</th>
                    <th>Erstellungsdatum</th>
                    <th>Mitglieder</th>
                    <th>Buchungen</th>
                    <th>Gesamtvolumen</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($runs)): ?>
                    <?php foreach ($runs as $run): ?>
                        <tr>
                            <td>
                                <span class="fw-bold fs-6"><?= format_date($run['due_date']) ?></span>
                            </td>
                            <td class="text-muted small">
                                <?= format_date($run['run_date'], 'd.m.Y H:i') ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= $run['member_count'] ?> Mitglieder</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= $run['transaction_count'] ?> Buchungen</span>
                            </td>
                            <td class="fw-bold text-primary fs-6">
                                <?= format_money($run['total_amount']) ?>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                                    <?= e($run['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history fs-2 text-muted d-block mb-2"></i>
                            Bisher keine archivierten SEPA-Läufe vorhanden.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
