<!DOCTYPE html>
<html lang="es">
<?php
require_once 'config.php';
require_once 'includes/info_negocio.php';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animación de fondo */
        .background-animation {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite;
        }

        .circle:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 10%;
            animation-delay: 0s;
        }

        .circle:nth-child(2) {
            width: 120px;
            height: 120px;
            right: 10%;
            animation-delay: 2s;
        }

        .circle:nth-child(3) {
            width: 60px;
            height: 60px;
            left: 50%;
            animation-delay: 4s;
        }

        .circle:nth-child(4) {
            width: 100px;
            height: 100px;
            right: 30%;
            animation-delay: 6s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.3;
            }
            50% {
                transform: translateY(-100px) rotate(180deg);
                opacity: 0.6;
            }
        }

        /* Contenedor principal */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header del login */
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .login-icon {
            font-size: 4em;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .login-header h1 {
            font-size: 1.8em;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .login-header p {
            font-size: 0.95em;
            opacity: 0.9;
        }

        /* Formulario */
        .login-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 0.95em;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2em;
            color: #999;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group input:focus + .input-icon {
            color: #667eea;
        }

        /* Botón de mostrar/ocultar contraseña */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.2em;
            color: #999;
            transition: color 0.3s;
            user-select: none;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        /* Recordar y Olvidé */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9em;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .forgot-password {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Botón de login */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Loading state */
        .btn-login.loading {
            position: relative;
            color: transparent;
        }

        .btn-login.loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Mensaje de error */
        .error-message {
            background: #fee;
            border-left: 4px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .error-icon {
            font-size: 1.5em;
        }

        /* Footer */
        .login-footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 0.9em;
            border-top: 1px solid #e0e0e0;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-footer a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 1.5em;
            }

            .login-form {
                padding: 30px 20px;
            }

            .form-options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        /* Animación de entrada de inputs */
        .form-group {
            animation: fadeInUp 0.6s ease backwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-options { animation: fadeInUp 0.6s ease 0.3s backwards; }
        .btn-login { animation: fadeInUp 0.6s ease 0.4s backwards; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <!-- Animación de fondo -->
    <div class="background-animation">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <!-- Contenedor de Login -->
    <div class="login-wrapper">
        <div class="login-container">
            
            <!-- Header -->
            <div class="login-header">
                <div class="login-icon">🍽️</div>
                <h1><?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></h1>
                <p>Panel de Administración</p>
            </div>

            <!-- Formulario -->
            <form action="verificar_login.php" method="POST" class="login-form" id="loginForm">
                
                <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <span class="error-icon">⚠️</span>
                    <div>
                        <strong>Error de autenticación</strong><br>
                        Usuario o contraseña incorrectos. Intenta nuevamente.
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['logout'])): ?>
                <div class="error-message" style="background: #d4edda; border-left-color: #28a745; color: #155724;">
                    <span class="error-icon">✅</span>
                    <div>
                        <strong>Sesión cerrada</strong><br>
                        Has cerrado sesión correctamente.
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            placeholder="Ingresa tu usuario"
                            required
                            autocomplete="username"
                            autofocus
                        >
                        <span class="input-icon">👤</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="clave">Contraseña</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="clave" 
                            name="clave" 
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password"
                        >
                        <span class="input-icon">🔒</span>
                        <span class="toggle-password" onclick="togglePassword()">👁️</span>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Recordarme</span>
                    </label>
                    <a href="#" class="forgot-password" onclick="alert('Contacta al administrador del sistema'); return false;">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="login-footer">
                <a href="index.php">← Volver al Menú</a>
            </div>
        </div>
    </div>

    <script>
        // Función para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('clave');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Animación de loading al enviar formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('btnLogin');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Prevenir múltiples envíos
        let formSubmitted = false;
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
        });

        // Focus automático en usuario si hay error
        <?php if (isset($_GET['error'])): ?>
        document.getElementById('usuario').focus();
        <?php endif; ?>

        // Limpiar error después de 5 segundos
        <?php if (isset($_GET['error']) || isset($_GET['logout'])): ?>
        setTimeout(function() {
            const errorMsg = document.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.style.animation = 'fadeOut 0.5s ease';
                setTimeout(() => errorMsg.remove(), 500);
            }
        }, 5000);
        <?php endif; ?>

        // Animación de fadeOut
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        `;
        document.head.appendChild(style);

        // Efecto de partículas en el fondo (opcional)
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'circle';
            particle.style.width = Math.random() * 100 + 50 + 'px';
            particle.style.height = particle.style.width;
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 5 + 's';
            document.querySelector('.background-animation').appendChild(particle);
            
            setTimeout(() => particle.remove(), 20000);
        }

        // Crear nueva partícula cada 5 segundos
        setInterval(createParticle, 5000);
    </script>

</body>
</html>