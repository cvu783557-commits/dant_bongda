<?php

namespace App;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

class Model
{
    protected $connection;

    public function __construct()
    {
        $connectionParams = [
            'user'      => $_ENV['DB_USERNAME'],
            'password'  => $_ENV['DB_PASSWORD'],
            'dbname'    => $_ENV['DB_NAME'],
            'host'      => $_ENV['DB_HOST'],
            'driver'    => $_ENV['DB_DRIVER'],
            'port'      => $_ENV['DB_PORT'],
        ];

        try {
            $this->connection = DriverManager::getConnection($connectionParams);
        } catch (Exception $e) {
            die('Lỗi kết nối CSDL: ' . $e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->connection = null;
    }
}
