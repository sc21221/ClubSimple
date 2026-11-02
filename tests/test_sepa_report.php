<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Services\SepaReportService;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$data = [
    'club' => 'Musikverein ÄÖÜ',
    'due_date' => '2026-12-01',
    'remittance' => str_repeat('W', 220) . ' Ende',
    'transactions' => [],
    'total_amount' => 12345.67,
];
for ($i = 1; $i <= 100; $i++) {
    $data['transactions'][] = [
        'member_name' => 'Jörg Müller ' . $i,
        'member_id' => $i,
        'holder' => 'Käthe Müller ' . $i,
        'iban' => 'DE12345678901234567890',
        'bic' => 'TESTDEFFXXX',
        'amount' => 123.45,
        'fee_classes' => [
            ['name' => 'Erwachsene ' . $i, 'amount' => 100.25],
            ['name' => str_repeat('X', 300) . ' Ende ' . $i, 'amount' => 23.20],
        ],
    ];
}
$original = $data;
$pdf = (new SepaReportService())->generate($data);
check($data === $original, 'Eingabedaten wurden verändert');
check(str_starts_with($pdf, '%PDF-1.4'), 'PDF-Kopf fehlt');
check((bool) preg_match('/\/Type \/Pages \/Kids \[.*?\] \/Count (\d+)/', $pdf, $pages) && (int) $pages[1] > 1, 'Mehrere Seiten erwartet');
foreach (['Musikverein ÄÖÜ', '2026-12-01', 'Jörg Müller 100', 'Käthe Müller 100', 'DE12345678901234567890', 'TESTDEFFXXX', 'Erwachsene 100', '23,20 EUR', '123,45 EUR', '12.345,67 EUR', 'Ende 100'] as $text) {
    check(str_contains($pdf, mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8')), 'Text fehlt: ' . $text);
}
check(substr_count($pdf, '(123,45 EUR)') === 100, 'Nicht alle Positionen enthalten');
check(substr_count($pdf, '(Beitragsklasse)') === (int) $pages[1], 'Tabellenkopf muss auf jeder Seite stehen');
foreach (['Lastschrift-', 'nummer', 'Mitglied', 'Kontoinhaber', 'IBAN', 'BIC', 'Betrag'] as $header) {
    check(str_contains($pdf, mb_convert_encoding($header, 'ISO-8859-1', 'UTF-8')), 'Tabellenkopf fehlt: ' . $header);
}
check(substr_count($pdf, 'W') >= 220 && substr_count($pdf, 'X') >= 30000, 'Lange Texte abgeschnitten');
check((bool) preg_match('/startxref\n(\d+)\n%%EOF/', $pdf, $xref) && substr($pdf, (int) $xref[1], 4) === 'xref', 'Ungültiges PDF-Inhaltsverzeichnis');

$names = $original;
$names['transactions'] = [$original['transactions'][0]];
$names['transactions'][0]['member_name'] = 'Maximilian Mustermann Beispiel';
$names['transactions'][0]['holder'] = str_repeat('i', 35);
$namesPdf = (new SepaReportService())->generate($names);
check(str_contains($namesPdf, 'Maximilian Mustermann Beispiel'), 'Mitgliedsname wird zu früh umgebrochen');
check(str_contains($namesPdf, str_repeat('i', 35)), 'Kontoinhaber wird zu früh umgebrochen');
$names['transactions'][0]['holder'] = str_repeat('W', 35);
$widePdf = (new SepaReportService())->generate($names);
check(!str_contains($widePdf, '(' . str_repeat('W', 35) . ')'), 'Breite Zeichen müssen innerhalb der Spalte umgebrochen werden');

$empty = $original;
$empty['transactions'] = [];
$empty['total_amount'] = 0.0;
$emptyPdf = (new SepaReportService())->generate($empty);
check(str_contains($emptyPdf, 'Anzahl Buchungen: 0') && str_contains($emptyPdf, '0,00 EUR'), 'Leerer Bericht fehlerhaft');

echo 'SEPA-Bericht OK: ' . $pages[1] . " Seiten, 100 Positionen\n";