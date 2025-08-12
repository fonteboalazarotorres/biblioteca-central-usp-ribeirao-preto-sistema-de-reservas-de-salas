<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    exit('Acesso negado.');
}

// Recebe e valida os dados
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$capacidade = isset($_POST['capacidade']) ? intval($_POST['capacidade']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'disponivel';

// Validação básica
if ($id <= 0) {
    echo "ID inválido.";
    exit;
}

if ($nome === '') {
    echo "Nome é obrigatório.";
    exit;
}

if ($capacidade < 1) {
    echo "Capacidade deve ser um número positivo.";
    exit;
}

// Opcional: validar status contra os valores permitidos
// Exemplo: status só pode ser 'disponivel', 'indisponivel' (altere conforme seu sistema)
$valoresPermitidosStatus = ['disponivel', 'indisponivel', 'manutencao']; // adapte conforme seu sistema
if (!in_array($status, $valoresPermitidosStatus)) {
    echo "Status inválido.";
    exit;
}

try {
    // Atualizar a sala no banco
    $stmt = $pdo->prepare("UPDATE salas SET nome = ?, capacidade = ?, status = ? WHERE id = ?");
    $executou = $stmt->execute([$nome, $capacidade, $status, $id]);

    if ($executou) {
        echo "ok";
    } else {
        echo "Erro ao atualizar dados da sala.";
    }
} catch (PDOException $e) {
    // Em ambiente de produção, é melhor logar o erro ao invés de mostrar
    echo "Erro no banco de dados: " . $e->getMessage();
}