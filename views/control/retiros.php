<link rel="stylesheet" href="/assets/css/control.css">

<div class="control-pagina">

    <header class="control-encabezado">
        <div>
            <h1><i class="fa-solid fa-bag-shopping"></i> Retiro de pedidos web</h1>
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
            <p id="camara-mensaje">Activá la cámara para escanear el QR de retiro del pedido.</p>
            <button type="button" id="btn-camara" class="control-boton">Activar cámara</button>
        </div>
    </section>

    <!-- Pedido escaneado -->
    <section id="resultado" class="control-resultado" hidden aria-live="polite">
        <div class="control-resultado-cabecera">
            <i id="resultado-icono" class="fa-solid"></i>
            <div>
                <h2 id="resultado-titulo"></h2>
                <p id="resultado-mensaje"></p>
            </div>
        </div>

        <p id="resultado-error" class="retiro-error" hidden></p>

        <div id="pedido" hidden>
            <dl id="pedido-datos" class="control-resultado-entrada"></dl>
            <ul id="pedido-items" class="retiro-items"></ul>
            <div id="pedido-historial" class="retiro-historial"></div>
        </div>

        <div class="control-resultado-acciones">
            <button type="button" id="btn-entregar" class="control-boton" hidden>
                <i class="fa-solid fa-hand-holding"></i> Entregar <span id="entregar-total"></span>
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
        <label for="codigo-manual">¿No lee el QR? Escribí el código del pedido</label>
        <div class="control-manual-fila">
            <input type="text" id="codigo-manual" name="codigo" placeholder="Ej: 3F9A12C0" maxlength="41"
                autocapitalize="characters" spellcheck="false">
            <button type="submit" class="control-boton"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        </div>
    </form>
</div>

