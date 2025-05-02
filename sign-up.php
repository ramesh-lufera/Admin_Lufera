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
    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

      $username = $email = $password = $fname = $phone = "" ;
      $errors = [];
      $success = "";
        
      // Pre-fill values from session if NOT a POST request
      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        if (isset($_SESSION['google_name'])) {
            $username = $_SESSION['google_name'];
        }
        if (isset($_SESSION['google_email'])) {
            $email = $_SESSION['google_email'];
        }
        if (isset($_SESSION['google_photo'])) {
            $photo = $_SESSION['google_photo'];
        }
        if (isset($_SESSION['google_password'])) {
            $password = $_SESSION['google_password']; // optional, usually not set
        }
    }



      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $fname = trim($_POST['fname']);
        $phone = trim($_POST['phone']);
        $created_at = date("Y-m-d H:i:s");
        $lname = $business_name = $address = $city = $state = $country = $pincode = $dob = null;
        $method = "1";
        $role = "user";

        // Validation
        if (empty($username)) {
            $errors['username'] = "Username is required";
        }
        if (empty($fname)) {
            $errors['fname'] = "Name is required";
        }
        if (empty($phone)) {
            $errors['phone'] = "Phone no is required";
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
            $errors['checkbox'] = 'Tick this box to continue';
        }
        // Insert if no errors
        if (empty($errors)) {
            // Generate new user_id
            // $result = $conn->query("SELECT user_id FROM users ORDER BY id DESC LIMIT 1");
            // $lastId = "LI000";
            // if ($result && $row = $result->fetch_assoc()) {
            //     $lastId = $row['user_id'];
            // }
    
            // $num = (int)substr($lastId, 2) + 1;
            // $newUserId = 'LI' . str_pad($num, 3, '0', STR_PAD_LEFT);
    
            // $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            function generateUserId() {
                $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
                $numbers = substr(str_shuffle('0123456789'), 0, 3);
                return $letters . $numbers;
            }
            
            $newUserId = generateUserId();
            
            $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name,last_name,business_name,address,city,state,country,pincode,dob,created_at,method,role,photo,google_photo ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $photo, $google_photo);
            
            if ($stmt->execute()) {
                $success = "Registration successful!";
                $username = $email = $password = $fname = $phone = "" ; // clear inputs
            } else {
                $errors['general'] = "Error while registering user.";
            }
    
            $stmt->close();
        }
    }

   // Google Client Setup (to create sign-in URL)
    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri('http://localhost/Lufera-Infotech/sign-up-redirect.php');
    // $client->setRedirectUri('https://admin.luferatech.com/sign-up-redirect.php');
    $client->addScope('email');
    $client->addScope('profile');
    
    $loginUrl = $client->createAuthUrl(); 
?>

<body>

    <section class="auth bg-base d-flex flex-wrap">
        <div class="auth-left d-lg-block d-none">
            <div class="d-flex align-items-center flex-column h-100 justify-content-center sign-up-img">
                <!-- <img src="assets/images/auth/auth-img.png" alt=""> -->
                <!-- <img src="assets/images/signin-page.jpg" alt="" class="sign-in-img"> -->
            </div>
        </div>
        <div class="auth-right py-32 px-24 d-flex flex-column justify-content-center">
            <div class="max-w-464-px mx-auto w-100 m-auto">
                <div>
                    <a href="sign-up.php" class="mb-40 max-w-290-px">
                        <!-- <img src="assets/images/logo.png" alt=""> -->
                        <img src="assets/images/logo_lufera.png" alt="" width="200px">
                    </a>
                    <h4 class="mb-12">Sign Up</h4>
                    <p class="mb-32 text-secondary-light text-lg">Welcome back! Enter your details</p>
                </div>

                <?php if ($success): ?>
                    <p class="success"><?= $success ?></p>
                <?php endif; ?>

                <form method="post" action="" id="registerForm">
                    
                    <div class="icon-field mb-16">
                        <span class="icon translate-middle-y">
                            <iconify-icon icon="mage:edit"></iconify-icon>
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
                        <input type="email" class="form-control h-56-px bg-neutral-50 radius-12 <?= isset($errors['email']) ? 'error-border' : '' ?>" placeholder="Email" name="email" value="<?= htmlspecialchars($email) ?>">
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
                            <iconify-icon icon="f7:person"></iconify-icon>
                        </span>
                        <input type="text" class="form-control h-56-px bg-neutral-50 radius-12 <?= isset($errors['username']) ? 'error-border' : '' ?>" placeholder="Username" name="username" value="<?= htmlspecialchars($username) ?>">
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
                                <input type="password" class="form-control h-56-px bg-neutral-50 radius-12 <?= isset($errors['password']) ? 'error-border' : '' ?>" id="your-password" placeholder="Password" name="password">
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

                    <button type="submit" class="btn lufera-bg text-white text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32"> Sign Up</button>

                    <div class="mt-32 center-border-horizontal text-center">
                        <span class="bg-base z-1 px-4">Or sign up with</span>
                    </div>
                    <div class="mt-32 d-flex align-items-center gap-3">
                        <button type="button" class="fw-semibold text-primary-light py-16 px-24 w-50 border radius-12 text-md d-flex align-items-center justify-content-center gap-12 line-height-1 bg-hover-primary-50">
                            <iconify-icon icon="ic:baseline-facebook" class="text-primary-600 text-xl line-height-1"></iconify-icon>
                            Facebook
                        </button>
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

</html>