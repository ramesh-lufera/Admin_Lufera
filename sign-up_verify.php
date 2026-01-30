<?php
session_start();
include './partials/head.php';
include './partials/connection.php';
include './log.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (isset($_GET['token']) && isset($_SESSION['pending_user'])) {
    $token   = $_GET['token'];
    $pending = $_SESSION['pending_user'];

    if ($token === $pending['token']) {
        // Generate unique user_id
        function generateUserId() {
            $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
            $numbers = substr(str_shuffle('0123456789'), 0, 3);
            return $letters . $numbers;
        }
        $newUserId = generateUserId();

        $lname = $business_name = $address = $city = $state = $country = $pincode = $dob = $photo = null;
        $is_verified = 1;

        $stmt = $conn->prepare("INSERT INTO users 
        (user_id, username, email, phone, password, first_name,last_name,business_name,address,city,state,country,pincode,dob,created_at,method,role,photo,is_verified) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssssssssssssssi", 
            $newUserId, 
            $pending['username'], 
            $pending['email'], 
            $pending['phone'], 
            $pending['password'], 
            $pending['first_name'], 
            $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, 
            $pending['created_at'], $pending['method'], $pending['role'], $photo, $is_verified);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id; // ✅ Required for correct user_id

            // 🔥 LOG ACTIVITY HERE
            logActivity(
                $conn,
                $user_id,
                "sign-up",        // module
                "User Registration",     // action
                "User verified email and completed registration successfully" // description
            );

            unset($_SESSION['pending_user']); // clear pending session

            $login_link = rtrim($_ENV['EMAIL_COMMON_LINK'], '/') . '/sign-in.php';

            // ================= WELCOME EMAIL =================
            $mail = new PHPMailer(true);
            try {
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
                $mail->addAddress($pending['email'], $pending['username']);
                $mail->isHTML(true);
                $mail->Subject = "Welcome to Admin Dashboard!";
                $mail->ContentType = 'text/html; charset=UTF-8';
                
                $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                    <meta charset="UTF-8">
                    <title>Welcome</title>
                    </head>
                    <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                        <tr>
                        <td align="center">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                                style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                            
                            <!-- Header -->
                            <tr>
                                <td style="padding:20px;text-align:center;">
                                <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '?text=L" alt="Lufera Infotech Logo" 
                                    style="width:150px;height:48px;display:block;margin:auto;">
                                </td>
                            </tr>

                            <!-- Divider -->
                            <tr>
                                <td style="border-top:1px solid #eaeaea;"></td>
                            </tr>

                            <!-- Main Content -->
                            <tr>
                                <td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Welcome, ' . htmlspecialchars($pending['first_name']) . '!</h3>
                                <p>We’re excited to have you on board at <b>Admin Dashboard</b>.</p>
                                <p>Your account has been successfully created and verified. You can now log in and start exploring our platform.</p>
                                <div style="margin:30px 0;text-align:center;">
                                    <a href="' . htmlspecialchars($login_link) . '" 
                                    style="background:#fec700;color:#101010;text-decoration:none;
                                            padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">
                                    Go to Login
                                    </a>
                                </div>
                                <p>If you have any questions, feel free to reply to this email.</p>
                                </td>
                            </tr>

                            <!-- Divider -->
                            <tr>
                                <td style="border-top:1px solid #eaeaea;"></td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                You’re receiving this email because your account has been successfully verified.<br>
                                &copy; 2025 Lufera Infotech. All rights reserved.
                                </td>
                            </tr>

                            </table>
                        </td>
                        </tr>
                    </table>
                    </body>
                    </html>
                ';
                $mail->send();
            } catch (Exception $e) {
                error_log("Welcome email failed: {$mail->ErrorInfo}");
            }
            // ================= END WELCOME EMAIL =================

            $_SESSION['user_id'] = $stmt->insert_id;

            $_SESSION['success_message'] = "Registration successful!";

            $username = $email = $password = $fname = $phone = "" ; // clear inputs
            unset($_POST['checkbox']); //

            header("Location: sign-up.php"); // ✅ always redirect safely
            exit();
        } else {
            $_SESSION['success_message'] = "Not verified, no sign-up!";
            header("Location: sign-up.php");
            exit();
        }
    } else {
        $_SESSION['success_message'] = "Invalid verification link!";
        header("Location: sign-up.php");
        exit();
    }
} else {
    $_SESSION['success_message'] = "No verification data found!";
    header("Location: sign-up.php");
    exit();
}
