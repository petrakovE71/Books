<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Book */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->can('updateBook')): ?>
            <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->can('deleteBook')): ?>
            <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Вы уверены, что хотите удалить эту книгу?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'title',
            'year',
            'isbn',
            [
                'attribute' => 'description',
                'format' => 'ntext',
            ],
            [
                'label' => 'Авторы',
                'format' => 'html',
                'value' => function ($model) {
                    $authors = array_map(function ($author) {
                        return Html::a(Html::encode($author->fio), ['author/view', 'id' => $author->id]);
                    }, $model->authors);
                    return implode('<br>', $authors);
                },
            ],
            [
                'attribute' => 'cover_photo',
                'format' => 'html',
                'value' => function ($model) {
                    if ($model->cover_photo) {
                        return Html::img($model->getCoverUrl(), ['width' => 200, 'class' => 'img-thumbnail']);
                    }
                    return '<span class="text-muted">Нет обложки</span>';
                },
            ],
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

</div>
