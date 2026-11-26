<?php
require __DIR__ . '/config/conn.php';

// Qualquer usuário logado acessa, o conteúdo muda conforme o tipo
exigir_login();

$tipo = $_SESSION['usuario_tipo'];
$eh_staff = in_array($tipo, ['admin', 'funcionario'], true);
$erro = '';
$sucesso = '';

// ---------- CRIAR NOTA (somente admin/funcionário) ----------
if ($eh_staff && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar_nota') {

    $id_clien = (int)($_POST['id_clien'] ?? 0);
    $id_veiculo = (int)($_POST['id_veiculo'] ?? 0);
    $servicos_sel = $_POST['servicos'] ?? [];
    $peca_ids = $_POST['peca_id'] ?? [];
    $peca_qtds = $_POST['peca_qtd'] ?? [];

    // Monta lista de peças usadas (ignora linhas com quantidade zero ou vazia)
    $pecas_usadas = [];
    foreach ($peca_ids as $i => $pid) {
        $pid = (int)$pid;
        $qtd = (int)($peca_qtds[$i] ?? 0);
        if ($pid > 0 && $qtd > 0) {
            $pecas_usadas[] = ['id' => $pid, 'qtd' => $qtd];
        }
    }

    if ($id_clien === 0 || $id_veiculo === 0) {
        $erro = 'Selecione o cliente e o veículo.';
    } elseif (empty($servicos_sel) && empty($pecas_usadas)) {
        $erro = 'Informe ao menos um serviço ou uma peça utilizada.';
    } else {
        // Confirma que o veículo pertence ao cliente selecionado
        $stmt = mysqli_prepare($conn, 'SELECT Id_veiculo FROM veiculos WHERE Id_veiculo = ? AND Id_clien = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $id_veiculo, $id_clien);
        mysqli_stmt_execute($stmt);
        $valido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$valido) {
            $erro = 'Veículo inválido para este cliente.';
        } else {
            // Confirma estoque suficiente e calcula o total
            $valor_total = 0;
            $pecas_info = [];
            $estoque_insuficiente = false;

            foreach ($pecas_usadas as $p) {
                $stmt = mysqli_prepare($conn, 'SELECT Nome_peca, valor_peca, quant_peca FROM estoque WHERE Id_peca = ?');
                mysqli_stmt_bind_param($stmt, 'i', $p['id']);
                mysqli_stmt_execute($stmt);
                $peca = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);

                if (!$peca || $peca['quant_peca'] < $p['qtd']) {
                    $estoque_insuficiente = true;
                    $erro = 'Estoque insuficiente para a peça: ' . htmlspecialchars($peca['Nome_peca'] ?? 'desconhecida');
                    break;
                }

                $valor_total += $peca['valor_peca'] * $p['qtd'];
                $pecas_info[] = [
                    'id' => $p['id'],
                    'qtd' => $p['qtd'],
                    'valor_unit' => $peca['valor_peca'],
                ];
            }

            if (!$estoque_insuficiente) {
                mysqli_begin_transaction($conn);

                try {
                    $id_fun = ($tipo === 'funcionario') ? $_SESSION['usuario_id'] : null;
                    $id_admin = ($tipo === 'admin') ? $_SESSION['usuario_id'] : null;

                    $stmt = mysqli_prepare(
                        $conn,
                        'INSERT INTO notas_serv (data_servico, valor_total, Id_fun, Id_admin, Id_veiculo, Id_clien)
                         VALUES (CURDATE(), ?, ?, ?, ?, ?)'
                    );
                    mysqli_stmt_bind_param($stmt, 'diiii', $valor_total, $id_fun, $id_admin, $id_veiculo, $id_clien);
                    mysqli_stmt_execute($stmt);
                    $id_nota = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    // Grava os serviços realizados
                    foreach ($servicos_sel as $id_servico) {
                        $id_servico = (int)$id_servico;
                        $stmt = mysqli_prepare($conn, 'INSERT INTO nota_servico (Id_servico, Id_notas) VALUES (?, ?)');
                        mysqli_stmt_bind_param($stmt, 'ii', $id_servico, $id_nota);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }

                    // Grava as peças utilizadas e baixa o estoque
                    foreach ($pecas_info as $p) {
                        $stmt = mysqli_prepare(
                            $conn,
                            'INSERT INTO nota_peca (Id_peca, Id_nota, quant_pecas, valor_unit_peca) VALUES (?, ?, ?, ?)'
                        );
                        mysqli_stmt_bind_param($stmt, 'iiid', $p['id'], $id_nota, $p['qtd'], $p['valor_unit']);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        $stmt = mysqli_prepare($conn, 'UPDATE estoque SET quant_peca = quant_peca - ? WHERE Id_peca = ?');
                        mysqli_stmt_bind_param($stmt, 'ii', $p['qtd'], $p['id']);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }

                    mysqli_commit($conn);
                    $sucesso = 'Nota de serviço criada com sucesso!';
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $erro = 'Erro ao gerar a nota. Tente novamente.';
                }
            }
        }
    }
}

