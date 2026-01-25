<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>

<h2>Bienvenido Administrador</h2>

<a href="logout.php">Cerrar sesión</a>

<hr>

<h3>Importar libros CSV</h3>

<form action="importar_csv.php" method="POST" enctype="multipart/form-data">
    <input type="file" name="archivo" accept=".csv" required>
    <br><br>
    <button type="submit">Subir CSV</button>
</form>
