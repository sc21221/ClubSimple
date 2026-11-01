<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\ClubController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Request;
use Clubmanager\Models\Club;
use Clubmanager\Repositories\ClubRepository;

class FormClubFieldsRepository extends ClubRepository
{
    public array $data = [];

    public function getPrimary(): Club
    {
        return new Club(['Id' => 7], true);
    }

    public function update(Club $club, array $data, ?int $userId = null): bool
    {
        $this->data = $data;
        return true;
    }
}

class FormClubFieldsController extends ClubController
{
    public function __construct(FormClubFieldsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function redirect(string $url, int $status = 302): void {}
}

session_start();
$repo = new FormClubFieldsRepository();
$controller = new FormClubFieldsController($repo);
$_GET = ['unexpected' => 'query'];
$_POST = [
    '_csrf_token' => Csrf::getToken(), 'Id' => '999', 'updated_by' => '999',
    'name' => 'Verein', 'name2' => 'Test', 'boss' => 'Anna', 'email' => 'a@example.com',
    'phone' => '123', 'street' => 'Straße', 'zip' => '12345', 'city' => 'Ort',
    'country' => 'Deutschland', 'bank_name' => 'Bank', 'bank_creditor_id' => 'DE98ZZZ09999999999',
    'bank_iban' => 'de89 3704 0044 0532 0130 00', 'bank_bic' => ' cobaDEFFxxx ',
];
$controller->update(new Request());
$checks = [
    'Update accepts only club fields and normalizes bank details' => $repo->data === [
        'name' => 'Verein', 'name2' => 'Test', 'boss' => 'Anna', 'email' => 'a@example.com',
        'phone' => '123', 'street' => 'Straße', 'zip' => '12345', 'city' => 'Ort',
        'country' => 'Deutschland', 'bank_name' => 'Bank', 'bank_creditor_id' => 'DE98ZZZ09999999999',
        'bank_iban' => 'DE89370400440532013000', 'bank_bic' => 'COBADEFFXXX',
    ],
];
$_POST['_csrf_token'] = 'invalid';
$repo->data = [];
$controller->update(new Request());
$checks['Invalid CSRF token prevents update'] = $repo->data === [];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
}
exit(in_array(false, $checks, true) ? 1 : 0);