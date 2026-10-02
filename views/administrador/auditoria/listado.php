<?php
use Classes\Paginador;

// Filtros activos (se mantienen al paginar y al exportar)
$filtrosActivos = array_filter($filtros, fn($v) => $v !== '');
$urlExportar = '/administrador/auditoria/exportar' . ($filtrosActivos ? '?' . http_build_query($filtrosActivos) : '');

// Una fila se resalta si es un login fallido o un cierre de caja con diferencia
$resaltar = function (array $r) use ($destacadas): bool {
    if (in_array($r['accion'], $destacadas, true)) return true;
    if ($r['accion'] === 'caja.cerrar') {
        $despues = json_decode($r['datos_despues'] ?? '', true);
        return !empty($despues['diferencia']);
    }
    return false;
};

// Ícono por categoría de acción
$icono = function (string $accion): string {
    return match (explode('.', $accion)[0]) {
        'sesion' => 'fa-right-to-bracket',
        'entrada', 'orden' => 'fa-ticket',
        'devolucion' => 'fa-rotate-left',
        'caja' => 'fa-cash-register',
        'precio' => 'fa-tag',
        'usuario' => 'fa-user',
        'permisos' => 'fa-key',
        'butaca' => 'fa-chair',
        'funcion' => 'fa-clapperboard',
        'pelicula' => 'fa-film',
        default => 'fa-circle-info',
    };
};
?>
<div class="listado auditoria" data-modulo="auditoria">

    <h1><i class="fa-solid fa-shield-halved"></i> Registro de auditoría</h1>
    <p class="auditoria-subtitulo">Quién hizo cada acción sensible, cuándo y desde dónde. Es de solo lectura: los registros no se pueden editar ni borrar.</p>

    <nav class="nav-listado">
        <ul>
            <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
            <li>
                <a href="<?php echo s($urlExportar); ?>" class="auditoria-exportar" title="Exporta lo que coincide con los filtros actuales">
                    <i class="fa-solid fa-file-excel"></i> Exportar CSV
                </a>
            </li>
        </ul>
    </nav>

    <form method="GET" action="/administrador/auditoria/listado" class="auditoria-filtros">
        <div class="campo-filtro">
            <label for="filtro-usuario">Usuario</label>
            <select name="usuario" id="filtro-usuario">
                <option value="">Todos</option>
                <option value="<?php echo s($sinUsuario); ?>" <?php echo $filtros['usuario'] === $sinUsuario ? 'selected' : ''; ?>>(sin usuario)</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?php echo s($u); ?>" <?php echo $filtros['usuario'] === $u ? 'selected' : ''; ?>><?php echo s($u); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="filtro-accion">Acción</label>
            <select name="accion" id="filtro-accion">
                <option value="">Todas</option>
                <?php foreach ($acciones as $clave => $nombre): ?>
                    <option value="<?php echo s($clave); ?>" <?php echo $filtros['accion'] === $clave ? 'selected' : ''; ?>><?php echo s($nombre); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="filtro-desde">Desde</label>
            <input type="date" name="fecha_desde" id="filtro-desde" value="<?php echo s($filtros['fecha_desde']); ?>">
        </div>

        <div class="campo-filtro">
            <label for="filtro-hasta">Hasta</label>
            <input type="date" name="fecha_hasta" id="filtro-hasta" value="<?php echo s($filtros['fecha_hasta']); ?>">
        </div>

        <div class="campo-filtro campo-busqueda">
            <label for="filtro-busqueda">Buscar en el detalle</label>
            <input type="text" name="busqueda" id="filtro-busqueda" placeholder="Ej: entrada 242, admin1, 2D" value="<?php echo s($filtros['busqueda']); ?>">
        </div>

        <div class="acciones-filtro">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
            <?php if ($filtrosActivos): ?>
                <a href="/administrador/auditoria/listado" class="limpiar"><i class="fa-solid fa-xmark"></i> Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="auditoria-tabla-contenedor">
        <table class="tabla-listado tabla-auditoria">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Detalle</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="5" class="auditoria-vacio">
                            <i class="fa-solid fa-inbox"></i>
                            <?php echo $filtrosActivos ? 'No hay registros que coincidan con los filtros.' : 'Todavía no hay acciones registradas.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($registros as $r): ?>
                    <tr class="<?php echo $resaltar($r) ? 'fila-destacada' : ''; ?>">
                        <td class="col-fecha"><?php echo date('d/m/Y H:i:s', strtotime($r['fecha'])); ?></td>
                        <td>
                            <?php if ($r['nombre_usuario']): ?>
                                <strong><?php echo s($r['nombre_usuario']); ?></strong>
                                <small><?php echo s($r['perfil']); ?></small>
                            <?php else: ?>
                                <span class="sin-usuario">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="etiqueta-accion">
                                <i class="fa-solid <?php echo $icono($r['accion']); ?>"></i>
                                <?php echo s(\Classes\Auditoria::nombreAccion($r['accion'])); ?>
                            </span>
                        </td>
                        <td class="col-detalle">
                            <?php if ($resaltar($r)): ?><i class="fa-solid fa-triangle-exclamation icono-alerta" title="Revisar"></i><?php endif; ?>
                            <?php echo s($r['descripcion']); ?>
                        </td>
                        <td>
                            <button type="button" class="btn-ver-detalle"
                                data-fecha="<?php echo s(date('d/m/Y H:i:s', strtotime($r['fecha']))); ?>"
                                data-usuario="<?php echo s($r['nombre_usuario'] ?? '—'); ?>"
                                data-perfil="<?php echo s($r['perfil'] ?? ''); ?>"
                                data-accion="<?php echo s(\Classes\Auditoria::nombreAccion($r['accion'])); ?>"
                                data-descripcion="<?php echo s($r['descripcion']); ?>"
                                data-ip="<?php echo s($r['ip'] ?? ''); ?>"
                                data-antes="<?php echo s($r['datos_antes'] ?? ''); ?>"
                                data-despues="<?php echo s($r['datos_despues'] ?? ''); ?>">
                                <i class="fa-solid fa-eye"></i> Ver
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php echo $paginador->render('/administrador/auditoria/listado', $filtrosActivos); ?>
</div>

