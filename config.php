<?php
// Configurações do banco de dados
define('DB_HOST', 'sql109.infinityfree.com');
define('DB_USER', 'if0_38667478');
define('DB_PASS', 'RkwER7GPXQA18g');
define('DB_NAME', 'if0_38667478_reserva_salas');

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("set names utf8");
} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}


?>