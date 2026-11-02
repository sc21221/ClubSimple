<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\FunctionController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Database;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\FunctionRepository;

class FunctionTestController extends FunctionController
{
    public string $location = '';
    public string $view = '';
    public array $rendered = [];
    public function redirect(string $url, int $status = 302): void { $this->location = $url; }
    public function render(string $view, array $data = [], ?string $layout = 'main'): void { $this->view = $view; $this->rendered = $data; }
}

session_start();
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('PRAGMA foreign_keys = ON');
$db->exec('CREATE TABLE members (id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT)');
$db->exec('CREATE TABLE club_functions (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
$db->exec('CREATE TABLE member_functions (id INTEGER PRIMARY KEY, member_id INTEGER NOT NULL REFERENCES members(id), function_id INTEGER NOT NULL REFERENCES club_functions(id), start_date TEXT NOT NULL, end_date TEXT, description TEXT)');
$db->exec("INSERT INTO members VALUES (1, 'Anna', 'A'), (2, 'Ben', 'B')");
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$controller = new FunctionTestController();
$repo = new FunctionRepository();
$token = Csrf::getToken();
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
$post = static function (array $data) use ($token): Request {
    $_POST = ['_csrf_token' => $token] + $data;
    return new Request();
};
$controller->store($post(['name' => ' Vorstand ']));
$controller->store($post(['name' => '']));
$check(count($repo->getAll()) === 1 && $repo->getAll()[0]->name === 'Vorstand', 'Name validation and creation');
$controller->update($post(['name' => 'Kasse']), 1);
$check($repo->findById(1)->name === 'Kasse', 'Update');
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$controller->assign($post(['function_id' => '1', 'start_date' => $today, 'end_date' => $today, 'description' => 'Amt']), 1);
$controller->assign($post(['function_id' => '1', 'start_date' => '2020-01-01', 'end_date' => $yesterday]), 1);
$controller->assign($post(['function_id' => '1', 'start_date' => $tomorrow]), 2);
$controller->assign($post(['function_id' => '1', 'start_date' => '2023-02-29']), 1);
$controller->assign($post(['function_id' => '1', 'start_date' => $today, 'end_date' => $yesterday]), 1);
$controller->assign($post(['function_id' => '1']), 1);
$controller->assign($post(['function_id' => '999', 'start_date' => $today]), 1);
$controller->assign($post(['function_id' => '1', 'start_date' => $today, 'description' => []]), 1);
$assignments = $repo->getForMember(1);
$check(count($assignments) === 2 && $assignments[0]->function_id == 1, 'Multiple periods accepted; invalid assignments rejected');
$controller->members(new Request(), 1);
$check($controller->view === 'functions/members' && count($controller->rendered['members']) === 1, 'Current list includes both date boundaries and excludes past and future');
$controller->history(new Request(), 1);
$check($controller->view === 'functions/members' && count($controller->rendered['members']) === 2, 'History includes past and current, not future');
$controller->delete($post([]), 1);
$check($repo->findById(1) !== null, 'Referenced function protected');
$assignmentId = (int) $assignments[0]->id;
$controller->remove($post([]), 2, $assignmentId);
$check(count($repo->getForMember(1)) === 2, 'Cannot remove another member assignment');
$_POST = ['_csrf_token' => 'invalid', 'function_id' => '1', 'start_date' => $today, 'name' => 'Nein'];
$controller->assign(new Request(), 1);
$controller->remove(new Request(), 1, $assignmentId);
$controller->store(new Request());
$controller->update(new Request(), 1);
$controller->delete(new Request(), 1);
$check(count($repo->getForMember(1)) === 2 && count($repo->getAll()) === 1 && $repo->findById(1)->name === 'Kasse', 'CSRF protects mutations');
$controller->remove($post([]), 1, $assignmentId);
$check(count($repo->getForMember(1)) === 1, 'Assignment removed');
$controller->index(new Request());
$check($controller->view === 'functions/index' && count($controller->rendered['functions']) === 1, 'Index view contract');
$controller->create(new Request());
$check($controller->view === 'functions/create', 'Create view contract');
$controller->edit(new Request(), 1);
$check($controller->view === 'functions/edit' && $controller->rendered['function']->name === 'Kasse', 'Edit view contract');