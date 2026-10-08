<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DynamicFamiliesExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * @param array $rows     مصفوفة صفوف (كل صف = مصفوفة قيم مرتبة)
     * @param array $headings عناوين الأعمدة بنفس الترتيب
     */
    public function __construct(
        private array $rows,
        private array $headings,
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        $sheet->getRowDimension(1)->setRowHeight(22);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F5C4F'],
                ],
            ],
        ];
    }
}
