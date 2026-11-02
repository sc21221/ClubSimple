<?php
$sort = $sort ?? 'name';
$direction = $direction ?? 'asc';
$sortLink = static function (string $column) use ($sort, $direction): string {
    return '/fee-classes?' . http_build_query([
        'sort' => $column,
        'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
    ]);
};
?>
<!-- Fee Classes Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('fee_classes_title') ?></h2>
        <p class="text-muted mb-0"><?= __('fee_classes_subtitle') ?></p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="/fee-classes/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> <?= __('fee_classes_create') ?>
        </a>
    </div>
</div>

<!-- Table Card -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <?php foreach (['name' => __('field_fee_name'), 'base_amount' => __('field_fee_base_amount'), 'billing_cycle' => __('field_fee_cycle'), 'age_span' => __('field_fee_age_span'), 'members_count' => __('assigned_members_count'), 'status' => __('status')] as $column => $label): ?>
                        <th scope="col"<?= $sort === $column ? ' aria-sort="' . ($direction === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
                            <a class="text-reset text-decoration-none" href="<?= e($sortLink($column)) ?>"><?= e($label) ?> <i class="bi <?= $sort === $column ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up' ?>" aria-hidden="true"></i></a>
                        </th>
                    <?php endforeach; ?>
                    <th class="text-end"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($feeClasses)): ?>
                    <?php foreach ($feeClasses as $fc): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e($fc->name) ?></div>
                                <small class="text-muted"><?= e($fc->description ?: '-') ?></small>
                            </td>
                            <td>
                                <span class="fw-bold fs-6 text-primary"><?= format_money($fc->base_amount, $fc->currency) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($fc->billing_cycle) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-dark border">
                                    <?= $fc->min_age ?> bis <?= $fc->max_age >= 900 ? 'offen' : ($fc->max_age . ' Jahre') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary rounded-pill px-3"><?= $fc->getActiveMembersCount() ?></span>
                            </td>
                            <td>
                                <?php if ($fc->is_active): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-muted border">Inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/fee-classes/<?= $fc->id ?>/edit" class="btn btn-outline-secondary" title="<?= __('edit') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="/fee-classes/<?= $fc->id ?>/delete" method="POST" onsubmit="return confirm('Möchten Sie diese Beitragsklasse wirklich löschen?');" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="<?= __('delete') ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-tags fs-1 text-muted d-block mb-2"></i>
                            Bisher keine Beitragsklassen angelegt.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
