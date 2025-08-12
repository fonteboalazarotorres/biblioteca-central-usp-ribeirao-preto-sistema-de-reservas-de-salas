<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) exit;

// 1. Campos obrigatórios
$obrigatorios = [
    'nome_completo', 'numero_usp', 'vinculo', 'sala_id', 'quantidade_pessoas',
    'data_reserva', 'hora_entrada', 'hora_saida', 'status', 'data_criacao'
];
foreach ($obrigatorios as $campo) {
    if (empty($_POST[$campo])) {
        echo "Preencha todos os campos obrigatórios.";
        exit;
    }
}

try {
    // 2. Validação numérica extra
    $sala_id = intval($_POST['sala_id']);
    $quantidade_pessoas = intval($_POST['quantidade_pessoas']);
    if ($sala_id < 1 || $quantidade_pessoas < 1) {
        echo "Sala e quantidade de pessoas inválidas.";
        exit;
    }

    // Inicie transação para garantir consistência
    $pdo->beginTransaction();

    // 3. Verifica conflito de horários: NÃO permite sobreposição
    $sql_conf = "
        SELECT COUNT(*) FROM reservas 
        WHERE sala_id=? AND data_reserva=? 
            AND status IN ('confirmada','pendente')
            AND (
                (hora_entrada < ? AND hora_saida > ?)
                OR (hora_entrada < ? AND hora_saida > ?)
                OR (hora_entrada >= ? AND hora_saida <= ?)
            )
    ";
    $stmt = $pdo->prepare($sql_conf);
    $stmt->execute([
        $sala_id,
        $_POST['data_reserva'],
        $_POST['hora_saida'], $_POST['hora_entrada'],
        $_POST['hora_entrada'], $_POST['hora_saida'],
        $_POST['hora_entrada'], $_POST['hora_saida']
    ]);
    if ($stmt->fetchColumn() > 0) {
        echo "Já existe reserva nesta sala e horário.";
        $pdo->rollBack();
        exit;
    }

    // 4. Inserir a reserva
    $stmt = $pdo->prepare("
        INSERT INTO reservas (
            nome_completo, numero_usp, vinculo, sala_id, quantidade_pessoas,
            data_reserva, hora_entrada, hora_saida, status, data_criacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ok = $stmt->execute([
        $_POST['nome_completo'],
        $_POST['numero_usp'],
        $_POST['vinculo'],
        $sala_id,
        $quantidade_pessoas,
        $_POST['data_reserva'],
        $_POST['hora_entrada'],
        $_POST['hora_saida'],
        $_POST['status'],
        $_POST['data_criacao'],
    ]);
    if (!$ok) {
        echo "Erro ao cadastrar reserva.";
        $pdo->rollBack();
        exit;
    }
    $reserva_id = $pdo->lastInsertId();

    // 5. Associar os equipamentos (se houver)
    if (!empty($_POST['equipamentos']) && is_array($_POST['equipamentos'])) {
        $stmtEq = $pdo->prepare("INSERT INTO reserva_equipamentos (reserva_id, equipamento_id) VALUES (?, ?)");
        foreach ($_POST['equipamentos'] as $equip_id) {
            $stmtEq->execute([$reserva_id, intval($equip_id)]);
        }
    }

    $pdo->commit(); // sucesso total!
    echo "ok";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Nova reserva erro: ' . $e->getMessage());
    echo "Erro ao processar a reserva. Tente novamente.";
    exit;
}
?>