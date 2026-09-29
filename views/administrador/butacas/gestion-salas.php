<!-- views/administrador/butacas/gestion-salas.php -->
<div class="gestion-salas-container">
    <div class="header-gestion">
        <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        <h1>Gestión de Butacas por Sala</h1>
        <p>Selecciona una sala para gestionar sus butacas</p>
    </div>

    <div class="salas-grid">
        <?php if (!empty($salas)): ?>
            <?php foreach ($salas as $sala): ?>
                <div class="sala-card">
                    <div class="sala-header">
                        <h3>Sala <?php echo $sala['id_sala']; ?></h3>
                        <div class="sala-info">
                            <span><i class="fas fa-users"></i> <?php echo $sala['capacidad_sala']; ?> personas</span>
                            <span><i class="fas fa-th"></i> <?php echo $sala['filas_sala']; ?>×<?php echo $sala['columnas_sala']; ?></span>
                        </div>
                    </div>

                    <div class="sala-acciones">
                        <a href="/administrador/butacas/sala?id_sala=<?php echo $sala['id_sala']; ?>"
                            class="btn-gestionar">
                            <i class="fas fa-couch"></i>
                            Gestionar Butacas
                        </a>

                        <button onclick="generarButacasSala(<?php echo $sala['id_sala']; ?>)"
                            class="btn-generar">
                            <i class="fas fa-magic"></i>
                            Generar Butacas
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-salas">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>No hay salas disponibles</h3>
                <p>Primero debes crear salas en el sistema</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .gestion-salas-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        background-color: #363130;
        min-height: 100vh;
    }

    .header-gestion {
        text-align: center;
        margin-bottom: 2rem;
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        border: 2px solid #ed850f;
    }

    .header-gestion h1 {
        color: #ed850f;
        font-size: 2.5rem;
        font-weight: bold;
        margin: 1rem 0;
    }

    .header-gestion p {
        color: #a0aec0;
        font-size: 1.1rem;
    }

    .nav-gestion {
        display: flex;
        justify-content: center;
        margin-bottom: 2rem;
    }

    .btn-volver {
        background: transparent;
        color: #e2e8f0;
        border: 2px solid #4a5568;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-volver:hover {
        border-color: #ed850f;
        color: #ed850f;
        background: rgba(237, 133, 15, 0.08);
    }

    .form-logo {
        width: 80px;
        height: auto;
        margin-bottom: 1rem;
    }

    .salas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(350px, 100%), 1fr));
        gap: 2rem;
    }

    .sala-card {
        background: #1a202c;
        border: 1px solid #4a5568;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        transition: all 0.3s ease;
    }

    .sala-card:hover {
        border-color: #ed850f;
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
    }

    .sala-header h3 {
        color: #ed850f;
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 1rem;
        text-align: center;
    }

    .sala-info {
        display: flex;
        justify-content: space-around;
        margin-bottom: 2rem;
        color: #a0aec0;
    }

    .sala-info span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .sala-info i {
        color: #ed850f;
    }

    .sala-acciones {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .btn-gestionar,
    .btn-generar {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        font-weight: bold;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }

    .btn-gestionar {
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: #fff;
        box-shadow: 0 4px 12px rgba(237, 133, 15, 0.3);
    }

    .btn-gestionar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(237, 133, 15, 0.45);
    }

    .btn-generar {
        background: #22c55e;
        color: white;
    }

    .btn-generar:hover {
        background: #16a34a;
        transform: translateY(-2px);
    }

    .no-salas {
        grid-column: 1 / -1;
        text-align: center;
        background: #1a202c;
        border: 1px solid #4a5568;
        padding: 3rem;
        border-radius: 15px;
        color: #a0aec0;
    }

    .no-salas i {
        font-size: 3rem;
        color: #f59e0b;
        margin-bottom: 1rem;
    }

    .no-salas h3 {
        color: #ed850f;
        margin-bottom: 1rem;
    }

    .modal-confirmacion-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    .modal-confirmacion-box {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 2px solid #ed850f;
        border-radius: 15px;
        padding: 30px;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    .modal-confirmacion-box p {
        color: #e2e8f0;
        font-size: 16px;
        margin-bottom: 25px;
        line-height: 1.5;
    }

    .modal-confirmacion-botones {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .btn-confirmar-modal,
    .btn-cancelar-modal {
        padding: 10px 24px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-confirmar-modal {
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: white;
    }

    .btn-confirmar-modal:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 133, 15, 0.4);
    }

    .btn-cancelar-modal {
        background: transparent;
        color: #e2e8f0;
        border: 2px solid #4a5568;
    }

    .btn-cancelar-modal:hover {
        border-color: #ef4444;
        color: #ef4444;
    }

    @media (max-width: 768px) {
        .gestion-salas-container {
            padding: 1rem;
        }

        .header-gestion {
            padding: 1rem;
        }

        .header-gestion h1 {
            font-size: 2rem;
        }

        .salas-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .sala-card {
            padding: 1.5rem;
        }

        .sala-info {
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
        }
    }

    @media (max-width: 480px) {
        .modal-confirmacion-box {
            padding: 22px 18px;
        }

        .modal-confirmacion-botones {
            flex-direction: column;
        }

        .btn-confirmar-modal,
        .btn-cancelar-modal {
            width: 100%;
        }
    }
</style>

<script>
    function confirmarAccionPersonalizada(mensaje) {
        return new Promise((resolve) => {
            let overlay = document.getElementById('modalConfirmacionOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'modalConfirmacionOverlay';
                overlay.className = 'modal-confirmacion-overlay';
                overlay.innerHTML = `
                    <div class="modal-confirmacion-box">
                        <p id="modalConfirmacionMensaje"></p>
                        <div class="modal-confirmacion-botones">
                            <button id="modalConfirmacionAceptar" class="btn-confirmar-modal">Confirmar</button>
                            <button id="modalConfirmacionCancelar" class="btn-cancelar-modal">Cancelar</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);
            }

            document.getElementById('modalConfirmacionMensaje').textContent = mensaje;
            overlay.style.display = 'flex';

            const btnAceptar = document.getElementById('modalConfirmacionAceptar');
            const btnCancelar = document.getElementById('modalConfirmacionCancelar');

            function limpiar(resultado) {
                overlay.style.display = 'none';
                btnAceptar.removeEventListener('click', onAceptar);
                btnCancelar.removeEventListener('click', onCancelar);
                resolve(resultado);
            }

            function onAceptar() {
                limpiar(true);
            }

            function onCancelar() {
                limpiar(false);
            }

            btnAceptar.addEventListener('click', onAceptar);
            btnCancelar.addEventListener('click', onCancelar);
        });
    }
</script>
<script type="module" src="/assets/js/formularios.js"></script>
<script>
    async function generarButacasSala(idSala) {
        const confirmado = await confirmarAccionPersonalizada(
            `¿Generar butacas automáticamente para la Sala ${idSala}?`
        );

        if (!confirmado) {
            return;
        }

        try {
            const response = await fetch('/administrador/butacas/generar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_sala: idSala
                })
            });

            const data = await response.json();

            if (data.ok) {
                mostrarAlerta(data.mensaje, 'exito');
            } else {
                mostrarAlerta('Error: ' + data.mensaje, 'error');
            }

        } catch (error) {
            mostrarAlerta('Error al generar butacas', 'error');
        }
    }
</script>