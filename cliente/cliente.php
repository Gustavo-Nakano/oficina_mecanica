<!doctype html>
<html lang="pt-br" >
    <head>
        <title>GV Performance</title>
        <!-- Required meta tags -->
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <!-- Bootstrap CSS v5.3.8 -->
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
            crossorigin="anonymous"
        />

        <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />
        <style>
            body{
                background-image:
            linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.85)),
            url('/oficina_mecanica/src/bkg01.jpg');
            background-size: cover;
            background-position: center;
                background-attachment: fixed;
                background-color: #000000;
            }
            .navbar{
                background-color: #000000;
            }
            .card{
    position: relative;
    min-height: 330px;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    overflow: hidden;
    background-color: #333;
    background-size: cover;
    background-position: center;
    color: #fff;
    

            }
            .card-body{
            
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            height: 100%;
            padding: 1.5rem;
            text-align: left;
            }

             .icone-card {
             width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #fff;
            }
            .card-title{
            color: #fff;
            font-weight: 700;
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
            }
            .card-text{
            color: #d4d4d4;
            margin-bottom: 1.25rem;
            }
             .btn-red{
            background-color: #dc2626;
            color: #fff;
            border: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            min-width: 350px;
             }

            .card-agendamento{
            background-image:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.90)
                ),
                url('/oficina_mecanica/src/agendamento.png');    
            }
              .card-veiculo{
                
            background-image:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.90)
                ),
                url('/oficina_mecanica/src/veiculos.jpg');
        }
        .card-servico{
            background-image:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.90)
                ),
                url('/oficina_mecanica/src/servicos.jpg');
        }

           .hero{
    display: flex;
    align-items: center;
    padding: 3rem ;
    color: #fff;
} 
        </style>
    </head>

    <body>
        <header>
            <!-- place navbar here -->
            <nav class="navbar navbar-expand-lg navbar-dark">

<div class="container-fluid">

    <a class="navbar-brand fw-bold" href="/oficina_mecanica/cliente/cliente.php">
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
                <a class="nav-link" href="servicos.php">
                    Serviços
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="orcamento.php">
                    Orçamento
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                    Agendamentos
                </a>
            </li>

            <li class="nav-item dropdown ms-lg-4">

    <a class="nav-link dropdown-toggle perfil-link"
       href="#"
       role="button"
       data-bs-toggle="dropdown"
       aria-expanded="false">

        <i class="bi bi-person-circle"></i>

        <span>Olá, Cliente</span>

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
            <a class="dropdown-item text-danger" href="/oficina_mecanica/index.php">                <i class="bi bi-box-arrow-right me-2"></i>
                Sair
            </a>
        </li>

    </ul>

</li>

        </ul>

    </div>

</div>

</nav>
<main>
<section class="hero">
    <div class="container">
        <h1>Bem-Vindo(a)</h1>
    </div>
</section>
<section class="container-fluid my-5">
    
    <div class="row g-4">

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 card-agendamento">
                <div class="card-body">
                    <div class="icone-card">
                        <i class="bi bi-calendar2-check"></i>
                    </div>
                    <h3 class="card-title text-center">Agendamentos</h3>
                    <p class="card-text">Agende seus serviços de forma rápida e prática.</p>
                    <a href="/oficina_mecanica/cliente/agendamento.php" class="btn btn-red">Confira seus agendamentos
                    <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>



        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 card-veiculo">
                <div class="card-body">
                    <div class="icone-card">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <h3 class="card-title">Veiculos</h3>
                    <p class="card-text">Veja e gerencie os veículos cadastrados em sua conta</p>
                    <a href="/oficina_mecanica/cliente/veiculo.php" class="btn btn-red">
                        Informações sobre seu veículo <i class="bi bi-arrow-right"></i></a>
                    
                </div>
            </div>
        </div>

            
        <div class="col-12 col-md-6 offset-md-3 col-lg-4 offset-lg-0">
            <div class="card h-100 card-servico">
                <div class="card-body">
                    <div class="icone-card">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h3 class="card-title text-center">Serviços</h3>
                    <p class="card-text">Conheça nossos serviços.</p>
                    <a href="/oficina_mecanica/orcamento.php" class="btn btn-red">
                        Confira seus Serviços<i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

    </div>
</section>


</section>
        </main>
        <footer>
            <!-- place footer here -->
            <footer class="py-4">

<div class="container-fluid">

    <div class="row">

        <div class="col-md-6">

            <h5 class="text-secondary">
                <i class="bi bi-tools"></i>
                GV Performance
            </h5>

        </div>
        
        <div class="col-md-6">
                 <p class="text-end text-secondary mb-0">

        © 2026 Nakano & Kayque

             </p>

        </div>


        

    </div>

</div>

</footer>

        </footer>
        <!-- Bootstrap JavaScript Bundle (includes Popper) -->
        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
            crossorigin="anonymous"
        ></script>
    </body>
</html>
