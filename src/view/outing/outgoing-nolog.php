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
    <link rel="stylesheet" href="css/style.css">
    <script src="https://kit.fontawesome.com/12176a67eb.js" crossorigin="anonymous"></script>

    <title>Friding- Conoce gente real cerca de ti</title>
</head>

<body>
    <?php include 'search.php'; ?>
    <div id="popups">
        <!-- popups -->
    </div>
    <nav class="d-flex flex-space bg-secondary p-1r d-sticky z-i-10">
        <div>
            <a href="/">
                <img class="logo" src="/img/logo.svg" alt="">
            </a>
        </div>
    </nav>
    <main class="scroll">

        <div id="event-nolog" class="card-container">
            <!-- Events -->
        </div>
        
    </main>
    <footer class="foot bg-secondary">
    <div class="d-flex flex-space-a">
        <div class="bg-secondaryh flex-1 tc-center">
            <a href="/outgoing-nolog" class="d-flex col flex-center tc-white texto td-none p-10">
                <i class="fa-solid fa-map-location-dot"></i>Salidas</a>
        </div>
        <div class="d-flex flex-center bg-secondaryh flex-1">
            <button id="open-popup2" type="button" class="d-flex flex-center flex-1 col btn-none borderless tc-white texto p-10">
                <i class="fa-solid fa-magnifying-glass"></i>Buscar</button>
            </button>
        </div>
    </div>
</footer>
</body>
<script type="module" src="/js/script.js"></script>
<script type="module" src="/js/events_component-nolog.js"></script>
</html>