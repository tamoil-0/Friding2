<?php

namespace App\Controller;

use App\DAO\AuthDAO;
use App\Model\Auth;
use App\Model\User;
use App\Services\EmailService;

class AuthController
{
    private AuthDAO $authDAO;

    public function __construct()
    {
        $this->authDAO = new AuthDAO();

        // Inicia la sesión solo si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Login

    public function showLogin()
    {
        require __DIR__ . '/../view/auth/login.php';
    }

public function login()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Por favor, complete todos los campos.";
            require __DIR__ . '/../view/auth/login.php';
            return;
        }

        $userData = $this->authDAO->findUserByEmail($email);

        if ($userData && password_verify($password, $userData['password_hash'])) {
            // ✅ Si el correo aún no está verificado
            if ((int)$userData['is_email_verified'] === 0) {
                // CORRECCIÓN: Guardar el email y el login_id para el reenvío
                $_SESSION['email_verification'] = $userData['email'];
                $_SESSION['pending_login_id'] = $userData['login_id']; 
                $error = "Debes verificar tu correo antes de ingresar. 
                      Hemos enviado un código a tu email.";
                header('Location: /verify-code');
                exit;
            }

            // ✅ Autenticación exitosa
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['login_id'] = $userData['login_id'];

            header('Location: /outgoing');
            exit;
        } else {
            // ❌ Fallo autenticación
            $error = "Correo o contraseña incorrectos.";
            require __DIR__ . '/../view/auth/login.php';
        }
    }

    // Register

    public function showRegister()
    {
        require __DIR__ . '/../view/auth/register.php';
    }

    public function register()
    {
        $username = $_POST['username'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $bio = $_POST['bio'] ?? null;
        $photoUrl = $_POST['photo_url'] ?? null;

        // Validaciones básicas
        if (empty($username) || empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = "Por favor, complete todos los campos obligatorios.";
            require __DIR__ . '/../view/auth/register.php';
            return;
        }

        if ($password !== $confirmPassword) {
            $error = "Las contraseñas no coinciden.";
            require __DIR__ . '/../view/auth/register.php';
            return;
        }

        // Hash de la contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $user = new User();
        $user->setUsername($username);
        $user->setFullName($fullName);
        $user->setBio($bio);
        $user->setPhotoUrl($photoUrl);

        $auth = new Auth();
        $auth->setEmail($email);
        $auth->setPasswordHash($passwordHash);

        $loginId = $this->authDAO->registerUserAndLogin($user, $auth);
        if ($loginId !== false) {
            // Iniciar sesión temporal con email
            session_start();
            $_SESSION['email_verification'] = $email;

            // Generar código de verificación
            $code = $this->authDAO->generateNewCode();
            $this->authDAO->saveVerificationCode($loginId, $code, 15); // válido 15 min

            // Enviar email con el código
            $emailService = new EmailService();
            $emailService->sendEmail(
                $email,
                'Verificación de correo',
                '<h1>Hola ' . htmlspecialchars($username) . '!</h1><p>Tu código de verificación es: <b>' . $code . '</b></p>'
            );

            // Redirigir a verify-code.php
            header('Location: /verify-code');
            exit;
        } else {
            // Manejar error de registro (ej. email o usuario ya existen)
            $error = "El usuario o correo electrónico ya existen.";
            require __DIR__ . '/../view/auth/register.php';
        }
    }


    // Verificar codigo

    public function showVerifyCode()
    {

        require __DIR__ . '/../view/auth/verify-code.php';
    }

    public function verifyCode()
    {

        $email = $_SESSION['email_verification'] ?? '';
        $code = $_POST['verification_code'] ?? '';

        if (empty($email)) {
            header('Location: /login');
            exit;
        }

        if (empty($code)) {
            $error = "Por favor, ingrese el código de verificación.";
            require __DIR__ . '/../view/auth/verify-code.php';
            return;
        }

        $loginData = $this->authDAO->findUserByEmail($email);
        if (!$loginData) {
            $error = "Usuario no encontrado.";
            require __DIR__ . '/../view/auth/verify-code.php';
            return;
        }

        $loginId = $loginData['login_id'];

        if ($this->authDAO->isVerificationCodeValid($loginId, $code)) {
            $this->authDAO->markEmailAsVerified($loginId);
            unset($_SESSION['email_verification']);

            // Auto-login
            $_SESSION['user_id'] = $loginData['user_id'];
            $_SESSION['username'] = $loginData['username'];

            header('Location: /outgoing');
            exit;
        } else {
            $error = "Código inválido o expirado.";
            require __DIR__ . '/../view/auth/verify-code.php';
        }
    }


    public function resendVerification()
    {
        $loginId = $_SESSION['pending_login_id'] ?? null;

        if (!$loginId) {
            $error = "No se encontró un usuario pendiente de verificación.";
            require __DIR__ . '/../view/auth/verify-code.php';
            return;
        }

        // 1. Generar nuevo código
        $newCode = random_int(100000, 999999);
        $expiresAt = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // 2. Guardar en la BD
        $this->authDAO->updateVerificationCode($loginId, $newCode, $expiresAt);

        // 3. Obtener datos del usuario
        $userData = $this->authDAO->findUserByLoginId($loginId);

        if (!$userData) {
            $error = "Usuario no encontrado.";
            require __DIR__ . '/../view/auth/verify-code.php';
            return;
        }

        // 4. Enviar correo
        $emailService = new EmailService();
        $emailService->sendEmail(
            $userData['email'],
            'Verificación de correo',
            '<h1>Hola ' . htmlspecialchars($userData['username']) . '!</h1>
         <p>Tu nuevo código de verificación es: <b>' . $newCode . '</b></p>'
        );

        $success = "Se envió un nuevo código a tu correo.";
        require __DIR__ . '/../view/auth/verify-code.php';
    }


    // Recuperar contraseña
    public function showForgotPassword()
    {
        require __DIR__ . '/../view/auth/forgot-password.php';
    }

    
    public function sendResetLink()
    {
        $email = $_POST['email'] ?? null;
        $error = null;
        $success = null;

        if (!$email) {
            $error = "Debes ingresar tu correo.";
            require __DIR__ . '/../view/auth/forgot-password.php';
            return;
        }

        $user = $this->authDAO->findUserByEmail($email);

        if (!$user) {
            $error = "No existe una cuenta con ese correo.";
            require __DIR__ . '/../view/auth/forgot-password.php';
            return;
        }

        // Generar token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Guardar en BD
        $this->authDAO->createPasswordResetToken($user['login_id'], $token, $expiresAt);

        // Enviar correo
        $resetLink = "https://friding.com/reset-password?token=" . urlencode($token);
        $emailService = new EmailService();
        $emailService->sendEmail(
            $email,
            "Restablecer contraseña",
            "<p>Hola " . htmlspecialchars($user['username']) . ",</p>
             <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
             <a href='$resetLink'>$resetLink</a>"
        );

        $success = "Se envió un enlace de recuperación a tu correo.";
        require __DIR__ . '/../view/auth/forgot-password.php';
    }

    // Mostrar formulario para cambiar contraseña
    public function showResetPassword()
    {
        $token = $_GET['token'] ?? null;
        $error = null;

        if (!$token) {
            $error = "Token inválido.";
        } else {
            $reset = $this->authDAO->findPasswordResetByToken($token);

            if (!$reset || strtotime($reset['expires_at']) < time()) {
                $error = "El enlace ya expiró o es inválido.";
                $token = null; // invalidamos el token para la vista
            }
        }

        require __DIR__ . '/../view/auth/reset-password.php';
    }

    // Procesar cambio de contraseña
    public function resetPassword()
    {
        $token = $_POST['token'] ?? null;
        $password = $_POST['password'] ?? null;
        $passwordConfirm = $_POST['password_confirm'] ?? null;
        $error = null;
        $success = null;

        if (!$token) {
            $error = "Token inválido.";
        } else {
            $reset = $this->authDAO->findPasswordResetByToken($token);

            if (!$reset || strtotime($reset['expires_at']) < time()) {
                $error = "El enlace ya expiró o es inválido.";
            } elseif (!$password || strlen($password) < 6) {
                $error = "La contraseña debe tener al menos 6 caracteres.";
            } elseif ($password !== $passwordConfirm) {
                $error = "Las contraseñas no coinciden.";
            } else {
                // Actualizar contraseña
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $this->authDAO->updatePassword($reset['login_id'], $hash);

                // Invalidar token
                $this->authDAO->deletePasswordReset($token);

                $success = "Tu contraseña fue cambiada. Ahora puedes iniciar sesión.";
                require __DIR__ . '/../view/auth/login.php';
                return;
            }
        }

        // Si hubo error, volvemos a mostrar reset-password
        require __DIR__ . '/../view/auth/reset-password.php';
    }



    public function logout()
    {
        // Inicia la sesión si no ha sido iniciada.
        // Esto es redundante si ya lo haces en index.php, pero es una buena práctica de seguridad.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vacía todas las variables de sesión.
        $_SESSION = [];

        // Destruye la sesión.
        session_destroy();

        // Redirige al usuario a la página de inicio o a la de login.
        header('Location: /');
        exit;
    }
}
