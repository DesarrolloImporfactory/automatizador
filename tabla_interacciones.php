<?php
include 'db.php';

$nombre_tabla_singular = "Interacción de Usuario";
$nombre_tabla_plural = "Interacciones de Usuarios";
$url_crud = "crud_interacciones.php";
require "widget_header.php";

// Verifica si se ha proporcionado el parámetro id_automatizador en la URL
$id_automatizador = isset($_GET['id_automatizador']) ? $_GET['id_automatizador'] : null;

if (!$id_automatizador) {
    // Redirige a la página anterior si no se proporciona id_automatizador
    header('Location: tabla_automatizadores.php');
    exit;
}

// Obtener el nombre del automatizador
$nombre_automatizador = '';
$sql = "SELECT nombre FROM automatizadores WHERE id='$id_automatizador'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nombre_automatizador = $row['nombre'];
} else {
    // Redirige a la página anterior si no se encuentra el automatizador
    header('Location: tabla_automatizadores.php');
    exit;
}
?>

<!-- Main content -->
<div class="container-fluid">
    <div class="container mt-5">
        <h2><?php echo $nombre_tabla_plural; ?></h2>
        <h3>Automatizador: <?php echo $nombre_automatizador; ?></h3>
        <button id="btnBack" class="btn btn-danger mb-3"><i class="fas fa-arrow-left"></i> Regresar</button>
        <script>
            $(document).ready(function() {
                $('#btnBack').click(function() {
                    window.history.back();
                });
            });
        </script>
        <button id="btnAdd" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Agregar <?php echo $nombre_tabla_singular; ?></button>
        <table class="table table-bordered">
            <thead>
                <tr id="tableHeaders"></tr>
            </thead>
            <tbody id="interaccionesTable"></tbody>
        </table>
    </div>
</div>

<!-- Modal for CRUD -->
<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Agregar <?php echo $nombre_tabla_singular; ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="interaccionesForm"></form>
      </div>
    </div>
  </div>
</div>

<!-- Modal for View -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewModalTitle">Ver <?php echo $nombre_tabla_singular; ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="viewInteraccion"></div>
      </div>
    </div>
  </div>
</div>

<style>
.section-title {
    margin-top: 20px;
    margin-bottom: 10px;
}

.section-content {
    margin-bottom: 20px;
}
</style>

