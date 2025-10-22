<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Книги';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->can('createBook')): ?>
            <?= Html::a('Создать книгу', ['create'], ['class' => 'btn btn-success']) ?>
        <?php endif; ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'title',
            'year',
            [
                'attribute' => 'isbn',
                'format' => 'text',
            ],
            [
                'label' => 'Авторы',
                'format' => 'html',
                'value' => function ($model) {
                    $authors = array_map(fn($author) => Html::encode($author->fio), $model->authors);
                    return implode('<br>', $authors);
                },
            ],
            [
                'attribute' => 'cover_photo',
                'format' => 'html',
                'value' => function ($model) {
                    if ($model->cover_photo) {
                        return Html::img($model->getCoverUrl(), ['width' => 50]);
                    }
                    return '<span class="text-muted">Нет</span>';
                },
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'visibleButtons' => [
                    'update' => function ($model, $key, $index) {
                        return !Yii::$app->user->isGuest && Yii::$app->user->can('updateBook');
                    },
                    'delete' => function ($model, $key, $index) {
                        return !Yii::$app->user->isGuest && Yii::$app->user->can('deleteBook');
                    },
                ],
            ],
        ],
    ]); ?>

</div>
