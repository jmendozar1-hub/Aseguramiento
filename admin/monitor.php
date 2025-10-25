<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
include "../includes/header.php";

// 1. Cuentas creadas hoy
$sql_cuentas_hoy = "SELECT COUNT(*) AS total FROM cuentas_bancarias WHERE DATE(fecha_creacion) = CURDATE()";
$result_cuentas = mysqli_query($conexion, $sql_cuentas_hoy);
$cuentas_hoy = $result_cuentas ? (int)(mysqli_fetch_assoc($result_cuentas)['total'] ?? 0) : 0;

// 2. Usuarios cliente registrados hoy
$sql_usuarios_hoy = "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'cliente' AND DATE(fecha_registro) = CURDATE()";
$result_usuarios = mysqli_query($conexion, $sql_usuarios_hoy);
$usuarios_hoy = $result_usuarios ? (int)(mysqli_fetch_assoc($result_usuarios)['total'] ?? 0) : 0;

// 3. Movimientos totales hoy (depósitos + retiros + transferencias)
$sql_mov_hoy = "SELECT ((SELECT COUNT(*) FROM transacciones WHERE DATE(fecha)=CURDATE()) + (SELECT COUNT(*) FROM transferencias WHERE DATE(fecha)=CURDATE())) AS total";
$result_mov = mysqli_query($conexion, $sql_mov_hoy);
$transacciones_hoy = $result_mov ? (int)(mysqli_fetch_assoc($result_mov)['total'] ?? 0) : 0;

// 4. Depósitos hoy (conteo)
$sql_depositos_hoy = "SELECT COUNT(*) AS total FROM transacciones WHERE tipo = 'deposito' AND DATE(fecha) = CURDATE()";
$result_depositos = mysqli_query($conexion, $sql_depositos_hoy);
$depositos_hoy = $result_depositos ? (int)(mysqli_fetch_assoc($result_depositos)['total'] ?? 0) : 0;

// 5. Retiros hoy (conteo)
$sql_retiros_hoy = "SELECT COUNT(*) AS total FROM transacciones WHERE tipo = 'retiro' AND DATE(fecha) = CURDATE()";
$result_retiros = mysqli_query($conexion, $sql_retiros_hoy);
$retiros_hoy = $result_retiros ? (int)(mysqli_fetch_assoc($result_retiros)['total'] ?? 0) : 0;

// 6. Monto total en depósitos hoy
$sql_monto_depositos = "SELECT COALESCE(SUM(monto), 0) AS total FROM transacciones WHERE tipo = 'deposito' AND DATE(fecha) = CURDATE()";
$result_monto_dep = mysqli_query($conexion, $sql_monto_depositos);
$monto_depositos = $result_monto_dep ? (float)(mysqli_fetch_assoc($result_monto_dep)['total'] ?? 0) : 0;

// 7. Monto total en retiros hoy
$sql_monto_retiros = "SELECT COALESCE(SUM(monto), 0) AS total FROM transacciones WHERE tipo = 'retiro' AND DATE(fecha) = CURDATE()";
$result_monto_ret = mysqli_query($conexion, $sql_monto_retiros);
$monto_retiros = $result_monto_ret ? (float)(mysqli_fetch_assoc($result_monto_ret)['total'] ?? 0) : 0;

// 8. Monto total en transferencias hoy
$sql_monto_transf = "SELECT COALESCE(SUM(monto), 0) AS total FROM transferencias WHERE DATE(fecha) = CURDATE()";
$result_monto_tr = mysqli_query($conexion, $sql_monto_transf);
$monto_transferencias = $result_monto_tr ? (float)(mysqli_fetch_assoc($result_monto_tr)['total'] ?? 0) : 0;
?>

<h2>Monitor de Actividad - Hoy</h2>

<!-- Sección 1: Estadísticas -->
<div class="estadisticas">
    <h3>Estadísticas del Día</h3>
    <ul>
        <li><strong>Cuentas Bancarias Creadas:</strong> <?php echo $cuentas_hoy; ?></li>
        <li><strong>Usuarios Cliente Registrados:</strong> <?php echo $usuarios_hoy; ?></li>
        <li><strong>Movimientos Totales:</strong> <?php echo $transacciones_hoy; ?></li>
        <li><strong>Depósitos Realizados (hoy):</strong> <?php echo $depositos_hoy; ?></li>
        <li><strong>Retiros Realizados (hoy):</strong> <?php echo $retiros_hoy; ?></li>
    </ul>
    
</div>

<!-- Sección 2: Gráfica -->
<div class="grafica">
    <h3>Comparativa: Depósitos vs Retiros vs Transferencias (Hoy)</h3>
    <canvas id="graficaMontos" width="400" height="200"></canvas>
</div>

