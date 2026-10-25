<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\AuditLogRepository;
use Clubmanager\Repositories\UserRepository;

/**
 * Audit Trail Controller
 *
 * Implements inspection, timeline tracking, and filtering for immutable mutation logs.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class AuditLogController extends Controller
{
    protected AuditLogRepository $repo;
    protected UserRepository $userRepo;

    public function __construct()
    {
        $this->repo = new AuditLogRepository();
        $this->userRepo = new UserRepository();
    }

    /**
     * Display searchable audit log table.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request): void
    {
        $filters = [
            'entity_type' => $request->query('entity_type'),
            'entity_id' => $request->query('entity_id'),
            'user_id' => $request->query('user_id'),
            'action' => $request->query('action'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $page = max(1, (int) $request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $totalLogs = $this->repo->countSearch($filters);
        $logs = $this->repo->search($filters, $limit, $offset);
        $users = $this->userRepo->getAll();

        $totalPages = (int) ceil($totalLogs / $limit);

        $this->render('audit/index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs,
        ]);
    }
}
