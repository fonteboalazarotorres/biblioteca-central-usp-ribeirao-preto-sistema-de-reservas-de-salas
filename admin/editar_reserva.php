<?php
// Configurações do banco de dados
define('DB_HOST', 'sql109.infinityfree.com');
define('DB_USER', 'if0_38667478');
define('DB_PASS', 'RkwER7GPXQA18g');
define('DB_NAME', 'if0_38667478_reserva_salas');

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("set names utf8");
} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
// Redireciona se não há id definido
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo "Reserva não especificada!";
    exit;
}

// Carrega informações da reserva e equipamentos
$stmt = $pdo->prepare("SELECT * FROM reservas WHERE id=?");
$stmt->execute([$id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    echo "Reserva não encontrada!";
    exit;
}

// Carrega todas as salas e equipamentos
$salas = $pdo->query("SELECT id, nome FROM salas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$equipamentos = $pdo->query("SELECT id, nome FROM equipamentos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Equipamentos já selecionados na reserva
$eq_stmt = $pdo->prepare("SELECT equipamento_id FROM reserva_equipamentos WHERE reserva_id=?");
$eq_stmt->execute([$id]);
$equipamentosSelecionados = $eq_stmt->fetchAll(PDO::FETCH_COLUMN);

// Lista de vínculos (ajuste conforme sua aplicação)
$vinculos = ['Graduação', 'Pós-graduação', 'Docente', 'Servidor', 'Externo'];

// Lista dos status da reserva conforme seu banco
$statusPossiveis = ['pendente' => 'Pendente', 'confirmada' => 'Confirmada', 'cancelada' => 'Cancelada', 'concluida' => 'Concluída'];

// Mensagem de feedback
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação básica
    $obrigatorios = ['nome_completo','numero_usp','vinculo','data_reserva','sala_id','quantidade_pessoas','hora_entrada','hora_saida','status'];
    foreach ($obrigatorios as $campo) {
        if (empty($_POST[$campo])) {
            $msg = "Preencha todos os campos obrigatórios.";
            break;
        }
    }

    // REMOVIDO: validação que bloqueava edição para datas anteriores
    // if (!$msg && strtotime($_POST['data_reserva']) < strtotime(date('Y-m-d'))) {
    //     $msg = "Data de reserva não pode ser anterior a hoje.";
    // }

    if (!$msg && $_POST['hora_entrada'] >= $_POST['hora_saida']) {
        $msg = "Hora de saída deve ser posterior à entrada.";
    }

    if (!$msg) {
        try {
            // Valida se existe conflito se mudar sala/dia/horários
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM reservas 
                WHERE id<>? AND data_reserva=? AND sala_id=? AND status IN ('pendente', 'confirmada')
                AND (
                    (hora_entrada <= ? AND hora_saida > ?) OR
                    (hora_entrada < ? AND hora_saida >= ?) OR
                    (hora_entrada >= ? AND hora_saida <= ?)
                )
            ");
            $stmt->execute([
                $id,
                $_POST['data_reserva'],
                $_POST['sala_id'],
                $_POST['hora_saida'], $_POST['hora_entrada'],
                $_POST['hora_entrada'], $_POST['hora_saida'],
                $_POST['hora_entrada'], $_POST['hora_saida']
            ]);
            if ($stmt->fetchColumn() > 0) {
                $msg = "Já existe reserva nesta sala e horário.";
            } else {
                // Inicia transação para salvar
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    UPDATE reservas
                    SET nome_completo=?, numero_usp=?, vinculo=?, sala_id=?, quantidade_pessoas=?,
                        data_reserva=?, hora_entrada=?, hora_saida=?, status=?
                    WHERE id=?
                ");
                $ok = $stmt->execute([
                    $_POST['nome_completo'],
                    $_POST['numero_usp'],
                    $_POST['vinculo'],
                    $_POST['sala_id'],
                    $_POST['quantidade_pessoas'],
                    $_POST['data_reserva'],
                    $_POST['hora_entrada'],
                    $_POST['hora_saida'],
                    $_POST['status'],
                    $id
                ]);

                // Atualiza equipamentos
                $pdo->prepare("DELETE FROM reserva_equipamentos WHERE reserva_id=?")->execute([$id]);
                if (!empty($_POST['equipamentos']) && is_array($_POST['equipamentos'])) {
                    $insert_eq = $pdo->prepare("INSERT INTO reserva_equipamentos (reserva_id, equipamento_id) VALUES (?, ?)");
                    foreach ($_POST['equipamentos'] as $eq_id) {
                        $insert_eq->execute([$id, $eq_id]);
                    }
                }
                $pdo->commit();

                $msg = "Reserva atualizada com sucesso!";

                // Recarrega dados após salvar
                $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id=?");
                $stmt->execute([$id]);
                $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

                $eq_stmt = $pdo->prepare("SELECT equipamento_id FROM reserva_equipamentos WHERE reserva_id=?");
                $eq_stmt->execute([$id]);
                $equipamentosSelecionados = $eq_stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <title>Editar Reserva</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    

    <style>

    body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px 15px;
    color: #212529;
}

h2 {
    font-weight: 600;
    color: #2563eb;
    margin-bottom: 25px;
    font-size: 1.6rem;
    text-align: center;
}

/* Formulário container para limitar largura e centralizar */
form {
    max-width: 600px;
    background: #fff;
    padding: 25px 30px;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgb(0 0 0 / 0.1);
    margin: 0 auto 30px auto;
}

/* Labels mais destacados */
label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    display: block;
    margin-bottom: 6px;
}

/* Inputs e selects com foco azul padrão */
input[type="text"],
input[type="date"],
input[type="time"],
input[type="number"],
select {
    width: 100%;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1.5px solid #ced4da;
    font-size: 1rem;
    margin-bottom: 18px;
    box-sizing: border-box;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

input[type="text"]:focus,
input[type="date"]:focus,
input[type="time"]:focus,
input[type="number"]:focus,
select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 8px rgba(37, 99, 235, 0.4);
}

/* Checkbox estilos */
input[type="checkbox"] {
    transform: scale(1.1);
    margin-right: 8px;
    vertical-align: middle;
}

label > input[type="checkbox"] {
    margin-right: 10px;
}

/* Botão manter padrão azul vibrante */
button[type="submit"] {
    background-color: #2563eb;
    color: white;
    border: none;
    padding: 12px 28px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 12px;
}

button[type="submit"]:hover,
button[type="submit"]:focus {
    background-color: #1d4ed8;
    outline: none;
}

/* Mensagem de feedback */
p strong, #msgEditarReserva div {
    display: block;
    margin-top: 15px;
    padding: 12px 15px;
    font-weight: 700;
    border-radius: 6px;
    font-size: 1rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

p{
    text-align: center;
}
p strong {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

#msgEditarReserva div.alert-success {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

#msgEditarReserva div.alert-danger {
    background-color: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}

/* Link de navegação */
p a {
    text-decoration: none;
    color: #2563eb;
    font-weight: 600;
    transition: color 0.2s ease;
}

p a:hover {
    color: #1e40af;
}

/* Responsividade simples para telas pequenas */
@media (max-width: 640px) {
    form {
        padding: 15px 20px;
    }
}

    
    </style>
</head>
<body>

    <h2>Tela de edição da Reserva</h2>

    <p>Você pode voltar ao <a href="login.php">Início</a></p>

    <?php if ($msg): ?>
        <p><strong><?=htmlspecialchars($msg)?></strong></p>
    <?php endif; ?>

    <form method="post">
        <label>Nome completo:<br>
            <input type="text" name="nome_completo" value="<?=htmlspecialchars($reserva['nome_completo'])?>" required>
        </label><br><br>

        <label>Número USP:<br>
            <input type="text" name="numero_usp" value="<?=htmlspecialchars($reserva['numero_usp'])?>" required>
        </label><br><br>

        <label>Vínculo:<br>
            <select name="vinculo" required>
                <option value="">Selecione...</option>
                <?php foreach ($vinculos as $vin): ?>
                    <option value="<?=$vin?>" <?=$reserva['vinculo']===$vin?'selected':''?>><?=$vin?></option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

        <label>Data da reserva:<br>
            <input type="date" name="data_reserva" value="<?=$reserva['data_reserva']?>" required>
        </label><br><br>

        <label>Sala:<br>
            <select name="sala_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($salas as $sala): ?>
                    <option value="<?=$sala['id']?>" <?=$reserva['sala_id']==$sala['id']?'selected':''?>><?=$sala['nome']?></option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

        <label>Quantidade de pessoas:<br>
            <input type="number" name="quantidade_pessoas" min="1" value="<?=htmlspecialchars($reserva['quantidade_pessoas'])?>" required>
        </label><br><br>

        <label>Hora de entrada:<br>
            <input type="time" name="hora_entrada" value="<?=htmlspecialchars($reserva['hora_entrada'])?>" required>
        </label><br><br>

        <label>Hora de saída:<br>
            <input type="time" name="hora_saida" value="<?=htmlspecialchars($reserva['hora_saida'])?>" required>
        </label><br><br>

        <label>Status:<br>
            <select name="status" required>
                <option value="">Selecione...</option>
                <?php foreach ($statusPossiveis as $key => $label): ?>
                    <option value="<?=$key?>" <?=$reserva['status'] === $key ? 'selected' : ''?>><?=$label?></option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

        <label>Necessidade de equipamentos:</label><br>
        <?php foreach ($equipamentos as $eq): ?>
            <label>
                <input type="checkbox" name="equipamentos[]" value="<?=$eq['id']?>" <?=in_array($eq['id'],$equipamentosSelecionados)?'checked':''?>>
                <?=$eq['nome']?>
            </label><br>
        <?php endforeach; ?>

        <br>
        <button type="submit">Salvar alterações</button>
    </form>

    
</body>
</html>