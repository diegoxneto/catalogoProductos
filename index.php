<?php
// Esto imprimirá "¡Hola desde Railway!" si PHP está funcionando
echo "<h1>¡Hola desde Railway!</h1>";

// Intento de conexión a la base de datos
try {
    // Estas variables de entorno son inyectadas por Railway para tu DB PostgreSQL.
    // getenv() intentará obtenerlas. Si no existen, PDO fallará.
    // A veces, Railway puede usar DATABASE_HOST, DATABASE_PORT, etc.,
    // si PGHOST, PGPORT no están presentes, los obtenemos como respaldo.
    $host = getenv('PGHOST') ?: getenv('DATABASE_HOST');
    $port = getenv('PGPORT') ?: getenv('DATABASE_PORT');
    $dbname = getenv('PGDATABASE') ?: getenv('DATABASE_NAME');
    $user = getenv('PGUSER') ?: getenv('DATABASE_USER');
    $password = getenv('PGPASSWORD') ?: getenv('DATABASE_PASSWORD');

    // Asegúrate de que las variables no estén vacías
    if (!$host || !$port || !$dbname || !$user || !$password) {
        echo "<p><strong>Error: Faltan variables de entorno para la base de datos.</strong></p>";
        echo "<p>Verifica la pestaña 'Variables' de tu aplicación en Railway.</p>";
        // Opcional: Imprime las variables para depurar si alguna está vacía:
        // echo "<p>Host: " . ($host ?? 'N/A') . ", Port: " . ($port ?? 'N/A') . ", DBNAME: " . ($dbname ?? 'N/A') . ", User: " . ($user ?? 'N/A') . ", Pass: " . ($password ? '*****' : 'N/A') . "</p>";
    } else {
        // Construye el DSN (Data Source Name)
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

        // Intenta la conexión
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Para que PDO lance excepciones en errores

        echo "<p style='color: green;'>¡Conexión a la base de datos exitosa desde PHP!</p>";

        // Opcional: Ejecutar una consulta simple para verificar la tabla 'productos'
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM productos;");
            $count = $stmt->fetchColumn();
            echo "<p>Número de productos en la base de datos: " . $count . "</p>";
        } catch (PDOException $e_query) {
            echo "<p style='color: orange;'>Advertencia: No se pudo consultar la tabla 'productos'. Quizás la tabla no existe o hay un problema con la consulta: " . $e_query->getMessage() . "</p>";
        }

    }
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error de conexión a la base de datos desde PHP:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Verifica tus credenciales en la pestaña 'Connect' de la DB en Railway y que tu PHP las lea correctamente.</p>";
}
?>