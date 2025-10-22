<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Subscription */
/* @var $authors app\models\Author[] */

$this->title = 'Подписаться на новые книги автора';
$this->params['breadcrumbs'][] = $this->title;

// Если передан author_id в GET, предустановим его
$preselectedAuthorId = Yii::$app->request->get('author_id');
if ($preselectedAuthorId && $model->isNewRecord) {
    $model->author_id = $preselectedAuthorId;
}
?>
<div class="subscription-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        Укажите свои контактные данные, чтобы получать SMS уведомления о выходе новых книг выбранного автора.
    </p>

    <div class="subscription-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'author_id')->dropDownList(
            ArrayHelper::map($authors, 'id', 'fio'),
            ['prompt' => 'Выберите автора']
        ) ?>

        <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Иван Иванов']) ?>

        <?= $form->field($model, 'phone')->textInput([
            'maxlength' => true,
            'placeholder' => '+79991234567'
        ])->hint('Укажите телефон в международном формате, например: +79991234567') ?>

        <div class="form-group">
            <?= Html::submitButton('Подписаться', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
