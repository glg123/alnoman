<?php

namespace App\Services\Import;

use RuntimeException;
use ZipArchive;

/**
 * كاتب xlsx بسيط بدون مكتبات: عدة أوراق، صف عناوين بخط عريض، اتجاه RTL.
 * كل القيم تُكتب كنصوص (حتى لا يخرّب Excel أرقام الهوية).
 *
 * $sheets = [ 'اسم الورقة' => [ ['عنوان1','عنوان2'], ['قيمة','قيمة'], ... ] ]
 * الصف الأول بكل ورقة يُعتبر صف العناوين.
 */
class SimpleXlsxWriter
{
    public static function write(string $path, array $sheets): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('تعذّر إنشاء ملف Excel.');
        }

        $names = array_keys($sheets);
        $n = count($names);

        $overrides = '';
        $sheetTags = '';
        $relTags = '';
        for ($i = 1; $i <= $n; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheetTags .= '<sheet name="' . self::esc(self::sheetName($names[$i - 1], $i)) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $relTags .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $relTags .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetTags . '</sheets></workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $relTags . '</Relationships>');

        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="49" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/></cellXfs>'
            . '</styleSheet>');

        $i = 0;
        foreach ($sheets as $rows) {
            $i++;
            $zip->addFromString('xl/worksheets/sheet' . $i . '.xml', self::sheetXml($rows));
        }

        $zip->close();
    }

    private static function sheetXml(array $rows): string
    {
        $maxCols = 1;
        foreach ($rows as $r) {
            $maxCols = max($maxCols, count($r));
        }

        $cols = '<cols>';
        for ($c = 1; $c <= $maxCols; $c++) {
            $cols .= '<col min="' . $c . '" max="' . $c . '" width="24" customWidth="1"/>';
        }
        $cols .= '</cols>';

        $data = '';
        $rn = 0;
        foreach ($rows as $r) {
            $rn++;
            $style = $rn === 1 ? 1 : 0;
            $data .= '<row r="' . $rn . '">';
            $c = 0;
            foreach (array_values($r) as $v) {
                $c++;
                $data .= '<c r="' . self::colName($c) . $rn . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
                    . self::esc((string) $v) . '</t></is></c>';
            }
            $data .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews>'
            . $cols . '<sheetData>' . $data . '</sheetData></worksheet>';
    }

    private static function colName(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }

    private static function sheetName(string $name, int $i): string
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $name);
        $name = mb_substr(trim($name), 0, 31);

        return $name !== '' ? $name : 'Sheet' . $i;
    }

    private static function esc(string $v): string
    {
        $v = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $v) ?? '';

        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
