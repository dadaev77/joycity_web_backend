<?php

namespace app\services;

use Mpdf\Mpdf;

class PdfService
{
    /**
     * Генерация накладной в PDF
     *
     * @param array $data Данные для накладной
     * @param string $template Путь к HTML-шаблону
     * @param string $orientation Ориентация
     * @return string Путь к сгенерированному PDF
     * @throws \Mpdf\MpdfException
     */
    public static function generateInvoice(array $data, string $template, string $orientation = 'vertical'): string
    {
        /**
         * Ориентация
         * L - горизонтальная
         * P - вертикальная
         */

        $orientation = match ($orientation) {
            'vertical' => 'P',
            'horizontal' => 'L',
        };
        // создание директории для хранения накладных
        $directory = \Yii::getAlias('@runtime') . '/invoices';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        // рендеринг шаблона с использованием данных
        $html = self::renderTemplate($template, $data);
        $mpdf = new Mpdf([
            'orientation' => $orientation,
            'format' => 'A4',
            'tempDir' => \Yii::getAlias('@runtime/mpdf') // Указываем временную директорию
        ]);
        $mpdf->WriteHTML($html);
        // генерация файла
        $outputPath = \Yii::getAlias('@runtime') . '/invoices/invoice_' . time() . '.pdf';
        $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);
        return $outputPath;
    }

    /**
     * Рендеринг шаблона с использованием данных
     *
     * @param string $template Путь к HTML-шаблону
     * @param array $data Данные для замены
     * @return string
     */
    protected static function renderTemplate(string $template, array $data): string
    {
        ob_start();
        extract($data);
        include($template);
        return ob_get_clean();
    }
}
