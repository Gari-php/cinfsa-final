// Botón "ojo" para mostrar/ocultar una contraseña (iconos de Font Awesome).
// Uso: <span class="icono" data-mostrar-password="ID_DEL_INPUT" role="button" tabindex="0"
//            aria-label="Mostrar contraseña" title="Mostrar contraseña">
//          <i class="fa-solid fa-eye" aria-hidden="true"></i>
//      </span>
(function () {
    function alternar(boton) {
        const input = document.getElementById(boton.dataset.mostrarPassword);
        if (!input) return;

        const mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';

        const texto = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
        boton.innerHTML = `<i class="fa-solid ${mostrar ? 'fa-eye-slash' : 'fa-eye'}" aria-hidden="true"></i>`;
        boton.setAttribute('aria-label', texto);
        boton.setAttribute('title', texto);
    }

    document.addEventListener('click', (e) => {
        const boton = e.target.closest('[data-mostrar-password]');
        if (boton) alternar(boton);
    });

    // Accesible con teclado (Enter o barra espaciadora)
    document.addEventListener('keydown', (e) => {
        const boton = e.target.closest && e.target.closest('[data-mostrar-password]');
        if (boton && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            alternar(boton);
        }
    });
})();
