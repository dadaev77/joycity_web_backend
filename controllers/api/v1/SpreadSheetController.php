<?php

namespace app\controllers\api\v1;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;

use app\controllers\api\V1Controller;
use app\services\order\OrderExcelService;
use app\services\product\ProductExcelService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use yii\web\UploadedFile;

use Yii;


class SpreadSheetController extends V1Controller
{
    private $orderExcelService;
    private $productExcelService;

    private $order = [
        'attributes' => [
            "A" => "Фото",
            "B" => "Название товара",
            "C" => "Категория товара",
            "D" => "Подкатегория",
            "E" => "Описание товара",
            "F" => "Желаемое количество товара, шт",
            "G" => "Желаемая стоимость за единицу товара, Р",
            "H" => "Тип доставки",
            "I" => "Тип пункта доставки",
            "J" => "Адрес пункта доставки",
            "K" => "Тип упаковки",
            "L" => "Количество упаковок, шт",
            "M" => "Глубокая инспекция"
        ],
        'exampleData' => [
            "A2" => "https://example.com/photo.jpg",
            "B2" => "Например Брюки женские",
            "C2" => "Женщинам",
            "D2" => "Брюки",
            "E2" => "Качественные женские брюки из хлопка",
            "F2" => "100",
            "G2" => "1500",
            "H2" => "Быстрое авто",
            "I2" => "Склад",
            "J2" => "Москва, Тестовый склад",
            "K2" => "Коробка",
            "L2" => "10",
            "M2" => "нет"
        ]
    ];

    private $product = [
        'attributes' => [
            "A" => "Фото",
            "B" => "Название товара",
            "C" => "Категория товара",
            "D" => "Подкатегория",
            "E" => "Описание товара",
            "F" => "Количество товара, шт",
            "G" => "Стоимость за единицу товара, Р",
            "H" => "Тип доставки",
            "I" => "Тип пункта доставки",
            "J" => "Адрес пункта доставки",
            "K" => "Тип упаковки",
            "L" => "Количество упаковок, шт",
            "M" => "Глубокая инспекция"
        ],
        'exampleData' => [
            "A2" => "https://example.com/photo.jpg",
            "B2" => "Например Брюки женские",
            "C2" => "Женщинам",
            "D2" => "Брюки",
            "E2" => "Качественные женские брюки из хлопка",
            "F2" => "100",
            "G2" => "1500",
            "H2" => "Быстрое авто",
            "I2" => "Склад",
            "J2" => "Москва, Тестовый склад",
            "K2" => "Коробка",
            "L2" => "10",
            "M2" => "нет"
        ]
    ];

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->orderExcelService = new OrderExcelService();
        $this->productExcelService = new ProductExcelService();
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['download-excel'] = ['get'];
        $behaviors['verbFilter']['actions']['upload-excel'] = ['post'];
        $behaviors['verbFilter']['actions']['generate-test-excel'] = ['get'];
        $behaviors['verbFilter']['actions']['download-test-excel'] = ['get'];
        $behaviors['verbFilter']['actions']['download-product-excel'] = ['get'];
        $behaviors['verbFilter']['actions']['upload-product-excel'] = ['post'];

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
    public function actionDownloadExcel(string $type)
    {
        try {
            $allowedTypes = ['order', 'product'];
            $type = strtolower($type);
            
            if (!in_array($type, $allowedTypes)) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getStatic()->BAD_REQUEST,
                    ['message' => 'Неверный тип файла'],
                    422
                );
            }

            $filePath = $type === 'order' 
                ? $this->orderExcelService->generateTemplate() 
                : $this->productExcelService->generateTemplate();

            if (!file_exists($filePath)) {
                throw new \Exception('Файл шаблона не найден');
            }

            $fileName = $type === 'order' ? 'order_template.xlsx' : 'product_template.xlsx';
            $webPath = '/entrypoint/api/xslx/' . $fileName;
            $fullPath = Yii::getAlias('@webroot') . $webPath;

