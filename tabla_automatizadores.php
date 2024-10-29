<?php


include 'db.php';


$nombre_tabla_singular = "Automatizador";
$nombre_tabla_plural = "Automatizadores";
$url_crud = "crud_automatizadores.php";
require "widget_header.php";

// Verifica si se ha proporcionado el parámetro id_configuracion en la URL
$id_configuracion = isset($_GET['id_configuracion']) ? $_GET['id_configuracion'] : null;

if (!$id_configuracion) {
?>

    <!-- Ejecuta Swal diciendo que sera redirigido a realizar una configuracion por que  no tiene -->
    <script>
        Swal.fire({
            title: 'No se ha proporcionado una configuración',
            text: 'Serás redirigido a la tabla de configuraciones para seleccionar una',
            icon: 'info',
            confirmButtonText: 'Aceptar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'https://new.imporsuitpro.com/pedidos/configuracion_chats_imporsuit';
            }
        });
    </script>
    <?php
    exit;
}


//validar si id_plataforma es igual al de la configuracion

$id_plataforma = $_SESSION['id_plataforma'];
$sql = "SELECT id_plataforma FROM configuraciones WHERE id='$id_configuracion'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['id_plataforma'] != $id_plataforma) {
    ?>
        <script>
            Swal.fire({
                title: 'No tienes permisos para acceder a esta configuración',
                text: 'Serás redirigido a la tabla de configuraciones para seleccionar una',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'https://new.imporsuitpro.com/pedidos/configuracion_chats_imporsuit';
                }
            });
        </script>
    <?php

        exit;
    }
} else {
    ?>
    <script>
        Swal.fire({
            title: 'No tienes permisos para acceder a esta configuración',
            text: 'Serás redirigido a la tabla de configuraciones para seleccionar una',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'https://new.imporsuitpro.com/pedidos/configuracion_chats_imporsuit';
            }
        });
    </script>
<?php

    exit;
}

// Obtener el nombre de la configuración
$nombre_configuracion = '';
$sql = "SELECT nombre_configuracion FROM configuraciones WHERE id='$id_configuracion'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nombre_configuracion = $row['nombre_configuracion'];
} else {
    // Redirige a tabla_configuraciones.php si no se encuentra la configuración
    header('Location: tabla_configuraciones.php');
    exit;
}
?>

