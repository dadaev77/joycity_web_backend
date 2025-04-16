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
            "C2" => "Выберите из списка",
            "D2" => "Выберите из списка",
            "E2" => "Описание товара",
            "F2" => "от 1 до 100 000",
            "G2" => "от 1 до 1 000 000",
            "H2" => "Выберите из списка",
            "I2" => "Выберите из списка",
            "J2" => "Выберите из списка",
            "K2" => "Выберите из списка",
            "L2" => "от 1 до 100 000",
            "M2" => "Выберите из списка"
        ],
    ];
    private $product = [
        'attributes' => [
            "A" => "Фото",
            "B" => "Название товара",
            "C" => "Категория товара",
            "D" => "Подкатегория",
            "E" => "Описание товара",
            "F" => "Желаемое количество товара, шт",
            "G" => "Желаемая стоимость за единицу товара, Р",
            "H" => "Тип доставки",
        ],
        'exampleData' => [
            "A2" => "https://example.com/photo.jpg",
            "B2" => "test 567",
            "C2" => "Детям",
            "D2" => "Детская электроника",
            "E2" => "тестовый товар",
            "F2" => "55",
            "G2" => "555",
            "H2" => "Быстрое авто",
            "I2" => "Склад",
            "J2" => "Москва, Тестовый склад",
            "K2" => "Мешок + скотч",
            "L2" => "10",
            "M2" => "да"
        ],
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
        $allowedTypes = ['order', 'product'];
        $type = strtolower($type);
        if (!in_array($type, $allowedTypes)) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getStatic()->BAD_REQUEST,
                [
                    'message' => 'Неверный тип файла'
                ],
                422
            );
        }
        $spreadsheet = $this->generateExcelTemplate($type);


        return ApiResponse::byResponseCode(
            ResponseCodes::getStatic()->SUCCESS,
            [
                'file' =>  $_ENV['APP_URL'] . $spreadsheet

            ]
        );
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
        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'Файл не был загружен'], 422);
        }

        // validate fields from excel file 
        $errors = $this->validateFields($file);
        if ($errors) {
            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, ['message' => 'ошибка валидации полей файла'], 422);
        }

        // SpreadSheet Service
        $result = $this->orderExcelService->processExcelFile($file);
        if (!$result['success']) {
            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, [
                'message' => $result['message'],
                'errors' => $result['errors'] ?? [],
                'debug_info' => $result['debug_info'] ?? null
            ], 422);
        }

        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, $result);
    }

    private function generateExcelTemplate(string $type)
    {
        $spreadsheet = null;
        if ($type === 'order') {
            $spreadsheet = $this->generateOrderExcelTemplate();
        }

        if ($type === 'product') {
            $spreadsheet = $this->generateProductExcelTemplate();
        }

        $fileName = $type . '_' . date('Ymd_His') . '.xlsx';
        $filePath = Yii::getAlias('@webroot') . '/xslx/' . $fileName;
        $fileUrl = Yii::getAlias('@web') . '/xslx/' . $fileName;

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $writer = new WriterXlsx($spreadsheet);
        $writer->save($filePath);
        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet);
        return $fileUrl;
    }

    private function generateOrderExcelTemplate()
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Заказы');
        foreach ($this->order['attributes'] as $key => $attribute) {
            $sheet->setCellValue($key . '1', $attribute);
            $sheet->getColumnDimension($key)->setAutoSize(true);
        }
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        foreach ($this->order['exampleData'] as $key => $data) {
            $sheet->setCellValue($key, $data);
        }

        $this->getDirectoryForOrderExcelTemplate($spreadsheet);

        return $spreadsheet;
    }

    private function generateProductExcelTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Товары');
        foreach ($this->product['attributes'] as $key => $attribute) {
            $sheet->setCellValue($key . '1', $attribute);
            $sheet->getColumnDimension($key)->setAutoSize(true);
        }
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach ($this->product['exampleData'] as $key => $data) {
            $sheet->setCellValue($key, $data);
        }

        $this->getDirectoryForProductExcelTemplate($spreadsheet);

        return $spreadsheet;
    }

    private function getDirectoryForOrderExcelTemplate($spreadsheet)
    {
        $columns = [
            "A" => "Категории",
            "B" => "Типы доставки",
            "C" => "Пункты доставки",
            "D" => "Адреса",
            "E" => "Тип упаковки",
            "F" => "Глубокая инспекция"
        ];

        $categories = \app\models\Category::find()->where(['parent_id' => 1])->all();
        $deliveryTypes = \app\models\TypeDelivery::find()->all();
        $deliveryPoints = \app\models\TypeDeliveryPoint::find()->all();
        $deliveryPointAddresses = \app\models\DeliveryPointAddress::find()->all();
        $packagingTypes = \app\models\TypePackaging::find()->all();

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Справочники');

        foreach ($columns as $key => $column) {
            $sheet2->setCellValue($key . '1', $column);
            $sheet2->getColumnDimension($key)->setAutoSize(true);
        }
        $sheet2->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet2->getStyle('A1:F1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet2->setCellValue('F2', 'Да');
        $sheet2->setCellValue('F3', 'Нет');

        $row = 2;
        $dataArrays = [
            'categories' => ['A', $categories],
            'deliveryTypes' => ['B', $deliveryTypes],
            'deliveryPoints' => ['C', $deliveryPoints],
            'deliveryPointAddresses' => ['D', $deliveryPointAddresses],
            'packagingTypes' => ['E', $packagingTypes],
        ];

        foreach ($dataArrays as $data) {
            list($column, $items) = $data;
            foreach ($items as $item) {
                $sheet2->setCellValue($column . $row, $item->ru_name ?? $item->address);
                $row++;
            }
            $row = 2;
        }

        return $spreadsheet;
    }

    private function getDirectoryForProductExcelTemplate($spreadsheet)
    {
        $categories = \app\models\Category::find()->where(['parent_id' => 1])->all();
        $deliveryTypes = \app\models\TypeDelivery::find()->all();
        $deliveryPoints = \app\models\TypeDeliveryPoint::find()->all();
        $deliveryPointAddresses = \app\models\DeliveryPointAddress::find()->all();
        $packagingTypes = \app\models\TypePackaging::find()->all();

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Справочники');

        $columns = [
            "A" => "Категории",
            "B" => "Типы доставки",
            "C" => "Пункты доставки",
            "D" => "Адреса",
            "E" => "Тип упаковки",
        ];

        foreach ($columns as $key => $column) {
            $sheet2->setCellValue($key . '1', $column);
            $sheet2->getColumnDimension($key)->setAutoSize(true);
        }
        $sheet2->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet2->getStyle('A1:E1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row = 2;
        $dataArrays = [
            'categories' => ['A', $categories],
            'deliveryTypes' => ['B', $deliveryTypes],
            'deliveryPoints' => ['C', $deliveryPoints],
            'deliveryPointAddresses' => ['D', $deliveryPointAddresses],
            'packagingTypes' => ['E', $packagingTypes],
        ];
        foreach ($dataArrays as $data) {
            list($column, $items) = $data;
            foreach ($items as $item) {
                $sheet2->setCellValue($column . $row, $item->ru_name ?? $item->address);
                $row++;
            }
            $row = 2;
        }
        return $spreadsheet;
    }
}
