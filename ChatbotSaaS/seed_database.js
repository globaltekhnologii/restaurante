// seed.js - Script para inicializar la base de datos con datos de prueba

const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');
require('dotenv').config();

// Importar modelos
const User = require('./backend/models/User');
const Restaurant = require('./backend/models/Restaurant');
const Conversation = require('./backend/models/Conversation');

// Datos de prueba
const seedData = {
  restaurants: [
    {
      ownerEmail: 'pizzeria@test.com',
      restaurantName: 'La Pizzería Italiana',
      phone: '+57 310 123 4567',
      address: 'Calle 85 #12-34, Bogotá',
      businessHours: '11:00 AM - 11:00 PM',
      chatbotName: 'PizzaBot',
      welcomeMessage: '¡Ciao! 🍕 Soy PizzaBot, tu asistente de La Pizzería Italiana. ¿Qué pizza te gustaría hoy?',
      primaryColor: '#e74c3c',
      enableReservations: true,
      enableDelivery: true,
      enableWhatsApp: true,
      whatsappNumber: '+57 310 123 4567',
      plan: 'pro',
      menuItems: [
        {
          name: 'Pizza Margarita',
          category: 'Pizzas',
          price: 28000,
          description: 'Salsa de tomate, mozzarella fresca, albahaca',
          available: true
        },
        {
          name: 'Pizza Pepperoni',
          category: 'Pizzas',
          price: 32000,
          description: 'Salsa de tomate, mozzarella, pepperoni premium',
          available: true
        },
        {
          name: 'Pizza Cuatro Quesos',
          category: 'Pizzas',
          price: 35000,
          description: 'Mozzarella, parmesano, gorgonzola, ricotta',
          available: true
        },
        {
          name: 'Pizza Hawaiana',
          category: 'Pizzas',
          price: 30000,
          description: 'Jamón, piña, mozzarella',
          available: true
        },
        {
          name: 'Lasagna Bolognesa',
          category: 'Pastas',
          price: 25000,
          description: 'Capas de pasta con carne, salsa bechamel',
          available: true
        },
        {
          name: 'Ensalada César',
          category: 'Ensaladas',
          price: 18000,
          description: 'Lechuga romana, crutones, parmesano, aderezo césar',
          available: true
        },
        {
          name: 'Coca-Cola 400ml',
          category: 'Bebidas',
          price: 4000,
          description: 'Refresco cola',
          available: true
        },
        {
          name: 'Limonada Natural',
          category: 'Bebidas',
          price: 5000,
          description: 'Limonada fresca con hierbabuena',
          available: true
        },
        {
          name: 'Tiramisú',
          category: 'Postres',
          price: 12000,
          description: 'Postre italiano tradicional con café',
          available: true
        }
      ]
    },
    {
      ownerEmail: 'burger@test.com',
      restaurantName: 'Burger Palace',
      phone: '+57 320 555 6789',
      address: 'Carrera 7 #45-67, Medellín',
      businessHours: '12:00 PM - 10:00 PM',
      chatbotName: 'BurgerBot',
      welcomeMessage: '¡Hey! 🍔 Soy BurgerBot. ¿Listo para la mejor hamburguesa de tu vida?',
      primaryColor: '#f59e0b',
      enableReservations: false,
      enableDelivery: true,
      enableWhatsApp: false,
      plan: 'basic',
      menuItems: [
        {
          name: 'Clásica',
          category: 'Hamburguesas',
          price: 18000,
          description: 'Carne angus, lechuga, tomate, cebolla, queso',
          available: true
        },
        {
          name: 'BBQ Bacon',
          category: 'Hamburguesas',
          price: 22000,
          description: 'Carne, bacon, salsa BBQ, aros de cebolla',
          available: true
        },
        {
          name: 'Doble Queso',
          category: 'Hamburguesas',
          price: 25000,
          description: 'Doble carne, doble queso cheddar',
          available: true
        },
        {
          name: 'Papas Francesas',
          category: 'Acompañamientos',
          price: 8000,
          description: 'Papas crujientes con sal',
          available: true
        },
        {
          name: 'Aros de Cebolla',
          category: 'Acompañamientos',
          price: 9000,
          description: 'Aros empanizados crujientes',
          available: true
        },
        {
          name: 'Malteada Chocolate',
          category: 'Bebidas',
          price: 10000,
          description: 'Malteada cremosa de chocolate',
          available: true
        }
      ]
    },
    {
      ownerEmail: 'sushi@test.com',
      restaurantName: 'Sakura Sushi Bar',
      phone: '+57 315 777 8888',
      address: 'Calle 82 #10-15, Bogotá',
      businessHours: '1:00 PM - 11:00 PM',
      chatbotName: 'SakuraBot',
      welcomeMessage: 'こんにちは! 🍣 Soy SakuraBot. ¿Quieres explorar nuestro menú japonés?',
      primaryColor: '#ec4899',
      enableReservations: true,
      enableDelivery: true,
      enableWhatsApp: true,
      whatsappNumber: '+57 315 777 8888',
      plan: 'enterprise',
      menuItems: [
        {
          name: 'Roll Filadelfia',
          category: 'Rolls',
          price: 28000,
          description: 'Salmón, queso crema, aguacate (8 piezas)',
          available: true
        },
        {
          name: 'Roll California',
          category: 'Rolls',
          price: 25000,
          description: 'Cangrejo, aguacate, pepino (8 piezas)',
          available: true
        },
        {
          name: 'Nigiri Salmón',
          category: 'Nigiri',
          price: 15000,
          description: 'Salmón fresco sobre arroz (4 piezas)',
          available: true
        },
        {
          name: 'Sashimi Atún',
          category: 'Sashimi',
          price: 32000,
          description: 'Atún rojo premium (8 piezas)',
          available: true
        },
        {
          name: 'Sopa Miso',
          category: 'Entradas',
          price: 8000,
          description: 'Sopa tradicional japonesa',
          available: true
        },
        {
          name: 'Edamames',
          category: 'Entradas',
          price: 9000,
          description: 'Frijoles de soya al vapor con sal',
          available: true
        },
        {
          name: 'Té Verde',
          category: 'Bebidas',
          price: 5000,
          description: 'Té verde japonés caliente',
          available: true
        },
        {
          name: 'Sake',
          category: 'Bebidas',
          price: 18000,
          description: 'Sake japonés premium',
          available: true
        }
      ]
    }
  ],
  
  // Conversaciones de ejemplo
  conversations: [
    {
      sessionId: 'demo_session_001',
      customerPhone: '+57 300 111 2222',
      customerName: 'Juan Pérez',
      messages: [
        {
          role: 'assistant',
          content: '¡Ciao! 🍕 Soy PizzaBot, tu asistente de La Pizzería Italiana. ¿Qué pizza te gustaría hoy?'
        },
        {
          role: 'user',
          content: 'Hola! Quiero una pizza margarita grande'
        },
        {
          role: 'assistant',
          content: '¡Excelente elección! Una Pizza Margarita grande por $28.000. ¿Deseas agregar alguna bebida o postre?'
        },
        {
          role: 'user',
          content: 'Sí, una Coca-Cola'
        },
        {
          role: 'assistant',
          content: 'Perfecto! Tu pedido:\n- Pizza Margarita: $28.000\n- Coca-Cola: $4.000\n\nTotal: $32.000\n\n¿Cuál es tu dirección de entrega?'
        }
      ],
      status: 'active',
      orderPlaced: false
    }
  ]
};

