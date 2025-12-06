import React, { useState, useRef, useEffect } from 'react';
import { Send, Bot, User, ShoppingCart, Trash2, Check } from 'lucide-react';

const RestaurantChatbot = () => {
  const [messages, setMessages] = useState([
    {
      role: 'assistant',
      content: '¡Hola! 👋 Soy tu asistente virtual. Estoy aquí para ayudarte a hacer tu pedido a domicilio. ¿Qué te gustaría ordenar hoy?'
    }
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [cart, setCart] = useState([]);
  const messagesEndRef = useRef(null);

  // Menú del restaurante
  const menu = {
    entradas: [
      { id: 1, nombre: 'Alitas BBQ', precio: 12000, descripcion: '10 alitas con salsa BBQ' },
      { id: 2, nombre: 'Nachos Supremos', precio: 15000, descripcion: 'Con queso, guacamole y pico de gallo' },
      { id: 3, nombre: 'Aros de Cebolla', precio: 8000, descripcion: 'Crujientes aros de cebolla' }
    ],
    platos_principales: [
      { id: 4, nombre: 'Hamburguesa Clásica', precio: 18000, descripcion: 'Carne, lechuga, tomate, queso' },
      { id: 5, nombre: 'Pizza Margarita', precio: 25000, descripcion: 'Tomate, mozzarella, albahaca' },
      { id: 6, nombre: 'Tacos al Pastor', precio: 20000, descripcion: '3 tacos con carne al pastor' },
      { id: 7, nombre: 'Ensalada César', precio: 16000, descripcion: 'Lechuga, pollo, crutones, aderezo' }
    ],
    bebidas: [
      { id: 8, nombre: 'Coca-Cola', precio: 3000, descripcion: '500ml' },
      { id: 9, nombre: 'Jugo Natural', precio: 5000, descripcion: 'Naranja, mora o lulo' },
      { id: 10, nombre: 'Limonada', precio: 4000, descripcion: 'Limonada natural' }
    ],
    postres: [
      { id: 11, nombre: 'Brownie con Helado', precio: 10000, descripcion: 'Brownie caliente con helado' },
      { id: 12, nombre: 'Cheesecake', precio: 12000, descripcion: 'De frutos rojos' }
    ]
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const getMenuContext = () => {
    return `Eres un asistente virtual amigable de un restaurante. Tu trabajo es ayudar a los clientes a hacer pedidos a domicilio.

MENÚ DISPONIBLE:

ENTRADAS:
${menu.entradas.map(item => `- ${item.nombre}: $${item.precio.toLocaleString()} (${item.descripcion})`).join('\n')}

PLATOS PRINCIPALES:
${menu.platos_principales.map(item => `- ${item.nombre}: $${item.precio.toLocaleString()} (${item.descripcion})`).join('\n')}

BEBIDAS:
${menu.bebidas.map(item => `- ${item.nombre}: $${item.precio.toLocaleString()} (${item.descripcion})`).join('\n')}

POSTRES:
${menu.postres.map(item => `- ${item.nombre}: $${item.precio.toLocaleString()} (${item.descripcion})`).join('\n')}

INSTRUCCIONES:
- Ayuda al cliente a elegir del menú
- Sé amigable y conversacional
- Sugiere combos o complementos
- Confirma cada item antes de agregarlo al pedido
- Al final, solicita dirección y método de pago
- Responde de forma concisa (máximo 2-3 líneas)
- Si el cliente pide algo que NO está en el menú, ofrece alternativas similares

CARRITO ACTUAL:
${cart.length > 0 ? cart.map(item => `- ${item.nombre} x${item.cantidad}`).join('\n') : 'Vacío'}`;
  };

  const findMenuItem = (itemName) => {
    const allItems = [
      ...menu.entradas,
      ...menu.platos_principales,
      ...menu.bebidas,
      ...menu.postres
    ];
    
    return allItems.find(item => 
      item.nombre.toLowerCase().includes(itemName.toLowerCase())
    );
  };

  const addToCart = (itemName, quantity = 1) => {
    const item = findMenuItem(itemName);
    if (item) {
      setCart(prev => {
        const existing = prev.find(i => i.id === item.id);
        if (existing) {
          return prev.map(i => 
            i.id === item.id 
              ? { ...i, cantidad: i.cantidad + quantity }
              : i
          );
        }
        return [...prev, { ...item, cantidad: quantity }];
      });
      return true;
    }
    return false;
  };

  const handleSend = async () => {
    if (!input.trim() || loading) return;

    const userMessage = input.trim();
    setInput('');
    setMessages(prev => [...prev, { role: 'user', content: userMessage }]);
    setLoading(true);

    try {
      const response = await fetch('https://api.anthropic.com/v1/messages', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          model: 'claude-sonnet-4-20250514',
          max_tokens: 1000,
          system: getMenuContext(),
          messages: [
            ...messages.filter(m => m.role !== 'system'),
            { role: 'user', content: userMessage }
          ]
        })
      });

      const data = await response.json();
      const assistantMessage = data.content
        .filter(block => block.type === 'text')
        .map(block => block.text)
        .join('\n');

      setMessages(prev => [...prev, { 
        role: 'assistant', 
        content: assistantMessage 
      }]);

      // Detección simple de intención de agregar al carrito
      const lowerMessage = userMessage.toLowerCase();
      if (lowerMessage.includes('quiero') || lowerMessage.includes('agrega') || 
          lowerMessage.includes('pide') || lowerMessage.includes('ordena')) {
        const allItems = [
          ...menu.entradas,
          ...menu.platos_principales,
          ...menu.bebidas,
          ...menu.postres
        ];
        
        allItems.forEach(item => {
          if (lowerMessage.includes(item.nombre.toLowerCase())) {
            addToCart(item.nombre);
          }
        });
      }

    } catch (error) {
      console.error('Error:', error);
      setMessages(prev => [...prev, { 
        role: 'assistant', 
        content: 'Lo siento, hubo un error. ¿Podrías intentarlo de nuevo?' 
      }]);
    } finally {
      setLoading(false);
    }
  };

  const removeFromCart = (itemId) => {
    setCart(prev => prev.filter(item => item.id !== itemId));
  };

  const getTotalCart = () => {
    return cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
  };

  const confirmOrder = () => {
    if (cart.length === 0) {
      alert('Tu carrito está vacío');
      return;
    }
    
    const orderSummary = cart.map(item => 
      `${item.nombre} x${item.cantidad} - $${(item.precio * item.cantidad).toLocaleString()}`
    ).join('\n');
    
    alert(`¡Pedido confirmado!\n\n${orderSummary}\n\nTotal: $${getTotalCart().toLocaleString()}\n\nTe contactaremos para confirmar la dirección de entrega.`);
    setCart([]);
  };

  return (
    <div className="flex h-screen bg-gradient-to-br from-orange-50 to-red-50">
      {/* Chat Section */}
      <div className="flex-1 flex flex-col">
        {/* Header */}
        <div className="bg-gradient-to-r from-orange-500 to-red-500 text-white p-4 shadow-lg">
          <div className="flex items-center gap-3">
            <Bot size={32} />
            <div>
              <h1 className="text-xl font-bold">Restaurante DeliciaBot</h1>
              <p className="text-sm text-orange-100">Pedidos a domicilio con IA</p>
            </div>
          </div>
        </div>

        {/* Messages */}
        <div className="flex-1 overflow-y-auto p-4 space-y-4">
          {messages.map((msg, idx) => (
            <div
              key={idx}
              className={`flex gap-3 ${msg.role === 'user' ? 'flex-row-reverse' : ''}`}
            >
              <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${
                msg.role === 'user' 
                  ? 'bg-blue-500' 
                  : 'bg-gradient-to-br from-orange-400 to-red-500'
              }`}>
                {msg.role === 'user' ? <User size={18} /> : <Bot size={18} />}
              </div>
              <div
                className={`max-w-[70%] p-3 rounded-2xl ${
                  msg.role === 'user'
                    ? 'bg-blue-500 text-white'
                    : 'bg-white shadow-md border border-gray-100'
                }`}
              >
                <p className="whitespace-pre-wrap">{msg.content}</p>
              </div>
            </div>
          ))}
          {loading && (
            <div className="flex gap-3">
              <div className="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center">
                <Bot size={18} />
              </div>
              <div className="bg-white p-3 rounded-2xl shadow-md">
                <div className="flex gap-1">
                  <div className="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style={{animationDelay: '0ms'}}></div>
                  <div className="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style={{animationDelay: '150ms'}}></div>
                  <div className="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style={{animationDelay: '300ms'}}></div>
                </div>
              </div>
            </div>
          )}
          <div ref={messagesEndRef} />
        </div>

        {/* Input */}
        <div className="p-4 bg-white border-t border-gray-200">
          <div className="flex gap-2">
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleSend()}
              placeholder="Escribe tu mensaje..."
              className="flex-1 p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500"
              disabled={loading}
            />
            <button
              onClick={handleSend}
              disabled={loading || !input.trim()}
              className="bg-gradient-to-r from-orange-500 to-red-500 text-white p-3 rounded-xl hover:from-orange-600 hover:to-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
              <Send size={20} />
            </button>
          </div>
        </div>
      </div>

      {/* Cart Sidebar */}
      <div className="w-80 bg-white border-l border-gray-200 flex flex-col">
        <div className="bg-gradient-to-r from-orange-500 to-red-500 text-white p-4">
          <div className="flex items-center gap-2">
            <ShoppingCart size={24} />
            <h2 className="text-lg font-bold">Tu Pedido</h2>
          </div>
        </div>

        <div className="flex-1 overflow-y-auto p-4">
          {cart.length === 0 ? (
            <div className="text-center text-gray-400 mt-8">
              <ShoppingCart size={48} className="mx-auto mb-2 opacity-50" />
              <p>Tu carrito está vacío</p>
            </div>
          ) : (
            <div className="space-y-3">
              {cart.map(item => (
                <div key={item.id} className="bg-gray-50 p-3 rounded-lg">
                  <div className="flex justify-between items-start mb-2">
                    <div className="flex-1">
                      <p className="font-semibold">{item.nombre}</p>
                      <p className="text-sm text-gray-600">
                        ${item.precio.toLocaleString()} x {item.cantidad}
                      </p>
                    </div>
                    <button
                      onClick={() => removeFromCart(item.id)}
                      className="text-red-500 hover:text-red-700"
                    >
                      <Trash2 size={18} />
                    </button>
                  </div>
                  <p className="text-right font-bold text-orange-600">
                    ${(item.precio * item.cantidad).toLocaleString()}
                  </p>
                </div>
              ))}
            </div>
          )}
        </div>

        {cart.length > 0 && (
          <div className="p-4 border-t border-gray-200">
            <div className="flex justify-between items-center mb-4 text-lg font-bold">
              <span>Total:</span>
              <span className="text-orange-600">
                ${getTotalCart().toLocaleString()}
              </span>
            </div>
            <button
              onClick={confirmOrder}
              className="w-full bg-gradient-to-r from-green-500 to-green-600 text-white p-3 rounded-xl hover:from-green-600 hover:to-green-700 flex items-center justify-center gap-2 font-semibold transition-all"
            >
              <Check size={20} />
              Confirmar Pedido
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default RestaurantChatbot;