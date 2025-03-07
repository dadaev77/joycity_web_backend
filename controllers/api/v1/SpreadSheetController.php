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

class SpreadSheetController extends V1Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['upload-spreadsheet'] = ['POST'];
        
        // Добавляем CORS фильтр для API
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['POST'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
            ],
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
}