<!DOCTYPE html>
<html class="dark">

<head>
    <title>Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/json.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .btn {
                @apply px-4 py-2 rounded-lg font-medium transition-all duration-200 ease-in-out shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800;
            }
            .btn-primary {
                @apply bg-gradient-to-r from-primary-500 to-primary-600 text-white hover:from-primary-600 hover:to-primary-700 focus:ring-primary-500;
            }
            .btn-secondary {
                @apply bg-gradient-to-r from-gray-500 to-gray-600 text-white hover:from-gray-600 hover:to-gray-700 focus:ring-gray-500;
            }
            .tab {
                @apply inline-flex items-center px-4 py-2 border-b-2 font-medium text-sm transition-colors duration-200 ease-in-out;
            }
            .tab-active {
                @apply border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400;
            }
            .tab-inactive {
                @apply border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300;
            }
            .log-entry {
                @apply mb-2 p-2 rounded font-mono text-sm;
            }
            .log-entry-error {
                @apply bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200;
            }
            .log-entry-warning {
                @apply bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200;
            }
            .log-entry-info {
                @apply bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200;
            }
            .log-entry-debug {
                @apply bg-gray-50 dark:bg-gray-900/30 text-gray-800 dark:text-gray-200;
            }
            .log-timestamp {
                @apply text-xs text-gray-500 dark:text-gray-400;
            }
        }
    </style>
</head>

