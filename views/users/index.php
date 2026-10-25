<!-- User Management Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('users_title') ?></h2>
        <p class="text-muted mb-0"><?= __('users_subtitle') ?></p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="/users/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> <?= __('users_create') ?>
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= __('field_user_name') ?></th>
                    <th><?= __('field_user_email') ?></th>
                    <th><?= __('field_user_role') ?></th>
                    <th><?= __('field_user_locale') ?></th>
                    <th><?= __('field_user_phone') ?></th>
                    <th>Erstellt am</th>
                    <th class="text-end"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="fw-bold d-flex align-items-center">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-inline-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        <?= strtoupper(substr($u->name, 0, 1)) ?>
                                    </div>
                                    <?= e($u->name) ?>
                                </div>
                            </td>
                            <td><?= e($u->email) ?></td>
                            <td>
                                <?php if ($u->isAdmin()): ?>
                                    <span class="badge bg-danger px-2 py-1"><?= __('role_admin') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-primary px-2 py-1"><?= __('role_treasurer') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= strtoupper($u->locale ?? 'de') ?></span>
                            </td>
                            <td><?= e($u->phone ?: '-') ?></td>
                            <td class="text-muted small"><?= format_date($u->created_at, 'd.m.Y H:i') ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/users/<?= $u->id ?>/edit" class="btn btn-outline-secondary" title="<?= __('edit') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($currentUser && (int)$currentUser->id !== (int)$u->id): ?>
                                        <form action="/users/<?= $u->id ?>/delete" method="POST" onsubmit="return confirm('Benutzerkonto wirklich löschen?');" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="<?= __('delete') ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
