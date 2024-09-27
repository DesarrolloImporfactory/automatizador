<?php
// Conexión con base de datos MySQL
require 'db.php';

// Obtener el id_automatizador de la URL
$id_automatizador = isset($_GET['id_automatizador']) ? $_GET['id_automatizador'] : null;

$json_output = [];
$json_bloques = [];
$facebook_templates = [];

if ($id_automatizador) {
    // Realizar la consulta para obtener json_output y json_bloques
    $query = "SELECT json_output, json_bloques, nombre, id_configuracion FROM automatizadores WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_automatizador);
    $stmt->execute();
    $stmt->bind_result($json_output, $json_bloques, $nombre_automatizador, $id_configuracion);
    $stmt->fetch();
    $stmt->close();

    // Verificar si los resultados son null
    $json_output = $json_output ? $json_output : '[]';
    $json_bloques = $json_bloques ? $json_bloques : '[]';

    // Obtener los Disparadores
    $sql_category = "SELECT category, name, description, icon, value FROM blocks_type WHERE category = 'Disparadores'";
    $result_category = $conn->query($sql_category);

    $blocks_type = array();

    if ($result_category->num_rows > 0) {
        // Salida de datos de cada fila
        while ($row_category = $result_category->fetch_assoc()) {
            $blocks_type[] = $row_category;
        }
    }

    /*
    // Verificar si el array tiene datos
    if (empty($blocks_type)) {
        echo "El array está vacío.";
    } else {
        echo "El array contiene datos.";
    }
    */

    //echo "holaaa".json_encode($blocks_type);

    $blocks_type = [
        [
            'id' => 1,
            'category' => 'Disparadores',
            'name' => 'Producto comprado',
            'description' => 'Dispara una acción según el producto comprado',
            'icon' => 'fa fa-cart-plus',
            'value' => 1,
            'name_tag' => 'productos'
        ],
        [
            'id' => 2,
            'category' => 'Disparadores',
            'name' => 'Categoria comprada',
            'description' => 'Dispara una acción según la categoría comprada',
            'icon' => 'fas fa-list-alt',
            'value' => 2,
            'name_tag' => 'categorias'
        ],
        [
            'id' => 3,
            'category' => 'Disparadores',
            'name' => 'Cambio de status de la orden',
            'description' => 'Dispara una acción cuando el producto cambia de status',
            'icon' => 'fa fa-exchange-alt',
            'value' => 3,
            'name_tag' => 'status'
        ],
        [
            'id' => 4,
            'category' => 'Disparadores',
            'name' => 'Una orden presenta una novedad',
            'description' => 'Dispara una acción cuando una orden presenta una novedad',
            'icon' => 'fa fa-bell',
            'value' => 4,
            'name_tag' => 'novedad'
        ],
        [
            'id' => 5,
            'category' => 'Disparadores',
            'name' => 'Departamento del comprador',
            'description' => 'Dispara una acción según el producto comprado',
            'icon' => 'fa fa-map-marked-alt',
            'value' => 5,
            'name_tag' => 'provincia'
        ],
        [
            'id' => 6,
            'category' => 'Disparadores',
            'name' => 'Ciudad',
            'description' => 'Dispara una acción según la ciudad del comprador',
            'icon' => 'fa fa-map-marker-alt',
            'value' => 6,
            'name_tag' => 'ciudad'
        ],
        [
            'id' => 7,
            'category' => 'Acciones',
            'name' => 'Enviar Email (Proximamente)',
            'description' => 'Envía un email (Proximamente)',
            'icon' => 'fa fa-envelope',
            'value' => 7,
            'name_tag' => null
        ],
        [
            'id' => 8,
            'category' => 'Acciones',
            'name' => 'Enviar WHATSAPP',
            'description' => 'Envía un mensaje de whatsapp',
            'icon' => 'fa-brands fa-whatsapp',
            'value' => 8,
            'name_tag' => null
        ],
        [
            'id' => 9,
            'category' => 'Acciones',
            'name' => 'Cambiar status de la orden',
            'description' => 'Cambia el status de una orden',
            'icon' => 'fa fa-exchange-alt',
            'value' => 9,
            'name_tag' => null
        ],
        [
            'id' => 10,
            'category' => 'Condiciones',
            'name' => 'Decisión(Respuesta Rápida)',
            'description' => 'Usuario responde con un botón de respuesta rápida',
            'icon' => 'fa fa-reply',
            'value' => 10,
            'name_tag' => null
        ]
    ];

    // Consultar la API de Facebook
    $stmt = $conn->prepare("SELECT a.*, c.nombre_configuracion FROM automatizadores a LEFT JOIN configuraciones c ON a.id_configuracion = c.id WHERE a.id = ? LIMIT 1");
    $stmt->bind_param("i", $id_automatizador);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $id_configuracion = $result['id_configuracion'];

    $sql_configuraciones = "SELECT * FROM configuraciones WHERE id='$id_configuracion'";
    $configuracion_result = $conn->query($sql_configuraciones)->fetch_assoc();

    $whatsappBusinessAccountId = $configuracion_result['id_whatsapp'];
    $accessToken = $configuracion_result['token'];

    // Obtener parámetros
    $fields = isset($_GET['fields']) ? $_GET['fields'] : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

    // Construir la URL
    $url = 'https://graph.facebook.com/v20.0/' . $whatsappBusinessAccountId . '/message_templates';
    $params = array(
        'fields' => $fields,
        'limit' => $limit,
        'access_token' => $accessToken
    );
    $url .= '?' . http_build_query($params);

    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Ejecutar la solicitud cURL
    $response = curl_exec($ch);

    // Manejar errores de cURL
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        echo json_encode(array('error' => $error_msg));
        exit;
    }

    // Cerrar cURL
    curl_close($ch);

    // Decodificar respuesta JSON
    $responseArray = json_decode($response, true);

    // Verificar si la respuesta contiene datos
    if (isset($responseArray['data'])) {
        $facebook_templates = $responseArray['data'];
    } else {
        $facebook_templates = array();
    }

    // Cerrar la conexión
    $conn->close();

    // Formatear las plantillas de Facebook al formato deseado
    $templatesOptions = array(array("id" => "0", "text" => "Seleccionar opción"));
    $templatesOptions = array_merge($templatesOptions, array_map(function ($template) {
        return array("id" => $template["id"], "text" => $template["name"]);
    }, $facebook_templates));
} else {
    // Redirigir a tabla_automatizadores.php si id_automatizador no está presente en la URL
    header("Location: tabla_automatizadores.php");
    exit;
}

