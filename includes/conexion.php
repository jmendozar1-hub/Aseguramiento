<?php

$host = "localhost";        
$usuario_db = "root";       
$clave_db = "";             
$nombre_bd = "banca_linea"; 

$conexion = mysqli_connect($host, $usuario_db, $clave_db, $nombre_bd);

if (!$conexion) {
    die("<strong>Error crítico:</strong> No se pudo conectar a la base de datos. " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

$appTz = getenv('APP_TZ') ?: 'America/Guatemala';
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($appTz);
}
$offset = date('P'); 
@mysqli_query($conexion, "SET time_zone = '$offset'");
?>
