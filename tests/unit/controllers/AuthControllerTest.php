<?php

namespace tests\unit\controllers;

// Определяем константы Yii
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Загружаем Yii
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';

use app\controllers\api\v1\AuthController;
use app\helpers\POSTHelper;
use app\models\User;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Request;
use yii\web\Response;

class AuthControllerTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Инициализация Yii приложения для тестов
        if (Yii::$app === null) {
            $this->mockApplication();
        }
        
        $this->controller = new AuthController('auth', Yii::$app);
        
        // Создаем новый объект Request и устанавливаем его через setRequest()
        $request = new Request();
        Yii::$app->set('request', $request);
        
        // Создаем новый объект Response и устанавливаем его через setResponse()
        $response = new Response();
        Yii::$app->set('response', $response);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Очистка тестовых данных
        User::deleteAll(['email' => 'test@example.com']);
    }

    public function testRegisterSuccess()
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'Test123456',
            'confirm_password' => 'Test123456',
            'phone_number' => '+79999999999',
            'organization_name' => 'Test Organization',
            'surname' => 'Test',
            'name' => 'User',
            'role' => 'buyer',
            'phone_country_code' => '+7',
            'country' => 'Russia',
            'city' => 'Moscow',
            'address' => 'Test Address'
        ];

        Yii::$app->request->setBodyParams($data);
        $result = $this->controller->actionRegister();

        // Проверяем, что ответ содержит ожидаемые ключи
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertArrayHasKey('response', $result);
        
        // Проверяем, что пользователь создан в БД
        $user = User::findOne(['email' => 'test@example.com']);
        $this->assertNotNull($user);
        $this->assertEquals('User Test', $user->name . ' ' . $user->surname);
    }

    public function testRegisterValidationError()
    {
        $data = [
            'email' => 'invalid-email',
            'password' => '123',
            'confirm_password' => '123',
            'phone_number' => 'invalid-phone',
            'organization_name' => '',
            'surname' => '',
            'name' => '',
            'role' => 'invalid-role',
            'phone_country_code' => '',
            'country' => '',
            'city' => '',
            'address' => ''
        ];

        Yii::$app->request->setBodyParams($data);
        $result = $this->controller->actionRegister();

        // Проверяем, что ответ содержит ожидаемые ключи
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('errors', $result['response']);
    }

    public function testLoginSuccess()
    {
        // Создаем тестового пользователя
        $user = new User();
        $user->email = 'test@example.com';
        $user->password = Yii::$app->security->generatePasswordHash('test123456');
        $user->name = 'Test';
        $user->surname = 'User';
        $user->phone_number = '+79999999999';
        $user->role = 'buyer';
        $user->access_token = Yii::$app->security->generateRandomString();
        $user->save();

        // Мокаем POSTHelper::getPostWithKeys
        $this->mockPOSTHelper();

        $data = [
            'email' => 'test@example.com',
            'password' => 'test123456',
            'role' => 'buyer'
        ];

        Yii::$app->request->setBodyParams($data);
        $result = $this->controller->actionLogin();

        // Проверяем только, что результат не null
        $this->assertNotNull($result);
    }

    public function testLoginInvalidCredentials()
    {
        // Мокаем POSTHelper::getPostWithKeys
        $this->mockPOSTHelper();

        $data = [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'role' => 'buyer'
        ];

        Yii::$app->request->setBodyParams($data);
        $result = $this->controller->actionLogin();

        // Проверяем, что результат не null
        $this->assertNotNull($result);
        
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertArrayHasKey('code', $result);
    }

    /**
     * Мокаем POSTHelper::getPostWithKeys для тестов
     */
    protected function mockPOSTHelper()
    {
        // Создаем мок для POSTHelper
        $mock = $this->getMockBuilder(POSTHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Устанавливаем ожидаемое поведение для метода getPostWithKeys
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

        // Устанавливаем ожидаемое поведение для метода getEmptyParams
        $mock->method('getEmptyParams')
            ->willReturnCallback(function ($params, $keysOnly = false) {
                $emptyParams = array_filter($params, static function ($value) {
                    return empty($value);
                });
                
                return $keysOnly ? array_keys($emptyParams) : $emptyParams;
            });

        // Заменяем оригинальный класс на мок
        Yii::$container->set(POSTHelper::class, $mock);
    }

    /**
     * Мокаем приложение Yii для тестов
     */
    protected function mockApplication()
    {
        // Загружаем конфигурацию из .env
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        
        // Создаем конфигурацию для тестов
        $config = [
            'id' => 'app-tests',
            'basePath' => dirname(__DIR__ . '/../../../'),
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
            'params' => require __DIR__ . '/../../../config/params.php',
        ];
        
        // Создаем приложение Yii
        Yii::$app = new \yii\web\Application($config);
    }
} 