<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <title>Sign Up</title>
    <style>
        .error-border {
            border: 1px solid red !important;
        }
        .error {
            color: red;
            font-size: 13px;
        }
        .success {
            color: green;
            /* font-weight: bold; */
            margin-bottom: 10px;
        }
    </style> 
</head>

<?php 
    session_start();
    include './partials/head.php';
    include './partials/connection.php';
    include 'fb-config.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $username = $email = $password = $fname = $phone = "" ;
    $errors = [];
    $success = "";

    // Show success message after verification redirect
    if (isset($_SESSION['success_message'])) {
        $success = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $fname = trim($_POST['fname']);
        $phone = trim($_POST['phone']);
        $created_at = date("Y-m-d H:i:s");
        $method = "1";
        $role = "8";
        $token    = bin2hex(random_bytes(32)); // secure token
        $is_verified = 0;

        $lname = $business_name = $address = $city = $state = $country = $pincode = $dob = null;

        // Validation
        if (empty($username)) {
            $errors['username'] = "Username is required";
        }
        if (empty($fname)) {
            $errors['fname'] = "Name is required";
        }
        if (empty($phone)) {
            $errors['phone'] = "Phone is required";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Valid email is required";
        } else {
            $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $checkEmail->store_result();
            if ($checkEmail->num_rows > 0) {
                $errors['email'] = "Email already exists";
            }
            $checkEmail->close();
        }
        if (empty($password)) {
            $errors['password'] = "Password is required";
        }
        if (empty($_POST['checkbox'])) {
            $errors['checkbox'] = 'Agree to the Terms & Conditions and our Privacy Policy';
        }

        // Insert if no errors
        // If no errors -> save pending signup and send email
        if (empty($errors)) {
            $_SESSION['pending_user'] = [
                'username'   => $username,
                'email'      => $email,
                'phone'      => $phone,
                'password'   => $password,
                'first_name' => $fname,
                'created_at' => $created_at,
                'method'     => $method,
                'role'       => $role,
                'token'      => $token
            ];

            $verify_link = $_ENV['EMAIL_COMMON_LINK'] . "/sign-up_verify.php?token=" . $token;

            // ============== SEND EMAIL =================
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
                $mail->Subject = "Verify your Admin Dashboard Account";

                $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                    <meta charset="UTF-8">
                    <title>Email Verification</title>
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
                                <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                                </td>
                            </tr>

                            <!-- Divider -->
                            <tr>
                                <td style="border-top:1px solid #eaeaea;"></td>
                            </tr>

                            <!-- Main Content -->
                            <tr>
                                <td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Verify your email address</h3>
                                <p>Hello <b>' . htmlspecialchars($username) . '</b>,</p>
                                <p>You recently created an account at <b>Admin Dashboard</b>. Please verify your email address to activate your account.</p>
                                <div style="margin:30px 0;text-align:center;">
                                    <a href="' . $verify_link . '" 
                                    style="background:#fec700;color:#101010;text-decoration:none;
                                            padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">
                                    Verify Email
                                    </a>
                                </div>
                                <p>If you didn\'t sign up, you can safely ignore this email.</p>
                                </td>
                            </tr>

                            <!-- Divider -->
                            <tr>
                                <td style="border-top:1px solid #eaeaea;"></td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                You’re receiving this email to verify your Admin Dashboard account.<br>
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
                $success = "Check your email to verify your account.";
            } catch (Exception $e) {
                $errors['mail'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

            // function generateUserId() {
            //     $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
            //     $numbers = substr(str_shuffle('0123456789'), 0, 3);
            //     return $letters . $numbers;
            // }
            
            // $newUserId = generateUserId();
            
            // $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name,last_name,business_name,address,city,state,country,pincode,dob,created_at,method,role,photo ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            // $stmt->bind_param("ssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $photo);
            
            // if ($stmt->execute()) {
            //     $_SESSION['user_id'] = $stmt->insert_id;
            //     $success = "Registration successful!";
            //     $username = $email = $password = $fname = $phone = "" ; // clear inputs
            //     unset($_POST['checkbox']); //
            // } else {
            //     $errors['general'] = "Error while registering user.";
            // }

            // $stmt->close();
        }
    }

    // Google Client Configuration
    require './partials/google-config.php';
    $redirectUri = rtrim($_ENV['GOOGLE_REDIRECT_URI'], '/') . '/sign-up-redirect.php';
    $client->setRedirectUri($redirectUri);

    // Force account selection every time
    $client->setPrompt('select_account');

    $loginUrl = $client->createAuthUrl();
    
    $company_sql = "SELECT * FROM company";
    $company_result = $conn->query($company_sql);
    $company_row = $company_result->fetch_assoc();
    $logo = $company_row['logo'];
    $sign_up_img = $company_row['sign_up_img'];
?>

<body>
    <section class="auth bg-base d-flex flex-wrap">
        <div class="auth-left d-lg-block d-none">
            <div class="d-flex align-items-center flex-column h-100 justify-content-center sign-up-img">
                <!-- <img src="assets/images/auth/auth-img.png" alt=""> -->
                <!-- <img src="assets/images/signin-page.jpg" alt="" class="sign-in-img"> -->
                <?php if($sign_up_img != NULL) { ?>
                    <img src="uploads/company_logo/<?php echo $sign_up_img; ?>" alt="" class="sign-in-img">
                <?php } else { ?>       
                    <img src="assets/images/1.jpg" alt="" class="sign-in-img">
                <?php } ?>
            </div>
        </div>
        <div class="auth-right py-32 px-24 d-flex flex-column justify-content-center">
            <div class="max-w-464-px mx-auto w-100 m-auto">
                <div>
                    <a href="sign-up.php" class="mb-40 max-w-290-px">
                        <!-- <img src="assets/images/logo.png" alt=""> -->
                        <!-- <img src="assets/images/logo_lufera.png" alt="" width="200px"> -->
                        <img src="uploads/company_logo/<?php echo $logo; ?>" alt="site logo" class="light-logo" width="200px">
                    </a>
                    <h4 class="mb-12">Sign Up</h4>
                    <p class="mb-32 text-secondary-light text-lg">Welcome back! Enter your details</p>
                </div>

                <!-- <?php if ($success): ?>
                    <p class="success"><?= $success ?></p>
                <?php endif; ?> -->

                <?php if ($success): ?>
                    <p class="success"><?= $success ?></p>
                <?php endif; ?>

                <form method="post" action="" id="registerForm">
                    
                    <div class="icon-field mb-16">
                        <span class="icon translate-middle-y">
                            <iconify-icon icon="f7:person"></iconify-icon>
                        </span>
                        <input type="text" class="form-control h-56-px bg-neutral-50 radius-12 <?= isset($errors['fname']) ? 'error-border' : '' ?>" placeholder="Name" name="fname" value="<?= htmlspecialchars($fname) ?>">
                        <?php if (isset($errors['fname'])): ?>
                            <div class="error"><?= $errors['fname'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="icon-field mb-16">
                        <span class="icon translate-middle-y">
                            <iconify-icon icon="mage:email"></iconify-icon>
                        </span>
                        <input type="email" class="form-control h-56-px bg-neutral-50 radius-12 <?= isset($errors['email']) ? 'error-border' : '' ?>" placeholder="Email" name="email" id="email" value="<?= htmlspecialchars($email) ?>">
                        <?php if (isset($errors['email'])): ?>
                            <div class="error"><?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="icon-field mb-16">
                        <span class="icon translate-middle-y">
                            <iconify-icon icon="f7:phone"></iconify-icon>
                        </span>
                        <input type="text" class="form-control h-56-px bg-neutral-50 radius-12 <?= isset($errors['phone']) ? 'error-border' : '' ?>" placeholder="Phone No" name="phone" value="<?= htmlspecialchars($phone) ?>">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="error"><?= $errors['phone'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="icon-field mb-16">
                        <span class="icon translate-middle-y">
                            <iconify-icon icon="mage:edit"></iconify-icon>
                        </span>
                        <input type="text" class="form-control h-56-px bg-neutral-50 radius-12 <?= isset($errors['username']) ? 'error-border' : '' ?>" placeholder="Username" name="username" id="uname" value="<?= htmlspecialchars($username) ?>" readonly>
                        <?php if (isset($errors['username'])): ?>
                            <div class="error"><?= $errors['username'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-20">
                        <div class="position-relative ">
                            <div class="icon-field">
                                <span class="icon translate-middle-y">
                                    <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                                </span>
                                <input type="password" class="form-control h-56-px bg-neutral-50 radius-12 <?= isset($errors['password']) ? 'error-border' : '' ?>" id="your-password" placeholder="Password" name="password" value="<?= htmlspecialchars($password) ?>" autocomplete="off" >
                                <?php if (isset($errors['password'])): ?>
                                    <div class="error"><?= $errors['password'] ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light" data-toggle="#your-password"></span>
                        </div>
                        <!-- <span class="mt-12 text-sm text-secondary-light">Your password must have at least 8 characters</span> -->
                    </div>

                    <div>
                        <div class="d-flex justify-content-between gap-2">
                            <div class="form-check style-check d-flex align-items-start">
                                <input class="form-check-input border border-neutral-300 mt-4" type="checkbox" name="checkbox" id="condition" <?= isset($_POST['checkbox']) ? 'checked' : '' ?>>
                                <label class="form-check-label text-sm" for="condition">
                                    By creating an account means you agree to the
                                    <a href="javascript:void(0)" class="text-warning-600 fw-semibold">Terms & Conditions</a> and our
                                    <a href="javascript:void(0)" class="text-warning-600 fw-semibold">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($errors['checkbox'])): ?>
                        <div class="error"><?= $errors['checkbox'] ?></div>
                    <?php endif; ?>

                    <button type="submit" class="btn lufera-bg text-white text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32" id="add-category-modal"> Sign Up</button>

                    <div class="mt-32 center-border-horizontal text-center">
                        <span class="bg-base z-1 px-4">Or sign up with</span>
                    </div>
                    <div class="mt-32 d-flex align-items-center gap-3">
                        <a href="<?php echo $loginUrl1; ?>" style="display:contents;">
                        <button type="button" class="fw-semibold text-primary-light py-16 px-24 w-50 border radius-12 text-md d-flex align-items-center justify-content-center gap-12 line-height-1 bg-hover-primary-50">
                            <iconify-icon icon="ic:baseline-facebook" class="text-primary-600 text-xl line-height-1"></iconify-icon>
                            Facebook
                        </button>
                        </a>
                        <a href="<?php echo $loginUrl; ?>" style="display:contents;">
                        <button type="button" class="fw-semibold text-primary-light py-16 px-24 w-50 border radius-12 text-md d-flex align-items-center justify-content-center gap-12 line-height-1 bg-hover-primary-50">
                            <iconify-icon icon="logos:google-icon" class="text-primary-600 text-xl line-height-1"></iconify-icon>
                            Google
                        </button>
                        </a>
                    </div>
                    <div class="mt-32 text-center text-sm">
                        <p class="mb-0">Already have an account? <a href="sign-in.php" class="text-warning-600 fw-semibold">Sign In</a></p>
                    </div>

                </form>
            </div>
        </div>
    </section>

    <?php $script = '<script>
                        // ================== Password Show Hide Js Start ==========
                        function initializePasswordToggle(toggleSelector) {
                            $(toggleSelector).on("click", function() {
                                $(this).toggleClass("ri-eye-off-line");
                                var input = $($(this).attr("data-toggle"));
                                if (input.attr("type") === "password") {
                                    input.attr("type", "text");
                                } else {
                                    input.attr("type", "password");
                                }
                            });
                        }
                        // Call the function
                        initializePasswordToggle(".toggle-password");
                        // ========================= Password Show Hide Js End ===========================
                    </script>';?>

    <?php include './partials/scripts.php' ?>

</body>

<script>
    document.getElementById('email').addEventListener('input', function() {
        const email = this.value;
        const usernameField = document.getElementById('uname');
        const atIndex = email.indexOf('@');
        if (atIndex > 0) {
            usernameField.value = email.substring(0, atIndex);
        }
    });
</script>

</html>