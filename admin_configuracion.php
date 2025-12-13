<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'includes/info_negocio.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Negocio - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar a { color: white; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        h2 { color: #333; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background 0.3s;
        }
        .btn:hover { background: #5a67d8; }
        
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>⚙️ Configuración del Negocio</h1>
        <a href="admin.php">← Volver al Panel</a>
    </div>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="message success">✅ <?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="message error">❌ <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="guardar_configuracion.php" method="POST" enctype="multipart/form-data">
            <div class="card">
                <h2>🏢 Información Básica</h2>
                <div class="form-group">
                    <label>Nombre del Restaurante</label>
                    <input type="text" name="nombre_restaurante" value="<?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?>" required>
                </div>
                <div class="form-group">
                    <label>NIT / RUT</label>
                    <input type="text" name="nit" value="<?php echo htmlspecialchars($info_negocio['nit'] ?? ''); ?>" placeholder="Ej: 900.123.456-7">
                </div>
                <div class="form-group">
                    <label>Logo Actual</label>
                    <?php if ($info_negocio['logo_url']): ?>
                        <img src="<?php echo htmlspecialchars($info_negocio['logo_url']); ?>" alt="Logo" style="max-height: 100px; display: block; margin-bottom: 10px;">
                    <?php endif; ?>
                    <input type="file" name="logo" accept="image/*">
                    <small>Deja en blanco para mantener el actual</small>
                </div>
            </div>

            <div class="card">
                <h2>🧾 Configuración de Factura</h2>
                <div class="form-group">
                    <label>Mensaje Pie de Factura</label>
                    <textarea name="mensaje_pie_factura" rows="3" placeholder="Ej: ¡Gracias por su compra! Propina voluntaria no incluida."><?php echo htmlspecialchars($info_negocio['mensaje_pie_factura'] ?? ''); ?></textarea>
                    <small>Este mensaje aparecerá al final de todas las facturas impresas.</small>
                </div>
            </div>

            <div class="card">
                <h2>📍 Ubicación y Contacto</h2>
                <div class="grid-2">
                    <div class="form-group">
                        <label>País</label>
                        <input type="text" name="pais" value="<?php echo htmlspecialchars($info_negocio['pais']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Departamento / Estado</label>
                        <input type="text" name="departamento" value="<?php echo htmlspecialchars($info_negocio['departamento']); ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Ciudad</label>
                        <input type="text" name="ciudad" value="<?php echo htmlspecialchars($info_negocio['ciudad']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" value="<?php echo htmlspecialchars($info_negocio['direccion']); ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($info_negocio['telefono']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($info_negocio['email']); ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>🌐 Redes y Horarios</h2>
                <div class="form-group">
                    <label>Sitio Web</label>
                    <input type="url" name="sitio_web" value="<?php echo htmlspecialchars($info_negocio['sitio_web']); ?>">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Facebook</label>
                        <input type="text" name="facebook" value="<?php echo htmlspecialchars($info_negocio['facebook']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Instagram</label>
                        <input type="text" name="instagram" value="<?php echo htmlspecialchars($info_negocio['instagram']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Horario de Atención General</label>
                    <textarea name="horario_atencion" rows="3"><?php echo htmlspecialchars($info_negocio['horario_atencion']); ?></textarea>
                </div>
            </div>

            <div class="card">
                <h2>🛵 Configuración de Domicilios</h2>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Hora de Apertura Domicilios</label>
                        <input type="time" name="horario_apertura_domicilios" value="<?php echo htmlspecialchars($info_negocio['horario_apertura_domicilios']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Hora de Cierre Domicilios</label>
                        <input type="time" name="horario_cierre_domicilios" value="<?php echo htmlspecialchars($info_negocio['horario_cierre_domicilios']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="domicilios_habilitados" value="1" <?php echo $info_negocio['domicilios_habilitados'] ? 'checked' : ''; ?>>
                        Domicilios Habilitados
                    </label>
                </div>
            </div>

            <button type="submit" class="btn">💾 Guardar Cambios</button>
        </form>
    </div>
</body>
</html>
