<?php
/**
 * File Upload Helper - Funciones de validación de subida de archivos
 * 
 * Este archivo contiene funciones reutilizables para validar
 * la subida segura de imágenes en todo el sistema.
 */

// Configuración de límites
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('MAX_IMAGE_WIDTH', 2000); // 2000px
define('MAX_IMAGE_HEIGHT', 2000); // 2000px

// Tipos MIME permitidos
$ALLOWED_MIME_TYPES = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp'
];

// Extensiones permitidas
$ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

/**
 * Valida una imagen subida completamente
 * 
 * @param array $file Array de $_FILES['nombre']
 * @param int $maxSize Tamaño máximo en bytes (default: 2MB)
 * @return array ['valido' => bool, 'error' => string, 'info' => array]
 */
function validarImagenSubida($file, $maxSize = MAX_FILE_SIZE) {
    global $ALLOWED_MIME_TYPES, $ALLOWED_EXTENSIONS;
    
    $resultado = [
        'valido' => false,
        'error' => '',
        'info' => []
    ];
    
    // 1. Verificar que el archivo existe
    if (!isset($file) || $file['error'] == UPLOAD_ERR_NO_FILE) {
        $resultado['error'] = "No se ha subido ningún archivo";
        return $resultado;
    }
    
    // 2. Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $resultado['error'] = obtenerMensajeErrorSubida($file['error']);
        return $resultado;
    }
    
    // 3. Verificar tamaño del archivo
    if ($file['size'] > $maxSize) {
        $maxMB = round($maxSize / (1024 * 1024), 1);
        $resultado['error'] = "El archivo es demasiado grande. Máximo permitido: {$maxMB}MB";
        return $resultado;
    }
    
    if ($file['size'] == 0) {
        $resultado['error'] = "El archivo está vacío";
        return $resultado;
    }
    
    // 4. Verificar extensión
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $ALLOWED_EXTENSIONS)) {
        $resultado['error'] = "Tipo de archivo no permitido. Extensiones permitidas: " . implode(', ', $ALLOWED_EXTENSIONS);
        return $resultado;
    }
    
    // 5. Verificar tipo MIME real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $ALLOWED_MIME_TYPES)) {
        $resultado['error'] = "El archivo no es una imagen válida (tipo MIME: {$mimeType})";
        return $resultado;
    }
    
    // 6. Verificar que es una imagen real usando getimagesize
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $resultado['error'] = "El archivo no es una imagen válida o está corrupto";
        return $resultado;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // 7. Verificar dimensiones
    if ($width > MAX_IMAGE_WIDTH || $height > MAX_IMAGE_HEIGHT) {
        $resultado['error'] = "La imagen es demasiado grande. Dimensiones máximas: " . MAX_IMAGE_WIDTH . "x" . MAX_IMAGE_HEIGHT . "px";
        return $resultado;
    }
    
    // 8. Verificar que el tipo de imagen es soportado
    $tiposPermitidos = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
    if (!in_array($type, $tiposPermitidos)) {
        $resultado['error'] = "Tipo de imagen no soportado";
        return $resultado;
    }
    
    // Todo OK
    $resultado['valido'] = true;
    $resultado['info'] = [
        'extension' => $extension,
        'mime_type' => $mimeType,
        'width' => $width,
        'height' => $height,
        'size' => $file['size'],
        'size_mb' => round($file['size'] / (1024 * 1024), 2)
    ];
    
    return $resultado;
}

/**
 * Genera un nombre de archivo seguro y único
 * 
 * @param string $extension Extensión del archivo
 * @return string Nombre de archivo seguro
 */
function generarNombreSeguro($extension) {
    // Sanitizar extensión
    $extension = strtolower(preg_replace('/[^a-z0-9]/', '', $extension));
    
    // Generar nombre único
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    
    return "{$timestamp}_{$random}.{$extension}";
}

/**
 * Obtiene el tipo MIME real de un archivo
 * 
 * @param string $tmpName Ruta temporal del archivo
 * @return string|false Tipo MIME o false si falla
 */
function obtenerMimeType($tmpName) {
    if (!file_exists($tmpName)) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    
    return $mimeType;
}

/**
 * Verifica si un archivo es una imagen válida
 * 
 * @param string $tmpName Ruta temporal del archivo
 * @return bool True si es imagen válida
 */
function esImagenValida($tmpName) {
    if (!file_exists($tmpName)) {
        return false;
    }
    
    $imageInfo = @getimagesize($tmpName);
    return $imageInfo !== false;
}

/**
 * Obtiene mensaje de error legible para códigos de error de subida
 * 
 * @param int $errorCode Código de error de $_FILES['file']['error']
 * @return string Mensaje de error legible
 */
function obtenerMensajeErrorSubida($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "El archivo excede el tamaño máximo permitido por el servidor";
        case UPLOAD_ERR_FORM_SIZE:
            return "El archivo excede el tamaño máximo permitido por el formulario";
        case UPLOAD_ERR_PARTIAL:
            return "El archivo se subió parcialmente";
        case UPLOAD_ERR_NO_FILE:
            return "No se subió ningún archivo";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Falta la carpeta temporal";
        case UPLOAD_ERR_CANT_WRITE:
            return "Error al escribir el archivo en disco";
        case UPLOAD_ERR_EXTENSION:
            return "Una extensión de PHP detuvo la subida del archivo";
        default:
            return "Error desconocido al subir el archivo";
    }
}

/**
 * Mueve un archivo subido a su destino final de forma segura
 * 
 * @param array $file Array de $_FILES['nombre']
 * @param string $directorio Directorio destino
 * @param string $nombreArchivo Nombre del archivo (opcional, se genera si no se proporciona)
 * @return array ['exito' => bool, 'ruta' => string, 'error' => string]
 */
function moverArchivoSubido($file, $directorio, $nombreArchivo = null) {
    $resultado = [
        'exito' => false,
        'ruta' => '',
        'error' => ''
    ];
    
    // Crear directorio si no existe
    if (!file_exists($directorio)) {
        if (!mkdir($directorio, 0755, true)) {
            $resultado['error'] = "No se pudo crear el directorio de destino";
            return $resultado;
        }
    }
    
    // Generar nombre si no se proporcionó
    if ($nombreArchivo === null) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nombreArchivo = generarNombreSeguro($extension);
    }
    
    // Asegurar que el directorio termina con /
    $directorio = rtrim($directorio, '/') . '/';
    
    $rutaCompleta = $directorio . $nombreArchivo;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
        $resultado['exito'] = true;
        $resultado['ruta'] = $rutaCompleta;
    } else {
        $resultado['error'] = "Error al mover el archivo al destino final";
    }
    
    return $resultado;
}

/**
 * Elimina un archivo de forma segura
 * 
 * @param string $ruta Ruta del archivo a eliminar
 * @return bool True si se eliminó correctamente
 */
function eliminarArchivoSeguro($ruta) {
    if (empty($ruta) || !file_exists($ruta)) {
        return false;
    }
    
    // Verificar que está dentro del directorio permitido
    $rutaReal = realpath($ruta);
    $directorioPermitido = realpath('imagenes_platos');
    
    if ($rutaReal === false || strpos($rutaReal, $directorioPermitido) !== 0) {
        error_log("Intento de eliminar archivo fuera del directorio permitido: {$ruta}");
        return false;
    }
    
    return @unlink($ruta);
}
?>
