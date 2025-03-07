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

class SpreadSheetController extends V1Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['upload-spreadsheet'] = ['post'];
        return $behaviors;
    }

    public function actionUploadSpreadsheet()
    {
        // Метод для загрузки файла Excel и его обработки
        // ссылка /api/v1/spread-sheet/upload-spreadsheet [POST ]
        // параметры:
        // - file: файл Excel
        
    }

    private function validateFields($file)
    {
        // метод для проверки полей в файле Excel и соответствия их типу данных модели Заказа/ Товара
        
    }

    private function getFields($modelType = 'Order'){
        // метод для получения полей модели Заказа/ Товара
        // здесь нам надо получить все необходимые поля для модели потому что они могу меняться с течением времени
        // поэтому мы будем использовать конфигурационный файл
        return 'config' ;
    }
}