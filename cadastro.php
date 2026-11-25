<?php
require __DIR__ . '/config/conn.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
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

        // Se não existe nenhum admin, o primeiro cadastro vira admin
        $resultado = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM admin');
        $total_admins = mysqli_fetch_assoc($resultado)['total'];

        if ($total_admins == 0) {
            $tipo = 'admin';
            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO admin (nome_adm, email_adm, tel_adm, senha_adm) VALUES (?, ?, ?, ?)'
            );
        } else {
            $tipo = 'cliente';
            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO cliente (nome_clien, email_clien, telefone_clien, senha_clien) VALUES (?, ?, ?, ?)'
            );
        }

        mysqli_stmt_bind_param($stmt, 'ssss', $nome, $email, $telefone, $hash);
        mysqli_stmt_execute($stmt);
        $novo_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Entra automaticamente
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $novo_id;
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_tipo'] = $tipo;

        redirecionar_por_tipo($tipo);
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GV Performance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <style>

        body{background-image:
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
        
        .box { max-width: 650px; margin: 60px auto; 
        background: #222; 
        padding: 2rem; 
        border-radius: 12px;
    color: #fff;}
        
        .btn-red { background-color: #dc2626; 
        color: #fff; 
        border: none; 
        width: 100%; }
        
        .btn-red:hover { background-color: #b91c1c;
        color: #fff; }

        .btn-entrar { background-color: #dc2626;
            color: #ffffff;
            border: none;}
    </style>
</head>
<body>

<header>
    
<nav class="navbar navbar-expand-lg navbar-dark">

    <div class="container-fluid">

        <a class="navbar-brand fw-bold" href="index.php">
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
                    <a class="nav-link" href="index.php">
                        Início
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="cadastro.php">
                        Cadastre-se
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="login.php">
                        Entrar
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>
</header>

<div class="container">
    <div class="box sm-12 md-5 lg-6">
        <h3 class="mb-4 text-center">Criar conta</h3>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="cadastro.php">
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
                <label class="form-label">Senha *</label>
                <input type="password" name="senha" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirmar senha *</label>
                <input type="password" name="confirma" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-red mb-3">Cadastrar</button>
        </form>

        <p class="text-center mb-0">
            Já possui uma conta? <a href="login.php">Entrar</a>
        </p>
    </div>
</div>

                <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>