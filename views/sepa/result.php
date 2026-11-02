<?php /** @var array $result */ ?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h2 class="h3 fw-bold"><?= e(__('sepa_result_title')) ?></h2>
        <p><?= e(__('sepa_result_report_notice')) ?></p>
        <a class="btn btn-success me-2" href="/sepa/download?file=<?= urlencode($result['filename']) ?>"><i class="bi bi-download me-1"></i> <?= e(__('sepa_download_xml')) ?></a>
        <a class="btn btn-primary me-2" href="/sepa/report?run=<?= urlencode($result['token']) ?>"><i class="bi bi-file-earmark-pdf me-1"></i> <?= e(__('sepa_download_report')) ?></a>
        <a class="btn btn-outline-secondary" href="/sepa"><?= e(__('back')) ?></a>
    </div>
</div>