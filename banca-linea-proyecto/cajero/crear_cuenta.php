<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
include "../includes/header.php";

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_titular = trim($_POST['nombre_titular'] ?? '');
    $numero_cuenta = trim($_POST['numero_cuenta'] ?? '');
    $dpi = trim($_POST['dpi'] ?? '');
    $monto_inicial = isset($_POST['monto_inicial']) ? (float)$_POST['monto_inicial'] : 0.0;

    $errores = [];

    if ($nombre_titular === '') $errores[] = "El nombre del titular es obligatorio.";
    if ($numero_cuenta === '') $errores[] = "El número de cuenta es obligatorio.";
    if ($dpi === '') $errores[] = "El DPI es obligatorio.";
    if (!is_numeric($monto_inicial) || $monto_inicial < 0) $errores[] = "El monto inicial debe ser un número válido mayor o igual a 0.";

    // Validar que el número de cuenta no exista
    if (empty($errores)) {
        $stmt_check = mysqli_prepare($conexion, "SELECT COUNT(*) FROM cuentas_bancarias WHERE numero_cuenta = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $numero_cuenta);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $cnt);
        mysqli_stmt_fetch($stmt_check);
        mysqli_stmt_close($stmt_check);

        if ((int)$cnt > 0) {
            $errores[] = "El número de cuenta ya existe. Usa uno diferente.";
        }
    }

    if (empty($errores)) {
        // Llamar SP para crear cuenta
        $stmt = mysqli_prepare($conexion, "CALL crear_cuenta_bancaria(?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssd", $nombre_titular, $numero_cuenta, $dpi, $monto_inicial);
        $resultado = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($resultado) {
            set_mensaje("Cuenta bancaria creada exitosamente.", "info");
            redirigir("crear_cuenta.php"); // Evita reenvío al recargar
            exit();
        } else {
            set_mensaje("Error al crear la cuenta: " . mysqli_error($conexion), "error");
        }
    } else {
        foreach ($errores as $error) {
            set_mensaje($error, "error");
        }
    }
}
?>

<h2>Crear Nueva Cuenta Bancaria</h2>

<div class="form-container">
    <form method="POST" action="">
        <label for="nombre_titular">Nombre del Titular:</label>
        <input type="text" id="nombre_titular" name="nombre_titular" required placeholder="Ej: María López">

        <label for="numero_cuenta">Número de Cuenta (único):</label>
        <input type="text" id="numero_cuenta" name="numero_cuenta" required placeholder="Ej: 1001001001">

        <label for="dpi">Número de DPI del Titular:</label>
        <input type="text" id="dpi" name="dpi" required placeholder="Ej: 1234567890101">

        <label for="monto_inicial">Monto Inicial (Q):</label>
        <input type="number" id="monto_inicial" name="monto_inicial" step="0.01" min="0" required value="0.00">

        <button type="submit" class="btn btn-primary">Crear Cuenta</button>
        <a href="index.php" class="btn btn-link btn-volver">Volver al Panel</a>
    </form>
    
</div>

<?php include "../includes/footer.php"; ?>
