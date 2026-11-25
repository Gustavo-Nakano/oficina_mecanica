<?php

$host = 'localhost';
$banco = 'sistema_login';   // nome do seu banco
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
    if ($tipo === 'admin') {
        header('Location: /oficina_mecanica/admin/index.php');
    } else {
        header('Location: /oficina_mecanica/index.php');
    }
    exit;
}