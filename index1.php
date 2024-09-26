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
    <h2>Configuración de Datos</h2>
    <form id="dataForm">
        <div class="form-group">
            <label for="id_configuracion">ID Configuración</label>
            <input type="text" class="form-control" id="id_configuracion" value="15" required>
        </div>
        <div class="form-group">
            <label for="user_id">ID Usuario</label>
            <input type="text" class="form-control" id="user_id" value="1" required>
        </div>
        <div class="form-group">
            <label for="order_id">ID Orden</label>
            <input type="text" class="form-control" id="order_id" value="1" required>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" value="Adrián Recalde" required>
        </div>
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" class="form-control" id="direccion" value="La Floresta y Villaroel" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" value="alfacodeplanet@gmail.com" required>
        </div>
        <div class="form-group">
            <label for="celular">Celular</label>
            <input type="text" class="form-control" id="celular" value="593987267904" required>
        </div>
        <div class="form-group">
            <label for="productos">Productos (separados por coma)</label>
            <input type="text" class="form-control" id="productos" value="1,2,3" required>
        </div>
        <div class="form-group">
            <label for="categorias">Categorías (separadas por coma)</label>
            <input type="text" class="form-control" id="categorias" value="4,5,6" required>
        </div>
        <div class="form-group">
            <label for="status">Status (separados por coma)</label>
            <input type="text" class="form-control" id="status" value="7,8,9" required>
        </div>
        <div class="form-group">
            <label for="novedad">Novedad (separados por coma)</label>
            <input type="text" class="form-control" id="novedad" value="10,11,12" required>
        </div>
        <div class="form-group">
            <label for="provincia">Provincia (separados por coma)</label>
            <input type="text" class="form-control" id="provincia" value="13,14,15" required>
        </div>
        <div class="form-group">
            <label for="ciudad">Ciudad (separados por coma)</label>
            <input type="text" class="form-control" id="ciudad" value="16,17,18" required>
        </div>
    </form>

    <h2>Disparadores</h2>
    <div id="disparadoresContainer" class="mb-3">
        <?php foreach ($blocks_type as $block) : ?>
            <button type="button" class="btn btn-info disparador-btn mb-2" data-value="<?php echo $block['value']; ?>" data-icon="<?php echo $block['icon']; ?>">
                <i class="<?php echo $block['icon']; ?>"></i> <?php echo $block['name']; ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.disparador-btn').forEach(button => {
        button.addEventListener('click', function() {
            const value = this.dataset.value;

            // Obtención de datos del formulario
            const formData = new FormData(document.getElementById('dataForm'));

            // Construcción del objeto data a partir del formulario
            const data = {
                id_configuracion: formData.get('id_configuracion') || '',
                value_blocks_type: value,
                user_id: formData.get('user_id') || '',
                order_id: formData.get('order_id') || '',
                user_info: {
                    nombre: formData.get('nombre') || '',
                    direccion: formData.get('direccion') || '',
                    email: formData.get('email') || '',
                    celular: formData.get('celular') || '',
                    order_id: formData.get('order_id') || '',
                },
                productos: (formData.get('productos') || '').split(',').filter(v => v),
                categorias: (formData.get('categorias') || '').split(',').filter(v => v),
                status: (formData.get('status') || '').split(',').filter(v => v),
                novedad: (formData.get('novedad') || '').split(',').filter(v => v),
                provincia: (formData.get('provincia') || '').split(',').filter(v => v),
                ciudad: (formData.get('ciudad') || '').split(',').filter(v => v)
            };

            fetch('https://alfabusi-cp63.wordpresstemporal.com/automatizador.importsuit.com/webhook_automatizador.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Interacción registrada:', data);
            })
            .catch((error) => {
                console.error('Error occurred:', error);
            });
        });
    });
});
</script>

<?php require "widget_footer.php"; ?>