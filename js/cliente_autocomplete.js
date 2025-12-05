// js/cliente_autocomplete.js - Autocomplete de clientes para checkout
class ClienteAutocomplete {
    constructor(telefonoInputId, nombreInputId, direccionInputId) {
        this.telefonoInput = document.getElementById(telefonoInputId);
        this.nombreInput = document.getElementById(nombreInputId);
        this.direccionInput = document.getElementById(direccionInputId);
        this.clienteIdInput = null;

        // Crear input hidden para cliente_id si no existe
        if (!document.getElementById('cliente_id')) {
            this.clienteIdInput = document.createElement('input');
            this.clienteIdInput.type = 'hidden';
            this.clienteIdInput.name = 'cliente_id';
            this.clienteIdInput.id = 'cliente_id';
            this.telefonoInput.form.appendChild(this.clienteIdInput);
        } else {
            this.clienteIdInput = document.getElementById('cliente_id');
        }

        this.init();
    }

    init() {
        // Agregar evento de búsqueda mientras escribe
        this.telefonoInput.addEventListener('input', () => {
            const telefono = this.telefonoInput.value.trim();
            if (telefono.length >= 7) {
                this.buscarCliente(telefono);
            }
        });

        // Limpiar datos al cambiar teléfono
        this.telefonoInput.addEventListener('change', () => {
            const telefono = this.telefonoInput.value.trim();
            if (telefono.length >= 7) {
                this.buscarCliente(telefono);
            }
        });
    }

    async buscarCliente(telefono) {
        try {
            const response = await fetch(`api/clientes.php?telefono=${encodeURIComponent(telefono)}`);
            const data = await response.json();

            if (data.success && data.cliente) {
                this.rellenarDatosCliente(data.cliente);
                this.mostrarMensaje('Cliente encontrado ✓', 'success');
            } else {
                this.limpiarDatosCliente();
                this.mostrarMensaje('Cliente nuevo', 'info');
            }
        } catch (error) {
            console.error('Error al buscar cliente:', error);
        }
    }

    rellenarDatosCliente(cliente) {
        this.clienteIdInput.value = cliente.id;

        if (this.nombreInput) {
            const nombreCompleto = (cliente.nombre + ' ' + (cliente.apellido || '')).trim();
            this.nombreInput.value = nombreCompleto;
            this.nombreInput.style.backgroundColor = '#e7f5e7';
            this.nombreInput.readOnly = true;
        }

        if (this.direccionInput && cliente.direccion_principal) {
            // Si hay direcciones, mostrar selector
            if (cliente.direcciones && cliente.direcciones.length > 0) {
                this.mostrarSelectorDirecciones(cliente.direcciones);
            } else if (cliente.direccion_principal) {
                this.direccionInput.value = cliente.direccion_principal;
                this.direccionInput.style.backgroundColor = '#e7f5e7';
            }
        }

        // Marcar campo de teléfono como verificado
        this.telefonoInput.style.backgroundColor = '#e7f5e7';
    }

    limpiarDatosCliente() {
        this.clienteIdInput.value = '';

        if (this.nombreInput) {
            this.nombreInput.style.backgroundColor = '';
            this.nombreInput.readOnly = false;
        }

        if (this.direccionInput) {
            this.direccionInput.style.backgroundColor = '';
            this.removeSelectorDirecciones();
        }

        this.telefonoInput.style.backgroundColor = '';
    }

    mostrarSelectorDirecciones(direcciones) {
        // Crear selector de direcciones si no existe
        let selector = document.getElementById('selector_direcciones');
        if (!selector) {
            selector = document.createElement('select');
            selector.id = 'selector_direcciones';
            selector.className = 'form-control';
            selector.style.marginTop = '10px';
            selector.style.padding = '10px';
            selector.style.border = '2px solid #667eea';
            selector.style.borderRadius = '8px';

            // Insertar después del input de dirección
            this.direccionInput.parentNode.insertBefore(selector, this.direccionInput.nextSibling);

            // Evento de cambio
            selector.addEventListener('change', (e) => {
                this.direccionInput.value = e.target.options[e.target.selectedIndex].dataset.direccion;
            });
        }

        // Limpiar opciones
        selector.innerHTML = '';

        // Agregar opciones
        direcciones.forEach((dir, index) => {
            const option = document.createElement('option');
            option.value = dir.id;
            option.dataset.direccion = dir.direccion;
            option.textContent = dir.alias ? `${dir.alias}: ${dir.direccion}` : dir.direccion;
            if (dir.es_principal || index === 0) {
                option.selected = true;
                this.direccionInput.value = dir.direccion;
            }
            selector.appendChild(option);
        });

        // Agregar opción de nueva dirección
        const optionNueva = document.createElement('option');
        optionNueva.value = 'nueva';
        optionNueva.textContent = '+ Nueva dirección';
        selector.appendChild(optionNueva);

        selector.addEventListener('change', (e) => {
            if (e.target.value === 'nueva') {
                this.direccionInput.value = '';
                this.direccionInput.style.backgroundColor = '';
                this.direccionInput.focus();
            }
        });
    }

    removeSelectorDirecciones() {
        const selector = document.getElementById('selector_direcciones');
        if (selector) {
            selector.remove();
        }
    }

    mostrarMensaje(mensaje, tipo) {
        // Crear o actualizar mensaje
        let mensajeDiv = document.getElementById('mensaje_cliente');
        if (!mensajeDiv) {
            mensajeDiv = document.createElement('div');
            mensajeDiv.id = 'mensaje_cliente';
            mensajeDiv.style.padding = '8px 12px';
            mensajeDiv.style.borderRadius = '6px';
            mensajeDiv.style.marginTop = '5px';
            mensajeDiv.style.fontSize = '0.9em';
            mensajeDiv.style.transition = 'all 0.3s';
            this.telefonoInput.parentNode.appendChild(mensajeDiv);
        }

        mensajeDiv.textContent = mensaje;

        // Aplicar estilos según tipo
        if (tipo === 'success') {
            mensajeDiv.style.backgroundColor = '#d4edda';
            mensajeDiv.style.color = '#155724';
            mensajeDiv.style.border = '1px solid #c3e6cb';
        } else if (tipo === 'info') {
            mensajeDiv.style.backgroundColor = '#d1ecf1';
            mensajeDiv.style.color = '#0c5460';
            mensajeDiv.style.border = '1px solid #bee5eb';
        }

        // Ocultar después de 3 segundos si es info
        if (tipo === 'info') {
            setTimeout(() => {
                mensajeDiv.style.opacity = '0';
                setTimeout(() => mensajeDiv.remove(), 300);
            }, 3000);
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // Buscar los campos en el formulario de checkout
    const telefonoInput = document.querySelector('input[name="telefono"]') ||
        document.querySelector('input[name="telefono_cliente"]') ||
        document.getElementById('telefono');

    const nombreInput = document.querySelector('input[name="nombre"]') ||
        document.querySelector('input[name="nombre_cliente"]') ||
        document.getElementById('nombre');

    const direccionInput = document.querySelector('input[name="direccion"]') ||
        document.querySelector('textarea[name="direccion"]') ||
        document.getElementById('direccion');

    if (telefonoInput && nombreInput) {
        new ClienteAutocomplete(
            telefonoInput.id || 'telefono',
            nombreInput.id || 'nombre',
            direccionInput ? (direccionInput.id || 'direccion') : null
        );

        console.log('🎯 Autocomplete de clientes iniciado');
    }
});
