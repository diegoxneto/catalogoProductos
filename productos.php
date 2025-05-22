<?php
// Inicia la sesión PHP
session_start();

// Asegúrate de incluir el archivo de conexión a la base de datos
require_once 'conexion.php'; // <--- ¡Esta es la línea que faltaba y es crucial!

// Si el usuario NO está logueado, redirige a la página de login
if (!isset($_SESSION['usuario_email'])) {
    // Si tu página de login se llama index.php ahora, redirige a index.php
    header('Location: index.php'); // O 'login.php' si decidiste mantenerlo así
    exit();
}

//Obtener el nombre del usuario para mostrarlo en la página
// Esto asume que $_SESSION['usuario_nombre'] fue establecido en el login
// Si no lo fue, usará el email, que es un buen fallback.
$nombre_usuario = $_SESSION['usuario_nombre'] ?? $_SESSION['usuario_email'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="style.css"> <style>
        /* Estilos básicos para que la tabla se vea bien */
        body { padding-top: 20px; }
        .container { margin-top: 20px; }
        table.dataTable tbody td { font-size: 1.8vh; }
        table.dataTable thead th { background-color: orange; color: black; }
        /* Estilos para animaciones  */
        .fade-out { opacity: 0; transition: opacity 0.5s ease-out; }
        .slide-up { transform: translateY(100%); opacity: 0; transition: transform 0.5s ease-out, opacity 0.5s ease-out; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Productos</h1>
        <div>
            <span class="me-3">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></span>
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>

    <button class="btn btn-success mb-3" id="btnAddProduct">Agregar Producto</button>

    <table id="productosTable" class="display MiTabla" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th> <th>Fecha Creación</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Categoría</th>
                <th>Fecha Creación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            </tbody>
    </table>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="productId" name="id">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="precio" class="form-label">Precio</label>
                        <input type="number" step="0.01" class="form-control" id="precio" name="precio" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoría</label>
                        <input type="text" class="form-control" id="categoria" name="categoria" required>
                    </div>
                    <div class="mb-3">
                        <label for="imagen_url" class="form-label">URL de Imagen</label>
                        <input type="url" class="form-control" id="imagen_url" name="imagen_url" placeholder="https://ejemplo.com/imagen.jpg">
                    </div>
                    <button type="submit" class="btn btn-primary" id="btnSaveProduct">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="successDialog" title="Operación Exitosa" style="display:none;">
    <p><span class="ui-icon ui-icon-check" style="float:left; margin:0 7px 50px 0;"></span><span id="successMessage"></span></p>
</div>

<div id="errorDialog" title="Error" style="display:none;">
    <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 50px 0;"></span><span id="errorMessage"></span></p>
</div>

<div id="confirmDeleteDialog" title="Confirmar Eliminación" style="display:none;">
    <p><span class="ui-icon ui-icon-help" style="float:left; margin:0 7px 20px 0;"></span>¿Estás seguro de que quieres eliminar este producto?</p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
$(document).ready(function() {
    var productosTable;

    // --- Configuración de jQuery UI Dialogs ---

    // Diálogo de éxito
    $("#successDialog").dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "Aceptar": function() {
                $(this).dialog("close");
            }
        },
        show: { effect: "fade", duration: 500 },
        hide: { effect: "fade", duration: 500 }
    });

    // Diálogo de error
    $("#errorDialog").dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "Cerrar": function() {
                $(this).dialog("close");
            }
        },
        show: { effect: "bounce", duration: 500 },
        hide: { effect: "explode", duration: 500 }
    });

    // Diálogo de confirmación para eliminar
    $("#confirmDeleteDialog").dialog({
        autoOpen: false,
        modal: true,
        resizable: false,
        height: "auto",
        width: 400,
        buttons: {
            "Eliminar": function() {
                $(this).dialog("close");
                // La lógica de eliminación real se maneja en el evento click del botón
                // Necesitamos pasar el ID a esta función.
            },
            "Cancelar": function() {
                $(this).dialog("close");
            }
        },
        show: { effect: "clip", duration: 300 },
        hide: { effect: "blind", duration: 300 }
    });


    // --- Inicialización de DataTables ---
    productosTable = $('#productosTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "api.php",
            "type": "GET",
            "data": function (d) {
                d.action = 'list';
            },
            "dataSrc": "data",
            "error": function(xhr, error, thrown) {
                console.error("Error en la solicitud AJAX de DataTables:", error, thrown);
                console.error("Respuesta del servidor:", xhr.responseText);
                $("#errorMessage").text('No se pudieron cargar los productos. ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Error desconocido.'));
                $("#errorDialog").dialog("open");
            }
        },
        "columns": [
            { "data": "id" },
            { // Nueva columna para la imagen
            "data": "imagen_url",
            "render": function (data, type, row) {
                if (data) {
                    return '<img src="' + data + '" alt="Producto" style="width: 50px; height: auto; border-radius: 5px;">';
                }
                return ''; // Si no hay URL, no muestra nada
            },
            "orderable": false // Las imágenes no suelen ser ordenables
            },
            { "data": "nombre" },
            { "data": "precio" },
            { "data": "categoria" }, 
            { "data": "fecha_creacion" }, 
            {
                "data": null,
                "render": function (data, type, row) {
                    return '<button class="btn btn-sm btn-info btnEdit me-1" data-id="' + row.id + '">Editar</button>' +
                           '<button class="btn btn-sm btn-danger btnDelete" data-id="' + row.id + '">Eliminar</button>';
                },
                "orderable": false
            }
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es_es.json"
        },
        "drawCallback": function(settings) {
            $(this).hide().fadeIn(800);
        }
    });

    // --- Manejo del Modal de Agregar/Editar ---

    $('#btnAddProduct').on('click', function() {
        $('#productForm')[0].reset();
        $('#productId').val('');
        $('#productModalLabel').text('Agregar Nuevo Producto');
        $('#productModal').modal('show');
    });

    $('#productosTable tbody').on('click', '.btnEdit', function () {
        var id = $(this).data('id');
        $('#productModalLabel').text('Editar Producto');

        $.ajax({
            url: 'api.php',
            type: 'GET',
            data: { action: 'get', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.id) {
                    $('#productId').val(response.id);
                    $('#imagen_url').val(response.imagen_url);
                    $('#nombre').val(response.nombre);
                    $('#precio').val(response.precio);
                    $('#categoria').val(response.categoria);
                    $('#productModal').modal('show');
                } else {
                    $("#errorMessage").text(response.message || 'Producto no encontrado para edición.');
                    $("#errorDialog").dialog("open");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al obtener producto para edición:', textStatus, errorThrown, jqXHR.responseText);
                $("#errorMessage").text('No se pudieron cargar los datos del producto para edición.');
                $("#errorDialog").dialog("open");
            }
        });
    });

    // --- Validación de Formulario y Envío con Animación ---
    $('#productForm').on('submit', function(e) {
        e.preventDefault();

        var nombre = $('#nombre').val().trim();
        var precio = $('#precio').val();
        var categoria = $('#categoria').val().trim(); // <-- Asegúrate de que 'categoria' exista en tu tabla de productos

        // Validación frontend (Animación de shake si hay error)
        if (nombre === '' || precio === '' || categoria === '') {
            $("#errorMessage").text('Todos los campos son obligatorios.');
            $("#errorDialog").dialog("open");
            $('#productModal .modal-content').effect("shake", { times: 2 }, 100);
            return;
        }
        if (isNaN(precio) || parseFloat(precio) <= 0) {
            $("#errorMessage").text('El precio debe ser un número positivo.');
            $("#errorDialog").dialog("open");
            $('#precio').effect("highlight", { color: "#ffcccc" }, 500);
            return;
        }

        var formData = $(this).serialize();
        var productId = $('#productId').val();
        var actionUrl = 'api.php';

        if (productId) {
            actionUrl += '?action=update';
            formData += '&action=update';
        } else {
            actionUrl += '?action=create';
            formData += '&action=create';
        }

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.message) {
                    $('#productModal').modal('hide');
                    $("#successMessage").text(response.message);
                    $("#successDialog").dialog("open");
                    productosTable.ajax.reload(function() {
                        if (!productId && response.id) {
                            $('#productosTable').hide().fadeIn(800);
                        }
                    });
                } else {
                    $("#errorMessage").text('Respuesta inesperada del servidor.');
                    $("#errorDialog").dialog("open");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al guardar producto:', textStatus, errorThrown, jqXHR.responseText);
                var errorMsg = jqXHR.responseJSON ? jqXHR.responseJSON.message : 'Error desconocido al guardar el producto.';
                $("#errorMessage").text(errorMsg);
                $("#errorDialog").dialog("open");
            }
        });
    });

    // --- Manejo de Eliminación con Diálogo de Confirmación y Animación ---
    $('#productosTable tbody').on('click', '.btnDelete', function () {
        var id = $(this).data('id');
        var $rowToDelete = $(this).closest('tr');

        $("#confirmDeleteDialog").dialog({
            buttons: {
                "Eliminar": function() {
                    $(this).dialog("close");

                    $.ajax({
                        url: 'api.php',
                        type: 'POST',
                        data: { action: 'delete', id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.message) {
                                $rowToDelete.fadeOut(500, function() {
                                    $("#successMessage").text(response.message);
                                    $("#successDialog").dialog("open");
                                    productosTable.ajax.reload(null, false);
                                });
                            } else {
                                $("#errorMessage").text('Respuesta inesperada del servidor al eliminar.');
                                $("#errorDialog").dialog("open");
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error al eliminar producto:', textStatus, errorThrown, jqXHR.responseText);
                            var errorMsg = jqXHR.responseJSON ? jqXHR.responseJSON.message : 'Error desconocido al eliminar el producto.';
                            $("#errorMessage").text(errorMsg);
                            $("#errorDialog").dialog("open");
                        }
                    });
                },
                "Cancelar": function() {
                    $(this).dialog("close");
                }
            }
        }).dialog("open");
    });
});
</script>

</body>
</html>