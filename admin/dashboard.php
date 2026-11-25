<?php
require __DIR__ . '/../config/conn.php';

exigir_tipo(['admin']);

function dashboard_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$resumoQuery = "
    SELECT
        (SELECT COUNT(*) FROM cliente) AS total_clientes,
        (SELECT COUNT(*) FROM veiculos) AS total_veiculos,
        (SELECT COUNT(*) FROM agendamento WHERE data_agend >= CURDATE()) AS proximos_agendamentos,
        (SELECT COUNT(*) FROM orcamento) AS total_orcamentos,
        (SELECT COUNT(*) FROM servicos) AS total_servicos,
        (
            SELECT COALESCE(SUM(valor_total), 0)
            FROM notas_serv
            WHERE data_servico >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND data_servico < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
        ) AS faturamento_mes
";
$resumoResultado = mysqli_query($conn, $resumoQuery);
if (!$resumoResultado) {
    throw new RuntimeException('Não foi possível carregar o resumo do dashboard.');
}
$resumo = mysqli_fetch_assoc($resumoResultado);
mysqli_free_result($resumoResultado);

$faturamentoQuery = "
    SELECT DATE_FORMAT(data_servico, '%Y-%m') AS mes, SUM(valor_total) AS total
    FROM notas_serv
    WHERE data_servico >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
      AND data_servico < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
    GROUP BY DATE_FORMAT(data_servico, '%Y-%m')
";
$faturamentoResultado = mysqli_query($conn, $faturamentoQuery);
if (!$faturamentoResultado) {
    throw new RuntimeException('Não foi possível carregar o histórico de faturamento.');
}

$mesesGrafico = [];
$inicioGrafico = new DateTimeImmutable('first day of this month');
for ($i = 11; $i >= 0; $i--) {
    $mes = $inicioGrafico->modify("-{$i} months");
    $mesesGrafico[$mes->format('Y-m')] = [
        'rotulo' => $mes->format('m/Y'),
        'valor' => 0.0,
    ];
}

while ($linha = mysqli_fetch_assoc($faturamentoResultado)) {
    if (isset($mesesGrafico[$linha['mes']])) {
        $mesesGrafico[$linha['mes']]['valor'] = (float) $linha['total'];
    }
}
mysqli_free_result($faturamentoResultado);

$maiorFaturamento = max(array_column($mesesGrafico, 'valor'));

$agendamentosQuery = "
    SELECT
        a.data_agend,
        a.hora_agend,
        v.marca_veiculo,
        v.modelo_veiculo,
        v.placa_veiculo,
        c.nome_clien
    FROM agendamento a
    INNER JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
    INNER JOIN cliente c ON c.Id_clien = v.Id_clien
    WHERE a.data_agend >= CURDATE()
    ORDER BY a.data_agend ASC, a.hora_agend ASC
    LIMIT 6
";
$agendamentosResultado = mysqli_query($conn, $agendamentosQuery);
if (!$agendamentosResultado) {
    throw new RuntimeException('Não foi possível carregar os próximos agendamentos.');
}

