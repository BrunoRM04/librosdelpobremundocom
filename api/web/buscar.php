<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require __DIR__ . '/../_shared/config.php';

header("Content-Type: application/json; charset=utf-8");

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

/*
-------------------------------------------------
 1) Normalizamos espacios y separamos palabras
-------------------------------------------------
*/
$q = preg_replace('/\s+/', ' ', $q);
$terms = explode(' ', $q);

/*
-------------------------------------------------
 2) Construimos dinámicamente WHERE + SCORE
-------------------------------------------------
*/
$whereParts = [];
$params = [];
$scoreParts = [];

$i = 0;
foreach ($terms as $term) {

    if (strlen($term) < 2) continue;

    $key = ":t$i";
    $like = "%" . $term . "%";

    // Buscar en TODOS los campos relevantes
    $whereParts[] = "
        titulo   LIKE $key OR
        autor    LIKE $key OR
        editorial LIKE $key OR
        isbn     LIKE $key
    ";

    // Ponderación de relevancia
    $scoreParts[] = "
        (CASE WHEN titulo LIKE $key THEN 10 ELSE 0 END) +
        (CASE WHEN autor LIKE $key THEN 6 ELSE 0 END) +
        (CASE WHEN isbn LIKE $key THEN 8 ELSE 0 END) +
        (CASE WHEN editorial LIKE $key THEN 4 ELSE 0 END)
    ";

    $params[$key] = $like;
    $i++;
}

if (!$whereParts) {
    echo json_encode([]);
    exit;
}

$whereSQL = implode(" AND ", $whereParts);
$scoreSQL = implode(" + ", $scoreParts);

/*
-------------------------------------------------
 3) Query final con ranking
-------------------------------------------------
*/
$sql = "
SELECT 
    id,
    titulo,
    autor,
    imagen,
    ($scoreSQL) AS relevancia
FROM libros
WHERE $whereSQL
ORDER BY relevancia DESC, titulo ASC
LIMIT 30
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($resultados);