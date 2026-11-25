<?php
require __DIR__ . '/../config/conn.php';

// Só admin acessa esta página
exigir_tipo(['admin']);

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $salario = str_replace(',', '.', trim($_POST['salario'] ?? '0'));
    $horario = trim($_POST['horario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma'] ?? '';

    if ($nome === '' || $email === '' || $senha === '') {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } elseif (buscar_usuario_por_email($conn, $email)) {
        $erro = 'Este e-mail já está cadastrado.';
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO funcionarios (nome_fun, email_fun, tel_fun, salario_fun, horario_fun, senha_fun)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'sssdss', $nome, $email, $telefone, $salario, $horario, $hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sucesso = 'Funcionário cadastrado com sucesso!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar Funcionário - Oficina Mecânica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-image:
            linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.85)),
            url('/oficina_mecanica/src/bkg03.jpg');
            background-size: cover;
            background-position: center;
                background-attachment: fixed;
                background-color: #000000;   
        }
        .navbar{
        background-color: #000000;
    }
        .box { max-width: 500px; margin: 60px auto;
         background: #222; padding: 2rem; border-radius: 12px;
        color: #fff; }
        .btn-red { background-color: #dc2626; color: #fff; border: none; width: 100%; }
        .btn-red:hover { background-color: #b91c1c; color: #fff; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">

    <div class="container-fluid">

        <a class="navbar-brand fw-bold" href="oficina_mecanica/admin/admin.php">
            <i class="bi bi-tools"></i>
            GV Performance
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menu">

            <span class="navbar-toggler-icon"></span>

        </button>


        <div class="collapse navbar-collapse" id="menu">

            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a class="nav-link" href="/oficina_mecanica/admin/admin.php">
                        Início
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/oficina_mecanica/admin/dashboard.php">
                        dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/oficina_mecanica/orcamentos.php">
                        orçamentos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/oficina_mecanica/clientes.php">
                        agendamentos
                    </a>
                </li>

                <li class="nav-item">

                    <a class="nav-link" href="/oficina_mecanica/estoque.php">
                        estoque
                    </a>

                    </li>
                    
                    <li class="nav-item">

                    <a class="nav-link" href="/oficina_mecanica/servicos.php">
                        serviços
                    </a>

                    </li>

                    <li class="nav-item">

                    <a class="nav-link" href="/oficina_mecanica/notas.php">
                        notas
                    </a>

                    </li>

                    <li class="nav-item">

                    <a class="nav-link active" href="/oficina_mecanica/admin/cadastro_funcionarios.php">
                        funcionários 
                    </a>

                    </li>

               
<li class="nav-item dropdown ms-lg-4">

    <a class="nav-link dropdown-toggle perfil-link"
       href="#"
       role="button"
       data-bs-toggle="dropdown"
       aria-expanded="false">

        <i class="bi bi-person-circle"></i>

        <span>Olá, Admin</span>

    </a>

    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">

        <li>
            <a class="dropdown-item" href="#">
                <i class="bi bi-person me-2"></i>
                Meu Perfil
            </a>
        </li>

        <li>
            <a class="dropdown-item" href="#">
                <i class="bi bi-gear me-2"></i>
                Configurações
            </a>
        </li>

        <li>
            <hr class="dropdown-divider">
        </li>

        <li>
            <a class="dropdown-item text-danger" href="/oficina_mecanica/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>
                Sair
            </a>
        </li>

    </ul>

</li>
            </ul>

        </div>

    </div>

</nav>
<div class="container">
    <div class="box">
        <h3 class="mb-4 text-center">Cadastrar funcionário</h3>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="post" action="cadastro_funcionarios.php">
            <div class="mb-3">
                <label class="form-label">Nome *</label>
                <input type="text" name="nome" class="form-control" required
                       value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">E-mail *</label>
                <input type="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Telefone</label>
                <input type="text" name="telefone" class="form-control"
                       value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Salário</label>
                <input type="text" name="salario" class="form-control" placeholder="Ex: 2500.00"
                       value="<?= htmlspecialchars($_POST['salario'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Horário de trabalho</label>
                <input type="text" name="horario" class="form-control" placeholder="Ex: 08:00 às 17:00"
                       value="<?= htmlspecialchars($_POST['horario'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Senha *</label>
                <input type="password" name="senha" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirmar senha *</label>
                <input type="password" name="confirma" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-red mb-3">Cadastrar funcionário</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>