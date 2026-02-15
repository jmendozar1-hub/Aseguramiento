
<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
include "../includes/header.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $clave_confirm = $_POST['clave_confirm'] ?? '';

    $errores = [];

    
    if ($nombre === '') {
        $errores[] = "El campo 'Nombre Completo' es obligatorio.";
    }
    if ($usuario === '') {
        $errores[] = "El campo 'Usuario (Correo)' es obligatorio.";
    }
    if ($clave === '') {
        $errores[] = "El campo 'Contraseña' es obligatorio.";
    }
    if ($clave !== $clave_confirm) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    
    if (empty($errores)) {
       
        $stmt = mysqli_prepare($conexion, "CALL agregar_cajero_sp(?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $nombre, $usuario, $clave);
        $resultado = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($resultado) {
            set_mensaje("Cajero registrado exitosamente.", "info");
            redirigir("gestion_cajeros.php");
            exit();
        } else {
            set_mensaje("Error al registrar cajero: " . mysqli_error($conexion), "error");
        }
    } else {
        
        foreach ($errores as $error) {
            set_mensaje($error, "error");
        }
    }
}
?>

<h2>Registrar Nuevo Cajero</h2>

<div class="form-container">
    <form method="POST" action="">
        <label for="nombre">Nombre Completo:</label>
        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan Pérez">

        <label for="usuario">Usuario (Correo electrónico):</label>
        <input type="email" id="usuario" name="usuario" required placeholder="cajero@banco.com">

        <label for="clave">Contraseña:</label>
        <input type="password" id="clave" name="clave" required>

        <label for="clave_confirm">Confirmar contraseña:</label>
        <input type="password" id="clave_confirm" name="clave_confirm" required>

        <button type="submit" class="btn btn-primary">Registrar Cajero</button>
        <a href="gestion_cajeros.php" class="btn btn-link btn-volver">Volver a Gestión de Cajeros</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>

