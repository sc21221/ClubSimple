<!DOCTYPE html>
<html lang="<?= e($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? __('app_name')) ?> - Musikverein Denkendorf</title>
    
    <!-- Bootstrap 5 CSS (with fallback CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-clubmanager sticky-top">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center" href="/dashboard">
                <i class="bi bi-music-note-beamed me-2 text-warning fs-4"></i>
                <div>
                    <span class="fs-5 fw-bold">Clubmanager</span>
                    <small class="d-block text-white-50" style="font-size: 0.7rem; line-height: 1;">Musikverein Denkendorf</small>
                </div>
                <span class="badge bg-warning text-dark ms-2" style="font-size: 0.65rem;">v1.7</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <!-- Role-Based Dynamic Menu Items -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/dashboard') ? 'active' : '' ?>" href="/dashboard">
                            <i class="bi bi-speedometer2 me-1"></i> <?= __('nav_dashboard') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/members') ? 'active' : '' ?>" href="/members">
                            <i class="bi bi-people me-1"></i> <?= __('nav_members') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/fee-classes') ? 'active' : '' ?>" href="/fee-classes">
                            <i class="bi bi-tags me-1"></i> <?= __('nav_fee_classes') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/sepa') ? 'active' : '' ?>" href="/sepa">
                            <i class="bi bi-cash-stack me-1"></i> <?= __('nav_sepa') ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/reports') ? 'active' : '' ?>" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i> <?= __('nav_reports') ?>
                        </a>
                        <ul class="dropdown-menu shadow-sm" aria-labelledby="reportsDropdown">
                            <li>
                                <a class="dropdown-item" href="/reports/birthdays">
                                    <i class="bi bi-cake2 me-2 text-primary"></i> <?= __('nav_birthdays') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/reports/anniversaries">
                                    <i class="bi bi-award me-2 text-warning"></i> <?= __('nav_anniversaries') ?>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <?php if (!empty($isAdmin)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/users') || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/club') || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/audit')) ? 'active' : '' ?>" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shield-lock me-1"></i> <?= __('nav_admin') ?>
                        </a>
                        <ul class="dropdown-menu shadow-sm" aria-labelledby="adminDropdown">
                            <li>
                                <a class="dropdown-item" href="/users">
                                    <i class="bi bi-person-gear me-2 text-primary"></i> <?= __('nav_users') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/club">
                                    <i class="bi bi-building me-2 text-info"></i> <?= __('nav_club') ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/audit">
                                    <i class="bi bi-journal-text me-2 text-secondary"></i> <?= __('nav_audit_log') ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Right Side: Language & User Profile -->
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <!-- Language Selector -->
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-globe2 me-1"></i>
                            <span><?= strtoupper($currentLocale) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="langDropdown">
                            <li><a class="dropdown-item <?= $currentLocale === 'de' ? 'active' : '' ?>" href="/auth/lang?lang=de">Deutsch (DE)</a></li>
                            <li><a class="dropdown-item <?= $currentLocale === 'en' ? 'active' : '' ?>" href="/auth/lang?lang=en">English (EN)</a></li>
                        </ul>
                    </li>

                    <!-- User Profile Dropdown -->
                    <?php if ($currentUser): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-light text-dark fw-bold d-inline-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                <?= strtoupper(substr($currentUser->name, 0, 1)) ?>
                            </div>
                            <span class="d-none d-sm-inline"><?= e($currentUser->name) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                            <li class="px-3 py-2 text-muted small">
                                <div><?= e($currentUser->email) ?></div>
                                <span class="badge <?= $currentUser->isAdmin() ? 'bg-danger' : 'bg-primary' ?> mt-1">
                                    <?= $currentUser->isAdmin() ? __('role_admin') : __('role_treasurer') ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="/logout" method="POST" class="m-0 p-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> <?= __('nav_logout') ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="main-content py-4">
        <div class="container-fluid px-lg-4">

            <!-- Flash Message Alerts -->
            <?php if (!empty($flashes)): ?>
                <?php foreach ($flashes as $type => $messages): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="alert alert-<?= e($type) ?> alert-dismissible fade show shadow-sm" role="alert">
                            <?php if ($type === 'success'): ?>
                                <i class="bi bi-check-circle-fill me-2"></i>
                            <?php elseif ($type === 'danger'): ?>
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php elseif ($type === 'warning'): ?>
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <?php else: ?>
                                <i class="bi bi-info-circle-fill me-2"></i>
                            <?php endif; ?>
                            <?= e($msg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- View Specific Body -->
            <?= $content ?>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-clubmanager py-3 mt-auto">
        <div class="container-fluid px-lg-4 text-center text-md-between d-md-flex align-items-center">
            <div>
                <strong>Clubmanager</strong> &bull; Version 1.7 &bull; Stand: 16. Oktober 2025
            </div>
            <div class="mt-2 mt-md-0">
                <span>Musikverein Denkendorf e.V.</span> &bull; 
                <span class="text-muted">Autor: Reinhard Schiessl</span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
