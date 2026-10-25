<?php

declare(strict_types=1);

namespace Clubmanager\Services;

use Clubmanager\Models\Member;
use Clubmanager\Models\Club;
use Clubmanager\Libraries\PdfBuilder;
use DateTimeImmutable;

/**
 * Report & Analytics Service
 *
 * Implements birthday calculations (including milestone round birthdays),
 * anniversary honors, and dual export capabilities (Excel-compatible CSV & PDF).
 *
 * @package Clubmanager\Services
 * @author  Reinhard Schiessl
 */
class ReportService
{
    /**
     * Standard round birthday milestones.
     */
    public const ROUND_BIRTHDAYS = [50, 60, 70, 75, 80, 85, 90, 95, 100];

    /**
     * Standard club anniversary milestones.
     */
    public const ANNIVERSARY_MILESTONES = [10, 20, 25, 30, 40, 50, 60, 70];

    /**
     * Retrieve upcoming member birthdays.
     *
     * @param int $days Max days ahead (default 30)
     * @param bool $roundOnly Filter strictly for round birthdays
     * @param int|null $filterMonth Filter for a specific calendar month (1-12)
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingBirthdays(int $days = 90, bool $roundOnly = false, ?int $filterMonth = null): array
    {
        $members = Member::where("status = 'Aktiv' AND birth_date IS NOT NULL AND birth_date != '0000-00-00'");
        $today = new DateTimeImmutable('today');
        $currentYear = (int) $today->format('Y');

        $results = [];

        foreach ($members as $member) {
            try {
                $birthDate = new DateTimeImmutable($member->birth_date);
            } catch (\Exception $e) {
                continue;
            }

            // Birthday in current year
            $month = (int) $birthDate->format('m');
            $day = (int) $birthDate->format('d');

            // Handle leap year Feb 29
            if ($month === 2 && $day === 29 && !checkdate(2, 29, $currentYear)) {
                $day = 28;
            }

            $bdayThisYear = new DateTimeImmutable("{$currentYear}-{$month}-{$day}");

            if ($bdayThisYear < $today) {
                // Birthday has already passed this year, check next year
                $nextYear = $currentYear + 1;
                if ($month === 2 && $day === 29 && !checkdate(2, 29, $nextYear)) {
                    $day = 28;
                }
                $nextBday = new DateTimeImmutable("{$nextYear}-{$month}-{$day}");
            } else {
                $nextBday = $bdayThisYear;
            }

            $daysUntil = (int) $today->diff($nextBday)->format('%r%a');
            $turningAge = (int) $nextBday->format('Y') - (int) $birthDate->format('Y');
            $isRound = in_array($turningAge, self::ROUND_BIRTHDAYS, true);

            // Filtering
            if ($filterMonth !== null && (int) $nextBday->format('n') !== $filterMonth) {
                continue;
            }

            if ($filterMonth === null && $daysUntil > $days) {
                continue;
            }

            if ($roundOnly && !$isRound) {
                continue;
            }

            $results[] = [
                'member' => $member,
                'birth_date' => $member->birth_date,
                'next_birthday' => $nextBday->format('Y-m-d'),
                'days_until' => $daysUntil,
                'turning_age' => $turningAge,
                'is_round' => $isRound,
                'full_name' => $member->getFullName(),
                'email' => $member->email,
                'phone' => $member->phone,
                'city' => $member->city,
            ];
        }

        // Sort by days remaining ascending, then age descending
        usort($results, function ($a, $b) {
            if ($a['days_until'] === $b['days_until']) {
                return $b['turning_age'] <=> $a['turning_age'];
            }
            return $a['days_until'] <=> $b['days_until'];
        });

        return $results;
    }

    /**
     * Retrieve upcoming club membership anniversaries.
     *
     * @param int $targetYear Year to evaluate (defaults to current year)
     * @param bool $milestonesOnly Filter strictly for official milestones (10, 25, 40, 50, etc.)
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingAnniversaries(?int $targetYear = null, bool $milestonesOnly = true): array
    {
        $targetYear = $targetYear ?: (int) date('Y');
        $members = Member::where("status = 'Aktiv' AND join_date IS NOT NULL AND join_date != '0000-00-00'");

        $results = [];

        foreach ($members as $member) {
            try {
                $joinDate = new DateTimeImmutable($member->join_date);
            } catch (\Exception $e) {
                continue;
            }

            $joinYear = (int) $joinDate->format('Y');
            $years = $targetYear - $joinYear;

            if ($years <= 0) {
                continue;
            }

            $isMilestone = in_array($years, self::ANNIVERSARY_MILESTONES, true);

            if ($milestonesOnly && !$isMilestone) {
                continue;
            }

            $anniversaryDate = sprintf('%04d-%s', $targetYear, $joinDate->format('m-d'));

            $results[] = [
                'member' => $member,
                'join_date' => $member->join_date,
                'anniversary_date' => $anniversaryDate,
                'years' => $years,
                'is_milestone' => $isMilestone,
                'full_name' => $member->getFullName(),
                'membership_type' => $member->membership_type,
                'email' => $member->email,
                'phone' => $member->phone,
                'city' => $member->city,
            ];
        }

        // Sort by years descending, then anniversary date ascending
        usort($results, function ($a, $b) {
            if ($a['years'] === $b['years']) {
                return strcmp($a['anniversary_date'], $b['anniversary_date']);
            }
            return $b['years'] <=> $a['years'];
        });

        return $results;
    }

    /**
     * Export dataset to CSV with UTF-8 BOM for Microsoft Excel.
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     * @param string $filename
     * @return void
     */
    public function exportCsv(array $headers, array $rows, string $filename = 'report.csv'): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // Write UTF-8 BOM for Microsoft Excel
        fputs($out, "\xEF\xBB\xBF");

