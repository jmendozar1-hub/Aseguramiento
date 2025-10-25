<?php

include_once '../includes/conexion.php';

header('Content-Type: text/plain; charset=utf-8');

function linea($txt) { echo $txt . "\n"; }

linea('== Pruebas BD: registrar_cliente y autenticar_cliente ==');

// Datos de prueba 
$correo = 'prueba@correo.com';
$clave = 'clave123';
$dpi   = '1234567890101';
$numcu = '1001001001';

// 1) Probar registro
linea("\n-- Registro de cliente");
$stmt = mysqli_prepare($conexion, "CALL registrar_cliente(?, ?, ?, ?, @resultado)");
mysqli_stmt_bind_param($stmt, 'ssss', $correo, $clave, $dpi, $numcu);
if (!mysqli_stmt_execute($stmt)) {
    linea('Error al ejecutar registrar_cliente: ' . mysqli_error($conexion));
}
mysqli_stmt_close($stmt);
while (mysqli_more_results($conexion)) { mysqli_next_result($conexion); }

$resMsg = mysqli_query($conexion, "SELECT @resultado AS mensaje");
if ($resMsg) {
    $row = mysqli_fetch_assoc($resMsg);
    linea('Resultado registro: ' . ($row['mensaje'] ?? 'NULL'));
} else {
    linea('No se pudo leer @resultado');
}

// 2) Probar login
linea("\n-- Login de cliente");
$stmt2 = mysqli_prepare($conexion, "CALL autenticar_cliente(?, ?)");
mysqli_stmt_bind_param($stmt2, 'ss', $correo, $clave);
if (!mysqli_stmt_execute($stmt2)) {
    linea('Error al ejecutar autenticar_cliente: ' . mysqli_error($conexion));
}

$fila = null;
$hash = '';
$resultado = @mysqli_stmt_get_result($stmt2);
if ($resultado) {
    $fila = mysqli_fetch_assoc($resultado);
    $hash = $fila['contrasena'] ?? '';
} else {
    $id_usuario = $nombre = $mail = $con = null; $id_cuenta = null;
    mysqli_stmt_bind_result($stmt2, $id_usuario, $nombre, $mail, $con, $id_cuenta);
    if (mysqli_stmt_fetch($stmt2)) {
        $fila = [
            'id_usuario' => $id_usuario,
            'nombre_completo' => $nombre,
            'correo' => $mail,
            'id_cuenta' => $id_cuenta,
        ];
        $hash = $con ?? '';
    }
}
mysqli_stmt_close($stmt2);

if ($fila && !empty($hash) && (password_verify($clave, $hash) || hash_equals($hash, $clave))) {
    linea('Login OK -> id_usuario=' . $fila['id_usuario'] . ', id_cuenta=' . $fila['id_cuenta']);
} else {
    linea('Login FAIL (sin resultados)');
}

mysqli_close($conexion);
linea("\n== Fin de pruebas ==\n");
?>
