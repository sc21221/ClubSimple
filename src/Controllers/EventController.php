<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\EventRepository;

class EventController extends Controller
{
    protected EventRepository $repo;

    public function __construct()
    {
        $this->repo = new EventRepository();
    }

    public function index(Request $request): void
    {
        $sort = 'name';
        $direction = $request->query('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $this->render('events/index', ['events' => $this->repo->getAll($sort, $direction), 'sort' => $sort, 'direction' => $direction]);
    }

    public function create(Request $request): void
    {
        $this->render('events/create');
    }

    public function store(Request $request): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $name = $this->validName($request->post('name'));
        if ($name === null) {
            $this->flash('danger', 'Bitte einen gültigen Ereignisnamen eingeben (maximal 100 Zeichen).');
            $this->redirect('/events/create');
            return;
        }
        $this->repo->create(['name' => $name]);
        $this->flash('success', 'Ereignis wurde angelegt.');
        $this->redirect('/events');
    }

    public function edit(Request $request, int|string $id): void
    {
        $event = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$event) { $this->redirect('/events'); return; }
        $this->render('events/edit', ['event' => $event]);
    }

    public function update(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $event = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$event) { $this->redirect('/events'); return; }
        $name = $this->validName($request->post('name'));
        if ($name === null) {
            $this->flash('danger', 'Bitte einen gültigen Ereignisnamen eingeben (maximal 100 Zeichen).');
            $this->redirect('/events/' . (int) $id . '/edit');
            return;
        }
        $this->repo->update($event, ['name' => $name]);
        $this->flash('success', 'Ereignis wurde aktualisiert.');
        $this->redirect('/events');
    }

    public function delete(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $event = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if ($event) {
            $deleted = $this->repo->delete($event);
            $this->flash($deleted ? 'success' : 'danger', $deleted ? 'Ereignis wurde gelöscht.' : 'Ereignis ist noch Mitgliedern zugeordnet.');
        }
        $this->redirect('/events');
    }

    public function members(Request $request, int|string $id): void
    {
        $event = $this->validId($id) ? $this->repo->findById((int) $id) : null;
        if (!$event) { $this->redirect('/events'); return; }
        $this->render('events/members', ['event' => $event, 'members' => $this->repo->getMembers((int) $id)]);
    }

    public function assign(Request $request, int|string $id): void
    {
        if (!$this->validateCsrf($request)) { return; }
        $eventId = $request->post('event_id');
        $date = $request->post('event_date');
        $description = $request->post('description');
        $assigned = $this->validId($id) && $this->validId($eventId) && $this->validDate($date)
            && ($description === null || is_string($description))
            && $this->repo->assignToMember((int) $id, (int) $eventId, $date, $description === '' ? null : $description);
        $this->flash($assigned ? 'success' : 'danger', $assigned ? 'Ereignis wurde zugeordnet.' : 'Ereignis konnte nicht zugeordnet werden.');
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