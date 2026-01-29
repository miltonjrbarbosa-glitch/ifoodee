<?php
// ARQUIVO: config.php

$host = 'localhost';
$db   = 'classificados_adultos';
$user = 'root'; 
$pass = '';     // No XAMPP a senha padrão é vazia

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco: " . $e->getMessage());
}
?>