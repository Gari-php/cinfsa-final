// ===== SINCRONIZACIÓN CONTADOR CARRITO MÓVIL =====
document.addEventListener('DOMContentLoaded', function() {
    
    // Función para actualizar ambos contadores (desktop y móvil)
    function actualizarContadoresCarrito() {
        const carritoCountDesktop = document.getElementById('carritoCount');
        const carritoCountMobile = document.getElementById('carritoCountMobile');
        
        // Obtener items del carrito desde la API
        fetch('/api/carrito/obtener')
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    const totalItems = data.items.reduce((sum, item) => sum + item.cantidad, 0);
                    
                    // Actualizar ambos badges
                    if (carritoCountDesktop) {
                        carritoCountDesktop.textContent = totalItems;
                    }
                    if (carritoCountMobile) {
                        carritoCountMobile.textContent = totalItems;
                    }
                }
            })
            .catch(error => console.error('Error al obtener carrito:', error));
    }

    // Actualizar al cargar la página
    actualizarContadoresCarrito();

    // Actualizar periódicamente cada 30 segundos
    setInterval(actualizarContadoresCarrito, 30000);

    // Evento click en carrito móvil
    const carritoNavBtnMobile = document.getElementById('carritoNavBtnMobile');
    if (carritoNavBtnMobile) {
        carritoNavBtnMobile.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Cerrar menú hamburguesa
            const menuToggle = document.getElementById('menu-hamburguesa-toggle');
            if (menuToggle) {
                menuToggle.checked = false;
            }
            
            // Abrir dropdown del carrito (desktop) o redirigir
            const carritoDropdown = document.getElementById('carritoDropdown');
            if (window.innerWidth > 768 && carritoDropdown) {
                carritoDropdown.classList.toggle('active');
            } else {
                // En móvil, ir directo al checkout
                window.location.href = '/carrito/checkout';
            }
        });
    }

    // Cerrar menú al hacer clic en un enlace
    const menuLinks = document.querySelectorAll('.menu-hamburguesa-nav a');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            const menuToggle = document.getElementById('menu-hamburguesa-toggle');
            if (menuToggle) {
                menuToggle.checked = false;
            }
        });
    });

    // Prevenir cierre al hacer scroll dentro del menú
    const menuPanel = document.querySelector('.menu-hamburguesa-panel');
    if (menuPanel) {
        menuPanel.addEventListener('touchmove', function(e) {
            e.stopPropagation();
        });
    }

    // Buscador móvil
    const busquedaMobile = document.getElementById('busquedaMobile');
    const btnBuscarMobile = document.querySelector('.menu-buscador-mobile button');
    
    if (btnBuscarMobile) {
        btnBuscarMobile.addEventListener('click', function() {
            if (busquedaMobile && busquedaMobile.value.trim()) {
                // Aquí puedes agregar la lógica de búsqueda
                console.log('Buscando:', busquedaMobile.value);
                
                // Cerrar menú después de buscar
                const menuToggle = document.getElementById('menu-hamburguesa-toggle');
                if (menuToggle) {
                    menuToggle.checked = false;
                }
            }
        });
    }

    // Enter en buscador móvil
    if (busquedaMobile) {
        busquedaMobile.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                btnBuscarMobile.click();
            }
        });
    }
});

// ===== OBSERVADOR PARA DETECTAR CAMBIOS EN EL CARRITO =====
// Esto mantiene sincronizados ambos contadores cuando se agregan/eliminan items
if (window.MutationObserver) {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.target.id === 'carritoCount') {
                const carritoCountMobile = document.getElementById('carritoCountMobile');
                if (carritoCountMobile) {
                    carritoCountMobile.textContent = mutation.target.textContent;
                }
            } else if (mutation.target.id === 'carritoCountMobile') {
                const carritoCountDesktop = document.getElementById('carritoCount');
                if (carritoCountDesktop) {
                    carritoCountDesktop.textContent = mutation.target.textContent;
                }
            }
        });
    });

    const carritoCountDesktop = document.getElementById('carritoCount');
    const carritoCountMobile = document.getElementById('carritoCountMobile');

    if (carritoCountDesktop) {
        observer.observe(carritoCountDesktop, { 
            childList: true, 
            characterData: true, 
            subtree: true 
        });
    }

    if (carritoCountMobile) {
        observer.observe(carritoCountMobile, { 
            childList: true, 
            characterData: true, 
            subtree: true 
        });
    }
}
