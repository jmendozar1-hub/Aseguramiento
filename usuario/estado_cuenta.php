<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
include "../includes/header.php";
// Mostrar mensajes de validación de transferencias si existe
$mensaje_ec = $_SESSION['mensaje_ec'] ?? null;
if ($mensaje_ec) {
    echo '<div class="mensaje ' . htmlspecialchars($mensaje_ec['tipo']) . '">' . htmlspecialchars($mensaje_ec['texto']) . '</div>';
    unset($_SESSION['mensaje_ec']);
}

$id_usuario = $_SESSION['id_usuario'];

// Verificar que el usuario tenga cuenta asignada
$stmt_cuenta = mysqli_prepare($conexion, "SELECT id_cuenta FROM cuentas_bancarias WHERE id_usuario_cliente = ?");
mysqli_stmt_bind_param($stmt_cuenta, "i", $id_usuario);
mysqli_stmt_execute($stmt_cuenta);
mysqli_stmt_bind_result($stmt_cuenta, $id_cuenta);
$tiene = mysqli_stmt_fetch($stmt_cuenta);
mysqli_stmt_close($stmt_cuenta);

if (!$tiene) {
    set_mensaje("No tienes una cuenta bancaria asignada.", "error");
    redirigir("index.php");
    exit();
}

// Resumen de límites de transferencias hoy por cuenta de tercero
$resumen_terceros = [];
$stmt_ter = mysqli_prepare($conexion, "
    SELECT ct.id_cuenta_destino, ct.alias, ct.max_transferencias_diarias, cb.numero_cuenta
    FROM cuentas_terceros ct
    JOIN cuentas_bancarias cb ON ct.id_cuenta_destino = cb.id_cuenta
    WHERE ct.id_usuario_cliente = ?
");
mysqli_stmt_bind_param($stmt_ter, "i", $id_usuario);
mysqli_stmt_execute($stmt_ter);
mysqli_stmt_bind_result($stmt_ter, $ter_id_dest, $ter_alias, $ter_max, $ter_num);
while (mysqli_stmt_fetch($stmt_ter)) {
    $resumen_terceros[] = [
        'id_cuenta_destino' => $ter_id_dest,
        'alias' => $ter_alias,
        'max_diarias' => $ter_max,
        'numero_cuenta' => $ter_num,
    ];
}
mysqli_stmt_close($stmt_ter);

if (!empty($resumen_terceros)) {
    $stmt_usos = mysqli_prepare($conexion, "
        SELECT COUNT(*)
        FROM transferencias t
        JOIN cuentas_bancarias origen ON t.id_cuenta_origen = origen.id_cuenta
        WHERE origen.id_usuario_cliente = ?
          AND t.id_cuenta_destino = ?
          AND t.fecha = CURDATE()
          AND t.estado = 'completada'
    ");
    foreach ($resumen_terceros as $idx => $r) {
        $usadas = 0;
        mysqli_stmt_bind_param($stmt_usos, "ii", $id_usuario, $r['id_cuenta_destino']);
        mysqli_stmt_execute($stmt_usos);
        mysqli_stmt_bind_result($stmt_usos, $usadas);
        mysqli_stmt_fetch($stmt_usos);
        $resumen_terceros[$idx]['usadas_hoy'] = (int)$usadas;
    }
    mysqli_stmt_close($stmt_usos);
}

// Obtener estado de cuenta vía SP 
$operaciones = [];
$stmt = mysqli_prepare($conexion, "CALL obtener_estado_cuenta(?)");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$result = @mysqli_stmt_get_result($stmt);
if ($result) {
    while ($fila = mysqli_fetch_assoc($result)) {
        $operaciones[] = $fila;
    }
} else {
    $tipo = $fecha = $hora = $signo = null; $monto = 0.0;
    mysqli_stmt_bind_result($stmt, $tipo, $monto, $fecha, $hora, $signo);
    while (mysqli_stmt_fetch($stmt)) {
        $operaciones[] = [
            'tipo' => $tipo,
            'monto' => $monto,
            'fecha' => $fecha,
            'hora' => $hora,
            'signo' => $signo,
        ];
    }
}
mysqli_stmt_close($stmt);
while (mysqli_more_results($conexion)) { mysqli_next_result($conexion); }
?>

<h2>Estado de Cuenta - <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></h2>
<p><strong>Cuenta: </strong> <?php echo (int)$id_cuenta; ?></p>

<?php if (!empty($resumen_terceros)): ?>
<div class="form-container" style="max-width:900px;">
    <h3>Resumen de límites de transferencias hoy</h3>
    <table>
        <thead>
            <tr>
                <th>Alias</th>
                <th>Número de Cuenta</th>
                <th>Usadas Hoy</th>
                <th>Límite Diario</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resumen_terceros as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['alias']); ?></td>
                    <td><?php echo htmlspecialchars($r['numero_cuenta']); ?></td>
                    <td><?php echo (int)($r['usadas_hoy'] ?? 0); ?></td>
                    <td><?php echo (int)$r['max_diarias']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (count($operaciones) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Tipo de Operación</th>
                <th>Monto (Q)</th>
                <th>Signo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($operaciones as $op): ?>
                <tr style="<?php echo ($op['signo'] ?? '') === 'credito' ? 'background-color: #d4edda;' : 'background-color: #f8d7da;'; ?>">
                    <td><?php echo htmlspecialchars($op['fecha'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($op['hora'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($op['tipo'] ?? ''); ?></td>
                    <td class="monto"><?php echo number_format((float)($op['monto'] ?? 0), 2); ?></td>
                    <td>
                        <?php if (($op['signo'] ?? '') === 'credito'): ?>
                            <span style="color: green; font-weight: bold;">+</span>
                        <?php else: ?>
                            <span style="color: red; font-weight: bold;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 1rem; padding: 1rem; background: #e9ecef; border-radius: 5px;">
        <strong>Leyenda:</strong><br>
        - <strong style="color: green;">+</strong> Crédito: Transferencias recibidas, depósitos.<br>
        - <strong style="color: red;">-</strong> Débito: Transferencias enviadas, retiros.
    </div>
<?php else: ?>
    <div class="mensaje info">
        <p>Aún no tienes operaciones registradas en tu cuenta.</p>
    </div>
<?php endif; ?>

<a href="index.php" class="btn btn-link btn-volver" style="display: inline-block; margin-top: 1rem;">Volver al Panel</a>

<?php include "../includes/footer.php"; ?>
$mensaje_ec = $_SESSION['mensaje_ec'] ?? null;
if ($mensaje_ec) {
    echo '<div class="mensaje ' . htmlspecialchars($mensaje_ec['tipo']) . '">' . htmlspecialchars($mensaje_ec['texto']) . '</div>';
    unset($_SESSION['mensaje_ec']);
}
