<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\DepartmentRepository;

class DepartmentController extends Controller
{
    protected DepartmentRepository $repo;

    public function __construct()
    {
        $this->repo = new DepartmentRepository();
    }

    public function index(Request $request): void
    {
        $this->render('departments/index', ['departments' => $this->repo->getAll()]);
    }

    public function create(Request $request): void
    {
        $this->render('departments/create');
    }

    public function store(Request $request): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $name = $this->validName($request->post('name'));
        if ($name === null) {
            $this->flash('danger', 'Bitte einen gültigen Abteilungsnamen eingeben (maximal 100 Zeichen).');
            $this->redirect('/departments/create');
            return;
        }
        $this->repo->create(['name' => $name]);
        $this->flash('success', 'Abteilung wurde angelegt.');
        $this->redirect('/departments');
    }

    public function show(Request $request, int|string $id): void
    {
        $department = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$department) { $this->redirect('/departments'); return; }
        $this->render('departments/members', ['department' => $department, 'members' => $this->repo->getMembers((int) $id)]);
    }

    public function edit(Request $request, int|string $id): void
    {
        $department = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$department) { $this->redirect('/departments'); return; }
        $this->render('departments/edit', ['department' => $department]);
    }

    public function update(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $department = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$department) { $this->redirect('/departments'); return; }
        $name = $this->validName($request->post('name'));
        if ($name === null) {
            $this->flash('danger', 'Bitte einen gültigen Abteilungsnamen eingeben (maximal 100 Zeichen).');
            $this->redirect('/departments/' . (int) $id . '/edit');
            return;
        }
        $this->repo->update($department, ['name' => $name]);
        $this->flash('success', 'Abteilung wurde aktualisiert.');
        $this->redirect('/departments');
    }

    public function delete(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $department = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if ($department) {
            $deleted = $this->repo->delete($department);
            $this->flash($deleted ? 'success' : 'danger', $deleted ? 'Abteilung wurde gelöscht.' : 'Abteilung ist noch Mitgliedern zugeordnet.');
        }
        $this->redirect('/departments');
    }

    public function assign(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $departmentId = $request->post('department_id');
        $date = $request->post('assigned_date');
        $description = $request->post('description');
        $validDate = $date === null || $date === '' || (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)));
        $validDescription = $description === null || is_string($description);
        $assigned = $this->validId($id) && $this->validId($departmentId) && $validDate && $validDescription
            && $this->repo->assignToMember((int) $id, (int) $departmentId, $date === '' ? null : $date, $description === '' ? null : $description);
        $this->flash($assigned ? 'success' : 'danger', $assigned ? 'Abteilung wurde zugeordnet.' : 'Abteilung konnte nicht zugeordnet werden.');
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
}