// js/auto_refresh.js - Sistema de Auto-Actualización con AJAX
class AutoRefresh {
    constructor(config) {
        this.endpoint = config.endpoint;
        this.targetElement = config.targetElement;
        this.interval = config.interval || 10000; // 10 segundos por defecto
        this.onUpdate = config.onUpdate || null;
        this.onNewItems = config.onNewItems || null;
        this.renderFunction = config.renderFunction || null;

        this.timer = null;
        this.isPaused = false;
        this.lastData = null;
        this.lastUpdate = null;
        this.errorCount = 0;
        this.maxErrors = 3;
    }

    start() {
        console.log(`🔄 Auto-refresh iniciado para ${this.endpoint}`);
        this.refresh();
        this.timer = setInterval(() => {
            if (!this.isPaused && !document.hidden) {
                this.refresh();
            }
        }, this.interval);

        // Pausar cuando la pestaña no está activa
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('⏸️ Auto-refresh pausado (pestaña inactiva)');
            } else {
                console.log('▶️ Auto-refresh reanudado');
                this.refresh(); // Actualizar inmediatamente al volver
            }
        });
    }

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
            console.log('⏹️ Auto-refresh detenido');
        }
    }

    pause() {
        this.isPaused = true;
        this.updateIndicator('pausado');
        console.log('⏸️ Auto-refresh pausado manualmente');
    }

    resume() {
        this.isPaused = false;
        this.updateIndicator('activo');
        this.refresh();
        console.log('▶️ Auto-refresh reanudado manualmente');
    }

    toggle() {
        if (this.isPaused) {
            this.resume();
        } else {
            this.pause();
        }
    }

    async refresh() {
        try {
            this.updateIndicator('cargando');

            const response = await fetch(this.endpoint, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.errorCount = 0; // Reset error counter
                this.processUpdate(data);
                this.lastUpdate = new Date();
                this.updateIndicator('activo');

                // Callback personalizado
                if (this.onUpdate) {
                    this.onUpdate(data);
                }
            } else {
                throw new Error(data.error || 'Error desconocido');
            }

        } catch (error) {
            console.error('❌ Error en auto-refresh:', error);
            this.errorCount++;
            this.updateIndicator('error');

            // Si hay muchos errores, aumentar el intervalo
            if (this.errorCount >= this.maxErrors) {
                console.warn('⚠️ Demasiados errores, aumentando intervalo');
                this.stop();
                setTimeout(() => this.start(), 30000); // Reintentar en 30 seg
            }
        }
    }

    processUpdate(data) {
        // Detectar nuevos items
        if (this.lastData && data.items) {
            const newItems = this.detectNewItems(data.items, this.lastData.items);
            if (newItems.length > 0 && this.onNewItems) {
                this.onNewItems(newItems);
            }
        }

        // Actualizar DOM
        if (this.renderFunction && this.targetElement) {
            const element = typeof this.targetElement === 'string'
                ? document.querySelector(this.targetElement)
                : this.targetElement;

            if (element) {
                element.innerHTML = this.renderFunction(data);
            }
        }

        this.lastData = data;
    }

    detectNewItems(currentItems, previousItems) {
        if (!previousItems) return [];

        const previousIds = new Set(previousItems.map(item => item.id));
        return currentItems.filter(item => !previousIds.has(item.id));
    }

    updateIndicator(status) {
        const indicator = document.getElementById('refresh-indicator');
        if (!indicator) return;

        const icons = {
            'cargando': '🔄',
            'activo': '✅',
            'pausado': '⏸️',
            'error': '❌'
        };

        const messages = {
            'cargando': 'Actualizando...',
            'activo': `Última actualización: ${this.getTimeAgo()}`,
            'pausado': 'Actualización pausada',
            'error': 'Error al actualizar'
        };

        indicator.innerHTML = `${icons[status]} ${messages[status]}`;
        indicator.className = `refresh-indicator ${status}`;

        if (status === 'cargando') {
            indicator.style.display = 'block';
        } else if (status === 'activo') {
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 2000);
        }
    }

    getTimeAgo() {
        if (!this.lastUpdate) return 'nunca';

        const seconds = Math.floor((new Date() - this.lastUpdate) / 1000);

        if (seconds < 60) return `hace ${seconds}s`;
        if (seconds < 3600) return `hace ${Math.floor(seconds / 60)}m`;
        return `hace ${Math.floor(seconds / 3600)}h`;
    }
}

// Utilidad para mostrar notificaciones toast
class ToastNotification {
    static show(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${this.getIcon(type)}</span>
            <span class="toast-message">${message}</span>
        `;

        document.body.appendChild(toast);

        // Animación de entrada
        setTimeout(() => toast.classList.add('show'), 10);

        // Remover después del tiempo especificado
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    static getIcon(type) {
        const icons = {
            'success': '✅',
            'info': 'ℹ️',
            'warning': '⚠️',
            'error': '❌',
            'new': '🔔'
        };
        return icons[type] || 'ℹ️';
    }
}

// Utilidad para reproducir sonidos de notificación
class NotificationSound {
    static play(type = 'default') {
        if (!this.isEnabled()) return;

        const sounds = {
            'new_order': 'sounds/new_order.mp3',
            'order_ready': 'sounds/order_ready.mp3',
            'alert': 'sounds/alert.mp3',
            'default': 'sounds/alert.mp3'
        };

        const audioPath = sounds[type] || sounds.default;

        // Agregar timestamp para evitar caché
        const audio = new Audio(audioPath + '?t=' + new Date().getTime());
        audio.volume = 0.7; // Volumen al 70% según README

        audio.play().catch(e => console.log('No se pudo reproducir sonido:', e));
    }

    static isEnabled() {
        return localStorage.getItem('notification_sound') !== 'false';
    }

    static toggle() {
        const current = this.isEnabled();
        localStorage.setItem('notification_sound', !current);
        return !current;
    }
}

// Exportar para uso global
window.AutoRefresh = AutoRefresh;
window.ToastNotification = ToastNotification;
window.NotificationSound = NotificationSound;
