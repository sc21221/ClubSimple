<!-- Dashboard Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><?= __('nav_dashboard') ?></h2>
        <p class="text-muted mb-0"><?= e($club->name) ?> &bull; <?= __('dashboard_welcome') ?>, <?= e($currentUser->name) ?></p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="/members/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> <?= __('nav_member_create') ?>
        </a>
        <a href="/sepa" class="btn btn-outline-primary shadow-sm">
            <i class="bi bi-cash-stack me-1"></i> <?= __('nav_sepa_run') ?>
        </a>
    </div>
</div>

<!-- 4 Mandatory Specification Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Club Name & Representative -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase"><?= __('dashboard_club') ?></span>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-building"></i>
                </div>
            </div>
            <div>
                <h5 class="fw-bold mb-1 text-truncate" title="<?= e($club->name) ?>"><?= e($club->name) ?></h5>
                <div class="text-muted small">
                    <i class="bi bi-person-badge text-secondary me-1"></i>
                    <strong><?= __('dashboard_boss') ?>:</strong> <?= e($club->boss ?: '-') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Active Members -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase"><?= __('dashboard_active_members') ?></span>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div>
                <h3 class="fw-bold mb-1 text-success"><?= $activeMembersCount ?></h3>
                <div class="text-muted small">
                    <a href="/members?status=Aktiv" class="text-decoration-none">
                        <?= __('members_title') ?> &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Fee Classes -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase"><?= __('dashboard_fee_classes') ?></span>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-tags-fill"></i>
                </div>
            </div>
            <div>
                <h3 class="fw-bold mb-1 text-dark"><?= $totalFeeClassesCount ?></h3>
                <div class="text-muted small">
                    <a href="/fee-classes" class="text-decoration-none">
                        <?= __('fee_classes_title') ?> &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Date & Total Amount of Last Generated SEPA Run -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase"><?= __('dashboard_last_sepa') ?></span>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-credit-card-2-front-fill"></i>
                </div>
            </div>
            <div>
                <?php if (!empty($lastSepaRun['date'])): ?>
                    <h4 class="fw-bold mb-1 text-primary"><?= format_money($lastSepaRun['total_amount']) ?></h4>
                    <div class="text-muted small">
                        <i class="bi bi-calendar3 me-1"></i> <?= format_date($lastSepaRun['due_date'] ?? $lastSepaRun['date']) ?>
                        (<?= $lastSepaRun['count'] ?> Buchungen)
                    </div>
                <?php else: ?>
                    <h5 class="fw-bold mb-1 text-muted"><?= __('dashboard_no_sepa_yet') ?></h5>
                    <div class="small">
                        <a href="/sepa" class="text-primary text-decoration-none"><?= __('nav_sepa_run') ?> &rarr;</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Row: Status Breakdown & Quick Access -->
