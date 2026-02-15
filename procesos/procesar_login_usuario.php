<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../login_usuario.php");
    exit();
}

$usuario = $_POST['usuario'] ?? '';
$clave = $_POST['clave'] ?? '';

if (empty($usuario) || empty($clave)) {
    set_mensaje("Por favor, ingresa tu correo y contraseña.", "error");
    redirigir("../login_usuario.php");
    exit();
}

$stmt = mysqli_prepare($conexion, "CALL autenticar_cliente(?, ?)");
mysqli_stmt_bind_param($stmt, "ss", $usuario, $clave);
mysqli_stmt_execute($stmt);

// Intentar obtener el resultado con mysqlnd; si no, usar bind_result
$resultado = @mysqli_stmt_get_result($stmt);
$fila = null;
$hash = '';
if ($resultado) {
    $fila = mysqli_fetch_assoc($resultado);
    $hash = $fila['contrasena'] ?? '';
} else {
    $id_usuario = $nombre_completo = $correo = $contrasena = null;
    $id_cuenta = null;
    mysqli_stmt_bind_result($stmt, $id_usuario, $nombre_completo, $correo, $contrasena, $id_cuenta);
    if (mysqli_stmt_fetch($stmt)) {
        $fila = [
            'id_usuario' => $id_usuario,
            'nombre_completo' => $nombre_completo,
            'correo' => $correo,
            'id_cuenta' => $id_cuenta,
        ];
        $hash = $contrasena ?? '';
    }
}

mysqli_stmt_close($stmt);

if ($fila && !empty($hash) && (password_verify($clave, $hash) || hash_equals($hash, $clave)) && !empty($fila['id_cuenta'])) {
    $verifiedByHash = password_verify($clave, $hash);
    if (!$verifiedByHash || password_needs_rehash($hash, PASSWORD_BCRYPT)) {
        $nuevo = password_hash($clave, PASSWORD_BCRYPT);
        $upd = mysqli_prepare($conexion, "UPDATE usuarios SET contrasena=? WHERE id_usuario=?");
        mysqli_stmt_bind_param($upd, "si", $nuevo, $fila['id_usuario']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }
    $_SESSION['id_usuario'] = $fila['id_usuario'];
    $_SESSION['nombre_completo'] = $fila['nombre_completo'];
    $_SESSION['rol'] = 'cliente';
    $_SESSION['correo'] = $fila['correo'];
    $_SESSION['id_cuenta'] = $fila['id_cuenta'];

    // Determinar si tiene cuentas de tercero para guiar el primer uso
    $tiene_terceros = 0;
    $stmt_ct = mysqli_prepare($conexion, "SELECT COUNT(*) FROM cuentas_terceros WHERE id_usuario_cliente = ?");
    mysqli_stmt_bind_param($stmt_ct, "i", $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt_ct);
    mysqli_stmt_bind_result($stmt_ct, $tiene_terceros);
    mysqli_stmt_fetch($stmt_ct);
    mysqli_stmt_close($stmt_ct);

    if ((int)$tiene_terceros === 0) {
        set_mensaje("Inicio de sesión exitoso. Bienvenido, " . $fila['nombre_completo'] . ". Primero agrega una Cuenta de Tercero para poder transferir.", "info");
        redirigir("../usuario/agregar_tercero.php");
    } else {
        set_mensaje("Inicio de sesión exitoso. Bienvenido, " . $fila['nombre_completo'] . ".", "info");
        redirigir("../usuario/index.php");
    }
} else {
    set_mensaje("Usuario o contraseña incorrectos, o no tienes cuenta bancaria asignada.", "error");
    redirigir("../login_usuario.php");
}

mysqli_close($conexion);
exit();
?>
