<?php

namespace App\Controller;

use App\DAO\OutingDAO;
use App\Model\Outing;

class OutingController
{
    private OutingDAO $OutingDAO;

    public function __construct()
    {
        $this->OutingDAO = new OutingDAO();
    }

    public function showOutgoingList()
    {

        $viewPath = __DIR__ . '/../view/outing/outgoing.php';

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "Error: La vista 'outgoing.php' no fue encontrada en la ruta esperada.";
        }
    }


    public function showOutgoingListnolog()
    {

        $viewPath = __DIR__ . '/../view/outing/outgoing-nolog.php';

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "Error: La vista 'outgoing.php' no fue encontrada en la ruta esperada.";
        }
    }

    //Listar salidas

    // En src/Controllers/OutingController.php (o similar)

    public function listOutings()
    {
        // 1. 🆕 Obtener el término de búsqueda de la URL ($_GET)
        //    Si el parámetro 'search' existe, lo toma. Si no, $searchTerm será null.
        $searchTerm = $_GET['search'] ?? null;

        // 2. 🆕 Pasar el término de búsqueda al método getAll del DAO
        //    El DAO ahora es responsable de filtrar los resultados.
        $outingsData = $this->OutingDAO->getAll($searchTerm);

        $outings = [];

        foreach ($outingsData as $data) {
            // ... (El mapeo de datos dentro del loop permanece igual) ...
            $outings[] = [
                'id'              => $data['id'],
                'title'           => $data['title'],
                'description'     => $data['description'],
                'meetingpoint'    => $data['meeting_point'],
                'date'            => $data['event_date'],
                'time'            => $data['event_time'],
                'estimatedamount' => $data['estimated_amount'],
                'status'          => $data['status'],
                'userId'          => $data['user_id'],
                'created_by'      => $this->OutingDAO->getCreatedBy($data['id']),
                'participants'    => $this->OutingDAO->getParticipants($data['id'])
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($outings);
    }


    // Unirse a una salida
    public function joinOuting(int $outingId)
    {
        // Tu lógica de sesión original (aunque es mejor manejarla globalmente)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            return;
        }

        // 2. Intentar unir al usuario
        $result = $this->OutingDAO->join($outingId, $userId);

        if ($result) {

            // 3. Obtener el ID del creador de la salida para enviarle la notificación
            // 🚨 NECESITAS UN MÉTODO EN OUTINGDAO QUE TE DEVUELVA EL ID DEL CREADOR
            $creatorId = $this->OutingDAO->getCreatorId($outingId);

            // 4. Enviar la notificación al creador de la salida
            if ($creatorId && $creatorId !== $userId) { // Aseguramos no notificarse a sí mismo si fuera el caso

                // NOTIFICACIÓN 1: Al creador de la salida
                $this->OutingDAO->createNotification(
                    $creatorId,
                    "Desea unirse a su salida", // El texto que mencionaste
                    "fa-solid fa-hands-clapping",
                    "/outgoing" // Usamos tu ruta /outgoing para ver las salidas propias
                );
            }

            // NOTIFICACIÓN 2 (OPCIONAL, según tu lógica): Al usuario que se unió
            // Aunque esto es redundante, puedes hacerlo si quieres una notificación interna de confirmación
            /*
            $this->notificationController->create(
                $userId, 
                "Te has unido a la salida", 
                "fa-solid fa-face-laugh-wink", 
                "/outgoing"
            );
            */

            echo json_encode(['success' => true, 'message' => '¡Te has unido a la salida con éxito!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ya estabas unido a esta salida.']);
        }
    }

    // Si el usuario ya se unio
    public function isJoined(int $outingId)
    {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $isJoined = false;
        } else {
            $isJoined = $this->OutingDAO->isUserJoined($outingId, $userId);
        }
        header('Content-Type: application/json');
        echo json_encode(['isJoined' => $isJoined]);
    }

    // Mis salidas unidas
    public function getMyJoinedOutings()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado.']);
            return;
        }

        // Llama al método DAO mejorado
        $outings = $this->OutingDAO->getOutingsUserIsJoined($userId);

        echo json_encode($outings);
    }

    //Mis salidas creadas

    public function getMyCreatedOutings()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no autenticado.']);
            return;
        }

        // 1. Llama al método DAO
        $outings = $this->OutingDAO->getOutingsCreatedByUser($userId);

        // =======================================================
        // 2. TRATAMIENTO/FILTRADO DE DATOS EN EL CONTROLADOR (Aquí está la magia)
        // =======================================================

        // Filtro: Solo incluir las salidas donde el creador está en la lista de participantes.
        // Aunque el DAO debería hacerlo, esto actúa como una doble verificación.
        $filteredOutings = [];

        foreach ($outings as $outing) {
            $creatorId = $outing['created_by']['id'];
            $isCreatorParticipant = false;

            // Verifica si el ID del creador se encuentra en la lista de participantes
            foreach ($outing['participants'] as $participant) {
                if ($participant['id'] === $creatorId) {
                    $isCreatorParticipant = true;
                    break; // Lo encontramos, no necesitamos seguir buscando
                }
            }

            // Solo añade la salida a la lista final si el creador es un participante.
            if ($isCreatorParticipant) {
                $filteredOutings[] = $outing;
            }
        }

        // =======================================================
        // 3. Envía los datos filtrados
        // =======================================================

        echo json_encode($filteredOutings);
    }


    // Ver vista mis salidas
    public function showMyOutgoingList()
    {

        $viewPath = __DIR__ . '/../view/outing/myoutgoing.php';

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "Error: La vista 'outgoing.php' no fue encontrada en la ruta esperada.";
        }
    }





    public function leaveOuting(int $outingId)
    {
        // 🔹 Nota: Si tu router o middleware ($isAuthenticated) ya inicia la sesión,
        // puedes omitir este bloque. Se mantiene por seguridad si no lo hace.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            // Respuesta 401: No autenticado
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado. Inicia sesión para retirarte.']);
            return;
        }

        // Llamar al método DAO para eliminar el registro de participación
        $result = $this->OutingDAO->leave($outingId, $userId);

        if ($result) {
            // Éxito: se eliminó el registro (respuesta 200 OK por defecto)
            echo json_encode(['success' => true, 'message' => 'Te has retirado de la salida con éxito.']);
        } else {
            // Se usa 404 porque el DAO devolvió false (no se encontró el registro para eliminar)
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No se pudo retirar. Quizás ya habías salido o la salida no existe.']);
        }
    }

    public function deleteOuting(int $outingId)
    {
        // 1. Iniciar sesión y obtener ID del usuario autenticado
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            return;
        }

        // 2. Verificar si el usuario es el creador de la salida
        if (!$this->OutingDAO->isCreator($outingId, $userId)) {
            http_response_code(403); // Prohibido
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar esta salida.']);
            return;
        }

        // 3. Llamar al DAO para eliminar la salida (y sus participantes en cascada)
        $result = $this->OutingDAO->delete($outingId);

        if ($result) {
            // Éxito: se eliminó el registro (respuesta 200 OK por defecto)
            echo json_encode(['success' => true, 'message' => 'Salida eliminada con éxito.']);
        } else {
            // Falla: (ej. la salida no existía o error de DB)
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No se pudo encontrar o eliminar la salida.']);
        }
    }

    public function createOuting()
    {
        // 1. Verificar autenticación
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            return;
        }

        // 2. Obtener y validar datos
        $data = $_POST;

        // Validaciones básicas
        if (empty($data['title']) || empty($data['event_date']) || empty($data['event_time'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan campos obligatorios (título, fecha, hora).',
                'received' => $data // Para depuración
            ]);
            return;
        }

        // 3. Crear la salida y registrar al participante
        $newOutingId = $this->OutingDAO->create($data, $userId);

        if ($newOutingId) {
            // Crear el canal de chat
            $this->OutingDAO->createChatChannel($newOutingId);

            // Éxito: Respuesta 201 Created
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Salida creada con éxito.',
                'outing_id' => $newOutingId
            ]);
        } else {
            // Falla en la inserción
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error interno al guardar la salida.',
                'received' => $data // Para depuración
            ]);
        }
    }
    public function showMyOwnProfileView()
    {
        // 🎯 CRÍTICO: Definir esta variable como 'true' para que profile.php cargue el modo de edición.
        $isOwnProfile = true;

        // Opcional: Definimos $userData para evitar un aviso si profile.php lo intenta usar.
        $userData = [];

        $viewPath = __DIR__ . '/../view/outing/profile.php';

        if (file_exists($viewPath)) {
            // Hacemos que $isOwnProfile y $userData estén disponibles para la vista.
            extract(['isOwnProfile' => $isOwnProfile, 'userData' => $userData]);

            include $viewPath;
        } else {
            echo "Error: La vista 'profile.php' no fue encontrada en la ruta esperada.";
        }
    }



    public function getMyProfile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            return;
        }

        // 1. Obtener datos básicos del usuario
        $user = $this->OutingDAO->getById($userId);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Perfil de usuario no encontrado.']);
            return;
        }

        // 2. Obtener recuento de actividades
        $createdCount = $this->OutingDAO->countCreatedOutings($userId);
        $joinedCount = $this->OutingDAO->countJoinedOutings($userId);

        // 3. Preparar la respuesta final
        $response = [
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'name' => $user['full_name'], // Mapeo a 'name' para tu JS
                'bio' => $user['bio'],
                'foto' => $user['photo_url'], // Mapeo a 'foto' para tu JS
                'create' => $createdCount,    // Salidas creadas
                'join' => $joinedCount        // Salidas participadas
            ]
        ];

        echo json_encode($response);
    }

    private function handlePhotoUpload(array $fileData, int $userId): ?string
    {
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // 1. Definir Rutas
        // 🎯 CORRECCIÓN: Usamos '2' para subir de src/Controller/ a [RaízProyecto]/
        // __DIR__ = [RaízProyecto]/src/Controller/
        // dirname(__DIR__, 2) = [RaízProyecto]/
        $baseDir = dirname(__DIR__, 2);

        // Ruta ABSOLUTA en el servidor: [RaízProyecto]/public/perfil/
        $targetDir = $baseDir . "/public/perfil/";

        // 2. Definir Nombre de Archivo
        $extension = strtolower(pathinfo($fileData["name"], PATHINFO_EXTENSION));
        $fileName = $userId . "." . $extension;
        $targetFile = $targetDir . $fileName;

        // 3. Definir la URL Pública (para guardar en la DB)
        $publicUrl = "/perfil/{$fileName}"; // Lo que ve el navegador

        // 4. Crear directorio si no existe
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                // Esto puede seguir fallando si no hay permisos globales para el usuario del servidor
                error_log("Error: No se pudo crear el directorio de subida: {$targetDir}");
                return null;
            }
        }

        // 5. Mover el archivo subido
        if (move_uploaded_file($fileData["tmp_name"], $targetFile)) {
            return $publicUrl;
        }

        // Si llega aquí, significa que la ruta de origen o destino fue incorrecta,
        // o el servidor no tiene permisos para mover/escribir.
        error_log("Fallo al mover archivo subido. Intentó mover desde: {$fileData['tmp_name']} hacia: {$targetFile}");
        return null;
    }
    /**
     * API para actualizar el perfil del usuario autenticado. (Ruta: POST /profile/update)
     */
    public function updateMyProfile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            return;
        }

        // 1. Obtener datos (vienen de $_POST porque el JS usa FormData)
        $fullName = $_POST['full_name'] ?? null;
        $bio = $_POST['bio'] ?? null;

        if (empty($fullName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El nombre completo es obligatorio.']);
            return;
        }

        // 2. Manejar la FOTO
        $newPhotoUrl = null;
        // Obtenemos la URL de la foto actual para conservarla si no hay nueva subida
        $currentPhoto = $this->OutingDAO->getById($userId)['photo_url'] ?? null;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $newPhotoUrl = $this->handlePhotoUpload($_FILES['photo'], $userId);
        }

        // La URL final es la nueva o la actual
        $finalPhotoUrl = $newPhotoUrl ?? $currentPhoto;


        // 3. Llamar al DAO para actualizar
        $updated = $this->OutingDAO->updateProfile(
            $userId,
            $fullName,
            $bio,
            $finalPhotoUrl
        );

        // 4. Responder al frontend
        if ($updated) {
            echo json_encode([
                'success' => true,
                'message' => 'Perfil actualizado con éxito.',
                'new_photo_url' => $finalPhotoUrl
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Fallo al actualizar el perfil en la base de datos.']);
        }
    }

    public function getUserProfile($userId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentUserId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$currentUserId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            return;
        }

        // Verificar si el usuario existe
        $user = $this->OutingDAO->getById($userId);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Perfil de usuario no encontrado.']);
            return;
        }

        // Obtener recuentos de actividades (asumiendo que los métodos DAO son generales y funcionan para cualquier userId)
        $createdCount = $this->OutingDAO->countCreatedOutings($userId);
        $joinedCount = $this->OutingDAO->countJoinedOutings($userId);

        // Preparar la respuesta final (mismos datos que para el perfil propio)
        $response = [
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'bio' => $user['bio'],
                'foto' => $user['photo_url'],
                'create' => $createdCount,
                'join' => $joinedCount
            ]
        ];

        echo json_encode($response);
    }

