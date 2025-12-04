-- Script para actualizar la contraseña del usuario admin
-- Ejecuta este script en phpMyAdmin o desde línea de comandos

USE menu_restaurante;

-- Actualizar contraseña del usuario admin a la nueva contraseña hasheada
-- Contraseña: Admin@2024!
-- Hash generado con password_hash('Admin@2024!', PASSWORD_DEFAULT)
UPDATE usuarios 
SET clave = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE usuario = 'admin';

-- Verificar que se actualizó correctamente
SELECT usuario, nombre, rol, 
       LEFT(clave, 20) as clave_preview,
       LENGTH(clave) as longitud_hash
FROM usuarios 
WHERE usuario = 'admin';
