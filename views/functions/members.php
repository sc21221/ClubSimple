<?php /** @var object $function */ ?>
<?php $isHistory = $isHistory ?? $history ?? false; ?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-1"><?= !empty($isHistory) ? __('functions_history') : __('functions_members') ?>: <?= e($function->name) ?></h2>
    <div class="d-flex gap-2">
        <a href="/functions/<?= (int) $function->id ?>/<?= !empty($isHistory) ? 'members' : 'history' ?>" class="btn btn-outline-primary"><?= !empty($isHistory) ? __('functions_members') : __('functions_history') ?></a>
        <a href="/functions" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> <?= __('back') ?></a>
    </div>
</div>
<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th><?= __('field_full_name') ?></th><th><?= __('field_email') ?></th><th><?= __('field_status') ?></th><th><?= __('valid_from') ?></th><th><?= __('valid_until') ?></th><th><?= __('function_description') ?></th></tr></thead>
        <tbody>
        <?php if (!empty($members)): ?>
            <?php foreach ($members as $member): ?>
                <tr>
                    <td><a href="/members/<?= (int) $member->id ?>" class="fw-bold text-dark text-decoration-none"><?= e(trim($member->first_name . ' ' . $member->last_name)) ?></a></td>
                    <td><?= e($member->email ?? '') ?></td><td><?= e($member->status ?? '') ?></td>
                    <td><?= e($member->start_date ?? '') ?></td><td><?= e($member->end_date ?? '') ?></td><td><?= e($member->description ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" class="text-center py-5 text-muted"><?= __('members_none_found') ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div></div>