<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $authors array */
/* @var $year int */
/* @var $availableYears array */

$this->title = 'Отчет: ТОП-10 авторов';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="report-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-md-12">
            <div class="well">
                <form method="get" action="<?= Url::to(['report/index']) ?>">
                    <div class="form-group">
                        <label>Выберите год:</label>
                        <select name="year" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($availableYears as $availableYear): ?>
                                <option value="<?= $availableYear ?>" <?= $availableYear == $year ? 'selected' : '' ?>>
                                    <?= $availableYear ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <h2>ТОП-10 авторов, выпустивших больше всего книг в <?= $year ?> году</h2>

    <?php if (empty($authors)): ?>
        <div class="alert alert-info">
            За <?= $year ?> год книги не найдены.
        </div>
    <?php else: ?>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Место</th>
                    <th>ФИО автора</th>
                    <th>Количество книг</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($authors as $index => $author): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= Html::encode($author['author_fio']) ?></td>
                        <td><?= $author['books_count'] ?></td>
                        <td>
                            <?= Html::a('Просмотр', ['author/view', 'id' => $author['author_id']], ['class' => 'btn btn-sm btn-primary']) ?>
                            <?= Html::a('Подписаться', ['subscription/create', 'author_id' => $author['author_id']], ['class' => 'btn btn-sm btn-info']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>
