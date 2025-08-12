<div class="position-sticky pt-3">
    <div class="text-center mb-4">
        <h3 class="text-white">
            <i class="fas fa-chart-line"></i> Painel de Controle
        </h3>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo ' active'; ?>" href="index.php">
                <i class="fas fa-tachometer-alt"></i> Painel
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='reservas.php') echo ' active'; ?>" href="reservas.php">
                <i class="fas fa-calendar-check"></i> Reservas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='estatisticas.php') echo ' active'; ?>" href="estatisticas.php">
                <i class="fas fa-chart-bar"></i> Estatísticas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='salas.php') echo ' active'; ?>" href="salas.php">
                <i class="fas fa-door-open"></i> Salas
            </a>
        </li>
        <li class="nav-item mt-5">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </li>
    </ul>
</div>