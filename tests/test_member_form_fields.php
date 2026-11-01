<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\MemberController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Request;
use Clubmanager\Models\Club;
use Clubmanager\Models\Member;
use Clubmanager\Models\MemberFeeClass;
use Clubmanager\Repositories\MemberRepository;
use Clubmanager\Repositories\FeeClassRepository;
use Clubmanager\Repositories\ClubRepository;

class FormMemberRepository extends MemberRepository
{
    public array $data = [];

    public function create(array $data, ?int $userId = null): Member
    {
        $this->data = $data;
        return new Member(['id' => 42, 'join_date' => $data['join_date']], true);
    }

    public function findById(int $id): ?Member
    {
        return new Member(['id' => $id], true);
    }

    public function update(Member $member, array $data, ?int $userId = null): bool
    {
        $this->data = $data;
        return true;
    }
}

class FormFeeClassRepository extends FeeClassRepository
{
    public array $assigned = [];

    public function assignToMember(int $memberId, int $feeClassId, ?float $customAmount = null, ?string $startDate = null, ?string $endDate = null, ?int $userId = null): MemberFeeClass
    {
        $this->assigned = [$memberId, $feeClassId, $startDate];
        return new MemberFeeClass();
    }
}

class FormClubRepository extends ClubRepository
{
    public function getPrimary(): Club
    {
        return new Club(['Id' => 7], true);
    }
}

class FormMemberController extends MemberController
{
    public function __construct(public FormMemberRepository $members, public FormFeeClassRepository $fees)
    {
        $this->memberRepo = $members;
        $this->feeClassRepo = $fees;
        $this->clubRepo = new FormClubRepository();
    }

    public function redirect(string $url, int $status = 302): void {}
}

session_start();
$members = new FormMemberRepository();
$fees = new FormFeeClassRepository();
$controller = new FormMemberController($members, $fees);
$_GET = ['unexpected' => 'query'];
$_POST = [
    '_csrf_token' => Csrf::getToken(),
    'first_name' => 'Anna', 'last_name' => 'Test',
    'birth_date' => '2000-01-01', 'join_date' => '2024-01-01',
    'membership_type' => 'Vollmitglied', 'status' => 'Aktiv',
    'leave_date' => '',
    'initial_fee_class_id' => '3', 'id' => '999', 'club_id' => '999',
];
$controller->store(new Request());
$checks = [
    'Create excludes form and unexpected fields' => $members->data['first_name'] === 'Anna'
        && !isset($members->data['_csrf_token'], $members->data['initial_fee_class_id'])
        && !array_key_exists('_csrf_token', $members->data)
        && !array_key_exists('initial_fee_class_id', $members->data)
        && !array_key_exists('unexpected', $members->data) && !array_key_exists('id', $members->data),
    'Initial fee class remains assigned' => $fees->assigned === [42, 3, '2024-01-01'],
    'Create uses primary club, not submitted club' => ($members->data['club_id'] ?? null) === 7,
    'Create stores empty leave date as null' => array_key_exists('leave_date', $members->data) && $members->data['leave_date'] === null,
];
$controller->update(new Request(), 42);
$checks['Update stores empty leave date as null'] = array_key_exists('leave_date', $members->data) && $members->data['leave_date'] === null;
$checks['Update excludes form and unexpected fields'] = $members->data['first_name'] === 'Anna'
    && !array_key_exists('club_id', $members->data)
    && !array_key_exists('_csrf_token', $members->data)
    && !array_key_exists('initial_fee_class_id', $members->data)
    && !array_key_exists('unexpected', $members->data) && !array_key_exists('id', $members->data);
$_POST['leave_date'] = '2025-01-02';
$controller->store(new Request());
$checks['Create preserves leave date'] = $members->data['leave_date'] === '2025-01-02';
$controller->update(new Request(), 42);
$checks['Update preserves leave date'] = $members->data['leave_date'] === '2025-01-02';
$_POST['_csrf_token'] = 'invalid';
$members->data = [];
$controller->store(new Request());
$checks['Invalid CSRF token prevents creation'] = $members->data === [];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
}
exit(in_array(false, $checks, true) ? 1 : 0);