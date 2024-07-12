<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\search\Client */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="client-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'name') ?>

    <?php //= $form->field($model, 'old_name') ?>

    <?php //= $form->field($model, 'surname') ?>

    <?php //= $form->field($model, 'birthday') ?>

    <?php // echo $form->field($model, 'old_birthday') ?>

    <?php echo $form->field($model, 'email') ?>

    <?php // echo $form->field($model, 'old_email') ?>

    <?php // echo $form->field($model, 'new_email') ?>

    <?php echo $form->field($model, 'phone') ?>

    <?php // echo $form->field($model, 'last_phone_code') ?>

    <?php // echo $form->field($model, 'balance') ?>

    <?php // echo $form->field($model, 'old_balance') ?>

    <?php echo $form->field($model, 'vcard_number') ?>

    <?php echo $form->field($model, 'card_number') ?>

    <?php // echo $form->field($model, 'card_cvv') ?>

    <?php // echo $form->field($model, 'segment') ?>

    <?php // echo $form->field($model, 'PAN') ?>

    <?php // echo $form->field($model, 'LAST_TRX') ?>

    <?php // echo $form->field($model, 'is_phone_confirmed') ?>

    <?php // echo $form->field($model, 'is_email_confirmed') ?>

    <?php // echo $form->field($model, 'is_email_sent') ?>

    <?php // echo $form->field($model, 'is_scanoil_sent') ?>

    <?php // echo $form->field($model, 'scanoil_api_response') ?>

    <?php // echo $form->field($model, 'last_api_call') ?>

    <?php // echo $form->field($model, 'source') ?>

    <?php // echo $form->field($model, 'registration_date') ?>

    <?php // echo $form->field($model, 'offer_date') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
        <?php //= Html::resetButton('Сброс', ['class' => 'btn btn-outline-secondary']) ?>
		<?= Html::a('Сброс', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
