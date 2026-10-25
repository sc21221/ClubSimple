<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Core\Response;
use Clubmanager\Core\Auth;
use Clubmanager\Repositories\ClubRepository;
use Clubmanager\Repositories\MembershipFeeRepository;
use Clubmanager\Services\SepaService;

/**
 * SEPA Direct Debit Processing Controller
 *
 * Coordinates automated age-based recategorization, ISO 20022 pain.008.001.02 XML generation,
 * batch recording in membership_fees, and XML downloads.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class SepaController extends Controller
{
    protected ClubRepository $clubRepo;
    protected MembershipFeeRepository $feeRepo;
    protected SepaService $sepaService;

    public function __construct()
    {
        $this->clubRepo = new ClubRepository();
        $this->feeRepo = new MembershipFeeRepository();
        $this->sepaService = new SepaService();
    }

    /**
     * Display SEPA direct debit overview and execution setup.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request): void
    {
        $club = $this->clubRepo->getPrimary();
        $lastRun = $this->feeRepo->getLastRunSummary();
        $recentRuns = $this->feeRepo->getRunHistory(10);

        $defaultDueDate = date('Y-m-d', strtotime('+7 days'));
        $defaultRemittance = __('sepa_remittance_default', ['year' => date('Y')]);

        $this->render('sepa/index', [
            'club' => $club,
            'lastRun' => $lastRun,
            'recentRuns' => $recentRuns,
            'defaultDueDate' => $defaultDueDate,
            'defaultRemittance' => $defaultRemittance,
        ]);
    }

    /**
     * Interactive preview of the SEPA run and automated age recategorizations.
     *
     * @param Request $request
     * @return void
     */
    public function preview(Request $request): void
    {
        $club = $this->clubRepo->getPrimary();
        $dueDate = $request->query('due_date') ?: date('Y-m-d', strtotime('+7 days'));
        $remittanceText = $request->query('remittance') ?: __('sepa_remittance_default', ['year' => date('Y')]);

        // Detect members that will be recategorized based on age
        $recategorizations = $this->sepaService->detectAgeRecategorizations($dueDate);

        // Preview debit debits
        $preview = $this->sepaService->previewDebits();

        $this->render('sepa/preview', [
            'club' => $club,
            'dueDate' => $dueDate,
            'remittanceText' => $remittanceText,
            'recategorizations' => $recategorizations,
            'transactions' => $preview['transactions'],
            'totalAmount' => $preview['total_amount'],
            'totalCount' => $preview['total_count'],
        ]);
    }

    /**
     * Execute the SEPA run: adjust age assignments, generate XML, and record debits.
     *
     * @param Request $request
     * @return void
     */
    public function execute(Request $request): void
    {
        if (!$this->validateCsrf($request)) {
            return;
        }

        $club = $this->clubRepo->getPrimary();
        $dueDate = $request->post('due_date') ?: date('Y-m-d', strtotime('+7 days'));
        $remittanceText = $request->post('remittance') ?: __('sepa_remittance_default', ['year' => date('Y')]);
        $autoRecat = !empty($request->post('auto_recategorize'));

        $userId = Auth::user() ? (int) Auth::user()->id : null;

        try {
            $result = $this->sepaService->executeDebitRun($club, $dueDate, $remittanceText, $autoRecat, $userId);

            $this->flash(
                'success',
                __('sepa_run_success') . ' ' .
                format_money($result['total_amount']) . ' (' . $result['total_transactions'] . ' Buchungen)' .
                ($result['recategorized_count'] > 0 ? ' - ' . $result['recategorized_count'] . ' Mitglieder altersbedingt rekategorisiert.' : '')
            );

            // Redirect to result page / trigger download
            $this->redirect('/sepa/download?file=' . urlencode($result['filename']));
        } catch (\Throwable $e) {
            $this->flash('danger', 'Fehler beim Ausführen des SEPA-Laufs: ' . $e->getMessage());
            $this->redirect('/sepa');
        }
    }

    /**
     * Download generated SEPA XML file.
     *
     * @param Request $request
     * @return void
     */
    public function download(Request $request): void
    {
        $filename = basename((string) $request->query('file'));
        $filePath = dirname(__DIR__, 2) . '/storage/sepa/' . $filename;

        if (!file_exists($filePath)) {
            $this->flash('danger', 'SEPA XML-Datei nicht gefunden.');
            $this->redirect('/sepa');
            return;
        }

        $content = file_get_contents($filePath);
        Response::downloadContent($content, $filename, 'application/xml; charset=UTF-8');
    }

    /**
     * Display past SEPA batch execution history.
     *
     * @param Request $request
     * @return void
     */
    public function history(Request $request): void
    {
        $runs = $this->feeRepo->getRunHistory(50);
        $this->render('sepa/history', [
            'runs' => $runs,
        ]);
    }
}