<script src="/assets/js/vendor/html5-qrcode.min.js"></script>
<script src="/assets/js/control-escaner.js"></script>
<script>
(function () {
    const SEGUNDOS_DESHACER = 15;
    const el = (id) => document.getElementById(id);
    const resultado = el('resultado');

    let ocupado = false;          // hay un pedido abierto en pantalla: la cámara no lo reemplaza
    let codigoActual = null;
    let cantidades = {};          // id_detalle → cantidad a entregar ahora
    let temporizadorDeshacer = null;

    const iconos = { verde: 'fa-circle-check', pendiente: 'fa-bag-shopping', rojo: 'fa-circle-xmark' };

    async function enviar(url, cuerpo) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(cuerpo)
        });
        return res.json();
    }

    function texto(etiqueta, contenido, clase) {
        const nodo = document.createElement(etiqueta);
        nodo.textContent = contenido;
        if (clase) nodo.className = clase;
        return nodo;
    }

    function mostrar(r) {
        clearInterval(temporizadorDeshacer);
        resultado.className = 'control-resultado ' + r.resultado;
        el('resultado-icono').className = 'fa-solid ' + (iconos[r.resultado] || 'fa-circle-info');
        el('resultado-titulo').textContent = r.titulo;
        el('resultado-mensaje').textContent = r.mensaje;
        el('resultado-error').hidden = !r.error;
        el('resultado-error').textContent = r.error || '';

        const p = r.pedido;
        el('pedido').hidden = !p;
        cantidades = {};
        if (p) {
            const datos = el('pedido-datos');
            datos.innerHTML = '';
            [['Pedido', p.numero], ['Cliente', p.cliente], ['Pagado', p.fecha], ['Código', p.codigo]]
                .filter(([, v]) => v)
                .forEach(([k, v]) => datos.append(texto('dt', k), texto('dd', v)));

            const lista = el('pedido-items');
            lista.innerHTML = '';
            p.items.forEach((item) => {
                // Por defecto se entrega todo lo que queda; se puede bajar para un retiro parcial
                if (r.puede_entregar) cantidades[item.id_detalle] = item.quedan;
                lista.append(filaItem(item, r.puede_entregar));
            });

            const historial = el('pedido-historial');
            historial.innerHTML = '';
            if (p.historial.length) {
                historial.append(texto('h3', 'Ya entregado'));
                const ul = document.createElement('ul');
                p.historial.forEach((h) => ul.append(texto('li', `${h.fecha} · ${h.detalle}` + (h.usuario ? ` (${h.usuario})` : ''))));
                historial.append(ul);
            }
        }

        el('btn-entregar').hidden = !r.puede_entregar;
        actualizarTotal();
        el('btn-deshacer').hidden = !r.puede_deshacer;
        if (r.puede_deshacer) iniciarCuentaDeshacer();

        resultado.hidden = false;
        resultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        if (navigator.vibrate) navigator.vibrate(r.resultado === 'rojo' ? [200, 100, 200] : 120);
        ocupado = !!p && r.resultado !== 'rojo';
    }

    function filaItem(item, editable) {
        const li = document.createElement('li');
        li.className = 'retiro-item' + (item.quedan === 0 ? ' completo' : '');
        li.dataset.idDetalle = item.id_detalle;

        const info = document.createElement('div');
        info.append(texto('strong', item.nombre), texto('span', item.tipo, 'retiro-tipo'));
        info.append(texto('small', item.quedan === 0
            ? `Compró ${item.comprado} · ✓ retirado`
            : `Compró ${item.comprado} · retiró ${item.entregado} · quedan ${item.quedan}`));
        li.append(info);

        if (editable && item.quedan > 0) {
            const control = document.createElement('div');
            control.className = 'retiro-cantidad';
            const menos = texto('button', '−'); menos.type = 'button'; menos.setAttribute('aria-label', 'Una menos');
            const valor = texto('output', String(item.quedan));
            const mas = texto('button', '+'); mas.type = 'button'; mas.setAttribute('aria-label', 'Una más');
            const cambiar = (delta) => {
                const nueva = Math.min(item.quedan, Math.max(0, cantidades[item.id_detalle] + delta));
                cantidades[item.id_detalle] = nueva;
                valor.textContent = String(nueva);
                actualizarTotal();
            };
            menos.addEventListener('click', () => cambiar(-1));
            mas.addEventListener('click', () => cambiar(1));
            control.append(menos, valor, mas);
            li.append(control);
        }
        return li;
    }

    function actualizarTotal() {
        const total = Object.values(cantidades).reduce((a, b) => a + b, 0);
        el('entregar-total').textContent = total ? `(${total})` : '';
        el('btn-entregar').disabled = total === 0;
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

    async function buscar(codigo) {
        codigo = codigo.trim();
        if (!codigo || ocupado) return;
        codigoActual = codigo;
        ocupado = true;
        try {
            const r = await enviar('/control/retiros/buscar', { codigo });
            if (r.ok === false) throw new Error(r.mensaje);
            mostrar(r);
        } catch (e) {
            ocupado = false;
            mostrar({ resultado: 'rojo', titulo: 'Error', mensaje: e.message || 'No se pudo buscar, probá de nuevo' });
        }
    }

    el('btn-entregar').addEventListener('click', async () => {
        el('btn-entregar').disabled = true;
        try {
            const r = await enviar('/control/retiros/entregar', { codigo: codigoActual, items: cantidades });
            if (r.ok === false) throw new Error(r.mensaje);
            mostrar(r);
        } catch (e) {
            mostrar({ resultado: 'rojo', titulo: 'Error', mensaje: e.message });
        }
    });

    el('btn-deshacer').addEventListener('click', async () => {
        clearInterval(temporizadorDeshacer);
        const r = await enviar('/control/retiros/deshacer', { codigo: codigoActual });
        mostrar(r.ok === false ? { resultado: 'rojo', titulo: 'Error', mensaje: r.mensaje } : r);
    });

    el('btn-cerrar-resultado').addEventListener('click', () => {
        clearInterval(temporizadorDeshacer);
        resultado.hidden = true;
        ocupado = false;
        codigoActual = null;
    });

    el('form-manual').addEventListener('submit', (e) => {
        e.preventDefault();
        ocupado = false; // a mano siempre se busca, aunque haya un pedido abierto
        const input = el('codigo-manual');
        buscar(input.value);
        input.value = '';
    });

    el('btn-camara').addEventListener('click', () => iniciarEscaner((t) => buscar(t)));
})();
</script>