<div class="row g-4 mb-4">
    <!-- Status Breakdown -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pie-chart-fill me-2 text-primary"></i> <?= __('dashboard_member_status_overview') ?></span>
                <span class="badge bg-light text-dark border"><?= array_sum($statusCounts) ?> <?= __('dashboard_total_members') ?></span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-around text-center py-2">
                    <div>
                        <div class="badge-status-aktiv px-3 py-2 rounded-3 fs-5 fw-bold"><?= $statusCounts['Aktiv'] ?? 0 ?></div>
                        <div class="small text-muted mt-1 fw-semibold"><?= __('status_active') ?></div>
                    </div>
                    <div>
                        <div class="badge-status-inaktiv px-3 py-2 rounded-3 fs-5 fw-bold"><?= $statusCounts['Inaktiv'] ?? 0 ?></div>
                        <div class="small text-muted mt-1 fw-semibold"><?= __('status_inactive') ?></div>
                    </div>
                    <div>
                        <div class="badge-status-gesperrt px-3 py-2 rounded-3 fs-5 fw-bold"><?= $statusCounts['Gesperrt'] ?? 0 ?></div>
                        <div class="small text-muted mt-1 fw-semibold"><?= __('status_locked') ?></div>
                    </div>
                </div>

                <hr class="my-3 text-muted">

                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span><i class="bi bi-info-circle me-1"></i> Nur Mitglieder mit Status "Aktiv" werden beim SEPA-Lastschriftlauf abgerechnet.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header card-header-custom py-3">
                <span class="fw-bold"><i class="bi bi-lightning-charge-fill me-2 text-warning"></i> <?= __('dashboard_quick_actions') ?></span>
            </div>
            <div class="card-body d-flex flex-column justify-content-around">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <a href="/members/create" class="btn btn-outline-primary w-100 p-3 text-start d-flex align-items-center">
                            <i class="bi bi-person-plus fs-3 me-3"></i>
                            <div>
                                <div class="fw-bold"><?= __('nav_member_create') ?></div>
                                <small class="text-muted">Neues Mitglied erfassen</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="/sepa" class="btn btn-outline-success w-100 p-3 text-start d-flex align-items-center">
                            <i class="bi bi-play-circle fs-3 me-3"></i>
                            <div>
                                <div class="fw-bold"><?= __('nav_sepa_run') ?></div>
                                <small class="text-muted">SEPA XML erzeugen</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="/reports/birthdays?round_only=1" class="btn btn-outline-info w-100 p-3 text-start d-flex align-items-center">
                            <i class="bi bi-cake2 fs-3 me-3"></i>
                            <div>
                                <div class="fw-bold"><?= __('nav_birthdays') ?></div>
                                <small class="text-muted">Runde Geburtstage</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="/reports/anniversaries" class="btn btn-outline-warning w-100 p-3 text-start d-flex align-items-center">
                            <i class="bi bi-award fs-3 me-3"></i>
                            <div>
                                <div class="fw-bold"><?= __('nav_anniversaries') ?></div>
                                <small class="text-muted">Vereinsjubiläen</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Milestone Lists (Widgets) -->
<div class="row g-4">
    <!-- Milestone Birthdays -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-cake2-fill me-2 text-danger"></i> <?= __('dashboard_upcoming_birthdays') ?></span>
                <a href="/reports/birthdays?round_only=1" class="btn btn-sm btn-link text-decoration-none">Alle &rarr;</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($upcomingBirthdays)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($upcomingBirthdays as $bday): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <a href="/members/<?= $bday['member']->id ?>" class="fw-semibold text-dark text-decoration-none">
                                        <?= e($bday['full_name']) ?>
                                    </a>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar-event me-1"></i> <?= format_date($bday['birth_date']) ?>
                                        &bull; in <?= $bday['days_until'] ?> Tag(en)
                                    </div>
                                </div>
                                <span class="badge bg-danger rounded-pill fs-6 px-3">
                                    <?= $bday['turning_age'] ?> Jahre
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-check fs-2 text-muted d-block mb-2"></i>
                        Keine anstehenden runden Geburtstage in den nächsten 60 Tagen.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Milestone Anniversaries -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header card-header-custom py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-award-fill me-2 text-warning"></i> <?= __('dashboard_upcoming_anniversaries') ?> (<?= date('Y') ?>)</span>
                <a href="/reports/anniversaries" class="btn btn-sm btn-link text-decoration-none">Alle &rarr;</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($upcomingAnniversaries)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($upcomingAnniversaries as $anniv): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <a href="/members/<?= $anniv['member']->id ?>" class="fw-semibold text-dark text-decoration-none">
                                        <?= e($anniv['full_name']) ?>
                                    </a>
                                    <div class="text-muted small">
                                        <i class="bi bi-flag me-1"></i> Eintritt: <?= format_date($anniv['join_date']) ?> &bull; <?= e($anniv['membership_type']) ?>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark rounded-pill fs-6 px-3">
                                    <?= $anniv['years'] ?> Jahre
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-check fs-2 text-muted d-block mb-2"></i>
                        Keine Vereinsjubiläen für das laufende Jahr gefunden.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
