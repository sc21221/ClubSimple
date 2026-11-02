<?php

declare(strict_types=1);

namespace Clubmanager\Services;

use Clubmanager\Models\Club;
use Clubmanager\Models\Member;
use Clubmanager\Models\FeeClass;
use Clubmanager\Models\MemberFeeClass;
use Clubmanager\Repositories\FeeClassRepository;
use Clubmanager\Repositories\MembershipFeeRepository;
use Clubmanager\Core\Database;
use Clubmanager\Core\Logger;
use DateTimeImmutable;
use DOMDocument;
use RuntimeException;

/**
 * SEPA Direct Debit Service
 *
 * Implements automated age-based recategorization, transaction aggregation,
 * and ISO 20022 pain.008.001.02 SEPA Direct Debit XML generation.
 *
 * @package Clubmanager\Services
 * @author  Reinhard Schiessl
 */
class SepaService
{
    protected FeeClassRepository $feeClassRepo;
    protected MembershipFeeRepository $feeRepo;

    public function __construct()
    {
        $this->feeClassRepo = new FeeClassRepository();
        $this->feeRepo = new MembershipFeeRepository();
    }

    /**
     * Detect members whose age at $atDate is out of bounds for their assigned fee classes.
     *
     * @param string $atDate Reference collection date (Y-m-d)
     * @return array<int, array{
     *     member: Member,
     *     current_assignment: MemberFeeClass,
     *     current_fee_class: FeeClass,
     *     new_fee_class: FeeClass|null,
     *     age: int
     * }>
     */
    public function detectAgeRecategorizations(string $atDate): array
    {
        $sql = "SELECT mfc.id as assignment_id, mfc.member_id, mfc.fee_class_id
                FROM `member_fee_classes` mfc
                JOIN `members` m ON m.id = mfc.member_id
                WHERE mfc.is_active = 1 AND m.status = 'Aktiv'";
        $assignments = Database::fetchAll($sql);

        $recategorizations = [];

        foreach ($assignments as $row) {
            $member = Member::find($row['member_id']);
            $currentClass = FeeClass::find($row['fee_class_id']);
            $assignment = MemberFeeClass::find($row['assignment_id']);

            if (!$member || !$currentClass || !$assignment) {
                continue;
            }

            $age = $member->getAge($atDate);

            // Check if current class boundaries are violated
            if (!$currentClass->matchesAge($age)) {
                $newClass = $this->feeClassRepo->findForAge($age);

                if ($newClass && $newClass->id !== $currentClass->id) {
                    $recategorizations[] = [
                        'member' => $member,
                        'current_assignment' => $assignment,
                        'current_fee_class' => $currentClass,
                        'new_fee_class' => $newClass,
                        'age' => $age,
                    ];
                }
            }
        }

        return $recategorizations;
    }

    /**
     * Apply detected age recategorizations.
     *
     * @param array<int, array{
     *     member: Member,
     *     current_assignment: MemberFeeClass,
     *     current_fee_class: FeeClass,
     *     new_fee_class: FeeClass,
     *     age: int
     * }> $recategorizations
     * @param string $effectiveDate
     * @param int|null $userId
     * @return int Count of adjusted members
     */
    public function applyAgeRecategorizations(array $recategorizations, string $effectiveDate, ?int $userId = null): int
    {
        $count = 0;
        foreach ($recategorizations as $item) {
            /** @var Member $member */
            $member = $item['member'];
            /** @var MemberFeeClass $currentAssignment */
            $currentAssignment = $item['current_assignment'];
            /** @var FeeClass $newClass */
            $newClass = $item['new_fee_class'];

            if (!$newClass) {
                continue;
            }

            // Close current assignment
            $currentAssignment->end_date = $effectiveDate;
            $currentAssignment->is_active = 0;
            $currentAssignment->save();

            // Create new assignment
            $newAssignment = new MemberFeeClass([
                'member_id' => $member->id,
                'fee_class_id' => $newClass->id,
                'custom_amount' => null,
                'start_date' => $effectiveDate,
                'end_date' => null,
                'is_active' => 1,
            ]);
            $newAssignment->save();

            AuditService::log(
                'MEMBER_AGE_RECATEGORIZED',
                'members',
                (int) $member->id,
                ['fee_class_id' => $item['current_fee_class']->id, 'fee_class_name' => $item['current_fee_class']->name],
                ['fee_class_id' => $newClass->id, 'fee_class_name' => $newClass->name, 'reason' => "Age {$item['age']} reached"],
                $userId
            );

            $count++;
        }

        Logger::info("Applied automated age-based recategorization for {$count} members.");
        return $count;
    }

