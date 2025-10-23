<?php

declare(strict_types=1);

namespace tests\functional;

use app\models\User;
use tests\fixtures\UserFixture;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use FunctionalTester;

class RbacCest
{
    public function _fixtures(): array
    {
        return [
            'users' => UserFixture::class,
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testGuestCanViewBooks(FunctionalTester $I): void
    {
        $I->amOnPage('/book/index');
        $I->seeResponseCodeIs(200);

        $I->amOnPage('/book/view?id=1');
        $I->seeResponseCodeIs(200);
    }

    public function testGuestCannotCreateBook(FunctionalTester $I): void
    {
        $I->amOnPage('/book/create');
        $I->seeResponseCodeIs(302);
        $I->seeInCurrentUrl('/site/login');
    }

    public function testGuestCannotUpdateBook(FunctionalTester $I): void
    {
        $I->amOnPage('/book/update?id=1');
        $I->seeResponseCodeIs(302);
        $I->seeInCurrentUrl('/site/login');
    }

    public function testGuestCannotDeleteBook(FunctionalTester $I): void
    {
        $I->sendAjaxPostRequest('/book/delete?id=1');
        $I->seeResponseCodeIs(302);
    }

    public function testUserWithoutPermissionCannotCreateBook(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'user1']);
        $I->amLoggedInAs($user);

        $I->amOnPage('/book/create');
        $I->seeResponseCodeIs(403);
    }

    public function testUserWithPermissionCanCreateBook(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        $authManager = \Yii::$app->authManager;
        $permission = $authManager->getPermission('createBook');
        if ($permission) {
            $authManager->assign($permission, $user->id);
        }

        $I->amOnPage('/book/create');
        $I->seeResponseCodeIs(200);
        $I->see('Create Book', 'h1');
    }

    public function testUserWithPermissionCanUpdateBook(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        $authManager = \Yii::$app->authManager;
        $permission = $authManager->getPermission('updateBook');
        if ($permission) {
            $authManager->assign($permission, $user->id);
        }

        $I->amOnPage('/book/update?id=1');
        $I->seeResponseCodeIs(200);
        $I->see('Update Book', 'h1');
    }

    public function testUserWithPermissionCanDeleteBook(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        $authManager = \Yii::$app->authManager;
        $permission = $authManager->getPermission('deleteBook');
        if ($permission) {
            $authManager->assign($permission, $user->id);
        }

        $I->sendAjaxPostRequest('/book/delete?id=1');
        $I->seeResponseCodeIs(302);
    }

    public function testGuestCanViewAuthors(FunctionalTester $I): void
    {
        $I->amOnPage('/author/index');
        $I->seeResponseCodeIs(200);

        $I->amOnPage('/author/view?id=1');
        $I->seeResponseCodeIs(200);
    }

    public function testGuestCannotCreateAuthor(FunctionalTester $I): void
    {
        $I->amOnPage('/author/create');
        $I->seeResponseCodeIs(302);
        $I->seeInCurrentUrl('/site/login');
    }

    public function testUserCanCreateAuthorWithPermission(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        $authManager = \Yii::$app->authManager;
        $permission = $authManager->getPermission('createAuthor');
        if ($permission) {
            $authManager->assign($permission, $user->id);
        }

        $I->amOnPage('/author/create');
        $I->seeResponseCodeIs(200);
    }

    public function testGuestCanViewReports(FunctionalTester $I): void
    {
        $I->amOnPage('/report/index');
        $I->seeResponseCodeIs(200);
    }

    public function testGuestCanSubscribe(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/create');
        $I->seeResponseCodeIs(200);
    }

    public function testAllPermissionsExist(FunctionalTester $I): void
    {
        $authManager = \Yii::$app->authManager;

        $permissions = [
            'viewBooks',
            'createBook',
            'updateBook',
            'deleteBook',
            'viewAuthors',
            'createAuthor',
            'updateAuthor',
            'deleteAuthor',
        ];

        foreach ($permissions as $permissionName) {
            $permission = $authManager->getPermission($permissionName);
            $I->assertNotNull($permission, "Permission {$permissionName} should exist");
        }
    }

    public function testUserRoleExists(FunctionalTester $I): void
    {
        $authManager = \Yii::$app->authManager;
        $role = $authManager->getRole('user');

        $I->assertNotNull($role, 'User role should exist');
    }
}
