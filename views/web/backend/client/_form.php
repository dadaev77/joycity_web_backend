<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Client */
/* @var $form yii\widgets\ActiveForm */
?>

    <?php $form = ActiveForm::begin( [
            'options' => ['class' => 'form-horizontal form-label-left'],
            'fieldConfig' => [
                    'template' => '{label}<div class="col-md-6 col-sm-6 col-xs-12">{input}
                                    <ul class="parsley-errors-list filled" id="parsley-id-5"><li class="parsley-required">{error}</li></ul></div>',
                    'labelOptions' => ['class' => 'control-label col-md-3 col-sm-3 col-xs-12'],
            ],
            
    ]); ?>

    <?php
        if ($model->old_name != "") 
            echo "<br> Имя из базы Scanoil: " . $model->surname . " " . $model->old_name. " <br><br>";
    ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'birthday')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'old_email')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?= $form->field($model, 'phone')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?php //= $form->field($model, 'last_phone_code')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?= $form->field($model, 'balance')->textInput(['readonly' => true]) ?>

    <?= $form->field($model, 'old_balance')->textInput(['readonly' => true]) ?>

    <?= $form->field($model, 'vcard_number')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?= $form->field($model, 'card_number')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?= $form->field($model, 'card_cvv')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?php //= $form->field($model, 'segment')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?php //= $form->field($model, 'PAN')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?php //= $form->field($model, 'LAST_TRX')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?php //= $form->field($model, 'is_phone_confirmed')->textInput() ?>

    <?php //= $form->field($model, 'is_email_confirmed')->textInput() ?>

    <?php //= $form->field($model, 'is_email_sent')->textInput() ?>

    <?php //= $form->field($model, 'is_scanoil_sent')->textInput() ?>

    <?= $form->field($model, 'scanoil_api_response')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?= $form->field($model, 'registration_date')->textInput(['maxlength' => true, 'readonly' => true]) ?>

    <?php //= $form->field($model, 'offer_date')->textInput() ?>

    <?= $form->field($model, 'created_at')->textInput(['readonly' => true]) ?>

    <?php //= $form->field($model, 'updated_at')->textInput() ?>

      <div class="ln_solid"></div>
      <div class="form-group">
        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
          <?= Html::a('Переслать QR', ['send-qr'], ['class' => 'btn']) ?>
		  <?= Html::a('Отменить', ['index'], ['class' => 'btn btn-default']) ?>
          <?= Html::submitButton('Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-success']) ?>
        </div>
      </div>

    <?php ActiveForm::end(); ?>

</div>
