<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Clubmanager\Libraries\PdfBuilder;

$pdf = new PdfBuilder();
$pdf->addPage();
$pdf->cell(50, 10, 'Erste Seite');
$pdf->addPage();
$pdf->cell(50, 10, 'Zweite Seite');

foreach ([$pdf->output('S'), $pdf->output('S')] as $document) {
    if (!preg_match('/startxref\n(\d+)\n%%EOF/', $document, $match) || substr($document, (int) $match[1], 4) !== 'xref') {
        throw new RuntimeException('Invalid xref offset');
    }
    preg_match('/xref\n0 (\d+)\n(.*?)trailer\n/s', $document, $xref);
    $lines = explode("\n", trim($xref[2]));
    for ($id = 1; $id < (int) $xref[1]; $id++) {
        $offset = (int) substr($lines[$id], 0, 10);
        if (!str_starts_with(substr($document, $offset), "$id 0 obj\n")) {
            throw new RuntimeException("Invalid offset for object $id");
        }
    }
    foreach ([1, 2] as $page) {
        $id = 2 + $page;
        if (!preg_match('/' . $id . ' 0 obj\n(.*?)endobj/s', $document, $object)
            || !str_contains($object[1], '/Contents ' . (4 + $page) . ' 0 R')
            || !str_contains($object[1], '/F1 7 0 R /F2 8 0 R /F3 9 0 R')) {
            throw new RuntimeException("Invalid page $page references");
        }
    }
}
echo "PDF structure OK\n";