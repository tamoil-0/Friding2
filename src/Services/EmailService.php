<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    protected $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        // Configuración básica de SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.hostinger.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'team@friding.com';
        $this->mailer->Password = 'laicadebil777K#';
        $this->mailer->SMTPSecure = 'tls'; // o 'ssl'
        $this->mailer->Port = 587; // según tu proveedor
        $this->mailer->CharSet = 'UTF-8';
    }

    public function sendEmail($to, $subject, $body, $from = null)
    {
        try {
            $this->mailer->setFrom($from ?? 'team@friding.com', 'Friding');
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            return "Error al enviar el email: " . $this->mailer->ErrorInfo;
        }
    }
}