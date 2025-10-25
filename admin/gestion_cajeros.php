<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
include "../includes/header.php";

if (isset($_GET['accion'], $_GET['id'])) {
    $id_cajero = (int)$_GET['id'];
    $accion = $_GET['accion'];

    if ($accion === 'bloquear') {
        $nuevo_estado = 'bloqueado';
    } elseif ($accion === 'desbloquear') {
        $nuevo_estado = 'activo';
    } else {
        set_mensaje("Acción no válida.", "error");
        redirigir("gestion_cajeros.php");
        exit();
    }

    $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET estado = ? WHERE id_usuario = ? AND rol = 'cajero'");
    mysqli_stmt_bind_param($stmt, "si", $nuevo_estado, $id_cajero);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        set_mensaje("Estado actualizado correctamente.", "info");
    } else {
        set_mensaje("No se pudo actualizar el estado. Verifica que el cajero exista.", "error");
    }
    mysqli_stmt_close($stmt);
    redirigir("gestion_cajeros.php");
    exit();
}


$cajeros = [];
$result = mysqli_query($conexion, "
    SELECT id_usuario, nombre_completo, correo, estado
    FROM usuarios
    WHERE rol = 'cajero'
    ORDER BY fecha_registro DESC
");

if ($result) {
    while ($fila = mysqli_fetch_assoc($result)) {
        $cajeros[] = $fila;
    }
    mysqli_free_result($result);
}
?>

<h2>Gestión de Cajeros</h2>

<!-- Enlace para agregar nuevo cajero -->
<a href="agregar_cajero.php" class="btn btn-primary" style="display: inline-block; margin: 1rem 0;">
    Agregar Nuevo Cajero
</a>

<!-- Listado de cajeros -->
<?php if (count($cajeros) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario (Email)</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cajeros as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['nombre_completo']); ?></td>
                    <td><?php echo htmlspecialchars($c['correo']); ?></td>
                    <td><?php echo ucfirst($c['estado']); ?></td>
                    <td>
                        <?php if ($c['estado'] === 'activo'): ?>
                            <a href="?accion=bloquear&id=<?php echo $c['id_usuario']; ?>"
                               onclick="return confirm('¿Bloquear a este cajero?');"
                               class="btn btn-danger btn-accion">Bloquear</a>
                        <?php else: ?>
                            <a href="?accion=desbloquear&id=<?php echo $c['id_usuario']; ?>"
                               onclick="return confirm('¿Desbloquear a este cajero?');"
                               class="btn btn-success btn-accion">Desbloquear</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No hay cajeros registrados.</p>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>
