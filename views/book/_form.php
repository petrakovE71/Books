<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Book */
/* @var $form yii\widgets\ActiveForm */
/* @var $authors app\models\Author[] */
/* @var $selectedAuthorIds array */

$selectedAuthorIds = $selectedAuthorIds ?? [];
?>

<div class="book-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'year')->textInput(['type' => 'number', 'min' => 1000, 'max' => date('Y') + 1]) ?>

    <?= $form->field($model, 'isbn')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'coverFile')->fileInput() ?>

    <?php if ($model->cover_photo): ?>
        <div class="form-group">
            <label>Текущая обложка:</label><br>
            <?= Html::img($model->getCoverUrl(), ['width' => 200, 'class' => 'img-thumbnail']) ?>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <label>Авторы <span class="text-danger">*</span></label>
        <?= Html::checkboxList(
            'Book[author_ids]',
            $selectedAuthorIds,
            ArrayHelper::map($authors, 'id', 'fio'),
            ['class' => 'checkbox']
        ) ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
