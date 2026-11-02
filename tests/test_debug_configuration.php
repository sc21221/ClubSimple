<?php

declare(strict_types=1);

$bootstrap = file_get_contents(__DIR__ . '/../public/index.php');
if (!preg_match('~// 3\. Error Reporting & Debug Mode\R(.*?)\R// 4\. Initialize~s', $bootstrap, $matches)) {
    throw new RuntimeException('Debug configuration not found in bootstrap');
}

$originalDebug = getenv('APP_DEBUG');
$originalDisplayErrors = ini_get('display_errors');
$originalStartupErrors = ini_get('display_startup_errors');
$originalErrorReporting = error_reporting();

try {
    foreach ([[null, false], ['true', true], ['false', false], ['0', false], ['1', false], ['TRUE', false], ['', false]] as [$value, $expected]) {
        $label = $value ?? 'unset';
        $value === null ? putenv('APP_DEBUG') : putenv('APP_DEBUG=' . $value);
        $config = require __DIR__ . '/../config/app.php';
        $isDebug = null;
        eval($matches[1]);
        if ($config['debug'] !== $expected || $isDebug !== $expected || (bool) ini_get('display_errors') !== $expected) {
            throw new RuntimeException("Incorrect debug behavior for APP_DEBUG={$label}");
        }
        echo "[PASS] APP_DEBUG={$label}\n";
    }
} finally {
    $originalDebug === false ? putenv('APP_DEBUG') : putenv('APP_DEBUG=' . $originalDebug);
    ini_set('display_errors', $originalDisplayErrors);
    ini_set('display_startup_errors', $originalStartupErrors);
    error_reporting($originalErrorReporting);
}