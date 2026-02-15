<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../admin/agregar_cajero.php");
    exit();
}

$nombre = $_POST['nombre'] ?? '';
$usuario = $_POST['usuario'] ?? '';
$clave = $_POST['clave'] ?? '';
$clave_confirm = $_POST['clave_confirm'] ?? '';

// Validar que contraseñas coincidan
if ($clave !== $clave_confirm) {
    set_mensaje("Las contraseñas no coinciden.", "error");
    redirigir("../admin/agregar_cajero.php");
    exit();
}

// Validar campos vacíos
if (empty($nombre) || empty($usuario) || empty($clave)) {
    set_mensaje("Todos los campos son obligatorios.", "error");
    redirigir("../admin/agregar_cajero.php");
    exit();
}

// Llamar SP para agregar cajero
$hash = password_hash($clave, PASSWORD_BCRYPT);
$stmt = mysqli_prepare($conexion, "CALL agregar_cajero_sp(?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $nombre, $usuario, $hash);
$resultado = mysqli_stmt_execute($stmt);

if ($resultado) {
    set_mensaje("✅ Cajero registrado exitosamente.", "info");
    redirigir("../admin/gestion_cajeros.php");
} else {
    set_mensaje("❌ Error al registrar cajero: " . mysqli_error($conexion), "error");
    redirigir("../admin/agregar_cajero.php");
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);
exit();
?>
