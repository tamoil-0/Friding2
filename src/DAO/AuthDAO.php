<?php

namespace App\DAO;

use App\Database\Connection;
use App\Model\User;
use App\Model\Auth;
use PDO;
use PDOException;

class AuthDAO
{

    private PDO $connection;

    public function __construct()
    {
        $this->connection = Connection::getConnection();
    }

    // Obtener datos con el email

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT l.id AS login_id, l.user_id, l.email, l.password_hash, l.is_email_verified,
                   u.username, u.full_name
            FROM login l
            JOIN user_account u ON l.user_id = u.id
            WHERE l.email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Registrar usaurio y login 

    public function registerUserAndLogin(User $user, Auth $auth): false|int
    {
        try {
            $this->connection->beginTransaction();

            // 1. Insertar en user_account
            $stmtUser = $this->connection->prepare("
                INSERT INTO user_account (username, full_name, bio, photo_url) 
                VALUES (?, ?, ?, ?)
            ");
            $stmtUser->execute([
                $user->getUsername(),
                $user->getFullName(),
                $user->getBio(),
                $user->getPhotoUrl()
            ]);
            $userId = (int)$this->connection->lastInsertId();

            // 2. Insertar en login
            $stmtLogin = $this->connection->prepare("
                INSERT INTO login (user_id, email, password_hash) 
                VALUES (?, ?, ?)
            ");
            $stmtLogin->execute([
                $userId,
                $auth->getEmail(),
                $auth->getPasswordHash()
            ]);
            $loginId = (int)$this->connection->lastInsertId();

            $this->connection->commit();
            return $loginId;
        } catch (PDOException $e) {
            $this->connection->rollBack();
            error_log("Error al registrar: " . $e->getMessage());
            return false;
        }
    }

    // Generar codigo 
    public function generateNewCode(int $length = 6): string
    {
        return substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }

    // Guardar código de verificación para login.id

    public function saveVerificationCode(int $loginId, string $code, int $minutesValid = 15): bool
    {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$minutesValid minutes"));
        $stmt = $this->connection->prepare("
            UPDATE login
            SET verification_code = ?, verification_expires_at = ?, is_email_verified = 0
            WHERE id = ?
        ");
        return $stmt->execute([$code, $expiresAt, $loginId]);
    }


    // Verificar si el código es correcto y no expiró (usando login.id)

    public function isVerificationCodeValid(int $loginId, string $code): bool
    {
        $stmt = $this->connection->prepare("
            SELECT verification_expires_at
            FROM login
            WHERE id = ? AND verification_code = ? AND is_email_verified = 0
        ");
        $stmt->execute([$loginId, $code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) return false;

        return strtotime($result['verification_expires_at']) > time();
    }


    // Marcar email como verificado

    public function markEmailAsVerified(int $loginId): bool
    {
        $stmt = $this->connection->prepare("
            UPDATE login
            SET is_email_verified = 1, verification_code = NULL, verification_expires_at = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$loginId]);
    }


    // Actualiza el código de verificación (para reenvío)

    public function updateVerificationCode(int $loginId, string $code, string $expiresAt): void
    {
        $stmt = $this->connection->prepare("
            UPDATE login
            SET verification_code = ?, verification_expires_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$code, $expiresAt, $loginId]);
    }


    // Busca usuario por login_id (para reenviar correo)

    public function findUserByLoginId(int $loginId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT l.id AS login_id, l.user_id, l.email, u.username, u.full_name
            FROM login l
            JOIN user_account u ON l.user_id = u.id
            WHERE l.id = ?
        ");
        $stmt->execute([$loginId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Guarda un token de recuperación
    public function createPasswordResetToken(int $loginId, string $token, string $expiresAt): void
    {
        $stmt = $this->connection->prepare("
        INSERT INTO password_resets (login_id, token, expires_at)
        VALUES (?, ?, ?)
    ");
        $stmt->execute([$loginId, $token, $expiresAt]);
    }

    // Busca un token válido
    public function findPasswordResetByToken(string $token): ?array
    {
        $stmt = $this->connection->prepare("
        SELECT * FROM password_resets WHERE token = ?
    ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    
    // Cambia la contraseña
    public function updatePassword(int $loginId, string $hash): void
    {
        $stmt = $this->connection->prepare("
        UPDATE login SET password_hash = ? WHERE id = ?
    ");
        $stmt->execute([$hash, $loginId]);
    }

    // Elimina un token usado
    public function deletePasswordReset(string $token): void
    {
        $stmt = $this->connection->prepare("
        DELETE FROM password_resets WHERE token = ?
    ");
        $stmt->execute([$token]);
    }

}