            // Создаем директорию, если её нет
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            // Копируем файл в публичную директорию
            copy($filePath, $fullPath);
            unlink($filePath); // Удаляем временный файл

            // Получаем базовый URL из .env
            $baseUrl = getenv('APP_URL') ?? 'https://joycityrussia.friflex.com';
            
            return ApiResponse::byResponseCode(
                ResponseCodes::getStatic()->SUCCESS,
                [
                    'file' => $baseUrl . $webPath
                ]
            );
        } catch (\Exception $e) {
            Yii::error("Ошибка при скачивании шаблона Excel: " . $e->getMessage());
            return ApiResponse::byResponseCode(
                ResponseCodes::getStatic()->INTERNAL_SERVER_ERROR,
                ['message' => 'Ошибка при создании шаблона Excel']
            );
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
            $file = UploadedFile::getInstanceByName('file');
            if (!$file) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getStatic()->BAD_REQUEST,
                    ['message' => 'Файл не был загружен'],
                    422
                );
            }

            $result = $this->orderExcelService->processExcelFile($file);
            
            if (!$result['success']) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getStatic()->BAD_REQUEST,
                    [
                        'message' => $result['message'],
                        'errors' => $result['errors'] ?? [],
                        'debug_info' => $result['debug_info'] ?? null
                    ],
                    422
                );
            }

            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, $result);
        } catch (\Exception $e) {
            Yii::error("Ошибка при загрузке Excel файла: " . $e->getMessage());
            return ApiResponse::byResponseCode(
                ResponseCodes::getStatic()->INTERNAL_SERVER_ERROR,
                ['message' => 'Ошибка при обработке Excel файла']
            );
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
            $filePath = $this->orderExcelService->generateTemplate();
            
            if (!file_exists($filePath)) {
                throw new \Exception('Файл шаблона не найден');
            }

            Yii::$app->response->sendFile($filePath, 'test_order_data.xlsx', [
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'inline' => false
            ])->send();

            unlink($filePath);
            
            return null;
        } catch (\Exception $e) {
            Yii::error("Ошибка при скачивании тестового Excel: " . $e->getMessage());
            return ApiResponse::byResponseCode(
                ResponseCodes::getStatic()->INTERNAL_SERVER_ERROR,
                ['message' => 'Ошибка при создании тестового Excel']
            );
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

    private function validateFields()
    {
        // парсим файл
        // проверяем наличие всех необходимых полей
        // возвращаем ошибки
    }

    private function getDeliveryPointAddressId($address)
    {
        $deliveryPointAddress = \app\models\DeliveryPointAddress::find()
            ->where(['address' => $address])
            ->andWhere(['is_deleted' => 0])
            ->one();
        
        return $deliveryPointAddress ? $deliveryPointAddress->id : null;
    }

    public function actionUploadProductExcel()
    {
        try {
            $file = UploadedFile::getInstanceByName('file');
            if (!$file) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getStatic()->BAD_REQUEST,
                    ['message' => 'Файл не был загружен'],
                    422
                );
            }

            $result = $this->productExcelService->processExcelFile($file);
            
            if (!$result['success']) {
                return ApiResponse::byResponseCode(
                    ResponseCodes::getStatic()->BAD_REQUEST,
                    [
                        'message' => $result['message'],
                        'errors' => $result['errors'] ?? [],
                        'debug_info' => $result['debug_info'] ?? null
                    ],
                    422
                );
            }

            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, $result);
        } catch (\Exception $e) {
            Yii::error("Ошибка при загрузке Excel файла для товаров: " . $e->getMessage());
            return ApiResponse::byResponseCode(
                ResponseCodes::getStatic()->INTERNAL_SERVER_ERROR,
                ['message' => 'Ошибка при обработке Excel файла для товаров']
            );
        }
    }
}
