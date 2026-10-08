<?php

namespace App\Support;

class PdfArabicFont
{
    /**
     * يسجّل خط Amiri (المرفق داخل storage/fonts) مع dompdf حتى تنعرض الحروف
     * العربية بشكل صحيح (dompdf لا يشكّل الحروف العربية صح من DejaVu الافتراضي).
     * استدعِها قبل render()/output()/download() على كائن الـ PDF.
     */
    public static function register(\Dompdf\Dompdf $dompdf): void
    {
        $fontMetrics = $dompdf->getFontMetrics();

        $fontMetrics->registerFont(
            ['family' => 'Amiri', 'style' => 'normal', 'weight' => 'normal'],
            storage_path('fonts/Amiri-Regular.ttf')
        );

        $fontMetrics->registerFont(
            ['family' => 'Amiri', 'style' => 'normal', 'weight' => 'bold'],
            storage_path('fonts/Amiri-Bold.ttf')
        );

        $fontMetrics->registerFont(
            ['family' => 'Amiri', 'style' => 'italic', 'weight' => 'normal'],
            storage_path('fonts/Amiri-Italic.ttf')
        );

        $fontMetrics->registerFont(
            ['family' => 'Amiri', 'style' => 'italic', 'weight' => 'bold'],
            storage_path('fonts/Amiri-BoldItalic.ttf')
        );
    }
}