<!-- Main content -->
<div class="container-fluid">
    <div class="container mt-5">
        <h2><?php echo $nombre_tabla_plural; ?></h2>
        <h3>Configuración: <?php echo $nombre_configuracion; ?></h3>
        <button id="btnBack" class="btn btn-danger mb-3"><i class="fas fa-arrow-left"></i> Regresar</button>
        <script>
            $(document).ready(function() {

                $('#btnBack').click(function() {
                    // Dividir por "."
                    var url = window.location.href;
                    var partes = url.split('.');
                    var subdominio = partes[1];
                    var url_api = "";

                    if (subdominio == "merkapro") {
                        url_api = "https://app.merkapro.ec/";
                    } else if (subdominio == "imporsuitpro") {
                        url_api = "https://new.imporsuitpro.com/";
                    }

                    window.location.href = url_api;
                });
            });
        </script>
        <button id="btnAdd" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Agregar <?php echo $nombre_tabla_singular; ?></button>
        <table class="table table-bordered">
            <thead>
                <tr id="tableHeaders"></tr>
            </thead>
            <tbody id="automatizadoresTable"></tbody>
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
                <form id="automatizadoresForm"></form>
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
                <div id="viewAutomatizador"></div>
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
    const idConfiguracion = <?php echo json_encode($id_configuracion); ?>;
    const automatizadoresFields = [{
            "nombre_campo_mysql": "nombre",
            "nombre_campo_visible": "Nombre",
            "tipo_campo": "text",
            "requerido": true,
            "aparece_en_tabla": true,
            "seccion_modal": "Información General",
            "class_input_group": "col-12 col-md-12"
        },
        //{ "nombre_campo_mysql": "json_output", "nombre_campo_visible": "JSON Output", "tipo_campo": "textarea", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Detalles", "class_input_group": "col-12 col-md-12" },
        //{ "nombre_campo_mysql": "json_bloques", "nombre_campo_visible": "JSON Bloques", "tipo_campo": "textarea", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Detalles", "class_input_group": "col-12 col-md-12" },
        {
            "nombre_campo_mysql": "estado",
            "nombre_campo_visible": "Estado",
            "tipo_campo": "checkbox",
            "requerido": false,
            "aparece_en_tabla": true,
            "seccion_modal": "Información General",
            "class_input_group": "col-12 col-md-6"
        }
    ];

    $(document).ready(function() {
        // Generate table headers
        let tableHeaderHtml = '<th>ID</th>';
        automatizadoresFields.forEach(field => {
            if (field.aparece_en_tabla) {
                tableHeaderHtml += `<th>${field.nombre_campo_visible}</th>`;
            }
        });
        tableHeaderHtml += '<th>Actions</th>';
        $('#tableHeaders').html(tableHeaderHtml);

        // Generate form fields
        let formHtml = '<input type="hidden" id="automatizadorId">';
        formHtml += `<input type="hidden" id="id_configuracion" value="${idConfiguracion}">`;
        let secciones = {};
        automatizadoresFields.forEach(field => {
            if (!secciones[field.seccion_modal]) {
                secciones[field.seccion_modal] = '';
            }
            if (field.tipo_campo === 'checkbox') {
                secciones[field.seccion_modal] += `<div class="${field.class_input_group}">
                <div class="form-group">
                    <label for="${field.nombre_campo_mysql}">${field.nombre_campo_visible}</label>
                    <input type="${field.tipo_campo}" class="form-control" id="${field.nombre_campo_mysql}">
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
        $('#automatizadoresForm').html(formHtml);

        // Load automatizadores and other functionalities
        loadAutomatizadores();
        $('#btnAdd').click(function() {
            $('#automatizadoresForm')[0].reset();
            $('#automatizadorId').val('');
            $('#modalTitle').text('Agregar <?php echo $nombre_tabla_singular; ?>');
            $('#configModal').modal('show');
        });

        $('#automatizadoresForm').submit(function(event) {
            event.preventDefault();
            let formData = {
                id: $('#automatizadorId').val(),
                action: $('#automatizadorId').val() ? 'update' : 'create'
            };
            automatizadoresFields.forEach(field => {
                if (field.tipo_campo === 'checkbox') {
                    formData[field.nombre_campo_mysql] = $(`#${field.nombre_campo_mysql}`).is(':checked') ? 1 : 0;
                } else {
                    formData[field.nombre_campo_mysql] = $(`#${field.nombre_campo_mysql}`).val();
                }
            });
            formData['id_configuracion'] = idConfiguracion;

            // Log formData to the console to verify the data being sent
            console.log("Form Data being sent:", formData);

            $.ajax({
                url: '<?php echo $url_crud; ?>',
                type: 'POST',
                data: formData,
                success: function(response) {
                    // Log the response from the server
                    console.log("Server Response:", response);

                    $('#configModal').modal('hide');
                    loadAutomatizadores();
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
                data: {
                    id: id,
                    action: 'read'
                },
                success: function(response) {
                    let automatizador = JSON.parse(response);
                    if (automatizador.length > 0) {
                        automatizador = automatizador[0];
                        let viewHtml = '';
                        let seccionesView = {};
                        automatizadoresFields.forEach(field => {
                            if (!seccionesView[field.seccion_modal]) {
                                seccionesView[field.seccion_modal] = '';
                            }
                            seccionesView[field.seccion_modal] += `<div class="${field.class_input_group}">
                            <div class="form-group">
                                <label>${field.nombre_campo_visible}</label>
                                <input type="${field.tipo_campo}" class="form-control" value="${automatizador[field.nombre_campo_mysql]}" disabled>
                            </div>
                        </div>`;
                        });
                        for (let seccion in seccionesView) {
                            viewHtml += `<div class="row section-title"><h5>${seccion}</h5></div><div class="row section-content">${seccionesView[seccion]}</div>`;
                        }
                        $('#viewAutomatizador').html(viewHtml);
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
                data: {
                    id: id,
                    action: 'read'
                },
                success: function(response) {
                    let automatizador = JSON.parse(response);
                    if (automatizador.length > 0) {
                        automatizador = automatizador[0];
                        $('#automatizadorId').val(automatizador.id);
                        automatizadoresFields.forEach(field => {
                            if (field.tipo_campo === 'checkbox') {
                                $(`#${field.nombre_campo_mysql}`).prop('checked', automatizador[field.nombre_campo_mysql] == 1);
                            } else {
                                $(`#${field.nombre_campo_mysql}`).val(automatizador[field.nombre_campo_mysql]);
                            }
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
                    data: {
                        id: id,
                        action: 'delete'
                    },
                    success: function(response) {
                        loadAutomatizadores();
                    }
                });
            }
        });
    });

    function loadAutomatizadores() {
        $.ajax({
            url: '<?php echo $url_crud; ?>',
            type: 'POST',
            data: {
                action: 'read',
                id_configuracion: idConfiguracion
            },
            success: function(response) {
                let automatizadores = JSON.parse(response);
                let html = '';
                automatizadores.forEach(function(automatizador) {
                    html += '<tr>';
                    html += `<td>${automatizador.id}</td>`;
                    automatizadoresFields.forEach(field => {
                        if (field.aparece_en_tabla) {
                            if (field.nombre_campo_mysql === 'id_configuracion') {
                                html += `<td>${automatizador.nombre_configuracion}</td>`;
                            } else if (field.nombre_campo_mysql === 'estado') {
                                html += `<td>${automatizador.estado == 1 ? 'Activo' : 'Inactivo'}</td>`;
                            } else {
                                html += `<td>${automatizador[field.nombre_campo_mysql]}</td>`;
                            }
                        }
                    });
                    //tabla_interacciones.php
                    /* html += `<td>
                            <a class="btn btn-info" href="constructor_automatizador.php?id_automatizador=${automatizador.id}"><i class="fas fa-paint-brush"></i> Construir</a>
                            <a class="btn btn-primary" href="tabla_interacciones.php?id_automatizador=${automatizador.id}"><i class="fa fa-tachometer"></i> Interacciones</a> 
                            <button class="btn btn-success btnView" data-id="${automatizador.id}"><i class="fas fa-eye"></i> Ver</button> 
                            <button class="btn btn-warning btnEdit" data-id="${automatizador.id}"><i class="fas fa-edit"></i> Editar</button> 
                            <button class="btn btn-danger btnDelete" data-id="${automatizador.id}"><i class="fas fa-trash-alt"></i> Eliminar</button>
                         </td>`; */

                    html += `<td>
                            <a class="btn btn-info" href="constructor_automatizador.php?id_automatizador=${automatizador.id}"><i class="fas fa-paint-brush"></i> Construir</a>
                            <button class="btn btn-warning btnEdit" data-id="${automatizador.id}"><i class="fas fa-edit"></i> Editar</button> 
                            <button class="btn btn-danger btnDelete" data-id="${automatizador.id}"><i class="fas fa-trash-alt"></i> Eliminar</button>
                         </td>`;
                    html += '</tr>';
                });
                $('#automatizadoresTable').html(html);
            }
        });
    }
</script>

<?php require "widget_footer.php"; ?>