<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div><h2 class="h3 fw-bold mb-1"><?= __('events_title') ?></h2><p class="text-muted mb-0"><?= __('events_subtitle') ?></p></div>
    <a href="/events/create" class="btn btn-primary shadow-sm mt-3 mt-md-0"><i class="bi bi-plus-lg me-1"></i> <?= __('events_create') ?></a>
</div>
<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <?php $direction = $direction ?? 'asc'; ?>
        <thead><tr><th scope="col" aria-sort="<?= $direction === 'asc' ? 'ascending' : 'descending' ?>"><a class="text-reset text-decoration-none" href="<?= e('/events?' . http_build_query(['sort' => 'name', 'direction' => $direction === 'asc' ? 'desc' : 'asc'])) ?>"><?= e(__('event_name')) ?> <i class="bi <?= $direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down' ?>" aria-hidden="true"></i></a></th><th scope="col" class="text-end"><?= __('actions') ?></th></tr></thead>
        <tbody>
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $event): ?>
                <tr><td class="fw-bold"><?= e($event->name) ?></td><td class="text-end"><div class="d-inline-flex gap-1">
                    <a href="/events/<?= (int) $event->id ?>/members" class="btn btn-outline-primary btn-sm"><?= __('nav_members') ?></a>
                    <a href="/events/<?= (int) $event->id ?>/edit" class="btn btn-outline-secondary btn-sm"><?= __('edit') ?></a>
                    <form action="/events/<?= (int) $event->id ?>/delete" method="POST" onsubmit="return confirm(<?= e(json_encode(__('event_confirm_delete'), JSON_HEX_APOS | JSON_HEX_QUOT)) ?>);">
                        <?= csrf_field() ?><button type="submit" class="btn btn-outline-danger btn-sm"><?= __('delete') ?></button>
                    </form>
                </div></td></tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2" class="text-center py-5 text-muted"><?= __('events_none') ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div></div>