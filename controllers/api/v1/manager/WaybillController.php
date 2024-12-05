<?php

namespace app\controllers\api\v1\manager;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use Mpdf\Mpdf;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use app\models\Order;

/**
 * Контроллер для работы с накладными
 */
class WaybillController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions'] = [
            'generate' => ['post'],
            'get-file' => ['get'],
            'regenerate' => ['put'],
        ];

        return $behaviors;
    }

    /**
     * Генерация новой накладной
     * 
     * @OA\Post(
     *     path="/api/v1/manager/waybill/generate",
     *     summary="Генерация новой накладной",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sender", "receiver", "items"},
     *             @OA\Property(property="sender", type="object",
     *                 @OA\Property(property="name", type="string", example="ООО Компания"),
     *                 @OA\Property(property="phone", type="string", example="+7 999 999 99 99"),
     *                 @OA\Property(property="address", type="string", example="г. Москва, ул. Примерная, д. 1")
     *             ),
     *             @OA\Property(property="receiver", type="object",
     *                 @OA\Property(property="name", type="string", example="ИП Иванов"),
     *                 @OA\Property(property="phone", type="string", example="+7 888 888 88 88"),
     *                 @OA\Property(property="address", type="string", example="г. Санкт-Петербург, ул. Тестовая, д. 2")
     *             ),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="name", type="string", example="Товар 1"),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="price", type="number", format="float", example=1000.50)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Накладная успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="file_url", type="string", example="https://example.com/uploads/waybills/waybill_123.pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Неверный формат данных")
     *         )
     *     )
     * )
     */
    public function actionGenerate()
    {
        try {
            $apiCodes = Order::apiCodes();
            $request = Yii::$app->request;
            $data = $request->post();

            // Генерируем уникальное имя файла
            $fileName = 'waybill_' . uniqid() . '.pdf';
            $filePath = Yii::getAlias('@webroot/uploads/waybills/' . $fileName);

            // Создаем директорию если её нет
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            // Генерируем PDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
            ]);

            // Рендерим шаблон с данными
            $content = Yii::$app->view->render('@app/views/pdf/templates/invoce', [
                'data' => $data,
            ]);

            $mpdf->WriteHTML($content);
            $mpdf->Output($filePath, 'F');
            
            return ApiResponse::byResponseCode($apiCodes->SUCCESS,[
                'invoce' => $_ENV['APP_URL'] . '/uploads/waybills/' . $fileName
            ]);

        } catch (\Exception $e) {
            return ApiResponse::byResponseCode($apiCodes->ERROR_SAVE, [
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Получение ссылки на файл накладной
     * 
     * @OA\Get(
     *     path="/api/v1/manager/waybill/get-file/{id}",
     *     summary="Получение ссылки на файл накладной",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID накладной",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешное получение ссылки",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="file_url", type="string", example="https://example.com/uploads/waybills/waybill_123.pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Накладная не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Файл накладной не найден")
     *         )
     *     )
     * )
     */
    public function actionGetFile($id)
    {
        try {
            // Здесь нужно получить информацию о накладной из БД
            $filePath = Yii::getAlias('@webroot/uploads/waybills/waybill_' . $id . '.pdf');
            
            if (!file_exists($filePath)) {
                throw new NotFoundHttpException('Файл накладной не найден');
            }

            return ApiResponse::success([
                'file_url' => Yii::$app->urlManager->createAbsoluteUrl(['/uploads/waybills/waybill_' . $id . '.pdf']),
            ]);
        } catch (NotFoundHttpException $e) {
            return ApiResponse::error($e->getMessage(), 404);
        }
    }

    /**
     * Повторная генерация накладной с учетом изменений
     * 
     * @OA\Put(
     *     path="/api/v1/manager/waybill/regenerate/{id}",
     *     summary="Регенерация накладной с новыми данными",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID накладной",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sender", "receiver", "items"},
     *             @OA\Property(property="sender", type="object",
     *                 @OA\Property(property="name", type="string", example="ООО Компания"),
     *                 @OA\Property(property="phone", type="string", example="+7 999 999 99 99"),
     *                 @OA\Property(property="address", type="string", example="г. Москва, ул. Примерная, д. 1")
     *             ),
     *             @OA\Property(property="receiver", type="object",
     *                 @OA\Property(property="name", type="string", example="ИП Иванов"),
     *                 @OA\Property(property="phone", type="string", example="+7 888 888 88 88"),
     *                 @OA\Property(property="address", type="string", example="г. Санкт-Петербург, ул. Тестовая, д. 2")
     *             ),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="name", type="string", example="Товар 1"),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="price", type="number", format="float", example=1000.50)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Накладная успешно обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="file_url", type="string", example="https://example.com/uploads/waybills/waybill_123.pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Накладная не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Файл накладной не найден")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Неверный формат данных")
     *         )
     *     )
     * )
     */
    public function actionRegenerate($id)
    {
        try {
            $request = Yii::$app->request;
            $changes = $request->getBodyParams();

            // Получаем старую накладную
            $filePath = Yii::getAlias('@webroot/uploads/waybills/waybill_' . $id . '.pdf');
            if (!file_exists($filePath)) {
                throw new NotFoundHttpException('Файл накладной не найден');
            }

            // Генерируем новую накладную с изменениями
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
            ]);

            // Рендерим шаблон с обновленными данными
            $content = Yii::$app->view->render('@app/views/pdf/templates/invoce', [
                'data' => $changes,
            ]);

            $mpdf->WriteHTML($content);
            $mpdf->Output($filePath, 'F');

            return ApiResponse::success([
                'file_url' => Yii::$app->urlManager->createAbsoluteUrl(['/uploads/waybills/waybill_' . $id . '.pdf']),
            ]);

        } catch (NotFoundHttpException $e) {
            return ApiResponse::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
