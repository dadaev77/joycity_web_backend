<?php

namespace app\models\structure;

use app\models\Attachment;
use app\models\Base;
use app\models\BuyerDeliveryOffer;
use app\models\BuyerOffer;
use app\models\Category;
use app\models\ChatUser;
use app\models\DeliveryPointAddress;
use app\models\FeedbackBuyer;
use app\models\FeedbackProduct;
use app\models\FeedbackUser;
use app\models\FulfillmentInspectionReport;
use app\models\FulfillmentMarketplaceTransaction;
use app\models\FulfillmentOffer;
use app\models\FulfillmentPackagingLabeling;
use app\models\FulfillmentStockReport;
use app\models\Notification;
use app\models\Order;
use app\models\OrderDistribution;
use app\models\Product;
use app\models\TypeDelivery;
use app\models\TypePackaging;
use app\models\User;
use app\models\UserLinkCategory;
use app\models\UserLinkTypeDelivery;
use app\models\UserLinkTypePackaging;
use app\models\UserSettings;
use app\models\UserVerificationRequest;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string|null $email
 * @property string $password
 * @property string|null $access_token
 * @property string $personal_id
 * @property string $name
 * @property string $surname
 * @property string $organization_name
 * @property string $phone_number
 * @property string|null $nickname
 * @property string|null $country
 * @property string|null $city
 * @property string|null $address
 * @property string $role
 * @property float $rating
 * @property int $feedback_count
 * @property int|null $is_deleted
 * @property int $is_email_confirmed
 * @property int $is_verified
 * @property int|null $avatar_id
 * @property string|null $mpstats_token
 * @property string|null $description
 * @property string $phone_country_code
 *
 * @property Attachment $avatar
 * @property BuyerDeliveryOffer[] $buyerDeliveryBuyerOffers
 * @property BuyerDeliveryOffer[] $buyerDeliveryManagerOffers
 * @property BuyerOffer[] $buyerOffers
 * @property ChatUser[] $chatUsers
 * @property DeliveryPointAddress $deliveryPointAddress
 * @property FeedbackBuyer[] $feedbackBuyers
 * @property FeedbackBuyer[] $feedbackBuyers0
 * @property FeedbackProduct[] $feedbackProducts
 * @property FeedbackUser[] $feedbackUsers
 * @property FulfillmentInspectionReport[] $fulfillmentInspectionReports
 * @property FulfillmentMarketplaceTransaction[] $fulfillmentMarketplaceTransactions
 * @property FulfillmentOffer[] $fulfillmentOffers
 * @property FulfillmentPackagingLabeling[] $fulfillmentPackagingLabelings
 * @property FulfillmentStockReport[] $fulfillmentStockReports
 * @property Notification[] $notifications
 * @property OrderDistribution[] $orderDistributions
 * @property Order[] $orders
 * @property Order[] $managerOrders
 * @property Order[] $buyerOrders
 * @property Product[] $products
 * @property UserLinkCategory[] $userLinkCategories
 * @property Category[] $categories
 * @property UserLinkTypeDelivery[] $userLinkTypeDeliveries
 * @property UserLinkTypePackaging[] $userLinkTypePackagings
 * @property UserSettings $userSettings
 * @property UserVerificationRequest[] $userVerificationRequestsApproving
 * @property UserVerificationRequest[] $userVerificationRequestsCreator
 * @property UserVerificationRequest[] $userVerificationRequestsManager
 *
 */
