<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\models\User;
use app\components\response\ResponseCodes;
use app\components\ApiResponse;
use Yii;

class UserController extends ManagerController
{
    protected $allowedRoles = ['client', 'buyer', 'manager', 'fulfillment'];

    protected $responseCodes;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->responseCodes = ResponseCodes::getStatic();
    }

    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['index'] = ['get'];
        $behaviours['verbFilter']['actions']['search'] = ['get'];
        $behaviours['verbFilter']['actions']['update-markup'] = ['patch'];
        return $behaviours;
    }
    /**
     * @param string $role
     * @param int $limit
     * @param int $page
     * @return array
     */

    public function actionIndex(string $role, int $limit = 10, int $page = 1, int $id = null)
    {
        if (!in_array($role, $this->allowedRoles)) {
            return ApiResponse::code(
                $this->responseCodes->BAD_REQUEST,
                [
                    'message' => 'Invalid role.'
                ],
                422
            );
        }
        
        $query = User::find()->where(['role' => $role, 'is_deleted' => 0]);
        $query->select(['id', 'name', 'surname', 'uuid', 'role', 'email', 'markup']);
        $totalUsers = $query->count(); 
        $pages = ceil($totalUsers / $limit);
        
        $users = $query->orderBy(['id' => 'DESC'])
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->all();

        if ($id) {
            $user = User::findOne($id);
            if (!$user) return ApiResponse::code($this->responseCodes->NOT_FOUND, ['message' => 'User not found.']);
        }

        if (count($users) < 1) {
            return ApiResponse::code(
                $this->responseCodes->NOT_FOUND,
                [
                    'message' => 'No users found for the specified role.'
                ],
                404
            );
        }

        if ($id) {
            return ApiResponse::code(
                $this->responseCodes->SUCCESS,
                [
                    'info' => $user
                ]
            );
        }

        return ApiResponse::code(
            $this->responseCodes->SUCCESS,
            [
                'items' => $users,
                'total_count' => $totalUsers,
                'total_pages' => $pages,
                'current_page' => $page,
                'limit' => $limit
            ]   
        );
    }


    public function actionSearch(
        string $query,
        int $limit = 10,
        int $page = 1
    )
    {
        $queryBuilder = User::find()
            ->select(['id', 'name', 'surname', 'uuid', 'role', 'email', 'markup'])
            ->where(['or',
                ['like', 'email', $query],
                ['like', 'surname', $query],
                ['like', 'name', $query],
                ['like', 'uuid', $query]
            ])
            ->andWhere(['is_deleted' => 0]);

        $totalUsers = $queryBuilder->count();
        $pages = ceil($totalUsers / $limit);

        $users = $queryBuilder
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->all();

        if ( count($users) < 1 ) return ApiResponse::code($this->responseCodes->NOT_FOUND,['message' => 'No users found for the specified query.'],404);

        return ApiResponse::code(
            $this->responseCodes->SUCCESS,
            [
                'items' => $users,
                'total_count' => $totalUsers,
                'total_pages' => $pages,
                'current_page' => $page,
                'limit' => $limit
            ]
        );
    }

    /**
     * @param int $id
     * @param int $markup
     * @return array
     */
    public function actionUpdateMarkup()
    {
        $user = User::findOne(Yii::$app->request->post('user_id'));
        $markup = Yii::$app->request->post('markup');

        if (!$user) return ApiResponse::code($this->responseCodes->NOT_FOUND, ['message' => 'User not found.']);        
        if ($user->role !== User::ROLE_CLIENT) return ApiResponse::code($this->responseCodes->BAD_REQUEST, ['message' => 'Markup can only be set for clients.']);
        
        if (!is_numeric($markup) || $markup < 0 || $markup > 100) return ApiResponse::code($this->responseCodes->BAD_REQUEST, ['message' => 'Markup must be between 0 and 100.']);
        
        $user->markup = (int)$markup;
        if (!$user->save()) return ApiResponse::code($this->responseCodes->ERROR_SAVE, ['errors' => $user->errors], 422);

        return ApiResponse::code($this->responseCodes->SUCCESS,['markup' => $user->markup]);
    }

}



