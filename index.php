<?php
// Inicia la sesión PHP. Esto es necesario para poder usar $_SESSION
session_start();

// Opcional: Configuración para mostrar errores de PHP durante el desarrollo
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Incluye el archivo de conexión a la base de datos
include 'conexion.php'; // Asegúrate de que 'conexion.php' esté en la misma carpeta o ajusta la ruta

// 1. Si ya existe una sesión de usuario, redirige al usuario a la página principal
if (isset($_SESSION['usuario_email'])) {
    header('Location: productos.php'); // Redirige a productos.php si ya está logueado
    exit(); // Importante: detener la ejecución después de la redirección
}

// 2. Procesa el formulario de login cuando se envía (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtiene los datos enviados por el formulario (email y password)
    $email = $_POST['email'];
    $password = $_POST['password']; // ¡¡Nota de seguridad: Almacenar y comparar contraseñas en texto plano no es seguro en la vida real!!

    // Consulta SQL para buscar el usuario por email
    $sql = "SELECT id, nombre, email, password FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql); // Prepara la consulta para evitar inyección SQL
    $stmt->bind_param("s", $email); // 's' indica que el parámetro es un string
    $stmt->execute();
    $result = $stmt->get_result(); // Obtiene el resultado de la consulta

    if ($result->num_rows === 1) {
        // Si se encontró un usuario con ese email
        $usuario = $result->fetch_assoc(); // Obtiene los datos del usuario
        $stored_password = $usuario['password']; // Contraseña almacenada en la DB (texto plano en este ejemplo simple)

        // ¡¡Comparación de contraseñas (simplificado para este ejemplo de tarea)!!
        // En un sistema real, usarías password_verify() con contraseñas hasheadas
        if ($password === $stored_password) {
            // Si la contraseña coincide
            $_SESSION['usuario_email'] = $usuario['email']; // Establece una variable de sesión
            $_SESSION['usuario_id'] = $usuario['id']; // Opcional: guarda también el ID del usuario
            $_SESSION['usuario_nombre'] = $usuario['nombre']; // Opcional: guarda el nombre del usuario

            // Redirige al usuario a la página principal después del login exitoso
            header('Location: productos.php');
            exit();
        } else {
            // Contraseña incorrecta
            $error_message = "Email o contraseña incorrectos.";
        }
    } else {
        // No se encontró un usuario con ese email
        $error_message = "Email o contraseña incorrectos.";
    }

    $stmt->close(); // Cierra la sentencia preparada
    $conn->close(); // Cierra la conexión a la base de datos (puedes cerrar la conexión aquí o al final del script)
}

// Si llegamos aquí, es porque no hay sesión activa y no se envió el formulario POST (o hubo un error).
// Se muestra el formulario de login.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa; /* Un fondo gris claro */
        }
        .login-container {
            max-width: 400px; /* Ancho máximo del formulario */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #ffffff; /* Fondo blanco para el formulario */
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="text-center mb-4">Iniciar Sesión</h2>

    <?php
    // Muestra el mensaje de error si existe (definido en la sección PHP de arriba)
    if (isset($error_message)) {
        echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
    }
    ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>