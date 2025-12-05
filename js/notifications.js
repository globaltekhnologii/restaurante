// notifications.js - Sistema de notificaciones en tiempo real
class NotificationSystem {
    constructor(config) {
        this.config = {
            apiUrl: config.apiUrl || 'api/check_updates.php',
            pollInterval: config.pollInterval || 5000, // 5 segundos
            soundEnabled: config.soundEnabled !== false,
            ...config
        };
        
        this.lastState = {};
        this.sounds = {};
        this.isPolling = false;
        
        this.init();
    }
    
    init() {
        // Cargar sonidos
        if (this.config.soundEnabled) {
            this.loadSounds();
        }
        
        // Solicitar permiso para notificaciones del navegador
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        // Iniciar polling
        this.startPolling();
        
        // Detener polling cuando la pestaña no está visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
    }
    
    loadSounds() {
        const soundFiles = {
            'new_order': 'sounds/new_order.mp3',
            'order_ready': 'sounds/order_ready.mp3',
            'alert': 'sounds/alert.mp3'
        };
        
        for (let [key, file] of Object.entries(soundFiles)) {
            this.sounds[key] = new Audio(file);
            this.sounds[key].volume = 0.7;
        }
    }
    
    playSound(soundName) {
        if (!this.config.soundEnabled) return;
        
        const sound = this.sounds[soundName];
        if (sound) {
            sound.currentTime = 0;
            sound.play().catch(err => console.log('Error playing sound:', err));
        }
    }
    
    startPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.poll();
    }
    
    stopPolling() {
        this.isPolling = false;
        if (this.pollTimeout) {
            clearTimeout(this.pollTimeout);
        }
    }
    
    async poll() {
        if (!this.isPolling) return;
        
        try {
            const response = await fetch(this.config.apiUrl);
            const data = await response.json();
            
            if (data.success) {
                this.processUpdates(data);
            }
        } catch (error) {
            console.error('Error checking updates:', error);
        }
        
        // Programar siguiente polling
        if (this.isPolling) {
            this.pollTimeout = setTimeout(() => this.poll(), this.config.pollInterval);
        }
    }
    
    processUpdates(data) {
        // Actualizar badges
        this.updateBadges(data);
        
        // Procesar notificaciones
        if (data.notifications && data.notifications.length > 0) {
            data.notifications.forEach(notification => {
                this.showNotification(notification);
            });
        }
        
        // Guardar estado actual
        this.lastState = data;
    }
    
    updateBadges(data) {
        // Actualizar contadores en la interfaz
        for (let [key, value] of Object.entries(data)) {
            if (key === 'notifications' || key === 'success' || key === 'timestamp') continue;
            
            const badge = document.getElementById(`badge-${key}`);
            if (badge) {
                if (value > 0) {
                    badge.textContent = value;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    }
    
    showNotification(notification) {
        // Reproducir sonido
        if (notification.sound) {
            this.playSound(notification.sound);
        }
        
        // Mostrar toast
        this.showToast(notification.message, notification.type);
        
        // Notificación del navegador (si está permitido)
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Restaurante El Sabor', {
                body: notification.message,
                icon: '/favicon.ico',
                tag: notification.type
            });
        }
        
        // Vibración en móviles
        if ('vibrate' in navigator) {
            navigator.vibrate([200, 100, 200]);
        }
    }
    
    showToast(message, type = 'info') {
        // Crear elemento toast
        const toast = document.createElement('div');
        toast.className = `notification-toast notification-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${this.getIcon(type)}</span>
                <span class="toast-message">${message}</span>
            </div>
        `;
        
        // Agregar estilos si no existen
        if (!document.getElementById('notification-styles')) {
            this.injectStyles();
        }
        
        // Agregar al DOM
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Remover después de 5 segundos
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    getIcon(type) {
        const icons = {
            'new_order': '🔔',
            'order_ready': '✅',
            'new_delivery': '🏍️',
            'alert': '⚠️',
            'info': 'ℹ️'
        };
        return icons[type] || icons.info;
    }
    
    injectStyles() {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            #notification-container {
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .notification-toast {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                padding: 15px 20px;
                min-width: 300px;
                max-width: 400px;
                opacity: 0;
                transform: translateX(400px);
                transition: all 0.3s ease;
            }
            
            .notification-toast.show {
                opacity: 1;
                transform: translateX(0);
            }
            
            .toast-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .toast-icon {
                font-size: 1.5em;
            }
            
            .toast-message {
                flex: 1;
                font-weight: 600;
                color: #333;
            }
            
            .notification-new_order {
                border-left: 4px solid #4299e1;
            }
            
            .notification-order_ready {
                border-left: 4px solid #48bb78;
            }
            
            .notification-new_delivery {
                border-left: 4px solid #ed8936;
            }
            
            .notification-alert {
                border-left: 4px solid #f56565;
            }
            
            .badge {
                display: none;
                background: #f56565;
                color: white;
                border-radius: 12px;
                padding: 2px 8px;
                font-size: 0.75em;
                font-weight: bold;
                margin-left: 5px;
            }
        `;
        document.head.appendChild(style);
    }
}

// Inicializar automáticamente si hay configuración
if (typeof notificationConfig !== 'undefined') {
    const notifications = new NotificationSystem(notificationConfig);
}
