<?php
session_start();

require_once "./vendor/autoload.php";

Dotenv\Dotenv::createImmutable(__DIR__)->load();

require_once "./routes/web.php";