<script>
const idAutomatizador = <?php echo json_encode($id_automatizador); ?>;
const interaccionesFields = [
    { "nombre_campo_mysql": "tipo_interaccion", "nombre_campo_visible": "Tipo de Interacción", "tipo_campo": "select", "requerido": true, "aparece_en_tabla": true, "opciones": ["acciones", "condiciones", "disparadores"], "seccion_modal": "Información General", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "id_interaccion", "nombre_campo_visible": "ID de Interacción", "tipo_campo": "number", "requerido": true, "aparece_en_tabla": true, "seccion_modal": "Información General", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "uid_usuario", "nombre_campo_visible": "UID de Usuario", "tipo_campo": "number", "requerido": false, "aparece_en_tabla": true, "seccion_modal": "Información General", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "json_interaccion", "nombre_campo_visible": "JSON de Interacción", "tipo_campo": "textarea", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Detalles", "class_input_group": "col-12 col-md-12" }
];

$(document).ready(function() {
    // Generate table headers
    let tableHeaderHtml = '<th>ID</th>';
    interaccionesFields.forEach(field => {
        if (field.aparece_en_tabla) {
            tableHeaderHtml += `<th>${field.nombre_campo_visible}</th>`;
        }
    });
    tableHeaderHtml += '<th>Actions</th>';
    $('#tableHeaders').html(tableHeaderHtml);

    // Generate form fields
    let formHtml = '<input type="hidden" id="interaccionId">';
    formHtml += `<input type="hidden" id="id_automatizador" value="${idAutomatizador}">`;
    let secciones = {};
    interaccionesFields.forEach(field => {
        if (!secciones[field.seccion_modal]) {
            secciones[field.seccion_modal] = '';
        }
        if (field.tipo_campo === 'select') {
            let opcionesHtml = '';
            field.opciones.forEach(opcion => {
                opcionesHtml += `<option value="${opcion}">${opcion}</option>`;
            });
            secciones[field.seccion_modal] += `<div class="${field.class_input_group}">
                <div class="form-group">
                    <label for="${field.nombre_campo_mysql}">${field.nombre_campo_visible}</label>
                    <select class="form-control" id="${field.nombre_campo_mysql}" ${field.requerido ? 'required' : ''}>${opcionesHtml}</select>
                </div>
            </div>`;
        } else {
            secciones[field.seccion_modal] += `<div class="${field.class_input_group}">
                <div class="form-group">
                    <label for="${field.nombre_campo_mysql}">${field.nombre_campo_visible}</label>
                    <input type="${field.tipo_campo}" class="form-control" id="${field.nombre_campo_mysql}" ${field.requerido ? 'required' : ''}>
                </div>
            </div>`;
        }
    });
    for (let seccion in secciones) {
        formHtml += `<div class="row section-title"><h5>${seccion}</h5></div><div class="row section-content">${secciones[seccion]}</div>`;
    }
    formHtml += '<button type="submit" class="btn btn-primary">Guardar</button>';
    $('#interaccionesForm').html(formHtml);

    // Load interacciones and other functionalities
    loadInteracciones();
    $('#btnAdd').click(function() {
        $('#interaccionesForm')[0].reset();
        $('#interaccionId').val('');
        $('#modalTitle').text('Agregar <?php echo $nombre_tabla_singular; ?>');
        $('#configModal').modal('show');
    });

    $('#interaccionesForm').submit(function(event) {
        event.preventDefault();
        let formData = { id: $('#interaccionId').val(), action: $('#interaccionId').val() ? 'update' : 'create' };
        interaccionesFields.forEach(field => {
            formData[field.nombre_campo_mysql] = $(`#${field.nombre_campo_mysql}`).val();
        });
        formData['id_automatizador'] = idAutomatizador;

        $.ajax({
            url: '<?php echo $url_crud; ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#configModal').modal('hide');
                loadInteracciones();
            },
            error: function(xhr, status, error) {
                console.error("Error occurred:", error);
            }
        });
    });

    $(document).on('click', '.btnView', function() {
        let id = $(this).attr('data-id');
        $.ajax({
            url: '<?php echo $url_crud; ?>',
            type: 'POST',
            data: { id: id, action: 'read' },
            success: function(response) {
                let interaccion = JSON.parse(response);
                if (interaccion.length > 0) {
                    interaccion = interaccion[0];
                    let viewHtml = '';
                    let seccionesView = {};
                    interaccionesFields.forEach(field => {
                        if (!seccionesView[field.seccion_modal]) {
                            seccionesView[field.seccion_modal] = '';
                        }
                        seccionesView[field.seccion_modal] += `<div class="${field.class_input_group}">
                            <div class="form-group">
                                <label>${field.nombre_campo_visible}</label>
                                <input type="${field.tipo_campo}" class="form-control" value="${interaccion[field.nombre_campo_mysql]}" disabled>
                            </div>
                        </div>`;
                    });
                    for (let seccion in seccionesView) {
                        viewHtml += `<div class="row section-title"><h5>${seccion}</h5></div><div class="row section-content">${seccionesView[seccion]}</div>`;
                    }
                    $('#viewInteraccion').html(viewHtml);
                    $('#viewModalTitle').text('Ver <?php echo $nombre_tabla_singular; ?>');
                    $('#viewModal').modal('show');
                }
            }
        });
    });

    $(document).on('click', '.btnEdit', function() {
        let id = $(this).attr('data-id');
        $.ajax({
            url: '<?php echo $url_crud; ?>',
            type: 'POST',
            data: { id: id, action: 'read' },
            success: function(response) {
                let interaccion = JSON.parse(response);
                if (interaccion.length > 0) {
                    interaccion = interaccion[0];
                    $('#interaccionId').val(interaccion.id);
                    interaccionesFields.forEach(field => {
                        $(`#${field.nombre_campo_mysql}`).val(interaccion[field.nombre_campo_mysql]);
                    });
                    $('#modalTitle').text('Editar <?php echo $nombre_tabla_singular; ?>');
                    $('#configModal').modal('show');
                }
            }
        });
    });

    $(document).on('click', '.btnDelete', function() {
        if (confirm('¿Estas seguro que deseas eliminar este registro?')) {
            let id = $(this).attr('data-id');
            $.ajax({
                url: '<?php echo $url_crud; ?>',
                type: 'POST',
                data: { id: id, action: 'delete' },
                success: function(response) {
                    loadInteracciones();
                }
            });
        }
    });
});

function loadInteracciones() {
    $.ajax({
        url: '<?php echo $url_crud; ?>',
        type: 'POST',
        data: { action: 'read', id_automatizador: idAutomatizador },
        success: function(response) {
            let interacciones = JSON.parse(response);
            let html = '';
            interacciones.forEach(function(interaccion) {
                html += '<tr>';
                html += `<td>${interaccion.id}</td>`;
                interaccionesFields.forEach(field => {
                    if (field.aparece_en_tabla) {
                        html += `<td>${interaccion[field.nombre_campo_mysql]}</td>`;
                    }
                });
                html += `<td>
                            <button class="btn btn-success btnView" data-id="${interaccion.id}"><i class="fas fa-eye"></i> Ver</button> 
                            <button class="btn btn-warning btnEdit" data-id="${interaccion.id}"><i class="fas fa-edit"></i> Editar</button> 
                            <button class="btn btn-danger btnDelete" data-id="${interaccion.id}"><i class="fas fa-trash-alt"></i> Eliminar</button>
                         </td>`;
                html += '</tr>';
            });
            $('#interaccionesTable').html(html);
        }
    });
}
</script>

<?php require "widget_footer.php"; ?>
