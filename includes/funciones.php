<?php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirigir a otra página
function redirigir($url) {
    header("Location: " . $url);
    exit();
}

// Establecer mensaje flash 
function set_mensaje($texto, $tipo = "info") {
    $_SESSION['mensaje'] = [
        'texto' => $texto,
        'tipo' => $tipo // 
    ];
}

// Mostrar mensaje flash si existe 
function mostrar_mensaje() {
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        echo '<div class="mensaje ' . htmlspecialchars($mensaje['tipo']) . '">';
        echo htmlspecialchars($mensaje['texto']);
        echo '</div>';
        unset($_SESSION['mensaje']); 
    }
}

// Verificar si el usuario está autenticado
function esta_autenticado() {
    return isset($_SESSION['id_usuario']);
}

// Verificar rol del usuario
function es_admin() {
    return esta_autenticado() && $_SESSION['rol'] === 'admin';
}

function es_cajero() {
    return esta_autenticado() && $_SESSION['rol'] === 'cajero';
}

function es_cliente() {
    return esta_autenticado() && $_SESSION['rol'] === 'cliente';
}

// Obtener ID del usuario actual
function obtener_id_usuario() {
    return esta_autenticado() ? $_SESSION['id_usuario'] : null;
}

// Obtener rol del usuario actual
function obtener_rol_usuario() {
    return esta_autenticado() ? $_SESSION['rol'] : null;
}

// Sanitizar entrada básica 
function sanitizar_entrada($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Formatear moneda
function formatear_moneda($monto) {
    return '$' . number_format($monto, 2, '.', ',');
}

// Validar que un valor no esté vacío
function requerido($valor) {
    return !empty(trim($valor));
}
?>