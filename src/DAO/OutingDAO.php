<?php

namespace App\DAO;

use App\Database\Connection;
use App\Model\Outing;
use PDO;

class OutingDAO
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Connection::getConnection();
    }

    // En tu OutingDAO (Clase de Acceso a Datos de Outing)

    // Leer todas las outing con filtro opcional
    public function getAll(?string $searchTerm = null): array
    {
        // 1. Consulta SQL base
        $sql = "SELECT * FROM outing";
        $params = [];

        // 2. Condición: Añadir la cláusula WHERE si se proporciona un término de búsqueda
        if (!empty($searchTerm)) {
            // Añadimos WHERE para buscar coincidencias en 'title' o 'description'
            $sql .= " WHERE title LIKE ? OR description LIKE ?";

            // Creamos el término con comodines '%' para buscar coincidencias parciales
            $likeTerm = "%" . $searchTerm . "%";

            // Preparamos los parámetros para evitar inyección SQL
            $params[] = $likeTerm; // Parámetro para title
            $params[] = $likeTerm; // Parámetro para description
        }

        // 3. Opcional: Agregar orden por defecto
        $sql .= " ORDER BY id DESC"; // O por fecha si tienes el campo

        // 4. Preparar y ejecutar la consulta
        $stmt = $this->conn->prepare($sql);

        // Ejecutar con los parámetros si existen
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC); // Usamos FETCH_ASSOC para un array asociativo

        return $results ?: [];
    }
    //Obtener participantes de una salida
    public function getParticipants(int $outingId): array
    {
        $sql = "SELECT u.id, u.username, u.full_name , u.photo_url
                FROM user_account u
                JOIN outing_participants op ON u.id = op.user_id
                WHERE op.outing_id = :outing_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':outing_id' => $outingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCreatedBy(int $outingId): ?array
    {
        $sql = "SELECT u.id, u.username, u.full_name, u.photo_url
            FROM outing o
            JOIN user_account u ON o.user_id = u.id
            WHERE o.id = :outing_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':outing_id' => $outingId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }


    // Unirse a una salida
    public function join(int $outingId, int $userId): bool
    {
        if ($this->isUserJoined($outingId, $userId)) {
            return false;
        }

        $sql = "INSERT INTO outing_participants (outing_id, user_id, joined_at)
             VALUES (:outing_id, :user_id, NOW())";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':outing_id' => $outingId,
            ':user_id' => $userId
        ]);
    }

    // Verificar si el usuario ya está unido
    public function isUserJoined(int $outingId, int $userId): bool
    {
        $sql = "SELECT 1 FROM outing_participants 
            WHERE outing_id = :outing_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':outing_id' => $outingId,
            ':user_id' => $userId
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function getParticipantsByOuting(int $outingId): array
    {
        $sql = "SELECT 
                u.id, 
                u.username, 
                u.full_name, 
                u.photo_url
            FROM outing_participants op
            JOIN user_account u ON op.user_id = u.id
            WHERE op.outing_id = :outing_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':outing_id' => $outingId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Salidas que el usaurio se unio

    public function getOutingsUserIsJoined(int $userId): array
    {
        // 1. Obtener los eventos base y el creador
        $sqlEvents = "SELECT 
                    o.id,
                    o.title,
                    o.description,
                    o.meeting_point,
                    o.event_date,
                    o.event_time,
                    o.estimated_amount,
                    o.status,
                    o.user_id AS creator_id,
                    uc.username AS creator_username,
                    uc.full_name AS creator_full_name,
                    uc.photo_url AS creator_photo_url
                FROM outing o
                JOIN outing_participants op ON o.id = op.outing_id
                JOIN user_account uc ON o.user_id = uc.id
                WHERE op.user_id = :user_id  -- El usuario es participante
                AND o.user_id != :user_id_creator; -- 🚩 Condición para NO ser el creador";

        $stmtEvents = $this->conn->prepare($sqlEvents);

        // Ejecutar con el mismo userId para ambas condiciones
        $stmtEvents->execute([
            ':user_id' => $userId,
            ':user_id_creator' => $userId // Usamos el mismo valor para la exclusión
        ]);

        $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

        // 2. Adjuntar los participantes a cada evento (este código no necesita cambios)
        foreach ($events as &$event) {

            // Ahora, esta lista de participantes incluirá al creador de la salida
            // (ya que el usuario actual NO es el creador de esta salida).
            $event['participants'] = $this->getParticipantsByOuting($event['id']);

            // 3. Reestructurar el creador
            $event['created_by'] = [
                'id' => $event['creator_id'],
                'username' => $event['creator_username'],
                'full_name' => $event['creator_full_name'],
                'photo_url' => $event['creator_photo_url'],
            ];

            // Limpiar los campos individuales del creador
            unset($event['creator_id'], $event['creator_username'], $event['creator_full_name'], $event['creator_photo_url']);
        }

        return $events;
    }

    // Salidas que el usaurio creo

    public function getOutingsCreatedByUser(int $userId): array
    {
        // 1. Obtener los eventos base y la información del creador
        $sqlEvents = "SELECT 
                        o.id,
                        o.title,
                        o.description,
                        o.meeting_point,
                        o.event_date,
                        o.event_time,
                        o.estimated_amount,
                        o.status,
                        uc.id AS creator_id,
                        uc.username AS creator_username,
                        uc.full_name AS creator_full_name,
                        uc.photo_url AS creator_photo_url
                    FROM outing o
                    INNER JOIN user_account uc ON o.user_id = uc.id
                    -- ❌ CAMBIO CLAVE: Unirse a la tabla de participantes
                    INNER JOIN outing_participants op ON o.id = op.outing_id
                    WHERE o.user_id = :user_id
                    -- ❌ CAMBIO CLAVE: Asegurar que el creador esté listado como participante
                    AND op.user_id = o.user_id;";

        $stmtEvents = $this->conn->prepare($sqlEvents);
        $stmtEvents->execute([':user_id' => $userId]);
        $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

        // 2. Adjuntar los participantes y reestructurar el creador
        foreach ($events as &$event) {
            // Adjuntar la lista de participantes usando el método auxiliar
            $event['participants'] = $this->getParticipantsByOuting($event['id']);

            // Reestructurar los datos del creador en un sub-objeto 'created_by'
            $event['created_by'] = [
                'id' => $event['creator_id'],
                'username' => $event['creator_username'],
                'full_name' => $event['creator_full_name'],
                'photo_url' => $event['creator_photo_url'],
            ];

            // Limpiar los campos individuales para un JSON más limpio
            unset($event['creator_id'], $event['creator_username'], $event['creator_full_name'], $event['creator_photo_url']);
        }

        return $events;
    }

    public function leave(int $outingId, int $userId): bool
    {
        // 1. Elimina al usuario de la tabla de PARTICIPANTES
        $sql_participants = "DELETE FROM outing_participants 
        WHERE outing_id = :outing_id AND user_id = :user_id";

        $stmt_participants = $this->conn->prepare($sql_participants);

        // Ejecuta la consulta de participantes
        $executed_participants = $stmt_participants->execute([
            ':outing_id' => $outingId,
            ':user_id' => $userId
        ]);

        // Verifica si la ejecución fue exitosa Y si se eliminó al menos una fila (era participante)
        $success_participants = $executed_participants && $stmt_participants->rowCount() > 0;

        // ---

        // 2. Elimina CUALQUIER registro de solicitud del usuario en outing_requests
        // Esto borra solicitudes 'pending', 'accepted' o 'rejected' para esa salida.
        $sql_requests = "DELETE FROM outing_requests 
        WHERE outing_id = :outing_id AND user_id = :user_id";

        $stmt_requests = $this->conn->prepare($sql_requests);

        // Ejecuta la consulta de solicitudes
        $executed_requests = $stmt_requests->execute([
            ':outing_id' => $outingId,
            ':user_id' => $userId
        ]);

        // Solo nos interesa que la consulta de solicitudes se haya ejecutado bien.
        $success_requests = $executed_requests;

        // ---

        // Retorna true solo si la eliminación del PARTICIPANTE fue exitosa. 
        // La eliminación de la solicitud es una acción de limpieza que se ejecuta siempre.
        return $success_participants;
    }

    public function isCreator(int $outingId, int $userId): bool
    {
        $sql = "SELECT user_id FROM outing WHERE id = :outing_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':outing_id' => $outingId]);
        $creatorId = $stmt->fetchColumn();

        // Compara el user_id de la salida con el user_id de la sesión
        return $creatorId !== false && (int)$creatorId === $userId;
    }

    /**
     * Elimina una salida y automáticamente todos sus participantes
     * gracias a la restricción ON DELETE CASCADE en la BD.
     */
    public function delete(int $outingId): bool
    {
        // La sentencia SQL es simple, solo borra la salida.
        // La restricción FOREIGN KEY (outing_id) REFERENCES outing(id) ON DELETE CASCADE
        // en la tabla outing_participants se encarga del resto.
        $sql = "DELETE FROM outing WHERE id = :outing_id";

        $stmt = $this->conn->prepare($sql);

        try {
            $executed = $stmt->execute([':outing_id' => $outingId]);

            // Retorna true si la ejecución fue exitosa Y se eliminó al menos una fila
            return $executed && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            // Manejo de errores de base de datos
            // En un entorno real, aquí registrarías $e->getMessage()
            return false;
        }
    }

    public function create(array $data, int $userId)
    {
        try {
            // Iniciar Transacción para asegurar que ambas inserciones se completen o ninguna.
            $this->conn->beginTransaction();

            // 1. Insertar la Salida (Tabla 'outing')
            $sqlOuting = "INSERT INTO outing (
                title, description, meeting_point, event_date, 
                event_time, estimated_amount, user_id, status
            ) VALUES (
                :title, :description, :meeting_point, :event_date, 
                :event_time, :estimated_amount, :user_id, :status
            )";

            $stmtOuting = $this->conn->prepare($sqlOuting);

            $executedOuting = $stmtOuting->execute([
                ':title'            => $data['title'],
                // Usa valores nulos o por defecto si no se proporcionan
                ':description'      => $data['description'] ?? null,
                ':meeting_point'    => $data['meeting_point'] ?? null,
                ':event_date'       => $data['event_date'],
                ':event_time'       => $data['event_time'],
                // Convertir a float/decimal, o usar null si no se proporciona
                ':estimated_amount' => $data['estimated_amount'] ?? null,
                ':user_id'          => $userId, // El ID del creador
                ':status'           => $data['status'] ?? 'Pendiente', // Estado por defecto
            ]);

            if (!$executedOuting) {
                $this->conn->rollBack();
                return false;
            }

            // Obtener el ID de la salida recién creada
            $outingId = $this->conn->lastInsertId();

            // 2. Registrar al Creador como Participante (Tabla 'outing_participants')
            $sqlParticipant = "INSERT INTO outing_participants (
                outing_id, user_id
            ) VALUES (
                :outing_id, :user_id
            )";

            $stmtParticipant = $this->conn->prepare($sqlParticipant);

            $executedParticipant = $stmtParticipant->execute([
                ':outing_id' => $outingId,
                ':user_id'   => $userId
            ]);

            if (!$executedParticipant) {
                $this->conn->rollBack();
                return false;
            }

            // Si ambas operaciones fueron exitosas, confirmar la transacción
            $this->conn->commit();

            return (int)$outingId;
        } catch (\PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            // En un entorno de producción, registrarías el error aquí
            error_log("Error al crear la salida: " . $e->getMessage());
            return false;
        }
    }

    public function getById(int $userId): ?array
    {
        $sql = "SELECT id, full_name, bio, photo_url 
                FROM user_account 
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function updateProfile(int $userId, string $fullName, ?string $bio, ?string $photoUrl): bool
    {
        $sql = "UPDATE user_account 
                SET full_name = :full_name, bio = :bio, photo_url = :photo_url 
                WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':full_name', $fullName);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':photo_url', $photoUrl);
            $stmt->bindParam(':id', $userId);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error al actualizar perfil del usuario ID: {$userId}. Mensaje: " . $e->getMessage());
            return false;
        }
    }


    public function countCreatedOutings(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM outing WHERE user_id = :userId";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        return (int) $stmt->fetchColumn();
    }


    public function countJoinedOutings(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM outing_participants WHERE user_id = :userId";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        return (int) $stmt->fetchColumn();
    }


    // notificciones

    public function getUnreadByUserId(int $userId): array
    {
        $sql = "SELECT id, texto, icono, enlace, fecha_creacion
                FROM notificaciones
                WHERE user_id = :user_id AND leida = 0
                ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // Mapeamos los campos a 'text', 'icon', 'link' para el frontend de JS
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Renombrar las claves para el consumo en JS
        $formattedResults = array_map(function ($data) {
            return [
                'id' => $data['id'],
                'text' => $data['texto'],
                'icon' => $data['icono'],
                'link' => $data['enlace'],
                'createdAt' => $data['fecha_creacion']
            ];
        }, $results);

        return $formattedResults;
    }


    public function markAsRead(int $userId, ?int $notificationId = null): bool
    {
        if ($notificationId) {
            $sql = "UPDATE notificaciones SET leida = 1 WHERE id = :id AND user_id = :user_id";
        } else {
            $sql = "UPDATE notificaciones SET leida = 1 WHERE user_id = :user_id AND leida = 0";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        if ($notificationId) {
            $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }


    public function createNotification(int $userId, string $texto, string $icono, string $enlace): int
    {
        $sql = "INSERT INTO notificaciones (user_id, texto, icono, enlace) 
                VALUES (:user_id, :texto, :icono, :enlace)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':texto', $texto, PDO::PARAM_STR);
        $stmt->bindParam(':icono', $icono, PDO::PARAM_STR);
        $stmt->bindParam(':enlace', $enlace, PDO::PARAM_STR);

        $stmt->execute();
        return (int) $this->conn->lastInsertId();
    }

    public function getCreatorId(int $outingId): ?int
    {
        $sql = "SELECT user_id FROM outing WHERE id = :outing_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':outing_id', $outingId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchColumn(); // Obtiene el valor de la primera columna

        return $result ? (int)$result : null;
    }

    // Registrar una solicitud de unión
    public function requestJoin(int $outingId, int $userId): bool
    {
        if ($this->hasRequested($outingId, $userId)) {
            return false; // Ya envió solicitud
        }

        $sql = "INSERT INTO outing_requests (outing_id, user_id, status, requested_at)
            VALUES (:outing_id, :user_id, 'pendiente', NOW())";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':outing_id' => $outingId,
            ':user_id' => $userId
        ]);
    }

    // Verificar si el usuario ya envió solicitud
    public function hasRequested(int $outingId, int $userId): bool
    {
        $sql = "SELECT 1 FROM outing_requests 
            WHERE outing_id = :outing_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':outing_id' => $outingId,
            ':user_id' => $userId
        ]);

        return $stmt->fetchColumn() !== false;
    }

    // Aceptar solicitud
    public function acceptRequest(int $requestId)
    {
        $sql = "UPDATE outing_requests 
            SET status = 'aceptado', responded_at = NOW()
            WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $requestId]);

        // Meter al usuario en outing_participants
        $sqlInsert = "INSERT IGNORE INTO outing_participants (outing_id, user_id, joined_at)
                  SELECT outing_id, user_id, NOW()
                  FROM outing_requests
                  WHERE id = :id";
        $stmtInsert = $this->conn->prepare($sqlInsert);
        $stmtInsert->execute([':id' => $requestId]);

        return true;
    }

    // Rechazar solicitud
    public function rejectRequest(int $requestId)
    {
        $sql = "UPDATE outing_requests 
            SET status = 'rechazado', responded_at = NOW()
            WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $requestId]);
    }

    // Obtener estado de la solicitud (null si no existe)
    public function getRequestStatus(int $outingId, int $userId): ?string
    {
        $sql = "SELECT status 
            FROM outing_requests 
            WHERE outing_id = :outing_id AND user_id = :user_id
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':outing_id' => $outingId,
            ':user_id' => $userId
        ]);

        $status = $stmt->fetchColumn();
        return $status !== false ? $status : null;
    }

    // Obtener solicitudes de un evento (pendientes)
    public function getRequestsForOuting(int $outingId): array
    {
        $sql = "SELECT r.id AS request_id, u.id, u.full_name, u.photo_url
            FROM outing_requests r
            JOIN user_account u ON r.user_id = u.id
            WHERE r.outing_id = :outing_id AND r.status = 'pendiente'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':outing_id' => $outingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Chat

    public function sendMessage(int $outingId, int $senderId, string $messageText, ?int $replyToId = null): ?int
    {
        $channelId = $this->getChannelIdByOuting($outingId);
        if (!$channelId) {
            return null;
        }

        $sql = "INSERT INTO chat_message (channel_id, sender_id, message_text, reply_to_id) 
            VALUES (:channel_id, :sender_id, :message_text, :reply_to_id)";
        $stmt = $this->conn->prepare($sql);
        $executed = $stmt->execute([
            ':channel_id' => $channelId,
            ':sender_id' => $senderId,
            ':message_text' => $messageText,
            ':reply_to_id' => $replyToId
        ]);

        return $executed ? (int)$this->conn->lastInsertId() : null;
    }
    // Método auxiliar para obtener el ID del canal
    public function getChannelIdByOuting(int $outingId): ?int
    {
        $sql = "SELECT id FROM chat_channel WHERE outing_id = :outing_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':outing_id' => $outingId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : null;
    }

    public function getMessagesByOuting(int $outingId, int $limit = 50, int $offset = 0): array
    {
        $channelId = $this->getChannelIdByOuting($outingId);
        if (!$channelId) {
            return [];
        }

        $sql = "SELECT 
                cm.id, cm.message_text, cm.sent_at, 
                u.id AS sender_id, u.username, u.full_name, u.photo_url
            FROM chat_message cm
            JOIN user_account u ON cm.sender_id = u.id
            WHERE cm.channel_id = :channel_id
            ORDER BY cm.sent_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':channel_id', $channelId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        // Deberías procesar los resultados para agrupar los datos del remitente
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Opcional: Reestructurar los mensajes para un JSON más limpio
        return array_map(function ($message) {
            return [
                'id' => $message['id'],
                'text' => $message['message_text'],
                'sent_at' => $message['sent_at'],
                'sender' => [
                    'id' => $message['sender_id'],
                    'username' => $message['username'],
                    'full_name' => $message['full_name'],
                    'photo_url' => $message['photo_url']
                ]
            ];
        }, array_reverse($messages)); // Invertir para que los más antiguos estén primero


    }

    /**
     * Obtiene todos los detalles de una salida, incluyendo participantes y creador.
     * Esta es la función clave para alimentar el frontend.
     */
    public function getOutingDetails(int $outingId): ?array
    {
        // Consulta base para obtener detalles de la salida y el creador
        $sql = "
            SELECT 
                o.id, o.title, o.description, o.user_id,
                u_creator.id AS creator_id, u_creator.full_name AS creator_full_name, u_creator.photo_url AS creator_photo_url
            FROM outing o
            JOIN user_account u_creator ON o.user_id = u_creator.id
            WHERE o.id = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$outingId]);
        $outing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$outing) {
            return null;
        }

        // Estructura los datos
        $details = [
            'id' => $outing['id'],
            'title' => $outing['title'],
            'description' => $outing['description'],
            'user_id' => [
                'id' => $outing['creator_id'],
                'full_name' => $outing['creator_full_name'],
                'photo_url' => $outing['creator_photo_url']
            ],
            'participants' => []
        ];

        // Obtener participantes
        $participantsSql = "
            SELECT 
                u.id, u.full_name, u.photo_url
            FROM outing_participants op
            JOIN user_account u ON op.user_id = u.id
            WHERE op.outing_id = ?
        ";
        $pStmt = $this->conn->prepare($participantsSql);
        $pStmt->execute([$outingId]);

        $participants = $pStmt->fetchAll(PDO::FETCH_ASSOC);

        $details['participants'] = array_map(function ($p) {
            return [
                'id' => $p['id'],
                'full_name' => $p['full_name'],
                'photo_url' => $p['photo_url']
            ];
        }, $participants);

        return $details;
    }
    public function createChatChannel(int $outingId): bool
    {
        $sql = "INSERT IGNORE INTO chat_channel (outing_id) VALUES (:outing_id)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':outing_id' => $outingId]);
    }

    /**
     * Crea un nuevo reporte en la base de datos.
     *
     * @param int $reporterId ID del usuario que realiza el reporte
     * @param int $reportedUserId ID del usuario reportado
     * @param string $reason Motivo del reporte (e.g., 'spam', 'abuse', 'hate', 'fraud')
     * @return bool True si el reporte se creó exitosamente, false en caso contrario
     */
    public function createReport(int $reporterId, int $reportedUserId, string $reason): bool
    {
        try {
            $sql = "INSERT INTO reports (reporter_id, reported_user_id, reason, reported_at) 
                    VALUES (:reporter_id, :reported_user_id, :reason, NOW())";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':reporter_id' => $reporterId,
                ':reported_user_id' => $reportedUserId,
                ':reason' => $reason
            ]);
        } catch (\PDOException $e) {
            // Opcional: Registrar el error en un log
            error_log("Error al crear reporte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un usuario ya ha reportado a otro usuario.
     *
     * @param int $reporterId ID del usuario que realiza el reporte
     * @param int $reportedUserId ID del usuario reportado
     * @return bool True si ya existe un reporte, false si no
     */
    public function hasReported(int $reporterId, int $reportedUserId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM reports 
                    WHERE reporter_id = :reporter_id AND reported_user_id = :reported_user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':reporter_id' => $reporterId,
                ':reported_user_id' => $reportedUserId
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error al verificar reporte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los reportes para un usuario específico.
     *
     * @param int $reportedUserId ID del usuario reportado
     * @return array Lista de reportes con detalles
     */
    public function getReportsByUser(int $reportedUserId): array
    {
        try {
            $sql = "SELECT 
                        r.id, r.reason, r.reported_at,
                        u_reporter.id AS reporter_id, u_reporter.full_name AS reporter_name
                    FROM reports r
                    JOIN user_account u_reporter ON r.reporter_id = u_reporter.id
                    WHERE r.reported_user_id = :reported_user_id
                    ORDER BY r.reported_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':reported_user_id' => $reportedUserId]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function ($report) {
                return [
                    'id' => (int)$report['id'],
                    'reason' => $report['reason'],
                    'reported_at' => $report['reported_at'],
                    'reporter' => [
                        'id' => (int)$report['reporter_id'],
                        'full_name' => $report['reporter_name']
                    ]
                ];
            }, $reports);
        } catch (\PDOException $e) {
            error_log("Error al obtener reportes: " . $e->getMessage());
            return [];
        }
    }

    public function getOutingDetailsById(int $outingId): ?array
    {
        // 1. Consulta SQL (obtiene la salida y los datos del creador)
        $sqlOuting = "SELECT 
                        o.id, o.title, o.description, o.meeting_point, 
                        o.event_date, o.event_time, o.estimated_amount, o.status,
                        uc.id AS creator_id, uc.username AS creator_username, 
                        uc.full_name AS creator_full_name, uc.photo_url AS creator_photo_url
                    FROM outing o
                    INNER JOIN user_account uc ON o.user_id = uc.id
                    WHERE o.id = :outing_id";

        $stmtOuting = $this->conn->prepare($sqlOuting);
        $stmtOuting->execute([':outing_id' => $outingId]);
        $outing = $stmtOuting->fetch(PDO::FETCH_ASSOC);

        if (!$outing) {
            return null;
        }

        // 2. Adjuntar participantes y reestructurar el creador
        // (Asume la existencia del método auxiliar getParticipantsByOuting)
        $outing['participants'] = $this->getParticipantsByOuting($outing['id']);

        $outing['created_by'] = [
            'id' => $outing['creator_id'],
            'username' => $outing['creator_username'],
            'full_name' => $outing['creator_full_name'],
            'photo_url' => $outing['creator_photo_url'],
        ];

        // Limpiar campos auxiliares del creador
        unset(
            $outing['creator_id'],
            $outing['creator_username'],
            $outing['creator_full_name'],
            $outing['creator_photo_url']
        );

        return $outing;
    }
}
