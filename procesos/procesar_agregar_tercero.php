<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
include "../includes/header.php";

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_cuenta = $_POST['numero_cuenta'] ?? '';
    $alias = $_POST['alias'] ?? '';
    $monto_maximo = $_POST['monto_maximo'] ?? '0';
    $max_diarias = $_POST['max_diarias'] ?? '3';

    $errores = [];

    if (empty($numero_cuenta)) $errores[] = "El número de cuenta es obligatorio.";
    if (empty($alias)) $errores[] = "El alias es obligatorio.";
    if (!is_numeric($monto_maximo) || $monto_maximo <= 0) $errores[] = "El monto máximo debe ser mayor que 0.";
    if (!is_numeric($max_diarias) || $max_diarias < 1 || $max_diarias > 10) $errores[] = "Las transacciones diarias deben ser entre 1 y 10.";

    if (empty($errores)) {
        // Validar que la cuenta destino exista y NO sea del mismo usuario
        $stmt_check = mysqli_prepare($conexion, "
            SELECT cb.id_cuenta 
            FROM cuentas_bancarias cb 
            WHERE cb.numero_cuenta = ? 
              AND (cb.id_usuario_cliente != ? OR cb.id_usuario_cliente IS NULL)
              AND cb.id_usuario_cliente IS NOT NULL
        ");
        mysqli_stmt_bind_param($stmt_check, "si", $numero_cuenta, $id_usuario);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) === 0) {
            $errores[] = "La cuenta no existe, es tuya, o no tiene usuario asignado.";
        } else {
            $fila = mysqli_fetch_assoc($result_check);
            $id_cuenta_destino = $fila['id_cuenta'];
        }
        mysqli_stmt_close($stmt_check);
    }

    if (empty($errores)) {
        // Validar que no tenga ya registrada esta cuenta como tercero
        $stmt_check_tercero = mysqli_prepare($conexion, "
            SELECT id_tercero FROM cuentas_terceros 
            WHERE id_usuario_cliente = ? AND id_cuenta_destino = ?
        ");
        mysqli_stmt_bind_param($stmt_check_tercero, "ii", $id_usuario, $id_cuenta_destino);
        mysqli_stmt_execute($stmt_check_tercero);
        $result_tercero = mysqli_stmt_get_result($stmt_check_tercero);

        if (mysqli_num_rows($result_tercero) > 0) {
            $errores[] = "Ya tienes registrada esta cuenta como tercero.";
        }
        mysqli_stmt_close($stmt_check_tercero);
    }

    if (empty($errores)) {
        $stmt = mysqli_prepare($conexion, "CALL agregar_cuenta_tercero(?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iisdi", $id_usuario, $numero_cuenta, $alias, $monto_maximo, $max_diarias);
        $resultado = mysqli_stmt_execute($stmt);

        if ($resultado) {
            set_mensaje("✅ Cuenta de tercero agregada exitosamente.", "info");
        } else {
            set_mensaje("❌ Error al agregar cuenta de tercero: " . mysqli_error($conexion), "error");
        }
        mysqli_stmt_close($stmt);
        redirigir("agregar_tercero.php");
        exit();
    } else {
        foreach ($errores as $error) {
            set_mensaje($error, "error");
        }
    }
}
?>

<h2>➕ Agregar Cuenta de Tercero</h2>
<p><em>Registra una cuenta bancaria a la que podrás transferir dinero. Debe existir y pertenecer a otro usuario.</em></p>

<div class="form-container">
    <form method="POST" action="">
        <label for="numero_cuenta">Número de Cuenta Bancaria:</label>
        <input type="text" id="numero_cuenta" name="numero_cuenta" required placeholder="Ej: 1001001002">

        <label for="alias">Alias (Nombre para identificarla):</label>
        <input type="text" id="alias" name="alias" required placeholder="Ej: Mi hermano">

        <label for="monto_maximo">Monto Máximo por Transferencia (Q):</label>
        <input type="number" id="monto_maximo" name="monto_maximo" step="0.01" min="0.01" required value="100.00">

        <label for="max_diarias">Máximo de Transferencias Diarias:</label>
        <input type="number" id="max_diarias" name="max_diarias" min="1" max="10" required value="3">

        <button type="submit">Agregar Cuenta de Tercero</button>
        <a href="index.php" class="btn-volver">← Volver al Panel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>