<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Cine</title>
    <link rel="stylesheet" href="/assets/css/estilos.css">
    <style>
        .carrito-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .carrito-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            color: white;
        }

        .carrito-vacio {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .carrito-vacio h2 {
            color: #666;
            margin-bottom: 1rem;
        }

        .btn-continuar {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 0.8rem 2rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-continuar:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .carrito-items {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .item-carrito {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }

        .item-carrito:hover {
            background-color: #f8f9fa;
        }

        .item-carrito:last-child {
            border-bottom: none;
        }

        .item-imagen {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }

        .item-info {
            flex: 1;
            margin-right: 1rem;
        }

        .item-titulo {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .item-detalles {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .item-precio {
            font-weight: 600;
            color: #28a745;
        }

        .item-controles {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cantidad-controles {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-cantidad-carrito {
            background: #007bff;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .btn-cantidad-carrito:hover {
            background: #0056b3;
        }

        .btn-cantidad-carrito:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .cantidad-input {
            width: 50px;
            text-align: center;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 0.3rem;
            font-weight: bold;
        }

        .btn-eliminar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .btn-eliminar:hover {
            background: #c82333;
        }

        .carrito-resumen {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        .resumen-titulo {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            color: #333;
            text-align: center;
        }

        .resumen-linea {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .resumen-linea:last-child {
            border-bottom: 2px solid #28a745;
            font-weight: bold;
            font-size: 1.1rem;
            color: #28a745;
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .carrito-acciones {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-vaciar {
            flex: 1;
            background: #6c757d;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-vaciar:hover {
            background: #5a6268;
        }

        .btn-proceder {
            flex: 2;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-proceder:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        @media (max-width: 768px) {
            .item-carrito {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .item-imagen {
                margin-right: 0;
            }

            .carrito-acciones {
                flex-direction: column;
            }

            .item-controles {
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        .loading {
            display: none;
            text-align: center;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="carrito-container">
        <div class="carrito-header">
            <h1>🛒 Mi Carrito de Entradas</h1>
            <p>Revisa tu selección antes de proceder al pago</p>
        </div>

        <?php if (empty($items)): ?>
            <div class="carrito-vacio">
                <h2>Tu carrito está vacío</h2>
                <p>¡Agrega algunas entradas para continuar!</p>
                <a href="/menu" class="btn-continuar">Ver Funciones Disponibles</a>
            </div>
        <?php else: ?>
            <div class="carrito-items">
                <?php foreach ($items as $item): ?>
                    <div class="item-carrito" data-funcion-id="<?php echo $item['funcion']['id_funcion']; ?>">
                        <img src="<?php echo $item['funcion']['imagen_pelicula'] ?: '/assets/img/default-movie.jpg'; ?>" 
                             alt="<?php echo $item['funcion']['titulo_pelicula']; ?>" 
                             class="item-imagen">
                        
                        <div class="item-info">
                            <div class="item-titulo"><?php echo $item['funcion']['titulo_pelicula']; ?></div>
                            <div class="item-detalles">
                                📅 <?php echo date('d/m/Y', strtotime($item['funcion']['fecha_hora'])); ?> • 
                                🕐 <?php echo $item['funcion']['turno_horario']; ?> • 
                                🎭 <?php echo $item['funcion']['nombre_sala'] ?: 'Sala ' . $item['funcion']['rela_salas']; ?>
                            </div>
                            <div class="item-precio">$<?php echo number_format($item['precio_unitario']); ?> c/u</div>
                        </div>

                        <div class="item-controles">
                            <div class="cantidad-controles">
                                <button type="button" class="btn-cantidad-carrito btn-menos" data-action="minus">−</button>
                                <input type="number" class="cantidad-input" value="<?php echo $item['cantidad']; ?>" 
                                       min="1" max="10" readonly>
                                <button type="button" class="btn-cantidad-carrito btn-mas" data-action="plus">+</button>
                            </div>
                            
                            <div class="subtotal-display">
                                <strong>$<?php echo number_format($item['subtotal']); ?></strong>
                            </div>
                            
                            <button type="button" class="btn-eliminar" onclick="eliminarItem(<?php echo $item['funcion']['id_funcion']; ?>)">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="carrito-resumen">
                <div class="resumen-titulo">Resumen de Compra</div>
                
                <div class="resumen-linea">
                    <span>Cantidad de entradas:</span>
                    <span id="total-entradas"><?php echo $cantidad_total; ?></span>
                </div>
                
                <div class="resumen-linea">
                    <span>Subtotal:</span>
                    <span id="subtotal-precio">$<?php echo number_format($total); ?></span>
                </div>
                
                <div class="resumen-linea">
                    <span>Total a pagar:</span>
                    <span id="total-precio">$<?php echo number_format($total); ?></span>
                </div>

                <div class="carrito-acciones">
                    <button type="button" class="btn-vaciar" onclick="vaciarCarrito()">
                        🗑️ Vaciar Carrito
                    </button>
                    <button type="button" class="btn-proceder" onclick="procederPago()">
                        💳 Proceder al Pago
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="loading" id="loading">Procesando...</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manejar controles de cantidad
            document.querySelectorAll('.item-carrito').forEach(item => {
                const btnMenos = item.querySelector('.btn-menos');
                const btnMas = item.querySelector('.btn-mas');
                const cantidadInput = item.querySelector('.cantidad-input');
                const funcionId = item.dataset.funcionId;
                
                btnMenos.addEventListener('click', function() {
                    let cantidad = parseInt(cantidadInput.value);
                    if (cantidad > 1) {
                        actualizarCantidad(funcionId, cantidad - 1);
                    }
                });

                btnMas.addEventListener('click', function() {
                    let cantidad = parseInt(cantidadInput.value);
                    if (cantidad < 10) {
                        actualizarCantidad(funcionId, cantidad + 1);
                    }
                });
            });
        });

        function actualizarCantidad(idFuncion, nuevaCantidad) {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';

            fetch('/carrito/actualizar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_funcion: idFuncion,
                    cantidad: nuevaCantidad
                })
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.ok) {
                    location.reload(); // Recargar para actualizar precios
                } else {
                    alert('Error: ' + data.mensaje);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }

        function eliminarItem(idFuncion) {
            if (!confirm('¿Estás seguro de eliminar este item del carrito?')) {
                return;
            }

            const loading = document.getElementById('loading');
            loading.style.display = 'block';

            fetch('/carrito/eliminar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_funcion: idFuncion
                })
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.ok) {
                    location.reload();
                } else {
                    alert('Error: ' + data.mensaje);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }

        function vaciarCarrito() {
            if (!confirm('¿Estás seguro de vaciar todo el carrito?')) {
                return;
            }

            const loading = document.getElementById('loading');
            loading.style.display = 'block';

            fetch('/carrito/vaciar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.ok) {
                    location.reload();
                } else {
                    alert('Error: ' + data.mensaje);
                }
            });
        }

        function procederPago() {
            // Aquí implementarás la lógica de pago
            alert('Función de pago en desarrollo...');
            
            // TODO: Redirigir a página de pago
            // window.location.href = '/checkout';
        }
    </script>
</body>
</html>