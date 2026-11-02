<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Core\Database;
use Clubmanager\Core\Request;
use Clubmanager\Controllers\EventController;
use Clubmanager\Controllers\FunctionController;
use Clubmanager\Controllers\DepartmentController;
use Clubmanager\Repositories\EventRepository;
use Clubmanager\Repositories\FunctionRepository;
use Clubmanager\Repositories\DepartmentRepository;

trait CaptureCatalogView
{
    public array $data = [];
    public string $html = '';

    public function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $this->data = $data;
        extract($data);
        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $this->html = ob_get_clean();
    }
}

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach (['events', 'club_functions', 'departments'] as $table) {
    $db->exec("CREATE TABLE `$table` (id INTEGER PRIMARY KEY, name TEXT)");
    $db->exec("INSERT INTO `$table` VALUES (1, 'Zulu'), (2, 'Alpha'), (3, 'Alpha')");
}
(new ReflectionProperty(Database::class, 'instance'))->setValue(null, $db);
$check = static function (bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
    echo "[PASS] $message\n";
};
$ids = static fn(array $items): array => array_map(static fn($item) => (int) $item->id, $items);
$cases = [
    ['events', new EventRepository(), new class extends EventController { use CaptureCatalogView; }],
    ['functions', new FunctionRepository(), new class extends FunctionController { use CaptureCatalogView; }],
    ['departments', new DepartmentRepository(), new class extends DepartmentController { use CaptureCatalogView; }],
];
foreach ($cases as [$path, $repo, $controller]) {
    $check($ids($repo->getAll()) === [2, 3, 1], "$path default order and tie-breaker");
    $check($ids($repo->getAll('name', 'desc')) === [1, 2, 3], "$path descending order");
    $check($ids($repo->getAll('name DESC; DROP TABLE events', 'invalid')) === [2, 3, 1], "$path rejects invalid SQL and direction");
    $check($ids($repo->getAll('invalid', 'desc')) === [1, 2, 3], "$path invalid column falls back to name");
    foreach (['asc', 'desc'] as $direction) {
        $_GET = ['sort' => 'name', 'direction' => $direction];
        $controller->index(new Request());
        $html = $controller->html;
        $nextDirection = $direction === 'asc' ? 'desc' : 'asc';
        $check($ids($controller->data[$path]) === ($direction === 'asc' ? [2, 3, 1] : [1, 2, 3]), "$path controller $direction ordering");
        $check(str_contains($html, "href=\"/$path?sort=name&amp;direction=$nextDirection\""), "$path $direction link toggles");
        $check(str_contains($html, 'aria-sort="' . ($direction === 'asc' ? 'ascending' : 'descending') . '"') && substr_count($html, 'aria-sort=') === 1, "$path $direction accessible header");
        $check(str_contains($html, $direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down'), "$path $direction icon");
    }
    $_GET = ['sort' => ['name'], 'direction' => ['desc']];
    $controller->index(new Request());
    $check($controller->data['sort'] === 'name' && $controller->data['direction'] === 'asc' && $ids($controller->data[$path]) === [2, 3, 1], "$path invalid request falls back");
    $_GET = ['sort' => 'id; DROP TABLE events', 'direction' => 'desc; DROP TABLE events'];
    $controller->index(new Request());
    $check($controller->data['sort'] === 'name' && $controller->data['direction'] === 'asc' && $ids($controller->data[$path]) === [2, 3, 1], "$path malicious request falls back");
}