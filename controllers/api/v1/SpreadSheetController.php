<?php

namespace app\controllers\api\v1;
use app\controllers\api\V1Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadsComments;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use app\models\Order;
use app\models\User;
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
            $filePath = Yii::getAlias('@app/data/templates/test_order_data.xlsx');

            if (!file_exists($filePath)) {
                throw new \Exception('Файл шаблона не найден');
            }

            return Yii::$app->response->sendFile($filePath, 'order_template.xlsx', [
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]);

        } catch (\Exception $e) {
            Yii::error("Ошибка при отправке шаблона Excel: " . $e->getMessage());
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
            // Увеличиваем лимиты для обработки больших файлов
            set_time_limit(300);
            ini_set('memory_limit', '256M');

            // Проверка наличия файла
            if (empty($_FILES['file'])) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Файл не был загружен'
                ]);
            }

            $uploadedFile = $_FILES['file'];
            
            // Добавляем логирование информации о файле
            Yii::debug("Uploaded file info:", 'upload');
            Yii::debug($uploadedFile, 'upload');

            // Проверка типа файла
            $allowedTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'text/csv'
            ];
            
            if (!in_array($uploadedFile['type'], $allowedTypes)) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Неверный формат файла. Поддерживаются только Excel файлы (.xlsx, .xls, .csv)'
                ]);
            }

            // Создание читателя и загрузка файла
            $reader = IOFactory::createReaderForFile($uploadedFile['tmp_name']);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($uploadedFile['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();

            // Логируем количество строк
            Yii::debug("Total rows in file: " . $worksheet->getHighestRow(), 'upload');

            // Начинаем транзакцию
            $transaction = Yii::$app->db->beginTransaction();

            $successCount = 0;
            $errors = [];
            $processedRows = 0;

            // Изменяем начальную строку с 4 на 2
            for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
                // Получаем название товара для проверки, не пустая ли строка
                $productName = trim($worksheet->getCell('B' . $row)->getValue());
                
                // Логируем данные каждой строки
                Yii::debug("Processing row $row. Product name: $productName", 'upload');
                
                if (empty($productName)) {
                    Yii::debug("Empty product name in row $row, skipping", 'upload');
                    continue;
                }

                $processedRows++;

                // Обрабатываем данные строки
                $result = $this->processOrderData($row, $worksheet);
                
                // Логируем результат обработки
                Yii::debug("Row $row processing result:", 'upload');
                Yii::debug($result, 'upload');
                
                if (!$result['success']) {
                    $errors[] = [
                        'row' => $row,
                        'errors' => $result['errors']
                    ];
                    continue;
                }

                // Создаем новый заказ
                $order = new Order();
                $order->load($result['data'], '');

                // Логируем данные заказа перед сохранением
                Yii::debug("Order data before save:", 'upload');
                Yii::debug($result['data'], 'upload');

                if (!$order->save()) {
                    Yii::error("Failed to save order in row $row:", 'upload');
                    Yii::error($order->getErrors(), 'upload');
                    
                    $errors[] = [
                        'row' => $row,
                        'errors' => $order->getFirstErrors()
                    ];
                    continue;
                }

                $successCount++;
            }

            if (empty($errors)) {
                $transaction->commit();
                return $this->asJson([
                    'success' => true,
                    'message' => "Успешно создано заказов: {$successCount}",
                    'debug_info' => [
                        'total_rows' => $worksheet->getHighestRow(),
                        'processed_rows' => $processedRows,
                        'success_count' => $successCount
                    ]
                ]);
            } else {
                $transaction->rollBack();
                return $this->asJson([
                    'success' => false,
                    'message' => 'Обнаружены ошибки при создании заказов',
                    'errors' => $errors,
                    'debug_info' => [
                        'total_rows' => $worksheet->getHighestRow(),
                        'processed_rows' => $processedRows,
                        'error_count' => count($errors)
                    ]
                ]);
            }

        } catch (\Throwable $e) {
            if (isset($transaction)) {
                $transaction->rollBack();
            }
            Yii::error('Excel upload error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return $this->asJson([
                'success' => false,
                'message' => 'Ошибка при обработке Excel файла',
                'error' => YII_DEBUG ? $e->getMessage() : 'Внутренняя ошибка сервера'
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
                 500, 800, 'Быстрое авто', 'Склад', 'Москва, Тестовый склад', 'Коробка', 50, 'нет'],
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
                'Подгузники', 'Детская электроника', 'Детское питание',
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
                'Москва, Тестовый склад',
                'кутузовское ш 12'
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

    private function getTypeDeliveryId($name)
    {
        if (empty($name)) {
            return null;
        }
        $name = trim($name);
        if (is_numeric($name)) {
            return (int)$name;
        }
        // Обновляем маппинг типов доставки с добавлением значений 8 и 9
        $types = [
            'Медленное авто' => 8,
            'медленное авто' => 8,
            'Быстрое авто' => 9,
            'быстрое авто' => 9,
            'Авиа' => 3,
            'авиа' => 3,
            'Морем' => 4,
            'морем' => 4,
            'Железная дорога' => 5,
            'железная дорога' => 5,
            '8' => 8,
            '9' => 9
        ];
        return $types[$name] ?? null;
    }

    private function getTypeDeliveryPointId($name)
    {
        // Входное логирование
        Yii::debug("=== getTypeDeliveryPointId ===", 'delivery_point_debug');
        Yii::debug("Raw input name: '" . $name . "'", 'delivery_point_debug');
        
        if (empty($name)) {
            Yii::debug("Empty delivery point name", 'delivery_point_debug');
            return null;
        }
        
        $name = trim($name);
        
        // Логируем имя после trim()
        Yii::debug("Trimmed name: '" . $name . "'", 'delivery_point_debug');
        
        if (is_numeric($name)) {
            $id = (int)$name;
            if ($id >= 1 && $id <= 4) {
                Yii::debug("Valid numeric ID: " . $id, 'delivery_point_debug');
                return $id;
            }
            Yii::debug("Invalid numeric ID: " . $id, 'delivery_point_debug');
            return null;
        }
        
        // Обновленный маппинг типов пунктов доставки
        $types = [
            'фулфилмент' => 1,
            'Фулфилмент' => 1,
            'ФУЛФИЛМЕНТ' => 1,
            'склад' => 2,
            'Склад' => 2,
            'СКЛАД' => 2,
            'магазин' => 3,
            'Магазин' => 3,
            'МАГАЗИН' => 3,
            'пункт выдачи' => 4,
            'Пункт выдачи' => 4,
            'ПУНКТ ВЫДАЧИ' => 4,
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4
        ];
        
        $normalizedName = mb_strtolower(trim($name), 'UTF-8');
        
        // Логируем нормализованное имя
        Yii::debug("Normalized name: '" . $normalizedName . "'", 'delivery_point_debug');
        
        if (isset($types[$normalizedName])) {
            $result = $types[$normalizedName];
            Yii::debug("Found in mapping, returning: " . $result, 'delivery_point_debug');
            return $result;
        }
        
        Yii::debug("Delivery point type not found in mapping", 'delivery_point_debug');
        return null;
    }

    private function getDeliveryPointAddressId($address)
    {
        // Входное логирование
        Yii::debug("=== getDeliveryPointAddressId ===", 'address_debug');
        Yii::debug("Raw input address: '" . $address . "'", 'address_debug');
        
        if (empty($address)) {
            Yii::debug("Empty address detected", 'address_debug');
            return null;
        }
        
        $address = trim($address);
        
        // Логируем адрес после trim()
        Yii::debug("Trimmed address: '" . $address . "'", 'address_debug');
        
        // Если это числовое значение
        if (is_numeric($address)) {
            $id = (int)$address;
            if ($id === 1 || $id === 2) {
                Yii::debug("Valid numeric ID: " . $id, 'address_debug');
                return $id;
            }
            Yii::debug("Invalid numeric ID: " . $id, 'address_debug');
            return null;
        }
        
        // Обновленный список адресов в соответствии с вашей базой данных
        $addresses = [
            // ID 1
            'москва, тестовый склад' => 1,
            'Москва, Тестовый склад' => 1,
            'МОСКВА, ТЕСТОВЫЙ СКЛАД' => 1,
            'москва тестовый склад' => 1,
            'Москва Тестовый склад' => 1,
            'тестовый склад' => 1,
            'Тестовый склад' => 1,
            
            // ID 2
            'кутузовское ш 12' => 2,
            'Кутузовское ш 12' => 2,
            'КУТУЗОВСКОЕ Ш 12' => 2,
            'кутузовское шоссе 12' => 2,
            'Кутузовское шоссе 12' => 2,
            'кутузовское ш. 12' => 2,
            'Кутузовское ш. 12' => 2,
            'кутузовское' => 2,
            'Кутузовское' => 2
        ];
        
        // Нормализуем входящий адрес
        $normalizedAddress = mb_strtolower(trim($address), 'UTF-8');
        
        // Удаляем множественные пробелы
        $normalizedAddress = preg_replace('/\s+/', ' ', $normalizedAddress);
        
        // Логируем нормализованный адрес
        Yii::debug("Normalized address: '" . $normalizedAddress . "'", 'address_debug');
        
        // Проверяем прямое совпадение
        if (isset($addresses[$normalizedAddress])) {
            $id = $addresses[$normalizedAddress];
            Yii::debug("Direct match found, returning: " . $id, 'address_debug');
            return $id;
        }
        
        // Проверяем частичное совпадение
        if (strpos($normalizedAddress, 'москва') !== false && strpos($normalizedAddress, 'тестовый') !== false && strpos($normalizedAddress, 'склад') !== false) {
            Yii::debug("Partial match for Moscow test warehouse, returning: 1", 'address_debug');
            return 1;
        }
        
        if (strpos($normalizedAddress, 'кутузовск') !== false && (strpos($normalizedAddress, '12') !== false || strpos($normalizedAddress, 'двенадцать') !== false)) {
            Yii::debug("Partial match for Kutuzovskoe 12, returning: 2", 'address_debug');
            return 2;
        }
        
        // Если адрес не найден
        Yii::debug("Address not found in mapping", 'address_debug');
        return null;
    }

    private function getTypePackagingId($name)
    {
        $types = [
            'Мешок + скотч' => 1,
            'Коробка' => 2,
            'Пакет' => 3,
            'Пленка' => 4,
            'Упаковка' => 5,
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5
        ];
        return $types[trim($name)] ?? null;
    }

    private function getSubcategoryId($category, $subcategory)
    {
        // Если подкатегория уже является числом, возвращаем его
        if (is_numeric($subcategory)) {
            return (int)$subcategory;
        }

        $subcategories = [
            'Женщинам' => [
                'Блузки и рубашки' => 1,
                'Верхняя одежда' => 2,
                'Джинсы' => 3,
                'Костюмы' => 4,
                'Пиджаки, жилеты и жакеты' => 5,
                'Толстовки, свитшоты и худи' => 6,
                'Футболки и топы' => 7,
                'Шорты' => 8,
                'Белье' => 9,
                'Будущие мамы' => 10,
                'Для невысоких' => 11,
                'Офис' => 12,
                'Религиозная' => 13,
                'Спецодежда и СИЗы' => 14,
                'Брюки' => 15,
                'Джемперы, водолазки и кардиганы' => 16,
                'Комбинезоны' => 17,
                'Лонгсливы' => 18,
                'Платья и сарафаны' => 19,
                'Туники' => 20,
                'Халаты' => 21,
                'Юбки' => 22,
                'Большие размеры' => 23,
                'Для высоких' => 24,
                'Одежда для дома' => 25,
                'Пляжная мода' => 26,
                'Подарки женщинам' => 27
            ],
            'Мужчинам' => [
                'Брюки' => 101,
                'Верхняя одежда' => 102,
                'Джемперы, водолазки и кардиганы' => 103,
                'Джинсы' => 104,
                'Комбинезоны и полукомбинезоны' => 105,
                'Костюмы' => 106,
                'Лонгсливы' => 107,
                'Майки' => 108,
                'Пиджаки, жилеты и жакеты' => 109,
                'Пижамы' => 110,
                'Рубашки' => 111,
                'Толстовки, свитшоты и худи' => 112,
                'Футболки' => 113,
                'Футболки-поло' => 114,
                'Халаты' => 115,
                'Шорты' => 116,
                'Белье' => 117,
                'Большие размеры' => 118,
                'Для высоких' => 119,
                'Для невысоких' => 120,
                'Одежда для дома' => 121,
                'Офис' => 122,
                'Пляжная одежда' => 123,
                'Религиозная' => 124,
                'Свадьба' => 125,
                'Спецодежда и СИЗы' => 126,
                'Подарки мужчинам' => 127
            ],
            'Детям' => [
                'Для девочек' => 201,
                'Для мальчиков' => 202,
                'Для новорожденных' => 203,
                'Верхняя одежда' => 204,
                'Школьная форма' => 205,
                'Детская' => 206,
                'Брюки' => 207,
                'Джинсы' => 208,
                'Комбинезоны' => 209,
                'Футболки' => 210,
                'Платья и сарафаны' => 211,
                'Толстовки, свитшоты и худи' => 212,
                'Шорты' => 213,
                'Белье' => 214,
                'Одежда для дома' => 215,
                'Конструкторы' => 216,
                'Прогулки и путешествия' => 217,
                'Религиозная одежда' => 218,
                'Подгузники' => 219,
                'Детская электроника' => 220,
                'Детское питание' => 222,
                'Товары для малыша' => 223,
                'Подарки детям' => 224
            ],
            'Обувь' => [
                'Детская' => 301,
                'Женская' => 302,
                'Спецобувь' => 303,
                'Для новорожденных' => 304,
                'Мужская' => 305,
                'Аксессуары для обуви' => 306
            ],
            'Дом' => [
                'Ванная' => 401,
                'Кухня' => 402,
                'Предметы интерьера' => 403,
                'Спальня' => 404,
                'Гостиная' => 405,
                'Детская' => 406,
                'Досуг и творчество' => 407,
                'Все для праздника' => 408,
                'Зеркала' => 409,
                'Коврики' => 410,
                'Кронштейны' => 411,
                'Освещение' => 412,
                'Для курения' => 413,
                'Отдых на природе' => 414,
                'Парфюмерия для дома' => 415,
                'Прихожая' => 416,
                'Религия, эзотерика' => 417,
                'Сувенирная продукция' => 418,
                'Хозяйственные товары' => 419,
                'Хранение вещей' => 420,
                'Цветы, вазы и кашпо' => 421,
                'Шторы' => 422
            ],
            'Красота' => [
                'Аксессуары' => 501,
                'Волосы' => 502,
                'Аптечная косметика' => 503,
                'Детская декоративная косметика' => 504,
                'Для загара' => 505,
                'Для мам и малышей' => 506,
                'Израильская косметика' => 507,
                'Инструменты для парикмахеров' => 508,
                'Корейские бренды' => 509,
                'Косметические аппараты и аксессуары' => 510,
                'Крымская косметика' => 511,
                'Макияж' => 512,
                'Мужская линия' => 513,
                'Наборы для ухода' => 514,
                'Ногти' => 515,
                'Органическая косметика' => 516,
                'Парфюмерия' => 517,
                'Подарочные наборы' => 518,
                'Профессиональная косметика' => 519,
                'Средства личной гигиены' => 520,
                'Гигиена полости рта' => 521
            ],
            'Аксессуары' => [
                'Аксессуары для одежды' => 601,
                'Бижутерия' => 602,
                'Ювелирные изделия' => 603,
                'Веера' => 604,
                'Галстуки и бабочки' => 605,
                'Головные уборы' => 606,
                'Зеркальца' => 607,
                'Зонты' => 608,
                'Кошельки и кредитницы' => 609,
                'Маски для сна' => 610,
                'Носовые платки' => 611,
                'Очки и футляры' => 612,
                'Перчатки и варежки' => 613,
                'Платки и шарфы' => 614,
                'Религиозные' => 615,
                'Ремни и пояса' => 616,
                'Сумки и рюкзаки' => 617,
                'Часы и ремешки' => 618,
                'Чемоданы и защита багажа' => 619
            ],
            'Электроника' => [
                'Автоэлектроника и навигация' => 701,
                'Гарнитуры и наушники' => 702,
                'Детская электроника' => 703,
                'Игровые консоли и игры' => 704,
                'Кабели и зарядные устройства' => 705,
                'Музыка и видео' => 706,
                'Ноутбуки и компьютеры' => 707,
                'Офисная техника' => 708,
                'Развлечения и гаджеты' => 709,
                'Сетевое оборудование' => 710,
                'Системы безопасности' => 711,
                'Смартфоны и телефоны' => 712,
                'Смарт-часы и браслеты' => 713,
                'Солнечные электростанции и комплектующие' => 714,
                'ТВ, Аудио, Фото, Видео техника' => 715,
                'Торговое оборудование' => 716,
                'Умный дом' => 717
            ],
            'Игрушки' => [
                'Электротранспорт и аксессуары' => 801,
                'Антистресс' => 802,
                'Для малышей' => 803,
                'Для песочницы' => 804,
                'Игровые комплексы' => 805,
                'Игровые наборы' => 806,
                'Игрушечное оружие и аксессуары' => 807,
                'Игрушечный транспорт' => 808,
                'Игрушки для ванной' => 809,
                'Интерактивные' => 810,
                'Кинетический песок' => 811,
                'Конструкторы' => 812,
                'Конструкторы LEGO' => 813,
                'Куклы и аксессуары' => 814,
                'Музыкальные' => 815,
                'Мыльные пузыри' => 816,
                'Мягкие игрушки' => 817,
                'Наборы для опытов' => 818,
                'Настольные игры' => 819,
                'Радиоуправляемые' => 820,
                'Развивающие игрушки' => 821,
                'Сборные модели' => 822
            ],
            'Мебель' => [
                'Бескаркасная мебель' => 901,
                'Детская мебель' => 902,
                'Диваны и кресла' => 903,
                'Матрасы' => 904,
                'Столы и стулья' => 905,
                'Компьютерная и геймерская мебель' => 906,
                'Мебель для гостиной' => 907,
                'Мебель для кухни' => 908,
                'Мебель для прихожей' => 909,
                'Мебель для спальни' => 910,
                'Гардеробная мебель' => 911,
                'Офисная мебель' => 912,
                'Садовая мебель' => 913,
                'Торговая мебель' => 914,
                'Торговое оборудование' => 915,
                'Мебель для салонов красоты' => 916,
                'Зеркала' => 917,
                'Мебельная фурнитура' => 918
            ]
        ];

        return $subcategories[$category][$subcategory] ?? null;
    }

    private function validateOrderData($data)
    {
        $errors = [];
        
        // Проверка обязательных полей
        if (empty($data['product_name_ru'])) {
            $errors['product_name_ru'] = 'Название товара обязательно для заполнения';
        }
        
        if (empty($data['expected_quantity']) || $data['expected_quantity'] < 1) {
            $errors['expected_quantity'] = 'Количество товара должно быть больше 0';
        }
        
        if (empty($data['expected_price_per_item']) || $data['expected_price_per_item'] < 0) {
            $errors['expected_price_per_item'] = 'Цена за единицу товара должна быть больше или равна 0';
        }
        
        if (empty($data['subcategory_id'])) {
            $errors['subcategory_id'] = 'Необходимо указать подкатегорию товара';
        }
        
        if (empty($data['type_delivery_id'])) {
            $errors['type_delivery_id'] = 'Необходимо указать тип доставки';
        }
        
        if (empty($data['type_delivery_point_id'])) {
            $errors['type_delivery_point_id'] = 'Необходимо указать тип пункта доставки';
        }
        
        // Временно отключаем проверку delivery_point_address_id
        /*if (empty($data['delivery_point_address_id'])) {
            $errors['delivery_point_address_id'] = 'Необходимо указать адрес пункта доставки';
        }*/
        
        if (empty($data['type_packaging_id'])) {
            $errors['type_packaging_id'] = 'Необходимо указать тип упаковки';
        }
        
        if (empty($data['expected_packaging_quantity']) || $data['expected_packaging_quantity'] < 1) {
            $errors['expected_packaging_quantity'] = 'Количество упаковок должно быть больше 0';
        }
        
        return $errors;
    }

    private function processOrderData($row, $worksheet)
    {
        // Получаем значения из Excel
        $productName = trim($worksheet->getCell('B' . $row)->getValue());
        $category = trim($worksheet->getCell('C' . $row)->getValue());
        $subcategory = trim($worksheet->getCell('D' . $row)->getValue());
        $description = trim($worksheet->getCell('E' . $row)->getValue());
        $quantity = (int)$worksheet->getCell('F' . $row)->getValue();
        $price = (float)$worksheet->getCell('G' . $row)->getValue();
        $deliveryType = trim($worksheet->getCell('H' . $row)->getValue());
        $deliveryPoint = trim($worksheet->getCell('I' . $row)->getValue());
        $address = trim($worksheet->getCell('J' . $row)->getValue());
        $packagingType = trim($worksheet->getCell('K' . $row)->getValue());
        $packagingQuantity = (int)$worksheet->getCell('L' . $row)->getValue();
        $deepInspection = strtolower($worksheet->getCell('M' . $row)->getValue()) === 'да';
        $photoUrl = trim($worksheet->getCell('A' . $row)->getValue());

        // Получаем ID для связанных таблиц
        $typeDeliveryId = $this->getTypeDeliveryId($deliveryType);
        $typeDeliveryPointId = $this->getTypeDeliveryPointId($deliveryPoint);
        $deliveryPointAddressId = $this->getDeliveryPointAddressId($address);
        $typePackagingId = $this->getTypePackagingId($packagingType);
        $subcategoryId = $this->getSubcategoryId($category, $subcategory);

        // Проверяем наличие всех необходимых ID
        $errors = [];
        if ($typeDeliveryId === null) {
            $errors['type_delivery_id'] = "Неверный тип доставки: {$deliveryType}";
        }
        if ($typeDeliveryPointId === null) {
            $errors['type_delivery_point_id'] = "Неверный тип пункта доставки: {$deliveryPoint}";
        }
        if ($deliveryPointAddressId === null) {
            $errors['delivery_point_address_id'] = "Неверный адрес пункта доставки: {$address}";
        }
        if ($typePackagingId === null) {
            $errors['type_packaging_id'] = "Неверный тип упаковки: {$packagingType}";
        }
        if ($subcategoryId === null) {
            $errors['subcategory_id'] = "Неверная подкатегория: {$subcategory} для категории {$category}";
        }

        // Добавляем отладочное логирование для входных данных
        Yii::debug("Row {$row} input data:", 'order');
        Yii::debug([
            'deliveryType' => $deliveryType,
            'deliveryPoint' => $deliveryPoint,
            'address' => $address,
            'category' => $category,
            'subcategory' => $subcategory
        ], 'order');

        // Добавляем отладочное логирование для конвертированных ID
        Yii::debug("Row {$row} converted IDs:", 'order');
        Yii::debug([
            'typeDeliveryId' => $typeDeliveryId,
            'typeDeliveryPointId' => $typeDeliveryPointId,
            'deliveryPointAddressId' => $deliveryPointAddressId,
            'typePackagingId' => $typePackagingId,
            'subcategoryId' => $subcategoryId
        ], 'order');

        // Добавляем подробное логирование
        Yii::debug("Row {$row} detailed data:", 'order');
        Yii::debug([
            'deliveryType' => $deliveryType,
            'deliveryPoint' => $deliveryPoint,
            'address' => $address,
            'category' => $category,
            'subcategory' => $subcategory,
            'raw_address' => $worksheet->getCell('J' . $row)->getValue(),
            'raw_delivery_point' => $worksheet->getCell('I' . $row)->getValue(),
            'converted_ids' => [
                'typeDeliveryId' => $typeDeliveryId,
                'typeDeliveryPointId' => $typeDeliveryPointId,
                'deliveryPointAddressId' => $deliveryPointAddressId,
                'typePackagingId' => $typePackagingId,
                'subcategoryId' => $subcategoryId
            ]
        ], 'order');

        // Формируем данные для создания заказа
        $orderData = [
            'product_name_ru' => $productName,
            'product_description_ru' => $description,
            'expected_quantity' => $quantity,
            'expected_price_per_item' => $price,
            'expected_packaging_quantity' => $packagingQuantity,
            'subcategory_id' => $subcategoryId,
            'type_packaging_id' => $typePackagingId,
            'type_delivery_id' => $typeDeliveryId,
            'type_delivery_point_id' => $typeDeliveryPointId,
            'delivery_point_address_id' => $deliveryPointAddressId, // Используем полученный ID адреса
            'is_need_deep_inspection' => $deepInspection ? 1 : 0,
            'link_tz' => $photoUrl,
            'created_by' => Yii::$app->user->id,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'created',
            'currency' => User::getIdentity()->settings->currency,
            'price_product' => 0,
            'price_inspection' => 0,
            'price_packaging' => 0,
            'price_fulfilment' => 0,
            'price_delivery' => 0,
            'total_quantity' => 0,
            'is_deleted' => 0,
            'waybill_isset' => 0,
            'client_waybill_isset' => 0,
            'delivery_days_expected' => 0,
            'delivery_delay_days' => 0
        ];

        // Валидация данных
        $errors = $this->validateOrderData($orderData);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Переводим название и описание на другие языки
        $translation = TranslationService::translateProductAttributes($productName, $description);
        $translations = $translation->result;

        foreach ($translations as $key => $value) {
            if ($key !== 'ru') {
                $orderData["product_name_$key"] = $value['name'];
                $orderData["product_description_$key"] = $value['description'];
            }
        }

        return [
            'success' => true,
            'data' => $orderData
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/spread-sheet/debug-excel",
     *     summary="Отладочный метод для просмотра данных Excel",
     *     @OA\Response(
     *         response=200,
     *         description="Подробные данные Excel файла с отладочной информацией"
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
    public function actionDebugExcel()
    {
        try {
            // Увеличиваем лимиты для обработки больших файлов
            set_time_limit(300);
            ini_set('memory_limit', '256M');

            if (empty($_FILES['file'])) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Файл не был загружен',
                    'error_code' => 'FILE_NOT_FOUND'
                ]);
            }

            $uploadedFile = $_FILES['file'];

            // Проверка типа файла
            $allowedTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'text/csv'
            ];
            
            if (!in_array($uploadedFile['type'], $allowedTypes)) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Неверный формат файла. Поддерживаются только Excel файлы (.xlsx, .xls, .csv)',
                    'error_code' => 'INVALID_FILE_TYPE',
                    'file_type' => $uploadedFile['type']
                ]);
            }

            $reader = IOFactory::createReaderForFile($uploadedFile['tmp_name']);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($uploadedFile['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();

            $debugData = [
                'file_info' => [
                    'name' => $uploadedFile['name'],
                    'size' => $uploadedFile['size'],
                    'type' => $uploadedFile['type'],
                    'sheet_name' => $worksheet->getTitle(),
                    'total_rows' => $worksheet->getHighestRow(),
                    'total_columns' => $worksheet->getHighestColumn()
                ],
                'rows_data' => [],
                'statistics' => [
                    'total_processed' => 0,
                    'empty_rows' => 0,
                    'valid_rows' => 0,
                    'invalid_rows' => 0,
                    'categories_count' => [],
                    'delivery_types_count' => [],
                    'packaging_types_count' => []
                ]
            ];

            // Обработка каждой строки
            for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {  // Изменено с 4 на 2
                $productName = trim($worksheet->getCell('B' . $row)->getValue());
                
                // Пропускаем пустые строки
                if (empty($productName)) {
                    $debugData['statistics']['empty_rows']++;
                    continue;
                }

                $debugData['statistics']['total_processed']++;

                // Получаем все значения из строки
                $category = trim($worksheet->getCell('C' . $row)->getValue());
                $subcategory = trim($worksheet->getCell('D' . $row)->getValue());
                $deliveryType = trim($worksheet->getCell('H' . $row)->getValue());
                $packagingType = trim($worksheet->getCell('K' . $row)->getValue());

                // Обновляем статистику
                $debugData['statistics']['categories_count'][$category] = 
                    ($debugData['statistics']['categories_count'][$category] ?? 0) + 1;
                $debugData['statistics']['delivery_types_count'][$deliveryType] = 
                    ($debugData['statistics']['delivery_types_count'][$deliveryType] ?? 0) + 1;
                $debugData['statistics']['packaging_types_count'][$packagingType] = 
                    ($debugData['statistics']['packaging_types_count'][$packagingType] ?? 0) + 1;

                // Получаем сконвертированные ID
                $typeDeliveryId = $this->getTypeDeliveryId($deliveryType);
                $typeDeliveryPointId = $this->getTypeDeliveryPointId(trim($worksheet->getCell('I' . $row)->getValue()));
                $deliveryPointAddressId = $this->getDeliveryPointAddressId(trim($worksheet->getCell('J' . $row)->getValue()));
                $typePackagingId = $this->getTypePackagingId($packagingType);
                $subcategoryId = $this->getSubcategoryId($category, $subcategory);

                // Проверяем валидность данных
                $isValid = $typeDeliveryId && $typeDeliveryPointId && $deliveryPointAddressId && 
                          $typePackagingId && $subcategoryId;

                if ($isValid) {
                    $debugData['statistics']['valid_rows']++;
                } else {
                    $debugData['statistics']['invalid_rows']++;
                }

                // Формируем детальные данные по строке
                $rowData = [
                    'row_number' => $row,
                    'is_valid' => $isValid,
                    'raw_data' => [
                        'photo_url' => trim($worksheet->getCell('A' . $row)->getValue()),
                        'product_name' => $productName,
                        'category' => $category,
                        'subcategory' => $subcategory,
                        'description' => trim($worksheet->getCell('E' . $row)->getValue()),
                        'quantity' => (int)$worksheet->getCell('F' . $row)->getValue(),
                        'price' => (float)$worksheet->getCell('G' . $row)->getValue(),
                        'delivery_type' => $deliveryType,
                        'delivery_point' => trim($worksheet->getCell('I' . $row)->getValue()),
                        'address' => trim($worksheet->getCell('J' . $row)->getValue()),
                        'packaging_type' => $packagingType,
                        'packaging_quantity' => (int)$worksheet->getCell('L' . $row)->getValue(),
                        'deep_inspection' => strtolower($worksheet->getCell('M' . $row)->getValue()) === 'да'
                    ],
                    'converted_ids' => [
                        'type_delivery_id' => $typeDeliveryId,
                        'type_delivery_point_id' => $typeDeliveryPointId,
                        'delivery_point_address_id' => $deliveryPointAddressId,
                        'type_packaging_id' => $typePackagingId,
                        'subcategory_id' => $subcategoryId
                    ],
                    'validation_errors' => []
                ];

                // Добавляем информацию об ошибках
                if (!$typeDeliveryId) $rowData['validation_errors'][] = 'Неверный тип доставки';
                if (!$typeDeliveryPointId) $rowData['validation_errors'][] = 'Неверный тип пункта доставки';
                if (!$deliveryPointAddressId) $rowData['validation_errors'][] = 'Неверный адрес доставки';
                if (!$typePackagingId) $rowData['validation_errors'][] = 'Неверный тип упаковки';
                if (!$subcategoryId) $rowData['validation_errors'][] = 'Неверная категория/подкатегория';

                $debugData['rows_data'][] = $rowData;
            }

            return $this->asJson([
                'success' => true,
                'debug_data' => $debugData
            ]);

        } catch (\Throwable $e) {
            Yii::error('Excel debug error: ' . $e->getMessage());
            return $this->asJson([
                'success' => false,
                'message' => 'Ошибка при обработке Excel файла',
                'error' => $e->getMessage(),
                'trace' => YII_DEBUG ? $e->getTraceAsString() : null
            ]);
        }
    }
}