<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) header('Location: login.php');

$stmt = $pdo->query('SELECT * FROM salas ORDER BY nome');
$salas = $stmt->fetchAll();

// Pega o horário atual
$horaAtual = date('H:i');
$dataHoje = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-primary sidebar collapse">
                <?php include('menu_lateral.php'); ?>
            </div>
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="card border-0 shadow mb-4 mt-4">
                    <div class="card-header bg-white d-flex align-items-center">
                        <i class="fas fa-door-open text-primary me-2"></i>
                        <h5 class="mb-0">Salas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Capacidade</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($salas as $s): 
                                    $statusExibido = ucfirst($s['status']);
                                    $badge = 'bg-secondary';

                                    // Se manutenção/inativa, mostra original
                                    if ($s['status'] == 'disponivel') {
                                        // Consulta p/ ver se existe reserva vigente agora
                                        $stmtReserva = $pdo->prepare("
                                            SELECT COUNT(*) FROM reservas 
                                            WHERE sala_id=? 
                                            AND data_reserva=? 
                                            AND hora_entrada <= ? 
                                            AND hora_saida > ? 
                                            AND status IN ('confirmada','pendente')
                                        ");
                                        $stmtReserva->execute([$s['id'], $dataHoje, $horaAtual, $horaAtual]);
                                        $emUso = $stmtReserva->fetchColumn() > 0;
                                        if ($emUso) {
                                            $statusExibido = 'Em uso';
                                            $badge = 'bg-info text-white';
                                        } else {
                                            $statusExibido = 'Disponível';
                                            $badge = 'bg-success';
                                        }
                                    } elseif ($s['status'] == 'manutencao') {
                                        $statusExibido = 'Em manutenção';
                                        $badge = 'bg-warning text-dark';
                                    } else {
                                        $badge = 'bg-secondary';
                                    }
                                ?>
                                <tr>
                                    <td><?=htmlspecialchars($s['nome'])?></td>
                                    <td><?= $s['capacidade'] ?></td>
                                    <td>
                                        <span class="badge <?= $badge ?>">
                                            <?= $statusExibido ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"
                                        onclick="window.open('editar_sala.php?id=<?= $s['id'] ?>', '_blank')">
                                        <i class="fas fa-edit"></i>
                                       </button>
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
</body>
</html>