async function seedDatabase() {
  try {
    console.log('🌱 Iniciando seed de la base de datos...\n');

    // Conectar a MongoDB
    await mongoose.connect(
      process.env.MONGODB_URI || 'mongodb://localhost:27017/restaurant-chatbot',
      {
        useNewUrlParser: true,
        useUnifiedTopology: true
      }
    );
    console.log('✅ Conectado a MongoDB\n');

    // Limpiar colecciones existentes
    console.log('🧹 Limpiando colecciones...');
    await User.deleteMany({});
    await Restaurant.deleteMany({});
    await Conversation.deleteMany({});
    console.log('✅ Colecciones limpiadas\n');

    // Crear restaurantes y usuarios
    console.log('📝 Creando restaurantes y usuarios...');
    const createdRestaurants = [];

    for (const restaurantData of seedData.restaurants) {
      // Crear restaurante
      const restaurant = new Restaurant(restaurantData);
      await restaurant.save();
      createdRestaurants.push(restaurant);

      // Crear usuario
      const user = new User({
        email: restaurantData.ownerEmail,
        password: 'password123', // Se hasheará automáticamente
        restaurantId: restaurant._id,
        role: 'owner'
      });
      await user.save();

      console.log(`✅ Creado: ${restaurant.restaurantName}`);
      console.log(`   Email: ${restaurantData.ownerEmail}`);
      console.log(`   Password: password123`);
      console.log(`   Items en menú: ${restaurant.menuItems.length}\n`);
    }

    // Crear conversaciones de ejemplo
    console.log('💬 Creando conversaciones de ejemplo...');
    for (const convData of seedData.conversations) {
      const conversation = new Conversation({
        ...convData,
        restaurantId: createdRestaurants[0]._id // Asociar con el primer restaurante
      });
      await conversation.save();
      console.log(`✅ Conversación creada: ${conversation.sessionId}\n`);
    }

    // Resumen
    console.log('\n========================================');
    console.log('🎉 SEED COMPLETADO EXITOSAMENTE');
    console.log('========================================\n');

    console.log('📊 RESUMEN:');
    console.log(`   Restaurantes: ${createdRestaurants.length}`);
    console.log(`   Usuarios: ${createdRestaurants.length}`);
    console.log(`   Conversaciones: ${seedData.conversations.length}\n`);

    console.log('👤 CREDENCIALES DE PRUEBA:\n');
    seedData.restaurants.forEach((r, i) => {
      console.log(`${i + 1}. ${r.restaurantName}`);
      console.log(`   Email: ${r.ownerEmail}`);
      console.log(`   Password: password123`);
      console.log(`   Plan: ${r.plan}`);
      console.log(`   URL: http://localhost:3000/admin\n`);
    });

    console.log('🚀 SIGUIENTE PASO:');
    console.log('   1. Inicia el servidor: npm run dev');
    console.log('   2. Accede al panel: http://localhost:3000/admin');
    console.log('   3. Usa cualquiera de los emails de arriba\n');

    console.log('📝 NOTAS:');
    console.log('   - Todos los passwords son: password123');
    console.log('   - Los restaurantes tienen menús completos');
    console.log('   - Hay conversaciones de ejemplo para testing');
    console.log('   - Puedes modificar datos en seed.js\n');

  } catch (error) {
    console.error('❌ Error al hacer seed:', error);
  } finally {
    await mongoose.connection.close();
    console.log('\n👋 Conexión cerrada. ¡Listo para comenzar!');
    process.exit(0);
  }
}

// Ejecutar seed
seedDatabase();
