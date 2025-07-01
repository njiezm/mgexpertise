<?php
// db_connect.php
$host = 'localhost';
$db   = 'planning_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}


/*<?php
// db_connect.php

$host = 'db.be-mons1.bengt.wasmernet.com';
$port = 3306;
$db   = 'planning_db';
$user = '2f2a6b69765c80007a67f8b878a3';
$pass = '06862f2a-6b69-7be6-8000-4aed3a0000cb';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "✅ Connexion réussie à la base de données.";
} catch (\PDOException $e) {
    // En production, mieux vaut ne pas afficher le mot de l’erreur brute
    echo "❌ Erreur de connexion à la base de données.";
    // Pour déboguer temporairement :
    // echo "Erreur : " . $e->getMessage();
    exit;
}
?>
*/
