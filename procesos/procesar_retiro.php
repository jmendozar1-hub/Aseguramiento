<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

// Verificar que el usuario esté autenticado y sea cajero
if (!esta_autenticado() || !es_cajero()) {
    set_mensaje("Acceso denegado.", "error");
    redirigir("../login_cajero.php");
    exit();
}

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../cajero/retiro.php");
    exit();
}

$numero_cuenta = $_POST['numero_cuenta'] ?? '';
$monto = $_POST['monto'] ?? '0';
$id_cajero = $_SESSION['id_usuario'];

// Validar campos vacíos
if (empty($numero_cuenta)) {
    set_mensaje("El número de cuenta es obligatorio.", "error");
    redirigir("../cajero/retiro.php");
    exit();
}

// Validar que el monto sea numérico y positivo
if (!is_numeric($monto) || $monto <= 0) {
    set_mensaje("El monto debe ser un número mayor que 0.", "error");
    redirigir("../cajero/retiro.php");
    exit();
}

// Verificar que la cuenta exista y obtener saldo actual
$stmt_check = mysqli_prepare($conexion, "SELECT id_cuenta, saldo FROM cuentas_bancarias WHERE numero_cuenta = ?");
mysqli_stmt_bind_param($stmt_check, "s", $numero_cuenta);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) === 0) {
    set_mensaje("La cuenta bancaria no existe.", "error");
    mysqli_stmt_close($stmt_check);
    redirigir("../cajero/retiro.php");
    exit();
}

$cuenta = mysqli_fetch_assoc($result_check);
$saldo_actual = $cuenta['saldo'];

if ($monto > $saldo_actual) {
    set_mensaje("Saldo insuficiente. Saldo actual: Q" . number_format($saldo_actual, 2), "error");
    mysqli_stmt_close($stmt_check);
    redirigir("../cajero/retiro.php");
    exit();
}
mysqli_stmt_close($stmt_check);

// Llamar SP para realizar retiro
$stmt = mysqli_prepare($conexion, "CALL realizar_retiro(?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sdi", $numero_cuenta, $monto, $id_cajero);
$resultado = mysqli_stmt_execute($stmt);

if ($resultado) {
    set_mensaje("✅ Retiro realizado exitosamente. Monto: Q" . number_format($monto, 2), "info");
    redirigir("../cajero/retiro.php");
} else {
    set_mensaje("❌ Error al realizar el retiro: " . mysqli_error($conexion), "error");
    redirigir("../cajero/retiro.php");
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);
exit();
?>