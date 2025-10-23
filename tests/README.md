# Tests Documentation

## Overview

This test suite provides comprehensive coverage for the Book Catalog application using Codeception framework.

## Test Structure

```
tests/
├── unit/              # Unit tests (isolated component testing)
│   ├── models/        # Model tests (4 files, ~50 tests)
│   ├── services/      # Service tests (3 files, ~20 tests)
│   ├── repositories/  # Repository tests (2 files, ~25 tests)
│   └── dto/           # DTO tests (3 files, ~12 tests)
├── functional/        # Functional tests (HTTP request testing)
│   ├── BookControllerCest.php
│   ├── AuthorControllerCest.php
│   ├── SubscriptionControllerCest.php
│   ├── ReportControllerCest.php
│   └── RbacCest.php
├── acceptance/        # Acceptance tests (E2E browser testing)
│   ├── BookManagementCest.php
│   ├── SubscriptionWorkflowCest.php
│   └── ReportsWorkflowCest.php
├── integration/       # Integration tests (component interaction)
│   ├── BookCreatedEventIntegrationTest.php
│   ├── NotificationQueueProcessingTest.php
│   └── DatabaseTransactionsTest.php
└── fixtures/          # Test data fixtures
    ├── UserFixture.php
    ├── AuthorFixture.php
    ├── BookFixture.php
    ├── SubscriptionFixture.php
    └── NotificationQueueFixture.php
```

## Test Coverage

### Unit Tests (~107 tests)
- **Models**: Book, Author, Subscription, NotificationQueue
- **Services**: BookService, SubscriptionService, NotificationService
- **Repositories**: BookRepository, SubscriptionRepository
- **DTOs**: CreateBookDto, CreateSubscriptionDto, NotificationDto

### Functional Tests (~50+ tests)
- Controllers: Book, Author, Subscription, Report
- RBAC permissions and access control

### Acceptance Tests (~20+ tests)
- Complete user workflows (E2E)
- Book management lifecycle
- Subscription process
- Reports viewing

### Integration Tests (~30+ tests)
- Event system (BookCreated → Notifications)
- Notification queue processing
- Database transactions
- Retry mechanisms

## Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Test Database

Create `config/test_db.php`:

```php
<?php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=books_test',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

### 3. Run Migrations for Test Database

```bash
php yii migrate --db=testdb
```

### 4. Initialize RBAC for Tests

```bash
php yii migrate --migrationPath=@yii/rbac/migrations --db=testdb
```

## Running Tests

### Run All Tests

```bash
./vendor/bin/codecept run
```

### Run Specific Test Suite

```bash
# Unit tests only
./vendor/bin/codecept run unit

# Functional tests only
./vendor/bin/codecept run functional

# Acceptance tests only
./vendor/bin/codecept run acceptance

# Integration tests
./vendor/bin/codecept run integration
```

### Run Specific Test File

```bash
# Run single test file
./vendor/bin/codecept run unit models/BookTest

# Run with detailed output
./vendor/bin/codecept run unit models/BookTest --debug

# Run with steps output
./vendor/bin/codecept run unit models/BookTest --steps
```

### Run Specific Test Method

```bash
./vendor/bin/codecept run unit models/BookTest:testValidation
```

## Test Options

```bash
# Verbose output
./vendor/bin/codecept run --verbose

# Debug mode
./vendor/bin/codecept run --debug

# Generate HTML report
./vendor/bin/codecept run --html

# Generate coverage report (requires XDebug)
./vendor/bin/codecept run --coverage --coverage-html
```

## Writing New Tests

### Unit Test Example

```php
<?php

namespace tests\unit\models;

use app\models\Book;
use Codeception\Test\Unit;

class BookTest extends Unit
{
    public function testValidation(): void
    {
        $book = new Book();
        $this->assertFalse($book->validate());
    }
}
```

### Functional Test Example

```php
<?php

namespace tests\functional;

use FunctionalTester;

class BookControllerCest
{
    public function testIndex(FunctionalTester $I): void
    {
        $I->amOnPage('/book/index');
        $I->see('Books');
    }
}
```

### Acceptance Test Example

```php
<?php

namespace tests\acceptance;

use AcceptanceTester;

class BookManagementCest
{
    public function testCreateBook(AcceptanceTester $I): void
    {
        $I->amOnPage('/site/login');
        $I->fillField('username', 'admin');
        $I->fillField('password', 'admin123');
        $I->click('Login');

        $I->amOnPage('/book/create');
        $I->fillField('title', 'Test Book');
        $I->click('Create');
        $I->see('Book successfully created');
    }
}
```

## Fixtures

Fixtures provide test data. To use fixtures in your tests:

```php
public function _fixtures(): array
{
    return [
        'books' => BookFixture::class,
        'authors' => AuthorFixture::class,
    ];
}
```

Access fixture data:

```php
$book = $this->tester->grabFixture('books', 'book1');
```

## Best Practices

1. **Isolation**: Each test should be independent
2. **Fixtures**: Use fixtures for consistent test data
3. **Cleanup**: Tests should clean up after themselves
4. **Naming**: Use descriptive test method names
5. **Assertions**: Include clear assertion messages
6. **Coverage**: Aim for high code coverage
7. **Speed**: Keep tests fast; use mocks for external services

## CI/CD Integration

Add to your CI pipeline:

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: ./vendor/bin/codecept run
```

## Troubleshooting

### Database Connection Issues
- Check `config/test_db.php` configuration
- Ensure test database exists
- Verify migrations are applied

### Fixtures Not Loading
- Check fixture dependencies
- Verify data file paths
- Ensure `_fixtures()` method is defined

### Tests Failing
- Run with `--debug` flag for detailed output
- Check test database state
- Review error messages carefully

## Statistics

- **Total Test Files**: 35+
- **Total Tests**: ~200+
- **Code Coverage**: Aim for 80%+
- **Test Execution Time**: < 2 minutes

## Support

For issues or questions about tests:
1. Check this README
2. Review test examples
3. Check Codeception documentation: https://codeception.com
