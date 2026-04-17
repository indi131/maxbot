---
name: Max Bot Backend
overview: Разработать PHP-бэкенд для бота мессенджера MAX с правильной OOP-архитектурой (Contracts → Repository → Service → Controller), Composer PSR-4 автозагрузкой, и возможностью лёгкого расширения.
todos:
  - id: composer
    content: Создать composer.json с PSR-4 autoload и .env.example
    status: pending
  - id: schema
    content: Создать sql/schema.sql с таблицами users, notifications, admins
    status: pending
  - id: config
    content: Написать src/Config.php — загрузка .env переменных
    status: pending
  - id: database
    content: Написать src/Database/Connection.php — PDO singleton
    status: pending
  - id: contracts
    content: Написать интерфейсы в src/Contracts/ (MessengerInterface, UserRepositoryInterface, NotificationRepositoryInterface)
    status: pending
  - id: messenger
    content: Написать src/Messenger/MaxBot.php — реализация MessengerInterface для MAX Bot API
    status: pending
  - id: repositories
    content: Написать src/Repository/UserRepository.php и NotificationRepository.php
    status: pending
  - id: services
    content: Написать src/Service/NotificationService.php, BotService.php, AdminService.php
    status: pending
  - id: http
    content: Написать src/Http/Request.php и Response.php
    status: pending
  - id: auth
    content: Написать src/Auth/AdminAuth.php
    status: pending
  - id: bootstrap
    content: Написать bootstrap.php — сборка DI-контейнера (зависимости вручную)
    status: pending
  - id: entrypoints
    content: Написать точки входа public/notify.php и public/webhook.php
    status: pending
  - id: webapp
    content: Написать public/webapp/index.php и detail.php — Mini-App
    status: pending
  - id: admin
    content: Написать public/admin/login.php и index.php — admin-панель
    status: pending
  - id: assets
    content: Написать assets/app.css и admin.css — стили
    status: pending
isProject: false
---

# Max Bot Backend — план реализации (OOP)

## Принципы архитектуры

- **Layered architecture**: точки входа → Service → Repository → Database
- **Dependency Inversion**: слои зависят от интерфейсов (`Contracts`), а не от конкретных классов
- **Single Responsibility**: каждый класс отвечает за одну задачу
- **Composer PSR-4**: весь src-код в неймспейсе `App\`, автозагрузка через Composer
- **Без фреймворка**: чистый PHP, минимум зависимостей (`vlucas/phpdotenv`)

## Структура директорий

```
backend/
├── composer.json              # PSR-4 autoload, зависимости
├── .env.example
├── bootstrap.php              # сборка зависимостей (DI вручную)
├── public/
│   ├── notify.php             # точка входа: POST /notify (заявка с сайта)
│   ├── webhook.php            # точка входа: MAX webhook
│   ├── webapp/
│   │   ├── index.php          # Mini-App: список заявок
│   │   └── detail.php         # Mini-App: одна заявка
│   └── admin/
│       ├── login.php          # Admin: вход
│       └── index.php          # Admin: список клиентов
├── src/
│   ├── Config.php             # читает .env, геттеры
│   ├── Contracts/
│   │   ├── MessengerInterface.php
│   │   ├── UserRepositoryInterface.php
│   │   └── NotificationRepositoryInterface.php
│   ├── Database/
│   │   └── Connection.php     # PDO singleton
│   ├── Messenger/
│   │   └── MaxBot.php         # implements MessengerInterface
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   └── NotificationRepository.php
│   ├── Service/
│   │   ├── NotificationService.php
│   │   ├── BotService.php
│   │   └── AdminService.php
│   ├── Http/
│   │   ├── Request.php        # парсит php://input, GET/POST, валидация
│   │   └── Response.php       # JSON-ответы, коды статуса
│   └── Auth/
│       └── AdminAuth.php      # сессия администратора
├── assets/
│   ├── app.css
│   └── admin.css
└── sql/
    └── schema.sql
