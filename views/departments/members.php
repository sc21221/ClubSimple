<?php /** @var object $department */ ?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-1"><?= __('departments_members') ?>: <?= e($department->name) ?></h2>
    <a href="/departments" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> <?= __('back') ?></a>
</div>
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th><?= __('field_full_name') ?></th><th><?= __('field_email') ?></th><th><?= __('field_status') ?></th><th><?= __('department_assigned_date') ?></th><th><?= __('department_description') ?></th></tr></thead>
            <tbody>
            <?php if (!empty($members)): ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><a href="/members/<?= (int) $member->id ?>" class="fw-bold text-dark text-decoration-none"><?= e(trim($member->first_name . ' ' . $member->last_name)) ?></a></td>
                        <td><?= e($member->email ?? '') ?></td>
                        <td><?= e($member->status ?? '') ?></td>
                        <td><?= e($member->assigned_date ?? '') ?></td>
                        <td><?= e($member->description ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center py-5 text-muted"><?= __('members_none_found') ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>