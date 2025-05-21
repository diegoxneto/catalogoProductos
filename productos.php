<?php
// Inicia la sesión PHP
session_start();

// Si el usuario NO está logueado, redirige a la página de login
if (!isset($_SESSION['usuario_email'])) {
    header('Location: login.php');
    exit();
}

//Obtener el nombre del usuario para mostrarlo en la página
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
    <link rel="stylesheet" href="style.css">
    <style>
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
        autoOpen: false, // No abrir automáticamente
        modal: true,    // Bloquear la interacción con el resto de la página
        buttons: {
            "Aceptar": function() {
                $(this).dialog("close");
            }
        },
        show: { effect: "fade", duration: 500 }, // Animación de aparición
        hide: { effect: "fade", duration: 500 }  // Animación de desaparición
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
        show: { effect: "bounce", duration: 500 }, // Animación de aparición
        hide: { effect: "explode", duration: 500 } // Animación de desaparición (podrías usar "fade" también)
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
        show: { effect: "clip", duration: 300 }, // Animación de aparición
        hide: { effect: "blind", duration: 300 }  // Animación de desaparición
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
        // Animación al dibujar/recargar la tabla (Animación 3)
        "drawCallback": function(settings) {
            // Animación: La tabla se desvanece y vuelve a aparecer suavemente al recargarse
            $(this).hide().fadeIn(800); // 800ms para la animación de aparición
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
        var categoria = $('#categoria').val().trim();

        // Validación frontend (Animación de shake si hay error)
        if (nombre === '' || precio === '' || categoria === '') {
            $("#errorMessage").text('Todos los campos son obligatorios.');
            $("#errorDialog").dialog("open");
            // Animación de "shake" al modal o a los campos con error
            $('#productModal .modal-content').effect("shake", { times: 2 }, 100);
            return; // Detener el envío del formulario
        }
        if (isNaN(precio) || parseFloat(precio) <= 0) {
            $("#errorMessage").text('El precio debe ser un número positivo.');
            $("#errorDialog").dialog("open");
            $('#precio').effect("highlight", { color: "#ffcccc" }, 500); // Resalta el campo
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
                        // Animación de aparición de nueva fila o resaltado (Animación 2)
                        // Es difícil animar solo la nueva fila con server-side processing directamente.
                        // Lo más práctico es resaltar la tabla o la fila si supiéramos el ID.
                        // Por ahora, el drawCallback ya anima la recarga de la tabla.
                        // Si se agregó un producto, podríamos tratar de encontrarlo y animarlo
                        if (!productId && response.id) { // Solo si es una creación
                            // DataTables no da el 'row' directamente de un nuevo registro con serverSide
                            // Podemos animar la tabla para la experiencia general
                            $('#productosTable').hide().fadeIn(800); // Anima toda la tabla de nuevo
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
        var $rowToDelete = $(this).closest('tr'); // Referencia a la fila que vamos a eliminar

        // Abrir el diálogo de confirmación de jQuery UI
        $("#confirmDeleteDialog").dialog({
            buttons: {
                "Eliminar": function() {
                    $(this).dialog("close"); // Cerrar el diálogo de confirmación

                    $.ajax({
                        url: 'api.php',
                        type: 'POST',
                        data: { action: 'delete', id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.message) {
                                // Animación de desaparición de la fila (Animación 1)
                                $rowToDelete.fadeOut(500, function() { // Desvanecer la fila
                                    // Después de la animación, recargar la tabla para que DataTables reorganice
                                    $("#successMessage").text(response.message);
                                    $("#successDialog").dialog("open");
                                    productosTable.ajax.reload(null, false); // No resetear la paginación al recargar
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
        }).dialog("open"); // Abrir el diálogo
    });

});
</script>



</body>
</html>