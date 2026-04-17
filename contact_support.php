<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/partials/connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');

// Get orders within 7 days window
$sql = "
SELECT o.*, u.email, u.first_name, u.last_name
FROM orders o
JOIN users u ON o.user_id = u.id
WHERE 
    DATE(o.created_on) <= '$today'
    AND DATE_ADD(o.created_on, INTERVAL 7 DAY) >= '$today'
    AND (o.last_reminder_sent IS NULL OR o.last_reminder_sent != '$today')
    AND o.status = 'Pending'
    AND is_Active = 2
";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {

    $email = $row['email'];
    $name  = $row['first_name'] . ' ' . $row['last_name'];
    $invoice = $row['invoice_id'];

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['EMAIL_USERNAME'];
        $mail->Password = $_ENV['GMAIL_APP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Reminder: Complete Your Payment";

        $mail->Body = "
            <h3>Hello $name,</h3>
            <p>Your order <b>$invoice</b> is still pending.</p>
            <p>Please complete your order within 7 days.</p>
        ";

        $mail->send();

        // Update last sent date
        $conn->query("
            UPDATE orders 
            SET last_reminder_sent = '$today' 
            WHERE id = {$row['id']}
        ");

    } catch (Exception $e) {
        error_log("Mail failed: " . $mail->ErrorInfo);
    }
}