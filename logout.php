<?php
// IMPORTANTE: Todo el código PHP debe ir ANTES del HTML
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando Sesión...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .logout-container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 400px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logout-icon {
            font-size: 5em;
            margin-bottom: 20px;
            animation: wave 1s ease-in-out infinite;
        }

        @keyframes wave {
            0%, 100% {
                transform: rotate(0deg);
            }
            25% {
                transform: rotate(-15deg);
            }
            75% {
                transform: rotate(15deg);
            }
        }

        h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8em;
        }

        .message {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 30px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .success-message {
            color: #4CAF50;
            font-weight: bold;
            margin-top: 20px;
            opacity: 0;
            animation: fadeInSuccess 0.5s ease forwards;
            animation-delay: 1s;
        }

        @keyframes fadeInSuccess {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .redirect-info {
            margin-top: 20px;
            color: #999;
            font-size: 0.9em;
        }

        /* Confetti effect */
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #667eea;
            animation: confetti-fall 3s linear forwards;
        }

        @keyframes confetti-fall {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>

    <div class="logout-container">
        <div class="logout-icon">👋</div>
        <h2>Cerrando Sesión</h2>
        <p class="message">Gracias por usar el panel de administración</p>
        
        <div class="spinner"></div>
        
        <p class="success-message">✅ Sesión cerrada correctamente</p>
        <p class="redirect-info">Redirigiendo al inicio de sesión...</p>
    </div>

    <script>
        // Crear efecto de confetti
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.background = ['#667eea', '#764ba2', '#51cf66', '#ff6b6b', '#ffd43b'][Math.floor(Math.random() * 5)];
                confetti.style.animationDelay = Math.random() * 2 + 's';
                document.body.appendChild(confetti);
                
                // Eliminar después de la animación
                setTimeout(() => confetti.remove(), 3000);
            }
        }

        // Ejecutar confetti después de 1 segundo
        setTimeout(createConfetti, 1000);

        // Redirigir después de 2.5 segundos
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2500);
    </script>

</body>
</html>