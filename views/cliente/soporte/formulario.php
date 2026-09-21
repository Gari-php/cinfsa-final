<div class="soporte-page">
    <div class="soporte-container">
        <div class="soporte-header">
            <div class="soporte-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h1>Centro de Soporte</h1>
            <p>¿Tuviste algún problema? Cuéntanos y lo resolveremos</p>
        </div>

        <!-- COMPATIBLE CON TU SISTEMA DE ALERTAS -->
        <form id="formSoporte"
            method="POST"
            action="/soporte/enviar"
            enctype="multipart/form-data"
            data-fetch="true"
            data-alerta=".soporte-container"
            class="soporte-form">

            <div class="form-row">
                <div class="form-group full-width campo">
                    <label for="asunto">
                        <i class="fas fa-tag"></i>
                        Tipo de Reclamo
                    </label>
                    <select name="asunto" id="asunto" required>
                        <option value="">Selecciona una opción</option>
                        <option value="Producto no disponible">Producto no disponible</option>
                        <option value="Producto en mal estado">Producto en mal estado/vencido</option>
                        <option value="Error en la orden">Error en la orden</option>
                        <option value="Problema con el pago">Problema con el pago</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width campo">
                    <label for="numero_orden">
                        <i class="fas fa-receipt"></i>
                        Número de Orden <span class="opcional">(Opcional)</span>
                    </label>
                    <input type="text" name="numero_orden" id="numero_orden" placeholder="Ej: ORD-20250124-001">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width campo">
                    <label for="mensaje">
                        <i class="fas fa-comment-dots"></i>
                        Describe tu problema
                    </label>
                    <textarea name="mensaje" id="mensaje" rows="6" required placeholder="Explícanos detalladamente qué sucedió..."></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width file-upload-group campo">
                    <label for="comprobante">
                        <i class="fas fa-paperclip"></i>
                        Adjuntar Comprobante <span class="opcional">(Opcional)</span>
                    </label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="comprobante" id="comprobante" accept="image/*,.pdf">
                        <label for="comprobante" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span id="file-name">Seleccionar archivo</span>
                        </label>
                    </div>
                    <small class="file-hint">
                        <i class="fas fa-info-circle"></i>
                        Puedes subir una foto o PDF del ticket/producto (Max. 5MB)
                    </small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-enviar-soporte">
                    <i class="fas fa-paper-plane"></i>
                    Enviar Reclamo
                </button>
            </div>
        </form>

        <div class="soporte-info">
            <div class="info-box">
                <i class="fas fa-clock"></i>
                <div>
                    <h4>Tiempo de Respuesta</h4>
                    <p>Te responderemos en menos de 24 horas</p>
                </div>
            </div>
            <div class="info-box">
                <i class="fas fa-envelope"></i>
                <div>
                    <h4>Respuesta por Email</h4>
                    <p>Recibirás la respuesta en tu correo electrónico</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .soporte-page {
        min-height: calc(100vh - 200px);
        padding: 2rem 1rem;
        background: #363130;
    }

    .soporte-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .soporte-header {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 2px solid #ed850f;
        border-radius: 20px 20px 0 0;
        padding: 3rem 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .soporte-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(237, 133, 15, 0.1) 0%, transparent 70%);
        animation: pulse-bg 3s ease-in-out infinite;
    }

    @keyframes pulse-bg {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }

        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    .soporte-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        background: linear-gradient(135deg, #ed850f, #f7931e);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 25px rgba(237, 133, 15, 0.4);
        position: relative;
        z-index: 1;
    }

    .soporte-icon i {
        font-size: 2.5rem !important;
        color: white;
    }

    .soporte-header h1 {
        font-size: 2.5rem !important;
        font-weight: bold;
        color: #ed850f;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }

    .soporte-header p {
        font-size: 1.1rem !important;
        color: #a0aec0;
        position: relative;
        z-index: 1;
    }

    .soporte-form {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 2px solid #ed850f;
        border-top: none;
        border-radius: 0 0 20px 20px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    .form-row {
        margin-bottom: 1.5rem;
    }

    .form-group {
        position: relative;
    }

    .form-group.full-width {
        width: 100%;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #ed850f;
        font-weight: bold;
        margin-bottom: 0.8rem;
        font-size: 15px !important;
    }

    .form-group label i {
        font-size: 16px !important;
    }

    .opcional {
        color: #a0aec0;
        font-weight: normal;
        font-size: 13px !important;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 1rem;
        border: 2px solid #4a5568;
        border-radius: 10px;
        background: #2d3748;
        color: #e2e8f0;
        font-size: 15px !important;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #ed850f;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
        background: #363130;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 150px;
        font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
    }

    .file-upload-group {
        margin-bottom: 2rem;
    }

    .file-upload-wrapper {
        position: relative;
    }

    .file-upload-wrapper input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .file-upload-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 1.5rem;
        border: 2px dashed #ed850f;
        border-radius: 10px;
        background: rgba(237, 133, 15, 0.05);
        color: #ed850f;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
        max-width: 100%;
        overflow: hidden;
    }

    .file-upload-label:hover {
        background: rgba(237, 133, 15, 0.15);
        border-color: #f7931e;
    }

    .file-upload-label i {
        flex-shrink: 0;
    }

    .file-upload-label span#file-name {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-align: left;
    }

    .file-upload-wrapper {
        position: relative;
        max-width: 100%;
        overflow: hidden;
    }

    .file-hint {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.8rem;
        color: #a0aec0;
        font-size: 13px !important;
    }

    .file-hint i {
        color: #ed850f;
    }

    .form-actions {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #4a5568;
    }

    .btn-enviar-soporte {
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: white;
        padding: 1rem 3rem;
        border: none;
        border-radius: 50px;
        font-weight: bold;
        font-size: 16px !important;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        box-shadow: 0 6px 20px rgba(237, 133, 15, 0.4);
    }

    .btn-enviar-soporte:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(237, 133, 15, 0.6);
    }

    .btn-enviar-soporte:active {
        transform: translateY(0);
    }

    .btn-enviar-soporte i {
        font-size: 18px !important;
    }

    .soporte-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .info-box {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 1px solid rgba(237, 133, 15, 0.3);
        border-radius: 15px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        transition: all 0.3s ease;
    }

    .info-box:hover {
        border-color: #ed850f;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 133, 15, 0.2);
    }

    .info-box i {
        font-size: 2rem !important;
        color: #ed850f;
        min-width: 40px;
        text-align: center;
    }

    .info-box h4 {
        color: #ed850f;
        font-size: 16px !important;
        font-weight: bold;
        margin: 0 0 0.5rem 0;
    }

    .info-box p {
        color: #a0aec0;
        font-size: 14px !important;
        margin: 0;
    }

    /* ESTILOS PARA ERRORES DE CAMPOS (Compatible con tu sistema) */
    .input-error {
        border-color: #dc3545 !important;
        background: rgba(220, 53, 69, 0.05) !important;
    }

    .mensaje-error-campo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #dc3545;
        font-size: 13px !important;
        margin-top: 0.5rem;
        padding: 0.5rem;
        background: rgba(220, 53, 69, 0.1);
        border-radius: 5px;
        border-left: 3px solid #dc3545;
    }

    .mensaje-error-campo i {
        font-size: 14px !important;
    }

    @media (max-width: 768px) {
        .soporte-page {
            padding: 1rem 0.5rem;
        }

        .soporte-header {
            padding: 2rem 1rem;
            border-radius: 15px 15px 0 0;
        }

        .soporte-header h1 {
            font-size: 1.8rem !important;
        }

        .soporte-header p {
            font-size: 1rem !important;
        }

        .soporte-icon {
            width: 60px;
            height: 60px;
        }

        .soporte-icon i {
            font-size: 2rem !important;
        }

        .soporte-form {
            padding: 1.5rem;
            border-radius: 0 0 15px 15px;
        }

        .btn-enviar-soporte {
            width: 100%;
            justify-content: center;
        }

        .soporte-info {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('comprobante');
        const fileName = document.getElementById('file-name');

        // Validación de archivo
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);

                    if (file.size > 5 * 1024 * 1024) {
                        // Usar el sistema de alertas modales existente
                        if (typeof mostrarAlerta === 'function') {
                            mostrarAlerta('El archivo es muy grande. El tamaño máximo es 5MB.', 'error', '.soporte-container');
                        } else {
                            alert('❌ El archivo es muy grande. El tamaño máximo es 5MB.');
                        }
                        this.value = '';
                        fileName.textContent = 'Seleccionar archivo';
                        return;
                    }

                    fileName.textContent = `${file.name} (${sizeMB}MB)`;
                } else {
                    fileName.textContent = 'Seleccionar archivo';
                }
            });
        }
    });
</script>