    /**
     * Preview debits to be executed for a specific due date.
     *
     * @return array{
     *     transactions: array<int, array{
     *         member: Member,
     *         amount: float,
     *         fee_classes: array<int, array<string, mixed>>,
     *         holder: string,
     *         iban: string,
     *         bic: string
     *     }>,
     *     total_amount: float,
     *     total_count: int
     * }
     */
    public function previewDebits(): array
    {
        // Select active members eligible for direct debit
        $members = Member::where("status = 'Aktiv' AND invoice_marker = 0 AND bank_iban IS NOT NULL AND bank_iban != ''", [], 'last_name ASC, first_name ASC');

        $transactions = [];
        $totalAmount = 0.0;

        foreach ($members as $member) {
            $feeClasses = $member->getActiveFeeClasses();
            if (empty($feeClasses)) {
                continue;
            }

            $memberTotal = 0.0;
            foreach ($feeClasses as $fc) {
                $amt = ($fc['custom_amount'] !== null && $fc['custom_amount'] !== '')
                    ? (float) $fc['custom_amount']
                    : (float) $fc['base_amount'];
                $memberTotal += $amt;
            }

            if ($memberTotal <= 0.0) {
                continue;
            }

            $holder = !empty($member->bank_holder) ? $member->bank_holder : $member->getFullName();
            $cleanIban = strtoupper(preg_replace('/\s+/', '', (string)$member->bank_iban));
            $cleanBic = strtoupper(trim((string)$member->bank_bic));

            $transactions[] = [
                'member' => $member,
                'amount' => $memberTotal,
                'fee_classes' => $feeClasses,
                'holder' => $holder,
                'iban' => $cleanIban,
                'bic' => $cleanBic,
            ];

            $totalAmount += $memberTotal;
        }

        return [
            'transactions' => $transactions,
            'total_amount' => round($totalAmount, 2),
            'total_count' => count($transactions),
        ];
    }

    /**
     * Generate ISO 20022 SEPA Direct Debit XML (pain.008.001.02).
     *
     * @param Club $club
     * @param array<int, array<string, mixed>> $transactions
     * @param string $dueDate
     * @param string $remittanceText
     * @return string Validated XML string
     */
    public function generateSepaXml(Club $club, array $transactions, string $dueDate, string $remittanceText): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        // Root element
        $root = $doc->createElement('Document');
        $root->setAttribute('xmlns', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:schemaLocation', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd');
        $doc->appendChild($root);

        $cstmrDrctDbtInitn = $doc->createElement('CstmrDrctDbtInitn');
        $root->appendChild($cstmrDrctDbtInitn);

        $now = new DateTimeImmutable();
        $msgId = 'CLM-' . $now->format('YmdHis') . '-' . mt_rand(100, 999);
        $totalSum = 0.0;
        foreach ($transactions as $t) {
            $totalSum += (float) $t['amount'];
        }
        $formattedSum = number_format($totalSum, 2, '.', '');
        $txCount = count($transactions);

        // Group Header (GrpHdr)
        $grpHdr = $doc->createElement('GrpHdr');
        $grpHdr->appendChild($doc->createElement('MsgId', $msgId));
        $grpHdr->appendChild($doc->createElement('CreDtTm', $now->format('Y-m-d\TH:i:s\Z')));
        $grpHdr->appendChild($doc->createElement('NbOfTxs', (string)$txCount));
        $grpHdr->appendChild($doc->createElement('CtrlSum', $formattedSum));

        $initgPty = $doc->createElement('InitgPty');
        $initgPty->appendChild($doc->createElement('Nm', htmlspecialchars($club->name, ENT_XML1)));
        $grpHdr->appendChild($initgPty);
        $cstmrDrctDbtInitn->appendChild($grpHdr);

        // Payment Information (PmtInf)
        $pmtInf = $doc->createElement('PmtInf');
        $pmtInf->appendChild($doc->createElement('PmtInfId', 'PMT-' . $now->format('YmdHis')));
        $pmtInf->appendChild($doc->createElement('PmtMtd', 'DD'));
        $pmtInf->appendChild($doc->createElement('BtchBookg', 'true'));
        $pmtInf->appendChild($doc->createElement('NbOfTxs', (string)$txCount));
        $pmtInf->appendChild($doc->createElement('CtrlSum', $formattedSum));

        // Payment Type Information (PmtTpInf)
        $pmtTpInf = $doc->createElement('PmtTpInf');
        $svcLvl = $doc->createElement('SvcLvl');
        $svcLvl->appendChild($doc->createElement('Cd', 'SEPA'));
        $pmtTpInf->appendChild($svcLvl);

        $lclInstrm = $doc->createElement('LclInstrm');
        $lclInstrm->appendChild($doc->createElement('Cd', 'CORE'));
        $pmtTpInf->appendChild($lclInstrm);

        $pmtTpInf->appendChild($doc->createElement('SeqTp', 'RCUR'));
        $pmtInf->appendChild($pmtTpInf);

