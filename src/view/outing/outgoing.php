<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- SEO: Palabras clave -->
    <meta name="keywords"
        content="conocer gente cerca, hacer amigos en mi ciudad, planes para hoy, salidas grupales, reuniones sociales, app para conocer gente real">
    <!-- SEO: Permitir indexación -->
    <meta name="robots" content="index, follow">
    <!-- SEO: Descripción -->
    <meta name="description"
        content="Friding es la app para hacer salidas grupales con personas cerca de ti. Sin citas ni chats eternos, solo salir de verdad. Únete hoy.">

    <link rel="icon" href="img/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/hover.css/2.3.1/css/hover-min.css" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <script src="https://kit.fontawesome.com/12176a67eb.js" crossorigin="anonymous"></script>


    <title>Friding- Conoce gente real cerca de ti</title>
</head>

<body>
    <?php include 'create.php'; ?>
    <?php include 'search.php'; ?>
    <?php $navbarMode = 'dashboard';
    include 'navbar.php'; ?>
    <div id="popups">
        <!-- popups -->
    </div>
    <main class="scroll">

        <div id="event" class="card-container">
            <!-- Events -->
        </div>

    </main>
    <?php $footer = 'dashboard';
    $footer2 = 'outgoing';
    include 'footer.php'; ?>
</body>
<script type="module" src="/js/events_component.js"></script>
<script type="module" src="/js/script.js"></script>

</html>