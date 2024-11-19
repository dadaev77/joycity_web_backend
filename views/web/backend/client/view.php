<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Client */

$this->title = $model->name . ' ID ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Клиенты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

  <div class="">
    <div class="page-title">
      <div class="title_left">
        <h3><small></small></h3>
		  <?= Html::a('Назад', ['index'], ['class' => 'btn btn-default']) ?>
      </div>
		
    </div>

    <div class="clearfix"></div>

	  
    <div class="x_panel">
        <div class="x_title">
            <h2><?= Html::encode($this->title) ?><small></small></h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            //'old_name',
            //'surname',
            'birthday',
            //'old_birthday',
            'email:email',
            'old_email:email',
            //'new_email:email',
            'phone',
            //'last_phone_code',
            'balance',
            'old_balance',
            'vcard_number',
            'card_number',
            'card_cvv',
            //'segment',
            //'PAN',
            //'LAST_TRX',
            //'is_phone_confirmed',
            //'is_email_confirmed:email',
            //'is_email_sent:email',
            'is_scanoil_sent',
            'scanoil_api_response',
            //'last_api_call',
            //'source',
            'registration_date',
            //'offer_date',
            'created_at',
            //'updated_at',
        ],
    ]) ?>

         </div>
    </div>
</div>
