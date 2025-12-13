<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/info_negocio.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin-modern.css">
    <title>Gestión de Publicidad - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <style>
        .grid-ads {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .ad-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            position: relative;
        }

        .ad-card:hover {
            transform: translateY(-5px);
        }

        .ad-preview {
            height: 200px;
            width: 100%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .ad-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ad-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .ad-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            color: white;
            font-size: 0.8em;
            font-weight: bold;
            z-index: 2;
        }
        
        .badge-imagen { background: #4e73df; }
        .badge-video { background: #e74a3b; }
        .badge-flyer { background: #1cc88a; }

        .ad-content {
            padding: 15px;
        }

        .ad-title {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 5px;
            color: #333;
        }

        .ad-meta {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 10px;
        }

        .ad-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .status-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ccc;
        }
        
        .status-active { background: #1cc88a; }
        .status-inactive { background: #e74a3b; }

        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            color: #666;
            transition: color 0.2s;
        }

        .btn-icon:hover { color: #4e73df; }
        .btn-delete:hover { color: #e74a3b; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }
        
        .drop-zone:hover {
            border-color: #4e73df;
            background: #f8f9fc;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📢 Gestión de Publicidad</h1>
        <div class="navbar-links">
            <a href="admin.php">← Volver al Panel</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Anuncios Activos</h2>
            <button class="btn btn-primary" onclick="openModal()">+ Nuevo Anuncio</button>
        </div>

        <div id="loading" style="text-align: center; padding: 40px;">Cargando anuncios...</div>
        <div id="adsGrid" class="grid-ads"></div>
    </div>

    <!-- Modal Nuevo Anuncio -->
    <div id="adModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 20px;">Nuevo Anuncio</h2>
            <form id="adForm" onsubmit="guardarAnuncio(event)">
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="titulo" required placeholder="Ej: Oferta de Fin de Semana">
                </div>

                <div class="form-group">
                    <label>Tipo de Anuncio</label>
                    <select name="tipo" onchange="toggleUploadType(this.value)" required>
                        <option value="imagen">Imagen (JPG, PNG)</option>
                        <option value="video">Video (MP4, WebM)</option>
                        <option value="flyer">Flyer Promocional</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Archivo Multimedia</label>
                    <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
                        <p>Haz clic para subir archivo</p>
                        <small id="fileHelp">Formatos: JPG, PNG (Max 5MB)</small>
                        <input type="file" name="archivo" id="fileInput" style="display: none" required>
                    </div>
                    <div id="previewContainer" style="display: none; margin-bottom: 15px;">
                        <!-- Preview se insertará aquí -->
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha Fin (Opcional)</label>
                        <input type="date" name="fecha_fin">
                    </div>
                </div>

                <div class="form-group">
                    <label>Link Destino (Opcional)</label>
                    <input type="url" name="link_destino" placeholder="https://...">
                    <small>Si se deja vacío, no habrá enlace al hacer clic.</small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Anuncio</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Cargar anuncios al iniciar
        document.addEventListener('DOMContentLoaded', cargarAnuncios);

        async function cargarAnuncios() {
            try {
                const response = await fetch('api/gestionar_publicidad.php?accion=listar');
                const data = await response.json();
                
                const grid = document.getElementById('adsGrid');
                document.getElementById('loading').style.display = 'none';
                grid.innerHTML = '';

                if (data.length === 0) {
                    grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #666;">No hay anuncios creados. ¡Crea el primero!</p>';
                    return;
                }

                data.forEach(ad => {
                    const card = createAdCard(ad);
                    grid.appendChild(card);
                });
            } catch (error) {
                console.error('Error cargando anuncios:', error);
                Swal.fire('Error', 'No se pudieron cargar los anuncios', 'error');
            }
        }

        function createAdCard(ad) {
            const div = document.createElement('div');
            div.className = 'ad-card';
            
            let preview = '';
            let badgeClass = '';
            
            if (ad.tipo === 'video') {
                preview = `<video src="${ad.archivo_url}" muted loop></video>`;
                badgeClass = 'badge-video';
            } else {
                preview = `<img src="${ad.archivo_url}" alt="${ad.titulo}">`;
                badgeClass = ad.tipo === 'flyer' ? 'badge-flyer' : 'badge-imagen';
            }

            const activeClass = ad.activo == 1 ? 'status-active' : 'status-inactive';
            const activeText = ad.activo == 1 ? 'Activo' : 'Inactivo';

            div.innerHTML = `
                <div class="ad-type-badge ${badgeClass}">${ad.tipo.toUpperCase()}</div>
                <div class="ad-preview">
                    ${preview}
                </div>
                <div class="ad-content">
                    <div class="ad-title">${ad.titulo}</div>
                    <div class="ad-meta">
                        📅 ${ad.fecha_inicio} ${ad.fecha_fin ? 'hasta ' + ad.fecha_fin : '(Indefinido)'}
                    </div>
                    <div class="ad-actions">
                        <div class="status-toggle" onclick="toggleEstado(${ad.id}, ${ad.activo})">
                            <div class="status-indicator ${activeClass}"></div>
                            <span style="font-size: 0.9em; color: #666;">${activeText}</span>
                        </div>
                        <div>
                            <button class="btn-icon btn-delete" onclick="eliminarAnuncio(${ad.id})">🗑️</button>
                        </div>
                    </div>
                </div>
            `;
            return div;
        }

        function toggleUploadType(tipo) {
            const help = document.getElementById('fileHelp');
            if (tipo === 'video') {
                help.textContent = 'Formatos: MP4, WebM (Max 50MB)';
            } else {
                help.textContent = 'Formatos: JPG, PNG (Max 5MB)';
            }
        }

        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.getElementById('previewContainer');
                    const type = document.querySelector('select[name="tipo"]').value;
                    
                    container.style.display = 'block';
                    if (type === 'video') {
                        container.innerHTML = `<video src="${e.target.result}" style="width: 100%; height: 200px; object-fit: cover;" controls></video>`;
                    } else {
                        container.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">`;
                    }
                }
                reader.readAsDataURL(file);
            }
        });

        async function guardarAnuncio(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('accion', 'crear');

            try {
                Swal.fire({
                    title: 'Subiendo...',
                    text: 'Por favor espere mientras se sube el archivo',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch('api/gestionar_publicidad.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();

                if (data.success) {
                    await Swal.fire('¡Éxito!', 'Anuncio creado correctamente', 'success');
                    closeModal();
                    cargarAnuncios();
                    e.target.reset(); // Limpiar formulario
                    document.getElementById('previewContainer').style.display = 'none'; // Ocultar preview
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                Swal.fire('Error', error.message || 'Error al guardar anuncio', 'error');
            }
        }

        async function toggleEstado(id, estadoActual) {
            try {
                const nuevoEstado = estadoActual == 1 ? 0 : 1;
                const formData = new FormData();
                formData.append('accion', 'cambiar_estado');
                formData.append('id', id);
                formData.append('activo', nuevoEstado); // Corregido: enviar 'activo'

                const response = await fetch('api/gestionar_publicidad.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    cargarAnuncios();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }

        async function eliminarAnuncio(id) {
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: "No podrás revertir esta acción",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('accion', 'eliminar');
                    formData.append('id', id);

                    const response = await fetch('api/gestionar_publicidad.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        Swal.fire('Eliminado', 'El anuncio ha sido eliminado', 'success');
                        cargarAnuncios();
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }
        }

        function openModal() {
            document.getElementById('adModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('adModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('adModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
