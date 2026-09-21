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
    </style>
</head>
<body>
    <?php echo $contenido; ?>
</body>
</html>