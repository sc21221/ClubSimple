<?php

declare(strict_types=1);

namespace Clubmanager\Services;

use Clubmanager\Libraries\PdfBuilder;

class SepaReportService
{
    public function generate(array $data): string
    {
        $pdf = new PdfBuilder('L');
        $pdf->setTitle('SEPA-Lastschriftbericht');
        $pdf->addPage();
        $pdf->setFont('Helvetica', 'B', 14);
        $this->write($pdf, 'SEPA-Lastschriftbericht', 14);
        $pdf->setFont('Helvetica', '', 10);
        $this->write($pdf, 'Gläubiger: ' . $data['club']);
        $this->write($pdf, 'Fälligkeitsdatum: ' . $data['due_date']);
        $this->write($pdf, 'Verwendungszweck: ' . $data['remittance']);
        $this->write($pdf, 'Anzahl Buchungen: ' . count($data['transactions']));
        $this->write($pdf, 'Gesamtbetrag: ' . $this->money($data['total_amount']));

        $this->tableHeader($pdf);
        foreach ($data['transactions'] as $index => $transaction) {
            $feeClasses = array_map(fn(array $feeClass): string => $feeClass['name'] . ': ' . $this->money($feeClass['amount']), $transaction['fee_classes']);
            $this->tableRow($pdf, [
                (string) ($index + 1),
                $transaction['member_name'] . ' (ID #' . $transaction['member_id'] . ")\n" . $transaction['holder'],
                $transaction['iban'] . "\n" . $transaction['bic'],
                implode("\n", $feeClasses),
                $this->money($transaction['amount']),
            ]);
        }

        $pdf->setFont('Helvetica', 'B', 10);
        $this->write($pdf, 'Summe aller ' . count($data['transactions']) . ' Lastschriften: ' . $this->money($data['total_amount']));

        return $pdf->output('S');
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', '.') . ' EUR';
    }

    private function tableHeader(PdfBuilder $pdf): void
    {
        if ($pdf->getY() + 10 > 195) {
            $pdf->addPage();
        }
        $pdf->setFont('Helvetica', 'B', 7);
        $this->tableLine($pdf, ['Lastschrift-', 'Mitglied', 'IBAN', 'Beitragsklasse', 'Betrag']);
        $this->tableLine($pdf, ['nummer', 'Kontoinhaber', 'BIC', '', '']);
        $pdf->setFont('Helvetica', '', 7);
    }

    private function tableRow(PdfBuilder $pdf, array $values): void
    {
        $widths = [29, 60, 67, 76, 35];
        $columns = [];
        foreach ($values as $i => $value) {
            $maxLength = (int) floor(($widths[$i] - 4) * 2.83464567 / 7);
            $lines = [];
            foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $value)) as $paragraph) {
                while (true) {
                    $length = $i === 1 ? $this->nameLineLength($paragraph, $widths[$i]) : $maxLength;
                    if (mb_strlen($paragraph, 'UTF-8') <= $length) {
                        break;
                    }
                    $chunk = mb_substr($paragraph, 0, $length, 'UTF-8');
                    $space = mb_strrpos($chunk, ' ', 0, 'UTF-8');
                    $length = $space === false || $space === 0 ? $length : $space + 1;
                    $lines[] = mb_substr($paragraph, 0, $length, 'UTF-8');
                    $paragraph = mb_substr($paragraph, $length, null, 'UTF-8');
                }
                $lines[] = $paragraph;
            }
            $columns[] = $lines;
        }

        $lineCount = max(array_map('count', $columns));
        for ($line = 0; $line < $lineCount; $line++) {
            if ($pdf->getY() + 5 > 195) {
                $pdf->addPage();
                $this->tableHeader($pdf);
            }
            $this->tableLine($pdf, array_map(static fn(array $column): string => $column[$line] ?? '', $columns));
        }
    }

    private function nameLineLength(string $text, float $width): int
    {
        $limit = ($width - 4) * 2.83464567;
        $used = 0.0;
        $length = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            $factor = match (true) {
                $char === ' ' => 0.28,
                $char === 'm' => 0.84,
                $char === 'w' => 0.73,
                $char >= 'a' && $char <= 'z' => 0.6,
                $char >= '0' && $char <= '9' => 0.56,
                default => 1.0,
            };
            $used += $factor * 7;
            if ($used > $limit) {
                return max(1, $i);
            }
        }
        return $length;
    }

    private function tableLine(PdfBuilder $pdf, array $values): void
    {
        foreach ([29, 60, 67, 76, 35] as $i => $width) {
            $pdf->cell($width, 5, $values[$i], 1, $i === 4 ? 1 : 0, $i === 4 ? 'R' : 'L');
        }
    }

    private function write(PdfBuilder $pdf, string $text, float $fontSize = 10): void
    {
        // Helvetica's widest glyph is about one em; leave room for cell padding.
        $maxLength = (int) floor(176 * 2.83464567 / $fontSize);
        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $text)) as $paragraph) {
            while (mb_strlen($paragraph, 'UTF-8') > $maxLength) {
                $chunk = mb_substr($paragraph, 0, $maxLength, 'UTF-8');
                $space = mb_strrpos($chunk, ' ', 0, 'UTF-8');
                $length = $space === false || $space === 0 ? $maxLength : $space + 1;
                $pdf->cell(180, 6, mb_substr($paragraph, 0, $length, 'UTF-8'), 0, 1);
                $paragraph = mb_substr($paragraph, $length, null, 'UTF-8');
            }
            $pdf->cell(180, 6, $paragraph, 0, 1);
        }
    }
}