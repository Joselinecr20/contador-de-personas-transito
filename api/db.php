<?php

function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Credenciales del contenedor MySQL definido en docker-compose.yml.
        $host = '127.0.0.1';
        $port = 3306;
        $dbName = 'traffic_people_counter';
        $user = 'contador';
        $pass = 'contador_pass';

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    return $pdo;
}
