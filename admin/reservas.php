<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$buscaNome = isset($_GET['buscaNome']) ? trim($_GET['buscaNome']) : '';

$sql = "
    SELECT r.id, r.nome_completo, r.vinculo, r.data_reserva, r.hora_entrada, r.hora_saida, r.status, s.nome as sala_nome
    FROM reservas r
    JOIN salas s ON r.sala_id = s.id
    WHERE 1
";

$params = [];

if ($buscaNome !== '') {
    $sql .= " AND r.nome_completo LIKE ?";
    $params[] = "%$buscaNome%";
}

$sql .= " ORDER BY r.data_reserva DESC, r.hora_entrada DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas | Administração</title>
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


                <form method="get" class="d-flex mb-3" onsubmit="return false;" id="form-busca-nome">
                <input 
                    type="text" 
                    name="buscaNome" 
                    id="buscaNome" 
                    class="form-control me-2" 
                    placeholder="Pesquisar pelo nome"
                    value="<?= htmlspecialchars($buscaNome) ?>">
                <button type="submit" class="btn btn-primary" onclick="pesquisarPorNome()">
                    <i class="fas fa-search"></i> Buscar
                </button>
                </form>

                <table>
                <!-- cabeçalho & etc -->
                <tbody>
                    <?php foreach ($reservas as $r): ?>
                    <tr>
                      
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>

                <script>
                function pesquisarPorNome() {
                const nome = document.getElementById('buscaNome').value.trim();
                const url = new URL(window.location.href);
                if (nome.length > 0) {
                    url.searchParams.set('buscaNome', nome);
                } else {
                    url.searchParams.delete('buscaNome');
                }
                window.location.href = url.toString();
                }
                </script>


                <div class="card border-0 shadow mb-4 mt-4">
                    <div class="card-header bg-white d-flex align-items-center">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        <h5 class="mb-0">Reservas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Sala</th>
                                        <th>Data</th>
                                        <th>Entrada</th>
                                        <th>Saída</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservas as $r): ?>
                                    <tr>
                                        <td><?=htmlspecialchars($r['nome_completo'])?></td>
                                        <td><?=htmlspecialchars($r['sala_nome'])?></td>
                                        <td><?=date('d/m/Y', strtotime($r['data_reserva']))?></td>
                                        <td><?=substr($r['hora_entrada'],0,5)?></td>
                                        <td><?=substr($r['hora_saida'],0,5)?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                    if($r['status']=='confirmada') echo 'bg-success';
                                                    elseif($r['status']=='pendente') echo 'bg-warning text-dark';
                                                    elseif($r['status']=='concluida') echo 'bg-primary';
                                                    else echo 'bg-danger';
                                                ?>">
                                                <?=ucfirst($r['status'])?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info text-white"
                                                        onclick="abrirModalReserva('ver_reserva.php?id=<?=$r['id']?>', 'Detalhes da Reserva')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-primary"
                                                       onclick="abrirLink(<?= $r['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="excluir_reserva.php?id=<?=$r['id']?>" 
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Deseja excluir esta reserva?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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

    <!-- Modal para visualização/edição -->
    <div class="modal fade" id="reservaModal" tabindex="-1" aria-labelledby="reservaModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="reservaModalLabel">Reserva</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body p-4" id="modalBodyContent">
            <!-- Conteúdo será carregado via AJAX -->
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        document.getElementById('modalBodyContent').innerHTML =
            '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';
        fetch(url)
          .then(response => response.text())
          .then(html => {
              document.getElementById('modalBodyContent').innerHTML = html;
              modal.show();
          })
          .catch(() => {
              document.getElementById('modalBodyContent').innerHTML =
                  '<div class="alert alert-danger">Erro ao carregar dados.</div>';
              modal.show();
          });
    }

    function pesquisarPorNome() {
    const nome = document.getElementById('buscaNome').value.trim();
    const url = new URL(window.location.href);
    if (nome.length > 0) {
        url.searchParams.set('buscaNome', nome);
    } else {
        url.searchParams.delete('buscaNome');
    }
    window.location.href = url.toString();
    }


    </script>
</body>
</html>