$nomeUsuario = dashboard_escape((string) ($_SESSION['usuario_nome'] ?? 'Administrador'));
$meses = [
    '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
    '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
    '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez',
];
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - GV Performance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            color-scheme: dark;
            --page: #101113;
            --surface: #191a1e;
            --surface-raised: #202126;
            --muted: #9a9ba3;
            --accent: #dc2626;
            --line: rgba(255, 255, 255, .08);
        }
        body { min-height: 100vh; background: var(--page); color: #f5f5f5; }
        .navbar { background: #080808; border-bottom: 1px solid var(--line); }
        .navbar-brand { letter-spacing: .02em; }
        .navbar-brand i { color: var(--accent); }
        .nav-link { color: #c9c9ce; }
        .nav-link:hover, .nav-link.active { color: #fff; }
        .page-content { max-width: 1440px; }
        .eyebrow { color: #ef7777; font-size: .75rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .page-title { font-weight: 750; letter-spacing: -.03em; }
        .muted { color: var(--muted); }
        .panel, .metric-card { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
        .metric-card { height: 100%; padding: 1.25rem; }
        .metric-icon { display: grid; width: 44px; height: 44px; place-items: center; border-radius: 12px; background: rgba(220, 38, 38, .14); color: #ff7777; font-size: 1.25rem; }
        .metric-value { margin: 1.1rem 0 .25rem; font-size: clamp(1.55rem, 3vw, 2rem); font-weight: 750; letter-spacing: -.04em; }
        .metric-label { color: var(--muted); font-size: .9rem; }
        .panel { height: 100%; padding: 1.35rem; }
        .panel-title { font-size: 1.05rem; font-weight: 700; }
        .chart { display: flex; min-height: 225px; align-items: end; gap: .55rem; padding: 1.25rem .25rem 0; border-bottom: 1px solid var(--line); }
        .chart-column { display: flex; min-width: 0; height: 100%; flex: 1; flex-direction: column; align-items: center; justify-content: end; gap: .55rem; }
        .chart-bar-wrap { display: flex; width: 100%; height: 170px; align-items: end; justify-content: center; }
        .chart-bar { width: min(28px, 72%); min-height: 4px; border-radius: 6px 6px 2px 2px; background: linear-gradient(180deg, #f87171, #b91c1c); }
        .chart-label { padding-bottom: .65rem; color: var(--muted); font-size: .68rem; white-space: nowrap; }
        .table { --bs-table-bg: transparent; --bs-table-color: #eee; --bs-table-border-color: var(--line); margin-bottom: 0; }
        .table thead th { padding-top: 0; color: var(--muted); font-size: .73rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
        .table td { padding-top: .9rem; padding-bottom: .9rem; vertical-align: middle; }
        .plate { display: inline-block; padding: .2rem .45rem; border: 1px solid var(--line); border-radius: 5px; color: #ddd; font-size: .76rem; white-space: nowrap; }
        .btn-accent { background: var(--accent); border: 0; color: #fff; font-weight: 600; }
        .btn-accent:hover { background: #b91c1c; color: #fff; }
        .quick-link { color: #ddd; text-decoration: none; }
        .quick-link:hover { color: #fff; }
        .quick-link i { color: #ef7777; }
        @media (max-width: 575.98px) {
            .page-content { padding-right: 1rem !important; padding-left: 1rem !important; }
            .panel { padding: 1rem; }
            .chart { gap: .2rem; }
            .chart-label { font-size: .58rem; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand fw-bold" href="/oficina_mecanica/admin/admin.php">
            <i class="bi bi-tools me-2"></i>GV Performance
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="/oficina_mecanica/admin/admin.php">Início</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="/oficina_mecanica/admin/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/oficina_mecanica/orcamento.php">Orçamentos</a></li>
                <li class="nav-item"><a class="nav-link" href="/oficina_mecanica/estoque.php">Estoque</a></li>
                <li class="nav-item"><a class="nav-link" href="/oficina_mecanica/servicos.php">Serviços</a></li>
                <li class="nav-item"><a class="nav-link" href="/oficina_mecanica/admin/cadastro_funcionarios.php">Funcionários</a></li>
                <li class="nav-item dropdown ms-lg-3">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i><?= $nomeUsuario ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                        <li><a class="dropdown-item" href="/oficina_mecanica/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container-fluid page-content px-4 px-lg-5 py-4 py-lg-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <div class="eyebrow mb-2">Visão geral da oficina</div>
            <h1 class="page-title h2 mb-1">Olá, <?= $nomeUsuario ?>.</h1>
            <p class="muted mb-0">Acompanhe os principais indicadores da GV Performance.</p>
        </div>
        <a class="btn btn-accent px-3 py-2" href="/oficina_mecanica/orcamento.php">
            <i class="bi bi-plus-lg me-2"></i>Novo orçamento
        </a>
    </div>

    <section class="row g-3 mb-4" aria-label="Indicadores">
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card">
                <div class="metric-icon"><i class="bi bi-people"></i></div>
                <div class="metric-value"><?= number_format((int) $resumo['total_clientes'], 0, ',', '.') ?></div>
                <div class="metric-label">Clientes cadastrados</div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card">
                <div class="metric-icon"><i class="bi bi-car-front"></i></div>
                <div class="metric-value"><?= number_format((int) $resumo['total_veiculos'], 0, ',', '.') ?></div>
                <div class="metric-label">Veículos cadastrados</div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card">
                <div class="metric-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="metric-value"><?= number_format((int) $resumo['proximos_agendamentos'], 0, ',', '.') ?></div>
                <div class="metric-label">Agendamentos a partir de hoje</div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card">
                <div class="metric-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="metric-value">R$ <?= number_format((float) $resumo['faturamento_mes'], 2, ',', '.') ?></div>
                <div class="metric-label">Faturamento deste mês</div>
            </article>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <div class="panel">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="panel-title mb-1">Faturamento</h2>
                        <p class="muted small mb-0">Últimos 12 meses · notas de serviço</p>
                    </div>
                    <span class="badge rounded-pill text-bg-dark border border-secondary-subtle">Orçamentos: <?= number_format((int) $resumo['total_orcamentos'], 0, ',', '.') ?></span>
                </div>
                <div class="chart" role="img" aria-label="Gráfico de faturamento mensal dos últimos 12 meses">
                    <?php foreach ($mesesGrafico as $mesId => $dadosMes): ?>
                        <?php
                        $altura = $maiorFaturamento > 0
                            ? max(4, (int) round(($dadosMes['valor'] / $maiorFaturamento) * 100))
                            : 4;
                        $mesNumero = substr($mesId, 5, 2);
                        ?>
                        <div class="chart-column" title="<?= dashboard_escape($dadosMes['rotulo']) ?>: R$ <?= number_format($dadosMes['valor'], 2, ',', '.') ?>">
                            <div class="chart-bar-wrap">
                                <div class="chart-bar" style="height: <?= $altura ?>%"></div>
                            </div>
                            <span class="chart-label"><?= $meses[$mesNumero] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="panel">
                <h2 class="panel-title mb-1">Resumo da oficina</h2>
                <p class="muted small mb-4">Cadastros e atalhos para gestão</p>
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom border-secondary-subtle">
                    <span class="muted"><i class="bi bi-clipboard2-check me-2"></i>Orçamentos</span>
                    <strong><?= number_format((int) $resumo['total_orcamentos'], 0, ',', '.') ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom border-secondary-subtle">
                    <span class="muted"><i class="bi bi-wrench-adjustable me-2"></i>Serviços cadastrados</span>
                    <strong><?= number_format((int) $resumo['total_servicos'], 0, ',', '.') ?></strong>
                </div>
                <div class="pt-3 d-grid gap-3">
                    <a class="quick-link" href="/oficina_mecanica/estoque.php"><i class="bi bi-box-seam me-2"></i>Consultar estoque <i class="bi bi-arrow-up-right float-end"></i></a>
                    <a class="quick-link" href="/oficina_mecanica/notas.php"><i class="bi bi-receipt me-2"></i>Consultar notas <i class="bi bi-arrow-up-right float-end"></i></a>
                    <a class="quick-link" href="/oficina_mecanica/admin/cadastro_funcionarios.php"><i class="bi bi-person-plus me-2"></i>Gerenciar funcionários <i class="bi bi-arrow-up-right float-end"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="panel-title mb-1">Próximos agendamentos</h2>
                <p class="muted small mb-0">Atendimentos marcados para hoje e os próximos dias</p>
            </div>
            <span class="badge rounded-pill text-bg-dark border border-secondary-subtle"><?= number_format((int) $resumo['proximos_agendamentos'], 0, ',', '.') ?> no total</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data e hora</th>
                        <th>Cliente</th>
                        <th>Veículo</th>
                        <th>Placa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($agendamentosResultado) === 0): ?>
                        <tr><td class="muted py-4 text-center" colspan="4">Não há agendamentos futuros cadastrados.</td></tr>
                    <?php else: ?>
                        <?php while ($agendamento = mysqli_fetch_assoc($agendamentosResultado)): ?>
                            <tr>
                                <td>
                                    <div><?= date('d/m/Y', strtotime($agendamento['data_agend'])) ?></div>
                                    <small class="muted"><?= dashboard_escape(substr((string) $agendamento['hora_agend'], 0, 5)) ?></small>
                                </td>
                                <td><?= dashboard_escape((string) $agendamento['nome_clien']) ?></td>
                                <td><?= dashboard_escape(trim((string) $agendamento['marca_veiculo'] . ' ' . (string) $agendamento['modelo_veiculo'])) ?></td>
                                <td><span class="plate"><?= dashboard_escape((string) $agendamento['placa_veiculo']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php
mysqli_free_result($agendamentosResultado);
include __DIR__ . '/../includes/footer.php';
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
