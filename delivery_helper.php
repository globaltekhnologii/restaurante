<?php
// delivery_helper.php

// Asegurarse de que $info_negocio esté disponible
if (!isset($info_negocio)) {
    require_once __DIR__ . '/includes/info_negocio.php';
}

/**
 * Verifica si el servicio de domicilios está actualmente habilitado y dentro del horario de atención.
 *
 * @param array $info_negocio Arreglo con la información de configuración del negocio.
 * @return array Un arreglo asociativo con 'status' ('abierto' o 'cerrado') y 'message'.
 */
function checkDeliveryStatus(array $info_negocio): array {
    if (!$info_negocio['domicilios_habilitados']) {
        return [
            'status' => 'cerrado',
            'message' => 'El servicio de domicilios está actualmente deshabilitado.'
        ];
    }

    $apertura_str = $info_negocio['horario_apertura_domicilios'];
    $cierre_str = $info_negocio['horario_cierre_domicilios'];

    $current_time = new DateTime();
    $apertura_time = DateTime::createFromFormat('H:i:s', $apertura_str);
    $cierre_time = DateTime::createFromFormat('H:i:s', $cierre_str);

    // Si la hora de cierre es anterior a la de apertura, significa que el horario abarca la medianoche.
    if ($cierre_time < $apertura_time) {
        // Ajustar la fecha para que la comparación sea correcta.
        // Si la hora actual es antes de la hora de cierre, es del día siguiente.
        // Si la hora actual es después de la hora de apertura, es del día actual.
        $apertura_time->setDate($current_time->format('Y'), $current_time->format('m'), $current_time->format('d'));
        $cierre_time->setDate($current_time->format('Y'), $current_time->format('m'), $current_time->format('d'));
        if ($current_time < $apertura_time) {
            $apertura_time->modify('-1 day');
        } else {
            $cierre_time->modify('+1 day');
        }
    } else {
        $apertura_time->setDate($current_time->format('Y'), $current_time->format('m'), $current_time->format('d'));
        $cierre_time->setDate($current_time->format('Y'), $current_time->format('m'), $current_time->format('d'));
    }

    if ($current_time >= $apertura_time && $current_time < $cierre_time) {
        return [
            'status' => 'abierto',
            'message' => "Servicio de domicilios abierto. Cerramos a las " . (new DateTime($cierre_str))->format('H:i') . "."
        ];
    } else {
        return [
            'status' => 'cerrado',
            'message' => "Servicio de domicilios cerrado. Abrimos a las " . (new DateTime($apertura_str))->format('H:i') . "."
        ];
    }
}
