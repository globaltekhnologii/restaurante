/**
 * Widget Embebible del Chatbot SaaS
 * Versión Vanilla JS (sin dependencias)
 * Inspirado en el diseño de chatbot.ts pero adaptado a JS puro
 */

(function () {
    'use strict';

    // Configuración del widget (se puede sobrescribir desde el HTML)
    const defaultConfig = {
        tenantId: null,
        apiEndpoint: '/Restaurante/ChatbotSaaS/backend/api/chat_handler.php',
        primaryColor: '#f97316',
        chatbotName: 'AsistenteBot',
        welcomeMessage: '¡Hola! 👋 ¿En qué puedo ayudarte hoy?'
    };

    // Merge con configuración del usuario
    const config = { ...defaultConfig, ...(window.chatbotConfig || {}) };

    if (!config.tenantId) {
        console.error('ChatbotWidget: tenantId es requerido');
        return;
    }

    // Estado del widget
    let isOpen = false;
    let messages = [];
    let sessionId = generateSessionId();

    // Crear estructura HTML del widget
    function createWidget() {
        const widgetHTML = `
            <div id="chatbot-widget" class="chatbot-widget">
                <!-- Botón flotante -->
                <button id="chatbot-toggle" class="chatbot-toggle" aria-label="Abrir chat">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </button>
                
                <!-- Ventana del chat -->
                <div id="chatbot-window" class="chatbot-window" style="display: none;">
                    <!-- Header -->
                    <div class="chatbot-header">
                        <div class="chatbot-header-content">
                            <div class="chatbot-avatar">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                                    <circle cx="12" cy="5" r="2"></circle>
                                    <path d="M12 7v4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="chatbot-title">${config.chatbotName}</h3>
                                <p class="chatbot-subtitle">En línea</p>
                            </div>
                        </div>
                        <button id="chatbot-close" class="chatbot-close-btn" aria-label="Cerrar chat">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Mensajes -->
                    <div id="chatbot-messages" class="chatbot-messages"></div>
                    
                    <!-- Input -->
                    <div class="chatbot-input-container">
                        <input 
                            type="text" 
                            id="chatbot-input" 
                            class="chatbot-input" 
                            placeholder="Escribe tu mensaje..."
                            autocomplete="off"
                        />
                        <button id="chatbot-send" class="chatbot-send-btn" aria-label="Enviar mensaje">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', widgetHTML);
        injectStyles();
        attachEventListeners();

        // Mensaje de bienvenida
        addMessage('assistant', config.welcomeMessage);
    }

    // Inyectar estilos CSS
    function injectStyles() {
        const styles = `
            .chatbot-widget {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .chatbot-toggle {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, ${config.primaryColor}, ${adjustColor(config.primaryColor, -20)});
                border: none;
                color: white;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .chatbot-toggle:hover {
                transform: scale(1.05);
                box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            }
            
            .chatbot-window {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: 380px;
                height: 600px;
                max-height: 80vh;
                background: white;
                border-radius: 16px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.12);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                animation: slideUp 0.3s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .chatbot-header {
                background: linear-gradient(135deg, ${config.primaryColor}, ${adjustColor(config.primaryColor, -20)});
                color: white;
                padding: 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .chatbot-header-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .chatbot-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: rgba(255,255,255,0.2);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .chatbot-title {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
            }
            
            .chatbot-subtitle {
                margin: 0;
                font-size: 12px;
                opacity: 0.9;
            }
            
            .chatbot-close-btn {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 4px;
                opacity: 0.8;
                transition: opacity 0.2s;
            }
            
            .chatbot-close-btn:hover {
                opacity: 1;
            }
            
            .chatbot-messages {
                flex: 1;
                overflow-y: auto;
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                background: linear-gradient(to bottom, #fef3f2, #fff);
            }
            
            .chatbot-message {
                display: flex;
                gap: 8px;
                animation: fadeIn 0.3s ease-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .chatbot-message.user {
                flex-direction: row-reverse;
            }
            
            .chatbot-message-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 18px;
            }
            
            .chatbot-message.assistant .chatbot-message-avatar {
                background: linear-gradient(135deg, ${config.primaryColor}, ${adjustColor(config.primaryColor, -20)});
                color: white;
            }
            
            .chatbot-message.user .chatbot-message-avatar {
                background: #3b82f6;
                color: white;
            }
            
            .chatbot-message-content {
                max-width: 70%;
                padding: 12px 16px;
                border-radius: 16px;
                line-height: 1.5;
                font-size: 14px;
            }
            
            .chatbot-message.assistant .chatbot-message-content {
                background: white;
                border: 1px solid #e5e7eb;
                color: #1f2937;
            }
            
            .chatbot-message.user .chatbot-message-content {
                background: #3b82f6;
                color: white;
            }
            
            .chatbot-typing {
                display: flex;
                gap: 4px;
                padding: 8px;
            }
            
            .chatbot-typing-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: ${config.primaryColor};
                animation: typing 1.4s infinite;
            }
            
            .chatbot-typing-dot:nth-child(2) { animation-delay: 0.2s; }
            .chatbot-typing-dot:nth-child(3) { animation-delay: 0.4s; }
            
            @keyframes typing {
                0%, 60%, 100% { transform: translateY(0); opacity: 0.7; }
                30% { transform: translateY(-10px); opacity: 1; }
            }
            
            .chatbot-input-container {
                padding: 16px;
                border-top: 1px solid #e5e7eb;
                display: flex;
                gap: 8px;
                background: white;
            }
            
            .chatbot-input {
                flex: 1;
                padding: 12px 16px;
                border: 1px solid #e5e7eb;
                border-radius: 24px;
                font-size: 14px;
                outline: none;
                transition: border-color 0.2s;
            }
            
            .chatbot-input:focus {
                border-color: ${config.primaryColor};
            }
            
            .chatbot-send-btn {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                background: ${config.primaryColor};
                border: none;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.2s;
            }
            
            .chatbot-send-btn:hover:not(:disabled) {
                transform: scale(1.05);
            }
            
            .chatbot-send-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            @media (max-width: 480px) {
                .chatbot-window {
                    width: calc(100vw - 40px);
                    height: calc(100vh - 100px);
                    max-height: none;
                }
            }
        `;

        const styleSheet = document.createElement('style');
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }

    // Event listeners
    function attachEventListeners() {
        const toggle = document.getElementById('chatbot-toggle');
        const close = document.getElementById('chatbot-close');
        const send = document.getElementById('chatbot-send');
        const input = document.getElementById('chatbot-input');

        toggle.addEventListener('click', toggleChat);
        close.addEventListener('click', toggleChat);
        send.addEventListener('click', sendMessage);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    }

    // Toggle chat window
    function toggleChat() {
        isOpen = !isOpen;
        const window = document.getElementById('chatbot-window');
        window.style.display = isOpen ? 'flex' : 'none';

        if (isOpen) {
            document.getElementById('chatbot-input').focus();
        }
    }

    // Agregar mensaje al chat
    function addMessage(role, content) {
        messages.push({ role, content });

        const messagesContainer = document.getElementById('chatbot-messages');
        const messageHTML = `
            <div class="chatbot-message ${role}">
                <div class="chatbot-message-avatar">
                    ${role === 'assistant' ? '🤖' : '👤'}
                </div>
                <div class="chatbot-message-content">${escapeHtml(content)}</div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Mostrar indicador de escritura
    function showTyping() {
        const messagesContainer = document.getElementById('chatbot-messages');
        const typingHTML = `
            <div class="chatbot-message assistant" id="typing-indicator">
                <div class="chatbot-message-avatar">🤖</div>
                <div class="chatbot-message-content">
                    <div class="chatbot-typing">
                        <div class="chatbot-typing-dot"></div>
                        <div class="chatbot-typing-dot"></div>
                        <div class="chatbot-typing-dot"></div>
                    </div>
                </div>
            </div>
        `;
        messagesContainer.insertAdjacentHTML('beforeend', typingHTML);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function hideTyping() {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) indicator.remove();
    }

    // Enviar mensaje
    async function sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();

        if (!message) return;

        // Agregar mensaje del usuario
        addMessage('user', message);
        input.value = '';

        // Deshabilitar input
        const sendBtn = document.getElementById('chatbot-send');
        input.disabled = true;
        sendBtn.disabled = true;

        // Mostrar typing
        showTyping();

        try {
            const response = await fetch(config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tenant_id: config.tenantId,
                    message: message,
                    session_id: sessionId
                })
            });

            const data = await response.json();

            hideTyping();

            if (data.success) {
                addMessage('assistant', data.message);
            } else {
                addMessage('assistant', 'Lo siento, hubo un error. ¿Podrías intentarlo de nuevo?');
            }

        } catch (error) {
            console.error('Error:', error);
            hideTyping();
            addMessage('assistant', 'Lo siento, hubo un problema de conexión. Intenta nuevamente.');
        } finally {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        }
    }

    // Utilidades
    function generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function adjustColor(color, amount) {
        const num = parseInt(color.replace('#', ''), 16);
        const r = Math.max(0, Math.min(255, (num >> 16) + amount));
        const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amount));
        const b = Math.max(0, Math.min(255, (num & 0x0000FF) + amount));
        return '#' + ((r << 16) | (g << 8) | b).toString(16).padStart(6, '0');
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }

})();
