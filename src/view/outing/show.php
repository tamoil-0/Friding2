<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de la Outing</title>
</head>
<body>

    <h1>Detalles de la Salida</h1>

    <p><strong>ID:</strong> <?= $outing->getId(); ?></p>
    <p><strong>Título:</strong> <?= $outing->getTitle(); ?></p>
    <p><strong>Fecha del evento:</strong> <?= $outing->getEventDate(); ?></p>
    <p><strong>Hora del evento:</strong> <?= $outing->getEventTime(); ?></p>
    <p><strong>Creado por:</strong> <?= $outing->getCreatedBy(); ?></p>
    <p><strong>Estado:</strong> <?= $outing->getStatus(); ?></p>
    <p><strong>Descripción:</strong> <?= $outing->getDescription() ? $outing->getDescription() : 'No disponible'; ?></p>

</body>
</html>