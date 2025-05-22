<?php
// Inicia la sesión PHP
session_start();

// Permite solicitudes desde cualquier origen (CORS - Cross-Origin Resource Sharing)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluye el archivo de conexión a la base de datos
// Asegúrate de que 'conexion.php' cree una variable $conn (objeto PDO)
require_once 'conexion.php'; // Cambiado a require_once y asegurado

// --- Implementar Seguridad (Verificar Sesión) ---
if (!isset($_SESSION['usuario_email'])) {
    http_response_code(401);
    echo json_encode(array("message" => "Acceso denegado. Por favor, inicie sesión."));
    exit();
}

// --- Procesar la solicitud AJAX ---
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try { // Añadimos un try-catch global para capturar errores de PDO en el API
    switch ($action) {
        case 'list':
            $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;

            $search_value = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            $where_clauses = [];
            $params = [];

            if (!empty($search_value)) {
                $search_term = "%" . $search_value . "%";
                $where_clauses[] = "nombre ILIKE ?"; // PostgreSQL usa ILIKE para búsqueda insensible a mayúsculas
                $params[] = $search_term;

                $where_clauses[] = "categoria ILIKE ?";
                $params[] = $search_term;
            }

            $where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" OR ", $where_clauses) : "";

            $order_column_index = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
            $order_direction = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

            // Nombres de las columnas de la DB, en el mismo orden que en el HTML y el JS de DataTables
            // Asegúrate de que estas columnas existan en tu tabla 'productos' en PostgreSQL
            $columns_db = array('id', 'nombre', 'precio', 'categoria', 'fecha_creacion');
            // Nota: 'acciones' no es una columna de la DB, así que no debe estar aquí para el ORDER BY
            $order_by_column = $columns_db[$order_column_index] ?? 'id';
            $order_direction = in_array(strtolower($order_direction), ['asc', 'desc']) ? $order_direction : 'asc';

            // --- 1. Obtener el total de registros sin filtrar ---
            $stmt_total = $conn->prepare("SELECT COUNT(id) FROM productos");
            $stmt_total->execute();
            $total_records = $stmt_total->fetchColumn(); // PDO para obtener un solo valor
            $stmt_total = null; // Cierra el statement

            // --- 2. Obtener el total de registros filtrados (con búsqueda) ---
            $stmt_filtered = $conn->prepare("SELECT COUNT(id) FROM productos" . $where_sql);
            foreach ($params as $i => $param) {
                $stmt_filtered->bindValue($i + 1, $param, PDO::PARAM_STR);
            }
            $stmt_filtered->execute();
            $total_filtered_records = $stmt_filtered->fetchColumn();
            $stmt_filtered = null;

            // --- 3. Obtener los datos para la página actual (filtrados, ordenados y paginados) ---
            $sql_data = "SELECT id, nombre, precio, categoria, fecha_creacion FROM productos"
                        . $where_sql
                        . " ORDER BY " . $order_by_column . " " . strtoupper($order_direction) // ¡PDO es seguro con nombres de columna directos aquí si los obtienes de un array controlado!
                        . " LIMIT ? OFFSET ?"; // Sintaxis de paginación para PostgreSQL

            $stmt_data = $conn->prepare($sql_data);

            // Bindeamos los parámetros de búsqueda
            $param_index = 1;
            foreach ($params as $param) {
                $stmt_data->bindValue($param_index++, $param, PDO::PARAM_STR);
            }
            // Bindeamos los parámetros de paginación
            $stmt_data->bindValue($param_index++, $length, PDO::PARAM_INT);
            $stmt_data->bindValue($param_index++, $start, PDO::PARAM_INT);

            $stmt_data->execute();
            $data = $stmt_data->fetchAll(PDO::FETCH_ASSOC); // Obtener todos los resultados
            $stmt_data = null;

            echo json_encode(array(
                "draw" => $draw,
                "recordsTotal" => $total_records,
                "recordsFiltered" => $total_filtered_records,
                "data" => $data
            ));
            break;

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(array("message" => "Método no permitido. Use POST."));
                exit();
            }

            $nombre = $_POST['nombre'] ?? '';
            $precio = $_POST['precio'] ?? '';
            $categoria = $_POST['categoria'] ?? '';

            if (empty($nombre) || empty($precio) || empty($categoria)) {
                http_response_code(400);
                echo json_encode(array("message" => "Faltan campos obligatorios (nombre, precio, categoría)."));
                exit();
            }
            if (!is_numeric($precio) || $precio <= 0) {
                http_response_code(400);
                echo json_encode(array("message" => "El precio debe ser un número positivo."));
                exit();
            }

            $sql = "INSERT INTO productos (nombre, precio, categoria) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // PDO::PARAM_STR para strings, PDO::PARAM_INT para enteros, PDO::PARAM_STR para decimales que se tratan como strings
            $stmt->bindValue(1, $nombre, PDO::PARAM_STR);
            $stmt->bindValue(2, $precio); // PDO infiere el tipo numérico
            $stmt->bindValue(3, $categoria, PDO::PARAM_STR);

            if ($stmt->execute()) {
                http_response_code(201);
                // Para PostgreSQL, lastInsertId() suele necesitar el nombre de la secuencia (nombre_tabla_id_seq)
                // O si usas SERIAL/BIGSERIAL, puede que funcione sin argumento o con el nombre de la tabla
                echo json_encode(array("message" => "Producto creado exitosamente.", "id" => $conn->lastInsertId('productos_id_seq')));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error al crear el producto: " . $stmt->errorInfo()[2])); // $stmt->errorInfo() para PDO
            }
            $stmt = null;
            break;

        case 'get':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(array("message" => "ID de producto inválido."));
                exit();
            }

            $sql = "SELECT id, nombre, precio, categoria FROM productos WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($producto) {
                http_response_code(200);
                echo json_encode($producto);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Producto no encontrado."));
            }
            $stmt = null;
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
                http_response_code(405);
                echo json_encode(array("message" => "Método no permitido. Use POST o PUT."));
                exit();
            }

            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                parse_str(file_get_contents("php://input"), $_PUT);
                $input_data = $_PUT;
            } else {
                $input_data = $_POST;
            }

            $id = isset($input_data['id']) ? intval($input_data['id']) : 0;
            $nombre = $input_data['nombre'] ?? '';
            $precio = $input_data['precio'] ?? '';
            $categoria = $input_data['categoria'] ?? '';

            if ($id <= 0 || empty($nombre) || empty($precio) || empty($categoria)) {
                http_response_code(400);
                echo json_encode(array("message" => "Faltan campos obligatorios (ID, nombre, precio, categoría)."));
                exit();
            }
            if (!is_numeric($precio) || $precio <= 0) {
                http_response_code(400);
                echo json_encode(array("message" => "El precio debe ser un número positivo."));
                exit();
            }

            $sql = "UPDATE productos SET nombre = ?, precio = ?, categoria = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $nombre, PDO::PARAM_STR);
            $stmt->bindValue(2, $precio);
            $stmt->bindValue(3, $categoria, PDO::PARAM_STR);
            $stmt->bindValue(4, $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) { // rowCount() para PDO
                    http_response_code(200);
                    echo json_encode(array("message" => "Producto actualizado exitosamente."));
                } else {
                    http_response_code(200);
                    echo json_encode(array("message" => "Producto encontrado pero no se realizaron cambios o ID no existe."));
                }
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error al actualizar el producto: " . $stmt->errorInfo()[2]));
            }
            $stmt = null;
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                http_response_code(405);
                echo json_encode(array("message" => "Método no permitido. Use POST o DELETE."));
                exit();
            }

            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                parse_str(file_get_contents("php://input"), $_DELETE);
                $id = isset($_DELETE['id']) ? intval($_DELETE['id']) : 0;
            } else {
                $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
            }

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(array("message" => "ID de producto inválido."));
                exit();
            }

            $sql = "DELETE FROM productos WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Producto eliminado exitosamente."));
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Producto no encontrado o ya ha sido eliminado."));
                }
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error al eliminar el producto: " . $stmt->errorInfo()[2]));
            }
            $stmt = null;
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "Acción no válida."));
            break;
    }
} catch (PDOException $e) {
    // Captura cualquier error de PDO en el API y lo devuelve como JSON
    http_response_code(500); // Internal Server Error
    echo json_encode(array("message" => "Error de base de datos en API: " . $e->getMessage()));
}

// En PDO, no necesitas $conn->close() explícito, la conexión se cierra al finalizar el script.
// $conn = null; // Opcional, para asegurar que la conexión se cierre de inmediato.

?>