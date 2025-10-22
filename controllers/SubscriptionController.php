<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\Subscription;
use app\models\Author;
use app\common\services\SubscriptionService;
use app\common\dto\CreateSubscriptionDto;
use app\common\exceptions\ValidationException;

class SubscriptionController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create', 'success'],
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Subscription();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try {
                /** @var SubscriptionService $subscriptionService */
                $subscriptionService = Yii::$app->get('subscriptionService');

                $dto = new CreateSubscriptionDto(
                    authorId: $model->author_id,
                    name: $model->name,
                    phone: $model->phone
                );

                $subscription = $subscriptionService->createSubscription($dto);

                Yii::$app->session->setFlash('success', 'Вы успешно подписались на новые книги автора!');
                return $this->redirect(['success', 'id' => $subscription->id]);
            } catch (ValidationException $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', 'Ошибка создания подписки: ' . $e->getMessage());
            }
        }

        $authors = Author::find()->orderBy(['fio' => SORT_ASC])->all();

        return $this->render('create', [
            'model' => $model,
            'authors' => $authors,
        ]);
    }

    /**
     * @param int $id
     * @return string
     */
    public function actionSuccess(int $id): string
    {
        $subscription = Subscription::findOne($id);

        return $this->render('success', [
            'subscription' => $subscription,
        ]);
    }
}
