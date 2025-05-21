<?php
// Inicia la sesión PHP
session_start();

// Permite solicitudes desde cualquier origen (CORS - Cross-Origin Resource Sharing)
// En un entorno de producción, SE DEBERIA restringir esto solo a tu dominio
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE"); // Define los métodos HTTP permitidos
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"); // Define las cabeceras permitidas

// Incluye el archivo de conexión a la base de datos
include 'conexion.php'; // Asegúrate de que la ruta sea correcta

// --- Implementar Seguridad (Verificar Sesión) ---
// Si el usuario NO está logueado, detener la ejecución y enviar un error
if (!isset($_SESSION['usuario_email'])) {
    http_response_code(401); // Código de estado HTTP 401 Unauthorized
    echo json_encode(array("message" => "Acceso denegado. Por favor, inicie sesión."));
    exit(); // Detener la ejecución
}

// --- Procesar la solicitud AJAX ---
// Obtiene la acción solicitada del parámetro 'action' en la URL
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Usa una estructura switch o if/else if para manejar las diferentes acciones
switch ($action) {
    case 'list':
        // Parámetros que DataTables envía
        $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $length = isset($_GET['length']) ? intval($_GET['length']) : 10; // Número de registros por página

        // Búsqueda
        $search_value = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
        $where_clauses = [];
        $params = [];
        $param_types = '';

        if (!empty($search_value)) {
            $search_term = "%" . $search_value . "%";
            $where_clauses[] = "nombre LIKE ?";
            $params[] = $search_term;
            $param_types .= 's';

            $where_clauses[] = "categoria LIKE ?";
            $params[] = $search_term;
            $param_types .= 's';
        }

        $where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" OR ", $where_clauses) : "";

        // Ordenamiento
        $order_column_index = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
        $order_direction = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

        // Nombres de las columnas de la DB, en el mismo orden que en el HTML y el JS de DataTables
        $columns_db = array('id', 'nombre', 'precio', 'categoria', 'fecha_creacion', 'acciones'); // 'acciones' no es una columna de DB, pero se usa para mapeo
        $order_by_column = $columns_db[$order_column_index] ?? 'id'; // Asegura un valor por defecto seguro

        // Validar dirección de ordenamiento
        $order_direction = in_array(strtolower($order_direction), ['asc', 'desc']) ? $order_direction : 'asc';

        // --- 1. Obtener el total de registros sin filtrar ---
        $sql_total = "SELECT COUNT(id) FROM productos";
        $stmt_total = $conn->prepare($sql_total);
        $stmt_total->execute();
        $result_total = $stmt_total->get_result();
        $total_records = $result_total->fetch_row()[0];
        $stmt_total->close();

        // --- 2. Obtener el total de registros filtrados (con búsqueda) ---
        $sql_filtered = "SELECT COUNT(id) FROM productos" . $where_sql;
        $stmt_filtered = $conn->prepare($sql_filtered);
        if (count($params) > 0) {
            $stmt_filtered->bind_param($param_types, ...$params);
        }
        $stmt_filtered->execute();
        $result_filtered = $stmt_filtered->get_result();
        $total_filtered_records = $result_filtered->fetch_row()[0];
        $stmt_filtered->close();

        // --- 3. Obtener los datos para la página actual (filtrados, ordenados y paginados) ---
        // Excluir la columna 'acciones' si no existe en la DB
        $sql_data = "SELECT id, nombre, precio, categoria, fecha_creacion FROM productos"
                    . $where_sql
                    . " ORDER BY " . $conn->real_escape_string($order_by_column) . " " . strtoupper($order_direction)
                    . " LIMIT ?, ?";

        $stmt_data = $conn->prepare($sql_data);

        // Combinar parámetros de búsqueda y paginación
        $all_params = array_merge($params, [$start, $length]);
        $all_param_types = $param_types . 'ii'; // Añadir 'ii' para LIMIT

        // Usar call_user_func_array si tienes una versión de PHP anterior a 5.6
        // Para PHP 5.6+, el operador ... (splat operator) es más limpio
        $stmt_data->bind_param($all_param_types, ...$all_params);

        $stmt_data->execute();
        $result_data = $stmt_data->get_result();

        $data = array();
        while ($row = $result_data->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt_data->close();

        // --- Enviar la respuesta en formato JSON para DataTables ---
        echo json_encode(array(
            "draw" => $draw,
            "recordsTotal" => $total_records,
            "recordsFiltered" => $total_filtered_records,
            "data" => $data
        ));
        break;

        case 'create':
            // Asegúrate de que la solicitud sea POST para crear datos
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405); // Método no permitido
                echo json_encode(array("message" => "Método no permitido. Use POST."));
                exit();
            }
    
            // Decodificar los datos JSON enviados en el cuerpo de la solicitud (si el frontend envía JSON)
            // Si el frontend envía form-data (ej. con jQuery.ajax formData), usa $_POST
            // Asumiremos que el frontend envía los datos como FormData (ej. `data: new FormData(this)` en jQuery)
            $nombre = $_POST['nombre'] ?? '';
            $precio = $_POST['precio'] ?? '';
            $categoria = $_POST['categoria'] ?? '';
    
            // Validación básica
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
            $stmt->bind_param("sds", $nombre, $precio, $categoria); // 's' para string, 'd' para double/decimal
    
            if ($stmt->execute()) {
                http_response_code(201); // 201 Created
                echo json_encode(array("message" => "Producto creado exitosamente.", "id" => $conn->insert_id));
            } else {
                http_response_code(500); // Error del servidor
                echo json_encode(array("message" => "Error al crear el producto: " . $stmt->error));
            }
            $stmt->close();
            break;
    
        case 'get':
            // Obtener los datos de un producto específico por ID (para editar)
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(array("message" => "ID de producto inválido."));
                exit();
            }
    
            $sql = "SELECT id, nombre, precio, categoria FROM productos WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id); // 'i' para integer
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows === 1) {
                $producto = $result->fetch_assoc();
                http_response_code(200);
                echo json_encode($producto);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode(array("message" => "Producto no encontrado."));
            }
            $stmt->close();
            break;
    
        case 'update':
            // Asegúrate de que la solicitud sea POST o PUT
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
                http_response_code(405);
                echo json_encode(array("message" => "Método no permitido. Use POST o PUT."));
                exit();
            }
    
            // Para PUT requests, los datos vienen en php://input
            // Para POST (como la mayoría de formularios jQuery), vienen en $_POST
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                parse_str(file_get_contents("php://input"), $_PUT);
                $input_data = $_PUT;
            } else { // POST
                $input_data = $_POST;
            }
    
            $id = isset($input_data['id']) ? intval($input_data['id']) : 0;
            $nombre = $input_data['nombre'] ?? '';
            $precio = $input_data['precio'] ?? '';
            $categoria = $input_data['categoria'] ?? '';
    
            // Validación básica
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
            $stmt->bind_param("sdsi", $nombre, $precio, $categoria, $id); // 's' string, 'd' double, 's' string, 'i' integer
    
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    http_response_code(200); // OK
                    echo json_encode(array("message" => "Producto actualizado exitosamente."));
                } else {
                    http_response_code(200); // OK (pero no se modificó nada, quizá el ID no existe o no hubo cambios)
                    echo json_encode(array("message" => "Producto encontrado pero no se realizaron cambios o ID no existe."));
                }
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error al actualizar el producto: " . $stmt->error));
            }
            $stmt->close();
            break;
    
        case 'delete':
            // Asegúrate de que la solicitud sea POST o DELETE
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                http_response_code(405);
                echo json_encode(array("message" => "Método no permitido. Use POST o DELETE."));
                exit();
            }
    
            // Para DELETE requests, los datos pueden venir en php://input o GET
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                parse_str(file_get_contents("php://input"), $_DELETE);
                $id = isset($_DELETE['id']) ? intval($_DELETE['id']) : 0;
            } else { // POST o GET
                $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
            }
    
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(array("message" => "ID de producto inválido."));
                exit();
            }
    
            $sql = "DELETE FROM productos WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id); // 'i' para integer
    
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Producto eliminado exitosamente."));
                } else {
                    http_response_code(404); // No encontrado o ya eliminado
                    echo json_encode(array("message" => "Producto no encontrado o ya ha sido eliminado."));
                }
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error al eliminar el producto: " . $stmt->error));
            }
            $stmt->close();
            break;
    default:
        // Acción no válida o no especificada
        http_response_code(400); // Código de estado HTTP 400 Bad Request
        echo json_encode(array("message" => "Acción no válida."));
        break;
}

// Al final de cada caso, o aquí si la conexión no se cierra antes, puedes cerrar la conexión
$conn->close();

?>