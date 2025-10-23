<?php
// No necesitas ignorar $_GET, ya que el controlador pasa las variables
$isOwnProfile = $isOwnProfile ?? false;
$profileUserId = $profileUserId ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/img/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/hover.css/2.3.1/css/hover-min.css" />
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://kit.fontawesome.com/12176a67eb.js" crossorigin="anonymous"></script>

    <title>Friding- Conoce gente real cerca de ti</title>
</head>

<body>
    <div id="popup-edit"></div>
    <?php $navbarMode = 'profile'; include 'navbar.php'; ?>
    
    <main  class="scroll mt-1-5r">
        <!-- Estructura unificada para ambos casos -->
        <div class="d-flex flex-center col w-100-h-100">
            <div id="img_profile" class="d-flex flex-center row">
            </div>
            
            <div class="d-flex row mx-2 w-60">
                <div class="d-flex col flex-left ">
                    <div class="d-flex flex-space">
                        <h3 class="tc-bold tc-secondary m-0 ">
                            Bio
                        </h3>
                        <?php if ($isOwnProfile): ?>
                            <button id="open-edit" class="btn-edit btn-secondary m-1 tc-white br-5 px-1 py-0-5">
                                <i class="fa-solid fa-pen-to-square"></i> Editar perfil
                            </button>
                        <?php endif; ?>
                    </div>

                    <p id="bio-text" class="tc-justify tc-secondary mr-1r ">
                        Cargando biografía...
                    </p>
                </div>
                <div class="extra-info">
                    <div class="d-flex col m-1 w-80">
                        <h3 class="tc-bold tc-secondary m-0">
                            Creo
                        </h3>
                        <div id="create-count" class="bg-secondary br-10 tc-center p-0-25-0-5r">
                            0 Salidas
                        </div>
                    </div>
                    <div class="d-flex col m-1 w-80">
                        <h3 class="tc-bold tc-secondary m-0">
                            Participo
                        </h3>
                        <div id="join-count" class="bg-secondary br-10 tc-center p-0-25-0-5r">
                            0 Salidas
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php $footer = 'chat'; $footer2 = 'chat'; include 'footer.php'; ?>
    
    <!-- Pasar variables a JS -->
    <script>
        const isOwnProfile = <?php echo json_encode($isOwnProfile); ?>;
        const profileUserId = <?php echo json_encode($profileUserId); ?>;
    </script>
</body>
<script type="module" src="/js/script.js"></script>
<script type="module" src="/js/profile_component.js"></script>
</html>