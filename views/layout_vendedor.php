<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ventas - CINFSA</title>
    
    <!-- Iconos Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Fuente Google-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat|Montserrat+Alternates|Poppins&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/assets/css/alertas.css">
    <script>window.CSRF_TOKEN = "<?php echo csrf_token(); ?>";</script>
    <script src="/assets/js/csrf.js"></script>
    <script src="/assets/js/cerrar-sesion.js" defer></script>
    <script type="module" src="/assets/js/formularios.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', 'Poppins', sans-serif;
        }
        
        body {
            min-height: 100vh;
        }

        /* Cerrar sesión en el panel de la caja abierta (franja del usuario, sobre el naranja) */
        .btn-salir-caja {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .btn-salir-caja:hover {
            background: #dc2626;
            border-color: #dc2626;
        }
    </style>
</head>
<body>
    <?php echo $contenido; ?>
</body>
</html>