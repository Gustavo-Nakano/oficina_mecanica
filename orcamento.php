<?php
require __DIR__ . '/config/conn.php';

exigir_login();

$tipo = $_SESSION['usuario_tipo'];
$eh_staff = in_array($tipo, ['admin', 'funcionario'], true);
$erro = '';
$sucesso = '';

// ---------- EXCLUIR ORÇAMENTO ----------
if (
    $eh_staff &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['acao'] ?? '') === 'excluir_orcamento'
) {
    $id_orcamento_excluir = (int)($_POST['id_orcamento'] ?? 0);

    if ($id_orcamento_excluir <= 0) {
        $erro = 'Orçamento inválido.';
    } else {
        try {
            mysqli_begin_transaction($conn);

            // Verifica se existe nota de serviço vinculada
            $stmt = mysqli_prepare(
                $conn,
                'SELECT COUNT(*) AS total
                 FROM notas_serv
                 WHERE Id_orcamento = ?'
            );

            mysqli_stmt_bind_param($stmt, 'i', $id_orcamento_excluir);
            mysqli_stmt_execute($stmt);

            $resultado = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );

            mysqli_stmt_close($stmt);

            if ((int)$resultado['total'] > 0) {
                mysqli_rollback($conn);

                $erro = 'Não é possível excluir este orçamento, pois possui uma nota de serviço vinculada.';
            } else {
                // Exclui o orçamento
                $stmt = mysqli_prepare(
                    $conn,
                    'DELETE FROM orcamento WHERE Id_orcamento = ?'
                );

                mysqli_stmt_bind_param($stmt, 'i', $id_orcamento_excluir);
                mysqli_stmt_execute($stmt);

                $excluido = mysqli_stmt_affected_rows($stmt);

                mysqli_stmt_close($stmt);

                if ($excluido > 0) {
                    mysqli_commit($conn);
                    $sucesso = 'Orçamento excluído com sucesso!';
                } else {
                    mysqli_rollback($conn);
                    $erro = 'Orçamento não encontrado.';
                }
            }

        } catch (Throwable $e) {
            mysqli_rollback($conn);

            error_log('Erro ao excluir orçamento: ' . $e->getMessage());

            $erro = 'Erro ao excluir o orçamento. Tente novamente.';
        }
    }
}

