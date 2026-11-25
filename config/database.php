<?php

declare(strict_types=1);

$driver = $_ENV["DB_CONNECTION"] ?? "sqlite";

if ($driver === "sqlite") {
    return [
        "driver" => "sqlite",
        "database" => $_ENV["DB_DATABASE"] ?? __DIR__ . "/../database/faculty_evaluation.sqlite",
        "prefix" => "",
        "options" => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ];
}

$initCommandAttr = defined("PDO\\Mysql::ATTR_INIT_COMMAND")
    ? constant("PDO\\Mysql::ATTR_INIT_COMMAND")
    : PDO::MYSQL_ATTR_INIT_COMMAND;

return [
    "driver" => "mysql",
    "host" => $_ENV["DB_HOST"] ?? "127.0.0.1",
    "port" => (int)($_ENV["DB_PORT"] ?? 3306),
    "database" => $_ENV["DB_DATABASE"] ?? "faculty_evaluation",
    "username" => $_ENV["DB_USERNAME"] ?? "root",
    "password" => $_ENV["DB_PASSWORD"] ?? "",
    "charset" => "utf8mb4",
    "collation" => "utf8mb4_unicode_ci",
    "prefix" => "",
    "options" => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        $initCommandAttr => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],
];