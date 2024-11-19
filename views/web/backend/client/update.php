<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Client */

$this->title =  $model->name . ' ID ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Clients', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
          <div class="">
            <div class="page-title">
              <div class="title_left">
                <h3><?= Html::encode($this->title) ?></h3>
              </div>             
            </div>
    		
    		<div class="clearfix"></div>
    		
           <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_content">
                		<br />
					    <?= $this->render('_form', [
					        'model' => $model,
					    ]) ?>
				    </div>
                </div>
              </div>
            </div>
          </div>
