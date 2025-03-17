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
        $behaviors['verbFilter']['actions']['generate-test-excel'] = ['get'];
        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/spread-sheet/download-excel",
     *     summary="Скачать шаблон Excel для загрузки заказов",
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
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Заголовки
            $headers = [
                'Фото',
                'Название товара',
                'Категория товара',
                'Подкатегория',
                'Описание товара',
                'Желаемое количество товара, шт',
                'Желаемая стоимость за единицу товара, Р',
                'Тип доставки',
                'Тип пункта доставки',
                'Адрес пункта доставки',
                'Тип упаковки',
                'Количество упаковок, шт',
                'Глубокая инспекция'
            ];
            
            // Подсказки
            $hints = [
                '(Добавьте сюда фотографии вашего товара)',
                '(Например Брюки женские)',
                'Выберите из списка',
                'Выберите из списка',
                '(Добавьте описание товара минимум 20 символов)',
                '(от 1 до 100 000)',
                '(от 1 до 1 000 000)',
                'Выберите из списка',
                'Выберите из списка',
                'Выберите из списка',
                'Выберите из списка',
                '(от 1 до 100 000)',
                'Выберите из списка'
            ];
            
            // Примеры
            $examples = [
                '',
                'Брюки женские',
                '1',
                '2',
                'Качественные брюки из натуральных материалов. Подходят для повседневной носки.',
                '1000',
                '1000',
                '1',
                '1',
                '1',
                '1',
                '100',
                '0'
            ];
            
            // Устанавливаем заголовки
            foreach ($headers as $index => $header) {
                $column = chr(65 + $index);
                $sheet->setCellValue($column . '1', $header);
                $sheet->getStyle($column . '1')->getFont()->setBold(true);
            }
            
            // Устанавливаем подсказки
            foreach ($hints as $index => $hint) {
                $column = chr(65 + $index);
                $sheet->setCellValue($column . '2', $hint);
                $sheet->getStyle($column . '2')->getFont()->setItalic(true);
            }
            
            // Устанавливаем примеры
            foreach ($examples as $index => $example) {
                $column = chr(65 + $index);
                $sheet->setCellValue($column . '3', $example);
            }
            
            // Форматирование
            foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Создаем временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'order_template_');
            $writer = new WriterXlsx($spreadsheet);
            $writer->save($tempFile);
            
            $response = Yii::$app->response;
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="order_template.xlsx"');
            $response->sendFile($tempFile);
            
            // Удаляем временный файл после отправки
            register_shutdown_function(function() use ($tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            });
            
            return $response;
        } catch (\Exception $e) {
            Yii::error("Ошибка при создании шаблона Excel: " . $e->getMessage());
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
            $modelFields = require Yii::getAlias('@app/config/modelFields.php');
            $orderFields = $modelFields['Order']['fields'];

            try {
                $reader = IOFactory::createReaderForFile($uploadedFile['tmp_name']);
                $reader->setReadDataOnly(true);

                $spreadsheet = $reader->load($uploadedFile['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();

                // Начинаем транзакцию
                $transaction = Yii::$app->db->beginTransaction();

                $successCount = 0;
                $errors = [];

                // Начинаем с 4-й строки (пропускаем заголовки, описания и примеры)
                for ($row = 4; $row <= $worksheet->getHighestRow(); $row++) {
                    // Получаем значения ячеек
                    $productName = trim($worksheet->getCell('B' . $row)->getValue());

                    // Проверяем, не пустая ли строка
                    if (empty($productName)) {
                        continue;
                    }

                    // Создаем новый заказ
                    $order = new Order();
                    $order->created_by = Yii::$app->user->id;
                    $order->created_at = date('Y-m-d H:i:s');
                    $order->status = Order::STATUS_CREATED;
                    $order->currency = User::getIdentity()->settings->currency;

                    // Читаем данные из Excel и заполняем модель
                    $order->product_name_ru = $productName;
                    $order->subcategory_id = (int)$worksheet->getCell('D' . $row)->getValue();
                    $order->product_description_ru = $worksheet->getCell('E' . $row)->getValue();
                    $order->expected_quantity = (int)$worksheet->getCell('F' . $row)->getValue();
                    $order->expected_price_per_item = (float)$worksheet->getCell('G' . $row)->getValue();
                    $order->type_delivery_id = (int)$worksheet->getCell('H' . $row)->getValue();
                    $order->type_delivery_point_id = (int)$worksheet->getCell('I' . $row)->getValue();
                    $order->delivery_point_address_id = (int)$worksheet->getCell('J' . $row)->getValue();
                    $order->type_packaging_id = (int)$worksheet->getCell('K' . $row)->getValue();
                    $order->expected_packaging_quantity = (int)$worksheet->getCell('L' . $row)->getValue();
                    $order->is_need_deep_inspection = (int)$worksheet->getCell('M' . $row)->getValue();

                    // Валидация полей
                    $validationErrors = [];
                    foreach ($orderFields as $field => $config) {
                        if (property_exists($order, $field) && isset($config['validate'])) {
                            $value = $order->$field;
                            $error = $config['validate']($value, $config);
                            if ($error !== null) {
                                $validationErrors[$field] = $error;
                            }
                        }
                    }

                    if (!empty($validationErrors)) {
                        $errors[] = [
                            'row' => $row,
                            'errors' => $validationErrors
                        ];
                        continue;
                    }

                    // Переводим название и описание на другие языки
                    $translation = TranslationService::translateProductAttributes($order->product_name_ru, $order->product_description_ru);
                    $translations = $translation->result;

                    foreach ($translations as $key => $value) {
                        if ($key !== 'ru') { // Русский уже установлен
                            $order->{"product_name_$key"} = $value['name'];
                            $order->{"product_description_$key"} = $value['description'];
                        }
                    }

                    if (!$order->save()) {
                        $errors[] = [
                            'row' => $row,
                            'errors' => $order->getFirstErrors()
                        ];
                        continue;
                    }

                    // Обработка фотографий (если есть)
                    // Для обработки фотографий потребуется дополнительная логика
                    // Можно реализовать через отдельную таблицу связей order_attachment

                    $successCount++;
                }

                if (empty($errors)) {
                    $transaction->commit();
                    return $this->asJson([
                        'success' => true,
                        'message' => "Успешно создано заказов: {$successCount}",
                    ]);
                } else {
                    $transaction->rollBack();
                    return $this->asJson([
                        'success' => false,
                        'message' => 'Обнаружены ошибки при создании заказов',
                        'errors' => $errors
                    ]);
                }

            } catch (\Throwable $e) {
                if (isset($transaction)) {
                    $transaction->rollBack();
                }
                Yii::error('Excel reading error: ' . $e->getMessage());

                return $this->asJson([
                    'success' => false,
                    'message' => 'Ошибка при обработке Excel файла',
                    'error' => $e->getMessage()
                ]);
            }
        } catch (\Throwable $e) {
            Yii::error('Fatal error: ' . $e->getMessage());
            return $this->asJson([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Временный метод для генерации тестового Excel файла
     * @return \yii\web\Response
     */
    public function actionGenerateTestExcel()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Заголовки
            $headers = [
                'Фото',
                'Название товара',
                'Категория товара',
                'Подкатегория',
                'Описание товара',
                'Желаемое количество товара, шт',
                'Желаемая стоимость за единицу товара, Р',
                'Тип доставки',
                'Тип пункта доставки',
                'Адрес пункта доставки',
                'Тип упаковки',
                'Количество упаковок, шт',
                'Глубокая инспекция'
            ];
            
            // Подсказки
            $hints = [
                '(Добавьте сюда фотографии вашего товара)',
                '(Например Брюки женские)',
                'Выберите из списка',
                'Выберите из списка',
                '(Добавьте описание товара минимум 20 символов)',
                '(от 1 до 100 000)',
                '(от 1 до 1 000 000)',
                'Выберите из списка',
                'Выберите из списка',
                'Выберите из списка',
                'Выберите из списка',
                '(от 1 до 100 000)',
                'Выберите из списка'
            ];
            
            // Примеры данных для тестирования
            $testData = [
                [
                    '', // Фото (пустое для тестирования)
                    'Футболка мужская хлопковая', // Название товара
                    '1', // Категория товара (ID)
                    '2', // Подкатегория (ID)
                    'Качественная хлопковая футболка для мужчин. Подходит для повседневной носки. Материал: 100% хлопок.', // Описание
                    '500', // Количество
                    '800', // Цена за единицу
                    '1', // Тип доставки (ID)
                    '1', // Тип пункта доставки (ID)
                    '1', // Адрес пункта доставки (ID)
                    '1', // Тип упаковки (ID)
                    '50', // Количество упаковок
                    '0' // Глубокая инспекция (0 - нет, 1 - да)
                ],
                [
                    '', 
                    'Джинсы женские', 
                    '1', 
                    '3', 
                    'Стильные джинсы для женщин. Удобный крой, высокое качество пошива. Состав: 95% хлопок, 5% эластан.', 
                    '300', 
                    '1500', 
                    '2', 
                    '2', 
                    '2', 
                    '2', 
                    '30', 
                    '1'
                ],
                [
                    '', 
                    'Куртка зимняя детская', 
                    '2', 
                    '4', 
                    'Теплая зимняя куртка для детей. Водонепроницаемый материал, утеплитель высокого качества. Подходит для температуры до -30°C.', 
                    '200', 
                    '3000', 
                    '1', 
                    '1', 
                    '3', 
                    '3', 
                    '20', 
                    '1'
                ]
            ];
            
            // Устанавливаем заголовки
            foreach ($headers as $index => $header) {
                $column = chr(65 + $index);
                $sheet->setCellValue($column . '1', $header);
                $sheet->getStyle($column . '1')->getFont()->setBold(true);
            }
            
            // Устанавливаем подсказки
            foreach ($hints as $index => $hint) {
                $column = chr(65 + $index);
                $sheet->setCellValue($column . '2', $hint);
                $sheet->getStyle($column . '2')->getFont()->setItalic(true);
            }
            
            // Добавляем тестовые данные
            foreach ($testData as $rowIndex => $rowData) {
                foreach ($rowData as $colIndex => $value) {
                    $column = chr(65 + $colIndex);
                    $sheet->setCellValue($column . ($rowIndex + 4), $value); // Начинаем с 4-й строки
                }
            }
            
            // Форматирование
            foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Создаем временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'test_order_data_');
            $writer = new WriterXlsx($spreadsheet);
            $writer->save($tempFile);
            
            $response = Yii::$app->response;
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="test_order_data.xlsx"');
            $response->sendFile($tempFile);
            
            // Удаляем временный файл после отправки
            register_shutdown_function(function() use ($tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            });
            
            return $response;
        } catch (\Exception $e) {
            Yii::error("Ошибка при создании тестового Excel файла: " . $e->getMessage());
            return $this->asJson([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера: ' . $e->getMessage()
            ]);
        }
    }
}