<?php 
include 'db.php';

$nombre_tabla_singular = "Instrucción";
$nombre_tabla_plural = "Instrucciones";
$url_crud = "index.php";
require "widget_header.php"; 

// Obtener los Disparadores
$sql_category = "SELECT category, name, description, icon, value FROM blocks_type WHERE category = 'Disparadores'";
$result_category = $conn->query($sql_category);

$blocks_type = array();

if ($result_category->num_rows > 0) {
    // Salida de datos de cada fila
    while($row_category = $result_category->fetch_assoc()) {
        $blocks_type[] = $row_category;
    }
}

?>

<div class="container mt-5">
    <h2>Formulario de Datos</h2>
    <form id="dataForm">
        <div class="form-group">
            <label for="id_configuracion">ID Configuración</label>
            <input type="text" class="form-control" id="id_configuracion" name="id_configuracion" value="1" required>
        </div>
        <div class="form-group">
            <label for="value_blocks_type">Tipo de Bloque</label>
            <select class="form-control" id="value_blocks_type" name="value_blocks_type" required>
                <?php foreach ($blocks_type as $block) : ?>
                    <option value="<?php echo $block['value']; ?>"><?php echo $block['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="user_id">User ID</label>
            <input type="text" class="form-control" id="user_id" name="user_id" value="1" required>
        </div>
        <div class="form-group">
            <label for="order_id">Order ID</label>
            <input type="text" class="form-control" id="order_id" name="order_id" value="1" required>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="Adrián Recalde" required>
        </div>
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" class="form-control" id="direccion" name="direccion" value="La Floresta y Villaroel" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="alfacodeplanet@gmail.com" required>
        </div>
        <div class="form-group">
            <label for="celular">Celular</label>
            <input type="text" class="form-control" id="celular" name="celular" value="593987267904" required>
        </div>
        <div class="form-group">
            <label for="productos">Productos (comma-separated)</label>
            <input type="text" class="form-control" id="productos" name="productos" value="1,2,3">
        </div>
        <div class="form-group">
            <label for="categorias">Categorías (comma-separated)</label>
            <input type="text" class="form-control" id="categorias" name="categorias" value="4,5,6">
        </div>
        <div class="form-group">
            <label for="status">Status (comma-separated)</label>
            <input type="text" class="form-control" id="status" name="status" value="7,8,9">
        </div>
        <div class="form-group">
            <label for="novedad">Novedad (comma-separated)</label>
            <input type="text" class="form-control" id="novedad" name="novedad" value="10,11,12">
        </div>
        <div class="form-group">
            <label for="provincia">Provincia (comma-separated)</label>
            <input type="text" class="form-control" id="provincia" name="provincia" value="13,14,15">
        </div>
        <div class="form-group">
            <label for="ciudad">Ciudad (comma-separated)</label>
            <input type="text" class="form-control" id="ciudad" name="ciudad" value="16,17,18">
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
</div>

<script>
$(document).ready(function() {
    $('#dataForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serializeArray();
        const data = {};
        formData.forEach(item => {
            if (item.name === 'productos' || item.name === 'categorias' || item.name === 'status' || item.name === 'novedad' || item.name === 'provincia' || item.name === 'ciudad') {
                data[item.name] = item.value.split(',');
            } else {
                data[item.name] = item.value;
            }
        });

        data.user_info = {
            nombre: data.nombre,
            direccion: data.direccion,
            email: data.email,
            celular: data.celular,
            order_id: data.order_id
        };

        $.ajax({
            url: 'https://alfabusi-cp63.wordpresstemporal.com/automatizador.importsuit.com/webhook_automatizador.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                alert("Datos enviados exitosamente");
                console.log("Interacción registrada:", response);
            },
            error: function(xhr, status, error) {
                alert("Error al enviar los datos");
                console.error("Error occurred:", error);
            }
        });
    });
});
</script>

<?php require "widget_footer.php"; ?>