// ---------- DADOS PARA O FORMULÁRIO (admin/funcionário) ----------
if ($eh_staff) {
    $clientes = mysqli_fetch_all(
        mysqli_query($conn, 'SELECT Id_clien, nome_clien FROM cliente ORDER BY nome_clien'),
        MYSQLI_ASSOC
    );

    $veiculos_todos = mysqli_fetch_all(
        mysqli_query($conn, 'SELECT Id_veiculo, Id_clien, marca_veiculo, modelo_veiculo, placa_veiculo FROM veiculos'),
        MYSQLI_ASSOC
    );

    $servicos = mysqli_fetch_all(
        mysqli_query($conn, 'SELECT Id_servico, nome_servico, descricao_servico FROM servicos ORDER BY nome_servico'),
        MYSQLI_ASSOC
    );

    $pecas_disp = mysqli_fetch_all(
        mysqli_query($conn, 'SELECT Id_peca, Nome_peca, partnumber, valor_peca, quant_peca FROM estoque WHERE ativo = 1 ORDER BY Nome_peca'),
        MYSQLI_ASSOC
    );

    // Agrupa veículos por cliente, para preencher o select dinamicamente via JS
    $veiculos_por_cliente = [];
    foreach ($veiculos_todos as $v) {
        $veiculos_por_cliente[$v['Id_clien']][] = $v;
    }
}

