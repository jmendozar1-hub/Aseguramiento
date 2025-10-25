<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

// Verificar que el usuario esté autenticado y sea cliente
if (!esta_autenticado() || !es_cliente()) {
    set_mensaje("Acceso denegado.", "error");
    redirigir("../login_usuario.php");
    exit();
}

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../usuario/transferir.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$id_cuenta_destino = $_POST['id_cuenta_destino'] ?? 0;
$monto = $_POST['monto'] ?? '0';

// Validar campos
if ($id_cuenta_destino <= 0) {
    set_mensaje("Selecciona una cuenta de destino válida.", "error");
    redirigir("../usuario/transferir.php");
    exit();
}

if (!is_numeric($monto) || $monto <= 0) {
    set_mensaje("El monto debe ser mayor que 0.", "error");
    redirigir("../usuario/transferir.php");
    exit();
}

// Validar que la cuenta de tercero pertenezca al usuario
$stmt_check = mysqli_prepare($conexion, "
    SELECT monto_maximo_por_transferencia, max_transferencias_diarias
    FROM cuentas_terceros
    WHERE id_cuenta_destino = ? AND id_usuario_cliente = ?
");
mysqli_stmt_bind_param($stmt_check, "ii", $id_cuenta_destino, $id_usuario);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) === 0) {
    set_mensaje("La cuenta seleccionada no está autorizada para transferencias.", "error");
    mysqli_stmt_close($stmt_check);
    redirigir("../usuario/transferir.php");
    exit();
}

$tercero = mysqli_fetch_assoc($result_check);
$monto_maximo = $tercero['monto_maximo_por_transferencia'];
$max_diarias = $tercero['max_transferencias_diarias'];
mysqli_stmt_close($stmt_check);

// Validar monto máximo permitido
if ($monto > $monto_maximo) {
    set_mensaje("El monto excede el límite permitido para esta cuenta (máximo: Q" . number_format($monto_maximo, 2) . ").", "error");
    redirigir("../usuario/transferir.php");
    exit();
}

// Contar transferencias hoy a esta cuenta
$stmt_count = mysqli_prepare($conexion, "
    SELECT COUNT(*) AS total
    FROM transferencias t
    JOIN cuentas_bancarias origen ON t.id_cuenta_origen = origen.id_cuenta
    WHERE origen.id_usuario_cliente = ? 
      AND t.id_cuenta_destino = ? 
      AND t.fecha = CURDATE()
");
mysqli_stmt_bind_param($stmt_count, "ii", $id_usuario, $id_cuenta_destino);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$count_row = mysqli_fetch_assoc($result_count);
$transferencias_hoy = $count_row['total'] ?? 0;
mysqli_stmt_close($stmt_count);

// Validar límite diario de transferencias
if ($transferencias_hoy >= $max_diarias) {
    set_mensaje("Has alcanzado el límite diario de transferencias para esta cuenta (" . $max_diarias . " permitidas).", "error");
    redirigir("../usuario/transferir.php");
    exit();
}

// Llamar SP para realizar transferencia
$stmt_sp = mysqli_prepare($conexion, "CALL realizar_transferencia(?, ?, ?, @resultado)");
mysqli_stmt_bind_param($stmt_sp, "iid", $id_usuario, $id_cuenta_destino, $monto);
mysqli_stmt_execute($stmt_sp);
mysqli_stmt_close($stmt_sp);

// Obtener mensaje de resultado
$result_msg = mysqli_query($conexion, "SELECT @resultado AS mensaje");
$msg_row = mysqli_fetch_assoc($result_msg);
$resultado_msg = $msg_row['mensaje'];

if ($resultado_msg === 'Transferencia exitosa') {
    set_mensaje("✅ " . $resultado_msg . ". Monto: Q" . number_format($monto, 2), "info");
} else {
    set_mensaje("❌ " . $resultado_msg, "error");
}

redirigir("../usuario/transferir.php");
exit();
?>