<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\Client */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Клиенты';
$this->params['breadcrumbs'][] = $this->title;
?>
  <div class="">
    <div class="page-title">
      <div class="title_left">
        <h3><?= Html::encode($this->title) ?><small></small></h3>
      </div>

	<!--  
      <div class="title_right">
        <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
          <div class="input-group">
            <input type="text" class="form-control" placeholder="Search for...">
            <span class="input-group-btn">
              <button class="btn btn-default" type="button">Go!</button>
            </span>
          </div>
        </div>
      </div>-->
    </div>
	  
	  <div class="clearfix"></div>

    <?php echo $this->render('_search', ['model' => $searchModel]); ?>
    
		<div class="x_panel">
			<div class="x_title">
				<h2>Список клиентов<small></small></h2>
				<div class="clearfix"></div>
			</div>
			<div class="x_content">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        //'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            'id',
            'name',
            //'old_name',
            //'surname',
            'birthday',
            //'old_birthday',
            'email:email',
            //'old_email:email',
            //'new_email:email',
            'phone',
            //'last_phone_code',
            //'balance',
            //'old_balance',
            'vcard_number',
            'card_number',
            //'card_cvv',
            //'segment',
            //'PAN',
            //'LAST_TRX',
            //'is_phone_confirmed',
            //'is_email_confirmed:email',
            //'is_email_sent:email',
            //'is_scanoil_sent',
            //'scanoil_api_response',
            //'last_api_call',
            //'source',
            'registration_date',
            //'offer_date',
            'created_at',
            //'updated_at',

			[
				'class' => 'yii\grid\ActionColumn',
				'template' => '{view}&nbsp;&nbsp;{update}',
				'buttons' => [
					'delete' => function ($url, $model) {
						return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
							'title' => 'Удалить игрока "'.$model->login.'"',
							'aria-label' => 'Удалить',
							'data-confirm' => 'Вы уверены, что хотите удалить игрока "'.$model->login.'"?',
							'data-method'=>'POST',
							'data-pjax' => "0",
						]);
					}
				]
			],
        ],
    ]); ?>

         </div>
    </div>
</div>
