<?php
$host = 'mysql';
$dbname = 'shopdb';
$user = 'shopuser';
$pass = 'shop123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    $pdo = null;
}
?>