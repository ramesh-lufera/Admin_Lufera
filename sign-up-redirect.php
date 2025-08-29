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
    $redirectUri = rtrim($_ENV['GOOGLE_REDIRECT_URI'], '/') . '/sign-up-redirect.php';
    $client->setRedirectUri($redirectUri);

    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        // Check for token error
        if (isset($token['error'])) {
            echo "Authentication error: " . htmlspecialchars($token['error_description']);
            exit;
        }
        
        $client->setAccessToken($token);

        $google_service = new Google_Service_Oauth2($client);
        $google_user = $google_service->userinfo->get();

        $fname = $google_user->givenName;
        $lname = $google_user->familyName;
        $email = $google_user->email;
        $username = explode('@', $email)[0];
        $password = '';
        $phone = '';
        $created_at = date("Y-m-d H:i:s");
        $method = "1";
        $role = "8";
        $photo = $google_user->picture;
        $business_name = $address = $city = $state = $country = $pincode = $dob = null;

        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // User exists
            $stmt->bind_result($id, $username);
            $stmt->fetch();

            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            // Update photo on each login
            $update = $conn->prepare("UPDATE users SET photo = ? WHERE email = ?");
            $update->bind_param("ss", $photo, $email);
            $update->execute();
            $update->close();
        } else {
            // User doesn't exist - insert new
            function generateUserId() {
                $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
                $numbers = substr(str_shuffle('0123456789'), 0, 3);
                return $letters . $numbers;
            }
            
            $newUserId = generateUserId();

            $insert = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name, last_name, business_name, address, city, state, country, pincode, dob, created_at, method, role, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param("ssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $photo);
            $insert->execute();

            $_SESSION['user_id'] = $insert->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            // ================= WELCOME EMAIL =================
            $signup_link = rtrim($_ENV['EMAIL_COMMON_LINK'], '/') . '/sign-up.php';

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
                $mail->addAddress($email, $fname . ' ' . $lname);

                $mail->isHTML(true);
                $mail->Subject = "Welcome to Admin Dashboard!";
                // $mail->Body = '
                //     <!DOCTYPE html>
                //     <html>
                //     <head><meta charset="UTF-8"><title>Welcome</title></head>
                //     <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                //     <table width="100%" style="background:#f5f5f5;padding:30px 0;">
                //     <tr><td align="center">
                //     <table width="600" style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
                //     <tr><td style="padding:20px;text-align:center;">
                //     <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '?text=L" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                //     </td></tr>
                //     <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                //     <tr><td style="padding:30px 40px;font-size:15px;line-height:1.6;color:#101010;">
                //     <h3>Welcome, ' . htmlspecialchars($fname) . '!</h3>
                //     <p>We’re excited to have you on board at <b>Admin Dashboard</b>.</p>
                //     <div style="margin:30px 0;text-align:center;">
                //         <a href="' . htmlspecialchars($login_link) . '" style="background:#fec700;color:#101010;text-decoration:none;padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">Go to Login</a>
                //     </div>
                //     <p>If you have any questions, feel free to reply to this email.</p>
                //     </td></tr>
                //     <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                //     <tr><td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                //     You’re receiving this email because your account has been successfully verified.<br>
                //     &copy; 2025 Lufera Infotech. All rights reserved.
                //     </td></tr>
                //     </table></td></tr></table>
                //     </body></html>
                // ';

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
                                    <a href="' . htmlspecialchars($signup_link) . '" 
                                    style="background:#fec700;color:#101010;text-decoration:none;
                                            padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">
                                    Go to Sign Up
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
            // ================= END WELCOME EMAIL =================

            $insert->close();
        }

        $stmt->close();
        header('Location: admin-dashboard.php');
        exit;
    } else {
        echo "Google login failed.";
    }
?>
