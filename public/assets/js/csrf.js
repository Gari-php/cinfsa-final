// Intercepta fetch() para agregar automáticamente el token CSRF a toda
// petición que modifique estado (POST/PUT/PATCH/DELETE), sin depender de
// que cada archivo que llama a fetch() lo agregue por su cuenta.
// Debe cargarse ANTES que cualquier otro script que use fetch().
(function () {
    if (!window.fetch || window.__csrfFetchPatched) {
        return;
    }
    window.__csrfFetchPatched = true;

    const fetchOriginal = window.fetch;

    window.fetch = function (recurso, opciones) {
        opciones = opciones || {};
        const metodo = (opciones.method || 'GET').toUpperCase();

        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(metodo) && window.CSRF_TOKEN) {
            if (opciones.headers instanceof Headers) {
                opciones.headers.set('X-CSRF-Token', window.CSRF_TOKEN);
            } else {
                opciones.headers = Object.assign({}, opciones.headers, {
                    'X-CSRF-Token': window.CSRF_TOKEN
                });
            }
        }

        return fetchOriginal.call(this, recurso, opciones);
    };
})();
