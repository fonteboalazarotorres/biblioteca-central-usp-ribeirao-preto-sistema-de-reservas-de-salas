<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Definir mês e ano de filtro padrão - mês e ano atual
$mesAno = $_GET['mesano'] ?? date('Y-m');
list($anoFiltro, $mesFiltro) = explode('-', $mesAno);

// Função para converter YYYY-MM para timestamp início e fim do mês
function getMesPeriodos($ano, $mes) {
    $inicio = strtotime("$ano-$mes-01 00:00:00");
    $fim = strtotime(date("Y-m-t 23:59:59", $inicio));
    return [$inicio, $fim];
}

// Obter período do mês filtrado
list($inicioMes, $fimMes) = getMesPeriodos($anoFiltro, $mesFiltro);
$dataInicioMes = date('Y-m-d', $inicioMes);
$dataFimMes = date('Y-m-d', $fimMes);

// Obter semana atual no mês filtrado (a partir do 1o dia do mês)
// Definimos primeiro dia da semana como segunda-feira (1 em YEARWEEK)
$semanaAtual = date('W', time());
$anoSemanaAtual = date('Y');

// Para calcular semana, usaremos semana do ano com base na data do servidor
// Para simplificar, calcular as datas da semana da atual para filtrar reservas nesta semana
// Mas como filtramos por mês, pegaremos a semana atual contando do mês por simplicidade

// Consultas principais para estatísticas no mês selecionado

// 1) Total de reservas no mês (considerando toda data entre início e fim do mês)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE data_reserva BETWEEN ? AND ?");
$stmt->execute([$dataInicioMes, $dataFimMes]);
$totalReservasMes = (int) $stmt->fetchColumn();

// 2) Total de reservas na semana atual (base da data atual para limitar dentro do mês)
$dataSemanaAtualInicio = date('Y-m-d', strtotime('monday this week'));
$dataSemanaAtualFim = date('Y-m-d', strtotime('sunday this week'));
// Ajustar para não sair do mês filtro
if ($dataSemanaAtualInicio < $dataInicioMes) $dataSemanaAtualInicio = $dataInicioMes;
if ($dataSemanaAtualFim > $dataFimMes) $dataSemanaAtualFim = $dataFimMes;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE data_reserva BETWEEN ? AND ?");
$stmt->execute([$dataSemanaAtualInicio, $dataSemanaAtualFim]);
$totalReservasSemana = (int) $stmt->fetchColumn();