<body class="bg-gray-100 dark:bg-gray-900 p-6 min-h-screen transition-colors duration-200">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg rounded-lg mb-6 p-4 transition-colors duration-200">
        <div class="flex flex-wrap gap-3">
            <button onclick="showSection('logs')" class="btn btn-primary">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Logs
            </button>
            <button onclick="showSection('models')" class="btn btn-primary">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
                Models
            </button>
            <button onclick="showSection('attachments')" class="btn btn-primary">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                Attachments
            </button>
        </div>
    </nav>

    <!-- Logs Section -->
    <div id="logs" class="section bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 mb-6 transition-colors duration-200">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">System Logs</h2>

        <!-- Logs Navigation Tabs -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <ul class="flex flex-wrap -mb-px" role="tablist">
                <li class="mr-2">
                    <button onclick="showLogTab('app')" class="tab tab-active" role="tab">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Application
                    </button>
                </li>
                <li class="mr-2">
                    <button onclick="showLogTab('front')" class="tab tab-inactive" role="tab">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Frontend
                    </button>
                </li>
                <li class="mr-2">
                    <button onclick="showLogTab('action')" class="tab tab-inactive" role="tab">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Actions
                    </button>
                </li>
                <li>
                    <button onclick="showLogTab('profiling')" class="tab tab-inactive" role="tab">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        SQL Profiling
                    </button>
                </li>
            </ul>
        </div>

        <!-- Logs Content -->
        <div class="space-y-4">
            <div id="log-app" class="log-tab bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                <div id="app-logs-container"></div>
            </div>

            <div id="log-front" class="log-tab bg-gray-50 dark:bg-gray-700 p-4 rounded-lg hidden">
                <div id="front-logs-container"></div>
            </div>

            <div id="log-action" class="log-tab bg-gray-50 dark:bg-gray-700 p-4 rounded-lg hidden">
                <div id="action-logs-container"></div>
            </div>

            <div id="log-profiling" class="log-tab bg-gray-50 dark:bg-gray-700 p-4 rounded-lg hidden">
                <div id="profiling-logs-container"></div>
            </div>
        </div>
    </div>

    <!-- Models Section -->
    <div id="models" class="section bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 mb-6 hidden transition-colors duration-200">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Models</h2>

        <!-- Model Selection -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <ul class="flex flex-wrap -mb-px" role="tablist">
                <li class="mr-2">
                    <button onclick="showModelTab('users')" class="tab tab-active" role="tab">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Users
                    </button>
                </li>
                <li class="mr-2">
                    <button onclick="showModelTab('products')" class="tab tab-inactive" role="tab">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Products
                    </button>
                </li>
                <li>
                    <button onclick="showModelTab('orders')" class="tab tab-inactive" role="tab">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Orders
                    </button>
                </li>
            </ul>
        </div>

        <!-- Users Tab -->
        <div id="model-users" class="model-tab">
            <!-- User Type Selection -->
            <div class="mb-4">
                <select id="userTypeSelect" onchange="filterUserType()" class="w-full p-2.5 text-sm rounded-lg border bg-gray-50 border-gray-300 text-gray-900 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all">All Users</option>
                    <option value="client">Clients</option>
                    <option value="manager">Managers</option>
                    <option value="fulfillment">Fulfillment</option>
                    <option value="buyer">Buyers</option>
                </select>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach (array_merge($clients, $managers, $fulfillment, $buyers) as $user): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 user-row transition-colors duration-150" data-role="<?= $user->role ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= $user->id ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= $user->name ?> <?= $user->surname ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= $user->email ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= $user->phone_number ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= $user->role ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user->is_deleted ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' ?>">
                                        <?= $user->is_deleted ? 'Deleted' : 'Active' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Products Tab -->
        <div id="model-products" class="model-tab hidden">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Цена</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Остаток</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300"><?= $product->id ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300"><?= htmlspecialchars($product->name_ru) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Orders Tab -->
        <div id="model-orders" class="model-tab hidden">
            <h2 class="text-xl font-semibold mb-4 dark:text-white">Последние заявки</h2>
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Клиент</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Создан</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300"><?= $order->id ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300"><?= $order->status ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300"><?= $order->created_by ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300"><?= $order->created_at ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Attachments Section -->
    <div id="attachments" class="section bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 mb-6 hidden transition-colors duration-200">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Attachments</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($attachments as $file): ?>
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400 dark:text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <span class="text-sm text-gray-900 dark:text-gray-100"><?= $file ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Show/hide main sections
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.add('hidden');
            });
            document.getElementById(sectionId).classList.remove('hidden');
        }

        // Show/hide log tabs
        function showLogTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.log-tab').forEach(tab => {
                tab.classList.add('hidden');
            });
            // Show selected tab
            document.getElementById('log-' + tabId).classList.remove('hidden');

            // Update active tab styling
            const buttons = document.querySelectorAll('[role="tab"]');
            buttons.forEach(button => {
                button.classList.remove('tab-active');
                button.classList.add('tab-inactive');
            });

            // Find and activate the clicked button
            if (event && event.currentTarget) {
                event.currentTarget.classList.remove('tab-inactive');
                event.currentTarget.classList.add('tab-active');
            } else {
                const button = document.querySelector(`button[onclick="showLogTab('${tabId}')"]`);
                if (button) {
                    button.classList.remove('tab-inactive');
                    button.classList.add('tab-active');
                }
            }

            // Update URL hash without triggering reload
            if (history.pushState) {
                history.pushState(null, null, '#' + tabId);
            } else {
                location.hash = '#' + tabId;
            }
        }

        // Show/hide model tabs
        function showModelTab(tabId) {
            document.querySelectorAll('.model-tab').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.getElementById('model-' + tabId).classList.remove('hidden');

            // Update active tab styling
            const buttons = document.querySelectorAll('[role="tab"]');
            buttons.forEach(button => {
                button.classList.remove('tab-active');
                button.classList.add('tab-inactive');
            });
            event.currentTarget.classList.remove('tab-inactive');
            event.currentTarget.classList.add('tab-active');
        }

        // Filter users by type
        function filterUserType() {
            const selectedType = document.getElementById('userTypeSelect').value;
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                if (selectedType === 'all' || row.dataset.role === selectedType) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        }

        // Dark mode toggle
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            const icon = document.getElementById('darkModeIcon');
            icon.textContent = document.documentElement.classList.contains('dark') ? '☀��' : '🌙';
        }

        // Функция для форматирования фронтенд логов
        function formatFrontendLogs(logs, containerId) {
            const container = document.getElementById(containerId);
            if (!logs || !container) return;

            // Разбиваем на отдельные записи по шаблону временной метки
            const logEntries = logs.match(/\[-\]\[-\]\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\]\[-\]\[-\][\s\S]*?(?=\[-\]\[-\]\[|$)/g) || [];

            container.innerHTML = logEntries.map(entry => {
                // Извлекаем временную метку
                const timestampMatch = entry.match(/\[-\]\[-\]\[([^\]]+)\]\[-\]\[-\]/);
                const timestamp = timestampMatch ? timestampMatch[1] : '';

                // Извлекаем JSON из pre тега
                const jsonMatch = entry.match(/<pre class="format">([\s\S]*?)<\/pre>/);
                let content = '';
                let logClass = 'log-entry-info';

                if (jsonMatch) {
                    try {
                        const jsonData = JSON.parse(jsonMatch[1]);
                        logClass = jsonData.error ? 'log-entry-error' : 'log-entry-info';
                        content = `
                            <div class="flex flex-col gap-2">
                                ${jsonData.application ? `<div><strong>Application:</strong> ${jsonData.application}</div>` : ''}
                                ${jsonData.url ? `<div><strong>URL:</strong> ${jsonData.url}</div>` : ''}
                                ${jsonData.error ? `<div><strong>Error:</strong> ${jsonData.error}</div>` : ''}
                                ${jsonData.request ? `
                                    <div>
                                        <strong>Request:</strong>
                                        <pre><code class="language-json">${JSON.stringify(JSON.parse(jsonData.request), null, 2)}</code></pre>
                                    </div>
                                ` : ''}
                                ${jsonData.response ? `
                                    <div>
                                        <strong>Response:</strong>
                                        <pre><code class="language-json">${JSON.stringify(jsonData.response, null, 2)}</code></pre>
                                    </div>
                                ` : ''}
                            </div>`;
                    } catch (e) {
                        logClass = 'log-entry-error';
                        content = `<div class="text-red-500">Error parsing JSON: ${e.message}</div>
                                 <div class="mt-2"><pre><code class="language-json">${jsonMatch[1]}</code></pre></div>`;
                    }
                } else {
                    content = entry;
                }

                return `
                    <div class="log-entry ${logClass} mb-4">
                        <div class="log-timestamp mb-2">${timestamp}</div>
                        ${content}
                    </div>`;
            }).join('');

            // Подсвечиваем синтаксис в отформатированных логах
            container.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        }

        // Функция для форматирования логов приложения
        function formatAppLogs(logs, containerId) {
            const container = document.getElementById(containerId);
            if (!logs || !container) return;

            // Разбиваем на отдельные записи по шаблону временной метки
            const logEntries = logs.match(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \[[^\]]+\](?:(?!\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \[)[\s\S])*/g) || [];

            container.innerHTML = logEntries.map(entry => {
                // Извлекаем основные компоненты лога
                const timestampMatch = entry.match(/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/);
                const ipMatch = entry.match(/\[([\d\.]+)\]/);
                const categoryMatch = entry.match(/\[(error|info|warning|debug)\]/i);
                const errorTypeMatch = entry.match(/\[([\w\\]+:[^\]]+)\]/);

                const timestamp = timestampMatch ? timestampMatch[1] : '';
                const ip = ipMatch ? ipMatch[1] : '';
                const category = categoryMatch ? categoryMatch[1].toLowerCase() : 'info';
                const errorType = errorTypeMatch ? errorTypeMatch[1] : '';

                let content = entry;
                let stackTrace = '';
                let variables = '';

                // Обработка стек-трейса
                if (entry.includes('Stack trace:')) {
                    const parts = entry.split('Stack trace:');
                    content = parts[0];
                    stackTrace = parts[1].split(/\$_[A-Z]+/)[0];
                }

                // Обработка переменных окружения
                const varsMatch = entry.match(/\$_[A-Z]+ = \[([\s\S]*?)(?=\$_[A-Z]+ = \[|$)/g);
                if (varsMatch) {
                    variables = varsMatch.map(varBlock => {
                        const varName = varBlock.match(/\$_([A-Z]+)/)[1];
                        let varContent = varBlock.replace(/\$_[A-Z]+ = /, '').trim();
                        try {
                            // Пытаемся отформатировать как объект
                            varContent = varContent
                                .replace(/\[[\s\S]*?\]/, match => {
                                    const obj = {};
                                    const lines = match.slice(1, -1).trim().split('\n');
                                    lines.forEach(line => {
                                        if (line.trim()) {
                                            const [key, ...value] = line.trim().split('=>').map(s => s.trim());
                                            obj[key.replace(/^'|'$/g, '')] = value.join('=>');
                                        }
                                    });
                                    return JSON.stringify(obj, null, 4);
                                });
                        } catch (e) {
                            // Если не удалось распарсить, оставляем как есть
                        }
                        return `<div class="mb-2">
                            <strong>$_${varName}</strong>
                            <pre><code class="language-json">${varContent}</code></pre>
                        </div>`;
                    }).join('');
                }

                const logClass = `log-entry-${category}`;

                return `
                    <div class="log-entry ${logClass} mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="log-timestamp">${timestamp}</span>
                            ${ip ? `<span class="text-gray-500">[${ip}]</span>` : ''}
                            ${errorType ? `<span class="font-semibold">${errorType}</span>` : ''}
                        </div>
                        ${content ? `<div class="mb-2 overflow-x-auto">${content}</div>` : ''}
                        ${stackTrace ? `
                            <div class="mb-2">
                                <div class="font-semibold mb-1">Stack trace:</div>
                                <pre><code class="language-php">${stackTrace}</code></pre>
                            </div>
                        ` : ''}
                        ${variables ? `
                            <div class="mt-4 border-t pt-2">
                                <div class="font-semibold mb-2">Variables:</div>
                                ${variables}
                            </div>
                        ` : ''}
                    </div>`;
            }).join('');

            // Подсвечиваем синтаксис в отформатированных логах
            container.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        }

        // Функция для форматирования логов действий
        function formatActionLogs(logs, containerId) {
            const container = document.getElementById(containerId);
            if (!logs || !container) return;

            // Разбиваем на отдельные записи
            const logEntries = logs.match(/<p[^>]*>.*?<\/p>/gs) || [];

            container.innerHTML = logEntries.map(entry => {
                // Определяем тип лога из класса
                const classMatch = entry.match(/class='([^']+)'/);
                const logClass = classMatch ? classMatch[1] : '';

                let logType = 'info'; // по умолчанию
                if (logClass.includes('text-danger')) logType = 'danger';
                else if (logClass.includes('text-warning')) logType = 'warning';
                else if (logClass.includes('text-success')) logType = 'success';
                else if (logClass.includes('text-primary')) logType = 'primary';
                else if (logClass.includes('text-info')) logType = 'info';
                else if (logClass.includes('text-log')) logType = 'log';

                // Извлекаем компоненты лога
                const parts = entry.match(/\[-\]\[-\](.*?)\[-\]\[-\](.*?)\[-\]\[-\](.*?)\[-\]\[-\](.*?)(?:<\/p>|$)/i);

                if (!parts) return '';

                const user = parts[1]?.trim() || '';
                const timestamp = parts[2]?.trim() || '';
                const controllerPart = parts[3]?.trim() || '';
                const message = parts[4]?.trim() || '';

                // Извлекаем имя контроллера из span
                const controllerMatch = controllerPart.match(/<span[^>]*>(.*?)<\/span>/);
                const controller = controllerMatch ? controllerMatch[1].trim() : controllerPart.trim();

                // Определяем стили
                const typeStyles = {
                    danger: 'border-red-500 bg-red-50/50 dark:bg-red-900/20',
                    warning: 'border-yellow-500 bg-yellow-50/50 dark:bg-yellow-900/20',
                    success: 'border-green-500 bg-green-50/50 dark:bg-green-900/20',
                    primary: 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20',
                    info: 'border-cyan-500 bg-cyan-50/50 dark:bg-cyan-900/20',
                    log: 'border-gray-500 bg-gray-50/50 dark:bg-gray-900/20'
                };

                const textStyles = {
                    danger: 'text-red-700 dark:text-red-300',
                    warning: 'text-yellow-700 dark:text-yellow-300',
                    success: 'text-green-700 dark:text-green-300',
                    primary: 'text-blue-700 dark:text-blue-300',
                    info: 'text-cyan-700 dark:text-cyan-300',
                    log: 'text-gray-700 dark:text-gray-300'
                };

                // Проверяем, содержит ли сообщение JSON
                let jsonContent = '';
                try {
                    if (message.includes('{') && message.includes('}')) {
                        const jsonStart = message.indexOf('{');
                        const jsonEnd = message.lastIndexOf('}') + 1;
                        const jsonStr = message.substring(jsonStart, jsonEnd);
                        const jsonData = JSON.parse(jsonStr);

                        // Отделяем текст сообщения от JSON
                        const textMessage = message.substring(0, jsonStart).trim();

                        jsonContent = `
                            <div class="mt-2 bg-gray-800/50 rounded p-2">
                                <pre><code class="language-json">${JSON.stringify(jsonData, null, 2)}</code></pre>
                            </div>`;
                        message = textMessage;
                    }
                } catch (e) {
                    // Если парсинг JSON не удался, оставляем сообщение как есть
                }

                return `
                    <div class="log-entry border-l-4 p-4 rounded-r ${typeStyles[logType]} mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="log-timestamp text-gray-600 dark:text-gray-400">${timestamp}</span>
                            <span class="font-semibold ${textStyles[logType]}">${user}</span>
                            ${controller ? `<span class="text-gray-500 dark:text-gray-400">[${controller}]</span>` : ''}
                        </div>
                        ${message ? `<div style="word-break: break-word" class="mb-2 ${textStyles[logType]}">${message}</div>` : ''}
                        ${jsonContent}
                    </div>`;
            }).join('');

            // Подсвечиваем синтаксис JSON
            container.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        }

        // Функция для форматирования SQL логов профилирования
        function formatProfilingLogs(logs, containerId) {
            const container = document.getElementById(containerId);
            if (!logs || !container) return;

            // Разбиваем на отдельные записи
            const logEntries = logs.match(/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}.*?(?=\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}|$)/gs) || [];

            // Группируем записи по SQL запросам
            const sqlGroups = [];
            let currentGroup = null;

            logEntries.forEach(entry => {
                const timestampMatch = entry.match(/(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})/);
                const sqlMatch = entry.match(/\[info\]\[yii\\db\\Command::query\]\s*(.*?)(?=\s+in\s+|$)/s);
                const profileMatch = entry.match(/\[(profile\s+(begin|end))\]/);
                const fileMatch = entry.match(/in\s+(.*?\.php:\d+)/);

                if (!timestampMatch) return;

                const timestamp = timestampMatch[1];
                const sql = sqlMatch ? sqlMatch[1].trim() : null;
                const profile = profileMatch ? profileMatch[1] : null;
                const file = fileMatch ? fileMatch[1] : null;

                if (sql) {
                    if (currentGroup) {
                        sqlGroups.push(currentGroup);
                    }
                    currentGroup = {
                        sql,
                        timestamp,
                        file,
                        profiles: []
                    };
                } else if (profile && currentGroup) {
                    currentGroup.profiles.push({
                        type: profile,
                        timestamp,
                        file
                    });
                }
            });

            if (currentGroup) {
                sqlGroups.push(currentGroup);
            }

            // Форматируем и выводим
            container.innerHTML = sqlGroups.map(group => {
                const duration = group.profiles.length >= 2 ?
                    (new Date(group.profiles[1].timestamp) - new Date(group.profiles[0].timestamp)) :
                    null;

                const sqlFormatted = group.sql
                    .replace(/\bSELECT\b/g, '<span class="text-blue-600 dark:text-blue-400">SELECT</span>')
                    .replace(/\bFROM\b/g, '<span class="text-purple-600 dark:text-purple-400">FROM</span>')
                    .replace(/\bWHERE\b/g, '<span class="text-green-600 dark:text-green-400">WHERE</span>')
                    .replace(/\bJOIN\b/g, '<span class="text-yellow-600 dark:text-yellow-400">JOIN</span>')
                    .replace(/\bON\b/g, '<span class="text-orange-600 dark:text-orange-400">ON</span>')
                    .replace(/\bAND\b/g, '<span class="text-red-600 dark:text-red-400">AND</span>')
                    .replace(/\bOR\b/g, '<span class="text-red-600 dark:text-red-400">OR</span>')
                    .replace(/\bORDER BY\b/g, '<span class="text-indigo-600 dark:text-indigo-400">ORDER BY</span>')
                    .replace(/\bGROUP BY\b/g, '<span class="text-pink-600 dark:text-pink-400">GROUP BY</span>')
                    .replace(/\bLIMIT\b/g, '<span class="text-cyan-600 dark:text-cyan-400">LIMIT</span>')
                    .replace(/\bOFFSET\b/g, '<span class="text-teal-600 dark:text-teal-400">OFFSET</span>')
                    .replace(/`([^`]+)`/g, '<span class="text-gray-600 dark:text-gray-400">`$1`</span>');

                return `
                    <div class="log-entry border-l-4 border-blue-500 bg-blue-50/50 dark:bg-blue-900/20 p-4 rounded-r mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-600 dark:text-gray-400">${group.timestamp}</span>
                            ${duration ? 
                                `<span class="px-2 py-1 text-xs rounded-full ${
                                    duration < 100 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                    duration < 500 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                }">
                                    ${duration}ms
                                </span>` : 
                                ''
                            }
                        </div>
                        <div class="mb-2 font-mono text-sm overflow-x-auto whitespace-pre-wrap">${sqlFormatted}</div>
                        ${group.file ? 
                            `<div class="text-sm text-gray-600 dark:text-gray-400">
                                in ${group.file}
                            </div>` : 
                            ''
                        }
                    </div>`;
            }).join('');
        }

        // Инициализация форматирования при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Format all logs first
            const appLogs = <?= json_encode($logs) ?>;
            const frontLogs = <?= json_encode($frontLogs) ?>;
            const actionLogs = <?= json_encode($actionLogs) ?>;
            const profilingLogs = <?= json_encode($profilingLogs) ?>;

            if (appLogs) formatAppLogs(appLogs, 'app-logs-container');
            if (frontLogs) formatFrontendLogs(frontLogs, 'front-logs-container');
            if (actionLogs) formatActionLogs(actionLogs, 'action-logs-container');
            if (profilingLogs) formatProfilingLogs(profilingLogs, 'profiling-logs-container');

            // Show logs section by default
            showSection('logs');

            // Get active tab from URL hash or default to 'app'
            const hash = window.location.hash.replace('#', '') || 'app';
            showLogTab(hash);
        });
    </script>
</body>

</html>