<?php

namespace App\Services\Import;

use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * قارئ ملفات xlsx و csv بدون أي مكتبة خارجية.
 * يرجع قائمة صفوف، كل صف: ['row' => رقم الصف الأصلي بالملف, 'cells' => [نصوص مرتبة بالأعمدة]]
 * الصفوف الفارغة بالكامل تُتجاهل.
 */
class SpreadsheetReader
{
    public const MAX_ROWS = 10000;
    public const MAX_COLS = 60;
    private const MAX_XML_BYTES = 150 * 1024 * 1024;

    public static function read(string $path, string $originalName = ''): array
    {
        $ext = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));
        $head = (string) @file_get_contents($path, false, null, 0, 8);

        if (str_starts_with($head, "\xD0\xCF\x11\xE0")) {
            throw new RuntimeException('هذا ملف Excel بصيغة xls القديمة. افتحه بـ Excel ثم احفظه باسم جديد بصيغة xlsx وارفعه من جديد.');
        }

        if (str_starts_with($head, 'PK')) {
            return self::readXlsx($path);
        }

        if (in_array($ext, ['csv', 'txt', 'tsv'], true)) {
            return self::readCsv($path);
        }

        throw new RuntimeException('صيغة الملف غير مدعومة. المسموح: xlsx أو csv.');
    }

    // ------------------------------------------------------------------ xlsx

    private static function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('تعذّر فتح الملف، يبدو تالفاً.');
        }

        try {
            $sheetPath = self::firstSheetPath($zip);

            $stat = $zip->statName($sheetPath);
            if ($stat === false) {
                throw new RuntimeException('لم أجد ورقة بيانات داخل الملف.');
            }
            if ($stat['size'] > self::MAX_XML_BYTES) {
                throw new RuntimeException('الملف كبير جداً.');
            }

            $shared = self::readSharedStrings($zip, $path);

            return self::readSheet($path, $sheetPath, $shared);
        } finally {
            $zip->close();
        }
    }

    private static function firstSheetPath(ZipArchive $zip): string
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($wb === false || $rels === false) {
            throw new RuntimeException('الملف ليس Excel صالحاً.');
        }

        $prev = libxml_use_internal_errors(true);
        $wbXml = simplexml_load_string($wb, 'SimpleXMLElement', LIBXML_NONET);
        $relXml = simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($prev);

        if (! $wbXml || ! $relXml) {
            throw new RuntimeException('الملف ليس Excel صالحاً.');
        }

        $targets = [];
        foreach ($relXml->Relationship as $rel) {
            $targets[(string) $rel['Id']] = (string) $rel['Target'];
        }

        $wbXml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($wbXml->xpath('//m:sheets/m:sheet') ?: [] as $sheet) {
            if (in_array((string) $sheet['state'], ['hidden', 'veryHidden'], true)) {
                continue;
            }
            $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            if (isset($targets[$rid])) {
                $t = $targets[$rid];

                return str_starts_with($t, '/') ? ltrim($t, '/') : 'xl/' . $t;
            }
        }

        throw new RuntimeException('لم أجد ورقة بيانات داخل الملف.');
    }

    private static function readSharedStrings(ZipArchive $zip, string $path): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $r = new XMLReader();
        $r->open('zip://' . $path . '#xl/sharedStrings.xml', null, LIBXML_NONET);

        $out = [];
        $current = '';
        $inSi = false;
        $inPhonetic = false;

        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT) {
                if ($r->name === 'si') {
                    if ($r->isEmptyElement) {
                        $out[] = '';
                    } else {
                        $inSi = true;
                        $current = '';
                    }
                } elseif ($r->name === 'rPh') {
                    $inPhonetic = true;
                } elseif ($r->name === 't' && $inSi && ! $inPhonetic && ! $r->isEmptyElement) {
                    $current .= $r->readString();
                }
            } elseif ($r->nodeType === XMLReader::END_ELEMENT) {
                if ($r->name === 'rPh') {
                    $inPhonetic = false;
                } elseif ($r->name === 'si') {
                    $out[] = $current;
                    $inSi = false;
                }
            }
        }
        $r->close();

        return $out;
    }

    private static function readSheet(string $path, string $sheetPath, array $shared): array
    {
        $r = new XMLReader();
        $r->open('zip://' . $path . '#' . $sheetPath, null, LIBXML_NONET);

        $rows = [];
        $rowNo = 0;
        $cells = [];
        $maxCol = 0;

        $col = 0;
        $type = '';
        $value = '';
        $inCell = false;
        $inInline = false;

        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT) {
                switch ($r->name) {
                    case 'row':
                        $rowNo = (int) $r->getAttribute('r') ?: $rowNo + 1;
                        $cells = [];
                        break;
                    case 'c':
                        $ref = (string) $r->getAttribute('r');
                        $col = $ref !== '' ? self::colIndex($ref) : count($cells);
                        $type = (string) $r->getAttribute('t');
                        $value = '';
                        $inCell = ! $r->isEmptyElement;
                        break;
                    case 'is':
                        $inInline = true;
                        break;
                    case 't':
                        if ($inInline && ! $r->isEmptyElement) {
                            $value .= $r->readString();
                        }
                        break;
                    case 'v':
                        if ($inCell && ! $r->isEmptyElement) {
                            $value = $r->readString();
                        }
                        break;
                }
            } elseif ($r->nodeType === XMLReader::END_ELEMENT) {
                switch ($r->name) {
                    case 'is':
                        $inInline = false;
                        break;
                    case 'c':
                        if ($inCell && $col < self::MAX_COLS) {
                            $cells[$col] = self::cellValue($type, $value, $shared);
                            $maxCol = max($maxCol, $col + 1);
                        }
                        $inCell = false;
                        break;
                    case 'row':
                        $rows[] = ['row' => $rowNo, 'cells' => $cells];
                        if (count($rows) > self::MAX_ROWS + 1) {
                            $r->close();
                            throw new RuntimeException('عدد الصفوف أكبر من الحد المسموح (' . self::MAX_ROWS . ').');
                        }
                        break;
                }
            }
        }
        $r->close();

        return self::finalize($rows, $maxCol);
    }

    private static function cellValue(string $type, string $value, array $shared): string
    {
        switch ($type) {
            case 's':
                return $shared[(int) $value] ?? '';
            case 'b':
                return $value === '1' ? '1' : '0';
            case 'e':
                return '';
            case 'str':
            case 'inlineStr':
                return $value;
            default:
                return self::numberToString($value);
        }
    }

    /** يحوّل 9.00000001E8 أو 900000001.0 إلى 900000001 (مهم لأرقام الهوية). */
    public static function numberToString(string $v): string
    {
        $v = trim($v);
        if ($v === '' || ! is_numeric($v)) {
            return $v;
        }
        if (! preg_match('/[.eE]/', $v)) {
            return $v;
        }
        $f = (float) $v;
        if (floor($f) == $f && abs($f) < 1e15) {
            return sprintf('%.0f', $f);
        }

        return rtrim(rtrim(sprintf('%.10F', $f), '0'), '.');
    }

    private static function colIndex(string $ref): int
    {
        $letters = preg_replace('/[^A-Za-z]/', '', $ref);
        $n = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return max(0, $n - 1);
    }

    // ------------------------------------------------------------------- csv

    private static function readCsv(string $path): array
    {
        $raw = (string) file_get_contents($path);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        if (! mb_check_encoding($raw, 'UTF-8')) {
            // ملفات CSV العربية من Excel القديم غالباً بترميز Windows-1256
            $converted = function_exists('iconv') ? @iconv('Windows-1256', 'UTF-8//IGNORE', $raw) : false;
            if ($converted === false || $converted === '') {
                throw new RuntimeException('ترميز ملف CSV غير مدعوم. احفظه بترميز UTF-8 أو ارفعه بصيغة xlsx.');
            }
            $raw = $converted;
        }

        $firstLine = strtok($raw, "\n") ?: '';
        $best = ',';
        $bestCount = -1;
        foreach ([',', ';', "\t"] as $d) {
            $c = substr_count($firstLine, $d);
            if ($c > $bestCount) {
                $bestCount = $c;
                $best = $d;
            }
        }

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $raw);
        rewind($fh);

        $rows = [];
        $maxCol = 0;
        $n = 0;
        while (($line = fgetcsv($fh, 0, $best, '"', '\\')) !== false) {
            $n++;
            if ($line === [null]) {
                continue;
            }
            $cells = array_map(fn ($x) => (string) $x, array_slice($line, 0, self::MAX_COLS));
            $maxCol = max($maxCol, count($cells));
            $rows[] = ['row' => $n, 'cells' => $cells];
            if (count($rows) > self::MAX_ROWS + 1) {
                fclose($fh);
                throw new RuntimeException('عدد الصفوف أكبر من الحد المسموح (' . self::MAX_ROWS . ').');
            }
        }
        fclose($fh);

        return self::finalize($rows, $maxCol);
    }

    // ----------------------------------------------------------------- common

    /** يوحّد طول الصفوف، ينظّف النصوص، ويحذف الصفوف الفارغة. */
    private static function finalize(array $rows, int $maxCol): array
    {
        $out = [];
        foreach ($rows as $row) {
            $cells = [];
            $any = false;
            for ($i = 0; $i < $maxCol; $i++) {
                $v = self::clean($row['cells'][$i] ?? '');
                if ($v !== '') {
                    $any = true;
                }
                $cells[] = $v;
            }
            if ($any) {
                $out[] = ['row' => $row['row'], 'cells' => $cells];
            }
        }

        return $out;
    }

    private static function clean(string $v): string
    {
        $v = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{FEFF}]/u', '', $v) ?? $v;
        $v = preg_replace('/[\s\x{00A0}]+/u', ' ', $v) ?? $v;

        return trim($v);
    }
}
