<?php

namespace app\controllers\api\v1;
use app\controllers\api\V1Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReadsComments;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls as WriterXls;
use PhpOffice\PhpSpreadsheet\Writer\Csv as WriterCsv;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadSheetController extends V1Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Определяем разрешенные действия и их HTTP методы
        $behaviors['verbFilter'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'upload-spreadsheet' => ['POST'],
                'export' => ['GET']
            ]
        ];
        
        // Настраиваем CORS для поддержки всех необходимых методов
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
                'Access-Control-Expose-Headers' => [
                    'Content-Disposition',
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ]
        ];
        
        return $behaviors;
    }

        // Метод для загрузки файла Excel и его обработки
        // ссылка /api/v1/spread-sheet/upload-spreadsheet [POST ]
        // параметры:
        // - file: файл Excel
    public function actionUploadSpreadsheet()
    {
        // Проверяем Content-Type запроса
        if (strpos(\Yii::$app->request->getContentType(), 'multipart/form-data') === false) {
            return $this->asJson([
                'success' => false,
                'message' => 'Неверный Content-Type. Ожидается multipart/form-data'
            ]);
        }
        
        $file = \yii\web\UploadedFile::getInstanceByName('file');
        
        if (!$file) {
            return $this->asJson([
                'success' => false,
                'message' => 'Файл не был загружен'
            ]);
        }

        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        if (!in_array($file->extension, $allowedExtensions)) {
            return $this->asJson([
                'success' => false,
                'message' => 'Неподдерживаемый формат файла'
            ]);
        }

        try {
            $reader = IOFactory::createReaderForFile($file->tempName);
            
            // Добавляем дополнительные настройки для reader
            if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
                $reader->setInputEncoding('UTF-8');
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
                $reader->setSheetIndex(0);
            }
            
            $spreadsheet = $reader->load($file->tempName);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            // Получаем заголовки столбцов из первой строки
            $headers = [];
            foreach ($worksheet->getRowIterator(1, 1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $headers[] = $cell->getValue();
                }
            }
            
            // Проверяем валидность полей
            $modelType = \Yii::$app->request->post('modelType', 'Order');
            $validationResult = $this->validateFields($headers, $modelType);
            
            if (!$validationResult['isValid']) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Ошибка валидации полей',
                    'errors' => $validationResult['errors']
                ]);
            }

            // Обработка данных с учетом маппинга
            $data = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                    $cellValue = $worksheet->getCell($column . $row)->getValue();
                    // Используем маппинг для правильного именования полей
                    $fieldName = $validationResult['headerMapping'][$header];
                    $rowData[$fieldName] = $cellValue;
                }
                $data[] = $rowData;
            }

            return $this->asJson([
                'success' => true,
                'message' => 'Файл успешно обработан',
                'data' => $data,
                'meta' => [
                    'total_rows' => count($data),
                    'file_name' => $file->name,
                    'file_type' => $file->type,
                    'model_type' => $modelType
                ]
            ]);

        } catch (\Exception $e) {
            \Yii::error('Ошибка при обработке файла: ' . $e->getMessage(), 'spreadsheet');
            return $this->asJson([
                'success' => false,
                'message' => 'Ошибка при обработке файла: ' . $e->getMessage(),
                'error_details' => YII_DEBUG ? $e->getTraceAsString() : null
            ]);
        }
    }

    private function validateFields($headers, $modelType)
    {
        $configFields = $this->getFields($modelType);
        if (empty($configFields)) {
            return [
                'isValid' => false,
                'errors' => ["Неподдерживаемый тип модели: {$modelType}"]
            ];
        }

        $errors = [];
        $requiredFields = [];
        $headerMapping = []; // Маппинг заголовков на реальные имена полей

        // Создаем маппинг заголовков
        foreach ($headers as $header) {
            $foundField = false;
            foreach ($configFields['fields'] as $fieldName => $fieldConfig) {
                // Приводим к нижнему регистру для сравнения
                if (strtolower($fieldName) === strtolower($header)) {
                    $headerMapping[$header] = $fieldName;
                    $foundField = true;
                    break;
                }
                if (isset($fieldConfig['aliases'])) {
                    // Проверяем алиасы регистронезависимо
                    $lowerAliases = array_map('strtolower', $fieldConfig['aliases']);
                    if (in_array(strtolower($header), $lowerAliases)) {
                        $headerMapping[$header] = $fieldName;
                        $foundField = true;
                        break;
                    }
                }
            }
            if (!$foundField) {
                $errors[] = "Неизвестное поле: {$header}";
            }
        }

        // Проверяем обязательные поля
        foreach ($configFields['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['required']) {
                $found = false;
                foreach ($headerMapping as $header => $mappedField) {
                    if ($mappedField === $fieldName) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $errors[] = "Отсутствует обязательное поле: {$fieldName}";
                }
            }
        }

        // Проверяем типы данных
        foreach ($headerMapping as $header => $fieldName) {
            $fieldConfig = $configFields['fields'][$fieldName];
            if (!$this->validateFieldType($fieldConfig['type'])) {
                $errors[] = "Неподдерживаемый тип данных для поля {$header}: {$fieldConfig['type']}";
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'headerMapping' => $headerMapping // Возвращаем маппинг для использования при обработке данных
        ];
    }

    private function validateFieldType($type)
    {
        $allowedTypes = [
            'string',
            'integer',
            'float',
            'email',
            'date',
            'datetime'  // Добавляем поддержку datetime
        ];
        return in_array($type, $allowedTypes);
    }

    private function getFields($modelType = 'Order')
    {
        $config = require \Yii::getAlias('@app/config/modelFields.php');
        return isset($config[$modelType]) ? $config[$modelType] : [];
    }

    /**
     * Экспорт данных в файл
     * GET /api/v1/spread-sheet/export
     * @param string $modelType тип модели (Order/Product)
     * @param string $format формат файла (xlsx/xls/csv)
     * @return \yii\web\Response
     */
    public function actionExport()
    {
        try {
            // Получаем параметры из запроса
            $modelType = \Yii::$app->request->get('modelType', 'Order');
            $format = \Yii::$app->request->get('format', 'xlsx');

            // Проверяем допустимые значения
            if (!in_array($modelType, ['Order', 'Product'])) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Неверный тип модели. Допустимые значения: Order, Product'
                ]);
            }

            if (!in_array($format, ['xlsx', 'xls', 'csv'])) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Неверный формат файла. Допустимые значения: xlsx, xls, csv'
                ]);
            }

            // Получаем данные из базы
            $data = $this->getModelData($modelType);
            if (empty($data)) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Нет данных для экспорта'
                ]);
            }

            // Создаем новый документ
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Получаем конфигурацию полей
            $configFields = $this->getFields($modelType);
            $headers = $this->getHeadersForExport($configFields['fields']);

            // Устанавливаем заголовки
            $column = 1;
            foreach ($headers as $header => $description) {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . '1';
                $sheet->setCellValue($cellCoordinate, $description);
                $column++;
            }

            // Стилизация заголовков
            $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ]);

            // Заполняем данными
            $row = 2;
            foreach ($data as $item) {
                $column = 1;
                foreach ($headers as $field => $description) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row;
                    $value = $item[$field] ?? '';
                    $sheet->setCellValue($cellCoordinate, $value);
                    $column++;
                }
                $row++;
            }

            // Автоматическая ширина столбцов
            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Создаем writer в зависимости от формата
            switch ($format) {
                case 'xlsx':
                    $writer = new WriterXlsx($spreadsheet);
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    break;
                case 'xls':
                    $writer = new WriterXls($spreadsheet);
                    $contentType = 'application/vnd.ms-excel';
                    break;
                case 'csv':
                    $writer = new WriterCsv($spreadsheet);
                    $contentType = 'text/csv';
                    $writer->setDelimiter(',');
                    $writer->setEnclosure('"');
                    $writer->setLineEnding("\r\n");
                    $writer->setSheetIndex(0);
                    $writer->setUseBOM(true);
                    break;
                default:
                    throw new \Exception("Неподдерживаемый формат файла: {$format}");
            }

            // Формируем имя файла
            $fileName = $modelType . '_export_' . date('Y-m-d_H-i-s') . '.' . $format;

            // Устанавливаем заголовки для скачивания
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');

            // Отправляем файл
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            \Yii::error('Ошибка при экспорте: ' . $e->getMessage());
            return $this->asJson([
                'success' => false,
                'message' => 'Ошибка при экспорте файла: ' . $e->getMessage(),
                'error_details' => YII_DEBUG ? $e->getTraceAsString() : null
            ]);
        }
    }

    /**
     * Получение данных модели для экспорта
     */
    private function getModelData($modelType)
    {
        switch ($modelType) {
            case 'Order':
                return \app\models\Order::find()
                    ->where(['is_deleted' => 0])
                    ->asArray()
                    ->all();
            case 'Product':
                return \app\models\Product::find()
                    ->where(['is_deleted' => 0])
                    ->asArray()
                    ->all();
            default:
                throw new \Exception("Неподдерживаемый тип модели");
        }
    }

    /**
     * Получение заголовков для экспорта
     */
    private function getHeadersForExport($fields)
    {
        $headers = [];
        foreach ($fields as $field => $config) {
            if (isset($config['description'])) {
                $headers[$field] = $config['description'];
            } else {
                $headers[$field] = $field;
            }
        }
        return $headers;
    }
}