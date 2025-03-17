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
            $sheet1->setTitle('Заказы');
            $sheet1->fromArray([
                ['Фото', 'Название товара', 'Категория товара', 'Подкатегория', 'Описание товара', 'Желаемое количество товара, шт',
                 'Желаемая стоимость за единицу товара, Р', 'Тип доставки', 'Тип пункта доставки', 'Адрес пункта доставки',
                 'Тип упаковки', 'Количество упаковок, шт', 'Глубокая инспекция'],
            ], null, 'A1');
            
            // Добавляем примеры заказов
            $sheet1->fromArray([
                ['https://example.com/image1.jpg', 'Футболка мужская', 'Мужчинам', 'Футболки', 
                 'Качественная хлопковая футболка для мужчин. Подходит для повседневной носки.', 
                 500, 800, 'Быстрое авто', 'Склад', 'Москва, ул. Ленина 10', 'Коробка', 50, 'нет'],
                ['https://example.com/image2.jpg', 'Джинсы женские', 'Женщинам', 'Джинсы', 
                 'Стильные джинсы для женщин. Удобный крой, высокое качество пошива.', 
                 300, 1500, 'Медленное авто', 'Фулфилмент', 'Санкт-Петербург, пр. Невский 20', 'Пакет', 30, 'да'],
                ['https://example.com/image3.jpg', 'Куртка зимняя детская', 'Детям', 'Верхняя одежда', 
                 'Теплая зимняя куртка для детей. Водонепроницаемый материал, утеплитель высокого качества.', 
                 200, 3000, 'Быстрое авто', 'Склад', 'Екатеринбург, ул. Мира 15', 'Коробка', 20, 'да'],
                ['https://example.com/image4.jpg', 'Кроссовки спортивные', 'Обувь', 'Мужская', 
                 'Удобные спортивные кроссовки для бега и повседневной носки.', 
                 100, 2500, 'Быстрое авто', 'Склад', 'Москва, ул. Ленина 10', 'Коробка', 10, 'нет'],
                ['https://example.com/image5.jpg', 'Светильник настольный', 'Дом', 'Освещение', 
                 'Современный настольный светильник с регулируемой яркостью. Идеально подходит для рабочего стола.', 
                 50, 1200, 'Медленное авто', 'Пункт выдачи', 'Санкт-Петербург, пр. Невский 20', 'Коробка', 5, 'да'],
                ['https://example.com/image6.jpg', 'Набор косметики', 'Красота', 'Подарочные наборы', 
                 'Подарочный набор косметики премиум-класса. Включает средства для ухода за лицом и телом.', 
                 30, 3500, 'Быстрое авто', 'Магазин', 'Москва, ул. Ленина 10', 'Коробка', 3, 'да'],
                ['https://example.com/image7.jpg', 'Сумка женская', 'Аксессуары', 'Сумки и рюкзаки', 
                 'Стильная женская сумка из натуральной кожи. Вместительная, с несколькими отделениями.', 
                 80, 4500, 'Медленное авто', 'Фулфилмент', 'Санкт-Петербург, пр. Невский 20', 'Пакет', 8, 'да'],
                ['https://example.com/image8.jpg', 'Смартфон', 'Электроника', 'Смартфоны и телефоны', 
                 'Современный смартфон с мощным процессором и качественной камерой. Поддержка 5G.', 
                 40, 25000, 'Быстрое авто', 'Магазин', 'Москва, ул. Ленина 10', 'Коробка', 4, 'да'],
                ['https://example.com/image9.jpg', 'Конструктор LEGO', 'Игрушки', 'Конструкторы LEGO', 
                 'Набор LEGO для детей от 6 лет. Развивает мелкую моторику и творческое мышление.', 
                 60, 3500, 'Медленное авто', 'Пункт выдачи', 'Екатеринбург, ул. Мира 15', 'Коробка', 6, 'нет'],
                ['https://example.com/image10.jpg', 'Диван угловой', 'Мебель', 'Диваны и кресла', 
                 'Удобный угловой диван с механизмом трансформации. Обивка из качественной ткани.', 
                 10, 35000, 'Медленное авто', 'Склад', 'Москва, ул. Ленина 10', 'Упаковка', 1, 'да']
            ], null, 'A2');
            
            // Создаем второй лист со справочниками
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Справочники');
            
            // Категории товаров - все указанные категории
            $sheet2->setCellValue('A1', 'Категории');
            $categories = [
                'Женщинам', 'Обувь', 'Детям', 'Мужчинам', 'Дом', 
                'Красота', 'Аксессуары', 'Электроника', 'Игрушки', 'Мебель',
                'Бытовая техника', 'Зоотовары', 'Спорт', 'Автотовары', 'Книги', 
                'Ювелирные изделия', 'Для ремонта', 'Сад и дача', 'Здоровье', 'Канцтовары'
            ];
            
            // Добавляем категории в столбец A
            foreach ($categories as $index => $category) {
                $sheet2->setCellValue('A' . ($index + 2), $category);
            }
            
            // Подкатегории для Женщинам
            $sheet2->setCellValue('B1', 'Подкатегории_Женщинам');
            $subcategoriesWomen = [
                'Блузки и рубашки', 'Верхняя одежда', 'Джинсы', 'Костюмы',
                'Пиджаки, жилеты и жакеты', 'Толстовки, свитшоты и худи', 'Футболки и топы',
                'Шорты', 'Белье', 'Будущие мамы', 'Для невысоких', 'Офис',
                'Религиозная', 'Спецодежда и СИЗы', 'Брюки', 'Джемперы, водолазки и кардиганы',
                'Комбинезоны', 'Лонгсливы', 'Платья и сарафаны', 'Туники', 'Халаты',
                'Юбки', 'Большие размеры', 'Для высоких', 'Одежда для дома', 'Пляжная мода',
                'Подарки женщинам'
            ];
            
            foreach ($subcategoriesWomen as $index => $subcategory) {
                $sheet2->setCellValue('B' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Мужчинам
            $sheet2->setCellValue('C1', 'Подкатегории_Мужчинам');
            $subcategoriesMen = [
                'Брюки', 'Верхняя одежда', 'Джемперы, водолазки и кардиганы', 'Джинсы',
                'Комбинезоны и полукомбинезоны', 'Костюмы', 'Лонгсливы', 'Майки',
                'Пиджаки, жилеты и жакеты', 'Пижамы', 'Рубашки', 'Толстовки, свитшоты и худи',
                'Футболки', 'Футболки-поло', 'Халаты', 'Шорты', 'Белье', 'Большие размеры',
                'Для высоких', 'Для невысоких', 'Одежда для дома', 'Офис', 'Пляжная одежда',
                'Религиозная', 'Свадьба', 'Спецодежда и СИЗы', 'Подарки мужчинам'
            ];
            
            foreach ($subcategoriesMen as $index => $subcategory) {
                $sheet2->setCellValue('C' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Детям
            $sheet2->setCellValue('D1', 'Подкатегории_Детям');
            $subcategoriesChildren = [
                'Для девочек', 'Для мальчиков', 'Для новорожденных', 'Верхняя одежда',
                'Школьная форма', 'Детская', 'Брюки', 'Джинсы', 'Комбинезоны', 'Футболки',
                'Платья и сарафаны', 'Толстовки, свитшоты и худи', 'Шорты', 'Белье',
                'Одежда для дома', 'Конструкторы', 'Прогулки и путешествия', 'Религиозная одежда',
                'Подгузники', 'Детская электроника', 'Детский транспорт', 'Детское питание',
                'Товары для малыша', 'Подарки детям'
            ];
            
            foreach ($subcategoriesChildren as $index => $subcategory) {
                $sheet2->setCellValue('D' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Обувь
            $sheet2->setCellValue('J1', 'Подкатегории_Обувь');
            $subcategoriesShoes = [
                'Детская',
                'Женская',
                'Спецобувь',
                'Для новорожденных',
                'Мужская',
                'Аксессуары для обуви'
            ];
            
            foreach ($subcategoriesShoes as $index => $subcategory) {
                $sheet2->setCellValue('J' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Дом
            $sheet2->setCellValue('K1', 'Подкатегории_Дом');
            $subcategoriesHome = [
                'Ванная',
                'Кухня',
                'Предметы интерьера',
                'Спальня',
                'Гостиная',
                'Детская',
                'Досуг и творчество',
                'Все для праздника',
                'Зеркала',
                'Коврики',
                'Кронштейны',
                'Освещение',
                'Для курения',
                'Отдых на природе',
                'Парфюмерия для дома',
                'Прихожая',
                'Религия, эзотерика',
                'Сувенирная продукция',
                'Хозяйственные товары',
                'Хранение вещей',
                'Цветы, вазы и кашпо',
                'Шторы'
            ];
            
            foreach ($subcategoriesHome as $index => $subcategory) {
                $sheet2->setCellValue('K' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Красота
            $sheet2->setCellValue('L1', 'Подкатегории_Красота');
            $subcategoriesBeauty = [
                'Аксессуары',
                'Волосы',
                'Аптечная косметика',
                'Детская декоративная косметика',
                'Для загара',
                'Для мам и малышей',
                'Израильская косметика',
                'Инструменты для парикмахеров',
                'Корейские бренды',
                'Косметические аппараты и аксессуары',
                'Крымская косметика',
                'Макияж',
                'Мужская линия',
                'Наборы для ухода',
                'Ногти',
                'Органическая косметика',
                'Парфюмерия',
                'Подарочные наборы',
                'Профессиональная косметика',
                'Средства личной гигиены',
                'Гигиена полости рта'
            ];
            
            foreach ($subcategoriesBeauty as $index => $subcategory) {
                $sheet2->setCellValue('L' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Аксессуары
            $sheet2->setCellValue('M1', 'Подкатегории_Аксессуары');
            $subcategoriesAccessories = [
                'Аксессуары для одежды',
                'Бижутерия',
                'Ювелирные изделия',
                'Веера',
                'Галстуки и бабочки',
                'Головные уборы',
                'Зеркальца',
                'Зонты',
                'Кошельки и кредитницы',
                'Маски для сна',
                'Носовые платки',
                'Очки и футляры',
                'Перчатки и варежки',
                'Платки и шарфы',
                'Религиозные',
                'Ремни и пояса',
                'Сумки и рюкзаки',
                'Часы и ремешки',
                'Чемоданы и защита багажа'
            ];
            
            foreach ($subcategoriesAccessories as $index => $subcategory) {
                $sheet2->setCellValue('M' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Электроника
            $sheet2->setCellValue('N1', 'Подкатегории_Электроника');
            $subcategoriesElectronics = [
                'Автоэлектроника и навигация',
                'Гарнитуры и наушники',
                'Детская электроника',
                'Игровые консоли и игры',
                'Кабели и зарядные устройства',
                'Музыка и видео',
                'Ноутбуки и компьютеры',
                'Офисная техника',
                'Развлечения и гаджеты',
                'Сетевое оборудование',
                'Системы безопасности',
                'Смартфоны и телефоны',
                'Смарт-часы и браслеты',
                'Солнечные электростанции и комплектующие',
                'ТВ, Аудио, Фото, Видео техника',
                'Торговое оборудование',
                'Умный дом'
            ];
            
            foreach ($subcategoriesElectronics as $index => $subcategory) {
                $sheet2->setCellValue('N' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Игрушки
            $sheet2->setCellValue('O1', 'Подкатегории_Игрушки');
            $subcategoriesToys = [
                'Электротранспорт и аксессуары',
                'Антистресс',
                'Для малышей',
                'Для песочницы',
                'Игровые комплексы',
                'Игровые наборы',
                'Игрушечное оружие и аксессуары',
                'Игрушечный транспорт',
                'Игрушки для ванной',
                'Интерактивные',
                'Кинетический песок',
                'Конструкторы',
                'Конструкторы LEGO',
                'Куклы и аксессуары',
                'Музыкальные',
                'Мыльные пузыри',
                'Мягкие игрушки',
                'Наборы для опытов',
                'Настольные игры',
                'Радиоуправляемые',
                'Развивающие игрушки',
                'Сборные модели'
            ];
            
            foreach ($subcategoriesToys as $index => $subcategory) {
                $sheet2->setCellValue('O' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Мебель
            $sheet2->setCellValue('P1', 'Подкатегории_Мебель');
            $subcategoriesFurniture = [
                'Бескаркасная мебель',
                'Детская мебель',
                'Диваны и кресла',
                'Матрасы',
                'Столы и стулья',
                'Компьютерная и геймерская мебель',
                'Мебель для гостиной',
                'Мебель для кухни',
                'Мебель для прихожей',
                'Мебель для спальни',
                'Гардеробная мебель',
                'Офисная мебель',
                'Садовая мебель',
                'Торговая мебель',
                'Торговое оборудование',
                'Мебель для салонов красоты',
                'Зеркала',
                'Мебельная фурнитура'
            ];
            
            foreach ($subcategoriesFurniture as $index => $subcategory) {
                $sheet2->setCellValue('P' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Бытовая техника
            $sheet2->setCellValue('Q1', 'Подкатегории_Бытовая_техника');
            $subcategoriesAppliances = [
                'Климатическая техника',
                'Красота и здоровье',
                'Садовая техника',
                'Техника для дома',
                'Техника для кухни',
                'Крупная бытовая техника'
            ];
            
            foreach ($subcategoriesAppliances as $index => $subcategory) {
                $sheet2->setCellValue('Q' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Зоотовары
            $sheet2->setCellValue('R1', 'Подкатегории_Зоотовары');
            $subcategoriesPets = [
                'Для кошек',
                'Для собак',
                'Для птиц',
                'Для грызунов и хорьков',
                'Для лошадей',
                'Аквариумистика',
                'Террариумистика',
                'Фермерство',
                'Корм и лакомства',
                'Аксессуары для кормления',
                'Лотки и наполнители',
                'Когтеточки и домики',
                'Транспортировка',
                'Амуниция и дрессировка',
                'Игрушки',
                'Груминг и уход',
                'Одежда',
                'Ветаптека',
                'Лекарственные препараты для животных'
            ];
            
            foreach ($subcategoriesPets as $index => $subcategory) {
                $sheet2->setCellValue('R' . ($index + 2), $subcategory);
            }
            
            // Типы доставки
            $sheet2->setCellValue('E1', 'Типы_доставки');
            $deliveryTypes = [
                'Медленное авто', 'Быстрое авто', 'Авиа', 'Морем', 'Железная дорога'
            ];
            
            foreach ($deliveryTypes as $index => $type) {
                $sheet2->setCellValue('E' . ($index + 2), $type);
            }
            
            // Типы пунктов доставки
            $sheet2->setCellValue('F1', 'Пункты_доставки');
            $deliveryPoints = [
                'Фулфилмент', 'Склад', 'Магазин', 'Пункт выдачи'
            ];
            
            foreach ($deliveryPoints as $index => $point) {
                $sheet2->setCellValue('F' . ($index + 2), $point);
            }
            
            // Адреса пунктов доставки
            $sheet2->setCellValue('G1', 'Адреса');
            $addresses = [
                'Апаринки вл9', 'Москва, ул. Ленина 10', 'Санкт-Петербург, пр. Невский 20', 
                'Екатеринбург, ул. Мира 15'
            ];
            
            foreach ($addresses as $index => $address) {
                $sheet2->setCellValue('G' . ($index + 2), $address);
            }
            
            // Типы упаковки
            $sheet2->setCellValue('H1', 'Типы_упаковки');
            $packagingTypes = [
                'Мешок + скотч', 'Коробка', 'Пакет', 'Пленка'
            ];
            
            foreach ($packagingTypes as $index => $type) {
                $sheet2->setCellValue('H' . ($index + 2), $type);
            }
            
            // Варианты глубокой инспекции
            $sheet2->setCellValue('I1', 'Инспекция');
            $inspectionOptions = [
                'да', 'нет'
            ];
            
            foreach ($inspectionOptions as $index => $option) {
                $sheet2->setCellValue('I' . ($index + 2), $option);
            }
            
            // Подкатегории для Спорт
            $sheet2->setCellValue('S1', 'Подкатегории_Спорт');
            $subcategoriesSport = [
                'Фитнес и тренажеры',
                'Велоспорт',
                'Йога и Пилатес',
                'Охота и рыбалка',
                'Самокаты, Ролики, Скейтборды',
                'Туризм, Походы',
                'Бег, Ходьба',
                'Командные виды спорта',
                'Водные виды спорта',
                'Зимние виды спорта',
                'Поддержка и восстановление',
                'Спортивное питание и косметика',
                'Бадминтон и Теннис',
                'Бильярд, Гольф, Дартс, Метание ножей',
                'Единоборства',
                'Конный спорт', 
                'Мотоспорт', 
                'Оборудование для сдачи нормативов',
                'Парусный спорт',
                'Скалолазание и Альпинизм',
                'Страйкбол и пейнтбол',
                'Танцы и Гимнастика',
                'Для детей',
                'Для женщин',
                'Для мужчин',
                'Спортивная обувь',
                'Товары для самообороны'
            ];
            
            foreach ($subcategoriesSport as $index => $subcategory) {
                $sheet2->setCellValue('S' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Автотовары
            $sheet2->setCellValue('T1', 'Подкатегории_Автотовары');
            $subcategoriesAuto = [
                'Шины и диски колесные', 
                'Запчасти на легковые автомобили', 
                'Масла и жидкости', 
                'Автокосметика и автохимия', 
                'Краски и грунтовки', 
                'Автоэлектроника и навигация', 
                'Аккумуляторы и сопутствующие товары', 
                'Аксессуары в салон и багажник', 
                'Коврики', 
                'Внешний тюнинг', 
                'Другие аксессуары и доп. оборудование', 
                'Инструменты', 
                'Мойки высокого давления и аксессуары', 
                'Мототовары', 
                'OFFroad', 
                'Запчасти на силовую технику', 
                'Запчасти для лодок и катеров'
            ];
            
            foreach ($subcategoriesAuto as $index => $subcategory) {
                $sheet2->setCellValue('T' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Книги
            $sheet2->setCellValue('U1', 'Подкатегории_Книги');
            $subcategoriesBooks = [
                'Художественная литература',
                'Комиксы и манга',
                'Книги для детей',
                'Воспитание и развитие ребенка',
                'Образование',
                'Самообразование и развитие',
                'Бизнес и менеджмент',
                'Хобби и досуг',
                'Астрология и эзотерика',
                'Дом, сад и огород',
                'Красота, здоровье и спорт',
                'Научно-популярная литература',
                'Интернет и технологии',
                'Литературоведение и публицистика',
                'Историческая и военная литература',
                'Философия',
                'Религия',
                'Политика и право',
                'Букинистика',
                'Книги на иностранных языках',
                'Плакаты',
                'Календари',
                'Коллекционные издания',
                'Репринтные издания',
                'Мультимедиа',
                'Аудиокниги',
                'Цифровые книги',
                'Цифровые аудиокниги'
            ];
            
            foreach ($subcategoriesBooks as $index => $subcategory) {
                $sheet2->setCellValue('U' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Ювелирные изделия
            $sheet2->setCellValue('V1', 'Подкатегории_Ювелирные_изделия');
            $subcategoriesJewelry = [
                'Кольца',
                'Серьги',
                'Браслеты',
                'Подвески и шармы',
                'Комплекты',
                'Колье, цепи, шнурки',
                'Броши',
                'Пирсинг',
                'Часы',
                'Зажимы, запонки, ремни',
                'Четки',
                'Сувениры и столовое серебро',
                'Украшения из золота',
                'Украшения из серебра',
                'Украшения из керамики',
                'Аксессуары для украшений'
            ];
            
            foreach ($subcategoriesJewelry as $index => $subcategory) {
                $sheet2->setCellValue('V' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Для ремонта
            $sheet2->setCellValue('W1', 'Подкатегории_Для_ремонта');
            $subcategoriesRepair = [
                'Колеровка краски',
                'Двери, окна и фурнитура',
                'Инструменты и оснастка',
                'Отделочные материалы',
                'Электрика',
                'Лакокрасочные материалы',
                'Сантехника, отопление и газоснабжение',
                'Вентиляция',
                'Крепеж',
                'Стройматериалы'
            ];
            
            foreach ($subcategoriesRepair as $index => $subcategory) {
                $sheet2->setCellValue('W' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Сад и дача
            $sheet2->setCellValue('X1', 'Подкатегории_Сад_и_дача');
            $subcategoriesGarden = [
                'Растения, семена и грунты',
                'Удобрения и уход за растениями',
                'Уличное и садовое освещение',
                'Инструменты для уборки снега и реагенты',
                'Снегоуборочные машины',
                'Теплицы, парники, укрывной материал',
                'Товары для бани и сауны',
                'Горшки, кашпо и подставки для растений',
                'Грили, мангалы и барбекю',
                'Садовая техника',
                'Садовые инструменты',
                'Мойки высокого давления и аксессуары',
                'Надувная мебель',
                'Товары для кемпинга, пикника и отдыха',
                'Биотуалеты, дачные умывальники и души',
                'Садовый декор',
                'Полив и водоснабжение',
                'Готовые строения и срубы',
                'Мебель для отдыха',
                'Защита от насекомых и грызунов'

            ];
            
            foreach ($subcategoriesGarden as $index => $subcategory) {
                $sheet2->setCellValue('X' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Здоровье
            $sheet2->setCellValue('Y1', 'Подкатегории_Здоровье');
            $subcategoriesHealth = [
                'Бассейны',
                'Аптека',
                'БАДы',
                'Дезинфекция, стерилизация и утилизация',
                'Ухо, горло, нос',
                'Комплексные пищевые добавки',
                'Контрацептивы и лубриканты',
                'Специализированное питание',
                'Маски защитные',
                'Медицинские изделия',
                'Медицинские приборы',
                'Оздоровление',
                'Оптика',
                'Ортопедия',
                'Реабилитация',
                'Сиропы и бальзамы',
                'Уход за полостью рта'
            ];
            
            foreach ($subcategoriesHealth as $index => $subcategory) {
                $sheet2->setCellValue('Y' . ($index + 2), $subcategory);
            }
            
            // Подкатегории для Канцтовары
            $sheet2->setCellValue('Z1', 'Подкатегории_Канцтовары');
            $subcategoriesStationery = [
                'Анатомические модели',
                'Бумажная продукция',
                'Карты и глобусы',
                'Офисные принадлежности',
                'Письменные принадлежности',
                'Рисование и лепка',
                'Счетный материал',
                'Торговые принадлежности',
                'Чертежные принадлежности'
            ];
            
            foreach ($subcategoriesStationery as $index => $subcategory) {
                $sheet2->setCellValue('Z' . ($index + 2), $subcategory);
            }
            
            // Форматирование первого листа
            foreach (range('A', 'M') as $column) {
                $sheet1->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Форматирование второго листа
            foreach (range('A', 'Z') as $column) {
                $sheet2->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Выделяем заголовки жирным
            $sheet1->getStyle('A1:M1')->getFont()->setBold(true);
            $sheet2->getStyle('A1:Z1')->getFont()->setBold(true);
            
            // Добавляем прямые выпадающие списки (без именованных диапазонов)
            
            // Категория товара (столбец C)
            $categoriesRange = 'Справочники!$A$2:$A$21';
            $this->addDropdownListDirect($sheet1, 'C', 2, 100, $categoriesRange);
            
            // Подкатегория (столбец D) - зависимый список
            // Для простоты используем прямые ссылки на диапазоны
            $subcategoriesWomenRange = 'Справочники!$B$2:$B$28';
            $subcategoriesMenRange = 'Справочники!$C$2:$C$28';
            $subcategoriesChildrenRange = 'Справочники!$D$2:$D$25';
            $subcategoriesShoesRange = 'Справочники!$J$2:$J$7'; // Диапазон для обуви
            $subcategoriesHomeRange = 'Справочники!$K$2:$K$23'; // Диапазон для дома
            $subcategoriesBeautyRange = 'Справочники!$L$2:$L$22'; // Диапазон для красоты
            $subcategoriesAccessoriesRange = 'Справочники!$M$2:$M$20'; // Диапазон для аксессуаров
            $subcategoriesElectronicsRange = 'Справочники!$N$2:$N$18'; // Диапазон для электроники
            $subcategoriesToysRange = 'Справочники!$O$2:$O$23'; // Диапазон для игрушек
            $subcategoriesFurnitureRange = 'Справочники!$P$2:$P$19'; // Диапазон для мебели
            $subcategoriesAppliancesRange = 'Справочники!$Q$2:$Q$7'; // Диапазон для бытовой техники
            $subcategoriesPetsRange = 'Справочники!$R$2:$R$20'; // Диапазон для зоотоваров
            $subcategoriesAutoRange = 'Справочники!$T$2:$T$18'; // Диапазон для автотоваров
            
            for ($row = 2; $row <= 100; $row++) {
                $validation = $sheet1->getCell('D' . $row)->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                
                // Используем IF для выбора нужного диапазона в зависимости от категории
                $formula = 'IF(C' . $row . '="Женщинам",' . $subcategoriesWomenRange . 
                          ',IF(C' . $row . '="Мужчинам",' . $subcategoriesMenRange . 
                          ',IF(C' . $row . '="Детям",' . $subcategoriesChildrenRange . 
                          ',IF(C' . $row . '="Обувь",' . $subcategoriesShoesRange . 
                          ',IF(C' . $row . '="Дом",' . $subcategoriesHomeRange . 
                          ',IF(C' . $row . '="Красота",' . $subcategoriesBeautyRange . 
                          ',IF(C' . $row . '="Аксессуары",' . $subcategoriesAccessoriesRange . 
                          ',IF(C' . $row . '="Электроника",' . $subcategoriesElectronicsRange . 
                          ',IF(C' . $row . '="Игрушки",' . $subcategoriesToysRange . 
                          ',IF(C' . $row . '="Мебель",' . $subcategoriesFurnitureRange . 
                          ',IF(C' . $row . '="Бытовая техника",' . $subcategoriesAppliancesRange . 
                          ',IF(C' . $row . '="Зоотовары",' . $subcategoriesPetsRange . 
                          ',IF(C' . $row . '="Спорт",' . 'Справочники!$S$2:$S$28' . 
                          ',IF(C' . $row . '="Автотовары",' . 'Справочники!$T$2:$T$18' . 
                          ',IF(C' . $row . '="Книги",' . 'Справочники!$U$2:$U$29' . 
                          ',IF(C' . $row . '="Ювелирные изделия",' . 'Справочники!$V$2:$V$17' . 
                          ',IF(C' . $row . '="Для ремонта",' . 'Справочники!$W$2:$W$11' . 
                          ',IF(C' . $row . '="Сад и дача",' . 'Справочники!$X$2:$X$21' . 
                          ',IF(C' . $row . '="Здоровье",' . 'Справочники!$Y$2:$Y$18' . 
                          ',IF(C' . $row . '="Канцтовары",' . 'Справочники!$Z$2:$Z$10' . 
                          ',"Выберите категорию"))))))))))))))))))))';
                $validation->setFormula1($formula);
            }
            
            // Тип доставки (столбец H)
            $this->addDropdownListDirect($sheet1, 'H', 2, 100, 'Справочники!$E$2:$E$6');
            
            // Тип пункта доставки (столбец I)
            $this->addDropdownListDirect($sheet1, 'I', 2, 100, 'Справочники!$F$2:$F$5');
            
            // Адрес пункта доставки (столбец J)
            $this->addDropdownListDirect($sheet1, 'J', 2, 100, 'Справочники!$G$2:$G$5');
            
            // Тип упаковки (столбец K)
            $this->addDropdownListDirect($sheet1, 'K', 2, 100, 'Справочники!$H$2:$H$5');
            
            // Глубокая инспекция (столбец M)
            $this->addDropdownListDirect($sheet1, 'M', 2, 100, 'Справочники!$I$2:$I$3');
            
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
     * Добавляет выпадающий список в указанный столбец с прямой ссылкой на диапазон
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Лист Excel
     * @param string $column Буква столбца
     * @param int $startRow Начальная строка
     * @param int $endRow Конечная строка
     * @param string $range Диапазон ячеек для списка
     */
    private function addDropdownListDirect($sheet, $column, $startRow, $endRow, $range)
    {
        for ($i = $startRow; $i <= $endRow; $i++) {
            $validation = $sheet->getCell($column . $i)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($range);
        }
    }
}