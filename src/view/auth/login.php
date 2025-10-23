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

<body class="bg-secondary img-aux-1">
    <div class="d-flex flex-center blur">
        <div class="form-1">
            <img src="/img/logo.svg" alt="Friding" class="w-60 my-10">
            
            <?php if (isset($error)): ?>
                <p class="tc-red tc-bold"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form action="/login" method="POST" class="d-flex col">
                <input type="email" name="email" placeholder="Correo" required
                    class="my-10 p-12 borderless br-5">
                <input type="password" name="password" placeholder="Contraseña" required
                    class="my-10 p-12 borderless br-5">
                <button type="submit"
                    class="tc-bold btn-secondary tc-white my-10 p-12 borderless br-5">Entrar</button>
            </form>


             <p class="my-10 tc-white t-s">¿Has olvidado la contraseña? <a href="/forgot-password" class="tc-bold tc-secondary tc-secondaryh td-none">Recuperar contraseña</a></p>
            <p class="my-10 tc-white t-s">¿No tienes una cuenta? <a href="/register" class="tc-bold tc-secondary tc-secondaryh td-none">Regístrate</a></p>
        </div>
        <div class="img-2">
        </div>
    </div>
</body>

</html>