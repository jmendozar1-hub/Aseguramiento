<?php

// Incluir funciones para manejo de sesión y roles
include_once 'funciones.php';

// Si no está autenticado, redirigir al login general
if (!esta_autenticado()) {
    set_mensaje("Debes iniciar sesión para acceder a esta página.", "error");
    redirigir("../index.php");
    exit();
}

// Detectar qué rol se requiere según la carpeta actual
$ruta_actual = $_SERVER['SCRIPT_NAME'] ?? '';
$partes = explode('/', trim($ruta_actual, '/'));
$carpeta = $partes[1] ?? ''; 

$rol_requerido = null;

if ($carpeta === 'admin') {
    $rol_requerido = 'admin';
    $login_redirect = '../login_admin.php';
} elseif ($carpeta === 'cajero') {
    $rol_requerido = 'cajero';
    $login_redirect = '../login_cajero.php';
} elseif ($carpeta === 'usuario') {
    $rol_requerido = 'cliente';
    $login_redirect = '../login_usuario.php';
}

// Si se pudo determinar un rol requerido, verificarlo
if ($rol_requerido) {
    $tiene_acceso = false;
    if ($rol_requerido === 'admin' && es_admin()) {
        $tiene_acceso = true;
    } elseif ($rol_requerido === 'cajero' && es_cajero()) {
        $tiene_acceso = true;
    } elseif ($rol_requerido === 'cliente' && es_cliente()) {
        $tiene_acceso = true;
    }

    if (!$tiene_acceso) {
        set_mensaje("No tienes permiso para acceder a esta sección.", "error");
        redirigir($login_redirect);
        exit();
    }
}
?>

