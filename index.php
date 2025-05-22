<?php
// index.php

session_start();

// Solo un require_once es suficiente
require_once 'conexion.php'; 

// 1. Si ya existe una sesión de usuario, redirige al usuario a la página principal
if (isset($_SESSION['usuario_email'])) {
    header('Location: productos.php');
    exit();
}

// 2. Procesa el formulario de login cuando se envía (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // ***** CAMBIOS AQUÍ PARA USAR PDO *****
    try {
        // Consulta SQL para buscar el usuario por email
        // PDO usa marcadores de posición con nombre (:email) o con signo de interrogación (?)
        $sql = "SELECT id, nombre, email, password FROM usuarios WHERE email = :email";
        $stmt = $conn->prepare($sql); // <--- Ahora $conn es un objeto PDO válido
        $stmt->bindParam(':email', $email, PDO::PARAM_STR); // 'PARAM_STR' para indicar que es un string
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC); // Obtiene los datos del usuario como un array asociativo

        if ($usuario) { // Si se encontró un usuario
            $stored_password = $usuario['password']; 

            // ¡¡Comparación de contraseñas (simplificado)!!
            // Idealmente, deberías usar password_verify() si las contraseñas están hasheadas con password_hash()
            if ($password === $stored_password) { // <-- Si usas MD5 en DB, compara con MD5($password) aquí.
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];

                header('Location: productos.php');
                exit();
            } else {
                $error_message = "Email o contraseña incorrectos.";
            }
        } else {
            // No se encontró un usuario con ese email
            $error_message = "Email o contraseña incorrectos.";
        }

    } catch (PDOException $e) {
        $error_message = "Error en la consulta de login: " . $e->getMessage();
        // Puedes loggear este error para producción en lugar de mostrarlo al usuario
    }
    // PDO no necesita un $stmt->close() ni $conn->close() explícito como mysqli.
    // La conexión se cierra automáticamente al finalizar el script o cuando el objeto $conn es destruido.
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
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="text-center mb-4">Iniciar Sesión</h2>

    <?php
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