?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <!-- Primary Meta Tags -->
    <title>Automatizador <?php echo $nombre_automatizador; ?> - ImportSuit</title>
    <link
        href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap"
        rel="stylesheet" />
    <link href="styles.css" rel="stylesheet" type="text/css" />
    <!-- Incluye Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Incluye jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Incluye la hoja de estilos de Flowy.js -->
    <link href="lib/flowy-master/flowy.min.css" rel="stylesheet">
    <!-- CSS de Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- JS de Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Incluye FontAwesome para obtener el icono de "crear" -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://kit.fontawesome.com/e141005de3.js" crossorigin="anonymous"></script>
    <meta
        name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <script>
        <?php if (!empty($json_output) && $json_output !== '[]') { ?>

            // Pasar los datos de PHP a JavaScript y parsear a formato de array
            window.flowlyOutputBlocks = JSON.parse('<?php echo addslashes($json_output); ?>');

        <?php } ?>

        window.id_configuracion = <?php echo $id_configuracion; ?>;

        window.blocks_type = JSON.parse('<?php echo json_encode($blocks_type); ?>');

        window.facebookTemplates = JSON.parse('<?php echo json_encode($templatesOptions); ?>');
        //console.log(window.facebookTemplates);


        /* llenar varible window.selectMultipleOptions */
        // JSON inicial vacío o con placeholders
        window.selectMultipleOptions = {
            "Productos": [],
            "Categorias": [],
            "Status": [{
                    id: "0",
                    text: "Todos los Estados"
                },
                {
                    id: "1",
                    text: "Estado 1"
                },
                {
                    id: "2",
                    text: "Estado 2"
                },
                {
                    id: "3",
                    text: "Estado 3"
                }
            ],
            "Novedad": [{
                    id: "0",
                    text: "Todas las Novedades"
                },
                {
                    id: "1",
                    text: "Novedad 1"
                },
                {
                    id: "2",
                    text: "Novedad 2"
                },
                {
                    id: "3",
                    text: "Novedad 3"
                }
            ],
            "Provincia": [],
            "Ciudad": [],
            "id_whatsapp_message_template": window.facebookTemplates || []
        };

        // Función para actualizar la sección "Productos" con los datos de la API
        function cargarProductos() {
            $.ajax({
                url: 'https://new.imporsuitpro.com/productos/obtener_productos_tienda',
                method: 'GET',
                success: function(response) {
                    // Aquí asumimos que 'response' contiene un array de productos
                    window.selectMultipleOptions.Productos = response.map(function(producto) {
                        return {
                            id: producto.id_producto_tienda,
                            text: producto.nombre_producto_tienda
                        };
                    });
                    console.log('Productos cargados:', window.selectMultipleOptions.Productos);
                },
                error: function(error) {
                    console.error('Error al cargar productos:', error);
                }
            });
        }

        // Función para actualizar la sección "Categorias" con los datos de la API
        function cargarCategorias() {
            $.ajax({
                url: 'https://new.imporsuitpro.com/productos/productos/cargar_categorias',
                method: 'GET',
                success: function(response) {
                    window.selectMultipleOptions.Categorias = response.map(function(categoria) {
                        return {
                            id: categoria.id_linea,
                            text: categoria.nombre_linea
                        };
                    });
                    console.log('Categorias cargadas:', window.selectMultipleOptions.Categorias);
                },
                error: function(error) {
                    console.error('Error al cargar categorías:', error);
                }
            });
        }

        // Función para cargar todas las opciones
        function cargarTodasLasOpciones() {
            cargarProductos(); // Carga los productos
            cargarCategorias(); // Carga las categorías
            // Añade más funciones para cargar el resto de las secciones (Status, Novedad, Provincia, Ciudad) si es necesario
        }

        // Ejecuta la función para cargar las opciones cuando la página se carga
        $(document).ready(function() {
            cargarTodasLasOpciones();
        });
        /* Fin llenar varible window.selectMultipleOptions */
        

        window.response_template_facebook = <?php echo $response; ?>;
        console.log(window.response_template_facebook);

        function updateMessage() {
            var selectedValue = document.getElementById('id_whatsapp_message_template').value;
            var templateData = window.response_template_facebook.data.find(template => template.id === selectedValue);

            if (templateData) {
                var bodyComponent = templateData.components.find(component => component.type === 'BODY');
                if (bodyComponent) {
                    document.getElementById('mensaje').value = bodyComponent.text;
                }
            }
        }
    </script>
    <script src="lib/flowy-master/flowy.min.js"></script>
    <script src="main.js"></script>
    <?php //require "main.php" 
    ?>
