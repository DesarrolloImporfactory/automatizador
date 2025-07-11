<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nombre_tabla_plural; ?> Automatizador Imporsuit</title>
    <link rel="icon" type="image/png" href="https://new.imporsuitpro.com//public/img/favicon_automatizador.png">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .container-fluid {
            flex: 1;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            transition: transform 0.3s ease;
        }

        .sidebar-collapsed {
            transform: translateX(-100%);
        }

        .navbar {
            box-shadow: 0 1px 0 rgba(0, 0, 0, .1);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }

        .toggle-btn {
            cursor: pointer;
        }

        .section-title {
            margin-top: 20px;
            /* Espacio superior */
            margin-bottom: 10px;
            /* Espacio inferior */
            margin-left: 5px;
        }

        .section-content {
            margin-bottom: 20px;
            /* Espacio inferior entre el contenido de la sección y el siguiente título */

        }
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #0f112b;">
        <a class="navbar-brand ml-3 text-white" href="#">Automatizador Imporsuit</a>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center me-3">
                <li class="nav-item">
                    <form action="logout.php" method="POST" class="d-inline">
                        <button type="submit" class="btn btn-sm d-flex align-items-center px-3 py-1" style="background-color: #2d2f4b; color: #fff; border: none; border-radius: 6px;">
                            <i class="fas fa-arrow-right-from-bracket me-2" style="font-size: 14px;"></i>
                            <span style="font-size: 14px;">Cerrar sesión</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <!-- <div class="d-flex">
        <div class="sidebar bg-light p-3 sidebar-collapsed" id="sidebar">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Menú</h4>
                <button class="btn btn-light toggle-btn" id="sidebarClose"><i class="fas fa-arrow-left"></i></button>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="tabla_configuraciones.php">Configuraciones</a>
                </li>
            </ul>
        </div>
    </div> -->

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            var sidebar = document.getElementById('sidebar');
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active');
            } else {
                sidebar.classList.toggle('sidebar-collapsed');
            }
        });

        document.getElementById('sidebarClose').addEventListener('click', function() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        });

        window.addEventListener('resize', function() {
            var sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                sidebar.classList.remove('sidebar-collapsed');
            }
        });
    </script>