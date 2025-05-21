<?php

// --- Configuración de la Base de Datos ---
// Obtener variables de entorno.
// Railway y Render inyectarán estas variables cuando se despliegue.

$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'Twentyone';
$dbname = getenv('DB_NAME') ?: 'CatalogoProductos';

// --- Crear la conexión ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Verificar la conexión ---
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres a utf8mb4
$conn->set_charset("utf8mb4");

?>