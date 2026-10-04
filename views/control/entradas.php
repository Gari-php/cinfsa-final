<link rel="stylesheet" href="/assets/css/control.css">

<div class="control-pagina">

    <header class="control-encabezado">
        <div>
            <h1><i class="fa-solid fa-qrcode"></i> Control de entradas</h1>
            <p><?php echo s($nombreUsuario); ?></p>
        </div>
        <nav>
            <?php if ($urlVolver): ?>
                <a href="<?php echo s($urlVolver); ?>" class="control-enlace"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            <?php endif; ?>
            <a href="/logaut" class="control-enlace"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        </nav>
    </header>

    <!-- Cámara -->
    <section class="control-camara">
        <div id="lector" class="control-lector"></div>
        <div id="camara-estado" class="control-camara-estado">
            <i class="fa-solid fa-camera"></i>
            <p id="camara-mensaje">Activá la cámara para escanear los QR de las entradas.</p>
            <button type="button" id="btn-camara" class="control-boton">Activar cámara</button>
        </div>
    </section>

    <!-- Resultado del último escaneo -->
    <section id="resultado" class="control-resultado" hidden aria-live="assertive">
        <div class="control-resultado-cabecera">
            <i id="resultado-icono" class="fa-solid"></i>
            <div>
                <h2 id="resultado-titulo"></h2>
                <p id="resultado-mensaje"></p>
            </div>
        </div>
        <dl id="resultado-entrada" class="control-resultado-entrada"></dl>
        <div class="control-resultado-acciones">
            <button type="button" id="btn-forzar" class="control-boton control-boton-forzar" hidden>
                <i class="fa-solid fa-person-walking-arrow-right"></i> Dejar pasar igual
            </button>
            <button type="button" id="btn-deshacer" class="control-boton control-boton-secundario" hidden>
                <i class="fa-solid fa-rotate-left"></i> Deshacer <span id="deshacer-cuenta"></span>
            </button>
            <button type="button" id="btn-cerrar-resultado" class="control-boton control-boton-secundario">
                <i class="fa-solid fa-check"></i> Listo
            </button>
        </div>
    </section>

    <!-- Carga manual -->
    <form id="form-manual" class="control-manual" autocomplete="off">
        <label for="codigo-manual">¿No lee el QR? Escribí el código que figura debajo</label>
        <div class="control-manual-fila">
            <input type="text" id="codigo-manual" name="codigo" placeholder="Ej: 827017D5" maxlength="41"
                autocapitalize="characters" spellcheck="false" inputmode="text">
            <button type="submit" class="control-boton"><i class="fa-solid fa-magnifying-glass"></i> Validar</button>
        </div>
    </form>

    <!-- Últimos escaneos de esta pantalla -->
    <section class="control-historial">
        <h3>Últimos escaneos</h3>
        <ul id="historial"><li class="vacio">Todavía no escaneaste ninguna entrada.</li></ul>
    </section>
</div>

