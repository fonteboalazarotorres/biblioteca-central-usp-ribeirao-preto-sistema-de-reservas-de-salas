<?php
require_once 'config.php';

// Verificar se existe uma mensagem de sucesso ou erro
$mensagem = '';
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

// Buscar todas as salas
$stmt = $pdo->prepare("SELECT * FROM salas ORDER BY nome");
$stmt->execute();
$salas = $stmt->fetchAll();

// Buscar todos os equipamentos
$stmt = $pdo->prepare("SELECT * FROM equipamentos");
$stmt->execute();
$equipamentos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Reserva de Salas BCRP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-4">
        <header class="mb-4">
            <h1 class="text-center">Sistema de Reserva de Salas BCRP</h1>
            <p class="text-center">Reserve uma sala para estudo ou reunião</p>
            <div class="text-end">
                <!--<a href="admin/login.php" class="btn btn-sm btn-outline-primary">Entrar como administrador</a>-->
            </div>
        </header>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Formulário para reserva de sala</h3>
                    </div>
                    <div class="card-body">
                        <form action="processar_reserva.php" method="post" id="formReserva">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="nome_completo" class="form-label">Nome completo</label>
                                    <input type="text" class="form-control" id="nome_completo" name="nome_completo" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="numero_usp" class="form-label">Número USP</label>
                                    <input type="text" class="form-control" id="numero_usp" name="numero_usp" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="vinculo" class="form-label">Vínculo</label>
                                    <select class="form-select" id="vinculo" name="vinculo" required>
                                        <option value="">Selecione...</option>
                                        <option value="Graduação">Graduação</option>
                                        <option value="Pós-graduação">Pós-graduação</option>
                                        <option value="Docente">Docente</option>
                                        <option value="Servidor">Servidor</option>
                                        <option value="Externo">Externo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="data_reserva" class="form-label">Data da reserva</label>
                                    <input type="date" class="form-control" id="data_reserva" name="data_reserva" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="quantidade_pessoas" class="form-label">Quantidade de pessoas</label>
                                    <input type="number" class="form-control" id="quantidade_pessoas" name="quantidade_pessoas" min="1" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="hora_entrada" class="form-label">Hora de entrada</label>
                                    <input type="time" class="form-control" id="hora_entrada" name="hora_entrada" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="hora_saida" class="form-label">Hora de saída</label>
                                    <input type="time" class="form-control" id="hora_saida" name="hora_saida" required>
                                </div>
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
                            
                            <div class="mb-4">
                                <label class="form-label">Salas Disponíveis</label>
                                <div class="row" id="salas_container">
                                    <!-- As salas disponíveis serão carregadas via AJAX -->
                                    <div class="col-12 text-center">
                                        <p>Selecione a data e horário para ver as salas disponíveis</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg" id="btnReservar">Reservar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 p-3 bg-light text-center">
        <p>Sistema de Reserva de Salas - BCRP-USP &copy; <?php echo date('Y'); ?></p>
        <p>Desenvolvido por <a href="https://lattes.cnpq.br/4623045728159220" target="_blank">Fonte-Boa Lázaro Torres</a> idealizado por <a href="https://lattes.cnpq.br/1572390366115265" target="_blank">Robson de Paula Araujo</a></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>