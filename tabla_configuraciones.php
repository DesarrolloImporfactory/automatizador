<?php 

include 'db.php';

$nombre_tabla_singular = "Configuración";
$nombre_tabla_plural = "Configuraciones";
$url_crud = "crud_configuraciones.php";
require "widget_header.php"; 
?>
<!-- Main content -->
<div class="container-fluid">
    <div class="container mt-5">
        <h2><?php echo $nombre_tabla_plural; ?></h2>
        <button id="btnAdd" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Agregar <?php echo $nombre_tabla_singular; ?></button>
        <table class="table table-bordered">
            <thead>
                <tr id="tableHeaders"></tr>
            </thead>
            <tbody id="configTable"></tbody>
        </table>
    </div>
</div>

<!-- Modal for CRUD -->
<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Hacer el modal más grande -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Agregar <?php echo $nombre_tabla_singular; ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="configForm"></form>
      </div>
    </div>
  </div>
</div>

<!-- Modal for View -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Hacer el modal más grande -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewModalTitle">Ver <?php echo $nombre_tabla_singular; ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="viewConfig"></div>
      </div>
    </div>
  </div>
</div>

<style>
.section-title {
    margin-top: 20px; /* Espacio superior */
    margin-bottom: 10px; /* Espacio inferior */
}

.section-content {
    margin-bottom: 20px; /* Espacio inferior entre el contenido de la sección y el siguiente título */
}
</style>

