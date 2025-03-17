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
        $behaviors['verbFilter']['actions']['download-test-excel'] = ['get'];
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
     * @OA\Get(
     *     path="/api/v1/spread-sheet/download-test-excel",
     *     summary="Скачать тестовый Excel файл для загрузки заказов",
     *     @OA\Response(
     *         response=200,
     *         description="Тестовый файл Excel"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
     *     )
     * )
     */
    public function actionDownloadTestExcel()
    {
        try {
            // Создаем новый Excel-файл
            $spreadsheet = new Spreadsheet();

            // Заполняем первый лист данными заказов
            $sheet1 = $spreadsheet->setActiveSheetIndex(0);
            $sheet1->setTitle('Лист1');
            $sheet1->fromArray([
                ['Фото', 'Название товара', 'Категория товара', 'Подкатегория', 'Описание товара', 'Желаемое количество товара, шт',
                 'Желаемая стоимость за единицу товара, Р', 'Тип доставки', 'Тип пункта доставки', 'Адрес пункта доставки',
                 'Тип упаковки', 'Количество упаковок, шт', 'Глубокая инспекция'],
                ['https://goods-photos.static1-sima-land.com/items/4043645/0/1600.jpg?v=1573812851', 'Блузка', 'Женщинам',
                 'Блузки и рубашки', 'красивая', 5, 500, 'Медленное авто', 'Фулфилмент', 'Апаринки вл9', 'Мешок + скотч', 1, 'да']
            ], null, 'A1');

            // Создаем второй лист с категориями товаров
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Категории');
            $sheet2->fromArray([
                ['Женщинам', 'Обувь', 'Детям', 'Мужчинам', 'Дом', 'Красота', 'Аксессуары', 'Электроника', 'Игрушки', 'Мебель',
                 'Бытовая техника', 'Зоотовары', 'Спорт', 'Автотовары', 'Книги', 'Ювелирные изделия', 'Для ремонта', 'Сад и дача',
                 'Здоровье', 'Канцтовары']
            ], null, 'A1');
            
            // Создаем третий лист с подкатегориями
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('Подкатегории');
            $sheet3->fromArray([
                ['Блузки и рубашки', 'Детская', 'Для девочек', 'Брюки', 'Ванная', 'Аксессуары', 'Аксессуары для одежды',
                 'Автоэлектроника и навигация', 'Электротранспорт и аксессуары', 'Бескаркасная мебель', 'Климатическая техника',
                 'Для кошек', 'Фитнес и тренажеры', 'Шины и диски колесные', 'Художественная литература', 'Кольца',
                 'Колеровка краски', 'Растения, семена и грунты', 'Бассейны', 'Анатомические модели']
            ], null, 'A1');
            
            // Создаем четвертый лист с типами доставки
            $sheet4 = $spreadsheet->createSheet();
            $sheet4->setTitle('Типы доставки');
            $sheet4->fromArray([
                ['Медленное авто', 'Быстрое авто', 'Авиа', 'Морем', 'Железная дорога']
            ], null, 'A1');
            
            // Создаем пятый лист с типами пунктов доставки
            $sheet5 = $spreadsheet->createSheet();
            $sheet5->setTitle('Пункты доставки');
            $sheet5->fromArray([
                ['Фулфилмент', 'Склад', 'Магазин', 'Пункт выдачи']
            ], null, 'A1');
            
            // Создаем шестой лист с адресами пунктов доставки
            $sheet6 = $spreadsheet->createSheet();
            $sheet6->setTitle('Адреса');
            $sheet6->fromArray([
                ['Апаринки вл9', 'Москва, ул. Ленина 10', 'Санкт-Петербург, пр. Невский 20', 'Екатеринбург, ул. Мира 15']
            ], null, 'A1');
            
            // Создаем седьмой лист с типами упаковки
            $sheet7 = $spreadsheet->createSheet();
            $sheet7->setTitle('Типы упаковки');
            $sheet7->fromArray([
                ['Мешок + скотч', 'Коробка', 'Пакет', 'Пленка']
            ], null, 'A1');
            
            // Создаем восьмой лист с вариантами глубокой инспекции
            $sheet8 = $spreadsheet->createSheet();
            $sheet8->setTitle('Инспекция');
            $sheet8->fromArray([
                ['да', 'нет']
            ], null, 'A1');
            
            // Добавляем еще несколько примеров заказов на первый лист
            $sheet1->fromArray([
                ['https://example.com/image1.jpg', 'Футболка мужская', 'Мужчинам', 'Футболки', 
                 'Качественная хлопковая футболка для мужчин. Подходит для повседневной носки.', 
                 500, 800, 'Быстрое авто', 'Склад', 'Москва, ул. Ленина 10', 'Коробка', 50, 'нет'],
                ['https://example.com/image2.jpg', 'Джинсы женские', 'Женщинам', 'Джинсы', 
                 'Стильные джинсы для женщин. Удобный крой, высокое качество пошива.', 
                 300, 1500, 'Медленное авто', 'Фулфилмент', 'Санкт-Петербург, пр. Невский 20', 'Пакет', 30, 'да'],
                ['https://example.com/image3.jpg', 'Куртка зимняя детская', 'Детям', 'Верхняя одежда', 
                 'Теплая зимняя куртка для детей. Водонепроницаемый материал, утеплитель высокого качества.', 
                 200, 3000, 'Быстрое авто', 'Склад', 'Екатеринбург, ул. Мира 15', 'Коробка', 20, 'да']
            ], null, 'A3');
            
            // Форматирование первого листа
            foreach (range('A', 'M') as $column) {
                $sheet1->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Выделяем заголовки жирным
            $sheet1->getStyle('A1:M1')->getFont()->setBold(true);
            
            // Добавляем выпадающие списки для соответствующих столбцов
            
            // Категория товара (столбец C)
            $this->addDropdownList($sheet1, 'C', 2, 100, '=Категории!$A$1:$T$1');
            
            // Подкатегория (столбец D)
            $this->addDropdownList($sheet1, 'D', 2, 100, '=Подкатегории!$A$1:$T$1');
            
            // Тип доставки (столбец H)
            $this->addDropdownList($sheet1, 'H', 2, 100, '=\'Типы доставки\'!$A$1:$E$1');
            
            // Тип пункта доставки (столбец I)
            $this->addDropdownList($sheet1, 'I', 2, 100, '=\'Пункты доставки\'!$A$1:$D$1');
            
            // Адрес пункта доставки (столбец J)
            $this->addDropdownList($sheet1, 'J', 2, 100, '=Адреса!$A$1:$D$1');
            
            // Тип упаковки (столбец K)
            $this->addDropdownList($sheet1, 'K', 2, 100, '=\'Типы упаковки\'!$A$1:$D$1');
            
            // Глубокая инспекция (столбец M)
            $this->addDropdownList($sheet1, 'M', 2, 100, '=Инспекция!$A$1:$B$1');
            
            // Возвращаемся к первому листу
            $spreadsheet->setActiveSheetIndex(0);
            
            // Создаем временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'test_order_data_');
            $writer = new WriterXlsx($spreadsheet);
            $writer->save($tempFile);
            
            $response = Yii::$app->response;
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="test_order_data.xlsx"');
            
            return $response->sendFile($tempFile, 'test_order_data.xlsx', ['inline' => false]);
            
        } catch (\Exception $e) {
            Yii::error("Ошибка при создании тестового Excel файла: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->asJson([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Добавляет выпадающий список в указанный столбец
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Лист Excel
     * @param string $column Буква столбца
     * @param int $startRow Начальная строка
     * @param int $endRow Конечная строка
     * @param string $formula Формула для списка
     */
    private function addDropdownList($sheet, $column, $startRow, $endRow, $formula)
    {
        $validation = $sheet->getCell($column . $startRow)->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($formula);
        
        // Копируем валидацию на все ячейки в столбце
        for ($i = $startRow; $i <= $endRow; $i++) {
            $sheet->getCell($column . $i)->setDataValidation(clone $validation);
        }
    }
}