        // Write header line (using semicolon delimiter)
        fputcsv($out, $headers, ';');

        // Write data lines
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }

        fclose($out);
        exit;
    }

    /**
     * Generate publication-quality PDF report.
     *
     * @param string $title
     * @param string $subtitle
     * @param array<int, array{label: string, width: float, align?: string}> $columns
     * @param array<int, array<int, string>> $rows
     * @param Club $club
     * @param string $filename
     * @return void
     */
    public function exportPdf(
        string $title,
        string $subtitle,
        array $columns,
        array $rows,
        Club $club,
        string $filename = 'report.pdf'
    ): void {
        $pdf = new PdfBuilder('P', 'mm', 'A4');
        $pdf->setTitle($title);
        $pdf->addPage();

        // Club Header
        $pdf->setFont('Helvetica', 'B', 14);
        $pdf->setTextColor(20, 50, 90);
        $pdf->cell(0, 7, $club->name, 0, 1, 'L');

        $pdf->setFont('Helvetica', '', 9);
        $pdf->setTextColor(100, 100, 100);
        $pdf->cell(0, 5, $club->street . ', ' . $club->zip . ' ' . $club->city . ' | ' . $club->email, 0, 1, 'L');

        // Decorative Rule
        $pdf->setDrawColor(20, 50, 90);
        $pdf->line(15, $pdf->getY() + 2, 195, $pdf->getY() + 2);
        $pdf->ln(6);

        // Report Title & Subtitle
        $pdf->setFont('Helvetica', 'B', 16);
        $pdf->setTextColor(33, 37, 41);
        $pdf->cell(0, 8, $title, 0, 1, 'L');

        $pdf->setFont('Helvetica', 'I', 10);
        $pdf->setTextColor(108, 117, 125);
        $pdf->cell(0, 5, $subtitle . ' - Stand: ' . date('d.m.Y H:i'), 0, 1, 'L');
        $pdf->ln(6);

        // Table Header
        $pdf->setFont('Helvetica', 'B', 9);
        $pdf->setFillColor(240, 243, 246);
        $pdf->setTextColor(30, 41, 59);
        $pdf->setDrawColor(200, 205, 210);

        foreach ($columns as $col) {
            $align = $col['align'] ?? 'L';
            $pdf->cell($col['width'], 7, $col['label'], 1, 0, $align, true);
        }
        $pdf->ln(7);

        // Table Rows
        $pdf->setFont('Helvetica', '', 8.5);
        $fill = false;

        foreach ($rows as $row) {
            if ($fill) {
                $pdf->setFillColor(248, 249, 250);
                $pdf->setTextColor(33, 37, 41);
            } else {
                $pdf->setFillColor(255, 255, 255);
                $pdf->setTextColor(33, 37, 41);
            }

            foreach ($row as $i => $cellText) {
                $w = $columns[$i]['width'] ?? 30;
                $align = $columns[$i]['align'] ?? 'L';
                $pdf->cell($w, 6.5, (string)$cellText, 1, 0, $align, true);
            }
            $pdf->ln(6.5);
            $fill = !$fill;
        }

        // Summary footer
        $pdf->ln(5);
        $pdf->setFont('Helvetica', 'I', 8.5);
        $pdf->setTextColor(120, 120, 120);
        $pdf->cell(0, 5, 'Gesamt: ' . count($rows) . ' Datensaetze | Clubmanager v1.7 (Musikverein Denkendorf)', 0, 1, 'R');

        $pdf->output('I', $filename);
    }
}
