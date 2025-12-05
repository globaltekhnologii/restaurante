<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Carrito</title>
</head>
<body>
    <h1>Test de Carrito</h1>
    <button onclick="testCarrito()">Probar Carrito</button>
    <div id="resultado"></div>

    <script>
        function testCarrito() {
            const resultado = document.getElementById('resultado');
            
            // 1. Verificar localStorage
            resultado.innerHTML = '<h2>1. Verificando localStorage...</h2>';
            const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            resultado.innerHTML += `<p>Carrito actual: ${JSON.stringify(carrito)}</p>`;
            resultado.innerHTML += `<p>Cantidad de items: ${carrito.length}</p>`;
            
            // 2. Agregar un producto de prueba
            resultado.innerHTML += '<h2>2. Agregando producto de prueba...</h2>';
            const productoTest = {
                id: 'test',
                nombre: 'Producto Test',
                precio: 10.00,
                imagen: '',
                cantidad: 1
            };
            
            carrito.push(productoTest);
            localStorage.setItem('carrito', JSON.stringify(carrito));
            resultado.innerHTML += `<p>✅ Producto agregado</p>`;
            resultado.innerHTML += `<p>Carrito actualizado: ${JSON.stringify(carrito)}</p>`;
            
            // 3. Verificar que se guardó
            const carritoGuardado = JSON.parse(localStorage.getItem('carrito'));
            resultado.innerHTML += '<h2>3. Verificando que se guardó...</h2>';
            resultado.innerHTML += `<p>Carrito guardado: ${JSON.stringify(carritoGuardado)}</p>`;
            
            // 4. Link a checkout
            resultado.innerHTML += '<h2>4. Ir a Checkout</h2>';
            resultado.innerHTML += '<p><a href="checkout.php" style="padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;">Ir a Checkout</a></p>';
            resultado.innerHTML += '<p><a href="carrito.php" style="padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;">Ir a Carrito</a></p>';
        }
    </script>
</body>
</html>
