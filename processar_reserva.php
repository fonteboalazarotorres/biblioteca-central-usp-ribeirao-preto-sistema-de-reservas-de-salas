<?php
require_once 'config.php';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: sucesso.php');
exit;
}

// Verificar se todos os campos obrigatórios foram preenchidos
$camposObrigatorios = ['nome_completo', 'numero_usp', 'vinculo', 'data_reserva', 'sala_id', 'quantidade_pessoas', 'hora_entrada', 'hora_saida'];
foreach ($camposObrigatorios as $campo) {
    if (empty($_POST[$campo])) {
        $_SESSION['mensagem'] = "Erro: Todos os campos são obrigatórios.";
        header('Location: index.php');
        exit;
    }
}

// Capturar os dados do formulário
$nomeCompleto = $_POST['nome_completo'];
$numeroUsp = $_POST['numero_usp'];
$vinculo = $_POST['vinculo'];
$dataReserva = $_POST['data_reserva'];
$salaId = $_POST['sala_id'];
$qtdPessoas = $_POST['quantidade_pessoas'];
$horaEntrada = $_POST['hora_entrada'];
$horaSaida = $_POST['hora_saida'];
$equipamentos = isset($_POST['equipamentos']) ? $_POST['equipamentos'] : [];

// Validar se a data não é anterior ao dia atual
if (strtotime($dataReserva) < strtotime(date('Y-m-d'))) {
    $_SESSION['mensagem'] = "Erro: A data da reserva não pode ser anterior ao dia atual.";
    header('Location: index.php');
    exit;
}

// Validar se a hora de saída é posterior à hora de entrada
if ($horaEntrada >= $horaSaida) {
    $_SESSION['mensagem'] = "Erro: A hora de saída deve ser posterior à hora de entrada.";
    header('Location: index.php');
    exit;
}

try {
    // Verificar se a sala está disponível no horário solicitado
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM reservas 
        WHERE data_reserva = :data_reserva 
        AND sala_id = :sala_id 
        AND status IN ('pendente', 'confirmada') 
        AND (
            (hora_entrada <= :hora_saida AND hora_saida > :hora_entrada) OR
            (hora_entrada < :hora_saida AND hora_saida >= :hora_entrada) OR
            (hora_entrada >= :hora_entrada AND hora_saida <= :hora_saida)
        )
    ");
    
    $stmt->bindParam(':data_reserva', $dataReserva);
    $stmt->bindParam(':sala_id', $salaId);
    $stmt->bindParam(':hora_entrada', $horaEntrada);
    $stmt->bindParam(':hora_saida', $horaSaida);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['mensagem'] = "Erro: A sala já está reservada para o horário solicitado.";
        header('Location: index.php');
        exit;
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Inserir a reserva
    $stmt = $pdo->prepare("
        INSERT INTO reservas (nome_completo, numero_usp, vinculo, sala_id, quantidade_pessoas, data_reserva, hora_entrada, hora_saida)
        VALUES (:nome_completo, :numero_usp, :vinculo, :sala_id, :quantidade_pessoas, :data_reserva, :hora_entrada, :hora_saida)
    ");
    
    $stmt->bindParam(':nome_completo', $nomeCompleto);
    $stmt->bindParam(':numero_usp', $numeroUsp);
    $stmt->bindParam(':vinculo', $vinculo);
    $stmt->bindParam(':sala_id', $salaId);
    $stmt->bindParam(':quantidade_pessoas', $qtdPessoas);
    $stmt->bindParam(':data_reserva', $dataReserva);
    $stmt->bindParam(':hora_entrada', $horaEntrada);
    $stmt->bindParam(':hora_saida', $horaSaida);
    $stmt->execute();
    
    $reservaId = $pdo->lastInsertId();
    
    // Inserir os equipamentos selecionados
    if (!empty($equipamentos)) {
        $stmt = $pdo->prepare("INSERT INTO reserva_equipamentos (reserva_id, equipamento_id) VALUES (:reserva_id, :equipamento_id)");
        
        foreach ($equipamentos as $equipamentoId) {
            $stmt->bindParam(':reserva_id', $reservaId);
            $stmt->bindParam(':equipamento_id', $equipamentoId);
            $stmt->execute();
        }
    }
    
    // Buscar informações da sala
    $stmt = $pdo->prepare("SELECT nome FROM salas WHERE id = :id");
    $stmt->bindParam(':id', $salaId);
    $stmt->execute();
    $sala = $stmt->fetch();
    
    // Buscar nomes dos equipamentos
    $equipamentosNomes = [];
    if (!empty($equipamentos)) {
        $placeholders = implode(',', array_fill(0, count($equipamentos), '?'));
        $stmt = $pdo->prepare("SELECT nome FROM equipamentos WHERE id IN ($placeholders)");
        $stmt->execute($equipamentos);
        $equipamentosNomes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Confirmar transação
    $pdo->commit();
    
    $_SESSION['mensagem'] = "Reserva realizada com sucesso!";
    header('Location: index.php');
    
} catch (PDOException $e) {
    // Reverter transação em caso de erro
    $pdo->rollBack();
    
    $_SESSION['mensagem'] = "Erro ao realizar a reserva: " . $e->getMessage();
    header('Location: index.php');
}
?>