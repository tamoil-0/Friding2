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

    <script>
    window.INITIAL_OUTING_DATA = <?php echo $outingDataJson; ?>;
    window.CURRENT_USER_ID = <?php echo json_encode($userId); ?>;
</script>
</head>

<body>
    <?php $navbarMode = 'dashboard';
    include 'navbar.php'; ?>
    <div id="report-box"></div>
    <main class="scroll">
        <div class="d-flex flex-center col m-0-5">
            <div class="d-flex row p-1r m-0-5 w-100">
                <div id="participants-list" class="d-flex participant-section m-1 ">
                </div>
                <div class="divider">
                </div>
                <div class="d-flex col flex-1 w-100">
                    <div id="resume-outgoing" class="d-flex flex-center col m-1 bg-tertiary">
                    </div>
                    <button id="btn-scroll-down" class="btn-scroll d-none">
                        <i class="fa-solid fa-down-long"></i>
                    </button>
                    <div id="chat-messages" class="chat-messages m-1 d-flex flex-center col m-1">
                    </div>
                    <div class="d-flex col bg-tertiary br-10">
                        
                        <div id="reply-box" class="reply-box">
                        </div>
                        <div class="input-container">
                            <input id="input-chat" class="input-chat" type="text" placeholder="Escribe Algo...">
                            <button id="btn-send" class="btn-send">
                                Enviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </main>
    <?php $footer = 'chat';
    $footer2 = 'chat';
    include 'footer.php'; ?>
</body>
<script type="module" src="/js/notification.js"></script>
<script type="module" src="/js/script.js"></script>
<script type="module" src="/js/chat_component.js"></script>

</html>