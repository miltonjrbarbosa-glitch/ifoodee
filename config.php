<?php
// ARQUIVO: config.php

// Prefer environment variables, fall back to defaults for local dev
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'classificados_adultos';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';     // No XAMPP a senha padrão é vazia

// Use utf8mb4 for better Unicode support
$dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Em ambientes de produção, evite expor a mensagem completa.
    if (getenv('APP_DEBUG') === 'true') {
        die("Erro na conexão com o banco: " . $e->getMessage());
    }
    die("Erro na conexão com o banco. Verifique as configurações.");
}
?>