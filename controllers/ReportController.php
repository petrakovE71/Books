<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\caching\TagDependency;
use app\common\repositories\BookRepository;

class ReportController extends Controller
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
                        'actions' => ['index'],
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex(): string
    {
        $year = (int)(Yii::$app->request->get('year') ?? date('Y'));

        $cacheKey = "report_top10_authors_{$year}";

        $authors = Yii::$app->cache->getOrSet(
            $cacheKey,
            function () use ($year) {
                /** @var BookRepository $repository */
                $repository = Yii::$app->get('bookRepository');
                return $repository->getTop10AuthorsByYear($year);
            },
            3600, // 1 hour
            new TagDependency(['tags' => ['books', 'authors']])
        );

        $availableYears = $this->getAvailableYears();

        return $this->render('index', [
            'authors' => $authors,
            'year' => $year,
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * @return array
     */
    private function getAvailableYears(): array
    {
        /** @var BookRepository $repository */
        $repository = Yii::$app->get('bookRepository');

        $stats = $repository->getBooksStatsByYears();

        return array_map(fn($item) => $item['year'], $stats);
    }
}
