<?php

/** @var array $data Данные для накладной */
?>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            /* Перенос слов */
            overflow: hidden;
            /* Скрывает переполнение */
            max-width: 150px;
            /* Максимальная ширина ячеек */
        }

        .logo-cell {
            text-align: center;
            vertical-align: middle;
            width: 15%;
        }

        .header-cell {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            padding: 10px;
        }

        .details-cell {
            text-align: left;
            padding-left: 5px;
        }

        .no-border {
            border: none;
        }

        .image-cell img {
            width: 100%;
            height: auto;
        }
    </style>
</head>

<body>
    <table class="table">
        <tbody>
            <tr>
                <td class="logo-cell" rowspan="4">
                    <img src="data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAA2AAAARiCAMAAAD1I2QTAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDkuMC1jMDAwIDc5LjE3MWMyN2ZhYiwgMjAyMi8wOC8xNi0yMjozNTo0MSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIDI0LjAgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6QUJENEU0NzlCMTU0MTFFRjhDQzZGMzREQzEzRkM4ODEiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6QUJENEU0N0FCMTU0MTFFRjhDQzZGMzREQzEzRkM4ODEiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpBQkQ0RTQ3N0IxNTQxMUVGOENDNkYzNERDMTNGQzg4MSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpBQkQ0RTQ3OEIxNTQxMUVGOENDNkYzNERDMTNGQzg4MSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PqKH3tQAAACHUExURQBr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" alt="Logo" style="width: 100px;">
                </td>
                <td colspan="6" class="header-cell" rowspan="1">Международная логистическая компания JOYCITY 313</td>
                <td class="image-cell" rowspan="4">
                    <img src="data:image/octet-stream;base64,<?= $data['first_attachment'] ?>" alt='Attachment' style='width: 100px;'>
                </td>
            </tr>
            <tr>
                <td>Отправитель</td>
                <td><?= htmlspecialchars($data['sender_name']) ?></td>
                <td>Номер телефона</td>
                <td><?= htmlspecialchars($data['sender_phone']) ?></td>
                <td>Город отправления</td>
                <td><?= htmlspecialchars($data['departure_city']) ?></td>
            </tr>
            <tr>
                <td>Получатель</td>
                <td><?= htmlspecialchars($data['recipient_name']) ?></td>
                <td>Номер получателя</td>
                <td><?= htmlspecialchars($data['recipient_phone']) ?></td>
                <td>Город получения</td>
                <td><?= htmlspecialchars($data['destination_city']) ?></td>
            </tr>
            <tr>
                <td>Дата изготовления</td>
                <td><?= htmlspecialchars($data['date_of_production']) ?></td>
                <td>Вид перевозки</td>
                <td><?= htmlspecialchars($data['delivery_type']) ?></td>
                <td>Номер</td>
                <td><?= htmlspecialchars($data['waybill_number']) ?></td>
            </tr>
            <tr>
                <td colspan="8" class="header-cell">Накладная</td>
            </tr>
            <tr>
                <td>Курс</td>
                <td><?= number_format($data['course'], 2) ?></td>
                <td>Итого количество(шт)</td>
                <td><?= number_format($data['total_quantity'], 0) ?></td>
                <td>Ассортимент</td>
                <td colspan="3"><?= htmlspecialchars($data['assortment']) ?></td>
            </tr>
            <tr>
                <td>Единичная цена на вес($/Kg)</td>
                <td><?= number_format($data['price_per_kg'], 2) ?></td>
                <td>Итого количество(пар)</td>
                <td><?= number_format($data['total_pairs'], 0) ?></td>
                <td>Итог таможенной пошлины($)</td>
                <td colspan="3"><?= number_format($data['total_customs_duty'], 2) ?></td>
            </tr>
            <tr>
                <td>Сумма страхования(¥)</td>
                <td><?= number_format($data['insurance_sum_yuan'], 2) ?></td>
                <td>Страховая ставка</td>
                <td><?= number_format($data['insurance_rate'] * 100) ?>%</td>
                <td>Расходы на страхование($)</td>
                <td colspan="3"><?= number_format($data['insurance_costs'], 2) ?></td>
            </tr>
            <tr>
                <td>Авансирование на территории Китая($)</td>
                <td><?= number_format($data['china_advance_usd'], 2) ?></td>
                <td>Объем(м³)</td>
                <td><?= number_format($data['volume'], 4) ?></td>
                <td>Расходы на объем($)</td>
                <td colspan="3"><?= number_format($data['volume_costs'], 2) ?></td>
            </tr>
            <tr>
                <td>Оплата в Китае($)</td>
                <td><?= number_format($data['china_payment_usd'], 2) ?></td>
                <td>Вес(Kg)</td>
                <td><?= number_format($data['weight'], 2) ?></td>
                <td>Расходы на вес($)</td>
                <td colspan="3"><?= number_format($data['weight_costs'], 2) ?></td>
            </tr>
            <tr>
                <td>Расходы за упаковку($)</td>
                <td><?= number_format($data['package_expenses'], 2) ?></td>
                <td colspan="2">Итог оплаты($)</td>
                <td colspan="4"><?= number_format($data['total_payment'], 2) ?></td>
            </tr>
            <tr>
                <td colspan="8" class="header-cell" style="text-align:start">Исполнитель: <?= htmlspecialchars($data['executor']) ?></td>
            </tr>
            <tr>
                <td colspan="8" class="header-cell" style="text-align:start">Утверждено: <?= htmlspecialchars($data['approved_by']) ?></td>
            </tr>
        </tbody>
    </table>


</body>

</html>