<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $numero_usp = trim($_POST['numero_usp'] ?? '');
    $vinculo = trim($_POST['vinculo'] ?? '');
    $sala_id = intval($_POST['sala_id'] ?? 0);
    $quantidade_pessoas = intval($_POST['quantidade_pessoas'] ?? 0);
    $data_reserva = $_POST['data_reserva'] ?? '';
    $hora_entrada = $_POST['hora_entrada'] ?? '';
    $hora_saida = $_POST['hora_saida'] ?? '';
    $equipamentos = $_POST['equipamentos'] ?? []; // array de ids
    $status = 'confirmada';
    $data_criacao = date('Y-m-d H:i:s');

    if ($nome_completo === '' || $numero_usp === '' || $vinculo === '' || 
        $sala_id <= 0 || $quantidade_pessoas <= 0 || $data_reserva === '' || 
        $hora_entrada === '' || $hora_saida === '') {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif ($hora_entrada >= $hora_saida) {
        $erro = "Hora de entrada deve ser menor que hora de saída.";
    } else {
        $stmtConflito = $pdo->prepare("
            SELECT COUNT(*) FROM reservas 
            WHERE sala_id = ? AND data_reserva = ? AND status IN ('pendente','confirmada')
            AND (
                (hora_entrada <= ? AND hora_saida > ?) OR
                (hora_entrada < ? AND hora_saida >= ?) OR
                (hora_entrada >= ? AND hora_saida <= ?)
            )
        ");
        $stmtConflito->execute([
            $sala_id, $data_reserva,
            $hora_saida, $hora_entrada,
            $hora_entrada, $hora_saida,
            $hora_entrada, $hora_saida
        ]);
        $conflito = $stmtConflito->fetchColumn();

        if ($conflito > 0) {
            $erro = "Já existe reserva para esta sala neste horário.";
        } else {
            $stmtInsert = $pdo->prepare("
                INSERT INTO reservas 
                (nome_completo, numero_usp, vinculo, sala_id, quantidade_pessoas, data_reserva, hora_entrada, hora_saida, status, data_criacao) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ok = $stmtInsert->execute([
                $nome_completo, $numero_usp, $vinculo, $sala_id, $quantidade_pessoas, 
                $data_reserva, $hora_entrada, $hora_saida, $status, $data_criacao
            ]);
            if ($ok) {
                $idReserva = $pdo->lastInsertId();
                if (!empty($equipamentos) && is_array($equipamentos)) {
                    $stmtEq = $pdo->prepare("INSERT INTO reserva_equipamentos (reserva_id, equipamento_id) VALUES (?, ?)");
                    foreach ($equipamentos as $eq_id) {
                        $stmtEq->execute([$idReserva, intval($eq_id)]);
                    }
                }
                $sucesso = "Reserva criada com sucesso!";
                // Limpar campos
                $_POST = [];
            } else {
                $erro = "Erro ao salvar a reserva. Tente novamente.";
            }
        }
    }
}

$salas = $pdo->query("SELECT id, nome FROM salas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$equipamentos = $pdo->query("SELECT id, nome FROM equipamentos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$lista_vinculos = ['Graduação', 'Pós-graduação', 'Docente', 'Servidor', 'Externo'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Adicionar Reserva</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:600px;">

  <h2 class="mb-4 text-primary fw-bold"><i class="fas fa-calendar-plus me-2"></i>Adicionar Nova Reserva</h2>

  <p>Você pode retornar ao <a href="login.php" class="button">Início</a>.</p>

  <?php if($erro): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($erro)?></div>
  <?php endif; ?>
  <?php if($sucesso): ?>
    <div class="alert alert-success"><?=htmlspecialchars($sucesso)?></div>
  <?php endif; ?>

  <form method="post" novalidate>

    <div class="mb-3">
      <label class="form-label fw-semibold" for="nome_completo">Nome completo <span class="text-danger">*</span></label>
      <input type="text" id="nome_completo" name="nome_completo" class="form-control" required
        value="<?=htmlspecialchars($_POST['nome_completo'] ?? '')?>" />
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold" for="numero_usp">Número USP <span class="text-danger">*</span></label>
      <input type="text" id="numero_usp" name="numero_usp" class="form-control" required
        value="<?=htmlspecialchars($_POST['numero_usp'] ?? '')?>" />
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold" for="vinculo">Vínculo <span class="text-danger">*</span></label>
      <select id="vinculo" name="vinculo" class="form-select" required>
        <option value="">-- selecione --</option>
        <?php foreach ($lista_vinculos as $vin): ?>
          <option value="<?=htmlspecialchars($vin)?>" <?=((($_POST['vinculo'] ?? '') === $vin) ? 'selected' : '')?>><?=htmlspecialchars($vin)?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold" for="sala_id">Sala <span class="text-danger">*</span></label>
      <select id="sala_id" name="sala_id" class="form-select" required>
        <option value="">-- selecione --</option>
        <?php foreach ($salas as $s): ?>
          <option value="<?=$s['id']?>" <?=((intval($_POST['sala_id'] ?? 0) === intval($s['id'])) ? 'selected' : '')?>><?=htmlspecialchars($s['nome'])?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold" for="quantidade_pessoas">Quantidade de pessoas <span class="text-danger">*</span></label>
      <input type="number" id="quantidade_pessoas" name="quantidade_pessoas" min="1" class="form-control" required
        value="<?=htmlspecialchars($_POST['quantidade_pessoas'] ?? '1')?>" />
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold" for="data_reserva">Data da reserva <span class="text-danger">*</span></label>
      <input type="date" id="data_reserva" name="data_reserva" class="form-control" required
        value="<?=htmlspecialchars($_POST['data_reserva'] ?? '')?>" />
    </div>

    <div class="mb-3 row gx-3">
      <div class="col">
        <label class="form-label fw-semibold" for="hora_entrada">Hora de entrada <span class="text-danger">*</span></label>
        <input type="time" id="hora_entrada" name="hora_entrada" class="form-control" required
          value="<?=htmlspecialchars($_POST['hora_entrada'] ?? '')?>" />
      </div>
      <div class="col">
        <label class="form-label fw-semibold" for="hora_saida">Hora de saída <span class="text-danger">*</span></label>
        <input type="time" id="hora_saida" name="hora_saida" class="form-control" required
          value="<?=htmlspecialchars($_POST['hora_saida'] ?? '')?>" />
      </div>
    </div>

    <fieldset class="mb-3">
      <legend class="fw-semibold mb-2">Equipamentos (opcional):</legend>
      <div class="row row-cols-auto g-2">
        <?php foreach($equipamentos as $eq): ?>
        <div class="col">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="equip_<?=$eq['id']?>" name="equipamentos[]" 
              value="<?=$eq['id']?>"
              <?= (isset($_POST['equipamentos']) && in_array($eq['id'], $_POST['equipamentos'])) ? 'checked' : '' ?>
              >
            <label class="form-check-label" for="equip_<?=$eq['id']?>"><?=htmlspecialchars($eq['nome'])?></label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <button type="submit" class="btn btn-primary w-100">
      <i class="fas fa-save me-1"></i> Salvar Reserva
    </button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
