<?php
require __DIR__ . '/config/conn.php';

// Qualquer usuário logado acessa, o conteúdo muda conforme o tipo
exigir_login();

$tipo = $_SESSION['usuario_tipo'];
$eh_cliente = $tipo === 'cliente';
$erro = '';
$sucesso = '';

// ---------- AÇÕES (somente cliente) ----------
if ($eh_cliente && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    // Cadastrar veículo
    if ($acao === 'add_veiculo') {
        $marca = trim($_POST['marca_veiculo'] ?? '');
        $modelo = trim($_POST['modelo_veiculo'] ?? '');
        $ano = trim($_POST['ano_veiculo'] ?? '');
        $placa = trim($_POST['placa_veiculo'] ?? '');

        if ($marca === '' || $modelo === '' || $placa === '') {
            $erro = 'Preencha marca, modelo e placa do veículo.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO veiculos (Id_clien, ano_veiculo, marca_veiculo, modelo_veiculo, placa_veiculo)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $id_clien = $_SESSION['usuario_id'];
            mysqli_stmt_bind_param($stmt, 'issss', $id_clien, $ano, $marca, $modelo, $placa);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sucesso = 'Veículo cadastrado com sucesso!';
        }
    }

    // Criar agendamento
    if ($acao === 'agendar') {
        $id_veiculo = (int)($_POST['id_veiculo'] ?? 0);
        $data = trim($_POST['data_agend'] ?? '');
        $hora = trim($_POST['hora_agend'] ?? '');
        $descricao = trim($_POST['descricao_servico'] ?? '');

        if ($id_veiculo === 0 || $data === '' || $hora === '') {
            $erro = 'Selecione o veículo, a data e o horário.';
        } elseif ($data < date('Y-m-d')) {
            $erro = 'Escolha uma data a partir de hoje.';
        } else {
            // Confirma que o veículo pertence a este cliente
            $stmt = mysqli_prepare($conn, 'SELECT Id_veiculo FROM veiculos WHERE Id_veiculo = ? AND Id_clien = ?');
            $id_clien = $_SESSION['usuario_id'];
            mysqli_stmt_bind_param($stmt, 'ii', $id_veiculo, $id_clien);
            mysqli_stmt_execute($stmt);
            $valido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$valido) {
                $erro = 'Veículo inválido.';
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO agendamento (data_agend, hora_agend, Id_veiculo, descricao_servico, status)
                     VALUES (?, ?, ?, ?, 'agendado')"
                );
                mysqli_stmt_bind_param($stmt, 'ssis', $data, $hora, $id_veiculo, $descricao);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $sucesso = 'Agendamento realizado com sucesso!';
            }
        }
    }

    // Cancelar agendamento (somente o dono do veículo)
    if ($acao === 'cancelar') {
        $id_agend = (int)($_POST['id_agendamento'] ?? 0);
        $id_clien = $_SESSION['usuario_id'];

        $stmt = mysqli_prepare(
            $conn,
            'UPDATE agendamento a
             JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
             SET a.status = "cancelado"
             WHERE a.Id_agendamento = ? AND v.Id_clien = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $id_agend, $id_clien);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sucesso = 'Agendamento cancelado.';
    }
}

// ---------- LISTAGENS ----------

