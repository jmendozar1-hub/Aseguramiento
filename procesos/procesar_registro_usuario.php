<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../registro_usuario.php");
    exit();
}

$numero_cuenta = trim($_POST['numero_cuenta'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$dpi = trim($_POST['dpi'] ?? '');
$clave = $_POST['clave'] ?? '';
$clave_confirm = $_POST['clave_confirm'] ?? '';

if ($clave !== $clave_confirm) {
    set_mensaje("Las contraseñas no coinciden.", "error");
    redirigir("../registro_usuario.php");
    exit();
}

if (empty($numero_cuenta) || empty($correo) || empty($dpi) || empty($clave)) {
    set_mensaje("Todos los campos son obligatorios.", "error");
    redirigir("../registro_usuario.php");
    exit();
}

// Validaciones básicas previas para dar mensajes claros
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    set_mensaje("El correo electrónico no es válido.", "error");
    redirigir("../registro_usuario.php");
    exit();
}

// 1) ¿Correo ya registrado?
$stmt_chk_correo = mysqli_prepare($conexion, "SELECT COUNT(*) FROM usuarios WHERE correo = ?");
mysqli_stmt_bind_param($stmt_chk_correo, "s", $correo);
mysqli_stmt_execute($stmt_chk_correo);
mysqli_stmt_bind_result($stmt_chk_correo, $cntCorreo);
mysqli_stmt_fetch($stmt_chk_correo);
mysqli_stmt_close($stmt_chk_correo);
if ((int)$cntCorreo > 0) {
    set_mensaje("El correo ya está registrado.", "error");
    redirigir("../registro_usuario.php");
    exit();
}

// 2) ¿Cuenta existe? ¿DPI coincide? ¿Cuenta libre?
$stmt_chk_cta = mysqli_prepare($conexion, "SELECT dpi_titular, id_usuario_cliente FROM cuentas_bancarias WHERE numero_cuenta = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_chk_cta, "s", $numero_cuenta);
mysqli_stmt_execute($stmt_chk_cta);
mysqli_stmt_bind_result($stmt_chk_cta, $dpi_titular_db, $id_usuario_cliente_db);
$encontro = mysqli_stmt_fetch($stmt_chk_cta);
mysqli_stmt_close($stmt_chk_cta);

if (!$encontro) {
    set_mensaje("La cuenta bancaria no existe.", "error");
    redirigir("../registro_usuario.php");
    exit();
}
if ($dpi_titular_db !== $dpi) {
    set_mensaje("El DPI no coincide con el titular de la cuenta.", "error");
    redirigir("../registro_usuario.php");
    exit();
}
if (!is_null($id_usuario_cliente_db)) {
    set_mensaje("Esta cuenta ya tiene un usuario registrado.", "error");
    redirigir("../registro_usuario.php");
    exit();
}

$hash = password_hash($clave, PASSWORD_BCRYPT);
$stmt = mysqli_prepare($conexion, "CALL registrar_cliente(?, ?, ?, ?, @resultado)");
mysqli_stmt_bind_param($stmt, "ssss", $correo, $hash, $dpi, $numero_cuenta);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
// Limpiar posibles result sets del procedimiento antes de consultar la variable OUT
while (mysqli_more_results($conexion)) { mysqli_next_result($conexion); }

$result_msg = mysqli_query($conexion, "SELECT @resultado AS mensaje");
$msg_row = $result_msg ? mysqli_fetch_assoc($result_msg) : null;
$resultado_msg = $msg_row['mensaje'] ?? null;

if ($resultado_msg === 'Registro exitoso') {
    set_mensaje("Registro exitoso. Ahora puedes iniciar sesion.", "info");
    redirigir("../login_usuario.php");
} else {
    $texto = $resultado_msg ?: "No fue posible completar el registro.";
    set_mensaje($texto, "error");
    redirigir("../registro_usuario.php");
}

mysqli_close($conexion);
exit();
?>
