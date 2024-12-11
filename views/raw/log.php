<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Логи системы</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Highlight.js CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/styles/default.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Highlight.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js"></script>

    <style>
        p {
            font-size: 12px !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Боковая панель -->
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Навигация</h5>
                    </div>
                    <div class="card-body">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                            <a class="nav-link active" id="system-logs-tab" data-bs-toggle="pill" href="#system-logs" role="tab">Системные логи</a>
                            <a class="nav-link" id="front-logs-tab" data-bs-toggle="pill" href="#front-logs" role="tab">Фронтенд логи</a>
                            <a class="nav-link" id="action-logs-tab" data-bs-toggle="pill" href="#action-logs" role="tab">Логи действий</a>
                            <a class="nav-link" id="models-tab" data-bs-toggle="pill" href="#models" role="tab">Модели</a>
                            <a class="nav-link" id="attachments-tab" data-bs-toggle="pill" href="#attachments" role="tab">Вложения</a>
                            <a class="nav-link" id="tables-tab" data-bs-toggle="pill" href="#tables" role="tab">Таблицы</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Основной контент -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Системные логи -->
                    <div class="tab-pane fade show active" id="system-logs" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Системные логи</h5>
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearLogs('system')">Очистить</button>
                            </div>
                            <div class="card-body">
                                <div class="log-content"><?= $logs ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Фронтенд логи -->
                    <div class="tab-pane fade" id="front-logs" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Фронтенд логи</h5>
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearLogs('front')">Очистить</button>
                            </div>
                            <div class="card-body">
                                <div class="log-content"><?= $frontLogs ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Логи действий -->
                    <div class="tab-pane fade" id="action-logs" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Логи действий</h5>
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearLogs('action')">Очистить</button>
                            </div>
                            <div class="card-body">
                                <div class="log-content"><?= $actionLogs ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Модели -->
                    <div class="tab-pane fade" id="models" role="tabpanel">
                        <div class="accordion" id="modelsAccordion">
                            <!-- Клиенты -->
                            <div class="card">
                                <div class="card-header" id="clientsHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#clientsCollapse">
                                            Клиенты (<?= count($clients) ?>)
                                        </button>
                                    </h2>
                                </div>
                                <div id="clientsCollapse" class="collapse" data-parent="#modelsAccordion">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Email</th>
                                                        <th>Создан</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($clients as $client): ?>
                                                        <tr>
                                                            <td><?= $client->id ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Менеджеры -->
                            <div class="card">
                                <div class="card-header" id="managersHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#managersCollapse">
                                            Менеджеры (<?= count($managers) ?>)
                                        </button>
                                    </h2>
                                </div>
                                <div id="managersCollapse" class="collapse" data-parent="#modelsAccordion">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Email</th>
                                                        <th>Создан</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($managers as $manager): ?>
                                                        <tr>
                                                            <td><?= $manager->id ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Фулфилмент -->
                            <div class="card">
                                <div class="card-header" id="fulfillmentHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#fulfillmentCollapse">
                                            Фулфилмент (<?= count($fulfillment) ?>)
                                        </button>
                                    </h2>
                                </div>
                                <div id="fulfillmentCollapse" class="collapse" data-parent="#modelsAccordion">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Email</th>
                                                        <th>Создан</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($fulfillment as $ff): ?>
                                                        <tr>
                                                            <td><?= $ff->id ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Покупатели -->
                            <div class="card">
                                <div class="card-header" id="buyersHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#buyersCollapse">
                                            Покупатели (<?= count($buyers) ?>)
                                        </button>
                                    </h2>
                                </div>
                                <div id="buyersCollapse" class="collapse" data-parent="#modelsAccordion">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Email</th>
                                                        <th>Создан</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($buyers as $buyer): ?>
                                                        <tr>
                                                            <td><?= $buyer->id ?></td>

                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Товары -->
                            <div class="card">
                                <div class="card-header" id="productsHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#productsCollapse">
                                            Товары (<?= count($products) ?>)
                                        </button>
                                    </h2>
                                </div>
                                <div id="productsCollapse" class="collapse" data-parent="#modelsAccordion">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Название</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($products as $product): ?>
                                                        <tr>
                                                            <td><?= $product->id ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Заказы -->
                            <div class="card">
                                <div class="card-header" id="ordersHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#ordersCollapse">
                                            Заказы (<?= count($orders) ?>)
                                        </button>
                                    </h2>
                                </div>
                                <div id="ordersCollapse" class="collapse" data-parent="#modelsAccordion">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Статус</th>
                                                        <th>Создан</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($orders as $order): ?>
                                                        <tr>
                                                            <td><?= $order->id ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Вложения -->
                    <div class="tab-pane fade" id="attachments" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Вложения</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php foreach ($attachments as $attachment): ?>
                                        <a href="<?= Url::to(['raw/download', 'file' => $attachment]) ?>" class="list-group-item list-group-item-action">

                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Таблицы -->
                    <div class="tab-pane fade" id="tables" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Таблицы базы данных</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php foreach ($tables as $table => $label): ?>
                                        <a href="<?= Url::to(['raw/table', 'name' => $table]) ?>" class="list-group-item list-group-item-action">
                                            <?= Html::encode($label) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .log-content {
            font-family: monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 500px;
            overflow-y: auto;
        }

        .nav-pills .nav-link.active {
            background-color: #007bff;
        }

        .card-header .btn-link {
            color: #007bff;
            text-decoration: none;
        }

        .card-header .btn-link:hover {
            color: #0056b3;
            text-decoration: none;
        }
    </style>

    <script>
        $(document).ready(function() {
            // Initialize tab functionality
            $('.nav-link').on('click', function(e) {
                e.preventDefault();
                $(this).tab('show');
            });

            // Initialize first tab
            $('.nav-link.active').tab('show');

            // Initialize highlight.js
            hljs.highlightAll();
        });

        function clearLogs(type) {
            if (confirm('Вы уверены, что хотите очистить логи?')) {
                $.post('<?= Url::to(['raw/clear-logs']) ?>', {
                    type: type
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Ошибка при очистке логов');
                    }
                });
            }
        }
    </script>
</body>

</html>