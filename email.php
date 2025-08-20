<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
require 'vendor/autoload.php'; // Composer autoload
 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail = new PHPMailer(true);
 
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@luferatech.com'; // your Gmail
        $mail->Password   = 'ucmp qnei nwmm eboz';   // Google App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
 
        // Recipients
        $mail->setFrom('info@luferatech.com', 'Lufera');
        $mail->addAddress('rameshmkarthi530@gmail.com'); // recipient
 
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from PHPMailer + Gmail SMTP';
        $mail->Body    = '<h2>Hello!</h2><p>This is a test email sent using <b>PHPMailer + Gmail SMTP</b>.</p>';
 
        $mail->send();
        echo "✅ Email sent successfully!";
    } catch (Exception $e) {
        echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}";
    }
}
?>
 
<!DOCTYPE html>
<html>
<head>
    <title>Send Email</title>
</head>
<body>
    <form method="post">
        <button type="submit">Send Test Email</button>
    </form>
</body>
</html>