        $pmtInf->appendChild($doc->createElement('ReqdColltnDt', $dueDate));

        // Creditor (Cdtr)
        $cdtr = $doc->createElement('Cdtr');
        $cdtr->appendChild($doc->createElement('Nm', htmlspecialchars($club->name, ENT_XML1)));
        $pmtInf->appendChild($cdtr);

        // Creditor Account (CdtrAcct)
        $cleanClubIban = strtoupper(preg_replace('/\s+/', '', (string)$club->bank_iban));
        $cdtrAcct = $doc->createElement('CdtrAcct');
        $cdtrAcctId = $doc->createElement('Id');
        $cdtrAcctId->appendChild($doc->createElement('IBAN', $cleanClubIban));
        $cdtrAcct->appendChild($cdtrAcctId);
        $pmtInf->appendChild($cdtrAcct);

        // Creditor Agent (CdtrAgt)
        $cleanClubBic = strtoupper(trim((string)$club->bank_bic));
        $cdtrAgt = $doc->createElement('CdtrAgt');
        $finInstnId = $doc->createElement('FinInstnId');
        $finInstnId->appendChild($doc->createElement('BIC', $cleanClubBic));
        $cdtrAgt->appendChild($finInstnId);
        $pmtInf->appendChild($cdtrAgt);

        $pmtInf->appendChild($doc->createElement('ChrgBr', 'SLEV'));

        // Creditor Scheme Identification (CdtrSchmeId)
        $cdtrSchmeId = $doc->createElement('CdtrSchmeId');
        $schemeId = $doc->createElement('Id');
        $orgId = $doc->createElement('OrgId');
        $othr = $doc->createElement('Othr');
        $othr->appendChild($doc->createElement('Id', trim((string)$club->bank_creditor_id)));
        $schmeNm = $doc->createElement('SchmeNm');
        $schmeNm->appendChild($doc->createElement('Prtry', 'SEPA'));
        $othr->appendChild($schmeNm);
        $orgId->appendChild($othr);
        $schemeId->appendChild($orgId);
        $cdtrSchmeId->appendChild($schemeId);
        $pmtInf->appendChild($cdtrSchmeId);

        // Direct Debit Transaction Information (DrctDbtTxInf)
        $index = 1;
        foreach ($transactions as $tx) {
            /** @var Member $member */
            $member = $tx['member'];
            $amtStr = number_format((float)$tx['amount'], 2, '.', '');
            $mandateId = 'MND-MBR-' . $member->id;
            $signatureDate = !empty($member->join_date) ? $member->join_date : '2020-01-01';

            $txInf = $doc->createElement('DrctDbtTxInf');

            // Payment Id
            $pmtId = $doc->createElement('PmtId');
            $pmtId->appendChild($doc->createElement('EndToEndId', 'E2E-' . $now->format('Ymd') . '-' . $member->id . '-' . $index));
            $txInf->appendChild($pmtId);

            // Instructed Amount
            $instdAmt = $doc->createElement('InstdAmt', $amtStr);
            $instdAmt->setAttribute('Ccy', 'EUR');
            $txInf->appendChild($instdAmt);

            // Direct Debit Transaction details
            $drctDbtTx = $doc->createElement('DrctDbtTx');
            $mndtRltdInf = $doc->createElement('MndtRltdInf');
            $mndtRltdInf->appendChild($doc->createElement('MndtId', $mandateId));
            $mndtRltdInf->appendChild($doc->createElement('DtOfSgntr', $signatureDate));
            $mndtRltdInf->appendChild($doc->createElement('AmdmntInd', 'false'));
            $drctDbtTx->appendChild($mndtRltdInf);
            $txInf->appendChild($drctDbtTx);

            // Debtor Agent (BIC)
            $dbtrAgt = $doc->createElement('DbtrAgt');
            $dbtrFinInstnId = $doc->createElement('FinInstnId');
            if (!empty($tx['bic'])) {
                $dbtrFinInstnId->appendChild($doc->createElement('BIC', $tx['bic']));
            } else {
                $dbtrOthr = $doc->createElement('Othr');
                $dbtrOthr->appendChild($doc->createElement('Id', 'NOTPROVIDED'));
                $dbtrFinInstnId->appendChild($dbtrOthr);
            }
            $dbtrAgt->appendChild($dbtrFinInstnId);
            $txInf->appendChild($dbtrAgt);

            // Debtor (Dbtr)
            $dbtr = $doc->createElement('Dbtr');
            $dbtr->appendChild($doc->createElement('Nm', htmlspecialchars($tx['holder'], ENT_XML1)));
            $txInf->appendChild($dbtr);

            // Debtor Account (DbtrAcct)
            $dbtrAcct = $doc->createElement('DbtrAcct');
            $dbtrAcctId = $doc->createElement('Id');
            $dbtrAcctId->appendChild($doc->createElement('IBAN', $tx['iban']));
            $dbtrAcct->appendChild($dbtrAcctId);
            $txInf->appendChild($dbtrAcct);

            // Remittance Information (RmtInf)
            $rmtInf = $doc->createElement('RmtInf');
            $rmtText = str_replace([':year', ':id', ':name'], [date('Y', strtotime($dueDate)), (string)$member->id, $member->getFullName()], $remittanceText);
            $rmtInf->appendChild($doc->createElement('Ustrd', htmlspecialchars(substr($rmtText, 0, 140), ENT_XML1)));
            $txInf->appendChild($rmtInf);

            $pmtInf->appendChild($txInf);
            $index++;
        }

