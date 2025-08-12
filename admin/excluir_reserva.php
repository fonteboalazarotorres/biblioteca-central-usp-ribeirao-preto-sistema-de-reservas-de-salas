<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) header('Location: login.php');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM reservas WHERE id=?");
    $stmt->execute([$id]);
}
header('Location: reservas.php');
exit;