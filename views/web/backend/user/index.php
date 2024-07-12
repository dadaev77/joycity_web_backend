<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h3><?= Html::encode($this->title) ?></h3>
    <?php //echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Создать пользователя', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <div class="x_panel">
        <div class="x_title">
            <h2>Список пользователей<small></small></h2>
            <!--ul class="nav navbar-right panel_toolbox">
                <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="#">Settings 1</a>
                        </li>
                        <li><a href="#">Settings 2</a>
                        </li>
                    </ul>
                </li>
                <li><a class="close-link"><i class="fa fa-close"></i></a>
                </li>
            </ul-->
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                //'filterModel' => $searchModel,
                
            	'tableOptions' => ['class' => 'table table-striped table-hover'],
                'columns' => [
                    //['class' => 'yii\grid\SerialColumn'],
					
                    'login',
                    'email:email',
                    'name',
                    //'password',
                    //'create_date',
                    //'role',
                    // 'id',

                     //'last_visit_date',
                	[
                		'attribute'=>'last_visit_date',
                		'label'=>'Последий визит',
                		'format'=>'text',
                		'content'=>function($data){
                			return date( 'd-m-Y H:i:s', strtotime( $data->last_visit_date ) );
                		},
                		//'filter' => Category::getParentsList()
                	],
                    [
                    		'class' => 'yii\grid\ActionColumn',
                    		'template' => '{update} {delete}', 
                    		
                    		'buttons' => [
                    				'delete' => function ($url, $model) {
                    					return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                    							'title' => 'Удалить пользователя "'.$model->login.'"',
                    							'aria-label' => 'Удалить',
                    							'data-confirm' => 'Вы уверены, что хотите удалить пользователя "'.$model->login.'"?',
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
