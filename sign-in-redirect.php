<?php
    session_start();
    include './partials/connection.php';
    require_once 'vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Google Client Configuration
    require './partials/google-config.php';
    $redirectUri = rtrim($_ENV['GOOGLE_REDIRECT_URI'], '/') . '/sign-in-redirect.php';
    $client->setRedirectUri($redirectUri);

    function generateUserId() {
        $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
        $numbers = substr(str_shuffle('0123456789'), 0, 3);
        return $letters . $numbers;
    }

    $newUserId = generateUserId();
    $method = "1";

    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);
            $google_service = new Google_Service_Oauth2($client);
            $userInfo = $google_service->userinfo->get();

            $fname = $userInfo->givenName;
            $lname = $userInfo->familyName;
            $email = $userInfo->email;
            $username = explode('@', $email)[0];
            $password = '';
            $phone = '';
            $created_at = date("Y-m-d H:i:s");
            $method = "1";
            $role = "8";
            $google_photo = $userInfo->picture;
            $business_name = $address = $city = $state = $country = $pincode = $dob = null;

            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // User exists: update photo
                $update = $conn->prepare("UPDATE users SET photo = ? WHERE email = ?");
                $update->bind_param("ss", $google_photo, $email);
                $update->execute();
            } else {
                // User doesn't exist - insert new
                $insert = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name, last_name, business_name, address, city, state, country, pincode, dob, created_at, method, role, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->bind_param("ssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $google_photo);
                $insert->execute();

                // Send Welcome Email (only for NEW user)
                $login_link = rtrim($_ENV['EMAIL_COMMON_LINK'], '/') . '/sign-in.php';

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['EMAIL_USERNAME'];
                    $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
                    $mail->addAddress($email, $username);

                    $mail->isHTML(true);
                    $mail->Subject = "Welcome to Admin Dashboard!";
                    $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset="UTF-8"><title>Welcome</title></head>
                        <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                            <tr><td align="center">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                                style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                                <tr>
                                    <td style="padding:20px;text-align:center;">
                                    <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '?text=L" alt="Lufera Infotech Logo" 
                                        style="width:150px;height:48px;display:block;margin:auto;">
                                    </td>
                                </tr>
                                <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                <tr>
                                    <td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                    <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Welcome, ' . htmlspecialchars($fname) . '!</h3>
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
                                <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                <tr>
                                    <td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                    You’re receiving this email because your account has been successfully created.<br>
                                    &copy; 2025 Lufera Infotech. All rights reserved.
                                    </td>
                                </tr>
                            </table>
                            </td></tr>
                        </table>
                        </body>
                        </html>
                    ';
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Welcome email failed: {$mail->ErrorInfo}");
                }
            }

            // Get user ID and username
            $stmt1 = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt1->bind_param("s", $email);
            $stmt1->execute();
            $stmt1->bind_result($id, $username);
            $stmt1->fetch();
            $stmt1->close();

            // Store in session
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            // Redirect to dashboard
            header("Location: admin-dashboard.php");
            exit;
        } else {
            echo "Google login failed.";
        }
    }

    // If failed, redirect back to login
    header("Location: sign-in.php");
    exit;
