<?php

// ------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ------Eliminar despues ------

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Utils\Router;
use App\Controller\AuthController;
use App\Controller\OutingController;

$router = new Router();

// --- Definición de Rutas ---

// Ruta Raíz
$router->get('/', function () {
    $indexPath = __DIR__ . '/../src/view/index.html';

    if (file_exists($indexPath)) {
        header('Content-Type: text/html');
        readfile($indexPath);
    } else {

        http_response_code(404);
        echo "404 - Archivo index.html no encontrado.";
    }
});

// Definir los middlewares
$redirectIfAuthenticated = function () {
    // Si el usuario ya tiene una sesión, lo redirige
    if (isset($_SESSION['user_id'])) {
        header('Location: /outgoing');
        exit;
    }
    return true;
};

$isAuthenticated = function () {
    // Si no hay sesión, niega el acceso
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    return true;
};

//Registro y logeo
$router->get('/login', [AuthController::class, 'showLogin'], $redirectIfAuthenticated);  //Listo
$router->post('/login', [AuthController::class, 'login']); //Listo
$router->get('/register', [AuthController::class, 'showRegister'], $redirectIfAuthenticated); //Listo
$router->post('/register', [AuthController::class, 'register']); //Listo
$router->get('/logout', [AuthController::class, 'logout']);

// Rutas para verificación de código
$router->get('/verify-code', [AuthController::class, 'showVerifyCode'], $redirectIfAuthenticated);  //Listo
$router->post('/verify-code', [AuthController::class, 'verifyCode']); //Listo 
$router->post('/resend-verification', [AuthController::class, 'resendVerification']); //Listo

// Recuperación de contraseña
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], $redirectIfAuthenticated); //Listo
$router->post('/forgot-password', [AuthController::class, 'sendResetLink']); //Listo

$router->get('/reset-password', [AuthController::class, 'showResetPassword'], $redirectIfAuthenticated); //Listo
$router->post('/reset-password', [AuthController::class, 'resetPassword']); //Listo

// Salidas
$router->get('/outgoing', [OutingController::class, 'showOutgoingList'], $isAuthenticated);
$router->get('/outgoing-nolog', [OutingController::class, 'showOutgoingListnolog']);
$router->get('/outings/list', [OutingController::class, 'listOutings'], $isAuthenticated);
$router->get('/outings/list/nolog', [OutingController::class, 'listOutings']);
$router->post('/outings/join/:int', [OutingController::class, 'joinOuting',$isAuthenticated]);
$router->get('/outings/:int/is-joined', [OutingController::class, 'isJoined']); 

// Mis salidas

$router->get('/myoutgoing', [OutingController::class, 'showMyOutgoingList'], $isAuthenticated);
$router->get('/outings/join', [OutingController::class, 'getMyJoinedOutings'], $isAuthenticated);
$router->get('/outings/created', [OutingController::class, 'getMyCreatedOutings'], $isAuthenticated);
$router->post('/outings/leave/:int', [OutingController::class, 'leaveOuting'], $isAuthenticated); 
$router->delete('/outings/delete/:int', [OutingController::class, 'deleteOuting'], $isAuthenticated);
$router->post('/outings/create', [OutingController::class, 'createOuting'], $isAuthenticated);

// El {id} captura el ID de la salida desde la URL (ej: /outings/123)
$router->get('/outings/:int', [OutingController::class, 'getOutingDetails'], $isAuthenticated);
///Salidas privadas
$router->post('/outings/request-join/:int', [OutingController::class, 'requestJoinOuting', $isAuthenticated]);
$router->get('/outings/:int/request-status', [OutingController::class, 'getRequestStatusOuting', $isAuthenticated]);
$router->get('/outings/:int/is-creator', [OutingController::class, 'isCreatorRoute']);
$router->get('/outings/:int/requests', [OutingController::class, 'getEventRequests', $isAuthenticated]);
$router->post('/outings/request/accept/:int', [OutingController::class, 'acceptRequest', $isAuthenticated]);
$router->post('/outings/request/reject/:int', [OutingController::class, 'rejectRequest', $isAuthenticated]);



$router->get('/profile/view', [OutingController::class, 'showMyOwnProfileView'], $isAuthenticated);
$router->get('/profile/me', [OutingController::class, 'getMyProfile'], $isAuthenticated); 
$router->post('/profile/update', [OutingController::class, 'updateMyProfile'], $isAuthenticated);

$router->get('/profile/view/:int', [OutingController::class, 'showProfileView'], $isAuthenticated);
$router->get('/profile/user/:int', [OutingController::class, 'getUserProfile'], $isAuthenticated);

// Endpoint para obtener las notificaciones no leídas (usado por el Polling)
$router->get('/notifications/unread', [OutingController::class, 'listUnread'], $isAuthenticated);

// Endpoint para limpiar/marcar como leídas (usado por el botón "Clear all")
$router->post('/notifications/read-all', [OutingController::class, 'markAllAsRead'], $isAuthenticated);



// routes.php
$router->get('/outings/:int/messages', [OutingController::class, 'getMessages', $isAuthenticated]);
$router->post('/outings/:int/messages', [OutingController::class, 'sendMessage', $isAuthenticated]);
$router->get('/chat.php', [OutingController::class, 'showChatPage']);
$router->post('/api/reports', [OutingController::class, 'reportUser', $isAuthenticated]);
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Despacha la solicitud a la ruta correspondiente.
$router->dispatch($uri, $method);
