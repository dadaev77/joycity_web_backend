<?php

namespace tests\unit\controllers\client;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../vendor/yiisoft/yii2/Yii.php';

use app\controllers\api\v1\client\OrderController;
use app\helpers\POSTHelper;
use app\models\User;
use app\models\Order;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Request;
use yii\web\Response;

class OrderControllerTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (Yii::$app === null) {
            $this->mockApplication();
        }
        
        $this->controller = new OrderController('order', Yii::$app);
        
        $request = new Request();
        Yii::$app->set('request', $request);
        
        $response = new Response();
        Yii::$app->set('response', $response);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        User::deleteAll(['email' => 'client@example.com']);
        Order::deleteAll(['client_id' => $this->getTestClient()->id]);
    }

    /**
     * @group create
     */
    public function testCreateOrderSuccess()
    {
        $client = $this->getTestClient();
        $this->mockUserIdentity($client);
        $this->mockPOSTHelper();

        $data = [
            'product_name_ru' => 'Test Product',
            'product_description_ru' => 'Test Description',
            'product_link' => 'https://example.com/product',
            'expected_quantity' => 1,
            'expected_price_per_item' => 100.00,
            'delivery_type' => 'standard',
            'delivery_address' => 'Test Address',
            'comment' => 'Test Comment',
            'status' => Order::STATUS_CREATED,
            'subcategory_id' => 1,
            'type_packaging_id' => 1,
            'type_delivery_id' => 1,
            'type_delivery_point_id' => 1,
            'delivery_point_address_id' => 1
        ];

        Yii::$app->request->setBodyParams($data);
        $result = $this->controller->actionCreate();

        $this->assertNotNull($result);
        
        $order = Order::findOne(['client_id' => $client->id]);
        $this->assertNotNull($order);
        $this->assertEquals($data['product_name_ru'], $order->product_name_ru);
        $this->assertEquals(Order::STATUS_WAITING_FOR_BUYER_OFFER, $order->status);
    }

    /**
     * @group update
     */
    public function testUpdateOrderSuccess()
    {
        // ... код теста ...
    }

    /**
     * @group cancel
     */
    public function testCancelOrderSuccess()
    {
        // ... код теста ...
    }

    public function testCreateOrderValidationError()
    {
        $testData = [
            'product_name_ru' => '', // Пустое имя продукта должно вызвать ошибку валидации
            'expected_price_per_item' => -100.00, // Отрицательная цена должна вызвать ошибку валидации
            'status' => Order::STATUS_CREATED
        ];
        // ... existing code ...
    }

    protected function getTestClient()
    {
        $client = new User();
        $client->email = 'client@example.com';
        $client->password = Yii::$app->security->generatePasswordHash('test123456');
        $client->name = 'Test';
        $client->surname = 'Client';
        $client->phone_number = '+79999999999';
        $client->role = 'client';
        $client->access_token = Yii::$app->security->generateRandomString();
        $client->save();
        
        return $client;
    }

    protected function mockUserIdentity($user)
    {
        $mock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getIdentity'])
            ->getMock();
            
        $mock->method('getIdentity')
            ->willReturn($user);
            
        Yii::$container->set(User::class, $mock);
    }

    protected function mockPOSTHelper()
    {
        $mock = $this->getMockBuilder(POSTHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('getPostWithKeys')
            ->willReturnCallback(function ($keys, $fillNull = false) {
                $request = Yii::$app->request;
                $postData = $request->post();
                
                if ($fillNull) {
                    $array = array_fill_keys($keys, null);
                    return array_intersect_key($postData, $array) + $array;
                }
                
                return array_intersect_key($postData, array_flip($keys));
            });

        $mock->method('getEmptyParams')
            ->willReturnCallback(function ($params, $keysOnly = false) {
                $emptyParams = array_filter($params, static function ($value) {
                    return empty($value);
                });
                
                return $keysOnly ? array_keys($emptyParams) : $emptyParams;
            });

        Yii::$container->set(POSTHelper::class, $mock);
    }

    protected function mockApplication()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../');
        $dotenv->load();
        
        $config = [
            'id' => 'app-tests',
            'basePath' => dirname(__DIR__ . '/../../../../'),
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
                    'username' => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASSWORD'],
                    'charset' => $_ENV['DB_CHARSET'],
                ],
                'urlManager' => [
                    'showScriptName' => true,
                ],
                'request' => [
                    'class' => 'yii\web\Request',
                    'cookieValidationKey' => 'test',
                    'enableCsrfValidation' => false,
                ],
                'response' => [
                    'class' => 'yii\web\Response',
                ],
                'telegramLog' => [
                    'class' => 'app\components\TelegramLog',
                    'enabled' => false,
                ],
                'queue' => [
                    'class' => 'yii\queue\sync\Queue',
                    'handle' => false,
                ],
            ],
            'params' => require __DIR__ . '/../../../../config/params.php',
        ];
        
        Yii::$app = new \yii\web\Application($config);
    }
} 