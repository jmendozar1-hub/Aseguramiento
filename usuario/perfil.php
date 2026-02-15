<?php
include "../includes/proteger.php"; 
include "../includes/conexion.php";
include "../includes/header.php";

$id_usuario = $_SESSION['id_usuario'];

// Obtener datos actuales del perfil
$nombre_actual = $_SESSION['nombre_completo'] ?? '';
$correo_actual = $_SESSION['correo'] ?? '';

// Por si se quiere mostrar el nombre más reciente desde BD
$stmt = mysqli_prepare($conexion, "SELECT nombre_completo, correo FROM usuarios WHERE id_usuario = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $nombre_db, $correo_db);
if (mysqli_stmt_fetch($stmt)) {
    $nombre_actual = $nombre_db;
    $correo_actual = $correo_db;
}
mysqli_stmt_close($stmt);
?>

<h2>Editar Perfil</h2>

<div class="form-container">
    <form method="POST" action="../procesos/procesar_actualizar_perfil.php">
        <label for="nombre_completo">Nombre Completo</label>
        <input type="text" id="nombre_completo" name="nombre_completo" required maxlength="100" value="<?php echo htmlspecialchars($nombre_actual); ?>">

        <label for="correo">Correo electrónico (solo lectura)</label>
        <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($correo_actual); ?>" readonly>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="index.php" class="btn btn-link btn-volver">Volver al Panel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>

