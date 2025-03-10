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

        // Определяем разрешенные действия и их HTTP методы
        $behaviors['verbFilter'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'upload-spreadsheet' => ['POST'],
                'export' => ['GET'],
                'upload-product-spreadsheet' => ['POST']
            ]
        ];
    }

}