// ---------- LISTAGEM DE NOTAS ----------
if ($eh_staff) {
    $notas = mysqli_fetch_all(
        mysqli_query(
            $conn,
            'SELECT n.*, c.nome_clien, v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo
             FROM notas_serv n
             JOIN cliente c ON c.Id_clien = n.Id_clien
             JOIN veiculos v ON v.Id_veiculo = n.Id_veiculo
             ORDER BY n.data_servico DESC, n.Id_nota DESC'
        ),
        MYSQLI_ASSOC
    );
} else {
    $id_clien = $_SESSION['usuario_id'];
    $stmt = mysqli_prepare(
        $conn,
        'SELECT n.*, v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo
         FROM notas_serv n
         JOIN veiculos v ON v.Id_veiculo = n.Id_veiculo
         WHERE n.Id_clien = ?
         ORDER BY n.data_servico DESC, n.Id_nota DESC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_clien);
    mysqli_stmt_execute($stmt);
    $notas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// ---------- DETALHE DE UMA NOTA ----------
$nota_detalhe = null;
$detalhe_servicos = [];
$detalhe_pecas = [];

if (isset($_GET['ver'])) {
    $id_ver = (int)$_GET['ver'];

    if ($eh_staff) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT n.*, c.nome_clien, c.telefone_clien, c.email_clien,
                    v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo, v.ano_veiculo
             FROM notas_serv n
             JOIN cliente c ON c.Id_clien = n.Id_clien
             JOIN veiculos v ON v.Id_veiculo = n.Id_veiculo
             WHERE n.Id_nota = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $id_ver);
    } else {
        $id_clien = $_SESSION['usuario_id'];
        $stmt = mysqli_prepare(
            $conn,
            'SELECT n.*, v.marca_veiculo, v.modelo_veiculo, v.placa_veiculo, v.ano_veiculo
             FROM notas_serv n
             JOIN veiculos v ON v.Id_veiculo = n.Id_veiculo
             WHERE n.Id_nota = ? AND n.Id_clien = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $id_ver, $id_clien);
    }
    mysqli_stmt_execute($stmt);
    $nota_detalhe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($nota_detalhe) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT s.nome_servico, s.descricao_servico
             FROM nota_servico ns
             JOIN servicos s ON s.Id_servico = ns.Id_servico
             WHERE ns.Id_notas = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $id_ver);
        mysqli_stmt_execute($stmt);
        $detalhe_servicos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare(
            $conn,
            'SELECT np.quant_pecas, np.valor_unit_peca, e.Nome_peca, e.partnumber
             FROM nota_peca np
             JOIN estoque e ON e.Id_peca = np.Id_peca
             WHERE np.Id_nota = ?'
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
    <title>Notas de Serviço - Oficina Mecânica</title>

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
                        <a class="nav-link active" href="/oficina_mecanica/notas.php">Notas</a>
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
        <i class="bi bi-receipt"></i>
        Notas de Serviço
    </h2>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if ($nota_detalhe): ?>

        <!-- ---------- DETALHE DE UMA NOTA ---------- -->
        <div class="box mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h4>Nota #<?= (int)$nota_detalhe['Id_nota'] ?></h4>
                <a href="notas.php" class="text-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>

            <p>
                <strong>Data:</strong> <?= date('d/m/Y', strtotime($nota_detalhe['data_servico'])) ?><br>
                <strong>Veículo:</strong>
                <?= htmlspecialchars($nota_detalhe['marca_veiculo']) ?> <?= htmlspecialchars($nota_detalhe['modelo_veiculo']) ?>
                (<?= htmlspecialchars($nota_detalhe['ano_veiculo']) ?>) — placa <?= htmlspecialchars($nota_detalhe['placa_veiculo']) ?>

                <?php if ($eh_staff): ?>
                    <br><strong>Cliente:</strong> <?= htmlspecialchars($nota_detalhe['nome_clien']) ?>
                    <br><strong>Contato:</strong> <?= htmlspecialchars($nota_detalhe['telefone_clien']) ?>
                    — <?= htmlspecialchars($nota_detalhe['email_clien']) ?>
                <?php endif; ?>
            </p>

            <h5 class="mt-4">Serviços realizados</h5>
            <?php if (empty($detalhe_servicos)): ?>
                <p class="text-secondary">Nenhum serviço registrado nesta nota.</p>
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

            <h5 class="mt-4">Peças utilizadas</h5>
            <?php if (empty($detalhe_pecas)): ?>
                <p class="text-secondary">Nenhuma peça registrada nesta nota.</p>
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
                                    <td><?= (int)$p['quant_pecas'] ?></td>
                                    <td>R$ <?= number_format((float)$p['valor_unit_peca'], 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($p['valor_unit_peca'] * $p['quant_pecas'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <p class="mt-4 mb-0">
                Total: <span class="total-destaque">R$ <?= number_format((float)$nota_detalhe['valor_total'], 2, ',', '.') ?></span>
            </p>
        </div>

    <?php else: ?>

        <?php if ($eh_staff): ?>
            <!-- ---------- FORMULÁRIO (somente admin/funcionário) ---------- -->
            <div class="box mb-5">
                <h4 class="mb-4">Gerar nova nota</h4>

                <form method="post" action="notas.php" id="form-nota">
                    <input type="hidden" name="acao" value="criar_nota">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Cliente *</label>
                            <select name="id_clien" id="select-cliente" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= (int)$c['Id_clien'] ?>">
                                        <?= htmlspecialchars($c['nome_clien']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Veículo *</label>
                            <select name="id_veiculo" id="select-veiculo" class="form-select" required>
                                <option value="">Selecione o cliente primeiro</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="mb-3">Serviços realizados</h5>
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

                    <h5 class="mb-3">Peças utilizadas</h5>
                    <?php if (empty($pecas_disp)): ?>
                        <p class="text-secondary">Nenhuma peça ativa no estoque.</p>
                    <?php else: ?>
                        <div id="lista-pecas">
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
                                               max="<?= (int)$p['quant_peca'] ?>" value="0" placeholder="Qtd usada">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-red mt-4">
                        <i class="bi bi-check2-circle"></i> Gerar nota
                    </button>
                </form>
            </div>

            <script>
                const veiculosPorCliente = <?= json_encode($veiculos_por_cliente, JSON_UNESCAPED_UNICODE) ?>;

                document.getElementById('select-cliente').addEventListener('change', function () {
                    const selectVeiculo = document.getElementById('select-veiculo');
                    const veiculos = veiculosPorCliente[this.value] || [];

                    selectVeiculo.innerHTML = '';

                    if (veiculos.length === 0) {
                        selectVeiculo.innerHTML = '<option value="">Este cliente não tem veículos</option>';
                        return;
                    }

                    selectVeiculo.innerHTML = '<option value="">Selecione</option>';
                    veiculos.forEach(function (v) {
                        const texto = v.marca_veiculo + ' ' + v.modelo_veiculo + ' — ' + v.placa_veiculo;
                        selectVeiculo.innerHTML += '<option value="' + v.Id_veiculo + '">' + texto + '</option>';
                    });
                });
            </script>
        <?php endif; ?>

        <!-- ---------- LISTAGEM ---------- -->
        <div class="box">
            <h4 class="mb-4"><?= $eh_staff ? 'Todas as notas' : 'Minhas notas' ?></h4>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nota</th>
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
                        <?php if (empty($notas)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">
                                    Nenhuma nota encontrada.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($notas as $n): ?>
                            <tr>
                                <td>#<?= (int)$n['Id_nota'] ?></td>
                                <td><?= date('d/m/Y', strtotime($n['data_servico'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($n['marca_veiculo']) ?> <?= htmlspecialchars($n['modelo_veiculo']) ?>
                                    — <?= htmlspecialchars($n['placa_veiculo']) ?>
                                </td>
                                <?php if ($eh_staff): ?>
                                    <td><?= htmlspecialchars($n['nome_clien']) ?></td>
                                <?php endif; ?>
                                <td>R$ <?= number_format((float)$n['valor_total'], 2, ',', '.') ?></td>
                                <td class="text-end">
                                    <a href="notas.php?ver=<?= (int)$n['Id_nota'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
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