if ($eh_cliente) {
    $id_clien = $_SESSION['usuario_id'];

    // Veículos do cliente, para o formulário de agendamento
    $stmt = mysqli_prepare($conn, 'SELECT * FROM veiculos WHERE Id_clien = ? ORDER BY marca_veiculo');
    mysqli_stmt_bind_param($stmt, 'i', $id_clien);
    mysqli_stmt_execute($stmt);
    $veiculos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // Agendamentos do próprio cliente
    $stmt = mysqli_prepare(
        $conn,
        'SELECT a.*, v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo
         FROM agendamento a
         JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
         WHERE v.Id_clien = ?
         ORDER BY a.data_agend, a.hora_agend'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_clien);
    mysqli_stmt_execute($stmt);
    $agendamentos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

} else {
    // Admin e funcionário veem todos os agendamentos com dados do cliente e do veículo
    $resultado = mysqli_query(
        $conn,
        'SELECT a.*, v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo, v.ano_veiculo,
                c.nome_clien, c.telefone_clien, c.email_clien
         FROM agendamento a
         JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
         JOIN cliente c ON c.Id_clien = v.Id_clien
         ORDER BY a.data_agend, a.hora_agend'
    );
    $agendamentos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agendamentos - Oficina Mecânica</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body{
            background-image:
                linear-gradient(rgba(0,0,0,0.80), rgba(0,0,0,0.90)),
                url('/oficina_mecanica/src/bkg01.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-color: #000;
            color: #fff;
        }
        .navbar{ background-color: #000; }

        .box{
            background-color: #222;
            border-radius: 12px;
            padding: 2rem;
        }

        .btn-red{
            background-color: #dc2626;
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-red:hover{ background-color: #b91c1c; color: #fff; }

        table{ color: #fff; }
        thead{ background-color: #333; }
        tbody tr{ background-color: #1a1a1a; }

        .badge-agendado{ background-color: #2563eb; }
        .badge-concluido{ background-color: #16a34a; }
        .badge-cancelado{ background-color: #6b7280; }
    </style>
</head>
<body>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/oficina_mecanica/index.php">
                <i class="bi bi-tools"></i>
                Oficina Mecânica
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/oficina_mecanica/index.php">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/oficina_mecanica/agendamento.php">Agendamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/oficina_mecanica/logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<section class="container my-5">

    <h2 class="mb-4">
        <i class="bi bi-calendar2-check"></i>
        Agendamentos
    </h2>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if ($eh_cliente): ?>

        <!-- ---------- CADASTRAR VEÍCULO ---------- -->
        <div class="box mb-4">
            <h4 class="mb-4">Meus veículos</h4>

            <?php if (empty($veiculos)): ?>
                <p class="text-secondary">Você ainda não tem veículos cadastrados.</p>
            <?php else: ?>
                <ul class="mb-4">
                    <?php foreach ($veiculos as $v): ?>
                        <li>
                            <?= htmlspecialchars($v['marca_veiculo']) ?> <?= htmlspecialchars($v['modelo_veiculo']) ?>
                            (<?= htmlspecialchars($v['ano_veiculo']) ?>) — placa <?= htmlspecialchars($v['placa_veiculo']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="agendamento.php" class="row g-3">
                <input type="hidden" name="acao" value="add_veiculo">

                <div class="col-md-3">
                    <label class="form-label">Marca *</label>
                    <input type="text" name="marca_veiculo" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modelo *</label>
                    <input type="text" name="modelo_veiculo" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ano</label>
                    <input type="text" name="ano_veiculo" class="form-control" placeholder="Ex: 2020">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Placa *</label>
                    <input type="text" name="placa_veiculo" class="form-control" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-red w-100">Adicionar</button>
                </div>
            </form>
        </div>

        <!-- ---------- AGENDAR SERVIÇO ---------- -->
        <div class="box mb-5">
            <h4 class="mb-4">Agendar serviço</h4>

            <?php if (empty($veiculos)): ?>
                <p class="text-secondary">Cadastre um veículo acima antes de agendar.</p>
            <?php else: ?>
                <form method="post" action="agendamento.php" class="row g-3">
                    <input type="hidden" name="acao" value="agendar">

                    <div class="col-md-3">
                        <label class="form-label">Veículo *</label>
                        <select name="id_veiculo" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($veiculos as $v): ?>
                                <option value="<?= (int)$v['Id_veiculo'] ?>">
                                    <?= htmlspecialchars($v['marca_veiculo']) ?> <?= htmlspecialchars($v['modelo_veiculo']) ?>
                                    — <?= htmlspecialchars($v['placa_veiculo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Data *</label>
                        <input type="date" name="data_agend" class="form-control" required
                               min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Horário *</label>
                        <input type="time" name="hora_agend" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Serviço desejado</label>
                        <input type="text" name="descricao_servico" class="form-control"
                               placeholder="Ex: Troca de óleo">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-red w-100">Agendar</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <!-- ---------- LISTAGEM ---------- -->
    <div class="box">
        <h4 class="mb-4"><?= $eh_cliente ? 'Meus agendamentos' : 'Todos os agendamentos' ?></h4>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Veículo</th>
                        <?php if (!$eh_cliente): ?>
                            <th>Cliente</th>
                            <th>Contato</th>
                        <?php endif; ?>
                        <th>Serviço</th>
                        <th>Status</th>
                        <?php if ($eh_cliente): ?>
                            <th class="text-end">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agendamentos)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                Nenhum agendamento encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($agendamentos as $a): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($a['data_agend'])) ?></td>
                            <td><?= substr($a['hora_agend'], 0, 5) ?></td>
                            <td>
                                <?= htmlspecialchars($a['marca_veiculo']) ?> <?= htmlspecialchars($a['modelo_veiculo']) ?>
                                — <?= htmlspecialchars($a['placa_veiculo']) ?>
                            </td>

                            <?php if (!$eh_cliente): ?>
                                <td><?= htmlspecialchars($a['nome_clien']) ?></td>
                                <td>
                                    <?= htmlspecialchars($a['telefone_clien']) ?><br>
                                    <small class="text-secondary"><?= htmlspecialchars($a['email_clien']) ?></small>
                                </td>
                            <?php endif; ?>

                            <td><?= htmlspecialchars($a['descricao_servico'] ?: '—') ?></td>

                            <td>
                                <?php if ($a['status'] === 'agendado'): ?>
                                    <span class="badge badge-agendado">Agendado</span>
                                <?php elseif ($a['status'] === 'concluido'): ?>
                                    <span class="badge badge-concluido">Concluído</span>
                                <?php else: ?>
                                    <span class="badge badge-cancelado">Cancelado</span>
                                <?php endif; ?>
                            </td>

                            <?php if ($eh_cliente): ?>
                                <td class="text-end">
                                    <?php if ($a['status'] === 'agendado'): ?>
                                        <form method="post" action="agendamento.php" class="d-inline"
                                              onsubmit="return confirm('Cancelar este agendamento?');">
                                            <input type="hidden" name="acao" value="cancelar">
                                            <input type="hidden" name="id_agendamento" value="<?= (int)$a['Id_agendamento'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle"></i> Cancelar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</section>

<footer class="py-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <h5 class="text-secondary">
                    <i class="bi bi-tools"></i>
                    Oficina Mecânica
                </h5>
            </div>
            <div class="col-md-6">
                <p class="text-end text-secondary mb-0">© 2026 Nakano & Kayque</p>
            </div>
        </div>
    </div>
</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"
></script>
</body>
</html>