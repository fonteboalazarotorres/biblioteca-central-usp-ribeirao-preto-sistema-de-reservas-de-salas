<?php
require_once '../config.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Buscar estatísticas
try {
    // Total de reservas no mês atual
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM reservas 
        WHERE MONTH(data_reserva) = MONTH(CURRENT_DATE()) 
        AND YEAR(data_reserva) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $reservasMes = $stmt->fetch()['total'];
    
    // Total de usuários únicos (por número USP)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT numero_usp) as total FROM reservas");
    $stmt->execute();
    $usuariosUnicos = $stmt->fetch()['total'];
    
    // Valor médio de horas de uso (placeholder)
    $horasMedias = 16.00;
    
    // Reservas por mês (para o gráfico)
    $stmt = $pdo->prepare("
        SELECT MONTH(data_reserva) as mes, COUNT(*) as total 
        FROM reservas 
        WHERE YEAR(data_reserva) = YEAR(CURRENT_DATE()) 
        GROUP BY MONTH(data_reserva) 
        ORDER BY MONTH(data_reserva)
    ");
    $stmt->execute();
    $reservasPorMes = $stmt->fetchAll();
    
    // Reservas por vínculo (para o gráfico)
    $stmt = $pdo->prepare("
        SELECT vinculo, COUNT(*) as total 
        FROM reservas 
        GROUP BY vinculo 
        ORDER BY total DESC
    ");
    $stmt->execute();
    $reservasPorVinculo = $stmt->fetchAll();
    
    // Próximas reservas
    $stmt = $pdo->prepare("
        SELECT r.id, r.nome_completo, r.vinculo, r.data_reserva, r.hora_entrada, r.hora_saida, r.status, s.nome as sala_nome
        FROM reservas r
        JOIN salas s ON r.sala_id = s.id
        WHERE r.data_reserva >= CURRENT_DATE()
        ORDER BY r.data_reserva ASC, r.hora_entrada ASC
        LIMIT 10
    ");
    $stmt->execute();
    $proximasReservas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $erro = 'Erro ao buscar estatísticas: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-primary sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h3 class="text-white">
                            <i class="fas fa-chart-line"></i>
                            Painel de Controle
                        </h3>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Painel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reservas.php">
                                <i class="fas fa-calendar-check"></i>
                                Reservas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="estatisticas.php">
                                <i class="fas fa-chart-bar"></i>
                                Estatísticas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="salas.php">
                                <i class="fas fa-door-open"></i>
                                Salas
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Painel de Controle</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-primary">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nome']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cards de estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1">Reservas este mês</h6>
                                        <h2 class="mb-0"><?php echo $reservasMes; ?></h2>
                                    </div>
                                    <div class="ms-3 bg-primary text-white p-3 rounded">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1">Usuários hoje</h6>
                                        <h2 class="mb-0"><?php echo $usuariosUnicos; ?></h2>
                                    </div>
                                    <div class="ms-3 bg-info text-white p-3 rounded">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--<div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1">Horário mais reservado</h6>
                                        <h2 class="mb-0"><?php echo number_format($horasMedias, 2); ?></h2>
                                    </div>
                                    <div class="ms-3 bg-warning text-white p-3 rounded">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>-->

                <!-- Gráficos -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Estatísticas</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="reservasPorMesChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Reservas</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="reservasPorVinculoChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

        <div class="mb-3 text-end">

        <a href="#" onclick="abrirReserva()">Nova Reserva</a>

        </div>


                <!-- Tabela de próximas reservas -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Reservas Recentes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>

                                        <th>Número USP</th>
                                        <th>Vínculo</th>
                                        <th>Data</th>                                       
                                        <th>Sala</th>
                                        <th>Entrada</th>
                                        <th>Saída</th>
                                       <th>Visualizar dados</th>
                                    </tr>
                                </thead>
                            <tbody id="reservas-futuras-tbody">
                                <?php foreach ($proximasReservas as $reserva): ?>
                                <tr>
                                    <td><?php echo $reserva['nome_completo']; ?></td>
                                    <td><?php echo $reserva['numero_usp']; ?></td>
                                    <td><?php echo $reserva['vinculo']; ?></td>
                                    <td><?php echo $reserva['data_reserva']; ?></td>
                                    
                                    <td><?php echo $reserva['sala_nome']; ?></td>
                                    
                                    <td><?php echo substr($reserva['hora_entrada'], 0, 5); ?></td>
                                    <td><?php echo substr($reserva['hora_saida'], 0, 5); ?></td>
                                    <td><?php echo substr($reserva['hora_entrada'], 0, 5); ?></td>
                                   <td>
                                        <div class="btn">
                                            <button class="btn btn-sm btn-info" 
                                                onclick="abrirModalReserva('ver_reserva.php?id=<?= $reserva['id'] ?>', 'Detalhes da Reserva')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>

   function abrirReserva(id) {
    // Monta o link com o ID como parâmetro
    window.open("adicionar_reserva.php", "_blank");
}


        // Gráfico de reservas por mês
        const mesesNomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const dadosReservasPorMes = Array(12).fill(0);
        
        <?php foreach ($reservasPorMes as $item): ?>
        dadosReservasPorMes[<?php echo $item['mes'] - 1; ?>] = <?php echo $item['total']; ?>;
        <?php endforeach; ?>
        
        const ctxMes = document.getElementById('reservasPorMesChart').getContext('2d');
        new Chart(ctxMes, {
            type: 'bar',
            data: {
                labels: mesesNomes,
                datasets: [{
                    label: 'Reservas por Mês',
                    data: dadosReservasPorMes,
                    backgroundColor: '#36a2eb',
                    borderColor: '#36a2eb',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Gráfico de reservas por vínculo
        const vinculos = [];
        const dadosVinculos = [];
        const coresVinculos = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'];
        
        <?php 
        $i = 0;
        foreach ($reservasPorVinculo as $item): 
        ?>
        vinculos.push('<?php echo $item['vinculo']; ?>');
        dadosVinculos.push(<?php echo $item['total']; ?>);
        <?php 
        $i++;
        endforeach; 
        ?>
        
        const ctxVinculo = document.getElementById('reservasPorVinculoChart').getContext('2d');
        new Chart(ctxVinculo, {
            type: 'line',
            data: {
                labels: vinculos,
                datasets: [{
                    label: 'Reservas por Vínculo',
                    data: dadosVinculos,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointBackgroundColor: '#36a2eb'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        function carregarReservasFuturas() {
            let termo = '';
            const input = document.getElementById('buscaTermo') || document.getElementById('buscaNumeroUsp');
            if (input) termo = input.value || '';
            fetch('reservas_futuras_ajax.php?termo=' + encodeURIComponent(termo))
                .then(response => response.json())
                .then(dados => {
                            let html = '';
                            dados.forEach(reserva => {
                                html += `<tr>
                                    <td>${reserva.nome_completo}</td>
                                    <td>${reserva.numero_usp}</td>
                                    <td>${reserva.vinculo}</td>
                                    <td>${reserva.data_reserva}</td>
                                    <td>${reserva.sala_nome}</td>
                                    <td>${reserva.hora_entrada.substr(0,5)}</td>
                                    <td>${reserva.hora_saida.substr(0,5)}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-info" onclick="abrirModalReserva('ver_reserva.php?id=${reserva.id}', 'Detalhes da Reserva')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="excluir_reserva.php?id=${reserva.id}" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta reserva?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>`;
                            });
                            document.getElementById('reservas-futuras-tbody').innerHTML = html;
                        });
                }

            // Atualize a cada 15 segundos (15000ms)
            setInterval(carregarReservasFuturas, 15000);
            // Faça o load inicial
            carregarReservasFuturas();


    </script>

        <div class="modal fade" id="reservaModal" tabindex="-1" aria-labelledby="reservaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="reservaModalLabel">Detalhes da Reserva</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4" id="modalBodyContent">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
            </div>
        </div>
        </div>

        
            <script>

                function abrirLink(id) {
                // Monta o link com o ID
                const url = `editar_reserva.php?id=${id}`;
                
                // Abre o link em uma nova aba (ou use window.location.href para redirecionar na mesma)
                window.open(url, '_blank');
            }


            function abrirModalReserva(url, titulo) {
                const modal = new bootstrap.Modal(document.getElementById('reservaModal'));
                document.getElementById('reservaModalLabel').textContent = titulo;
                document.getElementById('modalBodyContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';

                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('modalBodyContent').innerHTML = html;
                        modal.show();
                    })
                    .catch(() => {
                        document.getElementById('modalBodyContent').innerHTML = '<div class="alert alert-danger">Erro ao carregar dados.</div>';
                        modal.show();
                    });
            }


            </script>
    
    <footer class="mt-5 p-3 bg-light text-center">
        <p>Sistema de Reserva de Salas - BCRP-USP &copy; <?php echo date('Y'); ?></p>
        <p>Desenvolvido por <a href="http://lattes.cnpq.br/4623045728159220" target="_blank">Fonte-Boa Lázaro Torres</a> idealizado por <a href="https://lattes.cnpq.br/1572390366115265" target="_blank">Robson de Paula Araujo</a></p>
    </footer>


</body>
</html>