<script>
const configFields = [
    { "nombre_campo_mysql": "nombre_configuracion", "nombre_campo_visible": "Nombre Configuracion", "tipo_campo": "text", "requerido": true, "aparece_en_tabla": true, "seccion_modal": "Configuracion General", "class_input_group": "col-12 col-md-12" },
    //whatsapp
    { "nombre_campo_mysql": "telefono", "nombre_campo_visible": "Teléfono", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion WhatsApp", "class_input_group": "col-12 col-md-12" },
    { "nombre_campo_mysql": "id_telefono", "nombre_campo_visible": "ID Whatsapp", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion WhatsApp", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "id_whatsapp", "nombre_campo_visible": "ID WhatsApp Business Account", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion WhatsApp", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "token", "nombre_campo_visible": "Token WhatsApp API", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion WhatsApp", "class_input_group": "col-12 col-md-12" },
    { "nombre_campo_mysql": "webhook_url", "nombre_campo_visible": "Token Webhook URL", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion WhatsApp", "class_input_group": "col-12 col-md-12" },
    { "nombre_campo_mysql": "whatsapp_button", "nombre_campo_visible": "", "tipo_campo": "button", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion WhatsApp", "class_input_group": "col-12 col-md-12", "text": "<i class='fa fa-whatsapp'></i>Conectar WhatsApp Business", "onclick": "launchWhatsAppSignup()" },
    //email
    { "nombre_campo_mysql": "server", "nombre_campo_visible": "Server", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion Email", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "port", "nombre_campo_visible": "Port", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion Email", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "security", "nombre_campo_visible": "Security", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion Email", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "from_name", "nombre_campo_visible": "From Name", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion Email", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "from_email", "tipo_campo_visible": "From Email", "tipo_campo": "email", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion Email", "class_input_group": "col-12 col-md-12" },
    { "nombre_campo_mysql": "auth_required", "nombre_campo_visible": "Auth Required", "tipo_campo": "checkbox", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion Email", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "usuario", "nombre_campo_visible": "Usuario Email", "tipo_campo": "text", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion Email", "class_input_group": "col-12 col-md-6" },
    { "nombre_campo_mysql": "contrasena", "nombre_campo_visible": "Contraseña Email", "tipo_campo": "password", "requerido": false, "aparece_en_tabla": false, "seccion_modal": "Configuracion Email", "class_input_group": "col-12 col-md-12" }
];

$(document).ready(function() {
    // Generate table headers
    let tableHeaderHtml = '<th>ID</th>';
    configFields.forEach(field => {
        if (field.aparece_en_tabla) {
            tableHeaderHtml += `<th>${field.nombre_campo_visible}</th>`;
        }
    });
    tableHeaderHtml += '<th>Webhook URL</th>'; // Add new header for webhook URL
    tableHeaderHtml += '<th>Actions</th>';
    $('#tableHeaders').html(tableHeaderHtml);

    // Generate form fields
    let formHtml = '<input type="hidden" id="configId">';
    let secciones = {};
    configFields.forEach(field => {
        if (!secciones[field.seccion_modal]) {
            secciones[field.seccion_modal] = '';
        }
        if (field.tipo_campo === 'button') {
            secciones[field.seccion_modal] += `<div class="${field.class_input_group}">
                <div class="form-group">
                    <button type="button" class="btn btn-success w-100" onclick="${field.onclick}">${field.text}</button>
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
    $('#configForm').html(formHtml);

    // Check if new record and generate webhook URL
    if ($('#configId').val() === '') {
        $('#webhook_url').val(generateWebhookUrl());
    }

    // Load configurations and other functionalities
    loadConfigurations();
    $('#btnAdd').click(function() {
        $('#configForm')[0].reset();
        $('#configId').val('');
        $('#webhook_url').val(generateWebhookUrl());
        $('#modalTitle').text('Agregar <?php echo $nombre_tabla_singular; ?>');
        $('#configModal').modal('show');
    });

    $('#configForm').submit(function(event) {
        event.preventDefault();
        let formData = { id: $('#configId').val(), action: $('#configId').val() ? 'update' : 'create' };
        configFields.forEach(field => {
            if (field.tipo_campo !== 'button') {
                formData[field.nombre_campo_mysql] = $(`#${field.nombre_campo_mysql}`).val();
            }
        });
        $.ajax({
            url: '<?php echo $url_crud; ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#configModal').modal('hide');
                loadConfigurations();
            },
            error: function(xhr, status, error) {
                console.error(error);
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
                let config = JSON.parse(response);
                if (config.length > 0) {
                    config = config[0];
                    let viewHtml = '';
                    let seccionesView = {};
                    configFields.forEach(field => {
                        if (!seccionesView[field.seccion_modal]) {
                            seccionesView[field.seccion_modal] = '';
                        }

                        seccionesView[field.seccion_modal] += `<div class="${field.class_input_group}">
                            <div class="form-group">
                                <label>${field.nombre_campo_visible}</label>
                                <input type="${field.tipo_campo}" class="form-control" value="${config[field.nombre_campo_mysql]}" disabled>
                            </div>
                        </div>`;

                    });
                    for (let seccion in seccionesView) {

                        viewHtml += `<div class="row section-title"><h5>${seccion}</h5></div><div class="row section-content">${seccionesView[seccion]}</div>`;
                    }
                    $('#viewConfig').html(viewHtml);
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
                let config = JSON.parse(response);
                if (config.length > 0) {
                    config = config[0];
                    $('#configId').val(config.id);
                    configFields.forEach(field => {
                        if (field.tipo_campo !== 'button') {
                            $(`#${field.nombre_campo_mysql}`).val(config[field.nombre_campo_mysql]);
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
                data: { id: id, action: 'delete' },
                success: function(response) {
                    loadConfigurations();
                }
            });
        }
    });
});

function loadConfigurations() {
    $.ajax({
        url: '<?php echo $url_crud; ?>',
        type: 'POST',
        data: { action: 'read' },
        success: function(response) {
            let configs = JSON.parse(response);
            let html = '';
            configs.forEach(function(config) {
                html += '<tr>';
                html += `<td>${config.id}</td>`;
                configFields.forEach(field => {
                    if (field.aparece_en_tabla) {
                        html += `<td>${config[field.nombre_campo_mysql]}</td>`;
                    }
                });
                // Obtiene el dominio actual
                const currentDomain = window.location.origin;
                // Construcción de la URL final del webhook
                let webhook_url_final = `${currentDomain}/webhook_whatsapp.php?id=${config.id}&webhook=${config.webhook_url}`;

                html += `<td>${webhook_url_final}</td>`; // Add webhook URL to table
                html += `<td>
                            <a class="btn btn-info " href="tabla_automatizadores.php?id_configuracion=${config.id}"><i class="fas fa-cogs"></i> Automatizar</a> 
                            <button class="btn btn-success btnView" data-id="${config.id}"><i class="fas fa-eye"></i> Ver</button> 
                            <button class="btn btn-warning btnEdit" data-id="${config.id}"><i class="fas fa-edit"></i> Editar</button> 
                            <button class="btn btn-danger btnDelete" data-id="${config.id}"><i class="fas fa-trash-alt"></i> Eliminar</button>
                         </td>`;
                html += '</tr>';
            });
            $('#configTable').html(html);
        }
    });
}

function generateWebhookUrl() {
    return 'wh_' + Math.random().toString(36).substr(2, 9);
}

function launchWhatsAppSignup() {
    var fieldWappbAccessToken = document.querySelector("input[id=\"token\"]");
    
    FB.login(function (response) {

        console.log(JSON.stringify(response));

        /* ejemplo de respuesta
            {"authResponse":{
                "userID":null,
                "expiresIn":null,
                "code":"AQA0M397v03w0LkEw-PR6MqHI5FE2yxdjuK8MiMDpAY8x_6_lsv1Cw0peH1YsyQEB8e0ybmlVSkyHSrkN906pJfuyEMOrhp___tC6QwMawIkObvgK2K-q7pGRla9nLXQ3mLjCnr3AyT_FP29HPJKqzNaBFDecvXDeF27gZeBGsxVoYbsGuHducLW3jFODF_nwt6AJnnprZT2y0Yzb4DQi8tw6DUQ4y74tPT2r1nzGKuLG2YbCaoH_uJ_YVpzlQeUkLd4qDH6AE4VkS1R9oKPF74ZPdcR7UOc3f2i0SJrlJfCpee6BX3rFsgPa4_kCOxayVnKU6o83ywhhRVM-VQ3Z1SDtgTaoO4y5pvbW0v7sJ4RCJHPUHRnsWvFeOH0IEZtK-IohN_rL3pX8QHvvzi9HL46"
            },"status":"connected"}

        */

        if (response.authResponse) {
            const accessToken = response.authResponse.code;
            fieldWappbAccessToken.value = accessToken;

            //id telefono: 183909418148732
            document.querySelector("input[id=\"id_telefono\"]").value = "183909418148732";

            //id cuenta whatsapp business: 251469508042612
            document.querySelector("input[id=\"id_whatsapp\"]").value = "251469508042612";

        } else {
            console.log("User cancelled login or did not fully authorize.");
        }

    }, {
        config_id: "1527543077835593",
        response_type: "code",
        override_default_response_type: true,
        extras: {
            setup: {}
        }
    });
}

window.fbAsyncInit = function() {
    FB.init({
        appId            : "1492747491342122",
        autoLogAppEvents : true,
        xfbml            : true,
        version          : "v20.0"
    });
};

(function(d, s, id){
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {return;}
    js = d.createElement(s); js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

</script>

<?php require "widget_footer.php"; ?>