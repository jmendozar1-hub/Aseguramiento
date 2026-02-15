<?php
include "../includes/proteger.php";
include "../includes/conexion.php";
include "../includes/header.php";

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_cuenta = trim($_POST['numero_cuenta'] ?? '');
    $alias = trim($_POST['alias'] ?? '');
    $monto_maximo = isset($_POST['monto_maximo']) ? (float)$_POST['monto_maximo'] : 0.0;
    $max_diarias = isset($_POST['max_diarias']) ? (int)$_POST['max_diarias'] : 3;

    $errores = [];

    if ($numero_cuenta === '') $errores[] = "El número de cuenta es obligatorio.";
    if ($alias === '') $errores[] = "El alias es obligatorio.";
    if (!is_numeric($monto_maximo) || $monto_maximo <= 0) $errores[] = "El monto máximo debe ser mayor que 0.";
    if (!is_numeric($max_diarias) || $max_diarias < 1 || $max_diarias > 10) $errores[] = "Las transferencias diarias deben ser entre 1 y 10.";

    // Validar que la cuenta destino exista, tenga usuario asignado y no sea del mismo usuario
    $id_cuenta_destino = null;
    if (empty($errores)) {
        $stmt_check = mysqli_prepare($conexion, "SELECT id_cuenta, id_usuario_cliente FROM cuentas_bancarias WHERE numero_cuenta = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $numero_cuenta);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $id_cuenta_encontrada, $id_usuario_cliente);
        $tiene = mysqli_stmt_fetch($stmt_check);
        mysqli_stmt_close($stmt_check);

        if (!$tiene || empty($id_usuario_cliente)) {
            $errores[] = "La cuenta no existe o no tiene usuario asignado.";
        } elseif ((int)$id_usuario_cliente === (int)$id_usuario) {
            $errores[] = "No puedes registrar tu propia cuenta como tercero.";
        } else {
            $id_cuenta_destino = (int)$id_cuenta_encontrada;
        }
    }

    // Validar que no esté ya registrada como tercero
    if (empty($errores) && $id_cuenta_destino) {
        $stmt_check_tercero = mysqli_prepare($conexion, "SELECT COUNT(*) FROM cuentas_terceros WHERE id_usuario_cliente = ? AND id_cuenta_destino = ?");
        mysqli_stmt_bind_param($stmt_check_tercero, "ii", $id_usuario, $id_cuenta_destino);
        mysqli_stmt_execute($stmt_check_tercero);
        mysqli_stmt_bind_result($stmt_check_tercero, $cntTer);
        mysqli_stmt_fetch($stmt_check_tercero);
        mysqli_stmt_close($stmt_check_tercero);

        if ((int)$cntTer > 0) {
            $errores[] = "Ya tienes registrada esta cuenta como tercero.";
        }
    }

    if (empty($errores)) {
        // Llamar SP: recibe número de cuenta
        $stmt = mysqli_prepare($conexion, "CALL agregar_cuenta_tercero(?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iisdi", $id_usuario, $numero_cuenta, $alias, $monto_maximo, $max_diarias);
        $resultado = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($resultado) {
            set_mensaje("Cuenta de tercero agregada exitosamente.", "info");
        } else {
            set_mensaje("Error al agregar cuenta de tercero: " . mysqli_error($conexion), "error");
        }
        redirigir("agregar_tercero.php");
        exit();
    } else {
        foreach ($errores as $error) {
            set_mensaje($error, "error");
        }
    }
}
?>

<h2>Agregar Cuenta de Tercero</h2>
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

        <button type="submit" class="btn btn-primary">Agregar Cuenta de Tercero</button>
        <a href="index.php" class="btn btn-link btn-volver">Volver al Panel</a>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
