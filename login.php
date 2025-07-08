<?php
session_start();

// Comprobar si la sesión o la cookie ya existen y redirigir si es así
if (isset($_SESSION['id_plataforma'])) {
    header("Location: constructor_automatizador.php");
    exit();
}

if (isset($_COOKIE["id_plataforma"])) {
    $_SESSION["id_plataforma"] = $_COOKIE["id_plataforma"];
    header("Location: constructor_automatizador.php");
    exit();
}
// Procesar el formulario si se ha enviado
if (isset($_POST['email'])) {
    $servername = "3.233.119.65";
    $dbname = "imporsuitpro_new";
    $username = "imporsuit_system"; // Usuario por defecto de XAMPP
    $password = "imporsuit_system"; // Sin contraseña por defecto

    $conn = new mysqli($servername, $username, $password, $dbname);


    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        //echo "Connection successful!";
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prevenir inyección SQL utilizando consultas preparadas
    $stmt = $conn->prepare("SELECT * FROM users WHERE email_users = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verificar contraseña
        if (password_verify($password, $row['con_users']) || password_verify($password, $row['admin_pass'])) {

            // Obtener la plataforma del usuario
            $stmt = $conn->prepare("SELECT * FROM usuario_plataforma WHERE id_usuario = ?");
            $stmt->bind_param('i', $row['id_users']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();


            // Guardar en la sesión y cookie
            $_SESSION['id_plataforma'] = $row['id_plataforma'];
            setcookie('id_plataforma', $row['id_plataforma'], time() + 60 * 60 * 24 * 30, "/", "", true, true); // Cookie segura (HTTPOnly y Secure)

            // Si el usuario selecciona 'Recordar', guardar una cookie adicional
            if (isset($_POST['remember'])) {
                setcookie('id_plataforma', $row['id_plataforma'], time() + 60 * 60 * 24 * 30, "/", "", true, true);
            }

            $stmt_configuracion = $conn->prepare("SELECT * FROM configuraciones WHERE id_plataforma = ?");
            $stmt_configuracion->bind_param('i', $_SESSION['id_plataforma']);
            $stmt_configuracion->execute();
            $result_configuracion = $stmt_configuracion->get_result();
            $row_configuracion = $result_configuracion->fetch_assoc();
            $id_configuracion = $row_configuracion['id'];

            // Redirigir al constructor
            header("Location: tabla_automatizadores.php?id_configuracion=". $id_configuracion);
            exit();
        } else {
            echo "Contraseña incorrecta";
        }
    } else {
        echo "Usuario no encontrado";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/png" href="https://new.imporsuitpro.com//public/img/favicon_automatizador.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
  .particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background-color: rgba(255, 255, 255, 0.06);
    border-radius: 9999px;
    animation: rise linear infinite;
  }

  @keyframes rise {
    0% {
      transform: translateY(0) scale(1);
      opacity: 0;
    }
    10% {
      opacity: 0.2;
    }
    50% {
      opacity: 0.1;
    }
    100% {
      transform: translateY(-120vh) scale(1);
      opacity: 0;
    }
  }
  canvas {
    display: block;
  }

  @keyframes fadeInUp {
    0% {
      opacity: 0;
      transform: translateY(30px);
    }
    100% {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .animate-fade-in-up {
    animation: fadeInUp 1s ease-out forwards;
  }

  .animate-fade-in-up-delayed {
    animation: fadeInUp 1s ease-out forwards;
    animation-delay: 1.2s;
  }

  .opacity-0 {
    opacity: 0;
  }
  
</style>
</head>


<body class="bg-black min-h-screen w-full flex flex-col lg:flex-row">
  <!-- Panel izquierdo -->
    <div id="panel-izquierdo" class="relative w-full lg:w-1/2 flex flex-col justify-center items-center text-white bg-[#171931] p-10 overflow-hidden">
  <!-- canvas partículas -->
  <canvas id="particles-canvas" class="absolute inset-0 w-full h-full z-0"></canvas>

  <!-- contenido animado -->
  <div class="relative z-10 text-center px-8">
    <div class="opacity-0 animate-fade-in-up">
      <h2 class="text-3xl font-bold mb-4">Crea tu cuenta gratuita</h2>
      <p class="text-base mb-10">Explora funciones profesionales para importadores y emprendedores</p>
    </div>

    <img src="https://automatizador.imporsuitpro.com/img/automat_log.png" alt="Automatizador"
         class="w-80 drop-shadow-xl mx-auto opacity-0 animate-fade-in-up-delayed">
  </div>
</div>




  <!-- Panel derecho con animaciones -->
<div class="w-full lg:w-1/2 bg-white flex items-center justify-center p-8 sm:p-12">
  <div class="w-full max-w-md">
    
    <!-- Título -->
    <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center lg:text-center opacity-0 animate-fade-in-up" style="animation-delay: 0.2s;">Login</h1>
    
    <!-- FORMULARIO -->
    <form action="login.php" method="POST" class="space-y-5">
      
      <!-- EMAIL -->
      <div class="relative mt-6 flex items-center opacity-0 animate-fade-in-up" style="animation-delay: 0.4s;">
        <div class="flex items-center justify-center h-full px-3 py-2 border border-gray-300 bg-white rounded-l-md transition-colors duration-300">
          <svg class="h-5 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 8l9 6 9-6M21 8v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8m18-2a2 2 0 00-2-2H5a2 2 0 00-2 2v0" />
          </svg>
        </div>
        <div class="relative w-full">
          <label for="email" class="absolute -top-2 left-5 px-1 text-sm text-gray-600 bg-white z-10">Correo</label>
          <input type="email" name="email" id="email"
            class="peer w-full px-3 py-2 border border-gray-300 border-l-0 rounded-r-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black"
            required>
        </div>
      </div>

      <!-- PASSWORD -->
      <div class="relative mt-6 flex items-center opacity-0 animate-fade-in-up" style="animation-delay: 0.6s;">
        <div class="flex items-center justify-center h-full px-3 py-2 border border-gray-300 bg-white rounded-l-md transition-colors duration-300">
          <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 0v6" />
          </svg>
        </div>
        <div class="relative w-full">
          <label for="password" class="absolute -top-2 left-5 px-1 text-sm text-gray-600 bg-white z-10">Contraseña</label>
          <input type="password" name="password" id="password"
            class="peer w-full px-3 py-2 border border-gray-300 border-l-0 rounded-r-md focus:outline-none focus:ring-1 focus:ring-black focus:border-black"
            required>
        </div>
      </div>

      <!-- RECORDATORIO Y ENLACE -->
      <div class="flex items-center justify-between opacity-0 animate-fade-in-up" style="animation-delay: 0.8s;">
        <label for="remember" class="flex items-center text-sm text-gray-700">
          <input type="checkbox" name="remember" id="remember" class="mr-2">
          Recuérdame
        </label>
        <a href="https://new.imporsuitpro.com/Home/recovery" class="text-sm text-blue-600 hover:underline">¿Olvidaste tu contraseña?</a>
      </div>

      <!-- BOTÓN -->
      <button type="submit"
        class="w-full bg-black text-white py-2 rounded-md hover:bg-gray-900 transition-colors opacity-0 animate-fade-in-up"
        style="animation-delay: 1s;">Iniciar sesión</button>

      <!-- MENSAJE FINAL -->
      <p class="text-center text-sm text-gray-600 opacity-0 animate-fade-in-up" style="animation-delay: 1.2s;">
        ¿No tienes una cuenta?
        <a href="https://new.imporsuitpro.com/registro" class="text-blue-600 hover:underline">Regístrate ahora</a>
      </p>
    </form>
  </div>
</div>

</body>
<script>
  const canvas = document.getElementById('particles-canvas');
  const ctx = canvas.getContext('2d');

  let particles = [];
  const mouse = { x: null, y: null };

  // Configura dimensiones iniciales
  function resizeCanvas() {
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
  }

  window.addEventListener('resize', resizeCanvas);
  resizeCanvas();

  // Seguimiento del mouse
  document.getElementById('panel-izquierdo').addEventListener('mousemove', e => {
    const rect = canvas.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
  });

  // Crea una partícula nueva
  function createParticle() {
    const size = Math.random() * 2 + 1;
    return {
      x: Math.random() * canvas.width,
      y: canvas.height + size,
      size,
      speedY: Math.random() * 0.7 + 0.3,
      speedX: (Math.random() - 0.5) * 0.5,
      opacity: Math.random() * 0.2 + 0.05
    };
  }

  // Crea muchas al inicio
  for (let i = 0; i < 150; i++) {
    particles.push(createParticle());
  }

  function updateParticles() {
    particles.forEach(p => {
      p.y -= p.speedY;
      p.x += p.speedX;

      // Reacción al mouse
      if (mouse.x && mouse.y) {
        const dx = p.x - mouse.x;
        const dy = p.y - mouse.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 60) {
          p.x += dx * 0.05;
          p.y += dy * 0.05;
        }
      }

      // Reciclar partículas que salen de pantalla
      if (p.y < -10 || p.x < -10 || p.x > canvas.width + 10) {
        Object.assign(p, createParticle());
        p.y = canvas.height + 10; // Siempre desde abajo
      }
    });
  }

  function drawParticles() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(255, 255, 255, ${p.opacity})`;
      ctx.fill();
    });
  }

  function animate() {
    updateParticles();
    drawParticles();
    requestAnimationFrame(animate);
  }

  animate();
</script>





</html>