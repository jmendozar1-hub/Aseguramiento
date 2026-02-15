<?php

include_once 'funciones.php';
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco en Línea UMG</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <script src="../js/funciones.js" defer></script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../img/logo.jpg" alt="Logo del Banco">
            <h1>Banco en Línea UMG</h1>
        </div>
    </header>

    <nav>
        <?php if (esta_autenticado()): ?>
            <div class="menu-usuario">
                <?php if (es_admin()): ?>
                    <p>Bienvenido, Administrador: <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Administrador'); ?>.</p>
                    <ul>
                        <li><a class="btn btn-secondary" href="../admin/gestion_cajeros.php">Gestión de Cajeros</a></li>
                        <li><a class="btn btn-secondary" href="../admin/monitor.php">Monitor de Transferencias</a></li>
                        <li><a class="btn btn-danger" href="../logout.php">Cerrar sesión</a></li>
                    </ul>
                <?php elseif (es_cajero()): ?>
                    <p>Bienvenido, Cajero: <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Cajero'); ?>.</p>
                    <ul>
                        <li><a class="btn btn-secondary" href="../cajero/crear_cuenta.php">Crear Cuenta</a></li>
                        <li><a class="btn btn-secondary" href="../cajero/deposito.php">Depósito</a></li>
                        <li><a class="btn btn-secondary" href="../cajero/retiro.php">Retiro</a></li>
                        <li><a class="btn btn-danger" href="../logout.php">Cerrar sesión</a></li>
                    </ul>
                <?php elseif (es_cliente()): ?>
                    <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Cliente'); ?>.</p>
                    <ul>
                        <li><a class="btn btn-secondary" href="../usuario/index.php">Panel de Usuario</a></li>
                        <li><a class="btn btn-secondary" href="../usuario/perfil.php">Editar Perfil</a></li>
                        <li><a class="btn btn-secondary" href="../usuario/agregar_tercero.php">Agregar Cuenta de Tercero</a></li>
                        <li><a class="btn btn-secondary" href="../usuario/transferir.php">Transferir</a></li>
                        <li><a class="btn btn-secondary" href="../usuario/estado_cuenta.php">Estado de Cuenta</a></li>
                        <li><a class="btn btn-danger" href="../logout.php">Cerrar sesión</a></li>
                    </ul>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="menu-publico">
                <p>Menú Público</p>
                <ul>
                    <li><a class="btn btn-secondary" href="../login_admin.php">Iniciar sesión (Admin)</a></li>
                    <li><a class="btn btn-secondary" href="../login_cajero.php">Iniciar sesión (Cajero)</a></li>
                    <li><a class="btn btn-secondary" href="../login_usuario.php">Iniciar sesión (Cliente)</a></li>
                    <li><a class="btn btn-secondary" href="../registro_usuario.php">Registrarse</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </nav>

    <main class="container">
        <?php mostrar_mensaje(); ?>