<!-- Sección 3: Lista de Usuarios -->
<h3>Lista de Usuarios del Sistema</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Registrado</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql_usuarios = "SELECT id_usuario, nombre_completo, correo, rol, estado, fecha_registro FROM usuarios ORDER BY fecha_registro DESC";
        $result_usuarios = mysqli_query($conexion, $sql_usuarios);
        if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0) {
            while ($u = mysqli_fetch_assoc($result_usuarios)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($u['id_usuario']) . '</td>';
                echo '<td>' . htmlspecialchars($u['nombre_completo']) . '</td>';
                echo '<td>' . htmlspecialchars($u['correo']) . '</td>';
                echo '<td>' . ucfirst($u['rol']) . '</td>';
                echo '<td>' . ucfirst($u['estado']) . '</td>';
                echo '<td>' . htmlspecialchars($u['fecha_registro']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6" style="text-align:center;">No hay usuarios registrados.</td></tr>';
        }
        mysqli_free_result($result_usuarios);
        ?>
    </tbody>
</table>

<!-- Sección 4: Historial de Movimientos por Usuario -->
<h3>Historial de Movimientos por Usuario</h3>
<table>
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>Monto (Q)</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Cuenta Afectada</th>
        </tr>
    </thead>
    <tbody>
        <?php
        
        
        $sql_historial = "
            SELECT 
                COALESCE(NULLIF(uc.nombre_completo, 'Cliente Nuevo'), cb.nombre_titular) AS nombre,
                uc.correo AS correo,
                t.tipo AS tipo,
                t.monto AS monto,
                t.fecha AS fecha,
                t.hora AS hora,
                cb.numero_cuenta AS cuenta_afectada
            FROM transacciones t
            JOIN cuentas_bancarias cb ON t.id_cuenta = cb.id_cuenta
            LEFT JOIN usuarios uc ON cb.id_usuario_cliente = uc.id_usuario
            UNION ALL
            SELECT 
                COALESCE(NULLIF(uo.nombre_completo, 'Cliente Nuevo'), cbo.nombre_titular) AS nombre,
                uo.correo AS correo,
                'transferencia' AS tipo,
                tr.monto AS monto,
                tr.fecha AS fecha,
                tr.hora AS hora,
                CONCAT(cbo.numero_cuenta, ' -> ', cbd.numero_cuenta) AS cuenta_afectada
            FROM transferencias tr
            JOIN cuentas_bancarias cbo ON tr.id_cuenta_origen = cbo.id_cuenta
            LEFT JOIN usuarios uo ON cbo.id_usuario_cliente = uo.id_usuario
            JOIN cuentas_bancarias cbd ON tr.id_cuenta_destino = cbd.id_cuenta
            ORDER BY fecha DESC, hora DESC
            LIMIT 20
        ";
        $result_historial = mysqli_query($conexion, $sql_historial);
        function mask_email($email) {
            if (!$email) return '';
            $parts = explode('@', $email);
            if (count($parts) !== 2) return $email;
            [$local, $domain] = $parts;
            $localVis = max(1, min(2, strlen($local)));
            $maskedLocal = substr($local, 0, $localVis) . str_repeat('*', max(0, strlen($local) - $localVis));
            $domainParts = explode('.', $domain);
            if (count($domainParts) >= 2) {
                $tld = array_pop($domainParts);
                $domCore = implode('.', $domainParts);
                $domVis = max(1, min(2, strlen($domCore)));
                $maskedDom = substr($domCore, 0, $domVis) . str_repeat('*', max(0, strlen($domCore) - $domVis));
                return $maskedLocal . '@' . $maskedDom . '.' . $tld;
            } else {
                $domVis = max(1, min(2, strlen($domain)));
                $maskedDom = substr($domain, 0, $domVis) . str_repeat('*', max(0, strlen($domain) - $domVis));
                return $maskedLocal . '@' . $maskedDom;
            }
        }
        if ($result_historial && mysqli_num_rows($result_historial) > 0) {
            while ($h = mysqli_fetch_assoc($result_historial)) {
                $nombre = $h['nombre'] ?? '';
                $correo = $h['correo'] ?? '';
                $mostrarUsuario = trim($nombre) !== '' ? $nombre : '—';
                if ($correo) {
                    $mostrarUsuario .= ' (' . htmlspecialchars(mask_email($correo)) . ')';
                }
                echo '<tr>';
                echo '<td>' . htmlspecialchars($mostrarUsuario) . '</td>';
                echo '<td>' . ucfirst($h['tipo']) . '</td>';
                echo '<td>Q' . number_format((float)$h['monto'], 2) . '</td>';
                echo '<td>' . $h['fecha'] . '</td>';
                echo '<td>' . $h['hora'] . '</td>';
                echo '<td>' . htmlspecialchars($h['cuenta_afectada']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6" style="text-align:center;">No hay transacciones registradas.</td></tr>';
        }
        mysqli_free_result($result_historial);
        ?>
    </tbody>
</table>

<!-- Chart.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('graficaMontos').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Depósitos', 'Retiros', 'Transferencias'],
                datasets: [{
                    label: 'Monto Total (Q)',
                    data: [<?php echo (float)$monto_depositos; ?>, <?php echo (float)$monto_retiros; ?>, <?php echo (float)$monto_transferencias; ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(34, 197, 94, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(34, 197, 94, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Monto en Quetzales (Q)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    });
</script>

<?php 
mysqli_close($conexion);
include "../includes/footer.php"; 
?>
