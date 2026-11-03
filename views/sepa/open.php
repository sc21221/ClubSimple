<?php /** @var array<int, array<string, mixed>> $items */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0"><?= __('sepa_open_title') ?></h2>
    <a href="/sepa" class="btn btn-outline-secondary"><?= __('back') ?></a>
</div>
<p class="alert alert-info"><?= __('sepa_open_help') ?></p>
<form action="/sepa/open/paid" method="POST">
    <?= csrf_field() ?>
    <div class="card shadow-sm border-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th scope="col"><input class="form-check-input" type="checkbox" id="select-all-items" aria-label="<?= e(__('sepa_open_select_all')) ?>" <?= !$items ? 'disabled' : '' ?>></th>
                <th scope="col"><?= __('sepa_open_member') ?></th>
                <th scope="col"><?= __('sepa_open_fee_class') ?></th>
                <th scope="col"><?= __('sepa_open_due') ?></th>
                <th scope="col"><?= __('sepa_open_amount') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int) $item['id'] ?>" aria-label="<?= e(__('sepa_open_select') . ': ' . $item['first_name'] . ' ' . $item['last_name'] . ', ' . $item['fee_class_name']) ?>"></td>
                    <td><a href="/members/<?= (int) $item['member_id'] ?>"><?= e($item['last_name'] . ', ' . $item['first_name']) ?></a></td>
                    <td><?= e($item['fee_class_name']) ?></td>
                    <td><?= e(format_date($item['due_date'])) ?></td>
                    <td><?= e(format_money((float) $item['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted"><?= __('sepa_open_empty') ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($items): ?>
        <button type="submit" class="btn btn-primary mt-3"><?= __('sepa_open_mark_paid') ?></button>
    <?php endif; ?>
</form>
<script>
    const selectAllItems = document.getElementById('select-all-items');
    const openItems = document.querySelectorAll('input[name="ids[]"]');

    selectAllItems.addEventListener('change', () => {
        openItems.forEach(item => { item.checked = selectAllItems.checked; });
        selectAllItems.indeterminate = false;
    });

    openItems.forEach(item => item.addEventListener('change', () => {
        const selected = Array.from(openItems).filter(item => item.checked).length;
        selectAllItems.checked = selected === openItems.length;
        selectAllItems.indeterminate = selected > 0 && selected < openItems.length;
    }));
</script>