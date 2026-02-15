<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

// Verificar que se enviÃ³ el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../login_cajero.php");
    exit();
}

$usuario = $_POST['usuario'] ?? '';
$clave = $_POST['clave'] ?? '';

// Validar campos vacÃ­os
if (empty($usuario) || empty($clave)) {
    set_mensaje("Por favor, ingresa tu correo y contraseÃ±a.", "error");
    redirigir("../login_cajero.php");
    exit();
}

// Llamar SP para autenticar cajero
$stmt = mysqli_prepare($conexion, "CALL autenticar_cajero(?, ?)");
mysqli_stmt_bind_param($stmt, "ss", $usuario, $clave);
mysqli_stmt_execute($stmt);

// Compatibilidad
$resultado = @mysqli_stmt_get_result($stmt);

$fila = null;
$hash = '';
if ($resultado) {
    $fila = mysqli_fetch_assoc($resultado);
    $hash = $fila['contrasena'] ?? '';
} else {
    $id_usuario = $nombre_completo = $correo = $contrasena = null;
    mysqli_stmt_bind_result($stmt, $id_usuario, $nombre_completo, $correo, $contrasena);
    if (mysqli_stmt_fetch($stmt)) {
        $fila = [
            'id_usuario' => $id_usuario,
            'nombre_completo' => $nombre_completo,
            'correo' => $correo,
        ];
        $hash = $contrasena ?? '';
    }
}

// Â¡CIERRA el statement ANTES de hacer otra consulta!
mysqli_stmt_close($stmt);
while (mysqli_more_results($conexion)) { mysqli_next_result($conexion); }

if ($fila && !empty($hash) && (password_verify($clave, $hash) || hash_equals($hash, $clave))) {
    $verifiedByHash = password_verify($clave, $hash);
    if (!$verifiedByHash || password_needs_rehash($hash, PASSWORD_BCRYPT)) {
        $nuevo = password_hash($clave, PASSWORD_BCRYPT);
        $upd = mysqli_prepare($conexion, "UPDATE usuarios SET contrasena=? WHERE id_usuario=?");
        mysqli_stmt_bind_param($upd, "si", $nuevo, $fila['id_usuario']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }
    // Verificar si estÃ¡ bloqueado â€” AHORA SÃ PUEDES HACER OTRA CONSULTA
    $stmt_estado = mysqli_prepare($conexion, "SELECT estado FROM usuarios WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt_estado, "i", $fila['id_usuario']);
    mysqli_stmt_execute($stmt_estado);
    $estado_fila = null;
    $result_estado = @mysqli_stmt_get_result($stmt_estado);
    if ($result_estado) {
        $estado_fila = mysqli_fetch_assoc($result_estado);
    } else {
        $estado = null;
        mysqli_stmt_bind_result($stmt_estado, $estado);
        if (mysqli_stmt_fetch($stmt_estado)) {
            $estado_fila = ['estado' => $estado];
        }
    }
    mysqli_stmt_close($stmt_estado);

    if ($estado_fila['estado'] === 'bloqueado') {
        set_mensaje("Tu cuenta estÃ¡ bloqueada. Contacta al administrador.", "error");
        redirigir("../login_cajero.php");
        exit();
    }

    // AutenticaciÃ³n exitosa
    $_SESSION['id_usuario'] = $fila['id_usuario'];
    $_SESSION['nombre_completo'] = $fila['nombre_completo'];
    $_SESSION['rol'] = 'cajero';
    $_SESSION['correo'] = $fila['correo'];

    set_mensaje("Inicio de sesion exitoso. Bienvenido, " . $fila['nombre_completo'] . ".", "info");
    redirigir("../cajero/crear_cuenta.php");
} else {
    // Fallo en autenticaciÃ³n
    set_mensaje("Usuario o contraseÃ±a incorrectos.", "error");
    redirigir("../login_cajero.php");
}

mysqli_close($conexion);
exit();
?>