</head>

<body>
    <div id="navigation">
        <div id="leftside">
            <div id="details">
                <div id="back"><img src="assets/arrow.svg" /></div>
                <div id="names">
                    <p id="title">Automatizador - <?php echo $nombre_automatizador; ?></p>
                    <p id="subtitle">Constructor Visual</p>
                </div>
            </div>
        </div>
        <!-- 
      <div id="centerswitch">
        <div id="leftswitch"><?php //echo $nombre_automatizador; 
                                ?></div>
        
      </div>
      -->
        <div id="buttonsright">
            <!-- <div id="discard"><div id="removeblock"><i class="fa fa-trash"></i> Borrar</div></div> -->
            <div id="discard">
                <div id="removeblock"><i class="fa fa-arrow-left-o"></i> Regresar</div>
            </div>
            <div id="publish" onclick="guardarAutomatizador()"><i class="fa fa-floppy"></i> Guardar y Salir</div>
        </div>
    </div>

    <div id="leftcard2" style="display: none;">
        <div id="opencard">
            <img src="assets/closeleft.svg" />
        </div>
    </div>
    <div id="leftcard">
        <div id="closecard">
            <img src="assets/closeleft.svg" />
        </div>
        <p id="header">Bloques</p>
        <div id="search">
            <img src="assets/search.svg" />
            <input type="text" placeholder="Buscar Bloques" />
        </div>
        <div id="subnav">
            <div id="triggers" class="navactive side">Disparadores</div>
            <div id="actions" class="navdisabled side">Acciones</div>
            <div id="loggers" class="navdisabled side">Condiciones</div>
        </div>
        <div id="blocklist">
        </div>
        <div id="footer">
            <a href="https://alfaingenius.com" target="_blank">
                <p>Hecho por</p>
                <p>by</p>
                AlfaIngenius
            </a>
        </div>
    </div>

    <div id="propwrap">
        <div id="properties">
            <div id="close">
                <img src="assets/close.svg" />
            </div>
            <p id="header2">Información</p>
            <div id="propswitch">

            </div>
            <script>
                <?php if (!empty($json_bloques) && $json_bloques !== '[]') { ?>
                    // Pasar los datos de PHP a JavaScript y parsear a formato de array
                    let formDataByBlock = JSON.parse('<?php echo addslashes($json_bloques); ?>');
                <?php } else { ?>
                    let formDataByBlock = [];
                <?php } ?>

                // Definir la función obtenerValoresFormulario
                function obtenerValoresFormulario() {
                    const formData = {};

                    // Obtener todos los elementos del formulario
                    const formElements = document.getElementById('myForm').elements;

                    // Iterar a través de los elementos del formulario
                    for (let i = 0; i < formElements.length; i++) {
                        const element = formElements[i];
                        const elementType = element.type;
                        const elementName = element.name;

                        // Ignorar los botones de envío y los campos ocultos
                        if (elementType !== 'submit') {
                            // Si es un elemento de selección múltiple, obtener los valores seleccionados
                            if (elementType === 'select-multiple') {
                                const selectedOptions = [];
                                for (let j = 0; j < element.options.length; j++) {
                                    if (element.options[j].selected) {
                                        selectedOptions.push(element.options[j].value);
                                    }
                                }
                                formData[elementName] = selectedOptions;
                            } else {
                                // Para otros tipos de elementos, simplemente obtener su valor
                                formData[elementName] = element.value;
                            }
                        }
                    }

                    // Obtener el valor del campo id_block
                    const idBlockValue = formData['id_block'];

                    // Almacenar los datos del formulario en formDataByBlock organizados por id_block
                    formDataByBlock[idBlockValue] = formData;

                    console.log(formDataByBlock); // Imprimir los datos en la consola
                }

                function extractInfo(htmlString) {
                    // Crear un nuevo DOMParser
                    const parser = new DOMParser();
                    // Parsear el HTML string en un documento DOM
                    const doc = parser.parseFromString(htmlString, 'text/html');

                    // Seleccionar todos los elementos con la clase 'blockelem'
                    const blocks = doc.querySelectorAll('.blockelem');
                    const blocksInfo = [];

                    // Iterar sobre cada 'blockelem' para extraer la información
                    blocks.forEach(block => {
                        const type = block.querySelector('input[name="blockelemtype"]').value;
                        const id = block.querySelector('input[name="blockid"]').value;
                        const name = block.querySelector('.blockyname').textContent.trim();
                        const description = block.querySelector('.blockyinfo p').textContent.trim();

                        blocksInfo.push({
                            type,
                            id,
                            name,
                            description
                        }); //, name, description });
                    });

                    // Seleccionar todos los elementos con la clase 'arrowblock'
                    const arrows = doc.querySelectorAll('.arrowblock');
                    const arrowsInfo = [];

                    // Iterar sobre cada 'arrowblock' para extraer la información
                    arrows.forEach(arrow => {
                        const arrowId = arrow.querySelector('.arrowid').value;
                        arrowsInfo.push({
                            arrowId
                        });
                    });

                    // Retornar la información extraída
                    return {
                        blocks: blocksInfo
                    };
                }

                function combinarJSONs(jsonOrdenBloques, jsonTipoBloques, jsonInfoBloques) {
                    const ordenBloques = jsonOrdenBloques;
                    const tipoBloques = jsonTipoBloques;
                    const infoBloques = jsonInfoBloques;

                    const bloquesCombinados = ordenBloques.map(bloque => {
                        const tipoBloque = tipoBloques.blocks.find(t => t.id === String(bloque.id));
                        const infoBloque = infoBloques[bloque.id];

                        return {
                            ...bloque,
                            tipo: tipoBloque ? tipoBloque.type : null,
                            name_type: tipoBloque ? tipoBloque.name : null,
                            description_type: tipoBloque ? tipoBloque.description : null,
                            info: infoBloque ? infoBloque : null
                        };
                    });

                    return bloquesCombinados;
                }

                // Función para mostrar una alerta con el contenido de formDataByBlock
                function guardarAutomatizador() {
                    //alert(JSON.stringify(formDataByBlock, null, 2)+" - "+JSON.stringify(flowy.output()));
                    /*
                    console.log("JSON Flowly Output Bloques: "+JSON.stringify(flowy.output()));
                    console.log("var json_orden_bloques = '"+JSON.stringify(flowy.output().blocks)+"'");
                    console.log("var json_tipo_bloques = '"+JSON.stringify(extractInfo(flowy.output().html))+"'");
                    console.log("var json_info_bloques = '"+JSON.stringify(formDataByBlock)+"'");
                    */

                    //enviar datos a la base de datos para que al abrirse el documento, se cargue directamente
                    var json_flowly_output = flowy.output();
                    var json_info_bloques = formDataByBlock;

                    //enviar datos para que se procese solicitud y se creen o actualicen los bloques: (disparadores, acciones, desciciones)
                    var json_resultado_automatizador = combinarJSONs(flowy.output().blocks, extractInfo(flowy.output().html), formDataByBlock);
                    console.log(json_resultado_automatizador);

                    /*
                    // Crear un objeto JSON general para agrupar todos los JSONs
                    var json_general = {
                        "flowly_output": json_flowly_output,
                        "info_bloques": formDataByBlock,
                        "resultado_automatizador": json_resultado_automatizador
                    };
                    */

                    var flowlyoutput_json = flowy.output();
                    flowlyoutput_json.html = [];

                    // Enviar los datos al servidor
                    fetch('guardar_automatizador.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                "id_automatizador": "<?php echo $_GET['id_automatizador']; ?>",
                                "flowly_output": flowlyoutput_json,
                                "info_bloques": formDataByBlock,
                                "resultado_automatizador": combinarJSONs(flowy.output().blocks, extractInfo(flowy.output().html), formDataByBlock)
                            })
                        })
                        .then(response => {
                            if (response.ok) {
                                // Si la respuesta es exitosa, obtener la respuesta del servidor como texto
                                response.text().then(function(text) {
                                    // Imprimir la respuesta del servidor en la consola
                                    console.log(text);
                                    // Abrir una nueva pestaña con la URL del archivo PHP
                                    //window.open('guardar_automatizador.php', '_blank');
                                });

                                window.location.href = 'tabla_automatizadores.php?id_configuracion=' + window.id_configuracion;
                            } else {
                                // Si hay un error en la respuesta del servidor, mostrar un mensaje de error
                                console.error('Error al enviar JSON a PHP.');
                            }
                        })
                        .catch(error => {
                            // Si hay un error de red, mostrar un mensaje de error
                            console.error('Error de red:', error);
                        });

                }

                // Obtiene el ID de automatizador desde la URL
                function getAutomatizadorIdFromUrl() {
                    const urlParams = new URLSearchParams(window.location.search);
                    return urlParams.get('id_automatizador');
                }

                /*
                // Función para generar las opciones del select
                function loadTemplateOptions(selectedTemplate) {
                    const idAutomatizador = getAutomatizadorIdFromUrl();
                    $.ajax({
                        url: 'conexion_mensajesplantilla.php',
                        type: 'GET',
                        data: {
                            id_automatizador: idAutomatizador,
                            fields: 'name,status',
                            limit: 10
                        },
                        success: function(response) {
                            console.log(response);
                            let html = '<option value="">Select a Template</option>';
                            if (response.length > 0) {
                                response.forEach(function(template) {
                                    const isSelected = selectedTemplate == template.id ? "selected" : "";
                                    html += `<option value="${template.id}" ${isSelected}>${template.name} (${template.status})</option>`;
                                });
                            }
                            $('#id_whatsapp_message_template').html(html);
                        },
                        error: function() {
                            alert('Failed to fetch templates');
                            $('#id_whatsapp_message_template').html('<option value="">Select a Template</option>');
                        }
                    });
                }
                */

                //console.log("id_automatizador"+getAutomatizadorIdFromUrl());
                document.addEventListener('DOMContentLoaded', function() {

                });
            </script>
            <div id="proplist">
                <p class="inputlabel">Select database</p>
                <div class="dropme">Database 1 <img src="assets/dropdown.svg" /></div>
                <p class="inputlabel">Check properties</p>
                <div class="dropme">All<img src="assets/dropdown.svg" /></div>
                <div class="checkus">
                    <img src="assets/checkon.svg" />
                    <p>Log on successful performance</p>
                </div>
                <div class="checkus">
                    <img src="assets/checkoff.svg" />
                    <p>Give priority to this block</p>
                </div>
            </div>
        </div>
    </div>
    <div id="canvas"></div>
</body>

</html>