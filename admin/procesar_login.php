<?php
session_start();
include("conexion.php");

$usuario = $_POST['usuario'];
$password = $_POST['password'];

$sql = "SELECT * FROM admin 
        WHERE usuario='$usuario' 
        AND password='$password'";

$resultado = mysqli_query($conn, $sql);

if (mysqli_num_rows($resultado) == 1) {
    $_SESSION['admin'] = $usuario;
    header("Location: admin.php");
} else {
    echo "Login incorrecto";
}
