// js/theme-manager.js - Gestor de Temas

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || this.getSystemTheme();
        this.init();
    }

    init() {
        // Aplicar tema inicial
        this.applyTheme(this.currentTheme);

        // Escuchar cambios en preferencia del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!this.getStoredTheme()) {
                this.applyTheme(e.matches ? 'dark' : 'light');
            }
        });

        // Crear switcher si no existe
        this.createSwitcher();
    }

    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    getStoredTheme() {
        return localStorage.getItem('theme');
    }

    setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
    }

    applyTheme(theme) {
        this.currentTheme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        this.setStoredTheme(theme);

        // Emitir evento personalizado
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);

        // Animación de transición
        this.animateThemeChange();
    }

    animateThemeChange() {
        document.body.style.setProperty('--transition-duration', '0.3s');
        setTimeout(() => {
            document.body.style.removeProperty('--transition-duration');
        }, 300);
    }

    createSwitcher() {
        // Buscar contenedor del switcher
        const container = document.querySelector('.theme-switcher-container');
        if (!container) return;

        // Crear HTML del switcher
        container.innerHTML = `
            <div class="theme-switcher">
                <button class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">
                    <span class="theme-icon icon-sun">☀️</span>
                    <span class="theme-icon icon-moon">🌙</span>
                </button>
            </div>
        `;

        // Agregar event listener
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', () => this.toggleTheme());
        }
    }

    // Método para obtener el tema actual
    getTheme() {
        return this.currentTheme;
    }

    // Método para verificar si es tema oscuro
    isDark() {
        return this.currentTheme === 'dark';
    }
}

// Inicializar automáticamente
let themeManager;
document.addEventListener('DOMContentLoaded', () => {
    themeManager = new ThemeManager();
    console.log('✅ Theme Manager inicializado:', themeManager.getTheme());
});

// Exportar para uso global
window.ThemeManager = ThemeManager;
window.getThemeManager = () => themeManager;
