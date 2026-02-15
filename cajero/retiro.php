<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
// Nota: incluimos header después de procesar para mostrar mensajes inmediatos

$id_cajero = $_SESSION['id_usuario']; // ID del cajero que realiza la operación
$mensajes_inmediatos = [];

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_cuenta = trim($_POST['numero_cuenta'] ?? '');
    $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0.0;

    $errores = [];

    if ($numero_cuenta === '') {
        $errores[] = "El número de cuenta es obligatorio.";
    }
    if (!is_numeric($monto) || $monto <= 0) {
        $errores[] = "El monto debe ser un número mayor que 0.";
    }

    // Validar que la cuenta exista y obtener saldo actual
    $saldo_actual = null;
    if (empty($errores)) {
        $stmt_check = mysqli_prepare($conexion, "SELECT saldo FROM cuentas_bancarias WHERE numero_cuenta = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $numero_cuenta);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $saldo_actual);
        $tiene = mysqli_stmt_fetch($stmt_check);
        mysqli_stmt_close($stmt_check);

        if (!$tiene) {
            $errores[] = "La cuenta bancaria no existe.";
        } elseif ($monto > (float)$saldo_actual) {
            $errores[] = "Saldo insuficiente. Saldo actual: Q" . number_format((float)$saldo_actual, 2);
        }
    }

    if (empty($errores)) {
        // Llamar SP para realizar retiro
        $stmt = mysqli_prepare($conexion, "CALL realizar_retiro(?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sdi", $numero_cuenta, $monto, $id_cajero);
        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) {
            $err = mysqli_error($conexion);
            if (stripos($err, 'Cuenta no encontrada') !== false) {
                $mensajes_inmediatos[] = "La cuenta bancaria no existe.";
            } elseif (stripos($err, 'Saldo insuficiente') !== false) {
                $mensajes_inmediatos[] = "Saldo insuficiente para realizar el retiro.";
            } else {
                $mensajes_inmediatos[] = "No fue posible completar el retiro.";
            }
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            set_mensaje("Retiro realizado exitosamente. Monto: Q" . number_format($monto, 2), "info");
            redirigir("retiro.php");
            exit();
        }
    } else {
        foreach ($errores as $error) {
            $mensajes_inmediatos[] = $error;
        }
    }
}

include "../includes/header.php";
?>

<h2>Realizar Retiro</h2>

<?php if (!empty($mensajes_inmediatos)): ?>
    <div class="mensaje error" style="text-align:left;">
        <?php foreach ($mensajes_inmediatos as $e): ?>
            <div><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="">
        <label for="numero_cuenta">Número de Cuenta:</label>
        <input type="text" id="numero_cuenta" name="numero_cuenta" required placeholder="Ej: 1001001001">

        <label for="monto">Monto a Retirar (Q):</label>
        <input type="number" id="monto" name="monto" step="0.01" min="0.01" required placeholder="50.00">

        <button type="submit" class="btn btn-primary">Realizar Retiro</button>
        <a href="index.php" class="btn btn-link btn-volver">Volver al Panel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>

