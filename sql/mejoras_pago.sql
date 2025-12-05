-- Mejoras de Sistema de Pagos y Tipos de Pedido
-- Fase 11: Nuevas funcionalidades

-- Agregar campos a tabla pedidos
ALTER TABLE pedidos ADD COLUMN tipo_pedido ENUM('mesa', 'domicilio', 'para_llevar') DEFAULT 'mesa';
ALTER TABLE pedidos ADD COLUMN metodo_pago_seleccionado VARCHAR(50);
ALTER TABLE pedidos ADD COLUMN pago_anticipado TINYINT(1) DEFAULT 0;
ALTER TABLE pedidos ADD COLUMN pago_validado TINYINT(1) DEFAULT 0;
ALTER TABLE pedidos ADD COLUMN referencia_pago_anticipado VARCHAR(100);
ALTER TABLE pedidos ADD COLUMN validacion_automatica TINYINT(1) DEFAULT 0;

-- Modificar columna estado para agregar nuevo estado
ALTER TABLE pedidos MODIFY COLUMN estado ENUM('pendiente', 'confirmado', 'preparando', 'en_camino', 'listo_recoger', 'entregado', 'cancelado') DEFAULT 'pendiente';

-- Crear índices
CREATE INDEX idx_tipo_pedido ON pedidos(tipo_pedido);
CREATE INDEX idx_pago_anticipado ON pedidos(pago_anticipado);
CREATE INDEX idx_pago_validado ON pedidos(pago_validado);
