<?php
session_start();
require_once __DIR__ . '/../api/config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT id, usuario, password
        FROM admin
        WHERE usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$usuario]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $password === $admin['password']) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['usuario'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<form method="POST">
    <h2>Login Admin</h2>

    <input type="text" name="usuario" placeholder="Usuario" required>
    <br><br>

    <input type="password" name="password" placeholder="Contraseña" required>
    <br><br>

    <button type="submit">Ingresar</button>

    <p style="color:red;"><?php echo $error; ?></p>
</form>