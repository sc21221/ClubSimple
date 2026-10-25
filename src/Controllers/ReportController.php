<?php

declare(strict_types=1);

namespace Clubmanager\Controllers;

use Clubmanager\Core\Controller;
use Clubmanager\Core\Request;
use Clubmanager\Repositories\ClubRepository;
use Clubmanager\Services\ReportService;

/**
 * Reports & Lists Controller
 *
 * Implements birthday monitoring, anniversary lists, CSV and PDF generation.
 *
 * @package Clubmanager\Controllers
 * @author  Reinhard Schiessl
 */
class ReportController extends Controller
{
    protected ReportService $reportService;
    protected ClubRepository $clubRepo;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->clubRepo = new ClubRepository();
    }

    /**
     * Display and export upcoming birthdays list.
     *
     * @param Request $request
     * @return void
     */
    public function birthdays(Request $request): void
    {
        $days = (int) $request->query('days', 90);
        $roundOnly = (bool) $request->query('round_only', false);
        $month = $request->query('month') ? (int) $request->query('month') : null;
        $format = $request->query('format');

        $results = $this->reportService->getUpcomingBirthdays($days, $roundOnly, $month);
        $club = $this->clubRepo->getPrimary();

        // Handle CSV Export
        if ($format === 'csv') {
            $headers = ['Name', 'Geburtstag', 'Wird (Alter)', 'Runder Geburtstag', 'Tage bis zum Termin', 'E-Mail', 'Telefon', 'Ort'];
            $rows = [];
            foreach ($results as $item) {
                $rows[] = [
                    $item['full_name'],
                    format_date($item['birth_date']),
                    $item['turning_age'],
                    $item['is_round'] ? 'Ja' : 'Nein',
                    $item['days_until'],
                    $item['email'] ?: '',
                    $item['phone'] ?: '',
                    $item['city'] ?: '',
                ];
            }
            $this->reportService->exportCsv($headers, $rows, 'Geburtstagsliste_' . date('Ymd') . '.csv');
            return;
        }

        // Handle PDF Export
        if ($format === 'pdf') {
            $columns = [
                ['label' => 'Name', 'width' => 50],
                ['label' => 'Geburtstag', 'width' => 25, 'align' => 'C'],
                ['label' => 'Wird (Jahre)', 'width' => 25, 'align' => 'C'],
                ['label' => 'Tage bis', 'width' => 20, 'align' => 'C'],
                ['label' => 'Telefon', 'width' => 35],
                ['label' => 'Wohnort', 'width' => 25],
            ];
            $rows = [];
            foreach ($results as $item) {
                $rows[] = [
                    $item['full_name'] . ($item['is_round'] ? ' *' : ''),
                    format_date($item['birth_date']),
                    (string)$item['turning_age'],
                    (string)$item['days_until'],
                    $item['phone'] ?: '-',
                    $item['city'] ?: '-',
                ];
            }
            $this->reportService->exportPdf(
                'Geburtstagsliste',
                'Anstehende Geburtstage (' . ($roundOnly ? 'Nur runde Jubilare' : 'Alle') . ')',
                $columns,
                $rows,
                $club,
                'Geburtstagsliste_' . date('Ymd') . '.pdf'
            );
            return;
        }

        $this->render('reports/birthdays', [
            'results' => $results,
            'days' => $days,
            'roundOnly' => $roundOnly,
            'month' => $month,
        ]);
    }

    /**
     * Display and export upcoming anniversaries list.
     *
     * @param Request $request
     * @return void
     */
    public function anniversaries(Request $request): void
    {
        $targetYear = (int) $request->query('year', (int)date('Y'));
        $milestonesOnly = $request->query('milestones_only', '1') === '1';
        $format = $request->query('format');

        $results = $this->reportService->getUpcomingAnniversaries($targetYear, $milestonesOnly);
        $club = $this->clubRepo->getPrimary();

        // Handle CSV Export
        if ($format === 'csv') {
            $headers = ['Name', 'Mitgliedsart', 'Eintrittsdatum', 'Jahre Mitgliedschaft', 'Jubilaeum', 'E-Mail', 'Telefon', 'Ort'];
            $rows = [];
            foreach ($results as $item) {
                $rows[] = [
                    $item['full_name'],
                    $item['membership_type'],
                    format_date($item['join_date']),
                    $item['years'],
                    $item['is_milestone'] ? 'Ja (' . $item['years'] . ' Jahre)' : 'Nein',
                    $item['email'] ?: '',
                    $item['phone'] ?: '',
                    $item['city'] ?: '',
                ];
            }
            $this->reportService->exportCsv($headers, $rows, 'Jubilaeumsliste_' . $targetYear . '.csv');
            return;
        }

        // Handle PDF Export
        if ($format === 'pdf') {
            $columns = [
                ['label' => 'Name', 'width' => 50],
                ['label' => 'Mitgliedsart', 'width' => 35],
                ['label' => 'Eintrittsdatum', 'width' => 25, 'align' => 'C'],
                ['label' => 'Jahre', 'width' => 20, 'align' => 'C'],
                ['label' => 'Jubilaeum', 'width' => 25, 'align' => 'C'],
                ['label' => 'Wohnort', 'width' => 25],
            ];
            $rows = [];
            foreach ($results as $item) {
                $rows[] = [
                    $item['full_name'],
                    $item['membership_type'],
                    format_date($item['join_date']),
                    (string)$item['years'],
                    $item['is_milestone'] ? ($item['years'] . ' Jahre!') : '-',
                    $item['city'] ?: '-',
                ];
            }
            $this->reportService->exportPdf(
                'Vereinsjubilaeen ' . $targetYear,
                'Ehrungen fuer langjaehrige Mitgliedschaft',
                $columns,
                $rows,
                $club,
                'Jubilaeen_' . $targetYear . '.pdf'
            );
            return;
        }

        $this->render('reports/anniversaries', [
            'results' => $results,
            'targetYear' => $targetYear,
            'milestonesOnly' => $milestonesOnly,
        ]);
    }
}
