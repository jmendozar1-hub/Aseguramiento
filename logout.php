<?php
// Iniciar sesión para poder destruirla
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la cookie de sesión, también:
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Establecer mensaje de despedida
$_SESSION['mensaje'] = [
    'texto' => 'Has cerrado sesión correctamente.',
    'tipo' => 'info'
];

// Redirigir a la página principal
header("Location: index.php");
exit();
?>