<?php

declare(strict_types=1);

namespace Clubmanager\Services;

use Clubmanager\Models\Member;
use Clubmanager\Models\Club;
use Clubmanager\Libraries\PdfBuilder;
use Clubmanager\Repositories\MemberRepository;
use Clubmanager\Repositories\MembershipFeeRepository;
use Clubmanager\Core\Response;

/**
 * GDPR Data Protection Service
 *
 * Implements GDPR / DSGVO compliance tools:
 * - Article 15: Right of access / personal data export
 * - Article 17: Right to erasure / irreversible anonymization
 *
 * @package Clubmanager\Services
 * @author  Reinhard Schiessl
 */
class GdprService
{
    protected MemberRepository $memberRepo;
    protected MembershipFeeRepository $feeRepo;

    public function __construct()
    {
        $this->memberRepo = new MemberRepository();
        $this->feeRepo = new MembershipFeeRepository();
    }

    /**
     * Gather complete dossier of member data stored in the system.
     *
     * @param Member $member
     * @return array<string, mixed>
     */
    public function getMemberDossier(Member $member): array
    {
        return [
            'member_id' => $member->id,
            'personal_data' => [
                'salutation' => $member->salutation,
                'sex' => $member->sex,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'birth_date' => $member->birth_date,
                'age' => $member->getAge(),
            ],
            'contact_data' => [
                'email' => $member->email,
                'phone' => $member->phone,
                'street' => $member->street,
                'zip' => $member->zip,
                'city' => $member->city,
                'country' => $member->country,
            ],
            'membership_status' => [
                'membership_type' => $member->membership_type,
                'status' => $member->status,
                'invoice_marker' => (bool) $member->invoice_marker,
                'join_date' => $member->join_date,
                'leave_date' => $member->leave_date,
                'years_of_membership' => $member->getYearsOfMembership(),
            ],
            'bank_data' => [
                'bank_name' => $member->bank_name,
                'bank_bic' => $member->bank_bic,
                'bank_iban' => $member->bank_iban,
                'bank_holder' => $member->bank_holder,
            ],
            'fee_class_assignments' => $member->getActiveFeeClasses(),
            'fee_payment_history' => $this->feeRepo->getForMember((int) $member->id),
            'export_timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Export member data as JSON.
     *
     * @param Member $member
     * @return void
     */
    public function exportJson(Member $member): void
    {
        $dossier = $this->getMemberDossier($member);
        $json = json_encode($dossier, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = 'DSGVO_Export_Mitglied_' . $member->id . '_' . date('Ymd_His') . '.json';
        Response::downloadContent($json, $filename, 'application/json');
    }

    /**
     * Export member data as official PDF disclosure document.
     *
     * @param Member $member
     * @param Club $club
     * @return void
     */
    public function exportPdf(Member $member, Club $club): void
    {
        $dossier = $this->getMemberDossier($member);

        $pdf = new PdfBuilder('P', 'mm', 'A4');
        $pdf->setTitle('DSGVO-Datenauskunft Art. 15 DSGVO');
        $pdf->addPage();

        // Club Header
        $pdf->setFont('Helvetica', 'B', 14);
        $pdf->setTextColor(20, 50, 90);
        $pdf->cell(0, 7, $club->name, 0, 1, 'L');

        $pdf->setFont('Helvetica', '', 9);
        $pdf->setTextColor(100, 100, 100);
        $pdf->cell(0, 5, 'Offizielle DSGVO-Selbstauskunft gem. Art. 15 DSGVO', 0, 1, 'L');

        $pdf->line(15, $pdf->getY() + 2, 195, $pdf->getY() + 2);
        $pdf->ln(6);

        // Document Heading
        $pdf->setFont('Helvetica', 'B', 16);
        $pdf->setTextColor(33, 37, 41);
        $pdf->cell(0, 8, 'Datenauskunft nach Art. 15 DSGVO', 0, 1, 'L');

        $pdf->setFont('Helvetica', 'I', 10);
        $pdf->setTextColor(108, 117, 125);
        $pdf->cell(0, 5, 'Mitglied #' . $member->id . ' - ' . $member->getFullName() . ' (Erstellt am ' . date('d.m.Y H:i') . ')', 0, 1, 'L');
        $pdf->ln(6);

        // Section 1: Personal Data
        $this->renderPdfSection($pdf, '1. Stammdaten & Kontaktdaten', [
            ['Name', $member->getFullName()],
            ['Anrede / Geschlecht', $member->salutation . ' / ' . $member->sex],
            ['Geburtsdatum / Alter', format_date($member->birth_date) . ' (' . $member->getAge() . ' Jahre)'],
            ['E-Mail-Adresse', $member->email ?: '-'],
            ['Telefonnummer', $member->phone ?: '-'],
            ['Anschrift', $member->street . ', ' . $member->zip . ' ' . $member->city . ' (' . ($member->country ?: 'DE') . ')'],
        ]);

        // Section 2: Membership Details
        $this->renderPdfSection($pdf, '2. Vereinsmitgliedschaft', [
            ['Mitgliedsart', $member->membership_type],
            ['Status', $member->status],
            ['Eintrittsdatum', format_date($member->join_date) . ' (' . $member->getYearsOfMembership() . ' Jahre Mitgliedschaft)'],
            ['Austrittsdatum', format_date($member->leave_date)],
            ['Rechnungsempfaenger', $member->invoice_marker ? 'Ja (Keine SEPA-Lastschrift)' : 'Nein (SEPA-Einzug aktiv)'],
        ]);

        // Section 3: Bank Details
        $this->renderPdfSection($pdf, '3. Bank- & Mandatsdaten (SEPA)', [
            ['Kontoinhaber', $member->bank_holder ?: $member->getFullName()],
            ['Kreditinstitut', $member->bank_name ?: '-'],
            ['IBAN', format_iban($member->bank_iban) ?: '-'],
            ['BIC', $member->bank_bic ?: '-'],
            ['SEPA-Mandatsreferenz', 'MND-MBR-' . $member->id],
        ]);

        // Footer note
        $pdf->ln(4);
        $pdf->setFont('Helvetica', 'I', 8);
        $pdf->setTextColor(120, 120, 120);
        $pdf->cell(0, 4, 'Diese Auskunft enthaelt saemtliche zu Ihrer Person im Vereinssystem gespeicherten Daten gemaess DSGVO.', 0, 1, 'L');

        $filename = 'DSGVO_Auskunft_Mitglied_' . $member->id . '.pdf';
        $pdf->output('I', $filename);
    }

    /**
     * Render a neat key-value section block in PDF.
     *
     * @param PdfBuilder $pdf
     * @param string $heading
     * @param array<int, array{0: string, 1: string}> $rows
     * @return void
     */
    protected function renderPdfSection(PdfBuilder $pdf, string $heading, array $rows): void
    {
        $pdf->setFont('Helvetica', 'B', 11);
        $pdf->setFillColor(235, 240, 245);
        $pdf->setTextColor(20, 50, 90);
        $pdf->cell(0, 6.5, ' ' . $heading, 0, 1, 'L', true);
        $pdf->ln(1);

        $pdf->setFont('Helvetica', '', 9);
        $pdf->setDrawColor(220, 225, 230);

        foreach ($rows as $row) {
            $pdf->setTextColor(80, 80, 80);
            $pdf->cell(50, 6, $row[0], 'B', 0, 'L');
            $pdf->setTextColor(20, 20, 20);
            $pdf->cell(0, 6, $row[1], 'B', 1, 'L');
        }
        $pdf->ln(4);
    }

    /**
     * Irreversibly anonymize member personal data.
     *
     * @param Member $member
     * @param int|null $userId
     * @return bool
     */
    public function anonymize(Member $member, ?int $userId = null): bool
    {
        return $this->memberRepo->anonymize($member, $userId);
    }
}
