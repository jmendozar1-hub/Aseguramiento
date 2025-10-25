<?php
session_start();
include "../includes/conexion.php";
include "../includes/funciones.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_mensaje("Acceso no permitido.", "error");
    redirigir("../login_admin.php");
    exit();
}

$usuario = $_POST['usuario'] ?? '';
$clave = $_POST['clave'] ?? '';

if (empty($usuario) || empty($clave)) {
    set_mensaje("Por favor, ingresa tu correo y contraseña.", "error");
    redirigir("../login_admin.php");
    exit();
}

$stmt = mysqli_prepare($conexion, "CALL autenticar_admin(?, ?)");
mysqli_stmt_bind_param($stmt, "ss", $usuario, $clave);
mysqli_stmt_execute($stmt);

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

mysqli_stmt_close($stmt);
// Limpiar posibles result sets pendientes del SP
while (mysqli_more_results($conexion)) { mysqli_next_result($conexion); }

if ($fila && !empty($hash) && (password_verify($clave, $hash) || hash_equals($hash, $clave))) {
    $verifiedByHash = password_verify($clave, $hash);
    // Si la contraseña es válida pero está en texto plano, rehash immediato
    if (!$verifiedByHash || password_needs_rehash($hash, PASSWORD_BCRYPT)) {
        $nuevo = password_hash($clave, PASSWORD_BCRYPT);
        $upd = mysqli_prepare($conexion, "UPDATE usuarios SET contrasena=? WHERE id_usuario=?");
        mysqli_stmt_bind_param($upd, "si", $nuevo, $fila['id_usuario']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }
    $_SESSION['id_usuario'] = $fila['id_usuario'];
    $_SESSION['nombre_completo'] = $fila['nombre_completo'];
    $_SESSION['rol'] = 'admin';
    $_SESSION['correo'] = $fila['correo'];

    set_mensaje("Inicio de sesion exitoso. Bienvenido, " . $fila['nombre_completo'] . ".", "info");
    redirigir("../admin/gestion_cajeros.php");
} else {
    set_mensaje("Usuario o contraseña incorrectos.", "error");
    redirigir("../login_admin.php");
}

mysqli_close($conexion);
exit();
?>
