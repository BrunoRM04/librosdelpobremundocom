<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
</head>
<body>

<h2>Panel Administración</h2>

<form action="procesar_login.php" method="POST">
    <input type="text" name="usuario" placeholder="Usuario" required>
    <br><br>
    <input type="password" name="password" placeholder="Contraseña" required>
    <br><br>
    <button type="submit">Ingresar</button>
</form>

</body>
</html>
