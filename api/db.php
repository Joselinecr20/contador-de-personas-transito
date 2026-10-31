<?php

function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Por defecto usa el MySQL de XAMPP. Para el contenedor de
        // docker-compose.yml, definir DB_USER=contador y DB_PASS=contador_pass
        // (por ejemplo con SetEnv en httpd.conf).
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_NAME') ?: 'traffic_people_counter';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    return $pdo;
}
