<?php
/**
 * Created by PhpStorm.
 * User: igroc
 * Date: 21.11.2018
 * Time: 18:22
 */

namespace app\modules\api\controllers;



use app\modules\api\models\Comment;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class CommentController extends ActiveController
{

    public $modelClass = 'app\modules\api\models\Comment';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['@'],
                        'matchCallback' => function ($rules, $action) {
                            $currentUser = Yii::$app->user->identity;
                            $comment = Comment::findOne(['id' => Yii::$app->request->get('comment_id')]);

                            if (isset($comment)){
                                return Yii::$app->commentService->canDelete($currentUser, $comment);
                            }
                            else{
                                throw new NotFoundHttpException('Comment is not found');
                            }
                        },
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'actions' => ['by-task', 'create', 'update']
                    ],
                ],
                'denyCallback' => function () {
                    throw new ForbiddenHttpException('You a not have permissions for this action');
                }
            ],
        ];
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['delete']);

        return $actions;
    }

    protected function verbs()
    {
        $verbs = parent::verbs();
        $verbs['by-task'] = ['GET', 'HEAD'];
        return $verbs;
    }

    public function actionByTask($task_id)
    {
        $query = Comment::find()->byTask($task_id);

        $requestParams = Yii::$app->getRequest()->getQueryParams();

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'params' => $requestParams,
            ],
            'sort' => [
                'params' => $requestParams,
            ],
        ]);
    }

    /**
     * @param $comment_id
     * @return array
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($comment_id)
    {
        $comment = Comment::findOne($comment_id);
        if (!$comment) {
            throw new NotFoundHttpException("Comment is not found.");
        }
        $comment->delete();
        return [
            'id' => $comment_id,
        ];
    }

}