public function showProfileView($userId = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $currentUserId = $_SESSION['user_id'] ?? null;

    if (!$currentUserId) {
        header('Location: /login');
        return;
    }

    if ($userId === null) {
        $userId = $currentUserId;
    }

    $isOwnProfile = ($userId == $currentUserId);
    $profileUserId = $userId;

    // Ruta absoluta al archivo de la vista
    $viewPath = __DIR__ . '/../view/outing/profile.php';

    if (!file_exists($viewPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Archivo de vista no encontrado: ' . $viewPath]);
        return;
    }

    // Hacer que las variables estén disponibles para la vista
    extract(['isOwnProfile' => $isOwnProfile, 'profileUserId' => $profileUserId]);

    require $viewPath;
}


    /**
     * Función interna para obtener el ID del usuario autenticado.
     * DEBES usar tu lógica real de sesión aquí.
     */
    private function getCurrentUserId(): ?int
    {
        // Esto es correcto si usas la superglobal $_SESSION para el ID:
        return $_SESSION['user_id'] ?? null;
    }

    // ----------------------------------------------------
    // 1. Ruta GET /notifications/unread
    // ----------------------------------------------------
    public function listUnread()
    {
        $userId = $this->getCurrentUserId();

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autenticado.']);
            return;
        }

        // 🎯 Usando $this->OutingDAO como se solicitó 
        $notifications = $this->OutingDAO->getUnreadByUserId($userId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $notifications
        ]);
    }

    // ----------------------------------------------------
    // 2. Ruta POST /notifications/read-all
    // ----------------------------------------------------
    public function markAllAsRead()
    {
        $userId = $this->getCurrentUserId();

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autenticado.']);
            return;
        }

        // 🎯 Usando $this->OutingDAO como se solicitó
        $success = $this->OutingDAO->markAsRead($userId);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }


    // Unirse a una salida
    public function requestJoinOuting(int $outingId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            return;
        }

        // Intentar registrar la solicitud
        $result = $this->OutingDAO->requestJoin($outingId, $userId);

        if ($result) {
            // Obtener el creador de la salida
            $creatorId = $this->OutingDAO->getCreatorId($outingId);

            if ($creatorId && $creatorId !== $userId) {
                // Notificar al creador de la salida
                $this->OutingDAO->createNotification(
                    $creatorId,
                    "El usuario quiere unirse a tu salida",
                    "fa-solid fa-hands-clapping",
                    "/outgoing"
                );
            }

            echo json_encode(['success' => true, 'message' => 'Solicitud enviada con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ya enviaste una solicitud para esta salida.']);
        }
    }

    // Verificar estado de solicitud para el usuario logueado
    public function getRequestStatusOuting(int $outingId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null; // Ya tienes el ID

        header('Content-Type: application/json');

        if (!$userId) {
            echo json_encode(['status' => null, 'message' => 'Usuario no autenticado']);
            return;
        }

        // Consultar la tabla outing_requests usando el user_id de sesión
        $status = $this->OutingDAO->getRequestStatus($outingId, $userId);

        echo json_encode(['status' => $status]); // null, pendiente, aceptado, rechazado
    }

    public function isCreatorRoute(int $outingId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            return;
        }

        $isCreator = $this->OutingDAO->isCreator($outingId, $userId);
        echo json_encode(['isCreator' => $isCreator]);
    }


    // Obtener solicitudes de un evento
    public function getEventRequests(int $outingId)
    {
        $requests = $this->OutingDAO->getRequestsForOuting($outingId);
        // Devuelve array de solicitudes pendientes con info de usuario
        echo json_encode($requests);
    }

    // Aceptar solicitud
    public function acceptRequest(int $requestId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ]);
            exit;
        }

        // Aquí pasamos el userId a tu DAO si es necesario
        $success = $this->OutingDAO->acceptRequest($requestId, $userId);

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Solicitud aceptada' : 'Error al aceptar solicitud'
        ]);
        exit;
    }

    // Rechazar solicitud
    public function rejectRequest(int $requestId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ]);
            exit;
        }

        // Pasamos el userId a tu DAO si es necesario
        $success = $this->OutingDAO->rejectRequest($requestId, $userId);

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Solicitud rechazada' : 'Error al rechazar solicitud'
        ]);
        exit;
    }


    // Método para obtener el historial de mensajes de una salida (GET /outings/{id}/messages)
    public function getMessages(int $outingId)
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticación requerida.']);
            exit;
        }

        $isParticipant = $this->OutingDAO->isUserJoined($outingId, $userId);
        if (!$isParticipant) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo participantes.']);
            exit;
        }

        try {
            $messages = $this->OutingDAO->getMessagesByOuting($outingId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno al cargar mensajes.']);
            exit;
        }
    }
    // Método para enviar un nuevo mensaje (POST /outings/{id}/messages)
    public function sendMessage(int $outingId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit;
        }

        if (!$this->OutingDAO->isUserJoined($outingId, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Solo los participantes pueden enviar mensajes.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $messageText = htmlspecialchars(trim($input['text'] ?? ''), ENT_QUOTES, 'UTF-8');
        $replyToId = isset($input['reply_to']) ? (int)$input['reply_to'] : null;

        if (empty($messageText)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío.']);
            exit;
        }

        $messageId = $this->OutingDAO->sendMessage($outingId, $userId, $messageText, $replyToId);
        if ($messageId) {
            echo json_encode([
                'success' => true,
                'message' => 'Mensaje enviado correctamente.',
                'message_id' => $messageId
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al enviar el mensaje. El canal de chat no está disponible.'
            ]);
        }
        exit;
    }

    /**
     * Muestra la página de chat para una salida específica.
     * Ruta: GET /chat.php?id={outingId}
     */
    public function showChatPage()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            // Redirigir si no está autenticado
            header('Location: /login.php');
            exit;
        }

        // Obtener el outingId desde el parámetro GET
        $outingId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$outingId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de la salida no proporcionado.']);
            exit;
        }

        // 1. Obtener los detalles completos de la salida (incluye creador y participantes)
        $outing = $this->OutingDAO->getOutingDetails($outingId);

        if (!$outing) {
            // Manejar 404
            http_response_code(404);
            $viewPath = __DIR__ . '/../view/404.php';
            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                echo "Error: La vista '404.php' no fue encontrada.";
            }
            exit;
        }

        // 2. Verificar permisos: Solo participantes pueden acceder a la página de chat
        $isParticipant = $this->OutingDAO->isUserJoined($outingId, $userId);

        if (!$isParticipant) {
            // Si el usuario no es participante
            $_SESSION['error_message'] = "No tienes permiso para acceder al chat de esta salida.";
            header('Location: /outing_details.php?id=' . $outingId);
            exit;
        }

        // 3. Preparar datos para la vista (serán usados por el JS)
        $outingDataJson = json_encode($outing);

        // 4. Cargar la vista
        $viewPath = __DIR__ . '/../view/outing/chat.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error: La vista 'chat.php' no fue encontrada en la ruta esperada."]);
        }
    }
    public function reportUser()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $reportedUserId = (int)($input['user_id'] ?? 0);
        $reason = trim($input['reason'] ?? '');

        if (!$reportedUserId || !$reason) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan datos del reporte.']);
            exit;
        }

        // Validar que el motivo sea permitido
        $validReasons = ['spam', 'abuse', 'hate', 'fraud'];
        if (!in_array($reason, $validReasons)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Motivo de reporte no válido.']);
            exit;
        }

        // Verificar que el usuario no haya reportado previamente al mismo usuario
        if ($this->OutingDAO->hasReported($userId, $reportedUserId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ya has reportado a este usuario.']);
            exit;
        }

        // Crear el reporte
        if ($this->OutingDAO->createReport($userId, $reportedUserId, $reason)) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Reporte enviado correctamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al enviar el reporte.']);
        }
        exit;
    }

    public function getOutingDetails(int $outingId)
    {
        // Aseguramos que la respuesta sea JSON
        header('Content-Type: application/json');

        // **Nota:** No implementamos la verificación de sesión aquí (como en isJoined),
        // porque los detalles de una salida (outing) usualmente requieren solo el ID 
        // y no dependen de la sesión del usuario para su existencia. Si necesitas 
        // validación de sesión, añádela aquí.

        try {
            // Asumiendo que el DAO es una propiedad de la clase, como en tu ejemplo anterior
            // $this->OutingDAO debe ser una instancia de OutingDAO
            $outingDetails = $this->OutingDAO->getOutingDetailsById($outingId);

            if (!$outingDetails) {
                // Respuesta 404 Not Found
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => "Salida con ID $outingId no encontrada."
                ]);
                exit;
            }

            // Respuesta exitosa (200 OK)
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'outing' => $outingDetails
            ]);
            exit;
        } catch (\Exception $e) {
            // Manejo de error interno (500)
            http_response_code(500);
            // Opcional: registrar $e->getMessage() para debug
            echo json_encode([
                'success' => false,
                'message' => 'Error interno del servidor al cargar los detalles de la salida.'
            ]);
            exit;
        }
    }
}
