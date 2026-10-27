<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Reads the rows of an uploaded .xlsx or .csv file into memory.
 * Nothing is stored or generated: the upload is read from PHP's temporary file and discarded.
 *
 * Returns a list of ['row' => sheet row number, 'cells' => [column index => text]].
 */
class PatronImportReader
{
    public static function rows(string $path, string $extension): array
    {
        return strtolower($extension) === 'xlsx' ? self::xlsx($path) : self::csv($path);
    }

    private static function csv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('The file could not be opened.');
        }

        $first = (string) fgets($handle);
        rewind($handle);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        $rows = [];
        $n = 0;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $n++;

            if ($n === 1 && isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]); // UTF-8 BOM from Excel
            }

            $cells = [];
            foreach ($data as $i => $value) {
                $cells[$i] = trim((string) $value);
            }

            $rows[] = ['row' => $n, 'cells' => $cells];
        }

        fclose($handle);

        return $rows;
    }

    private static function xlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Excel files need the PHP "zip" extension. Save the sheet as CSV instead.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('This is not a valid .xlsx file. Save it again as Excel Workbook (.xlsx) or CSV.');
        }

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml !== false) {
            $xml = simplexml_load_string($sharedXml, SimpleXMLElement::class, LIBXML_NONET);

            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = '';

                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } else {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }

                    $shared[] = $text;
                }
            }
        }

        // First worksheet in the workbook.
        $sheets = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', (string) $name)) {
                $sheets[] = $name;
            }
        }
        natsort($sheets);
        $sheetName = reset($sheets);

        if (! $sheetName) {
            $zip->close();
            throw new RuntimeException('No worksheet was found in this file.');
        }

        $sheetXml = $zip->getFromName($sheetName);
        $zip->close();

        $sheet = $sheetXml === false ? false : simplexml_load_string($sheetXml, SimpleXMLElement::class, LIBXML_NONET);

        if (! $sheet || ! isset($sheet->sheetData)) {
            throw new RuntimeException('The worksheet could not be read.');
        }

        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $c) {
                $index = self::columnIndex((string) $c['r']);
                $type = (string) $c['t'];

                if ($type === 's') {
                    $value = $shared[(int) $c->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $c->is->t;
                } else {
                    $value = (string) $c->v;
                }

                $cells[$index] = trim($value);
            }

            $rows[] = ['row' => (int) $row['r'] ?: count($rows) + 1, 'cells' => $cells];
        }

        return $rows;
    }

    /** "A1" -> 0, "B7" -> 1, "AA3" -> 26 */
    private static function columnIndex(string $ref): int
    {
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $ref));
        $index = 0;

        foreach (str_split($letters) as $char) {
            $index = $index * 26 + (ord($char) - 64);
        }

        return max(0, $index - 1);
    }
}
