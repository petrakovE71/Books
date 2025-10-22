# Каталог книг - Инструкция по установке

## Требования

- PHP >= 8.0
- MySQL >= 5.7 или MariaDB >= 10.2
- Composer
- Веб-сервер (Apache/Nginx) или встроенный сервер PHP

## Установка

### 1. Установка зависимостей

```bash
composer install
```

### 2. Настройка базы данных

Создайте базу данных MySQL:

```sql
CREATE DATABASE book_catalog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Настройка окружения

Скопируйте файл `.env.example` в `.env`:

```bash
cp .env.example .env
```

Отредактируйте `.env` и укажите настройки вашей базы данных:

```env
DB_HOST=localhost
DB_NAME=book_catalog
DB_USERNAME=root
DB_PASSWORD=your_password

# Для тестирования используйте ключ-эмулятор SmsPilot
SMS_PILOT_API_KEY=XXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZXXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZ
SMS_TEST_MODE=true

YII_DEBUG=true
YII_ENV=dev
```

### 4. Применение миграций

Выполните миграции для создания таблиц:

```bash
./yii migrate
```

Это создаст:
- Таблицу пользователей с тестовым пользователем `admin/admin123`
- Таблицы для книг, авторов, подписок, очереди уведомлений
- RBAC таблицы и роли

### 5. Создание директории для загрузок

```bash
mkdir -p web/uploads/books
chmod 755 web/uploads/books
touch web/uploads/books/.gitkeep
```

### 6. Запуск приложения

#### Вариант 1: Встроенный сервер PHP (для разработки)

```bash
./yii serve --port=8080
```

Приложение будет доступно по адресу: http://localhost:8080

#### Вариант 2: Apache/Nginx

Настройте виртуальный хост, указывающий на директорию `web/`.

Пример для Apache:

```apache
<VirtualHost *:80>
    ServerName book-catalog.local
    DocumentRoot "/path/to/Books/web"

    <Directory "/path/to/Books/web">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Тестовые данные

После применения миграций будут созданы:

- **Пользователь**: `admin` / `admin123`
- **5 тестовых авторов**: Толстой, Достоевский, Пушкин, Чехов, Тургенев

## Структура приложения

```
common/
  ├── dto/              # Data Transfer Objects
  ├── events/           # Event классы
  ├── exceptions/       # Custom exceptions
  ├── handlers/         # Event handlers
  ├── interfaces/       # Интерфейсы (SMS провайдер)
  ├── repositories/     # Repository layer (сложные запросы)
  └── services/         # Service layer (бизнес-логика)

components/
  └── sms/              # SMS интеграция (SmsPilot)

controllers/          # Контроллеры
models/               # ActiveRecord модели
views/                # Представления
migrations/           # Миграции БД
commands/             # Console команды
```

## Основные функции

### Для гостей (неаутентифицированные):
- ✅ Просмотр списка книг
- ✅ Просмотр списка авторов
- ✅ Просмотр отчета топ-10 авторов
- ✅ Подписка на новые книги автора

### Для пользователей (после входа):
- ✅ Все функции гостя
- ✅ Создание/редактирование/удаление книг
- ✅ Создание/редактирование/удаление авторов
- ✅ Загрузка обложек книг

## SMS Уведомления

### Отправка уведомлений

Для обработки очереди SMS уведомлений используйте console команду:

```bash
# Отправить до 100 уведомлений
./yii notification/send

# Отправить до 50 уведомлений
./yii notification/send --limit=50

# Показать статистику очереди
./yii notification/stats

# Очистить старые отправленные уведомления (>30 дней)
./yii notification/cleanup
```

### Настройка Cron для автоматической отправки

Добавьте в crontab:

```cron
# Каждые 5 минут обрабатывать очередь уведомлений
*/5 * * * * cd /path/to/Books && ./yii notification/send >> /var/log/notifications.log 2>&1

# Раз в день очищать старые уведомления
0 3 * * * cd /path/to/Books && ./yii notification/cleanup >> /var/log/notifications.log 2>&1
```

### Тестовый режим SMS

По умолчанию включен тестовый режим (`SMS_TEST_MODE=true`), SMS не отправляются реально, только логируются.

Для реальной отправки:
1. Получите API ключ на https://smspilot.ru/apikey.php
2. Установите `SMS_TEST_MODE=false` в `.env`
3. Укажите реальный `SMS_PILOT_API_KEY`

## Архитектурные особенности

### Production-Ready Features

✅ **Layered Architecture**: Controllers → Services → Repositories → Models
✅ **Event-Driven**: BookCreatedEvent → NotificationHandler (decoupling)
✅ **DTOs**: Передача данных между слоями
✅ **Repository Pattern**: Сложные запросы изолированы
✅ **Service Layer**: Вся бизнес-логика в сервисах
✅ **Dependency Injection**: Все компоненты в DI container

### SMS Integration (Enterprise-level)

✅ **Interface-based**: Легко заменить провайдера
✅ **Retry Logic**: Exponential backoff (1s, 2s, 4s...)
✅ **Rate Limiting**: Max 30 requests/minute
✅ **Circuit Breaker**: Автоматическое отключение при сбоях
✅ **Queue Processing**: Background processing с retry
✅ **Error Recovery**: Dead letter queue для failed messages

### Database Optimization

✅ **Индексы** на всех частых запросах
✅ **Soft Deletes** для книг и авторов
✅ **Eager Loading** для связей
✅ **Cache** для отчета топ-10 (1 час)
✅ **Unique constraints** (ISBN, phone+author_id)

### Security & Validation

✅ **RBAC** с DbManager (роли и права)
✅ **Access Control** на всех экшенах
✅ **ISBN validation** (ISBN-10/13)
✅ **Phone validation** (международный формат)
✅ **File upload validation** (mime types, size)
✅ **Input sanitization** везде

## API для разработки

### Доступные URL

```
GET  /books              # Список книг
GET  /book/123           # Просмотр книги
GET  /book/create        # Форма создания (требует auth)
POST /book/create        # Создать книгу (требует auth)
GET  /book/update/123    # Форма редактирования (требует auth)
POST /book/update/123    # Обновить книгу (требует auth)
POST /book/delete/123    # Удалить книгу (требует auth)

GET  /authors            # Список авторов
GET  /author/123         # Просмотр автора + его книги
GET  /author/create      # Создать автора (требует auth)

GET  /subscribe          # Форма подписки (доступно всем)
POST /subscribe          # Создать подписку

GET  /report             # Отчет топ-10 авторов
GET  /report?year=2023   # Отчет за конкретный год
```

## Логи

Логи приложения находятся в `runtime/logs/app.log`

Для просмотра логов SMS отправки:

```bash
tail -f runtime/logs/app.log | grep SMS
```

## Troubleshooting

### Ошибка подключения к БД

Проверьте настройки в `.env` и убедитесь, что MySQL запущен:

```bash
mysql -u root -p -e "SHOW DATABASES;"
```

### Ошибки прав доступа

```bash
chmod -R 777 runtime web/assets web/uploads
```

### Пересоздать БД с нуля

```bash
./yii migrate/down all
./yii migrate
```

## Тестирование

Для запуска тестов (если настроены):

```bash
composer run test
```
