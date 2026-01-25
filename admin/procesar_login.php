<?php
session_start([
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

include("conexion.php");

$usuario  = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';

if ($usuario == '' || $password == '') {
    die("Datos incompletos");
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM admin WHERE usuario=? AND password=?"
);

mysqli_stmt_bind_param($stmt, "ss", $usuario, $password);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) == 1) {

    $_SESSION['admin'] = $usuario;
    header("Location: admin.php");
    exit();
} else {

    echo "Login incorrecto";
}
