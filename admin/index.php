<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>

<h1>Panel Admin</h1>

<p>Bienvenido: <?php echo $_SESSION['admin_user']; ?></p>

<a href="logout.php">Cerrar sesión</a>