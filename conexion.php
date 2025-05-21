<?php

// --- Configuración de la Base de Datos ---
$servername = "localhost"; // La dirección del servidor de la base de datos (generalmente "localhost" en entorno local)
$username = "root";      // Tu nombre de usuario de MySQL (por defecto suele ser "root" en XAMPP/WAMP/MAMP)
$password = "Twentyone"; // ¡¡Aquí debes poner la contraseña de tu usuario de MySQL!!
$dbname = "CatalogoProductos"; // ¡¡Aquí pones el nombre de la base de datos que acabas de crear o que ya tenías!!

// --- Crear la conexión ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Verificar la conexión ---
if ($conn->connect_error) {
    // Si hay un error, muestra un mensaje y detiene la ejecución
    die("Conexión fallida: " . $conn->connect_error);
}

// Si la conexión fue exitosa, el script simplemente continúa.
// La variable $conn ahora contiene el objeto de conexión a la base de datos que puedes usar en otros scripts.

// Opcional: Establecer el juego de caracteres a UTF8
$conn->set_charset("utf8mb4");

?>