<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) exit;

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo "ID inválido";
    exit;
}

// Validação mínima dos campos, de preferência completa no front-end

$stmt = $pdo->prepare("UPDATE reservas SET nome_completo=?, numero_usp=?, vinculo=?, sala_id=?, quantidade_pessoas=?, data_reserva=?, hora_entrada=?, hora_saida=?, status=?, data_criacao=? WHERE id=?");

$ok = $stmt->execute([
    $_POST['nome_completo'] ?? '',
    $_POST['numero_usp'] ?? '',
    $_POST['vinculo'] ?? '',
    $_POST['sala_id'] ?? 0,
    $_POST['quantidade_pessoas'] ?? 1,
    $_POST['data_reserva'] ?? '',
    $_POST['hora_entrada'] ?? '',
    $_POST['hora_saida'] ?? '',
    $_POST['status'] ?? '',
    $_POST['data_criacao'] ?? '',
    $id
]);

if (!$ok) {
    echo "Erro ao atualizar reserva";
    exit;
}

// Atualizar equipamentos
$stmt = $pdo->prepare("DELETE FROM reserva_equipamentos WHERE reserva_id = ?");
$stmt->execute([$id]);

if (!empty($_POST['equipamentos']) && is_array($_POST['equipamentos'])) {
    $stmt = $pdo->prepare("INSERT INTO reserva_equipamentos (reserva_id, equipamento_id) VALUES (?, ?)");
    foreach ($_POST['equipamentos'] as $equip_id) {
        $stmt->execute([$id, intval($equip_id)]);
    }
}

echo "ok";
