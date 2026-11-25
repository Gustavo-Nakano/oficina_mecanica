<?php

$host = 'localhost';
$banco = 'oficina';   // nome do seu banco
$usuario = 'root';             // seu usuário do MySQL
$senha = '';                   // sua senha do MySQL
$conn = mysqli_connect($host, $usuario, $senha, $banco); // Conexão com o banco de dados

if (!$conn) {
    die('Erro ao conectar ao banco de dados.');
}

mysqli_set_charset($conn, 'utf8mb4');
session_start();

// Bloqueia a página para quem não está logado
function exigir_login(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /oficina_mecanica/login.php');
        exit;
    }
}

// Bloqueia a página para perfis que não podem acessá-la
function exigir_tipo(array $tipos): void
{
    exigir_login();
    if (!in_array($_SESSION['usuario_tipo'], $tipos, true)) {
        header('Location: /oficina_mecanica/index.php');
        exit;
    }
}

function redirecionar_por_tipo(string $tipo): void
{
     if($tipo === 'admin') {
        header('Location: /oficina_mecanica/admin/admin.php');
    } elseif($tipo === 'funcionario') {
        header('Location: /oficina_mecanica/funcionario/funcionario.php');
    }elseif($tipo === 'cliente') {
        header('Location: /oficina_mecanica/cliente/cliente.php');
    } 
    else {
        header('Location: /oficina_mecanica/index.php');
    }
    exit;
}

function buscar_usuario_por_email(mysqli $conn, string $email): ?array
{
    $consultas = [
        ['admin', 'SELECT Id_admin AS id, nome_adm AS nome, senha_adm AS senha
                   FROM admin WHERE email_adm = ?'],
        ['funcionario', 'SELECT Id_fun AS id, nome_fun AS nome, senha_fun AS senha
                         FROM funcionarios WHERE email_fun = ?'],
        ['cliente', 'SELECT Id_clien AS id, nome_clien AS nome, senha_clien AS senha
                     FROM cliente WHERE email_clien = ?'],
    ];

    foreach ($consultas as [$tipo, $sql]) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $linha = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($linha) {
            $linha['tipo'] = $tipo;
            return $linha;
        }
    }

    return null;
}