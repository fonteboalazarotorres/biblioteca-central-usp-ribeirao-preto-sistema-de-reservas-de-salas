<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) exit;

$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';

$sql = "
    SELECT r.id, r.nome_completo, r.numero_usp, r.vinculo, r.data_reserva, r.hora_entrada, r.hora_saida, r.status, s.nome as sala_nome
    FROM reservas r
    JOIN salas s ON r.sala_id = s.id
    WHERE r.data_reserva >= CURRENT_DATE()
";

// Se tiver termo, acrescenta o filtro (pesquisa parcial por nome ou USP)
$params = [];
if ($termo !== '') {
    $sql .= " AND (r.numero_usp LIKE ? OR r.nome_completo LIKE ?)";
    $termoBusca = "%$termo%";
    $params[] = $termoBusca;
    $params[] = $termoBusca;
}

$sql .= " ORDER BY r.data_reserva ASC, r.hora_entrada ASC LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($reservas);