class UserStructure extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                [
                    'password',
                    'personal_id',
                    'name',
                    'surname',
                    'organization_name',
                    'phone_number',
                    'role',
                ],
                'required',
            ],
            [['rating'], 'number'],
            [
                [
                    'feedback_count',
                    'is_deleted',
                    'is_email_confirmed',
                    'is_verified',
                    'avatar_id',
                ],
                'integer',
            ],
            [['description'], 'string'],
            [['email', 'nickname'], 'string', 'max' => 100],
            [
                ['password', 'access_token', 'role', 'personal_id'],
                'string',
                'max' => 255,
            ],
            [
                [
                    'name',
                    'surname',
                    'organization_name',
                    'country',
                    'city',
                    'address',
                ],
                'string',
                'max' => 60,
            ],
            [['phone_number'], 'string', 'max' => 15],
            [['mpstats_token'], 'string', 'max' => 512],
            [['phone_country_code'], 'string', 'max' => 10],
            [['phone_number'], 'unique'],
            [['personal_id'], 'unique'],
            [['nickname'], 'unique'],
            [['email'], 'unique'],
            [
                ['avatar_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['avatar_id' => 'id'],
            ],

            // кастомные валидации
            [['id'], 'integer', 'max' => 9999999999],
            [['email'], 'email'],
            [
                ['email', 'phone_number'],
                'validateEmailOrPhoneNumber',
                'skipOnEmpty' => false,
            ],
            [
                ['country', 'city', 'address'],
                'required',
                'when' => function ($model) {
                    return $model->role === User::ROLE_BUYER;
                },
            ],
            [
                ['email'],
                'unique',
                'message' => 'Пользователь с таким email уже существует.',
            ],
            [
                ['phone_number'],
                'unique',
                'message' =>
                    'Пользователь с таким номером телефона уже существует.',
            ],
            [
                ['password'],
                'match',
                'pattern' => '/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]+$/',
                'message' =>
                    'Латиница, A-z, 0-9. Должен содержать большую букву и цифру. Минимально символов 8 - максимально 20',
            ],
            [['password', 'nickname'], 'string', 'min' => 8, 'max' => 20],
            [['role'], 'in', 'range' => User::ROLES_ALL],
            [
                [
                    'name',
                    'city',
                    'country',
                    'address',
                    'surname',
                    'organization_name',
                    'email',
                ],
                'required',
                'on' => [User::SCENARIO_PROFILE_CREATION, ''],
            ],
            [
                ['phone_number'],
                'match',
                'pattern' => '/^\+(\d{1,3})?\d{10}$/',
                'message' => 'Неверный формат номера телефона',
            ],
            [
                ['name', 'city', 'country', 'surname'],
                'match',
                'pattern' => '/^[A-Za-zА-Яа-я0-9\-\ ]{1,60}$/u',
                'message' =>
                    'Поле {attribute} должно содержать кириллицу, латиницу, цифры и не превышать 60 символов. Допустимы символы: A-z, А-я, 0-9, дефис.',
            ],
            [
                'organization_name',
                'match',
                'pattern' => '/^[A-Za-zА-Яа-я0-9\s\-\"\«\»]{1,60}$/u',
                'message' =>
                    'Поле {attribute} должно содержать кириллицу, латиницу, цифры, пробелы, кавычки и тире и не превышать 60 символов. Допустимы символы: A-z, А-я, 0-9, пробелы, кавычки и тире.',
            ],
            [
                'address',
                'match',
                'pattern' => '/^[A-Za-zА-Яа-я0-9\s\-.\/]{1,60}$/u',
                'message' =>
                    'Поле {attribute} должно содержать кириллицу, латиницу, цифры, пробелы, тире, точки и слеши и не превышать 60 символов. Допустимы символы: A-z, А-я, 0-9, пробелы, тире, точки и слеши.',
            ],
        ];
    }

    public function validateEmailOrPhoneNumber($attribute, $params)
    {
        if (empty($this->email) && empty($this->phone_number)) {
            $this->addError(
                $attribute,
                'Поле Email или Phone Number должно быть заполнено.',
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'password' => 'Password',
            'access_token' => 'Access Token',
            'personal_id' => 'Personal ID',
            'name' => 'Name',
            'surname' => 'Surname',
            'organization_name' => 'Organization Name',
            'phone_number' => 'Phone Number',
            'nickname' => 'Nickname',
            'country' => 'Country',
            'city' => 'City',
            'address' => 'Address',
            'role' => 'Role',
            'rating' => 'Rating',
            'feedback_count' => 'Feedback Count',
            'is_deleted' => 'Is Deleted',
            'is_email_confirmed' => 'Is Email Confirmed',
            'is_verified' => 'Is Verified',
            'avatar_id' => 'Avatar ID',
            'mpstats_token' => 'Mpstats Token',
            'description' => 'Description',
            'phone_country_code' => 'Phone Country Code',
        ];
    }

    /**
     * Gets query for [[Avatar]].
     *
     * @return ActiveQuery
     */
    public function getAvatar()
    {
        return $this->hasOne(Attachment::class, ['id' => 'avatar_id']);
    }

    /**
     * Gets query for [[BuyerDeliveryBuyerOffers]].
     *
     * @return ActiveQuery
     */
    public function getBuyerDeliveryBuyerOffers()
    {
        return $this->hasMany(BuyerDeliveryOffer::class, ['buyer_id' => 'id']);
    }

    /**
     * Gets query for [[BuyerDeliveryManagerOffers]].
     *
     * @return ActiveQuery
     */
    public function getBuyerDeliveryManagerOffers()
    {
        return $this->hasMany(BuyerDeliveryOffer::class, [
            'manager_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[BuyerOffers]].
     *
     * @return ActiveQuery
     */
    public function getBuyerOffers()
    {
        return $this->hasMany(BuyerOffer::class, ['buyer_id' => 'id']);
    }

    /**
     * Gets query for [[ChatUsers]].
     *
     * @return ActiveQuery
     */
    public function getChatUsers()
    {
        return $this->hasMany(ChatUser::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[DeliveryPointAddress]].
     *
     * @return ActiveQuery
     */
    public function getDeliveryPointAddress()
    {
        return $this->hasOne(DeliveryPointAddress::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[FeedbackBuyers]].
     *
     * @return ActiveQuery
     */
    public function getFeedbackBuyers()
    {
        return $this->hasMany(FeedbackBuyer::class, ['buyer_id' => 'id']);
    }

    /**
     * Gets query for [[FeedbackBuyers0]].
     *
     * @return ActiveQuery
     */
    public function getFeedbackBuyers0()
    {
        return $this->hasMany(FeedbackBuyer::class, ['created_by' => 'id']);
    }

    /**
     * Gets query for [[FeedbackProducts]].
     *
     * @return ActiveQuery
     */
    public function getFeedbackProducts()
    {
        return $this->hasMany(FeedbackProduct::class, ['created_by' => 'id']);
    }

    /**
     * Gets query for [[FeedbackUsers]].
     *
     * @return ActiveQuery
     */
    public function getFeedbackUsers()
    {
        return $this->hasMany(FeedbackUser::class, ['created_by' => 'id']);
    }

    /**
     * Gets query for [[FulfillmentInspectionReports]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentInspectionReports()
    {
        return $this->hasMany(FulfillmentInspectionReport::class, [
            'fulfillment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FulfillmentMarketplaceTransactions]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentMarketplaceTransactions()
    {
        return $this->hasMany(FulfillmentMarketplaceTransaction::class, [
            'fulfillment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FulfillmentOffers]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentOffers()
    {
        return $this->hasMany(FulfillmentOffer::class, [
            'fulfillment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FulfillmentPackagingLabelings]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentPackagingLabelings()
    {
        return $this->hasMany(FulfillmentPackagingLabeling::class, [
            'fulfillment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FulfillmentStockReports]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentStockReports()
    {
        return $this->hasMany(FulfillmentStockReport::class, [
            'fulfillment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[Notifications]].
     *
     * @return ActiveQuery
     */
    public function getNotifications()
    {
        return $this->hasMany(Notification::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[OrderDistributions]].
     *
     * @return ActiveQuery
     */
    public function getOrderDistributions()
    {
        return $this->hasMany(OrderDistribution::class, [
            'current_buyer_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['created_by' => 'id']);
    }

    /**
     * Gets query for [[ManagerOrders]].
     *
     * @return ActiveQuery
     */
    public function getManagerOrders()
    {
        return $this->hasMany(Order::class, ['manager_id' => 'id']);
    }

    /**
     * Gets query for [[BuyerOrders]].
     *
     * @return ActiveQuery
     */
    public function getBuyerOrders()
    {
        return $this->hasMany(Order::class, ['buyer_id' => 'id']);
    }

    /**
     * Gets query for [[Products]].
     *
     * @return ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::class, ['buyer_id' => 'id']);
    }

    /**
     * Gets query for [[UserLinkCategories]].
     *
     * @return ActiveQuery
     */
    public function getUserLinkCategories()
    {
        return $this->hasMany(UserLinkCategory::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Categories]].
     *
     * @return ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::class, [
            'id' => 'category_id',
        ])->via('userLinkCategories');
    }

    /**
     * Gets query for [[UserLinkTypeDeliveries]].
     *
     * @return ActiveQuery
     */
    public function getUserLinkTypeDeliveries()
    {
        return $this->hasMany(UserLinkTypeDelivery::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Delivery]].
     *
     * @return ActiveQuery
     */
    public function getDelivery()
    {
        return $this->hasMany(TypeDelivery::class, [
            'id' => 'type_delivery_id',
        ])->via('userLinkTypeDeliveries');
    }

    /**
     * Gets query for [[UserLinkTypePackagings]].
     *
     * @return ActiveQuery
     */
    public function getUserLinkTypePackagings()
    {
        return $this->hasMany(UserLinkTypePackaging::class, [
            'user_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[Packaging]].
     *
     * @return ActiveQuery
     */
    public function getPackaging()
    {
        return $this->hasMany(TypePackaging::class, [
            'id' => 'type_packaging_id',
        ])->via('userLinkTypePackagings');
    }

    /**
     * Gets query for [[UserSettings]].
     *
     * @return ActiveQuery
     */
    public function getUserSettings()
    {
        return $this->hasOne(UserSettings::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[UserVerificationRequestsApproving]].
     *
     * @return ActiveQuery
     */
    public function getUserVerificationRequestsApproving()
    {
        return $this->hasMany(UserVerificationRequest::class, [
            'accepted_by_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[UserVerificationRequestsCreator]].
     *
     * @return ActiveQuery
     */
    public function getUserVerificationRequestsCreator()
    {
        return $this->hasMany(UserVerificationRequest::class, [
            'created_by_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[UserVerificationRequestsManager]].
     *
     * @return ActiveQuery
     */
    public function getUserVerificationRequestsManager()
    {
        return $this->hasMany(UserVerificationRequest::class, [
            'manager_id' => 'id',
        ]);
    }
}