        $cstmrDrctDbtInitn->appendChild($pmtInf);
        return $doc->saveXML();
    }

    /**
     * Execute and record the full SEPA Direct Debit Run.
     *
     * @param Club $club
     * @param string $dueDate
     * @param string $remittanceText
     * @param bool $autoRecategorize
     * @param int|null $userId
     * @return array{
     *     xml: string,
     *     filepath: string,
     *     filename: string,
     *     total_amount: float,
     *     total_transactions: int,
     *     recategorized_count: int
     * }
     */
    public function executeDebitRun(
        Club $club,
        string $dueDate,
        string $remittanceText,
        bool $autoRecategorize = true,
        ?int $userId = null
    ): array {
        // Step 1: Automated Age Recategorization if requested
        $recatCount = 0;
        if ($autoRecategorize) {
            $recats = $this->detectAgeRecategorizations($dueDate);
            if (!empty($recats)) {
                $recatCount = $this->applyAgeRecategorizations($recats, $dueDate, $userId);
            }
        }

        // Step 2: Build transactions preview
        $preview = $this->previewDebits();
        $transactions = $preview['transactions'];

        if (empty($transactions)) {
            throw new RuntimeException("Keine abrechenbaren Mitglieder mit gültiger Bankverbindung vorhanden.");
        }

        // Step 3: Generate XML
        $xmlContent = $this->generateSepaXml($club, $transactions, $dueDate, $remittanceText);

        // Step 4: Save XML to disk
        $storageDir = dirname(__DIR__, 2) . '/storage/sepa';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $filename = 'SEPA_DirectDebit_' . str_replace('-', '', $dueDate) . '_' . date('His') . '_' . bin2hex(random_bytes(8)) . '.xml';
        $filepath = $storageDir . '/' . $filename;
        if (file_put_contents($filepath, $xmlContent, LOCK_EX) === false) {
            throw new RuntimeException('Die SEPA-Datei konnte nicht gespeichert werden.');
        }

        // Step 5: Record the run and its fee items atomically
        Database::beginTransaction();
        try {
            Database::execute('INSERT INTO `sepa_runs` (`due_date`) VALUES (?)', [$dueDate]);
            $runId = (int) Database::lastInsertId();
            foreach ($transactions as $tx) {
                /** @var Member $member */
                $member = $tx['member'];
                foreach ($tx['fee_classes'] as $fc) {
                    $itemAmt = ($fc['custom_amount'] !== null && $fc['custom_amount'] !== '')
                        ? (float) $fc['custom_amount']
                        : (float) $fc['base_amount'];

                    Database::execute(
                        'INSERT INTO `membership_fees` (`member_id`, `fee_class_id`, `amount`, `due_date`, `paid_date`, `status`, `created_at`, `sepa_run_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [(int) $member->id, (int) $fc['fee_class_id'], $itemAmt, $dueDate, null, 'Offen', date('Y-m-d H:i:s'), $runId]
                    );
                }
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            unlink($filepath);
            throw $e;
        }

        // Step 6: Audit trail & structured logging
        AuditService::log(
            'SEPA_RUN_EXECUTED',
            'membership_fees',
            null,
            null,
            [
                'due_date' => $dueDate,
                'total_amount' => $preview['total_amount'],
                'total_transactions' => $preview['total_count'],
                'recategorized_count' => $recatCount,
                'xml_file' => $filename,
            ],
            $userId
        );

        Logger::info("SEPA Run executed successfully: €{$preview['total_amount']} across {$preview['total_count']} debits.", [
            'DueDate' => $dueDate,
            'TotalAmount' => $preview['total_amount'],
            'Transactions' => $preview['total_count'],
            'Recategorized' => $recatCount,
        ]);

        return [
            'xml' => $xmlContent,
            'filepath' => $filepath,
            'filename' => $filename,
            'total_amount' => $preview['total_amount'],
            'total_transactions' => $preview['total_count'],
            'recategorized_count' => $recatCount,
        ];
    }
}
