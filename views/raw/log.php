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
                            <!-- Add tabs for buyers list, clients list, manager list, fulfillment list, and products -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="buyers-tab" data-bs-toggle="tab" data-bs-target="#buyers" type="button" role="tab" aria-controls="buyers" aria-selected="false">Buyers List</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab" aria-controls="clients" aria-selected="false">Clients List</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="managers-tab" data-bs-toggle="tab" data-bs-target="#managers" type="button" role="tab" aria-controls="managers" aria-selected="false">Managers List</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="fulfillment-tab" data-bs-toggle="tab" data-bs-target="#fulfillment" type="button" role="tab" aria-controls="fulfillment" aria-selected="false">Fulfillment List</button>
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
                        </ul>
                        <div class="tab-content py-4" id="myTabContent">
                            <div class="tab-pane fade show active" id="backend" role="tabpanel" aria-labelledby="backend-tab">
                                <div style="white-space: wrap; word-break: break-all; font-size: 12px;">
                                    <?= $logs ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="frontend" role="tabpanel" aria-labelledby="frontend-tab">
                                <div style="white-space: wrap; word-break: break-all; font-size: 12px;">
                                    <?= $frontLogs ?>
                                </div>
                            </div>
                            <!-- Add corresponding tab panes for the new tabs -->
                            <div class="tab-pane fade" id="buyers" role="tabpanel" aria-labelledby="buyers-tab">
                                <div class="row row-cols-1 row-cols-md-3">
                                    <?php foreach ($buyers as $buyer) : ?>
                                        <div class="col h-100">
                                            <ul class="list-unstyled p-0 card p-3 h-100">
                                                <li class="d-block">
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
                            <div class="tab-pane fade" id="clients" role="tabpanel" aria-labelledby="clients-tab">
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
                            <div class="tab-pane fade" id="managers" role="tabpanel" aria-labelledby="managers-tab">
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
                            <div class="tab-pane fade" id="fulfillment" role="tabpanel" aria-labelledby="fulfillment-tab">
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
                            <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                                <div class="row row-cols-1 row-cols-md-3">
                                    <?php foreach ($products as $product) : ?>
                                        <div class="col h-100">
                                            <ul class="list-unstyled p-0 card p-3">
                                                <li class="d-block">
                                                    <p class="mb-0">Name: <?= $product->name ?></p>
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
                                                    <p class="mb-0">Created By: <?= $order->created_by ?></p>
                                                    <p class="mb-0">Buyer ID: <?= $order->buyer_id ?></p>
                                                    <p class="mb-0">Manager ID: <?= $order->manager_id ?></p>
                                                    <p class="mb-0">Fulfillment ID: <?= $order->fulfillment_id ?></p>
                                                    <p class="mb-0">Product Name: <?= $order->product_name ?></p>
                                                    <p class="mb-0">Subcategory ID: <?= $order->subcategory_id ?></p>
                                                    <p class="mb-0">Status: <?= $order->created_at ?></p>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="attachments" role="tabpanel" aria-labelledby="attachments-tab">
                                <div class="row row-cols-2 row-cols-md-6">
                                    <?php foreach ($attachments as $attachment) : ?>
                                        <div class="col h-100">
                                            <img src="/attachments/<?= $attachment ?>" onerror="this.onerror=null; this.src='http://via.placeholder.com/150'" class="img-fluid" title="<?= $attachment ?>">
                                        </div>
                                    <?php endforeach; ?>
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