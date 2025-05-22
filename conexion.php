<?php
// conexion.php

$db_host = getenv('PGHOST');
$db_name = getenv('PGDATABASE');
$db_user = getenv('PGUSER');
$db_password = getenv('PGPASSWORD');
$db_port = getenv('PGPORT');

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $conn = new PDO($dsn, $db_user, $db_password); // Usamos $conn para ser consistente con index.php
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa a la base de datos PostgreSQL."; // Solo para depuración
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>