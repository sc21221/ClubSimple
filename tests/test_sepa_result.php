<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\SepaController;
use Clubmanager\Core\Request;
use Clubmanager\Core\Session;

class SepaResultTestController extends SepaController
{
    public string $location = '';
    public array $rendered = [];
    public function __construct() {}
    public function redirect(string $url, int $status = 302): void { $this->location = $url; }
    public function render(string $view, array $data = [], ?string $layout = 'main'): void { $this->rendered = [$view, $data]; }
}

session_start();
$controller = new SepaResultTestController();
$result = ['token' => 'abc123', 'filename' => 'test.xml', 'report' => ['transactions' => []]];
Session::set('sepa_last_result', $result);
$_GET = ['run' => 'abc123'];
$controller->result(new Request());
if ($controller->rendered !== ['sepa/result', ['result' => $result]]) {
    throw new RuntimeException('Result must show the matching run');
}
$_GET = ['run' => 'wrong'];
$controller->report(new Request());
if ($controller->location !== '/sepa') {
    throw new RuntimeException('Report must reject an invalid run token');
}
Session::remove('sepa_last_result');
$_GET = ['run' => 'abc123'];
$controller->location = '';
$controller->result(new Request());
if ($controller->location !== '/sepa') {
    throw new RuntimeException('Expired result must not be shown');
}
ob_start();
require __DIR__ . '/../views/sepa/result.php';
$html = ob_get_clean();
if (!str_contains($html, '/sepa/download?file=test.xml') || !str_contains($html, '/sepa/report?run=abc123')) {
    throw new RuntimeException('Both download links must address the same result');
}
echo "SEPA result tests passed.\n";