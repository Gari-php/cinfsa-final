<?php
function formatearDescripcionNotif($texto)
{
    $escapado = htmlspecialchars($texto);
    // Convierte rutas de adjuntos (/uploads/...) en un link clickeable
    return preg_replace_callback(
        '/\/uploads\/\S+/',
        function ($match) {
            $ruta = $match[0];
            return '<a href="' . $ruta . '" target="_blank" rel="noopener" class="notificacion-adjunto" onclick="event.stopPropagation()">📎 Ver adjunto</a>';
        },
        $escapado
    );
}
?>

<body>
    <div class="notificaciones-container">
        <div class="notificaciones-header">

            <h1>Mis Notificaciones</h1>

        </div>

        <div class="notificaciones-lista" id="lista-notificaciones">
            <?php if (empty($notificaciones)): ?>
                <div class="notificaciones-vacia">
                    <i class="fa-solid fa-bell-slash"></i>
                    <h3>No tienes notificaciones</h3>
                    <p>Cuando tengas nuevas notificaciones aparecerán aquí</p>
                </div>
            <?php else: ?>
                <?php foreach ($notificaciones as $notif): ?>
                    <div class="notificacion-item <?= $notif['leido'] ? 'leida' : 'no-leida' ?>"
                        data-id="<?= $notif['id_notificacion'] ?>"
                        data-tipo="<?= $notif['tipo'] ?>">

                        <div class="notificacion-icono">
                            <?php
                            $iconos = [
                                'registro_usuario' => 'fa-user-plus',
                                'stock_bajo' => 'fa-exclamation-triangle',
                                'nueva_venta' => 'fa-shopping-cart',
                                'soporte' => 'fa-headset',
                                'contacto' => 'fa-envelope',
                                'sistema' => 'fa-info-circle'
                            ];
                            $icono = $iconos[$notif['tipo']] ?? 'fa-bell';
                            ?>
                            <i class="fa-solid <?= $icono ?>"></i>
                        </div>

                        <div class="notificacion-contenido">
                            <div class="notificacion-titulo">
                                <?= htmlspecialchars($notif['titulo']) ?>
                                <?php if (!$notif['leido']): ?>
                                    <span class="badge-nueva">Nueva</span>
                                <?php endif; ?>
                            </div>

                            <div class="notificacion-descripcion">
                                <?= formatearDescripcionNotif($notif['descripcion']) ?>
                            </div>

                            <div class="notificacion-meta">
                                <span class="notificacion-fecha">
                                    <i class="fa-solid fa-clock"></i>
                                    <?= date('d/m/Y H:i', strtotime($notif['fecha_creacion'])) ?>
                                </span>

                                <span class="notificacion-tipo tipo-<?= $notif['tipo'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $notif['tipo'])) ?>
                                </span>
                            </div>
                        </div>

                        <div class="notificacion-acciones">
                            <?php if (!$notif['leido']): ?>
                                <button class="btn-marcar-leida"
                                    data-id="<?= $notif['id_notificacion'] ?>"
                                    title="Marcar como leída">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Área de alertas -->
    <div id="alerta-notificaciones" class="form-container"></div>

    <style>
        .notificaciones-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .notificaciones-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .notificaciones-header h1 {
            color: #ed850f;
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .notificaciones-lista {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .notificacion-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            background: rgba(45, 55, 72, 0.9);
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #bdc3c7;
        }

        .notificacion-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .notificacion-item.no-leida {
            border-left-color: #ed850f;
        }

        .notificacion-item.leida {
            border-left-color: #27ae60;
            opacity: 0.8;
        }

        .notificacion-icono {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ed850f;
            color: #ffffff;
            font-size: 18px;
            flex-shrink: 0;
        }

        .notificacion-contenido {
            flex: 1;
        }

        .notificacion-titulo {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
        }

        .badge-nueva {
            background: #e74c3c;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: normal;
        }

        .notificacion-descripcion {
            color: #ffffff;
            margin-bottom: 10px;
            line-height: 1.4;
            opacity: 0.9;
        }

        .notificacion-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #ffffff;
            opacity: 0.8;
        }

        .notificacion-fecha {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notificacion-tipo {
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .tipo-registro_usuario {
            background: #3498db;
            color: #ffffff;
        }

        .tipo-stock_bajo {
            background: #f39c12;
            color: #ffffff;
        }

        .tipo-nueva_venta {
            background: #27ae60;
            color: #ffffff;
        }

        .tipo-sistema {
            background: #ed850f;
            color: #ffffff;
        }

        .notificacion-adjunto {
            display: inline-block;
            margin-top: 6px;
            color: #ed850f;
            font-weight: 600;
            text-decoration: underline;
            font-size: 13px;
        }

        .notificacion-adjunto:hover {
            color: #ffa733;
        }

        .tipo-soporte {
            background: #e74c3c;
            color: #ffffff;
        }

        .tipo-contacto {
            background: #9b59b6;
            color: #ffffff;
        }

        .notificacion-acciones {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .btn-marcar-leida {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            background: #27ae60;
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-marcar-leida:hover {
            background: #ed850f;
            transform: scale(1.1);
        }

        .notificaciones-vacia {
            text-align: center;
            padding: 60px 20px;
            background: rgba(45, 55, 72, 0.9);
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .notificaciones-vacia i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ffffff;
            opacity: 0.6;
        }

        .notificaciones-vacia h3 {
            margin-bottom: 10px;
            color: #ffffff;
            font-weight: 500;
        }

        .notificaciones-vacia p {
            color: #ffffff;
            margin: 0;
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .notificaciones-container {
                padding: 15px;
            }

            .notificacion-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .notificacion-item {
                padding: 15px;
            }

            .notificaciones-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>

</body>