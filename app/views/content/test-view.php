<?php
require 'app/lib/PHPMailer/src/Exception.php';
require 'app/lib/PHPMailer/src/PHPMailer.php';
require 'app/lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'mail.digifutbol.com';
$mail->SMTPAuth = true;
$mail->Username = 'noreply@digifutbol.com';
$mail->Password = 'TU_PASSWORD';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('noreply@digifutbol.com', 'Prueba DigiFutbol');
$mail->addAddress('tucorreo@yahoo.com'); // prueba directo al problema
$mail->Subject = "🚀 Prueba SMTP DigiFutbol";
$mail->Body = "Si recibes este correo, Yahoo está aceptando los mensajes.";

if ($mail->send()) {
    echo "Correo enviado con éxito.";
} else {
    echo "Error: " . $mail->ErrorInfo;
}
?>
