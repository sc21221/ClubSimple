<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\FeeClassController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Request;
use Clubmanager\Models\FeeClass;
use Clubmanager\Repositories\FeeClassRepository;

class FormFeeClassFieldsRepository extends FeeClassRepository
{
    public array $data = [];

    public function create(array $data, ?int $userId = null): FeeClass
    {
        $this->data = $data;
        return new FeeClass();
    }

    public function findById(int $id): ?FeeClass
    {
        return new FeeClass(['id' => $id], true);
    }

    public function update(FeeClass $feeClass, array $data, ?int $userId = null): bool
    {
        $this->data = $data;
        return true;
    }
}

class FormFeeClassFieldsController extends FeeClassController
{
    public function __construct(FormFeeClassFieldsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function redirect(string $url, int $status = 302): void {}
}

session_start();
$repo = new FormFeeClassFieldsRepository();
$controller = new FormFeeClassFieldsController($repo);
$_GET = ['unexpected' => 'query'];
$_POST = [
    '_csrf_token' => Csrf::getToken(), 'id' => '999',
    'name' => 'Jugend', 'description' => 'Test', 'base_amount' => '84.00',
    'billing_cycle' => 'Jährlich', 'min_age' => '', 'max_age' => '',
    'is_active' => '1', 'currency' => 'USD',
];
$controller->store(new Request());
$checks = [
    'Create accepts fields and excludes unexpected input' => $repo->data === [
        'name' => 'Jugend', 'description' => 'Test', 'base_amount' => '84.00',
        'billing_cycle' => 'Jährlich', 'min_age' => 0, 'max_age' => 999,
        'is_active' => 1, 'currency' => 'EUR',
    ],
];
unset($_POST['is_active'], $_POST['currency']);
$controller->update(new Request(), 42);
$checks['Update accepts fields and excludes unexpected input'] = $repo->data === [
    'name' => 'Jugend', 'description' => 'Test', 'base_amount' => '84.00',
    'billing_cycle' => 'Jährlich', 'min_age' => 0, 'max_age' => 999,
    'is_active' => 0,
];
$_POST['_csrf_token'] = 'invalid';
$repo->data = [];
$controller->store(new Request());
$checks['Invalid CSRF token prevents creation'] = $repo->data === [];
$controller->update(new Request(), 42);
$checks['Invalid CSRF token prevents update'] = $repo->data === [];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
}
exit(in_array(false, $checks, true) ? 1 : 0);