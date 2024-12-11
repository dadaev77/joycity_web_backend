<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/styles/default.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/highlight.min.js"></script>
    <script>
        hljs.highlightAll();
    </script>
    <style>
        p {
            font-size: 12px !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="row">
                    <div class="col-12">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <!--
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="backend-tab" data-bs-toggle="tab" data-bs-target="#backend" type="button" role="tab" aria-controls="backend" aria-selected="true">Backend Logs</button>
                            </li>
                            -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="frontend-tab" data-bs-toggle="tab" data-bs-target="#frontend" type="button" role="tab" aria-controls="frontend" aria-selected="false">Frontend Logs</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab" aria-controls="invoices" aria-selected="false">Action Logs</button>
                            </li>
                            <!-- Add tabs for buyers list, clients list, manager list, fulfillment list, and products -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">Users</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">Products</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">Orders</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="dropdb-tab" data-bs-toggle="tab" data-bs-target="#dropdb" type="button" role="tab" aria-controls="dropdb" aria-selected="false">Drop DB</button>
                            </li>
                        </ul>
                        <div class="tab-content py-4" id="myTabContent">
                            <!--
                            <div class="tab-pane fade show active" id="backend" role="tabpanel" aria-labelledby="backend-tab">
                                <div style="white-space: wrap; word-break: break-all; font-size: 12px;">
                                     < ? =  $logs   ? >
                                </div>
                            </div>
                            -->
                            <div class="tab-pane fade show active" id="frontend" role="tabpanel" aria-labelledby="frontend-tab">
                                <div style="white-space: wrap; word-break: break-all; font-size: 12px;" id="frontend-logs">
                                    <div class="mb-4">
                                        <a href="/raw/clear-frontend-logs" class="btn btn-primary btn-sm">clear frontend logs</a>
                                        
                                        <!-- Пагинация для фронтенд логов -->
                                        <div class="mt-3">
                                            <?php if ($frontLogsPages > 1): ?>
                                            <nav aria-label="Frontend logs pagination">
                                                <ul class="pagination pagination-sm">
                                                    <?php for ($i = 1; $i <= $frontLogsPages; $i++): ?>
                                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $i ?>&per_page=<?= $pageSize ?>"><?= $i ?></a>
                                                    </li>
                                                    <?php endfor; ?>
                                                </ul>
                                            </nav>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?= $frontLogs ?>
                                </div>
                            </div>
                            
                            <!-- Вкладка с пользователями -->
                            <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                                <div class="row">
                                    <!-- Клиенты -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Клиенты</h5>
                                            </div>
                                            <div class="card-body">
                                                <?= \yii\grid\GridView::widget([
                                                    'dataProvider' => $dataProviders['clients'],
                                                    'columns' => [
                                                        'id',
                                                        'username',
                                                        'email',
                                                        'created_at:datetime',
                                                        [
                                                            'class' => 'yii\grid\ActionColumn',
                                                            'template' => '{view}',
                                                            'buttons' => [
                                                                'view' => function ($url, $model) {
                                                                    return \yii\helpers\Html::a(
                                                                        '<i class="fas fa-eye"></i>',
                                                                        ['/user/view', 'id' => $model->id],
                                                                        ['class' => 'btn btn-sm btn-info']
                                                                    );
                                                                },
                                                            ],
                                                        ],
                                                    ],
                                                ]); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Менеджеры -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Менеджеры</h5>
                                            </div>
                                            <div class="card-body">
                                                <?= \yii\grid\GridView::widget([
                                                    'dataProvider' => $dataProviders['managers'],
                                                    'columns' => [
                                                        'id',
                                                        'username',
                                                        'email',
                                                        'created_at:datetime',
                                                        [
                                                            'class' => 'yii\grid\ActionColumn',
                                                            'template' => '{view}',
                                                        ],
                                                    ],
                                                ]); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Вкладка с товарами -->
                            <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Товары</h5>
                                    </div>
                                    <div class="card-body">
                                        <?= \yii\grid\GridView::widget([
                                            'dataProvider' => $dataProviders['products'],
                                            'columns' => [
                                                'id',
                                                'name',
                                                'price:currency',
                                                'created_at:datetime',
                                                [
                                                    'attribute' => 'status',
                                                    'format' => 'raw',
                                                    'value' => function($model) {
                                                        $statusClasses = [
                                                            'active' => 'success',
                                                            'inactive' => 'danger',
                                                            'pending' => 'warning'
                                                        ];
                                                        $class = $statusClasses[$model->status] ?? 'secondary';
                                                        return '<span class="badge bg-' . $class . '">' . $model->status . '</span>';
                                                    }
                                                ],
                                                [
                                                    'class' => 'yii\grid\ActionColumn',
                                                    'template' => '{view} {update}',
                                                ],
                                            ],
                                        ]); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Вкладка с заказами -->
                            <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Заказы</h5>
                                    </div>
                                    <div class="card-body">
                                        <?= \yii\grid\GridView::widget([
                                            'dataProvider' => $dataProviders['orders'],
                                            'columns' => [
                                                'id',
                                                [
                                                    'attribute' => 'user_id',
                                                    'value' => 'user.username',
                                                ],
                                                'total_amount:currency',
                                                'created_at:datetime',
                                                [
                                                    'attribute' => 'status',
                                                    'format' => 'raw',
                                                    'value' => function($model) {
                                                        $statusClasses = [
                                                            'new' => 'info',
                                                            'processing' => 'warning',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $class = $statusClasses[$model->status] ?? 'secondary';
                                                        return '<span class="badge bg-' . $class . '">' . $model->status . '</span>';
                                                    }
                                                ],
                                                [
                                                    'class' => 'yii\grid\ActionColumn',
                                                    'template' => '{view} {update}',
                                                ],
                                            ],
                                        ]); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                                <div class="row">
                                    <style>
                                        p {
                                            margin-bottom: 0 !important;
                                        }
                                    </style>
                                    <div class="col">
                                        <?= $actionLogs ?>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="dropdb" role="tabpanel" aria-labelledby="dropdb-tab">
                                <div class="container">
                                    <div class="row">
                                        <div class="col-12 mb-4">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h5 class="card-title">Delete Twilio Chats</h5>
                                                    <button id="deleteTwilioChatsBtn" class="btn btn-danger">Delete All Twilio Chats</button>
                                                    <div id="twilioProgressContainer" class="mt-3 d-none">
                                                        <div class="progress mb-2">
                                                            <div id="twilioProgressBar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div id="twilioProgressMessages"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <form id="truncateForm" class="mt-3">
                                                <div class="card">
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <h5 class="mb-0">Select Tables to Truncate</h5>
                                                        <div>
                                                            <button type="button" class="btn btn-secondary btn-sm me-2" onclick="selectAllTables()">Select All</button>
                                                            <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAllTables()">Deselect All</button>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row g-3">
                                                            <?php foreach ($tables as $table) : ?>
                                                                <div class="col-lg-4 col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input table-checkbox" type="checkbox" name="tables[]" value="<?= $table ?>" id="table-<?= $table ?>">
                                                                        <label class="form-check-label" for="table-<?= $table ?>">
                                                                            <?= $table ?>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <div class="card-footer">
                                                        <button type="submit" class="btn btn-danger" id="truncateBtn">
                                                            Truncate Selected Tables
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <!-- Results Area -->
                                            <div id="truncateResults" class="mt-4" style="display: none;">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">Operation Results</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div id="progressArea">
                                                            <div class="progress mb-3">
                                                                <div id="truncateProgress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                            </div>
                                                        </div>
                                                        <div id="resultsLog" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    function selectAllTables() {
                                        document.querySelectorAll('.table-checkbox').forEach(checkbox => {
                                            checkbox.checked = true;
                                        });
                                    }

                                    function deselectAllTables() {
                                        document.querySelectorAll('.table-checkbox').forEach(checkbox => {
                                            checkbox.checked = false;
                                        });
                                    }

                                    function addLogMessage(message, type = 'info') {
                                        const resultsLog = document.getElementById('resultsLog');
                                        const messageDiv = document.createElement('div');
                                        messageDiv.className = `alert alert-${type} mb-2 py-1`;
                                        messageDiv.textContent = message;
                                        resultsLog.appendChild(messageDiv);
                                        resultsLog.scrollTop = resultsLog.scrollHeight;
                                    }

                                    document.getElementById('truncateForm').addEventListener('submit', async function(e) {
                                        e.preventDefault();

                                        const selectedTables = Array.from(document.querySelectorAll('.table-checkbox:checked')).map(cb => cb.value);
                                        if (selectedTables.length === 0) {
                                            alert('Please select at least one table');
                                            return;
                                        }

                                        if (!confirm('Are you sure you want to truncate selected tables? This action cannot be undone.')) {
                                            return;
                                        }

                                        const truncateBtn = document.getElementById('truncateBtn');
                                        const resultsArea = document.getElementById('truncateResults');
                                        const progressBar = document.getElementById('truncateProgress');

                                        truncateBtn.disabled = true;
                                        resultsArea.style.display = 'block';
                                        document.getElementById('resultsLog').innerHTML = '';

                                        try {
                                            for (let i = 0; i < selectedTables.length; i++) {
                                                const table = selectedTables[i];
                                                const progress = Math.round(((i + 1) / selectedTables.length) * 100);

                                                try {
                                                    const response = await fetch('/raw/truncate-table', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                        },
                                                        body: JSON.stringify({
                                                            table: table
                                                        })
                                                    });

                                                    const result = await response.json();

                                                    if (result.success) {
                                                        addLogMessage(`✓ Table ${table} truncated successfully`, 'success');
                                                    } else {
                                                        addLogMessage(`✗ Error truncating ${table}: ${result.error}`, 'danger');
                                                    }
                                                } catch (error) {
                                                    addLogMessage(`✗ Failed to truncate ${table}: ${error.message}`, 'danger');
                                                }

                                                progressBar.style.width = `${progress}%`;
                                                progressBar.textContent = `${progress}%`;
                                            }
                                        } finally {
                                            truncateBtn.disabled = false;
                                            addLogMessage('Operation completed', 'info');
                                        }
                                    });
                                </script>
                                <script>
                                    document.getElementById('deleteTwilioChatsBtn').addEventListener('click', async function() {
                                        if (!confirm('Are you sure you want to delete all Twilio chats? This action cannot be undone.')) {
                                            return;
                                        }

                                        const progressContainer = document.getElementById('twilioProgressContainer');
                                        const progressBar = document.getElementById('twilioProgressBar');
                                        const messagesContainer = document.getElementById('twilioProgressMessages');
                                        this.disabled = true;
                                        progressContainer.classList.remove('d-none');
                                        messagesContainer.innerHTML = '';

                                        try {
                                            // Start the deletion process
                                            const response = await fetch('/raw/delete-twilio-chats', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                }
                                            });
                                            const data = await response.json();
                                            
                                            if (!data.success) {
                                                throw new Error(data.error || 'Failed to start deletion process');
                                            }

                                            // Connect to SSE for progress updates
                                            const eventSource = new EventSource('/raw/twilio-deletion-progress');
                                            
                                            eventSource.onmessage = function(event) {
                                                const data = JSON.parse(event.data);
                                                
                                                if (data.progress !== undefined) {
                                                    progressBar.style.width = data.progress + '%';
                                                    progressBar.setAttribute('aria-valuenow', data.progress);
                                                }
                                                
                                                if (data.message) {
                                                    const messageDiv = document.createElement('div');
                                                    messageDiv.className = 'alert alert-' + (data.type || 'info') + ' mt-2';
                                                    messageDiv.textContent = data.message;
                                                    messagesContainer.prepend(messageDiv);
                                                }
                                                
                                                if (data.completed) {
                                                    eventSource.close();
                                                    document.getElementById('deleteTwilioChatsBtn').disabled = false;
                                                }
                                            };
                                            
                                            eventSource.onerror = function() {
                                                eventSource.close();
                                                document.getElementById('deleteTwilioChatsBtn').disabled = false;
                                                const messageDiv = document.createElement('div');
                                                messageDiv.className = 'alert alert-danger mt-2';
                                                messageDiv.textContent = 'Connection lost. Please try again.';
                                                messagesContainer.prepend(messageDiv);
                                            };
                                        } catch (error) {
                                            const messageDiv = document.createElement('div');
                                            messageDiv.className = 'alert alert-danger mt-2';
                                            messageDiv.textContent = error.message;
                                            messagesContainer.prepend(messageDiv);
                                            this.disabled = false;
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>