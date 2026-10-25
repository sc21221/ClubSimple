<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\ClubRepository;
use Clubmanager\Repositories\MemberRepository;
use Clubmanager\Repositories\FeeClassRepository;
use Clubmanager\Repositories\MembershipFeeRepository;
use Clubmanager\Services\ReportService;

/**
 * Dashboard Controller
 *
 * Provides overview metrics: club representative, active member counts,
 * total fee classes, and last SEPA run statistics.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class DashboardController extends Controller
{
    protected ClubRepository $clubRepo;
    protected MemberRepository $memberRepo;
    protected FeeClassRepository $feeClassRepo;
    protected MembershipFeeRepository $feeRepo;
    protected ReportService $reportService;

    public function __construct()
    {
        $this->clubRepo = new ClubRepository();
        $this->memberRepo = new MemberRepository();
        $this->feeClassRepo = new FeeClassRepository();
        $this->feeRepo = new MembershipFeeRepository();
        $this->reportService = new ReportService();
    }

    /**
     * Render the main dashboard.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request): void
    {
        $club = $this->clubRepo->getPrimary();
        $activeMembersCount = $this->memberRepo->getActiveCount();
        $totalFeeClassesCount = $this->feeClassRepo->getTotalCount();
        $lastSepaRun = $this->feeRepo->getLastRunSummary();
        $statusCounts = $this->memberRepo->getCountsByStatus();

        // Upcoming milestone events for quick dashboard widgets
        $upcomingBirthdays = $this->reportService->getUpcomingBirthdays(60, true);
        $upcomingAnniversaries = $this->reportService->getUpcomingAnniversaries((int)date('Y'), true);

        $this->render('dashboard/index', [
            'club' => $club,
            'activeMembersCount' => $activeMembersCount,
            'totalFeeClassesCount' => $totalFeeClassesCount,
            'lastSepaRun' => $lastSepaRun,
            'statusCounts' => $statusCounts,
            'upcomingBirthdays' => array_slice($upcomingBirthdays, 0, 5),
            'upcomingAnniversaries' => array_slice($upcomingAnniversaries, 0, 5),
        ]);
    }
}