// 3) Sala mais usada no mês (maior número de reservas)
$stmt = $pdo->prepare("
    SELECT s.nome, COUNT(*) as total_reservas, SUM(r.quantidade_pessoas) as total_pessoas
    FROM reservas r 
    JOIN salas s ON r.sala_id = s.id 
    WHERE r.data_reserva BETWEEN ? AND ?
    GROUP BY r.sala_id, s.nome 
    ORDER BY total_reservas DESC 
    LIMIT 1
    ");
$stmt->execute([$dataInicioMes, $dataFimMes]);
$salaMaisUsada = $stmt->fetch(PDO::FETCH_ASSOC);

// 4) Salas mais e menos reservadas por dia — prepara stats por cada dia do mês
$stmtDias = $pdo->prepare("SELECT DISTINCT data_reserva FROM reservas WHERE data_reserva BETWEEN ? AND ? ORDER BY data_reserva ASC");
$stmtDias->execute([$dataInicioMes, $dataFimMes]);
$datasMes = $stmtDias->fetchAll(PDO::FETCH_COLUMN);

$estatisticasDias = [];
foreach ($datasMes as $data){
    $stmtSalas = $pdo->prepare("
        SELECT s.nome, COUNT(r.id) as total_reservas
        FROM salas s
        LEFT JOIN reservas r ON r.sala_id = s.id AND r.data_reserva = ?
        GROUP BY s.id, s.nome
        ORDER BY total_reservas DESC
    ");
    $stmtSalas->execute([$data]);
    $salasData = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);

    if(empty($salasData)) continue;
    $maxReservas = max(array_column($salasData, 'total_reservas'));
    $minReservas = min(array_filter(array_column($salasData, 'total_reservas'), fn($v) => $v > 0));

    $maisReservadas = array_filter($salasData, fn($s) => $s['total_reservas'] == $maxReservas);
    $menosReservadas = array_filter($salasData, fn($s) => $s['total_reservas'] == $minReservas);

    $estatisticasDias[] = [
        'data' => $data,
        'mais_reservadas' => $maisReservadas,
        'menos_reservadas' => $menosReservadas,
    ];
}

// 5) Quantidade total de pessoas por data e sala no mês
$stmt = $pdo->prepare("
    SELECT r.data_reserva, s.nome AS sala_nome, SUM(r.quantidade_pessoas) AS total_pessoas
    FROM reservas r
    JOIN salas s ON r.sala_id = s.id
    WHERE r.data_reserva BETWEEN ? AND ?
    GROUP BY r.data_reserva, s.nome
    ORDER BY r.data_reserva ASC, s.nome ASC
");
$stmt->execute([$dataInicioMes, $dataFimMes]);
$quantidadePessoasPorDataSala = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6) Quantidade total de pessoas no mês e na semana atual
$stmt = $pdo->prepare("SELECT SUM(quantidade_pessoas) FROM reservas WHERE data_reserva BETWEEN ? AND ?");
$stmt->execute([$dataInicioMes, $dataFimMes]);
$totalPessoasMes = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(quantidade_pessoas) FROM reservas WHERE data_reserva BETWEEN ? AND ?");
$stmt->execute([$dataSemanaAtualInicio, $dataSemanaAtualFim]);
$totalPessoasSemana = (int)$stmt->fetchColumn();


// -----------------------------------------
// Preparar dados para gráficos
// -----------------------------------------

// Gráfico: Total de reservas por dia do mês
$labelsDias = [];
$dadosReservasPorDia = [];
foreach ($datasMes as $data) {
    $labelsDias[] = date('d/m', strtotime($data));
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE data_reserva = ?");
    $stmtCount->execute([$data]);
    $dadosReservasPorDia[] = (int)$stmtCount->fetchColumn();
}

// Gráfico: Total de reservas por sala no mês (para top 5)
$stmtSalaTotais = $pdo->prepare("
    SELECT s.nome, COUNT(*) as total_reservas 
    FROM reservas r 
    JOIN salas s ON r.sala_id = s.id 
    WHERE r.data_reserva BETWEEN ? AND ? 
    GROUP BY r.sala_id, s.nome 
    ORDER BY total_reservas DESC
    LIMIT 5
");
$stmtSalaTotais->execute([$dataInicioMes, $dataFimMes]);
$salasTop5 = $stmtSalaTotais->fetchAll(PDO::FETCH_ASSOC);
$labelsSalas = array_column($salasTop5, 'nome');
$dadosSalas = array_map(fn($r) => (int)$r['total_reservas'], $salasTop5);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Estatísticas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
  /* Ajuste compacto para melhor visual */
  body { background: #f8f9fa; padding: 20px; }
  .estatistica-box { background:#fff; padding:15px; border-radius:8px; box-shadow:0 0 12px rgb(0 0 0 / 0.05); margin-bottom:20px; }
  h4 { color:#2563eb; font-weight:600; margin-bottom:15px; }
  table thead { background:#e9ecef; }
  .btn-imprimir { margin-top:10px; }
</style>
</head>
<body>

<div class="container">
  <h2 class="mb-4">Estatísticas para o mês de <strong><?= date('m/Y', strtotime($mesAno.'-01')) ?></strong></h2>

  <form id="formFiltroMes" class="mb-4 d-flex align-items-center gap-2" style="max-width:300px;">
    <label for="mesano" class="mb-0">Selecione o mês:</label>
    <input type="month" id="mesano" name="mesano" value="<?= htmlspecialchars($mesAno) ?>" class="form-control" />
    <button class="btn btn-primary" type="submit">Filtrar</button>
  </form>

  <!-- Cards principais -->
  <div class="row g-3 mb-4">

   <!-- <button id="btnEnviarEmail" class="btn btn-success mb-3">
    <i class="fas fa-envelope"></i> Enviar Estatísticas por Email
</button>
<div id="msgEmail" class="mt-2"></div>-->
    
<p>Você pode retornar ao <a href="login.php" class="button">Início</a> ou <a href="#" onclick="window.print(); return false;">Imprimir página</a></p>

    <div class="col-md-3">
      <div class="estatistica-box text-center">
        <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
        <div>Reservas do Mês</div>
        <h3><?= $totalReservasMes ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="estatistica-box text-center">
        <i class="fas fa-calendar-week fa-2x text-primary mb-2"></i>
        <div>Reservas na Semana</div>
        <h3><?= $totalReservasSemana ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="estatistica-box text-center">
        <i class="fas fa-door-open fa-2x text-primary mb-2"></i>
        <div>Sala Mais Reservada</div>
        <h5><?= htmlspecialchars($salaMaisUsada['nome'] ?? '-') ?></h5>
        <small><?= (int)($salaMaisUsada['total_reservas'] ?? 0) ?> vezes</small><br/>
        <small><?= (int)($salaMaisUsada['total_pessoas'] ?? 0) ?> pessoas</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="estatistica-box text-center">
        <i class="fas fa-users fa-2x text-primary mb-2"></i>
        <div>Total de Pessoas no Mês</div>
        <h3><?= $totalPessoasMes ?></h3>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="estatistica-box">
        <h4><i class="fas fa-chart-line me-2"></i>Gráfico de Reservas por Dia</h4>
        <canvas id="chartReservasPorDia" height="300"></canvas>
        <!--<button class="btn btn-sm btn-outline-secondary btn-imprimir" onclick="printArea('chartReservasPorDia')">
          <i class="fas fa-print me-1"></i>Imprimir gráfico
        </button>-->
      </div>
    </div>
    <div class="col-md-6">
      <div class="estatistica-box">
        <h4><i class="fas fa-door-open me-2"></i>Gráfico das 5 Salas Mais Reservadas</h4>
        <canvas id="chartSalasTop5" height="300"></canvas>
        <!--<button class="btn btn-sm btn-outline-secondary btn-imprimir" onclick="printArea('chartSalasTop5')">
          <i class="fas fa-print me-1"></i>Imprimir gráfico
        </button>-->
      </div>
    </div>
  </div>

  <div class="estatistica-box mb-4">
    <h4><i class="fas fa-table me-2"></i>Salas Mais e Menos Reservadas por Dia</h4>
    <div class="table-responsive" style="max-height:350px; overflow-y:auto;">
      <table class="table table-striped table-bordered table-sm align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>Data</th>
            <th>Mais Reservadas</th>
            <th>Reservas</th>
            <th>Menos Reservadas</th>
            <th>Reservas</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($estatisticasDias as $dia): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($dia['data'])) ?></td>
              <td>
                <?php foreach ($dia['mais_reservadas'] as $sala) : ?>
                  <?= htmlspecialchars($sala['nome']) ?><br/>
                <?php endforeach; ?>
              </td>
              <td><?= (int)($dia['mais_reservadas'][0]['total_reservas'] ?? 0) ?></td>
              <td>
                <?php foreach ($dia['menos_reservadas'] as $sala) : ?>
                  <?= htmlspecialchars($sala['nome']) ?><br/>
                <?php endforeach; ?>
              </td>
              <td><?= (int)($dia['menos_reservadas'][0]['total_reservas'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <!--<button class="btn btn-sm btn-outline-secondary btn-imprimir" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Imprimir tabela
      </button>-->
    </div>
  </div>

  <div class="estatistica-box mb-4">
    <h4><i class="fas fa-users me-2"></i>Quantidade Total de Pessoas por Data</h4>
    <div class="table-responsive" style="max-height:350px; overflow-y:auto;">
      <table class="table table-striped table-bordered table-sm align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>Data</th>
            <th>Sala</th>
            <th>Total de Pessoas</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quantidadePessoasPorDataSala as $linha): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($linha['data_reserva'])) ?></td>
            <td><?= htmlspecialchars($linha['sala_nome']) ?></td>
            <td><?= (int)$linha['total_pessoas'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <!--<button class="btn btn-sm btn-outline-secondary btn-imprimir" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Imprimir tabela
      </button>-->
    </div>
  </div>

  <div class="estatistica-box text-center mb-5">
    <h4><i class="fas fa-users me-2"></i>Total de Pessoas na Semana</h4>
    <h2><?= $totalPessoasSemana ?></h2>
  </div>

</div>



<script>

$('#btnEnviarEmail').click(function() {
    $('#msgEmail').html('');
    const mesAno = $('#mesano').val() || '<?= date('Y-m') ?>';

    $.ajax({
        url: 'enviar_email_estatisticas.php',
        data: { mesano: mesAno },
        method: 'GET',
        success: function(res) {
            if(res.trim() === 'ok') {
                $('#msgEmail').html('<div class="alert alert-success">Estatísticas enviadas com sucesso!</div>');
            } else {
                $('#msgEmail').html('<div class="alert alert-danger">Erro ao enviar email: ' + res + '</div>');
            }
        },
        error: function() {
            $('#msgEmail').html('<div class="alert alert-danger">Erro na conexão ao enviar email.</div>');
        }
    });
});


// Atualizar a página ao mudar o filtro mês sem dar submit manual (opcional)
$('#mesano').on('change', function() {
  $('#formFiltroMes').submit();
});

// Print área para gráficos
function printArea(canvasId){
  const canvas = document.getElementById(canvasId);
  if(!canvas) return;
  const w=window.open('');
  w.document.write('<html><head><title>Imprimir gráfico</title></head><body style="margin:0;"><img src="'+canvas.toDataURL()+'" style="max-width:100%; height:auto;"></body></html>');
  w.document.close();
  w.focus();
  w.print();
  w.close();
}

// Gráficos Chart.js
const ctxDia = document.getElementById('chartReservasPorDia').getContext('2d');
const chartDia = new Chart(ctxDia, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsDias) ?>,
        datasets: [{
            label: 'Reservas por dia',
            data: <?= json_encode($dadosReservasPorDia) ?>,
            backgroundColor: '#2563eb',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, precision: 0 }
        }
    }
});

const ctxSala = document.getElementById('chartSalasTop5').getContext('2d');
const chartSala = new Chart(ctxSala, {
    type: 'pie',
    data: {
        labels: <?= json_encode($labelsSalas) ?>,
        datasets: [{
            label: 'Reservas por sala',
            data: <?= json_encode($dadosSalas) ?>,
            backgroundColor: ['#2563eb','#1d4ed8','#3b82f6','#60a5fa','#93c5fd']
        }]
    },
    options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' }
        }
    }
});
</script>

</body>
</html>
