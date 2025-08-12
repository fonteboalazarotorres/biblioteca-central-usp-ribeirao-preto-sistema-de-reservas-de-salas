<?php
require_once '../config.php';
require_once 'ajax_salas_disponiveis.php';

// Recebe filtros simples via GET (opcional, pode ser expandido)
$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$hora_inicio = isset($_GET['hora_inicio']) ? $_GET['hora_inicio'] : null;
$hora_fim = isset($_GET['hora_fim']) ? $_GET['hora_fim'] : null;

// Busca todas as salas
$salas = $pdo->query("SELECT * FROM salas WHERE status = 'disponivel' ORDER BY nome")->fetchAll();

// Prepara a tabela de disponibilidade
$disponiveis = [];
foreach ($salas as $sala) {
    // Busca reservas dessa sala para o dia
    $stmt = $pdo->prepare("
        SELECT hora_entrada, hora_saida FROM reservas
        WHERE sala_id = ? AND data_reserva = ? AND status IN ('confirmada','pendente')
        ORDER BY hora_entrada
    ");
    $stmt->execute([$sala['id'], $data]);
    $reservas_dia = $stmt->fetchAll();

    // Calcula janelas disponíveis
    $horas_livres = [];
    $hora_abertura = "08:00";
    $hora_fechamento = "22:00";
    $livre_inicio = $hora_abertura;

    foreach ($reservas_dia as $res) {
        if ($livre_inicio < $res['hora_entrada']) {
            $horas_livres[] = [$livre_inicio, $res['hora_entrada']];
        }
        $livre_inicio = max($livre_inicio, $res['hora_saida']);
    }

    if ($livre_inicio < $hora_fechamento) {
        $horas_livres[] = [$livre_inicio, $hora_fechamento];
    }

    // Filtra por horário se aplicável
    if ($hora_inicio && $hora_fim) {
        $horas_livres = array_filter($horas_livres, function($janela) use ($hora_inicio, $hora_fim) {
            return $janela[0] <= $hora_inicio && $janela[1] >= $hora_fim;
        });
    }

    $disponiveis[] = [
        'nome' => $sala['nome'],
        'capacidade' => $sala['capacidade'],
        'horarios' => $horas_livres
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Salas Disponíveis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container mt-4 mb-5">
    <h2 class="my-4 text-center">
        <i class="fas fa-door-open text-primary"></i> Salas Disponíveis para Reserva
    </h2>

<div class="container mt-4 mb-5">
    <h2 class="my-4 text-center">
        <i class="fas fa-door-open text-primary"></i> Salas Disponíveis para Reserva
    </h2>

    <form class="row g-3 mb-4" method="get">
        <div class="col-md-3">
            <label for="data" class="form-label">Data</label>
            <input type="date" id="data" name="data" class="form-control" value="<?=htmlspecialchars($data)?>">
        </div>
        <div class="col-md-3">
            <label for="hora_inicio" class="form-label">Horário Início (opcional)</label>
            <input type="time" id="hora_inicio" name="hora_inicio" class="form-control" value="<?=htmlspecialchars($hora_inicio)?>">
        </div>
        <div class="col-md-3">
            <label for="hora_fim" class="form-label">Horário Fim (opcional)</label>
            <input type="time" id="hora_fim" name="hora_fim" class="form-control" value="<?=htmlspecialchars($hora_fim)?>">
        </div>
        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search"></i> Ver Disponibilidade
            </button>
        </div>
    </form>

    <!-- Pelo container AJAX: -->
    <div class="row" id="salas-disponiveis-lista">
        <!-- Cards das salas serão carregados automaticamente aqui via AJAX -->
    </div>

    <footer class="mt-5 p-3 bg-light text-center text-muted rounded">
        Em caso de dúvidas, entre em contato com a administração.
    </footer>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function atualizarSalasDisponiveis() {
    // Pega os filtros atuais do formulário
    const data = document.getElementById('data').value;
    const hora_inicio = document.getElementById('hora_inicio').value;
    const hora_fim = document.getElementById('hora_fim').value;
    const params = new URLSearchParams({data, hora_inicio, hora_fim});
    fetch('ajax_salas_disponiveis.php?' + params.toString())
      .then(resp => resp.text())
      .then(html => {
        document.getElementById('salas-disponiveis-lista').innerHTML = html;
      });
}

// Atualiza ao carregar e a cada 30 segundos
atualizarSalasDisponiveis();
setInterval(atualizarSalasDisponiveis, 30000);

// Atualiza imediatamente ao mudar algum filtro
document.getElementById('data').addEventListener('change', atualizarSalasDisponiveis);
document.getElementById('hora_inicio').addEventListener('change', atualizarSalasDisponiveis);
document.getElementById('hora_fim').addEventListener('change', atualizarSalasDisponiveis);
</script>

</body>
</html>