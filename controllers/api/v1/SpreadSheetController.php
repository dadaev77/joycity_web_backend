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
                    $productName = $worksheet->getCell('A' . $row)->getValue();

                    // Проверяем, не пустая ли строка
                    if (empty($productName)) {
                        continue;
                    }

                    // Создаем новый заказ
                    $order = new Order();
                    $order->buyer_id = Yii::$app->user->id;
                    $order->status = Order::STATUS_CREATED;
                    $order->created_at = time();

                    // Читаем данные из Excel
                    $order->product_name = $productName;
                    $order->quantity = (int)$worksheet->getCell('B' . $row)->getValue();
                    $order->price = (float)$worksheet->getCell('C' . $row)->getValue();
                    $order->description = $worksheet->getCell('D' . $row)->getValue();

                    // Дополнительные поля
                    $order->type_delivery = TypeDelivery::TYPE_DEFAULT;
                    $order->currency = User::getIdentity()->settings->currency;

                    if (!$order->save()) {
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
}