<?php
require_once 'config.php';

$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$hora_inicio = isset($_GET['hora_inicio']) ? $_GET['hora_inicio'] : null;
$hora_fim = isset($_GET['hora_fim']) ? $_GET['hora_fim'] : null;

$salas = $pdo->query("SELECT * FROM salas WHERE status = 'disponivel' ORDER BY nome")->fetchAll();

$disponiveis = [];
foreach ($salas as $sala) {
    $stmt = $pdo->prepare("
        SELECT hora_entrada, hora_saida FROM reservas
        WHERE sala_id = ? AND data_reserva = ? AND status IN ('confirmada','pendente')
        ORDER BY hora_entrada
    ");
    $stmt->execute([$sala['id'], $data]);
    $reservas_dia = $stmt->fetchAll();

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
foreach ($disponiveis as $sala) {
    echo '<div class="col-md-6 mb-4">';
    echo '<div class="card h-100 shadow-sm">';
    echo '<div class="card-header bg-primary text-white">';
    echo '<strong>' . htmlspecialchars($sala['nome']) . '</strong> – Capacidade: ' . $sala['capacidade'] . ' pessoas';
    echo '</div><div class="card-body">';
    if ($sala['horarios']) {
        echo '<ul class="mb-0">';
        foreach ($sala['horarios'] as $hor) {
            echo '<li><span class="badge bg-success">Livre</span> ' . htmlspecialchars($hor[0]) . ' às ' . htmlspecialchars($hor[1]) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<span class="text-danger">Sem horários livres neste dia/intervalo.</span>';
    }
    echo '</div></div></div>';
}
