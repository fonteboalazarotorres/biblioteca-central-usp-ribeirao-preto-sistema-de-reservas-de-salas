<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) exit;

$id = $_GET['id'] ?? 0;
$id = intval($id);
if (!$id) {
    echo '<div class="alert alert-warning">ID da reserva inválido.</div>';
    exit;
}

// Buscar dados da reserva e nome da sala
$stmt = $pdo->prepare("
    SELECT r.*, s.nome AS sala_nome
    FROM reservas r
    JOIN salas s ON r.sala_id = s.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$reserva = $stmt->fetch();

if (!$reserva) {
    echo '<div class="alert alert-warning">Reserva não encontrada.</div>';
    exit;
}

// Buscar equipamentos associados a essa reserva
$stmtEq = $pdo->prepare("
    SELECT e.nome 
    FROM equipamentos e
    JOIN reserva_equipamentos re ON re.equipamento_id = e.id
    WHERE re.reserva_id = ?
");
$stmtEq->execute([$id]);
$equipamentos = $stmtEq->fetchAll(PDO::FETCH_COLUMN);

?>

<div class="container-fluid">
  <div class="mb-3">
    <span class="badge bg-primary"><i class="fas fa-door-open"></i> <?=htmlspecialchars($reserva['sala_nome'])?></span>
    <span class="badge bg-info ms-2"><i class="fas fa-user"></i> <?=htmlspecialchars($reserva['nome_completo'])?></span>
    <span class="badge bg-secondary ms-2"><?=htmlspecialchars($reserva['vinculo'])?></span>
  </div>
  <table class="table mb-4">
    <tr><th>Data</th><td><?=date('d/m/Y', strtotime($reserva['data_reserva']))?></td></tr>
    <tr><th>Entrada</th><td><?=substr($reserva['hora_entrada'], 0, 5)?></td></tr>
    <tr><th>Saída</th><td><?=substr($reserva['hora_saida'], 0, 5)?></td></tr>
    <tr><th>Status</th><td><span class="badge bg-success"><?=ucfirst($reserva['status'])?></span></td></tr>
    <tr><th>Quantidade de Pessoas</th><td><?=intval($reserva['quantidade_pessoas'])?></td></tr>
    <tr><th>Equipamentos</th>
      <td>
        <?php if (count($equipamentos) > 0): ?>
          <ul class="mb-0">
            <?php foreach ($equipamentos as $eq): ?>
              <li><?=htmlspecialchars($eq)?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <em>Não há equipamentos selecionados</em>
        <?php endif; ?>
      </td>
    </tr>
  </table>
  <div class="text-end">
    <button class="btn btn-secondary me-2" data-bs-dismiss="modal">Fechar</button>
    
  </div>
</div>
