<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Banco UMG</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="img/logo.jpg" alt="Logo del Banco">
            <h1>Banco en Línea UMG</h1>
        </div>
    </header>

    <main class="form-container">
        <h2>Registro de Nuevo Usuario</h2>
        <p><em>Recuerda: Debes tener una cuenta bancaria creada previamente por un cajero.</em></p>
        <div class="mensaje info" style="text-align:left;">
            <strong>¿No tienes una cuenta bancaria?</strong><br>
            - Pide a un cajero que la cree para ti.<br>
            - Si eres cajero: <a href="login_cajero.php">inicia sesión</a> y luego crea la cuenta en <a href="cajero/crear_cuenta.php">Crear Cuenta</a>.
        </div>

        <?php
        session_start();
        if (isset($_SESSION['mensaje'])) {
            echo '<div class="mensaje ' . $_SESSION['mensaje']['tipo'] . '">';
            echo htmlspecialchars($_SESSION['mensaje']['texto']);
            echo '</div>';
            unset($_SESSION['mensaje']);
        }
        ?>

        <form action="procesos/procesar_registro_usuario.php" method="POST">
            <label for="numero_cuenta">Número de Cuenta Bancaria:</label>
            <input type="text" id="numero_cuenta" name="numero_cuenta" required placeholder="Ej: 1001001001">

            <label for="correo">Correo electrónico (será tu usuario):</label>
            <input type="email" id="correo" name="correo" required>

            <label for="dpi">Número de DPI:</label>
            <input type="text" id="dpi" name="dpi" required placeholder="Ej: 1234567890101">

            <label for="clave">Contraseña:</label>
            <input type="password" id="clave" name="clave" required>

            <label for="clave_confirm">Confirmar contraseña:</label>
            <input type="password" id="clave_confirm" name="clave_confirm" required>

            <button type="submit" class="btn btn-primary">Registrarse</button>
            <a href="index.php" class="btn btn-link btn-volver">Volver al Inicio</a>
        </form>
    </main>

    <footer>
        <p>&copy; 2025 Universidad Mariano Gálvez - Proyecto Banca en Línea</p>
    </footer>
</body>
</html>
