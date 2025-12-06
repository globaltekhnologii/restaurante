import React, { useState } from 'react';
import { 
  Settings, Menu, X, Home, MessageSquare, Users, BarChart3, 
  CreditCard, Bot, Save, Plus, Trash2, Edit2, Eye, Phone,
  Mail, MapPin, Clock, Globe, Palette, Code
} from 'lucide-react';

const AdminPanel = () => {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [activeSection, setActiveSection] = useState('dashboard');
  const [config, setConfig] = useState({
    restaurantName: 'Mi Restaurante',
    ownerEmail: 'owner@restaurant.com',
    phone: '+57 300 123 4567',
    address: 'Calle 123 #45-67, Bogotá',
    businessHours: '9:00 AM - 10:00 PM',
    chatbotName: 'RestauBot',
    welcomeMessage: '¡Hola! 👋 Soy tu asistente virtual. ¿En qué puedo ayudarte hoy?',
    primaryColor: '#f97316',
    enableReservations: true,
    enableDelivery: true,
    enableWhatsApp: false,
    whatsappNumber: '',
    apiKey: '',
    menuItems: [
      { id: 1, name: 'Pizza Margarita', category: 'Platos', price: 25000, available: true },
      { id: 2, name: 'Hamburguesa', category: 'Platos', price: 18000, available: true },
      { id: 3, name: 'Coca-Cola', category: 'Bebidas', price: 3000, available: true }
    ]
  });

  const [newMenuItem, setNewMenuItem] = useState({ name: '', category: '', price: '', description: '' });
  const [showAddItem, setShowAddItem] = useState(false);

  const updateConfig = (key, value) => {
    setConfig(prev => ({ ...prev, [key]: value }));
  };

  const addMenuItem = () => {
    if (!newMenuItem.name || !newMenuItem.price) {
      alert('Por favor completa nombre y precio');
      return;
    }
    
    const item = {
      id: Date.now(),
      name: newMenuItem.name,
      category: newMenuItem.category || 'Otros',
      price: parseFloat(newMenuItem.price),
      description: newMenuItem.description,
      available: true
    };
    
    setConfig(prev => ({
      ...prev,
      menuItems: [...prev.menuItems, item]
    }));
    
    setNewMenuItem({ name: '', category: '', price: '', description: '' });
    setShowAddItem(false);
  };

  const removeMenuItem = (id) => {
    setConfig(prev => ({
      ...prev,
      menuItems: prev.menuItems.filter(item => item.id !== id)
    }));
  };

  const toggleItemAvailability = (id) => {
    setConfig(prev => ({
      ...prev,
      menuItems: prev.menuItems.map(item =>
        item.id === id ? { ...item, available: !item.available } : item
      )
    }));
  };

  const saveConfiguration = () => {
    // Aquí guardarías en tu backend
    console.log('Configuración guardada:', config);
    
    // Para pruebas locales, guardar en localStorage
    localStorage.setItem('chatbot_config', JSON.stringify(config));
    
    alert('✅ Configuración guardada correctamente');
  };

  const exportConfig = () => {
    const dataStr = JSON.stringify(config, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'chatbot-config.json';
    link.click();
  };

  const generateEmbedCode = () => {
    return `<!-- RestaurantBot Widget -->
<script>
  window.chatbotConfig = ${JSON.stringify({
    restaurantName: config.restaurantName,
    chatbotName: config.chatbotName,
    primaryColor: config.primaryColor,
    apiKey: config.apiKey
  })};
</script>
<script src="https://tu-dominio.com/chatbot-widget.js"></script>
<!-- Fin RestaurantBot Widget -->`;
  };

  const DashboardView = () => (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold text-gray-800">Dashboard</h2>
      
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-xl">
          <MessageSquare className="mb-2" size={32} />
          <p className="text-3xl font-bold">1,234</p>
          <p className="text-blue-100">Conversaciones hoy</p>
        </div>
        <div className="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl">
          <Users className="mb-2" size={32} />
          <p className="text-3xl font-bold">89</p>
          <p className="text-green-100">Pedidos completados</p>
        </div>
        <div className="bg-gradient-to-br from-orange-500 to-red-500 text-white p-6 rounded-xl">
          <BarChart3 className="mb-2" size={32} />
          <p className="text-3xl font-bold">$2.5M</p>
          <p className="text-orange-100">Ventas del mes</p>
        </div>
        <div className="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-xl">
          <Bot className="mb-2" size={32} />
          <p className="text-3xl font-bold">96%</p>
          <p className="text-purple-100">Tasa de respuesta</p>
        </div>
      </div>

      <div className="bg-white p-6 rounded-xl shadow-md">
        <h3 className="text-xl font-bold text-gray-800 mb-4">Actividad reciente</h3>
        <div className="space-y-3">
          {[
            { text: 'Nuevo pedido #1234', time: 'Hace 5 min', type: 'success' },
            { text: 'Reservación para 4 personas', time: 'Hace 12 min', type: 'info' },
            { text: 'Cliente solicitó el menú', time: 'Hace 20 min', type: 'neutral' }
          ].map((activity, idx) => (
            <div key={idx} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div className="flex items-center gap-3">
                <div className={`w-2 h-2 rounded-full ${
                  activity.type === 'success' ? 'bg-green-500' :
                  activity.type === 'info' ? 'bg-blue-500' : 'bg-gray-400'
                }`}></div>
                <span className="text-gray-700">{activity.text}</span>
              </div>
              <span className="text-sm text-gray-500">{activity.time}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );

  const ConfigView = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-gray-800">Configuración del Chatbot</h2>
        <button
          onClick={saveConfiguration}
          className="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-2 rounded-lg hover:from-green-600 hover:to-green-700 flex items-center gap-2 font-semibold"
        >
          <Save size={18} />
          Guardar Cambios
        </button>
      </div>

      {/* Información del Restaurante */}
      <div className="bg-white p-6 rounded-xl shadow-md">
        <h3 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
          <Home size={20} />
          Información del Restaurante
        </h3>
        <div className="grid md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Nombre del Restaurante
            </label>
            <input
              type="text"
              value={config.restaurantName}
              onChange={(e) => updateConfig('restaurantName', e.target.value)}
              className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Email del Propietario
            </label>
            <div className="relative">
              <Mail className="absolute left-3 top-3.5 text-gray-400" size={18} />
              <input
                type="email"
                value={config.ownerEmail}
                onChange={(e) => updateConfig('ownerEmail', e.target.value)}
                className="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              />
            </div>
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Teléfono
            </label>
            <div className="relative">
              <Phone className="absolute left-3 top-3.5 text-gray-400" size={18} />
              <input
                type="tel"
                value={config.phone}
                onChange={(e) => updateConfig('phone', e.target.value)}
                className="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              />
            </div>
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Horario
            </label>
            <div className="relative">
              <Clock className="absolute left-3 top-3.5 text-gray-400" size={18} />
              <input
                type="text"
                value={config.businessHours}
                onChange={(e) => updateConfig('businessHours', e.target.value)}
                className="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              />
            </div>
          </div>
          <div className="md:col-span-2">
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Dirección
            </label>
            <div className="relative">
              <MapPin className="absolute left-3 top-3.5 text-gray-400" size={18} />
              <input
                type="text"
                value={config.address}
                onChange={(e) => updateConfig('address', e.target.value)}
                className="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              />
            </div>
          </div>
        </div>
      </div>

      {/* Configuración del Bot */}
      <div className="bg-white p-6 rounded-xl shadow-md">
        <h3 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
          <Bot size={20} />
          Personalización del Chatbot
        </h3>
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Nombre del Bot
            </label>
            <input
              type="text"
              value={config.chatbotName}
              onChange={(e) => updateConfig('chatbotName', e.target.value)}
              className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Mensaje de Bienvenida
            </label>
            <textarea
              value={config.welcomeMessage}
              onChange={(e) => updateConfig('welcomeMessage', e.target.value)}
              rows={3}
              className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              Color Principal
            </label>
            <div className="flex gap-3 items-center">
              <input
                type="color"
                value={config.primaryColor}
                onChange={(e) => updateConfig('primaryColor', e.target.value)}
                className="w-20 h-12 rounded cursor-pointer"
              />
              <input
                type="text"
                value={config.primaryColor}
                onChange={(e) => updateConfig('primaryColor', e.target.value)}
                className="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              />
            </div>
          </div>
        </div>
      </div>

      {/* Funcionalidades */}
      <div className="bg-white p-6 rounded-xl shadow-md">
        <h3 className="text-xl font-bold text-gray-800 mb-4">Funcionalidades</h3>
        <div className="space-y-3">
          <label className="flex items-center justify-between p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
            <span className="text-gray-700 font-medium">Habilitar Reservaciones</span>
            <input
              type="checkbox"
              checked={config.enableReservations}
              onChange={(e) => updateConfig('enableReservations', e.target.checked)}
              className="w-5 h-5 text-orange-500 rounded"
            />
          </label>
          <label className="flex items-center justify-between p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
            <span className="text-gray-700 font-medium">Habilitar Domicilios</span>
            <input
              type="checkbox"
              checked={config.enableDelivery}
              onChange={(e) => updateConfig('enableDelivery', e.target.checked)}
              className="w-5 h-5 text-orange-500 rounded"
            />
          </label>
          <label className="flex items-center justify-between p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
            <span className="text-gray-700 font-medium">Integración WhatsApp</span>
            <input
              type="checkbox"
              checked={config.enableWhatsApp}
              onChange={(e) => updateConfig('enableWhatsApp', e.target.checked)}
              className="w-5 h-5 text-orange-500 rounded"
            />
          </label>
          {config.enableWhatsApp && (
            <div className="ml-4 mt-2">
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Número de WhatsApp Business
              </label>
              <input
                type="tel"
                value={config.whatsappNumber}
                onChange={(e) => updateConfig('whatsappNumber', e.target.value)}
                placeholder="+57 300 123 4567"
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              />
            </div>
          )}
        </div>
      </div>

      {/* API Key */}
      <div className="bg-white p-6 rounded-xl shadow-md">
        <h3 className="text-xl font-bold text-gray-800 mb-4">API de Anthropic</h3>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-2">
            API Key de Claude
          </label>
          <input
            type="password"
            value={config.apiKey}
            onChange={(e) => updateConfig('apiKey', e.target.value)}
            placeholder="sk-ant-..."
            className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
          />
          <p className="text-sm text-gray-500 mt-2">
            Obtén tu API key en{' '}
            <a href="https://console.anthropic.com" target="_blank" rel="noopener noreferrer" className="text-orange-500 hover:underline">
              console.anthropic.com
            </a>
          </p>
        </div>
      </div>

      {/* Código de Integración */}
      <div className="bg-white p-6 rounded-xl shadow-md">
        <h3 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
          <Code size={20} />
          Código de Integración
        </h3>
        <p className="text-sm text-gray-600 mb-3">
          Copia este código en tu sitio web para integrar el chatbot:
        </p>
        <div className="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm">
          <pre>{generateEmbedCode()}</pre>
        </div>
        <button
          onClick={() => {
            navigator.clipboard.writeText(generateEmbedCode());
            alert('Código copiado al portapapeles');
          }}
          className="mt-3 text-orange-500 hover:text-orange-600 font-semibold text-sm"
        >
          📋 Copiar código
        </button>
      </div>
    </div>
  );

  const MenuView = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-gray-800">Gestión de Menú</h2>
        <button
          onClick={() => setShowAddItem(!showAddItem)}
          className="bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-2 rounded-lg hover:from-orange-600 hover:to-red-600 flex items-center gap-2 font-semibold"
        >
          <Plus size={18} />
          Agregar Item
        </button>
      </div>

      {showAddItem && (
        <div className="bg-white p-6 rounded-xl shadow-md">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Nuevo Item del Menú</h3>
          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">Nombre</label>
              <input
                type="text"
                value={newMenuItem.name}
                onChange={(e) => setNewMenuItem({...newMenuItem, name: e.target.value})}
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                placeholder="Ej: Pizza Margarita"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">Categoría</label>
              <select
                value={newMenuItem.category}
                onChange={(e) => setNewMenuItem({...newMenuItem, category: e.target.value})}
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              >
                <option value="">Seleccionar...</option>
                <option value="Entradas">Entradas</option>
                <option value="Platos">Platos Principales</option>
                <option value="Bebidas">Bebidas</option>
                <option value="Postres">Postres</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">Precio (COP)</label>
              <input
                type="number"
                value={newMenuItem.price}
                onChange={(e) => setNewMenuItem({...newMenuItem, price: e.target.value})}
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                placeholder="25000"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">Descripción</label>
              <input
                type="text"
                value={newMenuItem.description}
                onChange={(e) => setNewMenuItem({...newMenuItem, description: e.target.value})}
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                placeholder="Opcional"
              />
            </div>
          </div>
          <div className="flex gap-3 mt-4">
            <button
              onClick={addMenuItem}
              className="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 font-semibold"
            >
              Guardar Item
            </button>
            <button
              onClick={() => setShowAddItem(false)}
              className="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 font-semibold"
            >
              Cancelar
            </button>
          </div>
        </div>
      )}

      <div className="bg-white rounded-xl shadow-md overflow-hidden">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="text-left p-4 font-semibold text-gray-700">Nombre</th>
              <th className="text-left p-4 font-semibold text-gray-700">Categoría</th>
              <th className="text-left p-4 font-semibold text-gray-700">Precio</th>
              <th className="text-left p-4 font-semibold text-gray-700">Estado</th>
              <th className="text-left p-4 font-semibold text-gray-700">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {config.menuItems.map((item) => (
              <tr key={item.id} className="border-t border-gray-100 hover:bg-gray-50">
                <td className="p-4 font-medium text-gray-800">{item.name}</td>
                <td className="p-4 text-gray-600">{item.category}</td>
                <td className="p-4 text-gray-800 font-semibold">
                  ${item.price.toLocaleString()}
                </td>
                <td className="p-4">
                  <span className={`px-3 py-1 rounded-full text-xs font-semibold ${
                    item.available 
                      ? 'bg-green-100 text-green-700' 
                      : 'bg-red-100 text-red-700'
                  }`}>
                    {item.available ? 'Disponible' : 'Agotado'}
                  </span>
                </td>
                <td className="p-4">
                  <div className="flex gap-2">
                    <button
                      onClick={() => toggleItemAvailability(item.id)}
                      className="text-blue-500 hover:text-blue-700"
                      title={item.available ? 'Marcar como agotado' : 'Marcar como disponible'}
                    >
                      <Eye size={18} />
                    </button>
                    <button
                      onClick={() => removeMenuItem(item.id)}
                      className="text-red-500 hover:text-red-700"
                      title="Eliminar"
                    >
                      <Trash2 size={18} />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );

  return (
    <div className="flex h-screen bg-gray-100">
      {/* Sidebar */}
      <div className={`${sidebarOpen ? 'w-64' : 'w-20'} bg-gradient-to-b from-gray-900 to-gray-800 text-white transition-all duration-300 flex flex-col`}>
        <div className="p-4 flex items-center justify-between">
          {sidebarOpen && (
            <div className="flex items-center gap-2">
              <div className="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center font-bold">
                RB
              </div>
              <span className="font-bold">RestaurantBot</span>
            </div>
          )}
          <button onClick={() => setSidebarOpen(!sidebarOpen)} className="text-gray-400 hover:text-white">
            {sidebarOpen ? <X size={24} /> : <Menu size={24} />}
          </button>
        </div>

        <nav className="flex-1 p-4 space-y-2">
          {[
            { id: 'dashboard', icon: Home, label: 'Dashboard' },
            { id: 'config', icon: Settings, label: 'Configuración' },
            { id: 'menu', icon: MessageSquare, label: 'Menú' },
            { id: 'analytics', icon: BarChart3, label: 'Análisis' },
            { id: 'billing', icon: CreditCard, label: 'Facturación' }
          ].map((item) => (
            <button
              key={item.id}
              onClick={() => setActiveSection(item.id)}
              className={`w-full flex items-center gap-3 p-3 rounded-lg transition ${
                activeSection === item.id
                  ? 'bg-orange-500 text-white'
                  : 'text-gray-400 hover:bg-gray-700 hover:text-white'
              }`}
            >
              <item.icon size={20} />
              {sidebarOpen && <span>{item.label}</span>}
            </button>
          ))}
        </nav>

        <div className="p-4 border-t border-gray-700">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
              👤
            </div>
            {sidebarOpen && (
              <div className="flex-1">
                <p className="text-sm font-semibold">{config.ownerEmail}</p>
                <p className="text-xs text-gray-400">Plan Profesional</p>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="flex-1 overflow-y-auto">
        <div className="p-8">
          {activeSection === 'dashboard' && <DashboardView />}
          {activeSection === 'config' && <ConfigView />}
          {activeSection === 'menu' && <MenuView />}
          {activeSection === 'analytics' && (
            <div className="text-center py-20">
              <BarChart3 size={64} className="mx-auto text-gray-400 mb-4" />
              <h3 className="text-2xl font-bold text-gray-800">Análisis y Reportes</h3>
              <p className="text-gray-600 mt-2">Próximamente...</p>
            </div>
          )}
          {activeSection === 'billing' && (
            <div className="text-center py-20">
              <CreditCard size={64} className="mx-auto text-gray-400 mb-4" />
              <h3 className="text-2xl font-bold text-gray-800">Facturación</h3>
              <p className="text-gray-600 mt-2">Gestión de suscripciones próximamente...</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminPanel;