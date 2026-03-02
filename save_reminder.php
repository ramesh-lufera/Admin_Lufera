<?php
session_start();
ob_start();
// save_reminder.php – FINAL VERSION WITH DIRECT EMAIL NOTIFICATION
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Optional: Keep debug logging during development
file_put_contents(
    __DIR__ . '/save_reminder_debug.log',
    date('Y-m-d H:i:s') . " → Request received\n" . file_get_contents('php://input') . "\n\n",
    FILE_APPEND | LOCK_EX
);

include './partials/connection.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$loggedInUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$user_fetch = "select first_name, last_name, email from users where id = $loggedInUserId";
$user_result = $conn ->query($user_fetch);
$user_row = $user_result ->fetch_assoc();
$user_name = $user_row['first_name']. " " . $user_row['last_name'];
$user_email = $user_row['email'];

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['sheet_id']) || !isset($data['sheet_row']) || !isset($data['remind_at'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}
$recipient_email = trim($data['recipient_email'] ?? '');
// Normalize datetime: 2025-02-10T18:30 → 2025-02-10 18:30:00
// Incoming time is in IST (Asia/Kolkata = +05:30)
$remind_at_raw = $data['remind_at'];           // e.g. "2026-02-26T16:54:00"

// Make it a proper DateTime object assuming it's IST
$remind_at_obj = new DateTime($remind_at_raw, new DateTimeZone('Asia/Kolkata'));

// Convert to UTC
$remind_at_obj->setTimezone(new DateTimeZone('UTC'));

// Now format for MySQL (UTC time)
$remind_at = $remind_at_obj->format('Y-m-d H:i:s');

// Optional: still create display version in IST for the email
$remind_at_display_obj = clone $remind_at_obj;
$remind_at_display_obj->setTimezone(new DateTimeZone('Asia/Kolkata'));
$remind_at_display = $remind_at_display_obj->format('d M Y, h:i A');

// Human-readable format for display
$remind_at_display = date('d M Y, h:i A', strtotime($remind_at));

$stmt = $conn->prepare("
    INSERT INTO sheet_reminders
    (sheet_id, sheet_row, remind_at, message, recipient_email, created_at, created_by, is_read, notified)
    VALUES (?, ?, ?, ?, ?, NOW(), ?, 0, 0)
");
    if ($stmt->execute()) {
        logActivity(
            $conn,
            $loggedInUserId,
            "Sheets",                   // module
            "Reminder schedule at $remind_at"  // description
        );
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
$stmt->bind_param(
    "iisssi",
    $data['sheet_id'],
    $data['sheet_row'],
    $remind_at,
    $data['message'],
    $recipient_email,
    $loggedInUserId
);

$success = $stmt->execute();

$response = [
    'success'     => $success,
    'error'       => $success ? null : $stmt->error,
    'id'          => $success ? $conn->insert_id : null,
    'email_sent'  => false,
];

$reminder_id = $success ? (int)$conn->insert_id : 0;

$stmt->close();

// ────────────────────────────────────────────────
// Send email directly (if valid recipient exists)
// ────────────────────────────────────────────────
if ($success && $reminder_id > 0 && !empty($recipient_email) && filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {

    $sheet_id = (int)$data['sheet_id'];           // for the link
    $message_raw = (string)($data['message'] ?? '');
    $message_content = $message_raw !== '' ? nl2br(htmlspecialchars($message_raw)) : 'No additional message provided.';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['EMAIL_USERNAME'];
        $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
        $mail->addAddress($recipient_email);

        $mail->isHTML(true);
        $mail->Subject = "Reminder Created – Action Required";

        // Fixed HTML email body (corrected $sheetId → $sheet_id)
        $mail->Body = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reminder Notification</title>
        </head>
        <body>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="padding: 20px 0;">
                        <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background:#ffffff; border-radius:12px; overflow:hidden; border: 1px solid #ccc;">
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2 style="margin-top:0; color:#1f2937; font-size:22px;">Hello,</h2>
                                    <p style="font-size:16px; line-height:1.6; margin:0 0 24px;">A new reminder has been created for you in the system.</p>
                                    
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#f8fafc; border-radius:8px; padding:20px; margin:24px 0;">
                                        <tr>
                                            <td style="font-weight:bold; width:140px; padding-bottom:12px;">Date & Time:</td>
                                            <td style="padding-bottom:12px;">' . htmlspecialchars($remind_at_display) . '</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight:bold; width:140px; padding-bottom:12px; vertical-align:top;">Message:</td>
                                            <td style="padding-bottom:12px;">' . $message_content . '</td>
                                        </tr>
                                    </table>

                                    <p style="font-size:16px; line-height:1.6; margin:24px 0 0;">
                                        Please take the necessary action at the scheduled time.<br>
                                        You can view this reminder in your sheet.
                                    </p>
                                    
                                    <p style="font-size:16px; line-height:1.6; margin:24px 0 0;">
                                        <b>Organizer</b><br>
                                        ' . $user_name . '<br>
                                        ' . $user_email . '
                                    </p>

                                    <div style="margin: 32px 0; text-align:center;">
                                        <a href="https://admin2.luferatech.com/sheets.php?id=' . $sheet_id . '" 
                                           style="background:#fec700; color:#ffffff; padding:14px 32px; text-decoration:none; border-radius:8px; font-weight:500; font-size:16px; display:inline-block;">
                                            View in Sheet
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';

        $mail->AltBody = "Reminder Scheduled\n\n"
                       . "Date & Time: $remind_at_display\n"
                       . "Message:\n" . ($message_raw ?: 'No message') . "\n\n"
                       . "View here: https://admin2.luferatech.com/sheets.php?id=$sheet_id\n"
                       . "This is an automated reminder from Lufera Infotech.";

        $mail->send();

        // Mark as notified
        //$conn->query("UPDATE sheet_reminders SET notified = 1 WHERE id = $reminder_id");

        $response['email_sent'] = true;

    } catch (Exception $e) {
        error_log("Reminder email failed (id=$reminder_id) → " . $mail->ErrorInfo);
        $response['email_error'] = $mail->ErrorInfo; // optional – for debugging
        // Do NOT fail the whole request — reminder is still saved
    }
}

ob_end_clean();
echo json_encode($response);
exit;