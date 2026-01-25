<?php
$host = "localhost";
$db   = "l0012920_pobre";
$user = "l0012920_libros";
$pass = "LibrosDelPobreMundo26";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error DB: " . $conn->connect_error);
}
?>