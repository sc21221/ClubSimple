<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\FunctionRepository;

class FunctionController extends Controller
{
    protected FunctionRepository $repo;

    public function __construct()
    {
        $this->repo = new FunctionRepository();
    }

    public function index(Request $request): void
    {
        $sort = 'name';
        $direction = $request->query('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $this->render('functions/index', ['functions' => $this->repo->getAll($sort, $direction), 'sort' => $sort, 'direction' => $direction]);
    }

    public function create(Request $request): void
    {
        $this->render('functions/create');
    }

    public function store(Request $request): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $name = $this->validName($request->post('name'));
        if ($name === null) {
            $this->flash('danger', 'Bitte einen gültigen Funktionsnamen eingeben (maximal 100 Zeichen).');
            $this->redirect('/functions/create');
            return;
        }
        $this->repo->create(['name' => $name]);
        $this->flash('success', 'Funktion wurde angelegt.');
        $this->redirect('/functions');
    }

    public function edit(Request $request, int|string $id): void
    {
        $function = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$function) { $this->redirect('/functions'); return; }
        $this->render('functions/edit', ['function' => $function]);
    }

    public function update(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $function = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$function) { $this->redirect('/functions'); return; }
        $name = $this->validName($request->post('name'));
        if ($name === null) {
            $this->flash('danger', 'Bitte einen gültigen Funktionsnamen eingeben (maximal 100 Zeichen).');
            $this->redirect('/functions/' . (int) $id . '/edit');
            return;
        }
        $this->repo->update($function, ['name' => $name]);
        $this->flash('success', 'Funktion wurde aktualisiert.');
        $this->redirect('/functions');
    }

    public function delete(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $function = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if ($function) {
            $deleted = $this->repo->delete($function);
            $this->flash($deleted ? 'success' : 'danger', $deleted ? 'Funktion wurde gelöscht.' : 'Funktion ist noch Mitgliedern zugeordnet.');
        }
        $this->redirect('/functions');
    }

    public function members(Request $request, int|string $id): void
    {
        $this->renderMembers($id, false);
    }

    public function history(Request $request, int|string $id): void
    {
        $this->renderMembers($id, true);
    }

    private function renderMembers(int|string $id, bool $history): void
    {
        $function = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$function) { $this->redirect('/functions'); return; }
        $this->render('functions/members', ['function' => $function, 'members' => $this->repo->getMembers((int) $id, $history), 'history' => $history]);
    }

    public function assign(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $functionId = $request->post('function_id');
        $start = $request->post('start_date');
        $end = $request->post('end_date');
        $description = $request->post('description');
        $assigned = $this->validId($id) && $this->validId($functionId) && $this->validDate($start)
            && ($end === null || $end === '' || ($this->validDate($end) && $end >= $start))
            && ($description === null || is_string($description))
            && $this->repo->assignToMember((int) $id, (int) $functionId, $start, $end === '' ? null : $end, $description === '' ? null : $description);
        $this->flash($assigned ? 'success' : 'danger', $assigned ? 'Funktion wurde zugeordnet.' : 'Funktion konnte nicht zugeordnet werden.');
        $this->redirect($this->validId($id) ? '/members/' . (int) $id . '/edit' : '/members');
    }

    public function remove(Request $request, int|string $id, int|string $assignmentId): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $removed = $this->validId($id) && $this->validId($assignmentId) && $this->repo->removeAssignment((int) $id, (int) $assignmentId);
        $this->flash($removed ? 'success' : 'danger', $removed ? 'Zuordnung wurde entfernt.' : 'Zuordnung wurde nicht gefunden.');
        $this->redirect($this->validId($id) ? '/members/' . (int) $id . '/edit' : '/members');
    }

    private function validName(mixed $name): ?string
    {
        if (!is_string($name)) { return null; }
        $name = trim($name);
        return $name !== '' && mb_strlen($name) <= 100 ? $name : null;
    }

    private function validId(mixed $id): bool
    {
        return (is_int($id) || is_string($id)) && preg_match('/^[1-9][0-9]*$/D', (string) $id) === 1 && filter_var($id, FILTER_VALIDATE_INT) !== false;
    }

    private function validDate(mixed $date): bool
    {
        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) === 1
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));
    }
}