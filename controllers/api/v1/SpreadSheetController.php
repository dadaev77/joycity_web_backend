<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use app\services\order\OrderExcelService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use Yii;

class SpreadSheetController extends V1Controller
{
    private $orderExcelService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->orderExcelService = new OrderExcelService();
    }

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
            Yii::info('Запрос на скачивание шаблона Excel');
            $filePath = Yii::getAlias('@app/data/templates/test_order_data.xlsx');

            if (!file_exists($filePath)) {
                Yii::error('Файл шаблона не найден: ' . $filePath);
                return $this->asJson([
                    'success' => false,
                    'message' => 'Файл шаблона не найден'
                ]);
            }

            Yii::info('Отправка файла шаблона');
            return Yii::$app->response->sendFile($filePath, 'order_template.xlsx', [
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]);
        } catch (\Exception $e) {
            Yii::error("Ошибка при отправке шаблона Excel: " . $e->getMessage() . "\n" . $e->getTraceAsString());
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
            Yii::info('Получен запрос на загрузку Excel файла');
            
            if (empty($_FILES['file'])) {
                Yii::warning('Файл не был загружен');
                return $this->asJson([
                    'success' => false,
                    'message' => 'Файл не был загружен'
                ]);
            }

            Yii::info('Начало обработки файла: ' . $_FILES['file']['name']);
            $result = $this->orderExcelService->processExcelFile($_FILES['file']);

            if (!$result['success']) {
                Yii::warning('Ошибки при обработке файла: ' . json_encode($result));
                return $this->asJson([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? [],
                    'debug_info' => $result['debug_info'] ?? null
                ]);
            }

            Yii::info('Файл успешно обработан: ' . json_encode($result));
            return $this->asJson($result);
        } catch (\Throwable $e) {
            Yii::error('Критическая ошибка при обработке Excel файла: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
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
            Yii::info('Запрос на создание тестового Excel файла');
            
            // Создаем новый Excel-файл
            $spreadsheet = new Spreadsheet();

            // Заполняем первый лист данными заказов
            $sheet1 = $spreadsheet->setActiveSheetIndex(0);
            $sheet1->setTitle('Заказы');
            $sheet1->fromArray([
                [
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
                ],
            ], null, 'A1');

            // Добавляем примеры заказов
            $sheet1->fromArray([
                [
                    'https://example.com/image1.jpg',
                    'Футболка мужская',
                    'Мужчинам',
                    'Футболки',
                    'Качественная хлопковая футболка для мужчин. Подходит для повседневной носки.',
                    500,
                    800,
                    'Быстрое авто',
                    'Склад',
                    'Москва, Тестовый склад',
                    'Коробка',
                    50,
                    'нет'
                ],
            ], null, 'A2');

            // Создаем второй лист со справочниками
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Справочники');

            // Получаем данные из базы данных
            $categories = Category::find()->select(['ru_name'])->column();
            $deliveryTypes = TypeDelivery::find()->select(['ru_name'])->column();
            $deliveryPoints = TypeDeliveryPoint::find()->select(['ru_name'])->column();
            $addresses = DeliveryPointAddress::find()->select(['ru_name'])->column();
            $packagingTypes = TypePackaging::find()->select(['ru_name'])->column();

            // Заполняем справочники
            $sheet2->setCellValue('A1', 'Категории');
            foreach ($categories as $index => $category) {
                $sheet2->setCellValue('A' . ($index + 2), $category);
            }

            $sheet2->setCellValue('E1', 'Типы_доставки');
            foreach ($deliveryTypes as $index => $type) {
                $sheet2->setCellValue('E' . ($index + 2), $type);
            }

            $sheet2->setCellValue('F1', 'Пункты_доставки');
            foreach ($deliveryPoints as $index => $point) {
                $sheet2->setCellValue('F' . ($index + 2), $point);
            }

            $sheet2->setCellValue('G1', 'Адреса');
            foreach ($addresses as $index => $address) {
                $sheet2->setCellValue('G' . ($index + 2), $address);
            }

            $sheet2->setCellValue('H1', 'Типы_упаковки');
            foreach ($packagingTypes as $index => $type) {
                $sheet2->setCellValue('H' . ($index + 2), $type);
            }

            // Варианты глубокой инспекции
            $sheet2->setCellValue('I1', 'Инспекция');
            $inspectionOptions = ['да', 'нет'];
            foreach ($inspectionOptions as $index => $option) {
                $sheet2->setCellValue('I' . ($index + 2), $option);
            }

            // Форматирование
            foreach (range('A', 'M') as $column) {
                $sheet1->getColumnDimension($column)->setAutoSize(true);
            }
            foreach (range('A', 'I') as $column) {
                $sheet2->getColumnDimension($column)->setAutoSize(true);
            }

            // Выделяем заголовки жирным
            $sheet1->getStyle('A1:M1')->getFont()->setBold(true);
            $sheet2->getStyle('A1:I1')->getFont()->setBold(true);

            // Добавляем выпадающие списки
            $this->addDropdownListDirect($sheet1, 'C', 2, 100, 'Справочники!$A$2:$A$' . (count($categories) + 1));
            $this->addDropdownListDirect($sheet1, 'H', 2, 100, 'Справочники!$E$2:$E$' . (count($deliveryTypes) + 1));
            $this->addDropdownListDirect($sheet1, 'I', 2, 100, 'Справочники!$F$2:$F$' . (count($deliveryPoints) + 1));
            $this->addDropdownListDirect($sheet1, 'J', 2, 100, 'Справочники!$G$2:$G$' . (count($addresses) + 1));
            $this->addDropdownListDirect($sheet1, 'K', 2, 100, 'Справочники!$H$2:$H$' . (count($packagingTypes) + 1));
            $this->addDropdownListDirect($sheet1, 'M', 2, 100, 'Справочники!$I$2:$I$3');

            // Возвращаемся к первому листу
            $spreadsheet->setActiveSheetIndex(0);

            // Создаем временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'test_order_data_');
            $writer = new WriterXlsx($spreadsheet);
            $writer->save($tempFile);

            Yii::info('Тестовый Excel файл успешно создан');
            
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
