<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Core\Database;
use Clubmanager\Repositories\MemberRepository;

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE members (id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT, birth_date TEXT, join_date TEXT, membership_type TEXT, status TEXT, email TEXT, city TEXT, street TEXT)');
$db->exec('CREATE TABLE member_fee_classes (member_id INTEGER, fee_class_id INTEGER, is_active INTEGER)');
$db->exec("INSERT INTO members VALUES
    (1, 'Zoe', 'Bauer', '2000-01-01', '2020-01-01', 'B', 'Aktiv', '', '', ''),
    (2, 'Anna', 'Bauer', '1980-01-01', '2022-01-01', 'A', 'Aktiv', '', '', ''),
    (3, 'Carl', 'Zimmer', '1990-01-01', '2010-01-01', 'A', 'Inaktiv', '', '', ''),
    (4, 'Dora', 'Alpha', NULL, NULL, 'B', 'Aktiv', '', '', '')");
$db->exec('INSERT INTO member_fee_classes VALUES (1, 7, 1), (2, 7, 1), (3, 8, 1)');
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$repo = new MemberRepository();
$ids = static fn(array $members): array => array_map(static fn($member) => (int) $member->id, $members);
$check = static function (bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
$check($ids($repo->search()) === [4, 2, 1, 3], 'Default name order includes first name');
$check($ids($repo->search([], 50, 0, 'name', 'desc')) === [3, 1, 2, 4], 'Descending name order');
$check($ids($repo->search([], 50, 0, 'age', 'asc')) === [1, 3, 2, 4], 'Age ascending, missing dates last');
$check($ids($repo->search([], 50, 0, 'age', 'desc')) === [2, 3, 1, 4], 'Age descending, missing dates last');
$check($ids($repo->search([], 2, 1, 'join_date', 'asc')) === [1, 2], 'Join date ordering precedes pagination');
$check($ids($repo->search(['fee_class_id' => 7, 'status' => 'Aktiv'], 50, 0, 'join_date', 'desc')) === [2, 1], 'Filters remain applied');
$check($ids($repo->search([], 50, 0, 'id DESC; DROP TABLE members', 'invalid')) === [4, 2, 1, 3], 'Invalid sorting falls back safely');

$filters = ['search' => 'Bauer & Sohn', 'status' => 'Aktiv', 'fee_class_id' => '7'];
$sort = 'age';
$direction = 'asc';
$members = [];
$feeClasses = [];
$totalMembers = 21;
$currentPage = 2;
$totalPages = 2;
ob_start();
require __DIR__ . '/../views/members/index.php';
$html = ob_get_clean();
$check(str_contains($html, 'search=Bauer+%26+Sohn&amp;status=Aktiv&amp;fee_class_id=7&amp;sort=age&amp;direction=desc'), 'Sort link preserves and escapes filters, toggles direction');
$check(str_contains($html, 'sort=age&amp;direction=asc&amp;page=1'), 'Pagination preserves sorting');
$check(str_contains($html, 'aria-sort="ascending"'), 'Current sort is accessible');