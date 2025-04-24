<?php

namespace app\controllers\api\v1;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\services\ParseExcelService;
use app\controllers\api\V1Controller;
use yii\web\UploadedFile;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;

use Yii;


class SpreadSheetController extends V1Controller
{


    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['download-excel'] = ['get'];
        $behaviors['verbFilter']['actions']['upload-excel'] = ['post'];

        return $behaviors;
    }


    public function actionDownloadExcel(string $type)
    {
        $allowedTypes = ['order', 'product'];
        $type = strtolower($type);
        if (!in_array($type, $allowedTypes)) return ['error' => 'Неверный тип файла'];
        $spreadsheet = $this->generateExcelTemplate($type);

        $fileName = $type . '_' . date('Ymd_His') . '.xlsx';
        $filePath = Yii::getAlias('@webroot') . '/xlsx/' . $fileName;
        $fileUrl = Yii::getAlias('@web') . '/xlsx/' . $fileName;

        if (!file_exists(Yii::getAlias('@webroot') . '/xlsx/')) {
            mkdir(Yii::getAlias('@webroot') . '/xlsx/', 0777, true);
        }

        $writer = new WriterXlsx($spreadsheet);
        $writer->save($filePath);

        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
            'file' => $_ENV['APP_URL'] . $fileUrl
        ]);
    }

    public function actionUploadExcel($type)
    {
        $file = UploadedFile::getInstanceByName('file');
        $result = ParseExcelService::parseExcel($file);

        if (isset($result['error']) && count($result['error']) > 0) return ApiResponse::byResponseCode(ResponseCodes::getStatic()->INTERNAL_ERROR, [
            'message' => $result['error']
        ]);

        return ApiResponse::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
            'data' => $result
        ]);
    }


    private function generateExcelTemplate(string $type)
    {
        $spreadsheet = $this->generateOrderExcelTemplate();
        return $spreadsheet;
    }

    private function generateOrderExcelTemplate()
    {
        $spreadsheet = new Spreadsheet();



        $tables = [
            new \app\components\excel\tables\ReadmeTable(),
            new \app\components\excel\tables\OrderTable(),
            new \app\components\excel\tables\CategoryTable(),
            new \app\components\excel\tables\SubcategoryTable(),
            new \app\components\excel\tables\TypeDeliveryTable(),
            new \app\components\excel\tables\DeliveryPointTypeTable(),
            new \app\components\excel\tables\DeliveryPointAddress(),
            new \app\components\excel\tables\TypePackaging(),
        ];

        $generator = new \app\components\excel\ExcelTableGenerator();

        $spreadsheet = $generator->generate($tables);

        return $spreadsheet;
    }
}
