# Testing Guide

This guide provides comprehensive instructions for setting up and running tests in the Book Catalog application.

## Overview

The test suite includes:
- **Unit Tests** (~107 tests): Models, Services, Repositories, DTOs, SMS, Events
- **Functional Tests** (~50 tests): Controllers and RBAC
- **Acceptance Tests** (~30 tests): End-to-end user workflows
- **Integration Tests** (~40 tests): Component interactions, SMS, Console commands, Caching

**Total**: ~227 tests providing comprehensive coverage

## Prerequisites

1. PHP 8.3 or higher
2. Composer
3. MySQL or MariaDB
4. Codeception testing framework

## Installation

### 1. Install Dependencies

```bash
composer install
```

### 2. Create Test Database

Create a separate database for testing:

```sql
CREATE DATABASE books_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Configure Test Database

The test database configuration is located in `config/test_db.php`:

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

Update with your database credentials if needed.

### 4. Run Migrations for Test Database

```bash
php yii migrate --db=testdb
```

### 5. Initialize RBAC for Tests

```bash
php yii migrate --migrationPath=@yii/rbac/migrations --db=testdb
```

### 6. Build Codeception Test Helpers

```bash
./vendor/bin/codecept build
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

## Test Suites Description

### Unit Tests (`tests/unit/`)

Test individual components in isolation:

- **Models** (`models/`): Book, Author, Subscription, NotificationQueue
- **Services** (`services/`): BookService, SubscriptionService, NotificationService
- **Repositories** (`repositories/`): BookRepository, SubscriptionRepository
- **DTOs** (`dto/`): CreateBookDto, CreateSubscriptionDto, NotificationDto
- **SMS** (`sms/`): SmsService, SmsPilotProvider
- **Events** (`events/`): BookCreatedEvent
- **Handlers** (`handlers/`): BookCreatedHandler

### Functional Tests (`tests/functional/`)

Test HTTP requests and responses:

- **Controllers**: Book, Author, Subscription, Report
- **RBAC**: Permission checks and access control
- **Forms**: Form validation and submission
- **Routing**: URL routing and parameters

### Acceptance Tests (`tests/acceptance/`)

Test complete user workflows in a browser-like environment:

- **Book Management**: Complete CRUD workflow
- **Author Management**: Author lifecycle management
- **Subscription Workflow**: Guest subscription process
- **Reports**: Viewing and filtering reports
- **Guest vs User**: Permission differences

### Integration Tests (`tests/integration/`)

Test component interactions:

- **Event System**: BookCreated → Notifications
- **Notification Queue**: Processing with retry logic
- **Database Transactions**: Commit and rollback behavior
- **SMS Integration**: End-to-end SMS sending
- **Console Commands**: Notification processing
- **Report Caching**: Cache behavior and invalidation

## Test Options

### Verbose Output

```bash
./vendor/bin/codecept run --verbose
```

### Debug Mode

```bash
./vendor/bin/codecept run --debug
```

### Generate HTML Report

```bash
./vendor/bin/codecept run --html
```

The report will be generated in `tests/_output/report.html`.

### Generate Coverage Report

Requires Xdebug or PCOV:

```bash
./vendor/bin/codecept run --coverage --coverage-html
```

Coverage report will be in `tests/_output/coverage/`.

### Run Tests in Parallel

Requires `codeception/robo-paracept`:

```bash
composer require codeception/robo-paracept --dev
./vendor/bin/robo parallel:run
```

## Fixtures

Fixtures provide consistent test data. Located in `tests/fixtures/`:

- `UserFixture.php`: Test users (admin, user1, inactive)
- `AuthorFixture.php`: Sample authors
- `BookFixture.php`: Sample books
- `SubscriptionFixture.php`: Sample subscriptions
- `NotificationQueueFixture.php`: Sample notifications

Fixtures are loaded automatically in tests that define `_fixtures()` method.

## Configuration Files

### Main Configuration

- `codeception.yml`: Main Codeception configuration
- `config/test.php`: Application configuration for tests
- `config/test_db.php`: Test database configuration

### Suite Configurations

