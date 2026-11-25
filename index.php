<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GV Performance</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body {
            background-color: #f2f2f2;
        }

        .navbar {
            background-color: #000000;
        }

        .hero {
            background-color: #000000;
            color: white;
            padding: 100px 0;
            background-size: cover;
            background-position: center;
            background-image:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.90)
                ),
                url('/oficina_mecanica/src/bkg02.png');   
        }

        .hero span {
            color:#dc2626;
        }

        .btn-red {
            background-color: #dc2626;
            color: #ffffff;
            border: none;
        }

        .icon-servico {
            font-size: 45px;
            color: #dc2626;
        }

        .sobre{
            background-color: #0a0a0a;
            color: #ffffff;
            background-size: cover;
            background-position: center;
            background-image:
                linear-gradient(
                    rgba(0,0,0,0.55),
                    rgba(0,0,0,0.70)
                ),
                url('/oficina_mecanica/src/cadbkg01.jpg');    
        }
        .cards {
            background-color: #141414;
            background-size: cover;
            background-position: center;
            background-image:
                linear-gradient(
                    rgba(0,0,0,0.55),
                    rgba(0,0,0,0.70)
                ),
                url('/oficina_mecanica/src/sobrebkg01.jpg'); 
        }
        .card-title,
        .card-text{
            color: #ffffff;
        }

        footer {
            background-color: #000000;
            color: white;
        }
        .cad{
            background-size: cover;
            background-position: center;
            background-image:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.90)
                ),
                url('/oficina_mecanica/src/cardbk01.png');   
        }
        .card{
            border-radius: 12px;
        }
        .text-sub{
            color: #ffffff;
        }
        .text-title{
            color: #dc2626;
        }
        .card-serv1{
            background-size: cover;
    background-position: center;
    background-image:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.80)
                ),
                url('/oficina_mecanica/src/cardserv1.png');   
        }
        .card-serv2{
            background-size: cover;
    background-position: center;
    background-image:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.80)
                ),
                url('/oficina_mecanica/src/cardserv2.png');   
        }
        .card-serv3{
            background-size: cover;
    background-position: center;
    background-image:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.80)
                ),
                url('/oficina_mecanica/src/cardserv3.png');   
        }

    </style>

</head>

<body>


<!-- NAVBAR -->

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
                    <a class="nav-link active" href="index.php">
                        Início
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="cadastro.php">
                        Orçamento
                    </a>
                </li>

                <li class="nav-item">
                    <a class="btn btn-secondary" href="login.php">
                        Entrar
                    </a>
                </li>

                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">

                    <a class="btn btn-red" href="cadastro.php">
                        Cadastre-se
                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- HERO -->

<section class="hero">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-7">

                <h1 class="display-4 fw-bold">

                    Seu carro em
                    <span>boas mãos.</span>

                </h1>

                <p class="lead mt-3">

                    Serviços de manutenção e cuidados
                    para o seu veículo com qualidade,
                    segurança e confiança.

                </p>

                <a
                    href="cadastro.php"
                    class="btn btn-red btn-lg mt-3">

                    <i class="bi bi-person-plus"></i>
                    Criar minha conta

                </a>

                <a
                    href="cadastro.php"
                    class="btn btn-outline-light btn-lg mt-3">

                    Solicitar orçamento

                </a>

            </div>


            <div class="col-lg-5 text-center mt-5 mt-lg-0">

                
            </div>

        </div>

    </div>

</section>


<!-- SERVIÇOS -->

<section class="py-5 cards">

    <div class="container">

        <div class="text-center mb-5">

            <h2 class="fw-bold text-title">
                Nossos Serviços
            </h2>

            <p class="text-sub">
                Alguns dos serviços oferecidos pela nossa oficina.
            </p>

        </div>


        <div class="row g-4">


            <!-- SERVIÇO 1 -->

            <div class="col-md-4">

                <div class="card h-100 card-serv1 shadow-sm">

                    <div class="card-body text-center">

                        <i class="bi bi-gear-fill icon-servico"></i>

                        <h4 class="card-title mt-3">
                            Manutenção 
                        </h4>

                        <p class="card-text">

                            Manutenção preventiva e corretiva
                            para manter o veículo em boas condições.

                        </p>

                        <a
                            href="cadastro.php"
                            class="btn btn-outline-danger">

                            Ver serviços

                        </a>

                    </div>

                </div>

            </div>


            <!-- SERVIÇO 2 -->

            <div class="col-md-4">

                <div class="card h-100 card-serv2 shadow-sm">

                    <div class="card-body text-center">

                        <i class="bi bi-car-front-fill icon-servico"></i>

                        <h4 class="card-title mt-3">
                            Revisão
                        </h4>

                        <p class="card-text ">

                            Verificação dos principais componentes
                            do veículo para garantir seu funcionamento.

                        </p>

                        <a
                            href="cadastro.php"
                            class="btn btn-outline-danger">

                            Ver serviços

                        </a>

                    </div>

                </div>

            </div>


            <!-- SERVIÇO 3 -->

            <div class="col-md-4">

                <div class="card h-100 card-serv3 shadow-sm">

                    <div class="card-body text-center">

                        <i class="bi bi-disc-fill icon-servico"></i>

                        <h4 class="card-title mt-3">
                            Remap e codificação
                        </h4>

                        <p class="card-text">

                            Calibração da ECU para mais potência, 
                            ou codificação de acessórios e funções.

                        </p>

                        <a
                            href="cadastro.php"
                            class="btn btn-outline-danger">

                            Ver serviços

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- CADASTRO -->
 <section class="cad text-white py-5">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-md-8">

                <h2 class="fw-bold">
                    Ainda não possui uma conta?
                </h2>

                <p class="text-secondary mb-md-0">

                    Cadastre-se para cadastrar seus veículos,
                    realizar agendamentos e solicitar orçamentos.

                </p>

            </div>

            <div class="col-md-4 text-md-end">

                <a
                    href="cadastro.php"
                    class="btn btn-red btn-lg">

                    <i class="bi bi-person-plus"></i>
                    Cadastre-se

                </a>

            </div>

        </div>

    </div>

</section>


<!-- SOBRE -->

<section class="sobre py-5">

    <div class="container">

        <div class="row align-items-center">


            <div class="col-md-5 text-center mb-4 mb-md-0">

                <i
                    class="bi bi-building-gear"
                    style="font-size: 120px; color: #dc3545;">
                </i>

            </div>


            <div class="col-md-7">

                <h2 class="fw-bold">
                    Sobre a empresa
                </h2>

                <p>

                    Somos uma oficina mecânica dedicada
                    à manutenção e aos cuidados com veículos.

                </p>

                <p>

                    Nosso objetivo é oferecer um atendimento
                    organizado, transparente e eficiente,
                    buscando sempre facilitar o atendimento
                    aos nossos clientes.

                </p>


            </div>

        </div>

    </div>

</section>

<!-- FOOTER -->

<footer class="py-4">

    <div class="container-fluid">

        <div class="row">

            <div class="col-md-4">

                <h5>
                    <i class="bi bi-tools"></i>
                    GV Performance
                </h5>
            

                

            </div>
            
            <div class="col-md-4">
                     <p class="text-center text-secondary mb-0">

            © 2026 Nakano & Kayque

                 </p>

            </div>


            <div class="col-md-4 text-md-end">

                <a
                    href="login.php"
                    class="text-white text-decoration-none me-3">

                    Entrar

                </a>

                <a
                    href="cadastro.php"
                    class="text-white text-decoration-none">

                    Cadastre-se

                </a>

            </div>

        </div>


       


       
    </div>

</footer>


<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>