<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Document</title>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <th>id</th>
                        <th>created_at</th>
                        <th>status</th>
                        <th>created_by</th>
                        <th>buyer_id</th>
                        <th>manager_id</th>
                        <th>fulfillment_id</th>
                        <th>product_id</th>
                        <th>product_name</th>
                        <th>product_description</th>
                        <th>expected_quantity</th>
                        <th>expected_price_per_item</th>
                        <th>expected_packaging_quantity</th>
                        <th>subcategory_id</th>
                        <th>type_packaging_id</th>
                        <th>type_delivery_id</th>
                        <th>type_delivery_point_id</th>
                        <th>delivery_point_address_id</th>
                        <th>price_product</th>
                        <th>price_inspection</th>
                        <th>price_packaging</th>
                        <th>price_fulfilment</th>
                        <th>price_delivery</th>
                        <th>total_quantity</th>
                        <th>is_need_deep_inspection</th>
                        <th>is_deleted</th>
                        <th>link_tz</th>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order) { ?>
                            <tr>
                                <td><?= $order->id ?></td>
                                <td><?= $order->created_at ?></td>
                                <td><?= $order->status ?></td>
                                <td><?= $order->created_by ?></td>
                                <td><?= $order->buyer_id ?></td>
                                <td><?= $order->manager_id ?></td>
                                <td><?= $order->fulfillment_id ?></td>
                                <td><?= $order->product_id ?></td>
                                <td><?= $order->product_name ?></td>
                                <td><?= $order->product_description ?></td>
                                <td><?= $order->expected_quantity ?></td>
                                <td><?= $order->expected_price_per_item ?></td>
                                <td><?= $order->expected_packaging_quantity ?></td>
                                <td><?= $order->subcategory_id ?></td>
                                <td><?= $order->type_packaging_id ?></td>
                                <td><?= $order->type_delivery_id ?></td>
                                <td><?= $order->type_delivery_point_id ?></td>
                                <td><?= $order->delivery_point_address_id ?></td>
                                <td><?= $order->price_product ?></td>
                                <td><?= \app\services\RateService::convertValue($order->price_inspection, $order->currency, Yii::$app->user->identity->settings->currency) ?></td>
                                <td><?= $order->price_packaging ?></td>
                                <td><?= $order->price_fulfilment ?></td>
                                <td><?= $order->price_delivery ?></td>
                                <td><?= $order->total_quantity ?></td>
                                <td><?= $order->is_need_deep_inspection ?></td>
                                <td><?= $order->is_deleted ?></td>
                                <td><?= $order->link_tz ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>