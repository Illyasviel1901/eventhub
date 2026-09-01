<?php

declare(strict_types=1);

function xlsxColumnName(int $number): string
{
    $name = '';
    while ($number > 0) {
        $number--;
        $name = chr(65 + ($number % 26)) . $name;
        $number = intdiv($number, 26);
    }
    return $name;
}

function xlsxXml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/** @param array<int, array<int, string|int|float|null>> $rows */
function createXlsxFile(array $rows, string $sheetName = 'Date'): string
{
    $path = tempnam(sys_get_temp_dir(), 'eventhub-xlsx-');
    if ($path === false) {
        throw new RuntimeException('Fișierul temporar XLSX nu a putut fi creat.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Arhiva XLSX nu a putut fi creată.');
    }

    $sheetRows = [];
    foreach ($rows as $rowIndex => $row) {
        $cells = [];
        foreach (array_values($row) as $columnIndex => $value) {
            $reference = xlsxColumnName($columnIndex + 1) . ($rowIndex + 1);
            if (is_int($value) || is_float($value)) {
                $cells[] = '<c r="' . $reference . '"><v>' . $value . '</v></c>';
            } else {
                $cells[] = '<c r="' . $reference . '" t="inlineStr"><is><t xml:space="preserve">' . xlsxXml((string) ($value ?? '')) . '</t></is></c>';
            }
        }
        $sheetRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . xlsxXml(substr($sheetName, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Arial"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="1"><xf xfId="0"/></cellXfs></styleSheet>');
    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . implode('', $sheetRows) . '</sheetData></worksheet>');
    $zip->close();

    return $path;
}

function sendXlsxDownload(string $path, string $filename): never
{
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9_.-]/', '-', $filename) . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    unlink($path);
    exit;
}

/** @return array<int, array<int, string>> */
function readXlsxFile(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Fișierul XLSX nu este o arhivă validă.');
    }

    if ($zip->numFiles > 100) {
        $zip->close();
        throw new RuntimeException('Fișierul XLSX conține prea multe componente.');
    }

    $uncompressedSize = 0;
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $statistics = $zip->statIndex($index);
        $uncompressedSize += (int) ($statistics['size'] ?? 0);
    }
    if ($uncompressedSize > 5 * 1024 * 1024) {
        $zip->close();
        throw new RuntimeException('Conținutul XLSX este prea mare.');
    }

    $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if (is_string($sharedXml)) {
        $sharedDocument = new DOMDocument();
        if (@$sharedDocument->loadXML($sharedXml, LIBXML_NONET | LIBXML_COMPACT)) {
            $sharedXPath = new DOMXPath($sharedDocument);
            $sharedXPath->registerNamespace('s', $namespace);
            foreach ($sharedXPath->query('//s:si') ?: [] as $item) {
                $value = '';
                foreach ($sharedXPath->query('.//s:t', $item) ?: [] as $part) {
                    $value .= $part->textContent;
                }
                $sharedStrings[] = $value;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!is_string($sheetXml)) {
        throw new RuntimeException('Fișierul XLSX nu conține prima foaie de calcul.');
    }

    $sheetDocument = new DOMDocument();
    if (!@$sheetDocument->loadXML($sheetXml, LIBXML_NONET | LIBXML_COMPACT)) {
        throw new RuntimeException('Prima foaie XLSX nu poate fi citită.');
    }
    $sheetXPath = new DOMXPath($sheetDocument);
    $sheetXPath->registerNamespace('s', $namespace);
    $rows = [];
    foreach ($sheetXPath->query('//s:sheetData/s:row') ?: [] as $rowNode) {
        if (count($rows) >= 501) {
            throw new RuntimeException('Fișierul poate conține maximum 500 de locații.');
        }
        $row = [];
        foreach ($sheetXPath->query('./s:c', $rowNode) ?: [] as $cell) {
            $reference = $cell->getAttribute('r');
            preg_match('/^[A-Z]+/', $reference, $match);
            $letters = $match[0] ?? 'A';
            $column = 0;
            foreach (str_split($letters) as $letter) {
                $column = $column * 26 + ord($letter) - 64;
            }
            $type = $cell->getAttribute('t');
            $valueNode = $sheetXPath->query('./s:v', $cell)?->item(0);
            if ($type === 's') {
                $value = $sharedStrings[(int) ($valueNode?->textContent ?? 0)] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = '';
                foreach ($sheetXPath->query('./s:is//s:t', $cell) ?: [] as $part) {
                    $value .= $part->textContent;
                }
            } else {
                $value = $valueNode?->textContent ?? '';
            }
            $row[$column - 1] = trim($value);
        }
        if ($row !== []) {
            $maximum = max(array_keys($row));
            $rows[] = array_replace(array_fill(0, $maximum + 1, ''), $row);
        }
    }

    return $rows;
}
