<?php
require_once 'config.php';
header('Content-Type: application/json');

// Verificar se todos os campos necessários foram enviados
if (empty($_POST['data']) || empty($_POST['hora_entrada']) || empty($_POST['hora_saida']) || empty($_POST['quantidade_pessoas'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Todos os campos são obrigatórios.'
    ]);
    exit;
}

$data = $_POST['data'];
$horaEntrada = $_POST['hora_entrada'];
$horaSaida = $_POST['hora_saida'];
$qtdPessoas = (int)$_POST['quantidade_pessoas'];

// Validar formato da data e hora
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !preg_match('/^\d{2}:\d{2}$/', $horaEntrada) || !preg_match('/^\d{2}:\d{2}$/', $horaSaida)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Formato de data ou hora inválido.'
    ]);
    exit;
}

// Validar se a hora de saída é posterior à hora de entrada
if ($horaEntrada >= $horaSaida) {
    echo json_encode([
        'status' => 'error',
        'message' => 'A hora de saída deve ser posterior à hora de entrada.'
    ]);
    exit;
}

try {
    // Buscar salas que comportam a quantidade de pessoas
    $stmt = $pdo->prepare("
        SELECT id, nome, capacidade, descricao 
        FROM salas 
        WHERE capacidade >= :qtd_pessoas 
        AND status = 'disponivel'
        ORDER BY capacidade ASC
    ");
    $stmt->bindParam(':qtd_pessoas', $qtdPessoas, PDO::PARAM_INT);
    $stmt->execute();
    $salas = $stmt->fetchAll();
    
    // Se não houver salas com capacidade suficiente
    if (empty($salas)) {
        echo json_encode([
            'status' => 'success',
            'salas' => []
        ]);
        exit;
    }
    
    // IDs das salas que comportam a quantidade de pessoas
    $salaIds = array_column($salas, 'id');
    $placeholders = implode(',', array_fill(0, count($salaIds), '?'));
    
    // Verificar quais salas já estão reservadas no horário solicitado
    $stmt = $pdo->prepare("
        SELECT sala_id 
        FROM reservas 
        WHERE data_reserva = ? 
        AND status IN ('pendente', 'confirmada') 
        AND (
            (hora_entrada <= ? AND hora_saida > ?) OR
            (hora_entrada < ? AND hora_saida >= ?) OR
            (hora_entrada >= ? AND hora_saida <= ?)
        )
        AND sala_id IN ($placeholders)
    ");
    
    $params = [$data, $horaSaida, $horaEntrada, $horaSaida, $horaEntrada, $horaEntrada, $horaSaida];
    foreach ($salaIds as $id) {
        $params[] = $id;
    }
    
    $stmt->execute($params);
    $salasReservadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Filtrar salas disponíveis
    $salasDisponiveis = array_filter($salas, function($sala) use ($salasReservadas) {
        return !in_array($sala['id'], $salasReservadas);
    });
    
    echo json_encode([
        'status' => 'success',
        'salas' => array_values($salasDisponiveis)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao verificar disponibilidade: ' . $e->getMessage()
    ]);
}
?>