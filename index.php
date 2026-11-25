<?php
session_start();

require_once "./vendor/autoload.php";

Dotenv\Dotenv::createImmutable(__DIR__)->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Ho_Chi_Minh');

require_once "./routes/web.php";