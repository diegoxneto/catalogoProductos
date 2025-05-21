<?php

// Render inyectará estas variables de entorno
$db_host = getenv('PGHOST');
$db_name = getenv('PGDATABASE');
$db_user = getenv('PGUSER');
$db_password = getenv('PGPASSWORD');
$db_port = getenv('PGPORT');

// $db_host = 'dpg-d0n6op1119vc7383an9g-a';
// $db_name = 'renderdb_ah6o';
// $db_user = 'admin';
// $db_password = '3PIxdiLfRUMhaXOX0BRXJ90X3LN7jAHo'; 
// $db_port = '5432';

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;user=$db_user;password=$db_password";
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa a la base de datos PostgreSQL.";
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>