<?php

declare(strict_types=1);

return [
    "up" => [
        "sqlite" => "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                employee_id VARCHAR(20) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                department VARCHAR(150) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'faculty',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                email_verified_at DATETIME NULL,
                last_login_at DATETIME NULL,
                failed_login_attempts INTEGER NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
            CREATE INDEX IF NOT EXISTS idx_users_employee_id ON users(employee_id);
            CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
        ",
        "mysql" => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id VARCHAR(20) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                department VARCHAR(150) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'faculty',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                email_verified_at DATETIME NULL,
                last_login_at DATETIME NULL,
                failed_login_attempts INT NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_users_email (email),
                UNIQUE KEY uniq_users_employee_id (employee_id),
                KEY idx_users_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
    ],
    "down" => [
        "sqlite" => "DROP TABLE IF EXISTS users;",
        "mysql" => "DROP TABLE IF EXISTS users;",
    ],
];
