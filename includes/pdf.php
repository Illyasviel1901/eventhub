<?php

declare(strict_types=1);

function pdfAscii(string $text): string
{
    return strtr($text, [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ş' => 'S', 'Ț' => 'T', 'Ţ' => 'T',
        '–' => '-', '—' => '-', '„' => '"', '”' => '"', '’' => "'",
    ]);
}

function pdfEscape(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], pdfAscii($text));
}

/** @param array<int, string> $lines */
function createSimplePdf(array $lines): string
{
    $wrapped = [];
    foreach ($lines as $line) {
        $parts = explode("\n", wordwrap(pdfAscii($line), 92, "\n", true));
        array_push($wrapped, ...$parts);
    }
    $pages = array_chunk($wrapped, 50);
    if ($pages === []) {
        $pages = [['Raport EventHub']];
    }

    $objects = [];
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $kids = [];
    foreach ($pages as $index => $_) {
        $kids[] = (4 + $index * 2) . ' 0 R';
    }
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pages) . ' >>';
    $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

    foreach ($pages as $index => $pageLines) {
        $pageObject = 4 + $index * 2;
        $contentObject = $pageObject + 1;
        $commands = ['BT', '/F1 10 Tf', '45 800 Td'];
        foreach ($pageLines as $lineIndex => $line) {
            if ($lineIndex > 0) {
                $commands[] = '0 -14 Td';
            }
            $commands[] = '(' . pdfEscape($line) . ') Tj';
        }
        $commands[] = 'ET';
        $stream = implode("\n", $commands) . "\n";
        $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
        $objects[$contentObject] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
    }

    ksort($objects);
    $pdf = "%PDF-1.4\n%EventHub\n";
    $offsets = [0];
    foreach ($objects as $number => $object) {
        $offsets[$number] = strlen($pdf);
        $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $maximum = max(array_keys($objects));
    $pdf .= "xref\n0 " . ($maximum + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($number = 1; $number <= $maximum; $number++) {
        $pdf .= sprintf('%010d 00000 n ', $offsets[$number]) . "\n";
    }
    $pdf .= "trailer\n<< /Size " . ($maximum + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

function sendPdfDownload(string $content, string $filename): never
{
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9_.-]/', '-', $filename) . '"');
    header('Content-Length: ' . strlen($content));
    header('X-Content-Type-Options: nosniff');
    echo $content;
    exit;
}
