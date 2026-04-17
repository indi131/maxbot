CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    secret_key    VARCHAR(64)  NOT NULL UNIQUE,
    user_id       BIGINT       NOT NULL UNIQUE,
    username      VARCHAR(255) NOT NULL DEFAULT '',
    first_name    VARCHAR(255) NOT NULL DEFAULT '',
    registered_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    secret_key VARCHAR(64) NOT NULL,
    content    TEXT        NOT NULL,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_secret_key (secret_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    login         VARCHAR(64)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Учётная запись по умолчанию: логин admin, пароль admin. Смените пароль после первого входа (админ-панель).
INSERT IGNORE INTO admins (login, password_hash) VALUES (
    'admin',
    '$2y$12$M/ZyHsIB4o5R7CUClD3UZehSf4En8HK/twh2eaGnGDk0eqi9kspIu'
);
