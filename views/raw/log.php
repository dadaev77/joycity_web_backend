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
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="backend-tab" data-bs-toggle="tab" data-bs-target="#backend" type="button" role="tab" aria-controls="backend" aria-selected="true">Backend Logs</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="frontend-tab" data-bs-toggle="tab" data-bs-target="#frontend" type="button" role="tab" aria-controls="frontend" aria-selected="false">Frontend Logs</button>
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
                                <button class="nav-link" id="attachments-tab" data-bs-toggle="tab" data-bs-target="#attachments" type="button" role="tab" aria-controls="attachments" aria-selected="false">Attachments</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button" role="tab" aria-controls="new" aria-selected="false">Reset App</button>
                            </li>
                        </ul>
                        <div class="tab-content py-4" id="myTabContent">
                            <div class="tab-pane fade show active" id="backend" role="tabpanel" aria-labelledby="backend-tab">
                                <div style="white-space: wrap; word-break: break-all; font-size: 12px;">
                                    <?= $logs ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="frontend" role="tabpanel" aria-labelledby="frontend-tab">
                                <div style="white-space: wrap; word-break: break-all; font-size: 12px;" id="frontend-logs">

                                    <?= $frontLogs ?>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            var items = document.getElementsByClassName('format');
                                            for (var i = 0; i < items.length; i++) {
                                                const formattedData = JSON.parse(items[i].innerText);
                                                items[i].innerText = JSON.stringify(formattedData, null, 4);
                                            }
                                        });
                                    </script>
                                </div>
                            </div>
                            <!-- Add corresponding tab panes for the new tabs -->
                            <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                                <ul class="nav nav-tabs" id="userRolesTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="buyers-role-tab" data-bs-toggle="tab" data-bs-target="#buyers-role" type="button" role="tab" aria-controls="buyers-role" aria-selected="true">Buyers</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="clients-role-tab" data-bs-toggle="tab" data-bs-target="#clients-role" type="button" role="tab" aria-controls="clients-role" aria-selected="false">Clients</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="managers-role-tab" data-bs-toggle="tab" data-bs-target="#managers-role" type="button" role="tab" aria-controls="managers-role" aria-selected="false">Managers</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="fulfillment-role-tab" data-bs-toggle="tab" data-bs-target="#fulfillment-role" type="button" role="tab" aria-controls="fulfillment-role" aria-selected="false">Fulfillment</button>
                                    </li>
                                </ul>
                                <div class="tab-content py-4" id="userRolesTabContent">
                                    <div class="tab-pane fade show active" id="buyers-role" role="tabpanel" aria-labelledby="buyers-role-tab">
                                        <div class="row row-cols-1 row-cols-md-3">
                                            <?php foreach ($buyers as $buyer) : ?>
                                                <div class="col h-100">
                                                    <ul class="list-unstyled p-0 card p-3 h-100">
                                                        <li class="d-block">
                                                            <p class="mb-0">ID: <?= $buyer->id ?></p>
                                                            <p class="mb-0">Email: <?= $buyer->email ?></p>
                                                            <p class="mb-0">Name: <?= $buyer->name ?></p>
                                                            <p class="mb-0">Surname: <?= $buyer->surname ?></p>
                                                            <p class="mb-0">Organization Name: <?= $buyer->organization_name ?></p>
                                                            <p class="mb-0">Rating: <?= $buyer->rating ?></p>
                                                        </li>
                                                    </ul>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="clients-role" role="tabpanel" aria-labelledby="clients-role-tab">
                                        <div class="row row-cols-1 row-cols-md-3">
                                            <?php foreach ($clients as $client) : ?>
                                                <div class="col h-100">
                                                    <ul class="list-unstyled p-0 card p-3 h-100">
                                                        <li class="d-block">
                                                            <p class="mb-0">Email: <?= $client->email ?></p>
                                                            <p class="mb-0">Name: <?= $client->name ?></p>
                                                            <p class="mb-0">Surname: <?= $client->surname ?></p>
                                                            <p class="mb-0">Organization Name: <?= $client->organization_name ?></p>
                                                            <p class="mb-0">Rating: <?= $client->rating ?></p>
                                                        </li>
                                                    </ul>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="managers-role" role="tabpanel" aria-labelledby="managers-role-tab">
                                        <div class="row row-cols-1 row-cols-md-3 g-4">
                                            <?php foreach ($managers as $manager) : ?>
                                                <div class="col h-100">
                                                    <ul class="list-unstyled p-0 card p-3">
                                                        <li class="d-block">
                                                            <p class="mb-0">Email: <?= $manager->email ?></p>
                                                            <p class="mb-0">Name: <?= $manager->name ?></p>
                                                            <p class="mb-0">Surname: <?= $manager->surname ?></p>
                                                            <p class="mb-0">Organization Name: <?= $manager->organization_name ?></p>
                                                            <p class="mb-0">Rating: <?= $manager->rating ?></p>
                                                        </li>
                                                    </ul>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="fulfillment-role" role="tabpanel" aria-labelledby="fulfillment-role-tab">
                                        <div class="row row-cols-1 row-cols-md-3">
                                            <?php foreach ($fulfillment as $fulfillment) : ?>
                                                <div class="col h-100">
                                                    <ul class="list-unstyled p-0 card p-3">
                                                        <li class="d-block">
                                                            <p class="mb-0">Email: <?= $fulfillment->email ?></p>
                                                            <p class="mb-0">Name: <?= $fulfillment->name ?></p>
                                                            <p class="mb-0">Surname: <?= $fulfillment->surname ?></p>
                                                            <p class="mb-0">Organization Name: <?= $fulfillment->organization_name ?></p>
                                                            <p class="mb-0">Rating: <?= $fulfillment->rating ?></p>
                                                        </li>
                                                    </ul>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                                <div class="row row-cols-1 row-cols-md-3">
                                    <?php foreach ($products as $product) : ?>
                                        <div class="col h-100">
                                            <ul class="list-unstyled p-0 card p-3">
                                                <li class="d-block">
                                                    <p class="mb-0">Name: <?= $product->name_ru ?></p>
                                                    <p class="mb-0">Rating: <?= $product->rating ?></p>
                                                    <p class="mb-0">Buyer ID: <?= $product->buyer_id ?></p>
                                                    <p class="mb-0">Subcategory ID: <?= $product->subcategory_id ?></p>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                                <div class="row row-cols-1 row-cols-md-3">
                                    <?php foreach ($orders as $order) : ?>
                                        <div class="col h-100">
                                            <ul class="list-unstyled p-0 card p-3">
                                                <li class="d-block">
                                                    <p class="mb-0">ID: <?= $order->id ?></p>
                                                    <p class="mb-0">Created By: <?= $order->created_by ?></p>
                                                    <p class="mb-0">Buyer ID: <?= $order->buyer_id ?></p>
                                                    <p class="mb-0">Manager ID: <?= $order->manager_id ?></p>
                                                    <p class="mb-0">Fulfillment ID: <?= $order->fulfillment_id ?></p>
                                                    <p class="mb-0">Product Name: <?= $order->product_name_ru ?></p>
                                                    <p class="mb-0">Subcategory ID: <?= $order->subcategory_id ?></p>
                                                    <p class="mb-0">Status: <?= $order->created_at ?></p>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="attachments" role="tabpanel" aria-labelledby="attachments-tab">
                                <div class="row row-cols-2 row-cols-md-6 g-2">
                                    <?php foreach ($attachments as $attachment) : ?>
                                        <div class="col h-100">
                                            <div title="<?= $attachment ?>" class="rounded" style="min-height: 180px; width: 100%;background-size: cover; background-position: center; background-image: url('/attachments/<?= $attachment ?>');">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
                            <div class="tab-pane fade" id="new" role="tabpanel" aria-labelledby="new-tab">
                                <div>
                                    <p>
                                        To reset the app, you need to text the following message to the field: <span class="text-danger" id="text-field"></span>
                                    </p>
                                    <input type="text" class="form-control mt-2" placeholder="Enter the text here" id="reset-text">
                                    <button class="btn btn-primary mt-2" id="reset-button">Reset</button>

                                    <button class="btn btn-primary mt-2 d-none" disabled id="loader">
                                        <div class="spinner-border-sm spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </button>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            document.getElementById('text-field').innerText = Math.random().toString(36).substring(2, 12);
                                        });
                                        document.getElementById('reset-button').addEventListener('click', function() {
                                            document.getElementById('reset-button').classList.add('d-none');
                                            document.getElementById('loader').classList.remove('d-none');
                                            async function reset() {
                                                const response = await fetch('/raw/reset-app', {
                                                    method: 'POST',
                                                    body: JSON.stringify({
                                                        text: document.getElementById('reset-text').value
                                                    })
                                                }).then(response => {
                                                    if (response.ok) {
                                                        alert('App reset successfully!\nReloading in 3 seconds...');
                                                        document.getElementById('loader').classList.add('d-none');
                                                        document.getElementById('reset-button').classList.remove('d-none');
                                                        setTimeout(() => {
                                                            location.reload();
                                                        }, 3000);
                                                    } else {
                                                        alert('Failed to reset the app.');
                                                    }
                                                }).catch(error => {
                                                    console.error('Error:', error);
                                                });
                                            }
                                            reset();
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>