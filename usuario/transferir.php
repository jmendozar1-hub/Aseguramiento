<?php
include "../includes/proteger.php";
include "../includes/conexion.php";

$id_usuario = $_SESSION['id_usuario'];

// Obtener cuentas de tercero del usuario
$cuentas_tercero = [];
$stmt = mysqli_prepare($conexion, "
    SELECT ct.id_cuenta_destino, ct.alias,
           ct.monto_maximo_por_transferencia, ct.max_transferencias_diarias,
           cb.numero_cuenta
    FROM cuentas_terceros ct
    JOIN cuentas_bancarias cb ON ct.id_cuenta_destino = cb.id_cuenta
    WHERE ct.id_usuario_cliente = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $rid_cuenta, $ralias, $rmonto_max, $rmax_diarias, $rnum_cuenta);
while (mysqli_stmt_fetch($stmt)) {
    $cuentas_tercero[] = [
        'id_cuenta_destino' => $rid_cuenta,
        'alias' => $ralias,
        'monto_maximo_por_transferencia' => $rmonto_max,
        'max_transferencias_diarias' => $rmax_diarias,
        'numero_cuenta' => $rnum_cuenta,
    ];
}
mysqli_stmt_close($stmt);

// Calcular uso de transferencias hoy por cuenta de tercero 
if (!empty($cuentas_tercero)) {
    $stmt_usos = mysqli_prepare($conexion, "
        SELECT COUNT(*)
        FROM transferencias t
        JOIN cuentas_bancarias origen ON t.id_cuenta_origen = origen.id_cuenta
        WHERE origen.id_usuario_cliente = ?
          AND t.id_cuenta_destino = ?
          AND t.fecha = CURDATE()
          AND t.estado = 'completada'
    ");
    foreach ($cuentas_tercero as $idx => $ct) {
        $usadas = 0;
        mysqli_stmt_bind_param($stmt_usos, "ii", $id_usuario, $ct['id_cuenta_destino']);
        mysqli_stmt_execute($stmt_usos);
        mysqli_stmt_bind_result($stmt_usos, $usadas);
        mysqli_stmt_fetch($stmt_usos);
        
        $cuentas_tercero[$idx]['usadas_hoy'] = (int)$usadas;
    }
    mysqli_stmt_close($stmt_usos);
}

// Errores inmediatos para mostrar 
$errores_inmediatos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cuenta_destino = isset($_POST['id_cuenta_destino']) ? (int)$_POST['id_cuenta_destino'] : 0;
    $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0.0;

    $errores = [];

    if ($id_cuenta_destino <= 0) {
        $errores[] = "Selecciona una cuenta de destino válida.";
    }
    if (!is_numeric($monto) || $monto <= 0) {
        $errores[] = "El monto debe ser mayor que 0.";
    }

    if (empty($errores)) {
        $monto_maximo = null; $max_diarias = null;
        $stmt_check = mysqli_prepare($conexion, "
            SELECT monto_maximo_por_transferencia, max_transferencias_diarias
            FROM cuentas_terceros
            WHERE id_cuenta_destino = ? AND id_usuario_cliente = ?
        ");
        mysqli_stmt_bind_param($stmt_check, "ii", $id_cuenta_destino, $id_usuario);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $monto_maximo, $max_diarias);
        $autorizada = mysqli_stmt_fetch($stmt_check);
        mysqli_stmt_close($stmt_check);

        if (!$autorizada) {
            $errores[] = "La cuenta seleccionada no está autorizada para transferencias.";
        } else {
            if ($monto > (float)$monto_maximo) {
                $errores[] = "El monto excede el límite permitido para esta cuenta (máximo: Q" . number_format((float)$monto_maximo, 2) . ").";
            }

            // Contar transferencias de hoy hacia esa cuenta
            $transferencias_hoy = 0;
            $stmt_count = mysqli_prepare($conexion, "
                SELECT COUNT(*) AS total
                FROM transferencias t
                JOIN cuentas_bancarias origen ON t.id_cuenta_origen = origen.id_cuenta
                WHERE origen.id_usuario_cliente = ?
                  AND t.id_cuenta_destino = ?
                  AND t.fecha = CURDATE()
                  AND t.estado = 'completada'
            ");
            mysqli_stmt_bind_param($stmt_count, "ii", $id_usuario, $id_cuenta_destino);
            mysqli_stmt_execute($stmt_count);
            mysqli_stmt_bind_result($stmt_count, $transferencias_hoy);
            mysqli_stmt_fetch($stmt_count);
            mysqli_stmt_close($stmt_count);

            if ($transferencias_hoy >= (int)$max_diarias) {
                $errores[] = "Has alcanzado el límite diario de transferencias para esta cuenta (" . (int)$max_diarias . " permitidas).";
            }
        }
    }

    if (empty($errores)) {
        $stmt_sp = mysqli_prepare($conexion, "CALL realizar_transferencia(?, ?, ?, @resultado)");
        mysqli_stmt_bind_param($stmt_sp, "iid", $id_usuario, $id_cuenta_destino, $monto);
        mysqli_stmt_execute($stmt_sp);
        mysqli_stmt_close($stmt_sp);
        while (mysqli_more_results($conexion)) { mysqli_next_result($conexion); }

        $result_msg = mysqli_query($conexion, "SELECT @resultado AS mensaje");
        $msg_row = $result_msg ? mysqli_fetch_assoc($result_msg) : null;
        $resultado_msg = $msg_row['mensaje'] ?? '';

        if ($resultado_msg === 'Transferencia exitosa') {
            set_mensaje("Transferencia exitosa. Monto: Q" . number_format($monto, 2), "info");
        } else {
            set_mensaje($resultado_msg ?: "No fue posible completar la transferencia.", "error");
        }
        redirigir("transferir.php");
        exit();
    } else {
        foreach ($errores as $error) {
            
            $errores_inmediatos[] = $error;
            $_SESSION['mensaje_ec'] = [ 'texto' => $error, 'tipo' => 'error' ];
        }
    }
}
include "../includes/header.php";
?>

<h2>Realizar Transferencia</h2>

<?php if (!empty($errores_inmediatos)): ?>
    <div class="mensaje error">
        <?php foreach ($errores_inmediatos as $e): ?>
            <div><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (count($cuentas_tercero) > 0): ?>
    <div class="form-container">
        <form method="POST" action="">
            <label for="id_cuenta_destino">Selecciona Cuenta de Destino:</label>
            <select id="id_cuenta_destino" name="id_cuenta_destino" required>
                <option value="">-- Selecciona una cuenta --</option>
                <?php foreach ($cuentas_tercero as $c): ?>
                    <option value="<?php echo $c['id_cuenta_destino']; ?>"
                            data-usadas="<?php echo (int)($c['usadas_hoy'] ?? 0); ?>"
                            data-max="<?php echo (int)$c['max_transferencias_diarias']; ?>"
                            data-monto-max="<?php echo (float)$c['monto_maximo_por_transferencia']; ?>">
                        <?php echo htmlspecialchars($c['alias'] . ' (' . $c['numero_cuenta'] . ')'); ?>
                        - Máx: Q<?php echo number_format((float)$c['monto_maximo_por_transferencia'], 2); ?>
                        - Límite: <?php echo (int)$c['max_transferencias_diarias']; ?> trans/día
                        - Hoy: <?php echo (int)($c['usadas_hoy'] ?? 0); ?>/<?php echo (int)$c['max_transferencias_diarias']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="infoLimite" class="mensaje info" style="margin-top: .75rem; text-align:left;">
                Selecciona una cuenta para ver su límite diario y el uso de hoy.
            </div>

            <label for="monto">Monto a Transferir (Q):</label>
            <input type="number" id="monto" name="monto" step="0.01" min="0.01" required placeholder="50.00">

            <button type="submit" id="btnTransferir" class="btn btn-primary">Transferir</button>
            <a href="index.php" class="btn btn-link btn-volver">Volver al Panel</a>
        </form>
    </div>
<?php else: ?>
    <div class="mensaje error">
        <p>No tienes cuentas de tercero registradas. <a href="agregar_tercero.php">Agrega una primero</a>.</p>
    </div>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>

<script>
  (function(){
    const sel = document.getElementById('id_cuenta_destino');
    const info = document.getElementById('infoLimite');
    const inputMonto = document.getElementById('monto');
    const btn = document.getElementById('btnTransferir');

    function setInfo(text, tipo) {
      if (!info) return;
      info.className = 'mensaje ' + (tipo || 'info');
      info.textContent = text;
    }

    function formatQ(n){
      try { return 'Q' + Number(n).toFixed(2); } catch(e){ return 'Q' + n; }
    }

    function updateInfo(){
      const opt = sel && sel.selectedOptions && sel.selectedOptions[0];
      
      if (!opt || !opt.hasAttribute('data-max')) {
        setInfo('Selecciona una cuenta para ver su límite diario y el uso de hoy.', 'info');
        if (btn) btn.disabled = true;
        return;
      }
      const usadas = parseInt(opt.dataset.usadas || '0', 10);
      const max = parseInt(opt.dataset.max || '0', 10);
      const montoMax = parseFloat(opt.dataset.montoMax || '0');
      const monto = parseFloat((inputMonto && inputMonto.value) ? inputMonto.value : '0');

      let tipo = 'info';
      let msg;
      let canTransfer = true;
      if (max > 0) {
        const restantes = Math.max(0, max - (isNaN(usadas) ? 0 : usadas));
        if (restantes > 0) {
          msg = `Puedes realizar ${restantes} de ${max} transferencias hoy a esta cuenta.`;
        } else {
          msg = `Has alcanzado el límite diario (${max}) para esta cuenta.`;
          tipo = 'error';
          canTransfer = false;
        }
      } else {
        msg = 'Límite diario no definido para esta cuenta.';
      }

      if (!isNaN(monto) && montoMax > 0 && monto > montoMax) {
        msg += ` Monto supera el máximo por transferencia (${formatQ(montoMax)}).`;
        tipo = 'error';
        canTransfer = false;
      }
      setInfo(msg, tipo);
      if (btn) btn.disabled = !canTransfer;
    }

    if (sel) sel.addEventListener('change', updateInfo);
    if (inputMonto) inputMonto.addEventListener('input', updateInfo);
    
    updateInfo();
  })();
</script>
