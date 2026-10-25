<!-- Audit Trail Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('audit_title') ?></h2>
        <p class="text-muted mb-0"><?= __('audit_subtitle') ?> &bull; <?= $totalLogs ?> Einträge</p>
    </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/audit" class="row g-2 align-items-center">
            <!-- Entity Type -->
            <div class="col-md-2">
                <select name="entity_type" class="form-select">
                    <option value="">Alle Bereiche</option>
                    <option value="members" <?= ($filters['entity_type'] ?? '') === 'members' ? 'selected' : '' ?>>Mitglieder (members)</option>
                    <option value="clubs" <?= ($filters['entity_type'] ?? '') === 'clubs' ? 'selected' : '' ?>>Verein (clubs)</option>
                    <option value="fee_classes" <?= ($filters['entity_type'] ?? '') === 'fee_classes' ? 'selected' : '' ?>>Beitragsklassen</option>
                    <option value="member_fee_classes" <?= ($filters['entity_type'] ?? '') === 'member_fee_classes' ? 'selected' : '' ?>>Beitragszuweisungen</option>
                    <option value="membership_fees" <?= ($filters['entity_type'] ?? '') === 'membership_fees' ? 'selected' : '' ?>>SEPA-Läufe</option>
                    <option value="users" <?= ($filters['entity_type'] ?? '') === 'users' ? 'selected' : '' ?>>Benutzer</option>
                </select>
            </div>

            <!-- Member / Entity ID -->
            <div class="col-md-2">
                <input type="number" name="entity_id" class="form-control" placeholder="Datensatz-ID" value="<?= e($filters['entity_id'] ?? '') ?>">
            </div>

            <!-- User -->
            <div class="col-md-3">
                <select name="user_id" class="form-select">
                    <option value="">Alle Benutzer</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u->id ?>" <?= ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' ?>>
                            <?= e($u->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date From -->
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from'] ?? '') ?>" title="Datum von">
            </div>

            <!-- Date To -->
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to'] ?? '') ?>" title="Datum bis">
            </div>

            <!-- Submit -->
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="<?= __('filter') ?>">
                    <i class="bi bi-funnel"></i>
                </button>
                <?php if (!empty($filters['entity_type']) || !empty($filters['entity_id']) || !empty($filters['user_id']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                    <a href="/audit" class="btn btn-outline-secondary" title="<?= __('reset') ?>">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= __('audit_timestamp') ?></th>
                    <th><?= __('audit_user') ?></th>
                    <th><?= __('audit_action') ?></th>
                    <th><?= __('audit_entity') ?></th>
                    <th><?= __('audit_changes') ?></th>
                    <th>IP-Adresse</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $actionBadge = match(true) {
                                str_contains($log->action, 'CREATED') => 'bg-success',
                                str_contains($log->action, 'UPDATED') => 'bg-primary',
                                str_contains($log->action, 'DELETED') => 'bg-danger',
                                str_contains($log->action, 'ARCHIVED') => 'bg-warning text-dark',
                                str_contains($log->action, 'ANONYMIZED') => 'bg-secondary',
                                default => 'bg-dark'
                            };
                            $oldVal = $log->getOldValues();
                            $newVal = $log->getNewValues();
                        ?>
                        <tr>
                            <td class="text-muted small"><?= $log->id ?></td>
                            <td class="small fw-semibold"><?= format_date($log->timestamp, 'd.m.Y H:i:s') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($log->getUserName()) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $actionBadge ?> px-2 py-1"><?= e($log->action) ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold"><?= e($log->entity_type) ?></span>
                                <?php if ($log->entity_id): ?>
                                    <span class="text-muted">#<?= $log->entity_id ?></span>
                                    <?php if ($log->entity_type === 'members'): ?>
                                        <a href="/members/<?= $log->entity_id ?>" class="text-decoration-none ms-1" title="Mitglied aufrufen">&rarr;</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($newVal || $oldVal): ?>
                                    <button class="btn btn-sm btn-outline-secondary py-0" type="button" data-bs-toggle="collapse" data-bs-target="#log-diff-<?= $log->id ?>" aria-expanded="false">
                                        <i class="bi bi-code-slash me-1"></i> Details anzeigen
                                    </button>
                                    <div class="collapse mt-2" id="log-diff-<?= $log->id ?>">
                                        <div class="card card-body p-2 bg-light small font-monospace" style="max-height: 200px; overflow-y: auto;">
                                            <?php if ($oldVal): ?>
                                                <div class="text-danger fw-bold mb-1">Vorher (Old):</div>
                                                <pre class="mb-2 text-wrap" style="font-size: 0.75rem;"><?= htmlspecialchars(json_encode($oldVal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                            <?php endif; ?>
                                            <?php if ($newVal): ?>
                                                <div class="text-success fw-bold mb-1">Nachher (New):</div>
                                                <pre class="mb-0 text-wrap" style="font-size: 0.75rem;"><?= htmlspecialchars(json_encode($newVal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small font-monospace"><?= e($log->ip_address ?: '127.0.0.1') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-text fs-1 text-muted d-block mb-2"></i>
                            <?= __('audit_no_logs') ?>
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
                Seite <?= $currentPage ?> von <?= $totalPages ?> (<?= $totalLogs ?> Einträge)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="/audit?page=<?= $currentPage - 1 ?>&entity_type=<?= urlencode($filters['entity_type'] ?? '') ?>&entity_id=<?= urlencode((string)($filters['entity_id'] ?? '')) ?>&user_id=<?= urlencode((string)($filters['user_id'] ?? '')) ?>&date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>">
                        &laquo;
                    </a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="/audit?page=<?= $p ?>&entity_type=<?= urlencode($filters['entity_type'] ?? '') ?>&entity_id=<?= urlencode((string)($filters['entity_id'] ?? '')) ?>&user_id=<?= urlencode((string)($filters['user_id'] ?? '')) ?>&date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="/audit?page=<?= $currentPage + 1 ?>&entity_type=<?= urlencode($filters['entity_type'] ?? '') ?>&entity_id=<?= urlencode((string)($filters['entity_id'] ?? '')) ?>&user_id=<?= urlencode((string)($filters['user_id'] ?? '')) ?>&date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>">
                        &raquo;
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>
