<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\models\Book;
use app\models\Author;
use app\common\services\BookService;
use app\common\dto\CreateBookDto;
use app\common\exceptions\BookNotFoundException;
use app\common\exceptions\ValidationException;

/**
 * BookController
 */
class BookController extends Controller
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
                        'actions' => ['index', 'view'],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'update', 'delete'],
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->can('createBook');
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     *
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Book::find()->with('authors')->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     *
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        $book = $this->findModel($id);

        return $this->render('view', [
            'model' => $book,
        ]);
    }

    /**
     *
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Book();

        if ($model->load(Yii::$app->request->post())) {
            $model->coverFile = UploadedFile::getInstance($model, 'coverFile');

            if ($model->validate()) {
                try {
                    // Upload cover if exists
                    if ($model->coverFile) {
                        $model->uploadCover();
                    }

                   
                    $authorIds = Yii::$app->request->post('Book')['author_ids'] ?? [];

                    /** @var BookService $bookService */
                    $bookService = Yii::$app->get('bookService');

                    $dto = new CreateBookDto(
                        title: $model->title,
                        year: $model->year,
                        isbn: $model->isbn,
                        authorIds: $authorIds,
                        description: $model->description,
                        coverPhoto: $model->cover_photo
                    );

                    $book = $bookService->createBook($dto);

                    Yii::$app->session->setFlash('success', 'Книга успешно создана');
                    return $this->redirect(['view', 'id' => $book->id]);
                } catch (ValidationException $e) {
                    Yii::$app->session->setFlash('error', 'Ошибка валидации: ' . $e->getMessage());
                } catch (\Throwable $e) {
                    Yii::$app->session->setFlash('error', 'Ошибка создания книги: ' . $e->getMessage());
                }
            }
        }

       
        $authors = Author::find()->orderBy(['fio' => SORT_ASC])->all();

        return $this->render('create', [
            'model' => $model,
            'authors' => $authors,
        ]);
    }

    /**
     *
     *
     * @param int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $model->coverFile = UploadedFile::getInstance($model, 'coverFile');

            if ($model->validate()) {
                try {
                    // Upload cover if exists
                    if ($model->coverFile) {
                        $model->uploadCover();
                    }

                   
                    $authorIds = Yii::$app->request->post('Book')['author_ids'] ?? [];

                    /** @var BookService $bookService */
                    $bookService = Yii::$app->get('bookService');

                    $dto = new CreateBookDto(
                        title: $model->title,
                        year: $model->year,
                        isbn: $model->isbn,
                        authorIds: $authorIds,
                        description: $model->description,
                        coverPhoto: $model->cover_photo
                    );

                    $book = $bookService->updateBook($id, $dto);

                    Yii::$app->session->setFlash('success', 'Книга успешно обновлена');
                    return $this->redirect(['view', 'id' => $book->id]);
                } catch (ValidationException $e) {
                    Yii::$app->session->setFlash('error', 'Ошибка валидации: ' . $e->getMessage());
                } catch (BookNotFoundException $e) {
                    throw new NotFoundHttpException($e->getMessage());
                } catch (\Throwable $e) {
                    Yii::$app->session->setFlash('error', 'Ошибка обновления книги: ' . $e->getMessage());
                }
            }
        }

       
        $authors = Author::find()->orderBy(['fio' => SORT_ASC])->all();

       
        $selectedAuthorIds = array_map(fn($author) => $author->id, $model->authors);

        return $this->render('update', [
            'model' => $model,
            'authors' => $authors,
            'selectedAuthorIds' => $selectedAuthorIds,
        ]);
    }

    /**
     *
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete(int $id)
    {
        try {
            /** @var BookService $bookService */
            $bookService = Yii::$app->get('bookService');
            $bookService->deleteBook($id);

            Yii::$app->session->setFlash('success', 'Книга успешно удалена');
        } catch (BookNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', 'Ошибка удаления книги: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     *
     *
     * @param int $id
     * @return Book
     * @throws NotFoundHttpException
     */
    protected function findModel(int $id): Book
    {
        $model = Book::find()->with('authors')->where(['id' => $id])->one();

        if ($model === null) {
            throw new NotFoundHttpException('Книга не найдена');
        }

        return $model;
    }
}