<script src="/assets/js/vendor/html5-qrcode.min.js"></script>
<script src="/assets/js/control-escaner.js"></script>
<script>
(function () {
    const SEGUNDOS_DESHACER = Math.min(<?php echo (int)$segundosDeshacer; ?>, 15); // el botón se ofrece unos segundos
    const el = (id) => document.getElementById(id);
    const resultado = el('resultado');

    let ocupado = false;          // hay un pedido en curso o un amarillo esperando decisión
    let ultimoCodigo = null;
    let ultimoMomento = 0;
    let codigoActual = null;      // el del resultado que se está mostrando
    let temporizadorDeshacer = null;

    const iconos = { verde: 'fa-circle-check', amarillo: 'fa-triangle-exclamation', rojo: 'fa-circle-xmark' };

    async function enviar(url, codigo) {
        const res = await fetch(url, {
            method: 'POST',
            // X-Requested-With: si le quitan el módulo, el router responde JSON en vez de redirigir
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ codigo })
        });
        return res.json();
    }

    function mostrar(r, codigo) {
        codigoActual = codigo;
        clearInterval(temporizadorDeshacer);

        resultado.className = 'control-resultado ' + r.resultado;
        el('resultado-icono').className = 'fa-solid ' + (iconos[r.resultado] || 'fa-circle-info');
        el('resultado-titulo').textContent = r.titulo;
        el('resultado-mensaje').textContent = r.mensaje;

        const datos = el('resultado-entrada');
        datos.innerHTML = '';
        if (r.entrada) {
            [['Película', r.entrada.pelicula], ['Función', r.entrada.cuando], ['Sala', r.entrada.sala],
             ['Ubicación', r.entrada.butaca], ['Tipo', r.entrada.tipo], ['Código', r.entrada.codigo]]
                .filter(([, v]) => v)
                .forEach(([k, v]) => {
                    const dt = document.createElement('dt'); dt.textContent = k;
                    const dd = document.createElement('dd'); dd.textContent = v;
                    datos.append(dt, dd);
                });
        }

        el('btn-forzar').hidden = !r.puede_forzar;
        el('btn-deshacer').hidden = !r.puede_deshacer;
        if (r.puede_deshacer) iniciarCuentaDeshacer();

        resultado.hidden = false;
        resultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        if (navigator.vibrate) navigator.vibrate(r.resultado === 'verde' ? 120 : [200, 100, 200]);

        // Un amarillo espera que el controlador decida; el resto deja seguir escaneando
        ocupado = r.resultado === 'amarillo';
        agregarHistorial(r);
    }

    function iniciarCuentaDeshacer() {
        let quedan = SEGUNDOS_DESHACER;
        el('deshacer-cuenta').textContent = `(${quedan})`;
        temporizadorDeshacer = setInterval(() => {
            quedan--;
            el('deshacer-cuenta').textContent = `(${quedan})`;
            if (quedan <= 0) {
                clearInterval(temporizadorDeshacer);
                el('btn-deshacer').hidden = true;
            }
        }, 1000);
    }

    function agregarHistorial(r) {
        const lista = el('historial');
        lista.querySelector('.vacio')?.remove();
        const li = document.createElement('li');
        li.className = r.resultado;
        const hora = new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        li.textContent = `${hora} · ${r.titulo}` + (r.entrada ? ` · ${r.entrada.pelicula} · ${r.entrada.butaca || r.entrada.sala}` : '');
        lista.prepend(li);
        while (lista.children.length > 10) lista.lastChild.remove();
    }

    async function validar(codigo) {
        codigo = codigo.trim();
        if (!codigo || ocupado) return;

        // La cámara lee el mismo QR muchas veces por segundo: se ignora si se repite enseguida
        const ahora = Date.now();
        if (codigo === ultimoCodigo && ahora - ultimoMomento < 4000) return;
        ultimoCodigo = codigo;
        ultimoMomento = ahora;

        ocupado = true;
        try {
            const r = await enviar('/control/entradas/validar', codigo);
            if (r.ok === false) throw new Error(r.mensaje);
            mostrar(r, codigo);
        } catch (e) {
            ocupado = false;
            mostrar({ resultado: 'rojo', titulo: 'Error', mensaje: e.message || 'No se pudo validar, probá de nuevo' }, codigo);
        }
    }

    el('btn-forzar').addEventListener('click', async () => {
        try {
            const r = await enviar('/control/entradas/forzar', codigoActual);
            if (r.ok === false) throw new Error(r.mensaje);
            mostrar(r, codigoActual);
        } catch (e) {
            mostrar({ resultado: 'rojo', titulo: 'Error', mensaje: e.message }, codigoActual);
        }
    });

    el('btn-deshacer').addEventListener('click', async () => {
        clearInterval(temporizadorDeshacer);
        const r = await enviar('/control/entradas/deshacer', codigoActual);
        mostrar({
            resultado: r.ok ? 'amarillo' : 'rojo',
            titulo: r.ok ? 'Ingreso deshecho' : 'No se pudo deshacer',
            mensaje: r.mensaje
        }, codigoActual);
        ocupado = false;
        ultimoCodigo = null; // se puede volver a escanear enseguida
    });

    el('btn-cerrar-resultado').addEventListener('click', () => {
        clearInterval(temporizadorDeshacer);
        resultado.hidden = true;
        ocupado = false;
    });

    el('form-manual').addEventListener('submit', (e) => {
        e.preventDefault();
        const input = el('codigo-manual');
        ultimoCodigo = null; // a mano siempre se valida, aunque sea el mismo
        validar(input.value);
        input.value = '';
    });

    // ── Cámara ──
    el('btn-camara').addEventListener('click', () => iniciarEscaner((texto) => validar(texto)));

})();
</script>