// ---------- CRIAR ORÇAMENTO (somente admin/funcionário) ----------
if ($eh_staff && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar_orcamento') {

    $id_agendamento = (int)($_POST['id_agendamento'] ?? 0);
    $servicos_sel = $_POST['servicos'] ?? [];
    $peca_ids = $_POST['peca_id'] ?? [];
    $peca_qtds = $_POST['peca_qtd'] ?? [];

    $pecas_usadas = [];
    foreach ($peca_ids as $i => $pid) {
        $pid = (int)$pid;
        $qtd = (int)($peca_qtds[$i] ?? 0);
        if ($pid > 0 && $qtd > 0) {
            $pecas_usadas[] = ['id' => $pid, 'qtd' => $qtd];
        }
    }

    if ($id_agendamento === 0) {
        $erro = 'Selecione o agendamento.';
    } elseif (empty($servicos_sel) && empty($pecas_usadas)) {
        $erro = 'Informe ao menos um serviço ou uma peça.';
    } else {
        $valor_total = 0;
        $pecas_info = [];

        foreach ($pecas_usadas as $p) {
            $stmt = mysqli_prepare($conn, 'SELECT valor_peca FROM estoque WHERE Id_peca = ?');
            mysqli_stmt_bind_param($stmt, 'i', $p['id']);
            mysqli_stmt_execute($stmt);
            $peca = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if ($peca) {
                $valor_total += $peca['valor_peca'] * $p['qtd'];
                $pecas_info[] = [
                    'id' => $p['id'],
                    'qtd' => $p['qtd'],
                    'valor_unit' => $peca['valor_peca'],
                ];
            }
        }

        mysqli_begin_transaction($conn);

        try {
            $id_fun = ($tipo === 'funcionario') ? $_SESSION['usuario_id'] : null;
            $id_admin = ($tipo === 'admin') ? $_SESSION['usuario_id'] : null;

            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO orcamento (Id_fun, id_admin, total, data_orcamento, Id_agendamento)
                 VALUES (?, ?, ?, CURDATE(), ?)'
            );
            mysqli_stmt_bind_param($stmt, 'iidi', $id_fun, $id_admin, $valor_total, $id_agendamento);
            mysqli_stmt_execute($stmt);
            $id_orcamento = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            foreach ($servicos_sel as $id_servico) {
                $id_servico = (int)$id_servico;
                $stmt = mysqli_prepare($conn, 'INSERT INTO orcamento_serv (Id_serv, Id_orcamento) VALUES (?, ?)');
                mysqli_stmt_bind_param($stmt, 'ii', $id_servico, $id_orcamento);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            foreach ($pecas_info as $p) {
                $stmt = mysqli_prepare(
                    $conn,
                    'INSERT INTO orcamento_peca (Id_orcamento, Id_peca, quant_peca, valor_unit) VALUES (?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param($stmt, 'iiid', $id_orcamento, $p['id'], $p['qtd'], $p['valor_unit']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            mysqli_commit($conn);
            $sucesso = 'Orçamento criado com sucesso!';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $erro = 'Erro ao gerar o orçamento. Tente novamente.';
        }
    }
}

// ---------- DADOS PARA O FORMULÁRIO (admin/funcionário) ----------
if ($eh_staff) {
    // Agendamentos que ainda não têm orçamento
    $agendamentos = mysqli_fetch_all(
        mysqli_query(
            $conn,
            'SELECT a.Id_agendamento, a.data_agend, a.hora_agend,
                    v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo, c.nome_clien
             FROM agendamento a
             JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
             JOIN cliente c ON c.Id_clien = v.Id_clien
             WHERE a.Id_agendamento NOT IN (SELECT Id_agendamento FROM orcamento)
             ORDER BY a.data_agend, a.hora_agend'
        ),
        MYSQLI_ASSOC
    );

    $servicos = mysqli_fetch_all(
        mysqli_query($conn, 'SELECT Id_servico, nome_servico, descricao_servico FROM servicos ORDER BY nome_servico'),
        MYSQLI_ASSOC
    );

    $pecas_disp = mysqli_fetch_all(
        mysqli_query($conn, 'SELECT Id_peca, Nome_peca, partnumber, valor_peca, quant_peca FROM estoque ORDER BY Nome_peca'),
        MYSQLI_ASSOC
    );
}

// ---------- LISTAGEM DE ORÇAMENTOS ----------
if ($eh_staff) {
    $orcamentos = mysqli_fetch_all(
        mysqli_query(
            $conn,
            'SELECT o.*, c.nome_clien, v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo
             FROM orcamento o
             JOIN agendamento a ON a.Id_agendamento = o.Id_agendamento
             JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
             JOIN cliente c ON c.Id_clien = v.Id_clien
             ORDER BY o.data_orcamento DESC, o.Id_orcamento DESC'
        ),
        MYSQLI_ASSOC
    );
} else {
    $id_clien = $_SESSION['usuario_id'];
    $stmt = mysqli_prepare(
        $conn,
        'SELECT o.*, v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo
         FROM orcamento o
         JOIN agendamento a ON a.Id_agendamento = o.Id_agendamento
         JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
         WHERE v.Id_clien = ?
         ORDER BY o.data_orcamento DESC, o.Id_orcamento DESC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_clien);
    mysqli_stmt_execute($stmt);
    $orcamentos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// ---------- DETALHE DE UM ORÇAMENTO ----------
$orc_detalhe = null;
$detalhe_servicos = [];
$detalhe_pecas = [];

if (isset($_GET['ver'])) {
    $id_ver = (int)$_GET['ver'];

    if ($eh_staff) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT o.*, c.nome_clien, c.telefone_clien, c.email_clien,
                    v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo, v.ano_veiculo
             FROM orcamento o
             JOIN agendamento a ON a.Id_agendamento = o.Id_agendamento
             JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
             JOIN cliente c ON c.Id_clien = v.Id_clien
             WHERE o.Id_orcamento = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $id_ver);
    } else {
        $id_clien = $_SESSION['usuario_id'];
        $stmt = mysqli_prepare(
            $conn,
            'SELECT o.*, v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo, v.ano_veiculo
             FROM orcamento o
             JOIN agendamento a ON a.Id_agendamento = o.Id_agendamento
             JOIN veiculos v ON v.Id_veiculo = a.Id_veiculo
             WHERE o.Id_orcamento = ? AND v.Id_clien = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $id_ver, $id_clien);
    }
    mysqli_stmt_execute($stmt);
    $orc_detalhe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($orc_detalhe) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT s.nome_servico, s.descricao_servico
             FROM orcamento_serv os
             JOIN servicos s ON s.Id_servico = os.Id_serv
             WHERE os.Id_orcamento = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $id_ver);
        mysqli_stmt_execute($stmt);
        $detalhe_servicos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare(
            $conn,
            'SELECT op.quant_peca, op.valor_unit, e.Nome_peca, e.partnumber
             FROM orcamento_peca op
             JOIN estoque e ON e.Id_peca = op.Id_peca
             WHERE op.Id_orcamento = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $id_ver);
        mysqli_stmt_execute($stmt);
        $detalhe_pecas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orçamentos - Oficina Mecânica</title>

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

        .peca-linha{
            background-color: #1a1a1a;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .total-destaque{
            font-size: 1.8rem;
            font-weight: 700;
            color: #22c55e;
        }
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
                        <a class="nav-link active" href="/oficina_mecanica/orcamento.php">Orçamentos</a>
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
        <i class="bi bi-file-earmark-text"></i>
        Orçamentos
    </h2>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if ($orc_detalhe): ?>

        <!-- ---------- DETALHE DE UM ORÇAMENTO ---------- -->
        <div class="box mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h4>Orçamento #<?= (int)$orc_detalhe['Id_orcamento'] ?></h4>
                <a href="orcamento.php" class="text-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>

            <p>
                <strong>Data:</strong> <?= date('d/m/Y', strtotime($orc_detalhe['data_orcamento'])) ?><br>
                <strong>Veículo:</strong>
                <?= htmlspecialchars($orc_detalhe['marca_veiculo']) ?> <?= htmlspecialchars($orc_detalhe['modelo_veiculo']) ?>
                (<?= htmlspecialchars($orc_detalhe['ano_veiculo']) ?>) — placa <?= htmlspecialchars($orc_detalhe['placa_veiculo']) ?>

                <?php if ($eh_staff): ?>
                    <br><strong>Cliente:</strong> <?= htmlspecialchars($orc_detalhe['nome_clien']) ?>
                    <br><strong>Contato:</strong> <?= htmlspecialchars($orc_detalhe['telefone_clien']) ?>
                    — <?= htmlspecialchars($orc_detalhe['email_clien']) ?>
                <?php endif; ?>
            </p>

            <h5 class="mt-4">Serviços propostos</h5>
            <?php if (empty($detalhe_servicos)): ?>
                <p class="text-secondary">Nenhum serviço neste orçamento.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($detalhe_servicos as $s): ?>
                        <li>
                            <strong><?= htmlspecialchars($s['nome_servico']) ?></strong>
                            — <?= htmlspecialchars($s['descricao_servico']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h5 class="mt-4">Peças previstas</h5>
            <?php if (empty($detalhe_pecas)): ?>
                <p class="text-secondary">Nenhuma peça neste orçamento.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-sm">
                        <thead>
                            <tr>
                                <th>Peça</th>
                                <th>Partnumber</th>
                                <th>Qtd</th>
                                <th>Valor unit.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalhe_pecas as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['Nome_peca']) ?></td>
                                    <td><?= htmlspecialchars($p['partnumber']) ?></td>
                                    <td><?= (int)$p['quant_peca'] ?></td>
                                    <td>R$ <?= number_format((float)$p['valor_unit'], 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($p['valor_unit'] * $p['quant_peca'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <p class="mt-4 mb-0">
                Total: <span class="total-destaque">R$ <?= number_format((float)$orc_detalhe['total'], 2, ',', '.') ?></span>
            </p>
        </div>

    <?php else: ?>

        <?php if ($eh_staff): ?>
            <!-- ---------- FORMULÁRIO (somente admin/funcionário) ---------- -->
            <div class="box mb-5">
                <h4 class="mb-4">Gerar novo orçamento</h4>

                <?php if (empty($agendamentos)): ?>
                    <p class="text-secondary">Não há agendamentos sem orçamento no momento.</p>
                <?php else: ?>
                    <form method="post" action="orcamento.php">
                        <input type="hidden" name="acao" value="criar_orcamento">

                        <div class="mb-4">
                            <label class="form-label">Agendamento *</label>
                            <select name="id_agendamento" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($agendamentos as $ag): ?>
                                    <option value="<?= (int)$ag['Id_agendamento'] ?>">
                                        <?= date('d/m/Y', strtotime($ag['data_agend'])) ?>
                                        <?= substr($ag['hora_agend'], 0, 5) ?>
                                        — <?= htmlspecialchars($ag['nome_clien']) ?>
                                        (<?= htmlspecialchars($ag['marca_veiculo']) ?> <?= htmlspecialchars($ag['modelo_veiculo']) ?>
                                        — <?= htmlspecialchars($ag['placa_veiculo']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <h5 class="mb-3">Serviços</h5>
                        <?php if (empty($servicos)): ?>
                            <p class="text-secondary">Nenhum serviço cadastrado no sistema.</p>
                        <?php else: ?>
                            <div class="row mb-4">
                                <?php foreach ($servicos as $s): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="servicos[]"
                                                   value="<?= (int)$s['Id_servico'] ?>" id="serv<?= (int)$s['Id_servico'] ?>">
                                            <label class="form-check-label" for="serv<?= (int)$s['Id_servico'] ?>">
                                                <?= htmlspecialchars($s['nome_servico']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h5 class="mb-3">Peças</h5>
                        <?php if (empty($pecas_disp)): ?>
                            <p class="text-secondary">Nenhuma peça cadastrada no estoque.</p>
                        <?php else: ?>
                            <?php foreach ($pecas_disp as $p): ?>
                                <div class="row peca-linha align-items-center">
                                    <div class="col-md-5">
                                        <?= htmlspecialchars($p['Nome_peca']) ?>
                                        <small class="text-secondary">(<?= htmlspecialchars($p['partnumber']) ?>)</small>
                                    </div>
                                    <div class="col-md-2">
                                        R$ <?= number_format((float)$p['valor_peca'], 2, ',', '.') ?>
                                    </div>
                                    <div class="col-md-2">
                                        Em estoque: <?= (int)$p['quant_peca'] ?>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="hidden" name="peca_id[]" value="<?= (int)$p['Id_peca'] ?>">
                                        <input type="number" name="peca_qtd[]" class="form-control" min="0"
                                               value="0" placeholder="Qtd">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-red mt-4">
                            <i class="bi bi-check2-circle"></i> Gerar orçamento
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ---------- LISTAGEM ---------- -->
        <div class="box">
            <h4 class="mb-4"><?= $eh_staff ? 'Todos os orçamentos' : 'Meus orçamentos' ?></h4>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Orçamento</th>
                            <th>Data</th>
                            <th>Veículo</th>
                            <?php if ($eh_staff): ?>
                                <th>Cliente</th>
                            <?php endif; ?>
                            <th>Total</th>
                            <th class="text-end">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orcamentos)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">
                                    Nenhum orçamento encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($orcamentos as $o): ?>
                            <tr>
                                <td>#<?= (int)$o['Id_orcamento'] ?></td>
                                <td><?= date('d/m/Y', strtotime($o['data_orcamento'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($o['marca_veiculo']) ?> <?= htmlspecialchars($o['modelo_veiculo']) ?>
                                    — <?= htmlspecialchars($o['placa_veiculo']) ?>
                                </td>
                                <?php if ($eh_staff): ?>
                                    <td><?= htmlspecialchars($o['nome_clien']) ?></td>
                                <?php endif; ?>
                                <td>R$ <?= number_format((float)$o['total'], 2, ',', '.') ?></td>
                                <td class="text-end">
                                    <a href="orcamento.php?ver=<?= (int)$o['Id_orcamento'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                <td class="text-end">

    <a href="orcamento.php?ver=<?= (int)$o['Id_orcamento'] ?>"
       class="btn btn-sm btn-outline-light">
        <i class="bi bi-eye"></i> Ver
    </a>

    <?php if ($eh_staff): ?>

        <form method="post"
              action="orcamento.php"
              class="d-inline"
              onsubmit="return confirm('Tem certeza que deseja excluir este orçamento?');">

            <input type="hidden"
                   name="acao"
                   value="excluir_orcamento">

            <input type="hidden"
                   name="id_orcamento"
                   value="<?= (int)$o['Id_orcamento'] ?>">

            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i> Excluir
            </button>

        </form>

    <?php endif; ?>

</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

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