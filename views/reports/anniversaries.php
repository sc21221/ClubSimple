<!-- Anniversaries Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('anniversaries_title') ?> (<?= $targetYear ?>)</h2>
        <p class="text-muted mb-0"><?= __('anniversaries_subtitle') ?> &bull; <?= count($results) ?> Treffer</p>
    </div>
    <!-- Export Buttons -->
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="/reports/anniversaries?year=<?= $targetYear ?>&milestones_only=<?= $milestonesOnly ? '1' : '0' ?>&format=csv" class="btn btn-outline-success shadow-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> <?= __('export_csv') ?>
        </a>
        <a href="/reports/anniversaries?year=<?= $targetYear ?>&milestones_only=<?= $milestonesOnly ? '1' : '0' ?>&format=pdf" target="_blank" class="btn btn-outline-danger shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> <?= __('export_pdf') ?>
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/reports/anniversaries" class="row g-3 align-items-center">
            <!-- Year -->
            <div class="col-md-4">
                <label for="year" class="form-label small fw-semibold">Jubiläumsjahr</label>
                <select name="year" id="year" class="form-select">
                    <?php 
                        $currentYear = (int)date('Y');
                        for ($y = $currentYear - 2; $y <= $currentYear + 3; $y++):
                    ?>
                        <option value="<?= $y ?>" <?= $targetYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Milestones Only Checkbox -->
            <div class="col-md-5 pt-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="milestones_only" name="milestones_only" value="1" <?= $milestonesOnly ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold text-warning" for="milestones_only">
                        <?= __('filter_anniversary_milestones') ?>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div class="col-md-3 pt-md-4 text-end">
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
                    <th>Mitgliedsart</th>
                    <th>Eintrittsdatum</th>
                    <th>Mitgliedsdauer</th>
                    <th>Jubiläumsstatus</th>
                    <th>Wohnort</th>
                    <th class="text-end">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $item): ?>
                        <tr class="<?= $item['is_milestone'] ? 'table-warning bg-opacity-25' : '' ?>">
                            <td>
                                <a href="/members/<?= $item['member']->id ?>" class="fw-bold text-dark text-decoration-none">
                                    <?= e($item['full_name']) ?>
                                </a>
                                <div class="text-muted small"><?= e($item['email'] ?: '-') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($item['membership_type']) ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold"><?= format_date($item['join_date']) ?></span>
                            </td>
                            <td>
                                <span class="fs-5 fw-bold text-primary"><?= $item['years'] ?> Jahre</span>
                            </td>
                            <td>
                                <?php if ($item['is_milestone']): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fs-6">
                                        <i class="bi bi-award-fill me-1"></i> <?= $item['years'] ?>-jähriges Jubiläum!
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border"><?= $item['years'] ?> Jahre</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($item['city'] ?: '-') ?>
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
                            <i class="bi bi-award fs-1 text-muted d-block mb-2"></i>
                            <?= __('report_no_data') ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
