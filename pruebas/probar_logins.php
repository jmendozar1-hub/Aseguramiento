<?php

include_once '../includes/conexion.php';
header('Content-Type: text/plain; charset=utf-8');

function out($t){ echo $t."\n"; }

out('== Probar autenticar_admin y autenticar_cajero ==');

$casos = [
  ['sp' => 'autenticar_admin',  'u' => 'admin@banco.com',   'p' => 'admin123'],
  ['sp' => 'autenticar_cajero', 'u' => 'cajero1@banco.com', 'p' => 'cajero123'],
];

foreach ($casos as $c) {
    out("\n-- Probando ".$c['sp']);
    $stmt = mysqli_prepare($conexion, "CALL {$c['sp']}(?, ?)");
    mysqli_stmt_bind_param($stmt, 'ss', $c['u'], $c['p']);
    if (!mysqli_stmt_execute($stmt)) {
        out('Error ejecutar '.$c['sp'].': '.mysqli_error($conexion));
        continue;
    }
    $fila = null;
    $res = @mysqli_stmt_get_result($stmt);
    $hash = '';
    if ($res) {
        $fila = mysqli_fetch_assoc($res);
        $hash = $fila['contrasena'] ?? '';
    } else {
        $id = $nom = $mail = $con = null;
        mysqli_stmt_bind_result($stmt, $id, $nom, $mail, $con);
        if (mysqli_stmt_fetch($stmt)) {
            $fila = ['id_usuario'=>$id,'nombre_completo'=>$nom,'correo'=>$mail];
            $hash = $con ?? '';
        }
    }
    mysqli_stmt_close($stmt);
    if ($fila && !empty($hash) && (password_verify($c['p'], $hash) || hash_equals($hash, $c['p']))) {
        out('OK -> id_usuario='.$fila['id_usuario'].' nombre='.$fila['nombre_completo']);
    } else {
        out('FAIL sin resultados');
    }
}

mysqli_close($conexion);
out("\n== Fin ==\n");
?>
