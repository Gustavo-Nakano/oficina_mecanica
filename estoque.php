<?php
require __DIR__ . '/config/conn.php';

// Só admin e funcionário acessam esta página
exigir_tipo(['admin', 'funcionario']);

$eh_admin = $_SESSION['usuario_tipo'] === 'admin';
$erro = '';
$sucesso = '';

// ---------- AÇÕES (somente admin) ----------
if ($eh_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    // Cadastrar nova peça
    if ($acao === 'cadastrar') {
        $nome = trim($_POST['nome_peca'] ?? '');
        $partnumber = trim($_POST['partnumber'] ?? '');
        $quantidade = (int)($_POST['quant_peca'] ?? 0);
        $valor = str_replace(',', '.', trim($_POST['valor_peca'] ?? '0'));

        if ($nome === '' || $partnumber === '') {
            $erro = 'Preencha o nome e o partnumber da peça.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO estoque (quant_peca, partnumber, Nome_peca, valor_peca, estoque)
                 VALUES (?, ?, ?, ?, 1)'
            );
            mysqli_stmt_bind_param($stmt, 'issd', $quantidade, $partnumber, $nome, $valor);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sucesso = 'Peça cadastrada com sucesso!';
        }
    }

    // Editar peça existente
    if ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome_peca'] ?? '');
        $partnumber = trim($_POST['partnumber'] ?? '');
        $quantidade = (int)($_POST['quant_peca'] ?? 0);
        $valor = str_replace(',', '.', trim($_POST['valor_peca'] ?? '0'));

        $stmt = mysqli_prepare(
            $conn,
            'UPDATE estoque SET quant_peca = ?, partnumber = ?, Nome_peca = ?, valor_peca = ?
             WHERE Id_peca = ?'
        );
        mysqli_stmt_bind_param($stmt, 'issdi', $quantidade, $partnumber, $nome, $valor, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sucesso = 'Peça atualizada com sucesso!';
    }

    // Ativar / desativar peça
    if ($acao === 'alternar_status') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = mysqli_prepare($conn, 'UPDATE estoque SET ativo = NOT ativo WHERE Id_peca = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sucesso = 'Status da peça atualizado!';
    }

    // Excluir peça
    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = mysqli_prepare($conn, 'DELETE FROM estoque WHERE Id_peca = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sucesso = 'Peça excluída com sucesso!';
    }
}

// ---------- LISTAGEM ----------
// Funcionário só vê peças ativas; admin vê todas
if ($eh_admin) {
    $resultado = mysqli_query($conn, 'SELECT * FROM estoque ORDER BY Nome_peca');
} else {
    $resultado = mysqli_query($conn, 'SELECT * FROM estoque WHERE ativo = 1 ORDER BY Nome_peca');
}
$pecas = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

// Se for edição, busca os dados da peça para preencher o formulário
$peca_editar = null;
if ($eh_admin && isset($_GET['editar'])) {
    $id_edit = (int)$_GET['editar'];
    $stmt = mysqli_prepare($conn, 'SELECT * FROM estoque WHERE Id_peca = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id_edit);
    mysqli_stmt_execute($stmt);
    $peca_editar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estoque - Oficina Mecânica</title>

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

        table{
            color: #fff;
        }
        thead{
            background-color: #333;
        }
        tbody tr{
            background-color: #1a1a1a;
        }
        .peca-inativa{
            opacity: 0.5;
        }
        .badge-ativo{ background-color: #16a34a; }
        .badge-inativo{ background-color: #6b7280; }
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
                        <a class="nav-link active" href="/oficina_mecanica/estoque.php">Estoque</a>
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
        <i class="bi bi-box-seam"></i>
        Estoque de Peças
    </h2>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if ($eh_admin): ?>
        <!-- ---------- FORMULÁRIO (somente admin) ---------- -->
        <div class="box mb-5">
            <h4 class="mb-4"><?= $peca_editar ? 'Editar peça' : 'Cadastrar nova peça' ?></h4>

            <form method="post" action="estoque.php" class="row g-3">
                <input type="hidden" name="acao" value="<?= $peca_editar ? 'editar' : 'cadastrar' ?>">
                <?php if ($peca_editar): ?>
                    <input type="hidden" name="id" value="<?= (int)$peca_editar['Id_peca'] ?>">
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label">Nome da peça *</label>
                    <input type="text" name="nome_peca" class="form-control" required
                           value="<?= htmlspecialchars($peca_editar['Nome_peca'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Partnumber *</label>
                    <input type="text" name="partnumber" class="form-control" required
                           value="<?= htmlspecialchars($peca_editar['partnumber'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Quantidade</label>
                    <input type="number" min="0" name="quant_peca" class="form-control"
                           value="<?= htmlspecialchars($peca_editar['quant_peca'] ?? 0) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Valor (R$)</label>
                    <input type="text" name="valor_peca" class="form-control" placeholder="Ex: 49.90"
                           value="<?= htmlspecialchars($peca_editar['valor_peca'] ?? '') ?>">
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-red w-100">
                        <?= $peca_editar ? 'Salvar' : 'Add' ?>
                    </button>
                </div>

                <?php if ($peca_editar): ?>
                    <div class="col-12">
                        <a href="estoque.php" class="text-secondary">Cancelar edição</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <!-- ---------- LISTAGEM ---------- -->
    <div class="box">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Partnumber</th>
                        <th>Nome</th>
                        <th>Quantidade</th>
                        <th>Valor</th>
                        <?php if ($eh_admin): ?>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pecas)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                Nenhuma peça cadastrada.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pecas as $peca): ?>
                        <tr class="<?= $peca['ativo'] ? '' : 'peca-inativa' ?>">
                            <td><?= htmlspecialchars($peca['partnumber']) ?></td>
                            <td><?= htmlspecialchars($peca['Nome_peca']) ?></td>
                            <td><?= (int)$peca['quant_peca'] ?></td>
                            <td>R$ <?= number_format((float)$peca['valor_peca'], 2, ',', '.') ?></td>

                            <?php if ($eh_admin): ?>
                                <td>
                                    <?php if ($peca['ativo']): ?>
                                        <span class="badge badge-ativo">Ativa</span>
                                    <?php else: ?>
                                        <span class="badge badge-inativo">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="estoque.php?editar=<?= (int)$peca['Id_peca'] ?>"
                                       class="btn btn-sm btn-outline-light me-1" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form method="post" action="estoque.php" class="d-inline">
                                        <input type="hidden" name="acao" value="alternar_status">
                                        <input type="hidden" name="id" value="<?= (int)$peca['Id_peca'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning me-1"
                                                title="<?= $peca['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>

                                    <form method="post" action="estoque.php" class="d-inline"
                                          onsubmit="return confirm('Excluir esta peça permanentemente?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$peca['Id_peca'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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