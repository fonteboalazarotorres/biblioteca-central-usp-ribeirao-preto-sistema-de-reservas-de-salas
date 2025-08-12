<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) exit;

// Obtém ID da sala
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Busca a sala no banco
$stmt = $pdo->prepare('SELECT * FROM salas WHERE id=?');
$stmt->execute([$id]);
$sala = $stmt->fetch();
if (!$sala) {
    echo '<div class="alert alert-warning">Sala não encontrada.</div>';
    exit;
}

// Status possíveis
$lista_status = [
    'disponivel' => 'Disponível',
    'manutencao' => 'Em manutenção',
    'inativa'    => 'Inativa'
];
?>

<style>
/* Fonte e layout básico */
body, input, select, button {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Container e espaçamentos do formulário */
#formEditarSala {
    max-width: 450px;
    margin: 0 auto;
    background: #fff;
    padding: 20px 24px;
    border-radius: 8px;
    box-shadow: 0 6px 15px rgb(0 0 0 / 0.1);
}

/* Labels */
#formEditarSala label.form-label {
    font-weight: 600;
    color: #212529;
    margin-bottom: 6px;
    display: block;
}

/* Inputs e selects estilizados */
#formEditarSala input.form-control,
#formEditarSala select.form-select {
    width: 100%;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1.8px solid #d1d5db; /* cinza claro */
    font-size: 1rem;
    transition: border-color 0.25s ease;
    box-sizing: border-box;
}

#formEditarSala input.form-control:focus,
#formEditarSala select.form-select:focus {
    outline: none;
    border-color: #2563eb; /* azul vivo */
    box-shadow: 0 0 6px rgba(37, 99, 235, 0.4);
}

/* Botões */
#formEditarSala .btn {
    font-weight: 600;
    border-radius: 6px;
    padding: 8px 20px;
    font-size: 1rem;
    transition: background-color 0.25s ease;
}

#formEditarSala .btn-primary {
    background-color: #2563eb;
    border: none;
    color: #fff;
}

#formEditarSala .btn-primary:hover,
#formEditarSala .btn-primary:focus {
    background-color: #1d4ed8;
}

#formEditarSala .btn-secondary {
    background-color: #6c757d;
    border: none;
    color: #fff;
}

#formEditarSala .btn-secondary:hover,
#formEditarSala .btn-secondary:focus {
    background-color: #5a6268;
}

/* Espaçamento entre campos */
#formEditarSala .mb-3 {
    margin-bottom: 18px;
}

/* Alinhar botões à direita */
#formEditarSala .text-end {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Mensagens de feedback */
#msgEditarSala div.alert {
    font-weight: 600;
    font-size: 0.95rem;
}

/* Ícones no botão */
#formEditarSala button i {
    vertical-align: middle;
}

/* Responsivo para telas pequenas */
@media (max-width: 480px) {
    #formEditarSala {
        padding: 15px 18px;
    }
    #formEditarSala .text-end {
        flex-direction: column;
        gap: 8px;
    }
    #formEditarSala .text-end button {
        width: 100%;
    }
}

</style>

<form id="formEditarSala">
  <div class="mb-3">
    <label class="form-label">Nome da sala</label>
    <input type="text" class="form-control" name="nome" value="<?=htmlspecialchars($sala['nome'])?>" required maxlength="100">
  </div>
  <div class="mb-3">
    <label class="form-label">Capacidade</label>
    <input type="number" class="form-control" name="capacidade" value="<?=$sala['capacidade']?>" min="1" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select" required>
      <?php foreach ($lista_status as $key => $rotulo): ?>
        <option value="<?=$key?>" <?=$sala['status']==$key?'selected':''?>><?=$rotulo?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <input type="hidden" name="id" value="<?=$sala['id']?>">
  <div class="text-end">
    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar alterações</button>
  </div>
  <div id="msgEditarSala" class="mt-3"></div>
</form>

<script>
document.getElementById('formEditarSala').onsubmit = function(e){
  e.preventDefault();
  const data = new FormData(this);
  fetch('salvar_edicao_sala.php', {
    method: 'POST',
    body: data
  })
  .then(r => r.text())
  .then(resp => {
    if(resp.trim() === 'ok') {
      document.getElementById('msgEditarSala').innerHTML = '<div class="alert alert-success">Sala atualizada com sucesso.</div>';
      setTimeout(()=>{ location.reload(); }, 1000);
    } else {
      document.getElementById('msgEditarSala').innerHTML = '<div class="alert alert-danger">'+resp+'</div>';
    }
  })
  .catch(() => {
    document.getElementById('msgEditarSala').innerHTML = '<div class="alert alert-danger">Erro ao atualizar. Tente novamente.</div>';
  });
}
</script>
