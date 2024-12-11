<!DOCTYPE html>
<?php

use app\models\Order;
?>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Yii::$app->request->getCsrfToken() ?>">
    <title>Системные логи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
    <style>
        .log-container {
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow-x: auto;
            margin-bottom: 20px;
            max-height: 600px;
            overflow-y: auto;
        }

        .log-container pre {
            margin: 0;
            white-space: pre-wrap;
        }

        .log-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            padding: 10px 20px;
            transition: all 0.3s;
        }

        .log-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background: none;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .json {
            background: #2d2d2d;
            padding: 8px;
            border-radius: 4px;
            margin: 4px 0;
        }

        .text-danger {
            color: #ff6b6b !important;
        }

        .text-warning {
            color: #ffd93d !important;
        }

        .text-info {
            color: #4dabf7 !important;
        }

        .text-secondary {
            color: #868e96 !important;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="log-header">
            <h2>Системные логи</h2>
            <div class="btn-group">
                <!-- <button class="btn btn-outline-danger" id="clearCurrentLog">
                    <i class="fa fa-trash"></i>
                </button> -->
            </div>
        </div>

        <ul class="nav nav-tabs log-tabs mb-3" id="logTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="system-tab" data-bs-toggle="tab" href="#system" role="tab">
                    <i class="fa fa-cogs"></i> Системные логи
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="front-tab" data-bs-toggle="tab" href="#front" role="tab">
                    <i class="fa fa-desktop"></i> Фронтенд логи
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="action-tab" data-bs-toggle="tab" href="#action" role="tab">
                    <i class="fa fa-list"></i> Логи действий
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="models-tab" data-bs-toggle="tab" href="#models" role="tab">
                    <i class="fa fa-database"></i> Модели
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="database-cleanup-tab" data-bs-toggle="tab" href="#database-cleanup" role="tab">
                    <i class="fa fa-trash"></i> Очистка базы данных
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="attachments-tab" data-bs-toggle="tab" href="#attachments" role="tab">
                    <i class="fa fa-paperclip"></i> Вложения
                </a>
            </li>
        </ul>

        <div class="tab-content" id="logTabContent">
            <div class="tab-pane fade show active" id="system" role="tabpanel">
                <div class="log-container" id="systemLogs">
                    <?= $logs ?>
                </div>
            </div>
            <div class="tab-pane fade" id="front" role="tabpanel">
                <div class="log-container" id="frontLogs">
                    <?= $frontLogs ?>
                </div>
            </div>
            <div class="tab-pane fade" id="action" role="tabpanel">
                <div class="log-container" id="actionLogs">
                    <?= $actionLogs ?>
                </div>
            </div>
            <div class="tab-pane fade" id="models" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="orders-tab" data-bs-toggle="tab" href="#orders-content" role="tab">
                                    <i class="fa fa-shopping-cart"></i> Заказы
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="products-tab" data-bs-toggle="tab" href="#products-content" role="tab">
                                    <i class="fa fa-cube"></i> Товары
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="users-tab" data-bs-toggle="tab" href="#users-content" role="tab">
                                    <i class="fa fa-users"></i> Пользователи
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="waybills-tab" data-bs-toggle="tab" href="#waybills-content" role="tab">
                                    <i class="fa fa-file-text"></i> Накладные
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="buyer-offers-tab" data-bs-toggle="tab" href="#buyer-offers-content" role="tab">
                                    <i class="fa fa-handshake-o"></i> Предложения байера
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="buyer-delivery-offers-tab" data-bs-toggle="tab" href="#buyer-delivery-offers-content" role="tab">
                                    <i class="fa fa-truck"></i> Предложения о доставке
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Orders Tab -->
                            <div class="tab-pane fade show active" id="orders-content" role="tabpanel">
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Статус</th>
                                                <th>Дата создания</th>
                                                <th>Товар</th>
                                                <th>Цена за ед.</th>
                                                <th>Кол-во</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($orders)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-3">Нет данных</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td><?= $order->id ?></td>
                                                        <td><span class="badge bg-<?= $order->status === Order::STATUS_COMPLETED ? 'success' : ($order->status === Order::STATUS_CREATED ? 'warning' : 'secondary') ?>"><?= $order->status ?></span></td>
                                                        <td><?= Yii::$app->formatter->asDatetime($order->created_at, 'php:d.m.Y H:i') ?></td>
                                                        <td><?= \yii\helpers\Html::encode($order->product_name_ru) ?></td>
                                                        <td><?= Yii::$app->formatter->asCurrency($order->expected_price_per_item) ?></td>
                                                        <td><?= $order->expected_quantity ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#order-details-<?= $order->id ?>">
                                                                <i class="fa fa-info-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="collapse" id="order-details-<?= $order->id ?>">
                                                        <td colspan="7">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Атрибуты</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <?php foreach ($tables['order'] as $column => $info): ?>
                                                                            <li class="list-group-item py-1">
                                                                                <small>
                                                                                    <strong><?= $column ?></strong>
                                                                                    <span class="badge bg-secondary float-end"><?= $info->type ?></span>
                                                                                </small>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Зависимости</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> User (buyer_id)
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> Product (product_id)
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Products Tab -->
                            <div class="tab-pane fade" id="products-content" role="tabpanel">
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Название</th>
                                                <th>Категория</th>
                                                <th>Цена</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($products)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-3">Нет данных</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($products as $product): ?>
                                                    <tr>
                                                        <td><?= $product->id ?></td>
                                                        <td><?= \yii\helpers\Html::encode($product->name) ?></td>
                                                        <td><?= $product->category ? \yii\helpers\Html::encode($product->category->name) : '<span class="text-muted">-</span>' ?></td>
                                                        <td><?= Yii::$app->formatter->asCurrency($product->price) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#product-details-<?= $product->id ?>">
                                                                <i class="fa fa-info-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="collapse" id="product-details-<?= $product->id ?>">
                                                        <td colspan="5">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Атрибуты</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <?php foreach ($tables['product'] as $column => $info): ?>
                                                                            <li class="list-group-item py-1">
                                                                                <small>
                                                                                    <strong><?= $column ?></strong>
                                                                                    <span class="badge bg-secondary float-end"><?= $info->type ?></span>
                                                                                </small>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Зависимости</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> Category
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> Order
                                                                                <span class="badge bg-success float-end">hasMany</span>
                                                                            </small>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Users Tab -->
                            <div class="tab-pane fade" id="users-content" role="tabpanel">
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Имя</th>
                                                <th>Email</th>
                                                <th>Роль</th>
                                                <th>Статус</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($users)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-3">Нет данных</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($users as $user): ?>
                                                    <tr>
                                                        <td><?= $user->id ?></td>
                                                        <td><?= \yii\helpers\Html::encode($user->name) ?></td>
                                                        <td><?= \yii\helpers\Html::encode($user->email) ?></td>
                                                        <td><span class="badge bg-<?= $user->role === 'admin' ? 'danger' : ($user->role === 'manager' ? 'primary' : 'secondary') ?>"><?= $user->role ?></span></td>
                                                        <td>
                                                            <?php if ($user->is_deleted): ?>
                                                                <span class="badge bg-danger">Удален</span>
                                                            <?php elseif ($user->is_verified): ?>
                                                                <span class="badge bg-success">Активен</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Не верифицирован</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#user-details-<?= $user->id ?>">
                                                                <i class="fa fa-info-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="collapse" id="user-details-<?= $user->id ?>">
                                                        <td colspan="6">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Атрибуты</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <?php foreach ($tables['user'] as $column => $info): ?>
                                                                            <li class="list-group-item py-1">
                                                                                <small>
                                                                                    <strong><?= $column ?></strong>
                                                                                    <span class="badge bg-secondary float-end"><?= $info->type ?></span>
                                                                                </small>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Зависимости</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> Order
                                                                                <span class="badge bg-success float-end">hasMany</span>
                                                                            </small>
                                                                        </li>
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> UserSettings
                                                                                <span class="badge bg-info float-end">hasOne</span>
                                                                            </small>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Waybills Tab -->
                            <div class="tab-pane fade" id="waybills-content" role="tabpanel">
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Дата</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($waybills)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">Нет данных</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($waybills as $waybill): ?>
                                                    <tr>
                                                        <td><?= $waybill->id ?></td>
                                                        <td><?= Yii::$app->formatter->asDate($waybill->created_at) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#waybill-details-<?= $waybill->id ?>">
                                                                <i class="fa fa-info-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="collapse" id="waybill-details-<?= $waybill->id ?>">
                                                        <td colspan="4">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Атрибуты</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <?php foreach ($tables['waybill'] as $column => $info): ?>
                                                                            <li class="list-group-item py-1">
                                                                                <small>
                                                                                    <strong><?= $column ?></strong>
                                                                                    <span class="badge bg-secondary float-end"><?= $info->type ?></span>
                                                                                </small>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Зависимости</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> Order
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> User (created_by)
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Buyer Offers Tab -->
                            <div class="tab-pane fade" id="buyer-offers-content" role="tabpanel">
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Номер</th>
                                                <th>Дата</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($buyerOffers)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">Нет данных</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($buyerOffers as $offer): ?>
                                                    <tr>
                                                        <td><?= $offer->id ?></td>
                                                        <td><?= $offer->number ?></td>
                                                        <td><?= Yii::$app->formatter->asDate($offer->created_at) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#buyer-offer-details-<?= $offer->id ?>">
                                                                <i class="fa fa-info-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="collapse" id="buyer-offer-details-<?= $offer->id ?>">
                                                        <td colspan="4">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Атрибуты</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <?php foreach ($tables['buyerOffer'] as $column => $info): ?>
                                                                            <li class="list-group-item py-1">
                                                                                <small>
                                                                                    <strong><?= $column ?></strong>
                                                                                    <span class="badge bg-secondary float-end"><?= $info->type ?></span>
                                                                                </small>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Зависимости</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> Order
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> User (created_by)
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Buyer Delivery Offers Tab -->
                            <div class="tab-pane fade" id="buyer-delivery-offers-content" role="tabpanel">
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Номер</th>
                                                <th>Дата</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($buyerDeliveryOffers)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">Нет данных</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($buyerDeliveryOffers as $offer): ?>
                                                    <tr>
                                                        <td><?= $offer->id ?></td>
                                                        <td><?= $offer->number ?></td>
                                                        <td><?= Yii::$app->formatter->asDate($offer->created_at) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#buyer-delivery-offer-details-<?= $offer->id ?>">
                                                                <i class="fa fa-info-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="collapse" id="buyer-delivery-offer-details-<?= $offer->id ?>">
                                                        <td colspan="4">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Атрибуты</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <?php foreach ($tables['buyerDeliveryOffer'] as $column => $info): ?>
                                                                            <li class="list-group-item py-1">
                                                                                <small>
                                                                                    <strong><?= $column ?></strong>
                                                                                    <span class="badge bg-secondary float-end"><?= $info->type ?></span>
                                                                                </small>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Зависимости</h6>
                                                                    <ul class="list-group list-group-flush">
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> Order
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                        <li class="list-group-item py-1">
                                                                            <small>
                                                                                <i class="fa fa-link"></i> User (created_by)
                                                                                <span class="badge bg-info float-end">belongsTo</span>
                                                                            </small>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Таб очистки базы данных -->
            <div class="tab-pane fade" id="database-cleanup" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Очистка базы данных</h5>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            Внимание! Эта операция необратима. Выберите таблицы, которые нужно очистить.
                        </div>

                        <form id="cleanup-form">
                            <div class="row mb-3">
                                <?php
                                $tablesPerColumn = ceil(count($allowedTables) / 3);
                                $tableColumns = array_chunk($allowedTables, $tablesPerColumn);
                                foreach ($tableColumns as $columnTables):
                                ?>
                                    <div class="col-md-4">
                                        <?php foreach ($columnTables as $table): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="tables[]" value="<?= $table ?>" id="check-<?= $table ?>">
                                                <label class="form-check-label" for="check-<?= $table ?>">
                                                    <?= $table ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="progress mb-3 d-none" id="cleanup-progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                            </div>

                            <div id="cleanup-results" class="mb-3"></div>

                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-trash"></i> Очистить выбранные таблицы
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Таб вложений -->
            <div class="tab-pane fade" id="attachments" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Вложения</h5>
                        <?php if (empty($attachments)): ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Нет доступных вложений
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Имя файла</th>
                                            <th>Размер</th>
                                            <th>Дата изменения</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attachments as $attachment):
                                            $filePath = Yii::getAlias('@webroot/attachments/' . $attachment);
                                            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                                            $modifiedTime = file_exists($filePath) ? filemtime($filePath) : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <i class="fa fa-file-o"></i>
                                                    <?= \yii\helpers\Html::encode($attachment) ?>
                                                </td>
                                                <td><?= Yii::$app->formatter->asShortSize($fileSize) ?></td>
                                                <td><?= Yii::$app->formatter->asDatetime($modifiedTime, 'php:d.m.Y H:i') ?></td>
                                                <td>
                                                    <a href="/attachments/<?= urlencode($attachment) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentLogType = 'system';

            // Обработка переключения вкладок
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    const newType = this.id.replace('-tab', '');
                    const isModelTab = this.closest('.card-header-tabs') !== null;

                    if (!isModelTab) {
                        currentLogType = newType;
                        if (currentLogType !== 'models' && currentLogType !== 'database-cleanup' && currentLogType !== 'attachments') {
                            refreshLogs();
                        }
                    }
                });
            });

            // Обновление логов
            async function refreshLogs() {
                try {
                    const response = await fetch(`/log/get?type=${currentLogType}`);
                    const data = await response.json();

                    if (data.success) {
                        const logsContainer = document.querySelector(`#${currentLogType}Logs`);
                        if (logsContainer) {
                            logsContainer.innerHTML = data.data;
                        }
                    } else {
                        throw new Error(data.error || 'Ошибка загрузки логов');
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                    showError('Произошла ошибка при загрузке логов');
                }
            }

            // Очистка текущего лога
            document.getElementById('clearCurrentLog').addEventListener('click', async function() {
                if (currentLogType === 'models') {
                    return;
                }

                try {
                    const response = await fetch(`/log/clear`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `type=${currentLogType}`
                    });
                    const data = await response.json();

                    if (data.success) {
                        showSuccess('Логи успешно очищены');
                        refreshLogs();
                    } else {
                        throw new Error(data.error || 'Ошибка при очистке логов');
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                    showError('Произошла ошибка при очистке логов');
                }
            });

            // Показ ошибки
            function showError(message) {
                Toastify({
                    text: message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    style: {
                        background: "#dc3545",
                    }
                }).showToast();
            }

            // Показ успешного сообщения
            function showSuccess(message) {
                Toastify({
                    text: message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    style: {
                        background: "#198754",
                    }
                }).showToast();
            }

            // Начальная загрузка логов
            refreshLogs();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Обработчик формы очистки базы данных
            const cleanupForm = document.getElementById('cleanup-form');
            const cleanupProgress = document.getElementById('cleanup-progress');
            const cleanupResults = document.getElementById('cleanup-results');

            if (cleanupForm) {
                cleanupForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    // Получаем выбранные таблицы
                    const formData = new FormData(cleanupForm);
                    const selectedTables = formData.getAll('tables[]');

                    if (selectedTables.length === 0) {
                        alert('Выберите хотя бы одну таблицу для очистки');
                        return;
                    }

                    if (!confirm('Вы уверены, что хотите очистить выбранные таблицы? Это действие необратимо!')) {
                        return;
                    }

                    // Показываем прогресс
                    cleanupProgress.classList.remove('d-none');
                    cleanupResults.innerHTML = '';
                    cleanupForm.querySelector('button[type="submit"]').disabled = true;

                    const results = [];

                    // Очищаем каждую таблицу последовательно
                    for (const table of selectedTables) {
                        try {
                            const response = await fetch('/log/truncate-table', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    table: table
                                })
                            });
                            const data = await response.json();
                            results.push({
                                table: table,
                                success: data.success,
                                message: data.message || (data.success ? 'Таблица очищена' : 'Ошибка при очистке')
                            });
                        } catch (error) {
                            results.push({
                                table: table,
                                success: false,
                                message: `Ошибка: ${error.message}`
                            });
                        }
                    }

                    // Скрываем прогресс и показываем результаты
                    cleanupProgress.classList.add('d-none');
                    cleanupForm.querySelector('button[type="submit"]').disabled = false;

                    // Формируем отчет
                    let html = '<div class="alert alert-success">Процесс очистки завершен</div><ul class="list-group">';
                    results.forEach(result => {
                        const alertClass = result.success ? 'success' : 'danger';
                        html += `
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>${result.table}</span>
                                    <span class="badge bg-${alertClass}">${result.message}</span>
                                </div>
                            </li>
                        `;
                    });
                    html += '</ul>';
                    cleanupResults.innerHTML = html;
                });
            }
        });
    </script>
</body>

</html>