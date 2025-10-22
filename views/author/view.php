<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Author */
/* @var $booksDataProvider yii\data\ActiveDataProvider */

$this->title = $model->fio;
$this->params['breadcrumbs'][] = ['label' => 'Авторы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="author-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->can('updateAuthor')): ?>
            <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->can('deleteAuthor')): ?>
            <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => ['confirm' => 'Вы уверены?', 'method' => 'post'],
            ]) ?>
        <?php endif; ?>
        <?= Html::a('Подписаться на новые книги', ['subscription/create', 'author_id' => $model->id], ['class' => 'btn btn-info']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'fio',
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

    <h2>Книги автора</h2>

    <?= GridView::widget([
        'dataProvider' => $booksDataProvider,
        'columns' => [
            'title',
            'year',
            'isbn',
            [
                'class' => 'yii\grid\ActionColumn',
                'controller' => 'book',
                'template' => '{view}',
            ],
        ],
    ]); ?>

</div>
