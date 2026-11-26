<?php
require __DIR__ . '/../config/conn.php';

exigir_login();

$tipo = $_SESSION['usuario_tipo'];
$eh_cliente = $tipo === 'cliente';
$eh_staff = in_array($tipo, ['admin', 'funcionario'], true);
$erro = '';
$sucesso = '';

// ---------- AÇÕES (somente cliente, só nos próprios veículos) ----------
if ($eh_cliente && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';
    $id_clien = $_SESSION['usuario_id'];

    // Cadastrar veículo
    if ($acao === 'cadastrar') {
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
            mysqli_stmt_bind_param($stmt, 'issss', $id_clien, $ano, $marca, $modelo, $placa);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sucesso = 'Veículo cadastrado com sucesso!';
        }
    }

    // Editar veículo (somente se for do próprio cliente)
    if ($acao === 'editar') {
        $id_veiculo = (int)($_POST['id'] ?? 0);
        $marca = trim($_POST['marca_veiculo'] ?? '');
        $modelo = trim($_POST['modelo_veiculo'] ?? '');
        $ano = trim($_POST['ano_veiculo'] ?? '');
        $placa = trim($_POST['placa_veiculo'] ?? '');

        if ($marca === '' || $modelo === '' || $placa === '') {
            $erro = 'Preencha marca, modelo e placa do veículo.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE veiculos SET ano_veiculo = ?, marca_veiculo = ?, modelo_veiculo = ?, placa_veiculo = ?
                 WHERE Id_veiculo = ? AND Id_clien = ?'
            );
            mysqli_stmt_bind_param($stmt, 'ssssii', $ano, $marca, $modelo, $placa, $id_veiculo, $id_clien);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sucesso = 'Veículo atualizado com sucesso!';
        }
    }

    // Excluir veículo (somente se for do próprio cliente)
    if ($acao === 'excluir') {
        $id_veiculo = (int)($_POST['id'] ?? 0);

        $stmt = mysqli_prepare($conn, 'DELETE FROM veiculos WHERE Id_veiculo = ? AND Id_clien = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $id_veiculo, $id_clien);

        try {
            mysqli_stmt_execute($stmt);
            $sucesso = 'Veículo excluído com sucesso!';
        } catch (mysqli_sql_exception $e) {
            // Acontece quando o veículo já tem nota de serviço vinculada (ON DELETE RESTRICT)
            $erro = 'Este veículo não pode ser excluído porque já possui notas de serviço registradas.';
        }
        mysqli_stmt_close($stmt);
    }
}

// ---------- LISTAGEM ----------
if ($eh_cliente) {
    $id_clien = $_SESSION['usuario_id'];
    $stmt = mysqli_prepare($conn, 'SELECT * FROM veiculos WHERE Id_clien = ? ORDER BY marca_veiculo');
    mysqli_stmt_bind_param($stmt, 'i', $id_clien);
    mysqli_stmt_execute($stmt);
    $veiculos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    // Admin e funcionário veem todos os veículos, com o dono de cada um
    $resultado = mysqli_query(
        $conn,
        'SELECT v.*, c.nome_clien, c.telefone_clien
         FROM veiculos v
         JOIN cliente c ON c.Id_clien = v.Id_clien
         ORDER BY c.nome_clien, v.marca_veiculo'
    );
    $veiculos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
}

// Se for edição, busca os dados do veículo (só o cliente edita, e só o próprio)
$veiculo_editar = null;
if ($eh_cliente && isset($_GET['editar'])) {
    $id_edit = (int)$_GET['editar'];
    $id_clien = $_SESSION['usuario_id'];

    $stmt = mysqli_prepare($conn, 'SELECT * FROM veiculos WHERE Id_veiculo = ? AND Id_clien = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $id_edit, $id_clien);
    mysqli_stmt_execute($stmt);
    $veiculo_editar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Veículos - Oficina Mecânica</title>

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
        <i class="bi bi-car-front-fill"></i>
        Veículos
    </h2>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if ($eh_cliente): ?>
        <!-- ---------- FORMULÁRIO (somente cliente) ---------- -->
        <div class="box mb-5">
            <h4 class="mb-4"><?= $veiculo_editar ? 'Editar veículo' : 'Cadastrar novo veículo' ?></h4>

            <form method="post" action="veiculo.php" class="row g-3">
                <input type="hidden" name="acao" value="<?= $veiculo_editar ? 'editar' : 'cadastrar' ?>">
                <?php if ($veiculo_editar): ?>
                    <input type="hidden" name="id" value="<?= (int)$veiculo_editar['Id_veiculo'] ?>">
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label">Marca *</label>
                    <input type="text" name="marca_veiculo" class="form-control" required
                           value="<?= htmlspecialchars($veiculo_editar['marca_veiculo'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modelo *</label>
                    <input type="text" name="modelo_veiculo" class="form-control" required
                           value="<?= htmlspecialchars($veiculo_editar['modelo_veiculo'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ano</label>
                    <input type="text" name="ano_veiculo" class="form-control" placeholder="Ex: 2020"
                           value="<?= htmlspecialchars($veiculo_editar['ano_veiculo'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Placa *</label>
                    <input type="text" name="placa_veiculo" class="form-control" required
                           value="<?= htmlspecialchars($veiculo_editar['placa_veiculo'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-red w-100">
                        <?= $veiculo_editar ? 'Salvar' : 'Adicionar' ?>
                    </button>
                </div>

                <?php if ($veiculo_editar): ?>
                    <div class="col-12">
                        <a href="veiculo.php" class="text-secondary">Cancelar edição</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <!-- ---------- LISTAGEM ---------- -->
    <div class="box">
        <h4 class="mb-4"><?= $eh_cliente ? 'Meus veículos' : 'Todos os veículos' ?></h4>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Ano</th>
                        <th>Placa</th>
                        <?php if ($eh_staff): ?>
                            <th>Dono</th>
                            <th>Contato</th>
                        <?php endif; ?>
                        <?php if ($eh_cliente): ?>
                            <th class="text-end">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($veiculos)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                Nenhum veículo encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($veiculos as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['marca_veiculo']) ?></td>
                            <td><?= htmlspecialchars($v['modelo_veiculo']) ?></td>
                            <td><?= htmlspecialchars($v['ano_veiculo']) ?></td>
                            <td><?= htmlspecialchars($v['placa_veiculo']) ?></td>

                            <?php if ($eh_staff): ?>
                                <td><?= htmlspecialchars($v['nome_clien']) ?></td>
                                <td><?= htmlspecialchars($v['telefone_clien']) ?></td>
                            <?php endif; ?>

                            <?php if ($eh_cliente): ?>
                                <td class="text-end">
                                    <a href="veiculo.php?editar=<?= (int)$v['Id_veiculo'] ?>"
                                       class="btn btn-sm btn-outline-light me-1" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form method="post" action="veiculo.php" class="d-inline"
                                          onsubmit="return confirm('Excluir este veículo permanentemente?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$v['Id_veiculo'] ?>">
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

    <?php include __DIR__ . '/../includes/footer.php'; ?>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"
></script>
</body>
</html>