<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) exit;

// Carregar opções de salas e vínculos
$salas = $pdo->query("SELECT id, nome FROM salas ORDER BY nome")->fetchAll();
$lista_vinculos = ['Graduação', 'Pós-graduação', 'Docente', 'Servidor', 'Externo'];
$lista_status = ['pendente'=>'Pendente', 'confirmada'=>'Confirmada', 'cancelada'=>'Cancelada', 'concluida'=>'Concluída'];
$hoje = date('Y-m-d');

// Buscar todos os equipamentos
$stmt = $pdo->prepare("SELECT * FROM equipamentos");
$stmt->execute();
$equipamentos = $stmt->fetchAll();
?>

<form id="formNovaReserva">
  <div class="mb-3">
    <label class="form-label">Nome completo</label>
    <input type="text" class="form-control" name="nome_completo" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Número USP</label>
    <input type="text" class="form-control" name="numero_usp" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Vínculo</label>
    <select name="vinculo" class="form-select" required>
      <?php foreach ($lista_vinculos as $vin): ?>
        <option value="<?=$vin?>"><?=$vin?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Sala</label>
    <select name="sala_id" class="form-select" required>
      <?php foreach ($salas as $sala): ?>
        <option value="<?=$sala['id']?>"><?=$sala['nome']?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Quantidade de pessoas</label>
    <input type="number" class="form-control" name="quantidade_pessoas" value="1" min="1" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Data da reserva</label>
    <input type="date" class="form-control" name="data_reserva" value="<?=$hoje?>" required>
  </div>
  <div class="mb-3 row">
    <div class="col">
      <label class="form-label">Hora de entrada</label>
      <input type="time" class="form-control" name="hora_entrada" required>
    </div>
    <div class="col">
      <label class="form-label">Hora de saída</label>
      <input type="time" class="form-control" name="hora_saida" required>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select" required>
      <?php foreach ($lista_status as $key=>$label): ?>
        <option value="<?=$key?>"><?=$label?></option>
      <?php endforeach; ?>
    </select>
  </div>

    <div class="mb-3">
    <label class="form-label">Necessidade de equipamentos</label> 
    <div class="row">
    <?php foreach ($equipamentos as $equipamento): ?>
    <div class="col-md-3 mb-2">
    <div class="form-check">
    <input class="form-check-input" type="checkbox" name="equipamentos[]" value="<?php echo $equipamento['id']; ?>" id="equip_<?php echo $equipamento['id']; ?>">
    <label class="form-check-label" for="equip_<?php echo $equipamento['id']; ?>">
    <?php echo $equipamento['nome']; ?>
    </label>
    </div>
    </div>
    <?php endforeach; ?>
    </div>
    </div>

  <div class="mb-3">
    <label class="form-label">Data de criação</label>
    <input type="datetime-local" class="form-control" name="data_criacao"
      value="<?=date('Y-m-d\TH:i')?>" required>
  </div>
  <div class="text-end">
    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar reserva</button>
  </div>
  <div id="msgNovaReserva" class="mt-3"></div>
</form>

<script>
document.getElementById('formNovaReserva').onsubmit = function(e){
  e.preventDefault();
  const data = new FormData(this);
  fetch('salvar_nova_reserva.php', {
    method: 'POST',
    body: data
  })
  .then(r => r.text())
  .then(resp => {
    if(resp.trim() === 'ok') {
      document.getElementById('msgNovaReserva').innerHTML = '<div class="alert alert-success">Reserva cadastrada com sucesso!</div>';
      setTimeout(()=>{ location.reload(); }, 1200);
    } else {
      document.getElementById('msgNovaReserva').innerHTML = '<div class="alert alert-danger">'+resp+'</div>';
    }
  })
  .catch(() => {
    document.getElementById('msgNovaReserva').innerHTML = '<div class="alert alert-danger">Erro ao cadastrar. Tente novamente.</div>';
  });
}
</script>