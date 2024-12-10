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
                <td class="logo-cell" rowspan="4"></td>
                <td colspan="6" class="header-cell" rowspan="1">Международная логистическая компания JOYCITY 313</td>
                <td class="image-cell" rowspan="4"></td>
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
