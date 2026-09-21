<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CINFSA</title>
    
    
    <!-- Fuente Google-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat|Montserrat+Alternates|Poppins&display=swap" rel="stylesheet">

    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script>window.CSRF_TOKEN = "<?php echo csrf_token(); ?>";</script>
    <script src="/assets/js/csrf.js"></script>

    <!-- Estilo para fuente -->
    <style>
        * {
            font-family: 'Montserrat', 'Poppins', 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            font-size: 15px !important;
        }
    </style>

    <!-- mis estilos -->
    <?php if (empty($sinBase)): ?>
        <link rel="stylesheet" href="/assets/css/base.css">
    <?php endif; ?>

    <?php if (isset($vista)): ?>
        <link rel="stylesheet" href="/assets/css/<?php echo $vista; ?>.css">
    <?php endif; ?>
    
    <link rel="stylesheet" href="/assets/css/alertas.css">
</head>
<body>
   
    <?php echo $contenido; ?>
    
</body>
</html>
