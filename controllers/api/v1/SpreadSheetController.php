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

            $response = Yii::$app->response;
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="order_template.xlsx"');
            $response->headers->set('X-Success', 'true');
            $response->headers->set('X-Message', 'Excel successfully uploaded');

            return $response->sendFile($templatePath);

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

                $descriptions = [];
                foreach ($worksheet->getRowIterator(2, 2) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $value = $cell->getValue();
                        $descriptions[] = $value !== null ? trim($value) : '';
                    }
                }

                $examples = [];
                foreach ($worksheet->getRowIterator(3, 3) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $value = $cell->getValue();
                        $examples[] = $value !== null ? trim($value) : '';
                    }
                }

                $data = [];
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

                            $value = $cell->getValue();
                            $calculatedValue = $cell->getCalculatedValue();

                            if ($value !== null || $calculatedValue !== null) {
                                $hasData = true;
                            }

                            $rowData[$header] = [
                                'raw_value' => $value,
                                'calculated_value' => $calculatedValue,
                                'type' => gettype($calculatedValue),
                                'data_type' => $cell->getDataType(),
                                'coordinate' => $cell->getCoordinate(),
                                'formula' => $cell->getDataType() === 'f' ? $cell->getValue() : null
                            ];
                        }
                    }

                    if ($hasData) {
                        $data[$rowIndex] = $rowData;
                    }
                }

                return $this->asJson([
                    'success' => true,
                    'message' => 'Данные файла прочитаны для отладки',
                    'debug_data' => [
                        'file_info' => [
                            'name' => $uploadedFile['name'],
                            'type' => $uploadedFile['type'],
                            'size' => $uploadedFile['size']
                        ],
                        'structure' => [
                            'headers' => array_combine(range('A', $worksheet->getHighestColumn()), $headers),
                            'descriptions' => array_combine(range('A', $worksheet->getHighestColumn()), $descriptions),
                            'examples' => array_combine(range('A', $worksheet->getHighestColumn()), $examples)
                        ],
                        'data_rows' => $data,
                        'row_count' => $worksheet->getHighestRow(),
                        'column_count' => $worksheet->getHighestColumn()
                    ]
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