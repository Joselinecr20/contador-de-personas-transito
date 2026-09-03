<?php

function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = 'localhost';
        $dbName = 'traffic_people_counter';
        $user = 'root';
        $pass = '';

        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    return $pdo;
}
