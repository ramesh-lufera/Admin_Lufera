<?php
// send_reminders.php
date_default_timezone_set('Asia/Kolkata');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/partials/connection.php';
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// $dotenv = Dotenv::createImmutable(__DIR__);
// $dotenv->load();
//$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$now       = new DateTime();
$windowMin = (clone $now)->modify('+8 minutes')->format('Y-m-d H:i:s');
$windowMax = (clone $now)->modify('+12 minutes')->format('Y-m-d H:i:s');

$query = "
SELECT id, sheet_id, sheet_row, remind_at, message, recipient_email
FROM sheet_reminders
WHERE remind_at BETWEEN DATE_ADD(NOW(), INTERVAL 8 MINUTE)
                    AND DATE_ADD(NOW(), INTERVAL 12 MINUTE)
AND notified = 0
AND recipient_email IS NOT NULL
AND recipient_email != ''
";

$stmt = $conn->prepare($query);
//$stmt->bind_param("ss", $windowMin, $windowMax);
$stmt->execute();
$result = $stmt->get_result();

$sentCount = 0;
$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = $_ENV['EMAIL_USERNAME'];
$mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;

$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
$mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
$mail->isHTML(true);
while ($row = $result->fetch_assoc()) {
    $toEmail = $row["recipient_email"];
    $sheet_id = $row["sheet_id"];
    $toName  = "ramesh lufera";
    try {
        // VERY IMPORTANT when reusing same mail object
        $mail->clearAddresses();
        $mail->addAddress($toEmail, $toName);
        //$mail->Subject = "Reminder: Row {$row["sheet_row"]} – due soon";
        $mail->Subject = "Reminder Alert Received Before Scheduled Time";
        $mail->Body = '
            <div style="border: 1px solid #ccc; padding: 10px; align-items: center; align-content: center; border-radius:12px">
                <h2>Upcoming Reminder Alert</h2>
                <p><strong>Row:</strong> '.$row["sheet_row"].'</p>
                <p>' .nl2br(htmlspecialchars($row["message"])) . '</p>
                <p><strong>Scheduled time:</strong> 
                ' .date("d M Y H:i", strtotime($row["remind_at"])) . '</p>
                <a href="https://admin2.luferatech.com/sheets.php?id=' . $sheet_id . '" 
                   style="background:#fec700; color:#ffffff; padding:14px 32px; text-decoration:none; border-radius:8px; font-weight:500; font-size:16px; display:inline-block;">
                    View in Sheet
                </a>
                <small>Sent ~10 minutes before due time</small>';
        $mail->send();
        // Mark as notified
        $update = $conn->prepare("UPDATE sheet_reminders SET notified = 1 WHERE id = ?");
        $update->bind_param("i", $row["id"]);
        $update->execute();
        $sentCount++;
    } catch (Exception $e) {
        error_log("Reminder email failed for ID {$row["id"]}: " . $mail->ErrorInfo);
    }
}

echo "Checked at " . date("c") . " → sent $sentCount reminder emails\n";