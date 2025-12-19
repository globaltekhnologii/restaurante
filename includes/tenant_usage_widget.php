<?php
/**
 * Widget de Uso de Recursos del Tenant
 * Muestra el uso actual vs límites del plan
 */

// Solo mostrar si está configurado el tenant
if (!file_exists(__DIR__ . '/tenant_config.php')) {
    return; // No mostrar widget si no hay configuración
}

require_once __DIR__ . '/tenant_config.php';
require_once __DIR__ . '/includes/tenant_limits.php';

try {
    $status = getTenantStatus();
    
    if (!$status['configured'] || isset($status['error'])) {
        return; // No mostrar si hay error
    }
    
    $metrics = $status['metrics'];
    $limits = $status['limits'];
    $usage = $status['usage_percentage'];
    
    // Determinar color según porcentaje
    function getColorByPercentage($pct) {
        if ($pct >= 90) return '#ef4444'; // Rojo
        if ($pct >= 80) return '#f59e0b'; // Naranja
        if ($pct >= 60) return '#eab308'; // Amarillo
        return '#22c55e'; // Verde
    }
    
} catch (Exception $e) {
    error_log("Error en widget de uso: " . $e->getMessage());
    return;
}
?>

<style>
.tenant-usage-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 1.5rem;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.tenant-usage-widget h3 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tenant-plan-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
}

.usage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.usage-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    backdrop-filter: blur(10px);
}

.usage-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.usage-item-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.usage-item-value {
    font-size: 1.2rem;
    font-weight: bold;
}

.usage-bar {
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.usage-bar-fill {
    height: 100%;
    transition: width 0.3s ease, background-color 0.3s ease;
    border-radius: 4px;
}

.usage-warning {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.5);
    border-radius: 8px;
    padding: 0.75rem;
    margin-top: 1rem;
    font-size: 0.9rem;
}

.sync-info {
    text-align: right;
    margin-top: 1rem;
    font-size: 0.8rem;
    opacity: 0.8;
}

.sync-button {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
    margin-top: 0.5rem;
}

.sync-button:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}
</style>

<div class="tenant-usage-widget">
    <h3>
        📊 Uso de Recursos
        <span class="tenant-plan-badge">Plan <?php echo ucfirst(TENANT_PLAN); ?></span>
    </h3>
    
    <div class="usage-grid">
        <!-- Usuarios -->
        <div class="usage-item">
            <div class="usage-item-header">
                <span class="usage-item-label">👥 Usuarios</span>
                <span class="usage-item-value"><?php echo $metrics['current_users']; ?>/<?php echo $limits['users']; ?></span>
            </div>
            <div class="usage-bar">
                <div class="usage-bar-fill" style="width: <?php echo min($usage['users'], 100); ?>%; background-color: <?php echo getColorByPercentage($usage['users']); ?>;"></div>
            </div>
            <div style="font-size: 0.8rem; margin-top: 0.25rem; opacity: 0.9;">
                <?php echo number_format($usage['users'], 1); ?>% utilizado
            </div>
        </div>
        
        <!-- Platos -->
        <div class="usage-item">
            <div class="usage-item-header">
                <span class="usage-item-label">🍽️ Platos</span>
                <span class="usage-item-value"><?php echo $metrics['current_menu_items']; ?>/<?php echo $limits['menu_items']; ?></span>
            </div>
            <div class="usage-bar">
                <div class="usage-bar-fill" style="width: <?php echo min($usage['menu_items'], 100); ?>%; background-color: <?php echo getColorByPercentage($usage['menu_items']); ?>;"></div>
            </div>
            <div style="font-size: 0.8rem; margin-top: 0.25rem; opacity: 0.9;">
                <?php echo number_format($usage['menu_items'], 1); ?>% utilizado
            </div>
        </div>
        
        <!-- Almacenamiento -->
        <div class="usage-item">
            <div class="usage-item-header">
                <span class="usage-item-label">💾 Almacenamiento</span>
                <span class="usage-item-value"><?php echo number_format($metrics['current_storage_mb'], 0); ?>/<?php echo $limits['storage_mb']; ?> MB</span>
            </div>
            <div class="usage-bar">
                <div class="usage-bar-fill" style="width: <?php echo min($usage['storage'], 100); ?>%; background-color: <?php echo getColorByPercentage($usage['storage']); ?>;"></div>
            </div>
            <div style="font-size: 0.8rem; margin-top: 0.25rem; opacity: 0.9;">
                <?php echo number_format($usage['storage'], 1); ?>% utilizado
            </div>
        </div>
    </div>
    
    <?php if ($usage['users'] >= 80 || $usage['menu_items'] >= 80 || $usage['storage'] >= 80): ?>
        <div class="usage-warning">
            ⚠️ <strong>Advertencia:</strong> Estás cerca del límite de tu plan. 
            <?php if ($usage['users'] >= 90 || $usage['menu_items'] >= 90 || $usage['storage'] >= 90): ?>
                Considera actualizar a un plan superior.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="sync-info">
        Última actualización: <?php echo date('d/m/Y H:i'); ?>
        <br>
        <button class="sync-button" onclick="syncTenantData()">🔄 Sincronizar Ahora</button>
    </div>
</div>

<script>
function syncTenantData() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '⏳ Sincronizando...';
    
    fetch('sync_tenant.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.textContent = '✅ Sincronizado';
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                btn.textContent = '❌ Error';
                alert('Error al sincronizar: ' + (data.message || 'Desconocido'));
                btn.disabled = false;
                setTimeout(() => {
                    btn.textContent = '🔄 Sincronizar Ahora';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.textContent = '❌ Error';
            btn.disabled = false;
            setTimeout(() => {
                btn.textContent = '🔄 Sincronizar Ahora';
            }, 2000);
        });
}
</script>
