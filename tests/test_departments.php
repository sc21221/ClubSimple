<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\DepartmentController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Database;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\DepartmentRepository;

class DepartmentTestController extends DepartmentController
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
$db->exec('CREATE TABLE departments (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
$db->exec('CREATE TABLE member_departments (id INTEGER PRIMARY KEY, member_id INTEGER NOT NULL REFERENCES members(id) ON DELETE RESTRICT, department_id INTEGER NOT NULL REFERENCES departments(id) ON DELETE RESTRICT, assigned_date TEXT, description TEXT, UNIQUE(member_id, department_id))');
$db->exec("INSERT INTO members VALUES (1, 'Anna', 'A'), (2, 'Ben', 'B')");
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$controller = new DepartmentTestController();
$repo = new DepartmentRepository();
$token = Csrf::getToken();
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
$post = static function (array $data) use ($token): Request {
    $_POST = ['_csrf_token' => $token] + $data;
    return new Request();
};
$controller->store($post(['name' => ' Sport ']));
$controller->store($post(['name' => 'Musik']));
$check(count($repo->getAll()) === 2 && $repo->getAll()[1]->name === 'Sport', 'CRUD creates trimmed and sorted departments');
$controller->store($post(['name' => '  ']));
$controller->store($post(['name' => []]));
$check(count($repo->getAll()) === 2, 'Invalid names rejected');
$controller->update($post(['name' => 'Turnen']), 1);
$check($repo->findById(1)->name === 'Turnen', 'Department updated');
$controller->assign($post(['department_id' => '1', 'assigned_date' => '2024-02-29', 'description' => 'Trainer']), 1);
$assignments = $repo->getForMember(1);
$check(count($assignments) === 1 && $assignments[0]->department_id == 1 && $assignments[0]->name === 'Turnen' && $assignments[0]->assigned_date === '2024-02-29' && $assignments[0]->description === 'Trainer', 'Assignment fields exposed as objects');
$controller->assign($post(['department_id' => '1']), 1);
$controller->assign($post(['department_id' => '2', 'assigned_date' => '2023-02-29']), 1);
$controller->assign($post(['department_id' => '2', 'description' => []]), 1);
$controller->assign($post(['department_id' => '2x']), 1);
$controller->assign($post(['department_id' => '999']), 1);
$controller->assign($post(['department_id' => '2']), 999);
$controller->assign($post([]), 1);
$check(count($repo->getForMember(1)) === 1, 'Duplicates and invalid assignments rejected');
$_POST = ['_csrf_token' => 'invalid', 'department_id' => '2'];
$controller->assign(new Request(), 1);
$check(count($repo->getForMember(1)) === 1, 'CSRF protects assignments');
$controller->show(new Request(), 1);
$check($controller->view === 'departments/members' && count($controller->rendered['members']) === 1, 'Department members view supplied');
$department = $controller->rendered['department'];
$members = $controller->rendered['members'];
ob_start();
require __DIR__ . '/../views/departments/members.php';
$html = ob_get_clean();
$check(str_contains($html, 'Anna A') && str_contains($html, '/members/1'), 'Members view renders repository results');
$controller->delete($post([]), 1);
$check($repo->findById(1) !== null, 'Referenced department cannot be deleted');
$assignmentId = (int) $assignments[0]->id;
$controller->remove($post([]), 2, $assignmentId);
$check(count($repo->getForMember(1)) === 1, 'Another member cannot remove assignment');
$controller->remove($post([]), 1, $assignmentId);
$check(count($repo->getForMember(1)) === 0, 'Assignment removed');
$controller->delete($post([]), 1);
$check($repo->findById(1) === null, 'Unreferenced department deleted');
$controller->assign($post(['department_id' => '2', 'assigned_date' => '', 'description' => '']), 1);
$optional = $repo->getForMember(1)[0];
$check($optional->assigned_date === null && $optional->description === null, 'Empty optional fields stored as null');
$_POST = ['_csrf_token' => 'invalid', 'name' => 'Unwanted'];
$controller->store(new Request());
$controller->update(new Request(), 2);
$controller->delete(new Request(), 2);
$controller->remove(new Request(), 1, $optional->id);
$check(count($repo->getAll()) === 1 && $repo->findById(2)->name === 'Musik' && count($repo->getForMember(1)) === 1, 'CSRF protects all mutations');
$controller->index(new Request());
$check($controller->view === 'departments/index' && count($controller->rendered['departments']) === 1, 'Index view receives departments');
$controller->edit(new Request(), 2);
$check($controller->view === 'departments/edit' && $controller->rendered['department']->name === 'Musik', 'Edit view receives department');