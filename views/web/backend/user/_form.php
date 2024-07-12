<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\User */
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

    <?= $form->field($model, 'login')->textInput(['maxlength' => true]) ?>

	<div class="ln_solid"></div>
		
	<?
	if ($model->isNewRecord)
		echo $form->field($model, 'password')->passwordInput(['maxlength' => '50']);
	else
		echo $form->field($model, 'password')->passwordInput(['maxlength' => '50', 'placeholder' => 'Оставьте поле пустым, если не нужно изменять пароль']);
	?>
	
	<?= $form->field($model, 'confirm')->passwordInput(['maxlength' => '50']) ?>
	
	<div class="ln_solid"></div>
	
    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

	<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
	
	<?//= $form->field($model, 'role')->textInput() ?>
	
	<?= $form->field($model, 'role')->dropDownList(User::getRoles()) ?>
	
	<div class="ln_solid"></div>
	
    <?//= $form->field($model, 'create_date')->textInput(['disabled' => true]) 
    if (!$model->isNewRecord) {
    ?>
   
   	<?= $form->field($model, 'create_date')->textInput(['disabled' => true, 'value' => date( 'd-m-Y H:i:s', strtotime( $model->create_date ) )]); ?> 
    
    <?//= $form->field($model, 'update_date')->textInput(['disabled' => true]) ?>

    <?//= $form->field($model, 'last_visit_date')->textInput(['disabled' => true]) ?>
    
    <?= $form->field($model, 'last_visit_date')->textInput(['disabled' => true, 'value' => date( 'd-m-Y H:i:s', strtotime( $model->last_visit_date ) )]); ?>

	<?} ?>
	
	  <div class="ln_solid"></div>
	  <div class="form-group">
	    <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
	      <?= Html::a('Отменить', ['index'], ['class' => 'btn btn-default']) ?>
	      <?= Html::submitButton('Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-success']) ?>
	    </div>
	  </div>


    <?php ActiveForm::end(); ?>

                