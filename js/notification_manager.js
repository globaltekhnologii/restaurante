/**
 * Notification Manager
 * Maneja el sondeo al servidor, reproducción de audio y notificaciones Web Push
 */

const NotificationManager = {
    lastCheck: Math.floor(Date.now() / 1000), // Timestamp actual en segundos
    interval: null,
    sounds: {
        new_order: null,
        order_ready: null
    },

    init: function () {
        console.log('🔔 Iniciando Gestor de Notificaciones...');

        // 1. Solicitar permisos de notificación navegador
        if ("Notification" in window) {
            Notification.requestPermission();
        }

        // 2. Pre-cargar audios
        this.sounds.new_order = new Audio('sounds/new_order.mp3');
        this.sounds.order_ready = new Audio('sounds/order_ready.mp3');

        // 3. Iniciar Polling (cada 10 seg)
        this.interval = setInterval(() => this.check(), 10000);
    },

    check: function () {
        fetch(`api/check_notifications.php?last_check=${this.lastCheck}`)
            .then(response => response.json())
            .then(data => {
                // Actualizar timestamp para la próxima vez
                this.lastCheck = data.timestamp;

                // 4. Procesar Alertas
                if (data.nuevos_pedidos > 0) {
                    this.triggerNewOrder(data.nuevos_pedidos);
                }

                if (data.pedidos_listos > 0) {
                    this.triggerOrderReady(data.pedidos_listos);
                }
            })
            .catch(err => console.error('Error checking notifications:', err));
    },

    triggerNewOrder: function (count) {
        console.log(`🔔 ${count} Nuevos Pedidos!`);

        // Sonido
        this.playSound('new_order');

        // Notificación Visual (Escritorio)
        this.showNotification("¡Nuevo Pedido Recibido!", {
            body: `Han llegado ${count} nuevos pedidos a cocina.`,
            icon: 'img/icon-chef.png' // Asegúrate de tener un icono o usa uno genérico
        });

        // Recargar tabla si existe función de recarga (opcional)
        if (typeof recargarTablaPedidos === 'function') {
            recargarTablaPedidos();
        } else {
            // Si estamos en admin_pedidos o chef, recargar pagina para verlos
            if (window.location.href.includes('admin_pedidos') || window.location.href.includes('chef')) {
                setTimeout(() => location.reload(), 2000); // Dar tiempo a ver la noti
            }
        }
    },

    triggerOrderReady: function (count) {
        console.log(`✅ ${count} Pedidos Listos!`);

        // Sonido
        this.playSound('order_ready');

        // Notificación Visual
        this.showNotification("¡Pedido Listo para Entregar!", {
            body: `${count} pedidos están listos en cocina.`,
            icon: 'img/icon-bell.png'
        });

        if (window.location.href.includes('mesero') || window.location.href.includes('admin_pedidos')) {
            setTimeout(() => location.reload(), 2000);
        }
    },

    playSound: function (type) {
        // Reproducir sonido solo si hay interacción previa (política navegadores)
        // Se asume que el usuario ha hecho clic en la página antes
        if (this.sounds[type]) {
            this.sounds[type].play().catch(e => console.log('Audio bloqueado por navegador (necesita interacción previa):', e));
        }
    },

    showNotification: function (title, options) {
        if (!("Notification" in window)) return;

        if (Notification.permission === "granted") {
            new Notification(title, options);
        }
    }
};

// Iniciar al cargar
document.addEventListener('DOMContentLoaded', () => {
    NotificationManager.init();

    // Desbloqueo de audio con el primer clic en cualquier parte
    document.body.addEventListener('click', function () {
        // Reproducir y pausar inmediatamente para "calentar" el motor de audio y desbloquear Autoplay
        const unlockAudio = (audioObj) => {
            if (audioObj) {
                audioObj.play().then(() => {
                    audioObj.pause();
                    audioObj.currentTime = 0;
                }).catch(e => console.warn('No se pudo desbloquear audio (posiblemente ya desbloqueado o error):', e));
            }
        };

        unlockAudio(NotificationManager.sounds.new_order);
        unlockAudio(NotificationManager.sounds.order_ready);

        console.log('🔊 Sistema de Audio Inicializado por usuario');
    }, { once: true });
});
