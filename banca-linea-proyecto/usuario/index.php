<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
include "../includes/header.php";   

$id_usuario = $_SESSION['id_usuario'];

// Obtener saldo y número de cuenta
$saldo_actual = 0.0;
$numero_cuenta = '';
$stmt_c = mysqli_prepare($conexion, "SELECT numero_cuenta, saldo FROM cuentas_bancarias WHERE id_usuario_cliente = ?");
mysqli_stmt_bind_param($stmt_c, "i", $id_usuario);
mysqli_stmt_execute($stmt_c);
mysqli_stmt_bind_result($stmt_c, $numero_cuenta, $saldo_actual);
mysqli_stmt_fetch($stmt_c);
mysqli_stmt_close($stmt_c);

// Contar cuentas de tercero
$cant_terceros = 0;
$stmt_t = mysqli_prepare($conexion, "SELECT COUNT(*) FROM cuentas_terceros WHERE id_usuario_cliente = ?");
mysqli_stmt_bind_param($stmt_t, "i", $id_usuario);
mysqli_stmt_execute($stmt_t);
mysqli_stmt_bind_result($stmt_t, $cant_terceros);
mysqli_stmt_fetch($stmt_t);
mysqli_stmt_close($stmt_t);
?>

<div style="margin: 1rem 0; padding: 1rem; background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.06)">
    <strong>Tu Cuenta:</strong>
    <div>- Número: <?php echo htmlspecialchars($numero_cuenta ?: '—'); ?></div>
    <div>- Saldo actual: Q<?php echo number_format((float)$saldo_actual, 2); ?></div>
</div>

<?php if ((int)$cant_terceros === 0): ?>
    <div class="mensaje info">Aún no tienes cuentas de tercero registradas. Agrega una para poder transferir dinero.</div>
<?php endif; ?>


<?php include "../includes/footer.php"; ?>
