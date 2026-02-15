<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

if (!esta_autenticado() || !es_cajero()) {
    set_mensaje("Acceso denegado.", "error");
    redirigir("../login_cajero.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../cajero/crear_cuenta.php");
    exit();
}

$nombre_titular = $_POST['nombre_titular'] ?? '';
$numero_cuenta = $_POST['numero_cuenta'] ?? '';
$dpi = $_POST['dpi'] ?? '';
$monto_inicial = $_POST['monto_inicial'] ?? '0';

if (empty($nombre_titular) || empty($numero_cuenta) || empty($dpi)) {
    set_mensaje("Todos los campos son obligatorios.", "error");
    redirigir("../cajero/crear_cuenta.php");
    exit();
}

if (!is_numeric($monto_inicial) || $monto_inicial < 0) {
    set_mensaje("El monto inicial debe ser un número válido mayor o igual a 0.", "error");
    redirigir("../cajero/crear_cuenta.php");
    exit();
}

// Verificar que la cuenta no exista
$stmt_check = mysqli_prepare($conexion, "SELECT id_cuenta FROM cuentas_bancarias WHERE numero_cuenta = ?");
mysqli_stmt_bind_param($stmt_check, "s", $numero_cuenta);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    set_mensaje("El número de cuenta ya existe. Usa uno diferente.", "error");
    mysqli_stmt_close($stmt_check);
    redirigir("../cajero/crear_cuenta.php");
    exit();
}
mysqli_stmt_close($stmt_check);

// Llamar SP
$stmt = mysqli_prepare($conexion, "CALL crear_cuenta_bancaria(?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sssd", $nombre_titular, $numero_cuenta, $dpi, $monto_inicial);
$resultado = mysqli_stmt_execute($stmt);

if ($resultado) {
    set_mensaje("✅ Cuenta bancaria creada exitosamente.", "info");
    redirigir("../cajero/crear_cuenta.php");
} else {
    set_mensaje("❌ Error al crear la cuenta: " . mysqli_error($conexion), "error");
    redirigir("../cajero/crear_cuenta.php");
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);
exit();
?>
