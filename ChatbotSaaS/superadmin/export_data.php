<?php
require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();
$type = $_GET['type'] ?? '';

if ($type === 'tenants') {
    // Exportar Tenants
    $filename = "restaurantes_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, ['ID', 'Restaurante', 'Email Propietario', 'Teléfono', 'Dirección', 'Plan', 'Estado', 'Inicio Suscripción', 'Fin Suscripción', 'Tarifa Mensual', 'Fecha Registro']);
    
    $result = $conn->query("SELECT id, restaurant_name, owner_email, phone, address, plan, status, subscription_start, subscription_end, monthly_fee, created_at FROM saas_tenants ORDER BY created_at DESC");
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['restaurant_name'],
            $row['owner_email'],
            $row['phone'],
            $row['address'],
            $row['plan'],
            $row['status'],
            $row['subscription_start'],
            $row['subscription_end'],
            $row['monthly_fee'],
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();

} elseif ($type === 'payments') {
    // Exportar Pagos
    $filename = "pagos_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, ['ID Pago', 'ID Tenant', 'Restaurante', 'Monto', 'Fecha Pago', 'Método', 'Estado', 'Referencia', 'Notas']);
    
    $query = "SELECT p.id, p.tenant_id, t.restaurant_name, p.amount, p.payment_date, p.payment_method, p.status, p.reference_number, p.notes 
              FROM saas_payments p 
              JOIN saas_tenants t ON p.tenant_id = t.id 
              ORDER BY p.payment_date DESC";
              
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['tenant_id'],
            $row['restaurant_name'],
            $row['amount'],
            $row['payment_date'],
            $row['payment_method'],
            $row['status'],
            $row['reference_number'],
            $row['notes']
        ]);
    }
    
    fclose($output);
    exit();

} else {
    die("Tipo de exportación no válido");
}

$conn->close();
?>
