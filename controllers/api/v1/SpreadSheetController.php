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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use yii\web\UploadedFile;
use app\models\Order;
use app\models\Product;
use app\models\User;
use app\models\TypeDelivery;
use app\services\TranslationService;
use Yii;


class SpreadSheetController extends V1Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['download-excel'] = ['get'];
        $behaviors['verbFilter']['actions']['upload-excel'] = ['post'];
        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/spread-sheet/download-excel",
     *     summary="Скачать шаблон Excel для загрузки заявок",
     *     @OA\Response(
     *         response=200,
     *         description="Файл шаблона Excel"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
     *     )
     * )
     */
    public function actionDownloadExcel()
    {
        try {
            $templatePath = Yii::getAlias('@app/data/templates/order_template.xlsx');

            if (!file_exists($templatePath)) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Шаблон не найден'
                ]);
            }

            return Yii::$app->response->sendFile(
                $templatePath,
                'order_template.xlsx',
                [
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'inline' => false
                ]
            );

        } catch (\Exception $e) {
            Yii::error("Ошибка при передаче файла: " . $e->getMessage());
            return $this->asJson([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/spread-sheet/upload-excel",
     *     summary="Загрузить Excel файл с заявками",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Excel файл с заявками"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Файл успешно обработан"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка в данных"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
     *     )
     * )
     */
    public function actionUploadExcel()
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '256M');

            if (empty($_FILES['file'])) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Файл не был загружен'
                ]);
            }

            $uploadedFile = $_FILES['file'];
            $user = User::getIdentity();

            try {
                $reader = IOFactory::createReaderForFile($uploadedFile['tmp_name']);
                $reader->setReadDataOnly(false);

                $spreadsheet = $reader->load($uploadedFile['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();

                $headers = [];
                foreach ($worksheet->getRowIterator(1, 1) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $value = $cell->getValue();
                        $headers[] = $value !== null ? trim($value) : '';
                    }
                }

                $createdOrders = [];
                $errors = [];
                $modelFields = require Yii::getAlias('@app/config/modelFields.php');
                $orderFields = $modelFields['Order']['fields'];

                // Начинаем с 4-й строки, так как первые три - заголовки, описания и примеры
                for ($rowIndex = 4; $rowIndex <= $worksheet->getHighestRow(); $rowIndex++) {
                    $rowData = [];
                    $hasData = false;

                    foreach ($worksheet->getRowIterator($rowIndex, $rowIndex) as $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);

                        foreach ($cellIterator as $cell) {
                            $column = $cell->getColumn();
                            $headerIndex = Coordinate::columnIndexFromString($column) - 1;
                            $header = isset($headers[$headerIndex]) ? $headers[$headerIndex] : '';

                            $value = $cell->getCalculatedValue();
                            if ($value !== null) {
                                $hasData = true;
                                $rowData[$header] = $value;
                            }
                        }
                    }

                    if ($hasData) {
                        // Создаем новую заявку
                        $order = new Order();
                        $order->status = Order::STATUS_CREATED;
                        $order->created_by = $user->id;
                        $order->created_at = date('Y-m-d H:i:s');

                        // Получаем конфигурацию полей из modelFields.php
                        $modelFields = require Yii::getAlias('@app/config/modelFields.php');
                        $orderFields = $modelFields['Order']['fields'];

                        // Проходим по каждому полю из конфигурации
                        foreach ($orderFields as $field => $config) {
                            // Проверяем все возможные алиасы поля
                            foreach ($config['aliases'] as $alias) {
                                if (isset($rowData[$alias])) {
                                    $value = $rowData[$alias];
                                    
                                    // Валидация значения если есть валидатор
                                    if (isset($config['validate'])) {
                                        $error = $config['validate']($value, $config);
                                        if ($error !== null) {
                                            $errors[] = "Строка {$rowIndex}: {$error}";
                                            continue 2;
                                        }
                                    }

                                    $order->$field = $value;
                                    break;
                                }
                            }
                        }

                        // Добавляем переводы через сервис
                        if (isset($rowData['name']) && isset($rowData['description'])) {
                            $translation = TranslationService::translateProductAttributes(
                                $rowData['name'], 
                                $rowData['description']
                            );
                            
                            if ($translation && isset($translation->result)) {
                                foreach ($translation->result as $lang => $values) {
                                    $order->{"product_name_$lang"} = $values['name'];
                                    $order->{"product_description_$lang"} = $values['description'];
                                }
                            }
                        }

                        // Устанавливаем значения по умолчанию
                        $order->currency = 'RUB';
                        $order->is_deleted = 0;
                        $order->total_quantity = 0;
                        $order->waybill_isset = 0;
                        $order->client_waybill_isset = 0;
                        $order->delivery_days_expected = 0;
                        $order->delivery_delay_days = 0;

                        // Добавим отладочную информацию
                        Yii::debug([
                            'row_data' => $rowData,
                            'order_data' => $order->attributes,
                            'headers' => $headers
                        ], 'excel_import');

                        // Сохраняем заявку
                        if ($order->save()) {
                            $createdOrders[] = $order->id;
                        } else {
                            $errors[] = "Строка {$rowIndex}: " . json_encode($order->getErrors());
                        }
                    }
                }

                return $this->asJson([
                    'success' => true,
                    'message' => 'Файл успешно обработан',
                    'created_orders' => $createdOrders,
                    'errors' => $errors,
                    'total_created' => count($createdOrders),
                    'total_errors' => count($errors)
                ]);

            } catch (\Throwable $e) {
                \Yii::error('Excel reading error: ' . $e->getMessage());
                \Yii::error('Stack trace: ' . $e->getTraceAsString());

                return $this->asJson([
                    'success' => false,
                    'message' => 'Ошибка при чтении Excel файла',
                    'error' => $e->getMessage(),
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile()
                ]);
            }
        } catch (\Throwable $e) {
            \Yii::error('Fatal error: ' . $e->getMessage());
            return $this->asJson([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера',
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Размер файла превышает upload_max_filesize';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Размер файла превышает MAX_FILE_SIZE';
            case UPLOAD_ERR_PARTIAL:
                return 'Файл был загружен частично';
            case UPLOAD_ERR_NO_FILE:
                return 'Файл не был загружен';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Отсутствует временная папка';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Не удалось записать файл на диск';
            case UPLOAD_ERR_EXTENSION:
                return 'PHP-расширение остановило загрузку файла';
            default:
                return 'Неизвестная ошибка при загрузке файла';
        }
    }
}