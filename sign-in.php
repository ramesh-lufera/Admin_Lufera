<?php
    session_start();

    require_once 'vendor/autoload.php';
     include './partials/connection.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Google Client Configuration
    require './partials/google-config.php';
    $redirectUri = rtrim($_ENV['GOOGLE_REDIRECT_URI'], '/') . '/sign-in-redirect.php';
    $client->setRedirectUri($redirectUri);

    // Force account selection every time
    $client->setPrompt('select_account');

    $loginUrl = $client->createAuthUrl();
?>

<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<style>
    .border-danger {
        border: 1px solid red !important;
    }
    .toggle-password{
        inset-inline-start: auto !important;
    }
    
</style>
<?php include './partials/head.php' ?>
<body>

    <section class="auth bg-base d-flex flex-wrap">
        <div class="auth-left d-lg-block d-none">
            <div class="d-flex align-items-center flex-column h-100 justify-content-center">
                <img src="assets/images/signin-page.jpg" alt="" class="sign-in-img">
            </div>
        </div>
        <div class="auth-right py-32 px-24 d-flex flex-column justify-content-center">
            <div class="max-w-464-px mx-auto w-100">
                <div>
                    <a href="index.php" class="mb-40 max-w-290-px">
                        <img src="assets/images/logo_lufera.png" alt="" width="200px">
                    </a>
                    <h4 class="mb-12">Sign In</h4>
                    <p class="mb-32 text-secondary-light text-lg">Welcome back! Enter your details</p>
                </div>
                <form id="login-form">
                    <div class="icon-field mb-16">
                        <span class="icon translate-middle-y">
                            <iconify-icon icon="mage:email"></iconify-icon>
                        </span>
                        <input id="email" type="email" class="form-control h-56-px bg-neutral-50 radius-12" placeholder="Email" name="email" required>
                        <div id="email-error" class="error text-danger mt-1"></div>

                    </div>
                    <div class="position-relative">
                        <div class="icon-field">
                            <span class="icon translate-middle-y">
                                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                            </span>

                            <input id="password" type="password" class="form-control h-56-px bg-neutral-50 radius-12" placeholder="Password" name="password" required>
                            <div id="password-error" class="error text-danger mt-1"></div>
                            <span class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 translate-middle-y me-16 text-secondary-light icon" data-toggle="#password"></span>
                        </div>
                        
                    </div>
                    <?php if (!empty($error_pass)) : ?>
                         <div class="error text-danger"><?= $error_pass ?></div>
                     <?php endif; ?> 
                    <div class="mt-20">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="form-check style-check d-flex align-items-center">
                                <input class="form-check-input border border-neutral-300" type="checkbox" value="" id="remeber">
                                <label class="form-check-label" for="remeber">Remember me </label>
                            </div>
                            <!-- <a href="javascript:void(0)" class="text-warning-600 fw-medium">Forgot Password?</a> -->
                        </div>
                    </div>

                    <button type="submit" class="btn lufera-bg text-white text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32" name="login"> Sign In</button>
                    
                    <div class="mt-32 center-border-horizontal text-center">
                        <span class="bg-base z-1 px-4">Or sign in with</span>
                    </div>
                    <div class="mt-32 d-flex align-items-center gap-3">
                        <button type="button" class="fw-semibold text-primary-light py-16 px-24 w-50 border radius-12 text-md d-flex align-items-center justify-content-center gap-12 line-height-1 bg-hover-primary-50">
                            <iconify-icon icon="ic:baseline-facebook" class="text-primary-600 text-xl line-height-1"></iconify-icon>
                            Facebook
                        </button>
                        <a href="<?= $loginUrl ?>" style="display:contents;">
                        <button type="button" class="fw-semibold text-primary-light py-16 px-24 w-50 border radius-12 text-md d-flex align-items-center justify-content-center gap-12 line-height-1 bg-hover-primary-50">
                            <iconify-icon icon="logos:google-icon" class="text-primary-600 text-xl line-height-1"></iconify-icon>
                            Google
                        </button>
                        </a>
                    </div>
                    <div class="mt-32 text-center text-sm">
                        <p class="mb-0">Don’t have an account? <a href="sign-up.php" class="text-warning-600 fw-semibold">Sign Up</a></p>
                    </div>

                </form>
                <div id="login-error" class="error text-danger mt-3"></div>

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

<script>
    document.getElementById("login-form").addEventListener("submit", function (e) {
        e.preventDefault();

        // Clear previous errors
        ["email", "password"].forEach(field => {
            document.getElementById(`${field}-error`).innerText = '';
            document.getElementById(field).classList.remove("border-danger");
        });

        const form = e.target;
        const formData = new FormData(form);

        fetch("login-process.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = "admin-dashboard.php";
            } else {
                const errors = data.errors || {};

                if (errors.email) {
                    document.getElementById("email-error").innerText = errors.email;
                    document.getElementById("email").classList.add("border-danger");
                }

                if (errors.password) {
                    document.getElementById("password-error").innerText = errors.password;
                    document.getElementById("password").classList.add("border-danger");
                }
            }
        })
        .catch(error => console.error("Error:", error));
    });
</script>

</body>

</html>

