// js/reportes.js - Lógica de reportes y gráficos

let charts = {};
let currentFilter = 'hoy';

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function () {
    setQuickFilter('hoy');
});

// Cambiar entre tabs
function switchTab(tabName) {
    // Actualizar botones
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');

    // Actualizar contenido
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
}

// Establecer filtro rápido
function setQuickFilter(filter) {
    currentFilter = filter;

    // Actualizar UI
    document.querySelectorAll('.quick-filter').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    const customFilters = document.getElementById('customFilters');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');

    const hoy = new Date();
    let inicio, fin;

    switch (filter) {
        case 'hoy':
            inicio = fin = formatDate(hoy);
            customFilters.style.display = 'none';
            break;
        case 'semana':
            const inicioSemana = new Date(hoy);
            inicioSemana.setDate(hoy.getDate() - hoy.getDay() + 1); // Lunes
            inicio = formatDate(inicioSemana);
            fin = formatDate(hoy);
            customFilters.style.display = 'none';
            break;
        case 'mes':
            inicio = formatDate(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
            fin = formatDate(hoy);
            customFilters.style.display = 'none';
            break;
        case 'personalizado':
            customFilters.style.display = 'grid';
            return; // No cargar automáticamente
    }

    if (filter !== 'personalizado') {
        fechaInicio.value = inicio;
        fechaFin.value = fin;
        cargarReportes();
    }
}

// Formatear fecha a YYYY-MM-DD
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

// Cargar todos los reportes
async function cargarReportes() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;

    try {
        await Promise.all([
            cargarVentas(fechaInicio, fechaFin),
            cargarProductos(fechaInicio, fechaFin),
            cargarDashboard()
        ]);
    } catch (error) {
        console.error('Error cargando reportes:', error);
        alert('Error al cargar los reportes. Por favor, intenta nuevamente.');
    }
}

// Cargar ventas por período
async function cargarVentas(fechaInicio, fechaFin) {
    try {
        const response = await fetch(`api/get_ventas_periodo.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Error al cargar ventas');
        }

        // Actualizar estadísticas
        document.getElementById('stat-ventas').textContent = '$' + formatNumber(data.resumen.total_ventas);
        document.getElementById('stat-pedidos').textContent = data.resumen.total_pedidos;
        document.getElementById('stat-promedio').textContent = '$' + formatNumber(data.resumen.ticket_promedio);
        document.getElementById('stat-maxima').textContent = '$' + formatNumber(data.resumen.venta_maxima);

        // Gráfico de ventas diarias
        const labels = data.ventas_por_dia.map(v => formatDateLabel(v.fecha));
        const valores = data.ventas_por_dia.map(v => v.total_ventas);

        renderChart('chartVentas', {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas ($)',
                    data: valores,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: (context) => 'Ventas: $' + formatNumber(context.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '$' + formatNumber(value)
                        }
                    }
                }
            }
        });

        // Gráfico de métodos de pago
        const totalEfectivo = data.ventas_por_dia.reduce((sum, v) => sum + v.efectivo, 0);
        const totalTarjeta = data.ventas_por_dia.reduce((sum, v) => sum + v.tarjeta, 0);
        const totalTransferencia = data.ventas_por_dia.reduce((sum, v) => sum + v.transferencia, 0);

        renderChart('chartMetodos', {
            type: 'doughnut',
            data: {
                labels: ['Efectivo', 'Tarjeta', 'Transferencia'],
                datasets: [{
                    data: [totalEfectivo, totalTarjeta, totalTransferencia],
                    backgroundColor: ['#4caf50', '#2196f3', '#9c27b0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (context) => context.label + ': $' + formatNumber(context.parsed)
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error en cargarVentas:', error);
        throw error;
    }
}

// Cargar productos más vendidos
async function cargarProductos(fechaInicio, fechaFin) {
    try {
        const response = await fetch(`api/get_productos_vendidos.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&limite=10`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Error al cargar productos');
        }

        // Gráfico por cantidad
        renderChart('chartProductosCantidad', {
            type: 'bar',
            data: {
                labels: data.top_cantidad.map(p => p.nombre),
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: data.top_cantidad.map(p => p.cantidad_vendida),
                    backgroundColor: '#4caf50'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });

        // Gráfico por ingresos
        renderChart('chartProductosIngresos', {
            type: 'bar',
            data: {
                labels: data.top_ingresos.map(p => p.nombre),
                datasets: [{
                    label: 'Ingresos ($)',
                    data: data.top_ingresos.map(p => p.ingresos_totales),
                    backgroundColor: '#2196f3'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => '$' + formatNumber(context.parsed.x)
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '$' + formatNumber(value)
                        }
                    }
                }
            }
        });

        // Tabla de productos
        const tbody = document.getElementById('tableProductosBody');
        tbody.innerHTML = '';

        data.top_cantidad.forEach(producto => {
            const row = tbody.insertRow();
            row.innerHTML = `
                <td><strong>${producto.nombre}</strong></td>
                <td>${producto.categoria}</td>
                <td>${producto.cantidad_vendida}</td>
                <td>$${formatNumber(producto.ingresos_totales)}</td>
                <td>$${formatNumber(producto.precio_promedio)}</td>
            `;
        });

    } catch (error) {
        console.error('Error en cargarProductos:', error);
        throw error;
    }
}

// Cargar dashboard
async function cargarDashboard() {
    try {
        const response = await fetch('api/get_estadisticas_dashboard.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Error al cargar dashboard');
        }

        // Gráfico de tendencia
        renderChart('chartTendencia', {
            type: 'line',
            data: {
                labels: data.tendencia_7_dias.map(d => formatDateLabel(d.fecha)),
                datasets: [
                    {
                        label: 'Ventas ($)',
                        data: data.tendencia_7_dias.map(d => d.total),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: 'Pedidos',
                        data: data.tendencia_7_dias.map(d => d.pedidos),
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: (value) => '$' + formatNumber(value)
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error en cargarDashboard:', error);
        throw error;
    }
}

// Renderizar o actualizar gráfico
function renderChart(canvasId, config) {
    const ctx = document.getElementById(canvasId);

    if (!ctx) {
        console.error('Canvas no encontrado:', canvasId);
        return;
    }

    // Destruir gráfico anterior si existe
    if (charts[canvasId]) {
        charts[canvasId].destroy();
    }

    // Crear nuevo gráfico
    charts[canvasId] = new Chart(ctx, config);
}

// Formatear números
function formatNumber(num) {
    if (!num) return '0';
    return parseFloat(num).toLocaleString('es-CO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

// Formatear etiqueta de fecha
function formatDateLabel(dateStr) {
    const date = new Date(dateStr + 'T00:00:00');
    const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    return `${dias[date.getDay()]} ${date.getDate()} ${meses[date.getMonth()]}`;
}
