<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Usuario - Banco UMG</title>
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
        <h2>Iniciar sesión - Usuario Cliente</h2>

        <?php
        session_start();
        if (isset($_SESSION['mensaje'])) {
            echo '<div class="mensaje ' . $_SESSION['mensaje']['tipo'] . '">';
            echo htmlspecialchars($_SESSION['mensaje']['texto']);
            echo '</div>';
            unset($_SESSION['mensaje']);
        }
        ?>

        <form action="procesos/procesar_login_usuario.php" method="POST">
            <label for="usuario">Correo electrónico:</label>
            <input type="email" id="usuario" name="usuario" required>

            <label for="clave">Contraseña:</label>
            <input type="password" id="clave" name="clave" required>

            <button type="submit" class="btn btn-primary">Iniciar sesión</button>
            <a href="index.php" class="btn btn-link btn-volver">Volver al Inicio</a>
        </form>
    </main>

    <footer>
        <p>&copy; 2025 Universidad Mariano Gálvez - Proyecto Banca en Línea</p>
    </footer>
</body>
</html>
