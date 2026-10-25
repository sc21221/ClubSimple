<!-- Birthday Reports Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('birthdays_title') ?></h2>
        <p class="text-muted mb-0"><?= __('birthdays_subtitle') ?> &bull; <?= count($results) ?> Treffer</p>
    </div>
    <!-- Export Buttons -->
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="/reports/birthdays?days=<?= $days ?>&round_only=<?= $roundOnly ? '1' : '0' ?>&month=<?= $month ?? '' ?>&format=csv" class="btn btn-outline-success shadow-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> <?= __('export_csv') ?>
        </a>
        <a href="/reports/birthdays?days=<?= $days ?>&round_only=<?= $roundOnly ? '1' : '0' ?>&month=<?= $month ?? '' ?>&format=pdf" target="_blank" class="btn btn-outline-danger shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> <?= __('export_pdf') ?>
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/reports/birthdays" class="row g-3 align-items-center">
            <!-- Timeframe -->
            <div class="col-md-4">
                <label for="days" class="form-label small fw-semibold"><?= __('filter_timeframe') ?></label>
                <select name="days" id="days" class="form-select">
                    <option value="30" <?= $days === 30 ? 'selected' : '' ?>><?= __('timeframe_30') ?></option>
                    <option value="60" <?= $days === 60 ? 'selected' : '' ?>><?= __('timeframe_60') ?></option>
                    <option value="90" <?= $days === 90 ? 'selected' : '' ?>><?= __('timeframe_90') ?></option>
                    <option value="180" <?= $days === 180 ? 'selected' : '' ?>><?= __('timeframe_180') ?></option>
                    <option value="365" <?= $days === 365 ? 'selected' : '' ?>><?= __('timeframe_365') ?></option>
                </select>
            </div>

            <!-- Specific Month (Optional) -->
            <div class="col-md-3">
                <label for="month" class="form-label small fw-semibold">Nach Monat filtern</label>
                <select name="month" id="month" class="form-select">
                    <option value="">Alle Monate</option>
                    <?php 
                        $monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
                        foreach ($monthNames as $idx => $mName):
                            $mNum = $idx + 1;
                    ?>
                        <option value="<?= $mNum ?>" <?= $month === $mNum ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Milestone Round Only Checkbox -->
            <div class="col-md-3 pt-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="round_only" name="round_only" value="1" <?= $roundOnly ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold text-danger" for="round_only">
                        <?= __('filter_round_only') ?>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div class="col-md-2 pt-md-4 text-end">
                <button type="submit" class="btn btn-primary w-100 shadow-sm">
                    <i class="bi bi-funnel me-1"></i> <?= __('filter') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mitglied / Name</th>
                    <th>Geburtsdatum</th>
                    <th>Wird (Alter)</th>
                    <th>Status Jubiläum</th>
                    <th>Tage bis zum Geburtstag</th>
                    <th>Kontakt</th>
                    <th class="text-end">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $item): ?>
                        <tr class="<?= $item['is_round'] ? 'table-warning bg-opacity-25' : '' ?>">
                            <td>
                                <a href="/members/<?= $item['member']->id ?>" class="fw-bold text-dark text-decoration-none">
                                    <?= e($item['full_name']) ?>
                                </a>
                                <div class="text-muted small"><?= e($item['city'] ?: '-') ?></div>
                            </td>
                            <td>
                                <div><?= format_date($item['birth_date']) ?></div>
                                <small class="text-muted">Nächster: <?= format_date($item['next_birthday']) ?></small>
                            </td>
                            <td>
                                <span class="fs-5 fw-bold <?= $item['is_round'] ? 'text-danger' : 'text-primary' ?>">
                                    <?= $item['turning_age'] ?> Jahre
                                </span>
                            </td>
                            <td>
                                <?php if ($item['is_round']): ?>
                                    <span class="badge bg-danger rounded-pill px-3 py-1 fs-6">
                                        <i class="bi bi-star-fill me-1"></i> Runder Geburtstag!
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Regulär</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['days_until'] === 0): ?>
                                    <span class="badge bg-success fs-6">Heute!</span>
                                <?php else: ?>
                                    <span class="fw-semibold">in <?= $item['days_until'] ?> Tag(en)</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <div><i class="bi bi-envelope me-1 text-muted"></i> <?= e($item['email'] ?: '-') ?></div>
                                <div><i class="bi bi-telephone me-1 text-muted"></i> <?= e($item['phone'] ?: '-') ?></div>
                            </td>
                            <td class="text-end">
                                <a href="/members/<?= $item['member']->id ?>" class="btn btn-sm btn-outline-primary">
                                    <?= __('details') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-cake2 fs-1 text-muted d-block mb-2"></i>
                            <?= __('report_no_data') ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
