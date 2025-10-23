<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords"
        content="conocer gente cerca, hacer amigos en mi ciudad, planes para hoy, salidas grupales, reuniones sociales, app para conocer gente real">
    <meta name="robots" content="index, follow">
    <meta name="description"
        content="Friding es la app para hacer salidas grupales con personas cerca de ti. Sin citas ni chats eternos, solo salir de verdad. Únete hoy.">

    <link rel="icon" href="/img/logo.ico" type="image/x-icon">

    <link rel="stylesheet" href="/css/style.css">
    <script src="https://kit.fontawesome.com/d2049865bd.js" crossorigin="anonymous"></script>

    <title>Friding - Conoce gente real cerca de ti</title>
</head>

<body class="bg-secondary img-aux-2">
    <div class="d-flex flex-center blur">
        <div class="img-3">
        </div>
        <div class="form-1">
            <img src="/img/logo.svg" alt="Friding" class="w-60 my-10">

            <?php if (isset($error)): ?>
                <p class="tc-red tc-bold"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form action="/forgot-password" method="POST" class="d-flex col">
                <input type="email" name="email" placeholder="Tu correo" required
                    class="my-10 p-12 borderless br-5">
                <button type="submit"
                    class="tc-bold btn-secondary tc-white my-10 p-12 borderless br-5">
                    Enviar enlace
                </button>
            </form>



            <p class="my-10 tc-white t-s">¿Tienes una cuenta?<a href="/login" class="tc-bold tc-secondary tc-secondaryh td-none"> Inicia Sesión Aquí</a></p>
        </div>

    </div>
</body>

</html>