```

## Слои и зависимости

```
public/*.php (точки входа)
    └── bootstrap.php (DI-сборка)
         ├── Service\NotificationService
         │       ├── Contracts\UserRepositoryInterface  ← Repository\UserRepository
         │       ├── Contracts\NotificationRepositoryInterface ← Repository\NotificationRepository
         │       └── Contracts\MessengerInterface       ← Messenger\MaxBot
         ├── Service\BotService
         │       ├── Contracts\UserRepositoryInterface
         │       └── Contracts\MessengerInterface
         └── Service\AdminService
                 ├── Contracts\UserRepositoryInterface
                 ├── Contracts\NotificationRepositoryInterface
                 └── Contracts\MessengerInterface
```

## Interfaces (`src/Contracts/`)

### `MessengerInterface`

```php
interface MessengerInterface {
    public function sendMessage(int $userId, string $text, array $buttons = []): void;
    public function setWebhook(string $url, string $secret): bool;
}
```

### `UserRepositoryInterface`

```php
interface UserRepositoryInterface {
    public function findBySecretKey(string $key): ?array;
    public function findByUserId(int $userId): ?array;
    public function create(string $secretKey, int $userId, string $username, string $firstName): void;
    public function touchActivity(string $secretKey): void;
    public function delete(int $id): void;
    public function listAll(int $limit, int $offset): array;
    public function countAll(): int;
}
```

### `NotificationRepositoryInterface`

```php
interface NotificationRepositoryInterface {
    public function create(string $secretKey, string $content): int;  // returns id
    public function findById(int $id): ?array;
    public function findByUserId(int $userId, int $limit = 20): array;
    public function countBySecretKey(string $secretKey): int;
}
```

## Классы

### `src/Messenger/MaxBot.php` — implements `MessengerInterface`

- `sendMessage()` — `POST https://platform-api.max.ru/messages?user_id={id}` с `Authorization: Bearer {token}`
- `setWebhook()` — `POST /subscriptions` с `update_types: ["message_created","bot_started"]` и `secret`
- Кнопка webapp: `{"type":"link","text":"Открыть","url":"{WEBAPP_URL}/detail.php?id={id}"}`
- Приватный `request(string $method, string $endpoint, array $body)` — cURL-обёртка

### `src/Service/NotificationService.php`

Принимает через конструктор: `UserRepositoryInterface`, `NotificationRepositoryInterface`, `MessengerInterface`, `Config`

- `receive(string $secretKey, string $content): array` — сохранить, найти user, отправить MAX-сообщение, обновить activity, вернуть `['status' => 'ok', 'id' => ...]` или бросить `\RuntimeException`

### `src/Service/BotService.php`

Принимает: `UserRepositoryInterface`, `MessengerInterface`

- `handleUpdate(array $update): void` — диспетчер событий:
  - `bot_started` → `onBotStarted(int $userId)`
  - `message_created` → `onMessageCreated(int $userId, string $text, array $sender)`
- `onBotStarted()` — приветствие + инструкция по ключу
- `onMessageCreated()` — регистрация ключа или уведомление о статусе

### `src/Service/AdminService.php`

Принимает: `UserRepositoryInterface`, `NotificationRepositoryInterface`, `MessengerInterface`

- `getClients(int $page): array` — список клиентов с кол-вом заявок, пагинация
- `deleteClient(int $id): void` — удалить пользователя + уведомить его через MAX

### `src/Http/Request.php`

- `getJson(): array` — декодирует `php://input`, бросает `\InvalidArgumentException` при невалидном JSON
- `get(string $key, mixed $default = null): mixed` — `$_GET`
- `post(string $key, mixed $default = null): mixed` — `$_POST`
- `header(string $name): ?string` — HTTP-заголовок

### `src/Http/Response.php`

- `json(array $data, int $status = 200): never` — выставить заголовок, код, echo JSON, exit
- `html(string $content, int $status = 200): never`

### `bootstrap.php` — сборка зависимостей

```php
// Пример сборки (без контейнера, явный DI)
$config = new Config();
$pdo    = Connection::getInstance($config);
$userRepo = new UserRepository($pdo);
$notifRepo = new NotificationRepository($pdo);
$messenger = new MaxBot($config->get('MAX_BOT_TOKEN'), $config->get('WEBAPP_URL'));
$notificationService = new NotificationService($userRepo, $notifRepo, $messenger, $config);
$botService  = new BotService($userRepo, $messenger);
$adminService = new AdminService($userRepo, $notifRepo, $messenger);
```

## База данных

```sql
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    secret_key    VARCHAR(64)  UNIQUE NOT NULL,
    user_id       BIGINT       UNIQUE NOT NULL,
    username      VARCHAR(255) DEFAULT '',
    first_name    VARCHAR(255) DEFAULT '',
    registered_at DATETIME     DEFAULT NOW(),
    last_activity DATETIME     DEFAULT NOW()
);

CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    secret_key VARCHAR(64) NOT NULL,
    content    TEXT        NOT NULL,
    created_at DATETIME    DEFAULT NOW(),
    INDEX idx_secret_key (secret_key)
);

CREATE TABLE admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    login         VARCHAR(64)  UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);
```

## Конфигурация (`.env`)

- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
- `MAX_BOT_TOKEN` — токен из business.max.ru → Чат-боты → Интеграция
- `MAX_WEBHOOK_SECRET` — секрет для заголовка `X-Max-Bot-Api-Secret`
- `WEBAPP_URL` — публичный HTTPS-адрес (без слеша в конце)

## Точки входа (`public/`)

### `notify.php`

```
POST /notify   JSON: {"password":"...", "data":"..."}
```

- Создаёт `Request`, вызывает `$notificationService->receive()`, возвращает `Response::json()`
- При `RuntimeException` — `Response::json(['error' => ...], 404)`

### `webhook.php`

- Проверяет `Request::header('X-Max-Bot-Api-Secret')` == `MAX_WEBHOOK_SECRET`
- Вызывает `$botService->handleUpdate(Request::getJson())`
- Всегда возвращает `Response::json(['ok' => true])`

## MAX Bot API — отличия от Telegram

- Base URL: `https://platform-api.max.ru` (не `api.telegram.org`)
- Auth: заголовок `Authorization: Bearer {token}` (не токен в URL)
- user ID: поле `user_id` (не `chat_id`)
- Webhook secret: заголовок `X-Max-Bot-Api-Secret`
- Старт бота: `update_type = "bot_started"` (не `/start` команда)

## Расширяемость

- Сменить мессенджер (например, добавить Telegram) — реализовать `MessengerInterface`, заменить в `bootstrap.php`
- Сменить хранилище (Redis, MongoDB) — реализовать `UserRepositoryInterface` / `NotificationRepositoryInterface`
- Добавить новый тип события в webhook — добавить метод в `BotService` без изменений остальных слоёв

