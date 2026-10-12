// Confirmación antes de cerrar sesión, para todos los perfiles.
// Cualquier enlace con data-cerrar-sesion (apunta a /logaut) pide confirmar; si se acepta,
// se va a /logaut, que cierra la sesión y la registra en la auditoría.
(function () {
    function cerrarModal(overlay) {
        overlay.remove();
        document.removeEventListener('keydown', overlay._alTeclear);
    }

    function confirmarCierre(destino) {
        if (document.getElementById('modal-cerrar-sesion')) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.id = 'modal-cerrar-sesion';
        overlay.className = 'modal-alertas-overlay';
        overlay.style.display = 'flex';
        overlay.innerHTML = `
            <div class="alerta-modal confirmacion" role="dialog" aria-modal="true" aria-labelledby="titulo-cerrar-sesion">
                <div class="alerta-modal-content confirmacion-content">
                    <div class="alerta-modal-icon" style="color:#ed850f">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </div>
                    <div class="alerta-modal-message" id="titulo-cerrar-sesion">¿Seguro que querés cerrar sesión?</div>
                    <div class="confirmacion-botones">
                        <button type="button" class="boton-modal cancelar" data-accion="cancelar">
                            <i class="fa-solid fa-xmark"></i> Cancelar
                        </button>
                        <button type="button" class="boton-modal confirmar" data-accion="confirmar">
                            <i class="fa-solid fa-right-from-bracket"></i> Sí, cerrar sesión
                        </button>
                    </div>
                </div>
            </div>
        `;

        overlay.addEventListener('click', (e) => {
            const boton = e.target.closest('[data-accion]');
            if (e.target === overlay || (boton && boton.dataset.accion === 'cancelar')) {
                cerrarModal(overlay);
            } else if (boton && boton.dataset.accion === 'confirmar') {
                window.location.href = destino;
            }
        });

        overlay._alTeclear = (e) => {
            if (e.key === 'Escape') cerrarModal(overlay);
        };
        document.addEventListener('keydown', overlay._alTeclear);

        document.body.appendChild(overlay);
        overlay.querySelector('[data-accion="cancelar"]').focus();
    }

    // Delegado en el documento: sirve también para enlaces que aparecen después (menús)
    document.addEventListener('click', (e) => {
        const enlace = e.target.closest('a[data-cerrar-sesion]');
        if (!enlace) return;
        e.preventDefault();
        confirmarCierre(enlace.getAttribute('href') || '/logaut');
    });
})();
