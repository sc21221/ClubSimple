<?php
/** @var array<int, array<string, mixed>> $items */
/** @var array<int, array<string, mixed>> $runs */
$itemsByRun = [];
$legacyItems = [];
foreach ($items as $item) {
    if (($item['sepa_run_id'] ?? null) === null) {
        $legacyItems[] = $item;
    } else {
        $itemsByRun[(int) $item['sepa_run_id']][] = $item;
    }
}
$groups = [];
foreach ($runs as $run) {
    $runId = (int) $run['id'];
    $groups[] = ['run' => $run, 'id' => $runId, 'items' => $itemsByRun[$runId] ?? []];
    unset($itemsByRun[$runId]);
}
foreach ($itemsByRun as $runId => $runItems) {
    $groups[] = ['run' => null, 'id' => $runId, 'items' => $runItems];
}
$groups[] = ['run' => null, 'id' => null, 'items' => $legacyItems];
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0"><?= __('sepa_open_title') ?></h2>
    <a href="/sepa" class="btn btn-outline-secondary"><?= __('back') ?></a>
</div>
<p class="alert alert-info"><?= __('sepa_open_help') ?></p>
<p class="alert alert-warning"><?= __('sepa_open_delete_help') ?></p>
<form action="/sepa/open/paid" method="POST">
    <?= csrf_field() ?>
    <div class="card shadow-sm border-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th scope="col">
                    <input class="form-check-input" type="checkbox" id="select-all-items" aria-label="<?= e(__('sepa_open_select_all')) ?>" <?= !$items ? 'disabled' : '' ?>>
                    <label class="visually-hidden" for="select-all-items"><?= __('sepa_open_select_all') ?></label>
                </th>
                <th scope="col"><?= __('sepa_open_member') ?></th>
                <th scope="col"><?= __('sepa_open_fee_class') ?></th>
                <th scope="col"><?= __('sepa_open_due') ?></th>
                <th scope="col"><?= __('sepa_open_amount') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($groups as $group): ?>
                <tr class="table-light">
                    <th colspan="5" scope="rowgroup">
                        <?php if ($group['id'] === null): ?>
                            <?= __('sepa_open_legacy') ?>
                        <?php else: ?>
                            <?= __('sepa_open_run') ?> #<?= (int) $group['id'] ?>
                            <?php if ($group['run'] !== null): ?>
                                &mdash; <?= __('sepa_open_created') ?>: <?= e(format_date($group['run']['created_at'], 'd.m.Y H:i')) ?>
                                &mdash; <?= __('sepa_open_due') ?>: <?= e(format_date($group['run']['due_date'])) ?>
                                <button type="submit" form="delete-run-<?= (int) $group['id'] ?>" class="btn btn-outline-danger btn-sm ms-2"><?= __('sepa_open_delete_run') ?></button>
                            <?php else: ?>
                                &mdash; <?= __('sepa_open_run_missing') ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </th>
                </tr>
            <?php foreach ($group['items'] as $item): ?>
                <tr>
                    <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int) $item['id'] ?>" aria-label="<?= e(__('sepa_open_select') . ': ' . $item['first_name'] . ' ' . $item['last_name'] . ', ' . $item['fee_class_name']) ?>"></td>
                    <td><a href="/members/<?= (int) $item['member_id'] ?>"><?= e($item['last_name'] . ', ' . $item['first_name']) ?></a></td>
                    <td><?= e($item['fee_class_name']) ?></td>
                    <td><?= e(format_date($item['due_date'])) ?></td>
                    <td><?= e(format_money((float) $item['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$group['items']): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted"><?= __('sepa_open_empty') ?></td></tr>
            <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($items): ?>
        <button type="submit" class="btn btn-primary mt-3"><?= __('sepa_open_mark_paid') ?></button>
    <?php endif; ?>
</form>
<?php foreach ($runs as $run): ?>
    <form id="delete-run-<?= (int) $run['id'] ?>" action="/sepa/runs/<?= (int) $run['id'] ?>/delete" method="POST" onsubmit="return confirm(<?= e(json_encode(__('sepa_open_delete_confirm'), JSON_HEX_APOS | JSON_HEX_QUOT)) ?>);">
        <?= csrf_field() ?>
    </form>
<?php endforeach; ?>
<script>
    const selectAllItems = document.getElementById('select-all-items');
    const openItems = document.querySelectorAll('input[name="ids[]"]');
    selectAllItems.addEventListener('change', () => {
        openItems.forEach(item => { item.checked = selectAllItems.checked; });
    });
    openItems.forEach(item => item.addEventListener('change', () => {
        const selected = document.querySelectorAll('input[name="ids[]"]:checked').length;
        selectAllItems.checked = selected === openItems.length;
        selectAllItems.indeterminate = selected > 0 && selected < openItems.length;
    }));
</script>