<?php

require __DIR__ . '/config/conn.php';

if (isset($_POST['entrar'])) {

    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    $usuario = mysqli_fetch_assoc($resultado);
    mysqli_smt_close($stmt);

    if ($usuario && password_verify($senha, $usuario['senha'])) {

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];

        if ($usuario['tipo'] === 'admin') {
            header('Location: admin/admin.php');
            } elseif ($usuario['tipo'] === 'funcionario') {
            header('Location: funcionario/funcionario.php');
            }elseif ($usuario['tipo'] === 'cliente') {
            header('Location: cliente/cliente.php');
            }
             else {
             header('Location: index.php');
            }
        exit;

    } else {

        $erro = "E-mail ou senha incorretos.";

    }

    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>GV Performance</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
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

    .card{
        background: #222; 
        padding: 2rem; 
        border-radius: 12px;
    color: #fff;
   
    
    }
</style>
</head>
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
                    <a class="nav-link" href="cadastro.php">
                        Cadastre-se
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="login.php">
                        Entrar
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>
</header>

<div class="container mt-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card p-4">

                <h3 class="text-center mb-4">Login</h3>

                <?php if (isset($erro)) { ?>

                    <div class="alert alert-danger">
                        <?= htmlspecialchars($erro) ?>
                    </div>

                <?php } ?>

                <form method="POST">

                    <div class="mb-3">
                        <label>E-mail</label>
                        <input type="email" name="email"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Senha</label>
                        <input type="password" name="senha"
                               class="form-control" required>
                    </div>

                    <button type="submit" name="entrar"
                            class="btn btn-success w-100">
                        Entrar
                    </button>

                </form>

                <p class="text-center mt-3">
                    Não possui uma conta?
                    <a href="cadastro.php">Cadastrar</a>
                </p>

            </div>

        </div>

    </div>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>