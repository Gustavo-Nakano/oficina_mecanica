<?php
require __DIR__ . '/config/conn.php';

// Qualquer usuário logado pode ver a lista; só admin pode cadastrar/excluir
exigir_login();

$eh_admin = $_SESSION['usuario_tipo'] === 'admin';
$erro = '';
$sucesso = '';

// ---------- AÇÕES (somente admin) ----------
if ($eh_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    // Cadastrar novo serviço
    if ($acao === 'cadastrar') {
        $nome = trim($_POST['nome_servico'] ?? '');
        $descricao = trim($_POST['descricao_servico'] ?? '');

        if ($nome === '') {
            $erro = 'Informe o nome do serviço.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO servicos (nome_servico, descricao_servico) VALUES (?, ?)'
            );
            mysqli_stmt_bind_param($stmt, 'ss', $nome, $descricao);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sucesso = 'Serviço cadastrado com sucesso!';
        }
    }

    // Excluir serviço
    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = mysqli_prepare($conn, 'DELETE FROM servicos WHERE Id_servico = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);

        try {
            mysqli_stmt_execute($stmt);
            $sucesso = 'Serviço excluído com sucesso!';
        } catch (mysqli_sql_exception $e) {
            // Acontece quando o serviço já foi usado em algum orçamento ou nota
            $erro = 'Este serviço não pode ser excluído porque já está sendo usado em algum orçamento ou nota.';
        }
        mysqli_stmt_close($stmt);
    }
}

// ---------- LISTAGEM (todos os logados) ----------
$resultado = mysqli_query($conn, 'SELECT * FROM servicos ORDER BY nome_servico');
$servicos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Serviços - Oficina Mecânica</title>

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
        <i class="bi bi-wrench-adjustable"></i>
        Serviços
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
            <h4 class="mb-4">Cadastrar novo serviço</h4>

            <form method="post" action="servicos.php" class="row g-3">
                <input type="hidden" name="acao" value="cadastrar">

                <div class="col-md-4">
                    <label class="form-label">Nome do serviço *</label>
                    <input type="text" name="nome_servico" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Descrição</label>
                    <input type="text" name="descricao_servico" class="form-control"
                           placeholder="Ex: Troca de óleo do motor e filtro">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-red w-100">Cadastrar</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- ---------- LISTAGEM (todos os logados) ---------- -->
    <div class="box">
        <h4 class="mb-4">Serviços oferecidos</h4>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <?php if ($eh_admin): ?>
                            <th class="text-end">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($servicos)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">
                                Nenhum serviço cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($servicos as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['nome_servico']) ?></td>
                            <td><?= htmlspecialchars($s['descricao_servico'] ?: '—') ?></td>

                            <?php if ($eh_admin): ?>
                                <td class="text-end">
                                    <form method="post" action="servicos.php" class="d-inline"
                                          onsubmit="return confirm('Excluir este serviço permanentemente?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$s['Id_servico'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Excluir
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

    <?php include __DIR__ . '/includes/footer.php'; ?>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"
></script>
</body>
</html>