<!-- Detalle de un registro -->
<div class="auditoria-modal" id="auditoria-modal" hidden>
    <div class="auditoria-modal-contenido" role="dialog" aria-modal="true" aria-labelledby="auditoria-modal-titulo">
        <button type="button" class="auditoria-modal-cerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        <h2 id="auditoria-modal-titulo"></h2>
        <p class="auditoria-modal-descripcion"></p>
        <dl class="auditoria-modal-datos"></dl>
        <div class="auditoria-modal-cambios"></div>
    </div>
</div>

<?php echo Paginador::renderCSS(); ?>

<script>
(function () {
    const modal = document.getElementById('auditoria-modal');

    const texto = (v) => {
        if (v === null || v === undefined || v === '') return '—';
        if (Array.isArray(v)) return v.join(', ');
        if (typeof v === 'object') return JSON.stringify(v);
        return String(v);
    };
    const nombreCampo = (c) => c.replace(/_/g, ' ').replace(/^./, (l) => l.toUpperCase());
    const escapar = (s) => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    const leer = (json) => { try { return json ? JSON.parse(json) : null; } catch (e) { return null; } };

    function abrir(boton) {
        const d = boton.dataset;
        modal.querySelector('#auditoria-modal-titulo').textContent = d.accion;
        modal.querySelector('.auditoria-modal-descripcion').textContent = d.descripcion;
        modal.querySelector('.auditoria-modal-datos').innerHTML =
            `<dt>Fecha</dt><dd>${escapar(d.fecha)}</dd>` +
            `<dt>Usuario</dt><dd>${escapar(d.usuario)}${d.perfil ? ' · ' + escapar(d.perfil) : ''}</dd>` +
            `<dt>IP</dt><dd>${escapar(d.ip || '—')}</dd>`;

        const antes = leer(d.antes) || {};
        const despues = leer(d.despues) || {};
        const campos = [...new Set([...Object.keys(antes), ...Object.keys(despues)])];
        const zona = modal.querySelector('.auditoria-modal-cambios');

        if (campos.length === 0) {
            zona.innerHTML = '';
        } else {
            const hayAntes = Object.keys(antes).length > 0;
            zona.innerHTML = `
                <table class="tabla-cambios">
                    <thead><tr><th>Dato</th>${hayAntes ? '<th>Antes</th>' : ''}<th>${hayAntes ? 'Después' : 'Valor'}</th></tr></thead>
                    <tbody>${campos.map((c) => `
                        <tr>
                            <td>${escapar(nombreCampo(c))}</td>
                            ${hayAntes ? `<td class="valor-antes">${escapar(texto(antes[c]))}</td>` : ''}
                            <td class="valor-despues">${escapar(texto(despues[c]))}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>`;
        }

        modal.hidden = false;
        modal.querySelector('.auditoria-modal-cerrar').focus();
    }

    const cerrar = () => { modal.hidden = true; };

    document.querySelectorAll('.btn-ver-detalle').forEach((b) => b.addEventListener('click', () => abrir(b)));
    modal.querySelector('.auditoria-modal-cerrar').addEventListener('click', cerrar);
    modal.addEventListener('click', (e) => { if (e.target === modal) cerrar(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) cerrar(); });
})();
</script>

<style>
.auditoria-subtitulo {
    color: #a0aec0;
    margin: -0.5rem 0 1.2rem;
}

.auditoria-exportar {
    background: #1d6f42 !important;
}

.auditoria-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem 1rem;
    align-items: flex-end;
    background: #1a202c;
    border: 1px solid #4a5568;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.2rem;
}

.auditoria-filtros .campo-filtro {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 150px;
}

.auditoria-filtros .campo-busqueda {
    flex: 1 1 220px;
}

.auditoria-filtros label {
    color: #a0aec0;
    font-size: 0.85rem;
    font-weight: 600;
}

.auditoria-filtros select,
.auditoria-filtros input {
    background: #2d3748;
    color: #e2e8f0;
    border: 1px solid #4a5568;
    border-radius: 8px;
    padding: 0.5rem 0.6rem;
    font-size: 0.9rem;
}

.auditoria-filtros select:focus,
.auditoria-filtros input:focus {
    outline: none;
    border-color: #ed850f;
}

.auditoria-filtros .acciones-filtro {
    display: flex;
    gap: 0.5rem;
}

.auditoria-filtros button,
.auditoria-filtros .limpiar {
    border: none;
    border-radius: 8px;
    padding: 0.55rem 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
}

.auditoria-filtros button {
    background: #ed850f;
    color: #fff;
}

.auditoria-filtros .limpiar {
    background: #4a5568;
    color: #fff;
}

.auditoria-tabla-contenedor {
    overflow-x: auto;
}

.tabla-auditoria td {
    vertical-align: middle;
}

.tabla-auditoria .col-fecha {
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.tabla-auditoria td small {
    display: block;
    color: #a0aec0;
    font-size: 0.75rem;
}

.tabla-auditoria .sin-usuario {
    color: #718096;
}

.etiqueta-accion {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    white-space: nowrap;
    background: rgba(237, 133, 15, 0.12);
    color: #f6ad55;
    border-radius: 999px;
    padding: 0.2rem 0.7rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.tabla-auditoria .col-detalle {
    min-width: 280px;
}

/* Más específico que ".listado .tabla-listado td" (listado.css), que fija el fondo gris de las celdas */
.listado .tabla-auditoria tr.fila-destacada td {
    background-color: #4a2c2c;
}

.icono-alerta {
    color: #f87171;
    margin-right: 0.3rem;
}

.btn-ver-detalle {
    background: transparent;
    color: #ed850f;
    border: 1px solid #ed850f;
    border-radius: 6px;
    padding: 0.3rem 0.7rem;
    cursor: pointer;
    white-space: nowrap;
}

.btn-ver-detalle:hover {
    background: #ed850f;
    color: #fff;
}

.auditoria-vacio {
    text-align: center;
    padding: 40px !important;
    color: #a0aec0;
}

.auditoria-vacio i {
    margin-right: 0.4rem;
}

/* Modal de detalle */
.auditoria-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    z-index: 2000;
}

.auditoria-modal[hidden] {
    display: none;
}

.auditoria-modal-contenido {
    position: relative;
    background: #1a202c;
    border: 1px solid #ed850f;
    border-radius: 12px;
    padding: 1.5rem;
    width: 100%;
    max-width: 620px;
    max-height: 90vh;
    overflow-y: auto;
    color: #e2e8f0;
}

.auditoria-modal-contenido h2 {
    color: #ed850f;
    margin: 0 2rem 0.5rem 0;
    font-size: 1.3rem;
}

.auditoria-modal-cerrar {
    position: absolute;
    top: 12px;
    right: 12px;
    background: none;
    border: none;
    color: #a0aec0;
    font-size: 1.2rem;
    cursor: pointer;
}

.auditoria-modal-cerrar:hover {
    color: #ed850f;
}

.auditoria-modal-descripcion {
    margin-bottom: 1rem;
}

.auditoria-modal-datos {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.3rem 1rem;
    margin: 0 0 1rem;
}

.auditoria-modal-datos dt {
    color: #a0aec0;
    font-weight: 600;
}

.auditoria-modal-datos dd {
    margin: 0;
    word-break: break-word;
}

.tabla-cambios {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.tabla-cambios th,
.tabla-cambios td {
    border: 1px solid #4a5568;
    padding: 0.45rem 0.6rem;
    text-align: left;
    word-break: break-word;
}

.tabla-cambios th {
    background: #2d3748;
    color: #a0aec0;
}

.tabla-cambios .valor-antes {
    color: #fca5a5;
}

.tabla-cambios .valor-despues {
    color: #86efac;
}
</style>
