<?php
/** @var array $filters */
/** @var array $feeClasses */
/** @var int $totalMembers */
/** @var int $totalPages */
/** @var int $currentPage */
$sort = $sort ?? 'name';
$direction = $direction ?? 'asc';
$query = array_filter($filters, static fn($value) => $value !== null && $value !== '');
$query['sort'] = $sort;
$query['direction'] = $direction;
$sortLink = static function (string $column) use ($query, $sort, $direction): string {
    $params = $query;
    $params['sort'] = $column;
    $params['direction'] = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    return '/members?' . http_build_query($params);
};
$pageLink = static function (int $page) use ($query): string {
    return '/members?' . http_build_query(array_merge($query, ['page' => $page]));
};
?>
<!-- Member Management Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('members_title') ?></h2>
        <p class="text-muted mb-0"><?= __('members_subtitle') ?> &bull; <?= __('members_total_found', ['count' => $totalMembers]) ?></p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="/members/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> <?= __('members_create') ?>
        </a>
    </div>
</div>

<!-- Search & Multi-Faceted Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/members" class="row g-2 align-items-center">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="direction" value="<?= e($direction) ?>">
            <!-- Search Text -->
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="<?= __('members_filter_search') ?>" value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value=""><?= __('members_filter_all_statuses') ?></option>
                    <option value="Aktiv" <?= ($filters['status'] ?? '') === 'Aktiv' ? 'selected' : '' ?>><?= __('status_active') ?></option>
                    <option value="Inaktiv" <?= ($filters['status'] ?? '') === 'Inaktiv' ? 'selected' : '' ?>><?= __('status_inactive') ?></option>
                    <option value="Gesperrt" <?= ($filters['status'] ?? '') === 'Gesperrt' ? 'selected' : '' ?>><?= __('status_locked') ?></option>
                </select>
            </div>

            <!-- Fee Class Filter -->
            <div class="col-md-3">
                <select name="fee_class_id" class="form-select">
                    <option value=""><?= __('members_filter_all_fee_classes') ?></option>
                    <?php foreach ($feeClasses as $fc): ?>
                        <option value="<?= $fc->id ?>" <?= ($filters['fee_class_id'] ?? '') == $fc->id ? 'selected' : '' ?>>
                            <?= e($fc->name) ?> (<?= format_money($fc->base_amount) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="<?= __('filter') ?>">
                    <i class="bi bi-funnel"></i>
                </button>
                <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['fee_class_id'])): ?>
                    <a href="/members" class="btn btn-outline-secondary" title="<?= __('reset') ?>">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- DataGrid Table -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <?php foreach (['name' => __('field_full_name'), 'membership_type' => __('field_membership_type'), 'status' => __('field_status'), 'age' => __('field_age') . ' / ' . __('field_birth_date'), 'join_date' => __('field_join_date')] as $column => $label): ?>
                        <th scope="col"<?= $sort === $column ? ' aria-sort="' . ($direction === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
                            <a class="text-reset text-decoration-none" href="<?= e($sortLink($column)) ?>"><?= e($label) ?> <i class="bi <?= $sort === $column ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up' ?>" aria-hidden="true"></i></a>
                        </th>
                    <?php endforeach; ?>
                    <th scope="col"><?= __('assigned_fees') ?></th>
                    <th scope="col" class="text-end"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($members)): ?>
                    <?php foreach ($members as $m): ?>
                        <?php 
                            $assignedFeeList = $m->getActiveFeeClasses();
                            $statusClass = match($m->status) {
                                'Aktiv' => 'badge-status-aktiv',
                                'Inaktiv' => 'badge-status-inaktiv',
                                'Gesperrt' => 'badge-status-gesperrt',
                                default => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <td>
                                <a href="/members/<?= $m->id ?>" class="fw-bold text-dark text-decoration-none">
                                    <?= e($m->getFullName()) ?>
                                </a>
                                <div class="text-muted small">
                                    <?= e($m->email ?: '') ?>
                                    <?= (!empty($m->email) && !empty($m->city)) ? '&bull;' : '' ?>
                                    <?= e($m->city ?: '') ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($m->membership_type) ?></span>
                                <?php if ($m->invoice_marker): ?>
                                    <span class="badge bg-info text-dark" title="Rechnungsempfänger"><i class="bi bi-receipt"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $statusClass ?> px-2 py-1"><?= e($m->status) ?></span>
                            </td>
                            <td>
                                <div><?= $m->getAge() ?> Jahre</div>
                                <small class="text-muted"><?= format_date($m->birth_date) ?></small>
                            </td>
                            <td>
                                <div><?= format_date($m->join_date) ?></div>
                                <small class="text-muted"><?= $m->getYearsOfMembership() ?> J. Mitglied</small>
                            </td>
                            <td>
                                <?php if (!empty($assignedFeeList)): ?>
                                    <?php foreach ($assignedFeeList as $af): ?>
                                        <span class="badge bg-secondary-subtle text-dark border me-1">
                                            <?= e($af['fee_class_name']) ?> (<?= format_money($af['custom_amount'] ?? $af['base_amount']) ?>)
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small"><?= __('none') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/members/<?= $m->id ?>" class="btn btn-outline-primary" title="<?= __('action_view') ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/members/<?= $m->id ?>/edit" class="btn btn-outline-secondary" title="<?= __('action_edit') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <?php if ($m->status === 'Aktiv'): ?>
                                            <li>
                                                <form action="/members/<?= $m->id ?>/archive" method="POST" onsubmit="return confirm('<?= __('confirm_archive') ?>');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="dropdown-item text-warning">
                                                        <i class="bi bi-archive me-2"></i> <?= __('action_archive') ?>
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <li>
                                            <a class="dropdown-item" href="/members/<?= $m->id ?>/export/pdf">
                                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> <?= __('gdpr_export_data') ?> (PDF)
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="/members/<?= $m->id ?>/delete" method="POST" onsubmit="return confirm('<?= __('confirm_delete') ?>');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i> <?= __('action_delete') ?>
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 text-muted d-block mb-2"></i>
                            <?= __('members_none_found') ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small">
                Seite <?= $currentPage ?> von <?= $totalPages ?> (<?= $totalMembers ?> Einträge)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($pageLink($currentPage - 1)) ?>">
                        &laquo;
                    </a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e($pageLink($p)) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($pageLink($currentPage + 1)) ?>">
                        &raquo;
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>
