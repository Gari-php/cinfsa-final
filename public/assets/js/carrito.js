(function() {
    'use strict';

    if (window.CarritoManager) return;

    class CarritoManager {
        constructor() {
            this.inicializando = false;
            this.init();
        }

        async init() {
            if (this.inicializando) return;
            this.inicializando = true;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                await this.setup();
            }
        }

        async setup() {
            this.bindEvents();
            await this.sincronizar();
            setInterval(() => this.sincronizar(), 30000);
        }

        bindEvents() {
            const btnCarrito = document.getElementById('carritoNavBtn');
            if (btnCarrito) {
                btnCarrito.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await this.cargarItems();
                    this.toggle();
                });
            }

            const btnCerrar = document.getElementById('btnCerrarCarrito');
            if (btnCerrar) btnCerrar.addEventListener('click', () => this.cerrar());

            const btnVaciar = document.getElementById('btnVaciarCarrito');
            if (btnVaciar) btnVaciar.addEventListener('click', () => this.vaciar());

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.carrito-nav')) this.cerrar();
            });
        }

        async sincronizar() {
            try {
                const response = await fetch('/api/carrito/contar');
                if (!response.ok) {
                    console.warn('Respuesta no OK:', response.status);
                    return;
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.warn('Respuesta no es JSON');
                    return;
                }
                
                const data = await response.json();
                console.log('Datos recibidos:', data);
                
                if (data.ok) {
                    this.actualizarContador(data.total);
                }
            } catch (error) {
                console.error('Error al sincronizar:', error);
            }
        }

        async cargarItems() {
            try {
                const response = await fetch('/api/carrito/items');
                if (!response.ok) return;
                
                const data = await response.json();
                console.log('Items cargados:', data);
                
                if (data.ok) {
                    this.mostrarItems(data.items);
                }
            } catch (error) {
                console.error('Error cargando items:', error);
            }
        }

        mostrarItems(items) {
            const vacio = document.getElementById('carritoVacio');
            const tabla = document.getElementById('listaCarrito');
            const footer = document.getElementById('carritoFooter');
            const tbody = document.getElementById('carritoTableBody');
            const totalAmount = document.getElementById('carritoTotalAmount');

            if (!items || items.length === 0) {
                if (vacio) vacio.style.display = 'block';
                if (tabla) tabla.style.display = 'none';
                if (footer) footer.style.display = 'none';
                return;
            }

            if (vacio) vacio.style.display = 'none';
            if (tabla) tabla.style.display = 'table';
            if (footer) footer.style.display = 'block';

            if (tbody) {
                tbody.innerHTML = '';
                let total = 0;

                items.forEach(item => {
                    const subtotal = parseFloat(item.precio) * parseInt(item.cantidad);
                    total += subtotal;

                    const esButaca = item.tipo_producto === 'butacas';
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <img src="${item.imagen}" alt="${item.nombre}" class="item-imagen" 
                                 onerror="this.src='/assets/img/cart.png'">
                        </td>
                        <td class="item-nombre" title="${item.nombre}">${item.nombre}</td>
                        <td>
                            ${esButaca ? `<span class="cantidad-fija">${item.cantidad}</span>` : `
                            <div class="cantidad-controles-tabla">
                                <button class="btn-cantidad-tabla" onclick="window.carritoGlobal.cambiarCantidadItem('${item.id}', '${item.tipo_producto}', -1)">-</button>
                                <span class="cantidad-display-tabla">${item.cantidad}</span>
                                <button class="btn-cantidad-tabla" onclick="window.carritoGlobal.cambiarCantidadItem('${item.id}', '${item.tipo_producto}', 1)">+</button>
                            </div>`}
                        </td>
                        <td class="item-precio">$${subtotal.toLocaleString('es-AR')}</td>
                        <td>
                            <button class="btn-eliminar-tabla" onclick="window.carritoGlobal.eliminarItem('${item.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                if (totalAmount) {
                    totalAmount.textContent = total.toLocaleString('es-AR');
                }
            }
        }

        actualizarContador(total) {
            console.log('Actualizando contador a:', total);
            const contador = document.getElementById('carritoCount');
            if (contador) {
                contador.textContent = total;
                contador.style.display = total > 0 ? 'flex' : 'none';
            } else {
                console.error('No se encontró el elemento carritoCount');
            }
        }

        async agregar(idProducto, tipo, cantidad = 1) {
            try {
                console.log('Agregando:', { idProducto, tipo, cantidad });
                
                const response = await fetch('/carrito/agregar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_producto: idProducto,
                        tipo: tipo,
                        cantidad: cantidad
                    })
                });

                const data = await response.json();
                console.log('Respuesta agregar:', data);
                
                if (data.ok) {
                    this.mostrarNotificacion(data.mensaje || 'Producto agregado');
                    await this.sincronizar();
                } else {
                    this.mostrarNotificacion(data.mensaje || 'Error al agregar', 'error');
                }

                return data.ok;
            } catch (error) {
                console.error('Error agregando:', error);
                this.mostrarNotificacion('Error al agregar producto', 'error');
                return false;
            }
        }

        async cambiarCantidadItem(idItem, tipo, incremento) {
            try {
                const response = await fetch('/carrito/actualizar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_item: idItem,
                        incremento: incremento
                    })
                });

                const data = await response.json();
                if (data.ok) {
                    await this.cargarItems();
                    await this.sincronizar();
                } else {
                    this.mostrarNotificacion(data.mensaje || 'Error al actualizar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async eliminarItem(idItem) {
            mostrarAlertaPersonalizada(
                '¿Eliminar producto?',
                '¿Estás seguro de que deseas eliminar este producto del carrito?',
                async () => {
                    try {
                        const response = await fetch('/carrito/eliminar', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id_item: idItem })
                        });

                        const data = await response.json();
                        if (data.ok) {
                            this.mostrarNotificacion('Producto eliminado');
                            await this.cargarItems();
                            await this.sincronizar();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            );
        }

        async vaciar() {
            mostrarAlertaPersonalizada(
                '¿Vaciar carrito?',
                'Se eliminarán todos los productos del carrito. Esta acción no se puede deshacer.',
                async () => {
                    try {
                        const response = await fetch('/carrito/vaciar', { method: 'POST' });
                        const data = await response.json();
                        
                        if (data.ok) {
                            this.mostrarNotificacion('Carrito vaciado correctamente');
                            await this.cargarItems();
                            await this.sincronizar();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            );
        }

        toggle() {
            const dropdown = document.getElementById('carritoDropdown');
            if (dropdown) dropdown.classList.toggle('active');
        }

        cerrar() {
            const dropdown = document.getElementById('carritoDropdown');
            if (dropdown) dropdown.classList.remove('active');
        }

        mostrarNotificacion(mensaje, tipo = 'success') {
            const notif = document.getElementById('notificacion');
            if (notif) {
                notif.textContent = mensaje;
                notif.className = 'notificacion show ' + tipo;
                setTimeout(() => notif.classList.remove('show'), 3000);
            }
        }
    }

    window.CarritoManager = CarritoManager;
    window.carritoGlobal = new CarritoManager();

    window.cambiarCantidad = function(id, incremento) {
        const cantidadEl = document.getElementById(`cantidad-${id}`);
        if (cantidadEl) {
            let cantidad = parseInt(cantidadEl.textContent) + incremento;
            if (cantidad < 1) cantidad = 1;
            if (cantidad > 99) cantidad = 99;
            cantidadEl.textContent = cantidad;
        }
    };

    window.cambiarCantidadProducto = (button, incremento) => {
        window.cambiarCantidad(button.getAttribute('data-id'), incremento);
    };

    window.agregarProductoCantina = async function(button) {
        if (!window.carritoGlobal) return;
        const id = button.getAttribute('data-id');
        const cantidadEl = document.getElementById(`cantidad-${id}`);
        const cantidad = cantidadEl ? parseInt(cantidadEl.textContent) : 1;
        
        const resultado = await window.carritoGlobal.agregar(id, 'cantina', cantidad);
        if (resultado && cantidadEl) cantidadEl.textContent = '1';
    };

    window.cambiarCantidadMaquina = (button, incremento) => {
        window.cambiarCantidad(button.getAttribute('data-id'), incremento);
    };

    window.agregarMaquinaAlCarrito = async function(button) {
        if (!window.carritoGlobal) return;
        const id = button.getAttribute('data-id');
        const cantidadEl = document.getElementById(`cantidad-${id}`);
        const cantidad = cantidadEl ? parseInt(cantidadEl.textContent) : 1;
        
        const resultado = await window.carritoGlobal.agregar(id, 'fichas', cantidad);
        if (resultado && cantidadEl) cantidadEl.textContent = '1';
    };

    console.log('✅ Sistema de carrito inicializado');
})();