<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../usuario/perfil.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? 0;
$rol = $_SESSION['rol'] ?? '';
if (!$id_usuario || $rol !== 'cliente') {
    set_mensaje("No estás autorizado para esta acción.", "error");
    redirigir("../index.php");
    exit();
}

$nombre = trim($_POST['nombre_completo'] ?? '');

if ($nombre === '') {
    set_mensaje("El nombre no puede estar vacío.", "error");
    redirigir("../usuario/perfil.php");
    exit();
}

if (mb_strlen($nombre, 'UTF-8') > 100) {
    set_mensaje("El nombre es demasiado largo (máx. 100 caracteres).", "error");
    redirigir("../usuario/perfil.php");
    exit();
}

$stmt = mysqli_prepare($conexion, "UPDATE usuarios SET nombre_completo = ? WHERE id_usuario = ? AND rol = 'cliente'");
mysqli_stmt_bind_param($stmt, "si", $nombre, $id_usuario);
mysqli_stmt_execute($stmt);
$afectadas = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($afectadas > 0) {
    $_SESSION['nombre_completo'] = $nombre; // refrescar sesión
    set_mensaje("Perfil actualizado correctamente.", "exito");
} else {
    set_mensaje("No hubo cambios para guardar.", "info");
}

redirigir("../usuario/perfil.php");
exit();
?>

