<?php
require_once __DIR__ . '/../../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'mail.digitech.com.ec';
$mail->SMTPAuth = true;
$mail->Username = 'info@digifutbol.com';
$mail->Password = 'Fu7b0l#2520';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;

$mail->setFrom('info@digifutbol.com', 'Prueba DigiFutbol');
$mail->addAddress('test-b7x7ot7ed@srv1.mail-tester.com'); // prueba directo al problema
$mail->Subject = "🚀 Prueba SMTP DigiFutbol";
$mail->Body = "Si recibes este correo, Yahoo está aceptando los mensajes.";

if ($mail->send()) {
    echo "Correo enviado con éxito absoluto.";
} else {
    echo "Error: " . $mail->ErrorInfo;
}
?>