- `tests/unit.suite.yml`: Unit test suite
- `tests/functional.suite.yml`: Functional test suite
- `tests/acceptance.suite.yml`: Acceptance test suite
- `tests/integration.suite.yml`: Integration test suite

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
        $this->assertArrayHasKey('title', $book->errors);
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
        $I->seeResponseCodeIsSuccessful();
        $I->see('Books');
    }
}
```

### Using Fixtures

```php
public function _fixtures(): array
{
    return [
        'books' => BookFixture::class,
        'authors' => AuthorFixture::class,
    ];
}

public function testSomething(): void
{
    $book = $this->tester->grabFixture('books', 'book1');
    // Use $book in your test
}
```

## Best Practices

1. **Test Isolation**: Each test should be independent
2. **Use Fixtures**: Provide consistent test data
3. **Clear Assertions**: Include descriptive messages
4. **Proper Cleanup**: Tests clean up after themselves
5. **Meaningful Names**: Use descriptive test method names
6. **Test One Thing**: Each test should verify one behavior
7. **Mock External Services**: Use mocks for SMS, APIs, etc.

## Troubleshooting

### Database Connection Issues

**Problem**: Cannot connect to test database

**Solution**:
1. Verify `config/test_db.php` settings
2. Ensure test database exists
3. Check MySQL is running
4. Verify credentials

### Fixtures Not Loading

**Problem**: Fixture data not available in tests

**Solution**:
1. Check fixture dependencies
2. Verify `_fixtures()` method is defined
3. Ensure fixture data files exist in `tests/fixtures/data/`

### Tests Failing

**Problem**: Random test failures

**Solution**:
1. Run with `--debug` flag for details
2. Check test database state
3. Clear cache: `rm -rf runtime/cache/*`
4. Rebuild test helpers: `./vendor/bin/codecept build`

### Permission Errors

**Problem**: Cannot write to output directory

**Solution**:
```bash
chmod -R 777 tests/_output
chmod -R 777 runtime
```

### RBAC Errors

**Problem**: RBAC permissions not working

**Solution**:
```bash
php yii migrate --migrationPath=@yii/rbac/migrations --db=testdb
```

## Continuous Integration

### GitHub Actions Example

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: books_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, intl, pdo_mysql
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run migrations
        run: |
          php yii migrate --interactive=0 --db=testdb
          php yii migrate --migrationPath=@yii/rbac/migrations --interactive=0 --db=testdb

      - name: Build test helpers
        run: ./vendor/bin/codecept build

      - name: Run tests
        run: ./vendor/bin/codecept run --coverage-xml

      - name: Upload coverage
        uses: codecov/codecov-action@v3
```

## Performance

Expected execution times:
- **Unit Tests**: ~30 seconds
- **Functional Tests**: ~45 seconds
- **Acceptance Tests**: ~60 seconds
- **Integration Tests**: ~45 seconds
- **Total**: ~3 minutes

## Test Statistics

- **Test Files**: 43
- **Total Tests**: ~227
- **Target Coverage**: 80%+
- **Test Execution Time**: < 3 minutes

## Additional Resources

- [Codeception Documentation](https://codeception.com/docs/01-Introduction)
- [Yii2 Testing Guide](https://www.yiiframework.com/doc/guide/2.0/en/test-overview)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

## Support

For issues or questions:
1. Check this guide
2. Review test examples in `tests/`
3. Check test output with `--debug` flag
4. Review `tests/README.md` for detailed documentation

## Quick Reference

```bash
# Setup
composer install
./vendor/bin/codecept build

# Run all tests
./vendor/bin/codecept run

# Run with output
./vendor/bin/codecept run --verbose

# Run specific suite
./vendor/bin/codecept run unit
./vendor/bin/codecept run functional
./vendor/bin/codecept run acceptance
./vendor/bin/codecept run integration

# Run specific test
./vendor/bin/codecept run unit models/BookTest
./vendor/bin/codecept run unit models/BookTest:testValidation

# Generate reports
./vendor/bin/codecept run --html
./vendor/bin/codecept run --coverage --coverage-html

# Debug
./vendor/bin/codecept run --debug
./vendor/bin/codecept run --steps
```
