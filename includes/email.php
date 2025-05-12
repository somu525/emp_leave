<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $subject, $bodyHtml, $bodyAlt = '') {
    $mail = new PHPMailer(true);

    try {
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bsd4753@gmail.com'; 
        $mail->Password = 'dgpi jhxy yjbp sfyi'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('bsd4753@gmail.com', 'Leave Management System');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyAlt;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}