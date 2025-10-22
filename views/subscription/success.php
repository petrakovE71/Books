<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $subscription app\models\Subscription */

$this->title = 'Подписка оформлена';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subscription-success">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-success">
        <h4>Спасибо за подписку!</h4>
        <p>
            Вы успешно подписались на новые книги автора <strong><?= Html::encode($subscription->author->fio) ?></strong>.
        </p>
        <p>
            На номер <strong><?= Html::encode($subscription->getFormattedPhone()) ?></strong>
            будут приходить SMS уведомления о выходе новых книг этого автора.
        </p>
    </div>

    <p>
        <?= Html::a('Вернуться к списку авторов', ['author/index'], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Вернуться к списку книг', ['book/index'], ['class' => 'btn btn-default']) ?>
    </p>

</div>
