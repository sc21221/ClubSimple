<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Controllers\EventController;
use Clubmanager\Core\Csrf;
use Clubmanager\Core\Database;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\EventRepository;

class EventTestController extends EventController
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
$db->exec(file_get_contents(__DIR__ . '/../database/migrate_events_sqlite.sql'));
$db->exec("INSERT INTO members VALUES (1, 'Anna', 'A'), (2, 'Ben', 'B')");
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$controller = new EventTestController();
$repo = new EventRepository();
$token = Csrf::getToken();
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
$post = static function (array $data) use ($token): Request {
    $_POST = ['_csrf_token' => $token] + $data;
    return new Request();
};
$controller->create(new Request());
$check($controller->view === 'events/create', 'Create view selected');
$controller->store($post(['name' => ' Sportfest ']));
$controller->store($post(['name' => 'Ausflug']));
$check(count($repo->getAll()) === 2 && $repo->getAll()[1]->name === 'Sportfest', 'Events created trimmed and sorted');
foreach (['  ', [], str_repeat('a', 101)] as $name) { $controller->store($post(['name' => $name])); }
$check(count($repo->getAll()) === 2, 'Invalid names rejected');
$controller->update($post(['name' => 'Treffen']), 1);
$controller->update($post(['name' => '']), 1);
$controller->update($post(['name' => 'Wrong']), '1x');
$check($repo->findById(1)->name === 'Treffen', 'Update validates name and ID');
$controller->assign($post(['event_id' => '1', 'event_date' => '2024-02-29', 'description' => 'Helferin']), 1);
$controller->assign($post(['event_id' => '1', 'event_date' => '2024-02-29', 'description' => '']), 1);
$assignments = $repo->getForMember(1);
$check(count($assignments) === 2 && $assignments[0]->event_id == 1 && $assignments[0]->name === 'Treffen' && $assignments[0]->event_date === '2024-02-29' && $assignments[0]->description === 'Helferin' && $assignments[1]->description === null, 'Repeated assignments and fields preserved');
foreach ([
    ['event_id' => '1'], ['event_id' => '1', 'event_date' => ''],
    ['event_id' => '1', 'event_date' => '2023-02-29'], ['event_id' => '1', 'event_date' => []],
    ['event_id' => '1', 'event_date' => '2024-01-01', 'description' => []],
    ['event_id' => '1x', 'event_date' => '2024-01-01'], ['event_id' => '999', 'event_date' => '2024-01-01'],
] as $data) { $controller->assign($post($data), 1); }
$controller->assign($post(['event_id' => '1', 'event_date' => '2024-01-01']), 999);
$check(count($repo->getForMember(1)) === 2 && $repo->getForMember(999) === [], 'Invalid assignments rejected');
$controller->members(new Request(), 1);
$members = $controller->rendered['members'];
$check($controller->view === 'events/members' && $controller->rendered['event']->name === 'Treffen' && count($members) === 2 && $members[0]->first_name === 'Anna' && $members[0]->event_date === '2024-02-29' && $members[0]->description === 'Helferin' && $members[0]->assignment_id == $assignments[0]->id, 'Members view receives assignment details');
$event = $controller->rendered['event'];
ob_start();
require __DIR__ . '/../views/events/members.php';
$html = ob_get_clean();
$check(str_contains($html, 'Anna A') && str_contains($html, '2024-02-29') && str_contains($html, 'Helferin'), 'Members view renders assignment details');
$controller->delete($post([]), 1);
$check($repo->findById(1) !== null, 'Referenced event cannot be deleted');
$controller->remove($post([]), 2, (int) $assignments[0]->id);
$check(count($repo->getForMember(1)) === 2, 'Another member cannot remove assignment');
$_POST = ['_csrf_token' => 'invalid', 'name' => 'Unwanted', 'event_id' => '2', 'event_date' => '2024-01-01'];
$controller->store(new Request());
$controller->update(new Request(), 2);
$controller->delete(new Request(), 2);
$controller->assign(new Request(), 1);
$controller->remove(new Request(), 1, (int) $assignments[0]->id);
$check(count($repo->getAll()) === 2 && $repo->findById(2)->name === 'Ausflug' && count($repo->getForMember(1)) === 2, 'CSRF protects all mutations');
$controller->remove($post([]), 1, (int) $assignments[0]->id);
$controller->delete($post([]), 1);
$check($repo->findById(1) !== null && count($repo->getForMember(1)) === 1, 'Remaining assignment still protects event');
$controller->remove($post([]), 1, (int) $assignments[1]->id);
$controller->delete($post([]), 1);
$check($repo->findById(1) === null, 'Unreferenced event deleted');
$controller->index(new Request());
$check($controller->view === 'events/index' && count($controller->rendered['events']) === 1, 'Index view receives events');
$controller->edit(new Request(), 2);
$check($controller->view === 'events/edit' && $controller->rendered['event']->name === 'Ausflug', 'Edit view receives event');
$controller->members(new Request(), 'invalid');
$check($controller->location === '/events', 'Invalid event redirects to index');