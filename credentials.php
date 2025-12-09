<?php include './partials/layouts/layoutTop.php' ?>

<?php
    $envPath = __DIR__ . '/.env';
    $clientId = '';
    $clientSecret = '';
    $redirectUri = '';
    $emailUsername = '';
    $gmailAppPassword = '';
    $emailCommonLink = '';
    $emailImageLink = '';
    $paypalClientId = '';

    // Load existing values from .env
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'GOOGLE_CLIENT_ID=') === 0) {
                $clientId = trim(explode('=', $line, 2)[1], "\"");
            } elseif (strpos($line, 'GOOGLE_CLIENT_SECRET=') === 0) {
                $clientSecret = trim(explode('=', $line, 2)[1], "\"");
            } elseif (strpos($line, 'GOOGLE_REDIRECT_URI=') === 0) {
                $redirectUri = trim(explode('=', $line, 2)[1], "\"");
            } elseif (strpos($line, 'EMAIL_USERNAME=') === 0) {
                $emailUsername = trim(explode('=', $line, 2)[1], "\"");
            } elseif (strpos($line, 'GMAIL_APP_PASSWORD=') === 0) {
                $gmailAppPassword = trim(explode('=', $line, 2)[1], "\"");
            } elseif (strpos($line, 'EMAIL_COMMON_LINK=') === 0) {
                $emailCommonLink = trim(explode('=', $line, 2)[1], "\"");
            } elseif (strpos($line, 'EMAIL_IMAGE_LINK=') === 0) {
                $emailImageLink = trim(explode('=', $line, 2)[1], "\"");
            } elseif (strpos($line, 'PAYPAL_CLIENT_ID=') === 0) {
                $paypalClientId = trim(explode('=', $line, 2)[1], "\"");
            }
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newClientId = trim($_POST['google_client_id']);
        $newClientSecret = trim($_POST['google_client_secret']);
        $newRedirectUri = trim($_POST['google_redirect_uri']);
        $newEmailUsername = trim($_POST['email_username']);
        $newGmailAppPassword = trim($_POST['gmail_app_password']);
        $newEmailCommonLink = trim($_POST['email_common_link']);
        $newEmailImageLink = trim($_POST['email_image_link']);
        $newPaypalClientId = trim($_POST['paypal_client_id']);

        $updatedLines = [];
        $foundClientId = $foundClientSecret = $foundRedirectUri = false;
        $foundEmailUsername = $foundGmailAppPassword = false;
        $foundEmailCommonLink = $foundEmailImageLink = false;
        $foundPaypalClientId = false;

        // Update .env values while preserving others
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, 'GOOGLE_CLIENT_ID=') === 0) {
                    $updatedLines[] = 'GOOGLE_CLIENT_ID="' . $newClientId . '"';
                    $foundClientId = true;
                } elseif (strpos($line, 'GOOGLE_CLIENT_SECRET=') === 0) {
                    $updatedLines[] = 'GOOGLE_CLIENT_SECRET="' . $newClientSecret . '"';
                    $foundClientSecret = true;
                } elseif (strpos($line, 'GOOGLE_REDIRECT_URI=') === 0) {
                    $updatedLines[] = 'GOOGLE_REDIRECT_URI="' . $newRedirectUri . '"';
                    $foundRedirectUri = true;
                } elseif (strpos($line, 'EMAIL_USERNAME=') === 0) {
                    $updatedLines[] = 'EMAIL_USERNAME="' . $newEmailUsername . '"';
                    $foundEmailUsername = true;
                } elseif (strpos($line, 'GMAIL_APP_PASSWORD=') === 0) {
                    $updatedLines[] = 'GMAIL_APP_PASSWORD="' . $newGmailAppPassword . '"';
                    $foundGmailAppPassword = true;
                } elseif (strpos($line, 'EMAIL_COMMON_LINK=') === 0) {
                    $updatedLines[] = 'EMAIL_COMMON_LINK="' . $newEmailCommonLink . '"';
                    $foundEmailCommonLink = true;
                } elseif (strpos($line, 'EMAIL_IMAGE_LINK=') === 0) {
                    $updatedLines[] = 'EMAIL_IMAGE_LINK="' . $newEmailImageLink . '"';
                    $foundEmailImageLink = true;
                } elseif (strpos($line, 'PAYPAL_CLIENT_ID=') === 0) {
                    $updatedLines[] = 'PAYPAL_CLIENT_ID="' . $newPaypalClientId . '"';
                    $foundPaypalClientId = true;
                } else {
                    $updatedLines[] = $line;
                }
            }
        }

        if (!$foundClientId) {
            $updatedLines[] = 'GOOGLE_CLIENT_ID="' . $newClientId . '"';
        }
        if (!$foundClientSecret) {
            $updatedLines[] = 'GOOGLE_CLIENT_SECRET="' . $newClientSecret . '"';
        }
        if (!$foundRedirectUri) {
            $updatedLines[] = 'GOOGLE_REDIRECT_URI="' . $newRedirectUri . '"';
        }
        if (!$foundEmailUsername) {
            $updatedLines[] = 'EMAIL_USERNAME="' . $newEmailUsername . '"';
        }
        if (!$foundGmailAppPassword) {
            $updatedLines[] = 'GMAIL_APP_PASSWORD="' . $newGmailAppPassword . '"';
        }
        if (!$foundEmailCommonLink) {
            $updatedLines[] = 'EMAIL_COMMON_LINK="' . $newEmailCommonLink . '"';
        }
        if (!$foundEmailImageLink) {
            $updatedLines[] = 'EMAIL_IMAGE_LINK="' . $newEmailImageLink . '"';
        }
        if (!$foundPaypalClientId) {
            $updatedLines[] = 'PAYPAL_CLIENT_ID="' . $newPaypalClientId . '"';
        }

        file_put_contents($envPath, implode("\n", $updatedLines) . "\n");

        // Update displayed values
        $clientId = $newClientId;
        $clientSecret = $newClientSecret;
        $redirectUri = $newRedirectUri;
        $emailUsername = $newEmailUsername;
        $gmailAppPassword = $newGmailAppPassword;
        $emailCommonLink = $newEmailCommonLink;
        $emailImageLink = $newEmailImageLink;
        $paypalClientId = $newPaypalClientId;
        logActivity(
            $conn, 
            $loggedInUserId, 
            "Credentials", 
            "Credentials Updated", 
        );
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Credentials updated successfully.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    }
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0">Credentials</h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-body p-40">

            <form method="POST">
                <div class="row">
                    <!-- Google Section Title -->
                    <div class="col-sm-12">
                        <h6 class="fw-bold mt-4 mb-3">Google</h6>
                    </div>
                    <!-- Google Client ID -->
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label for="google_client_id" class="form-label fw-semibold text-primary-light text-sm mb-8">Google Client ID</label>
                            <input type="text" class="form-control radius-8" id="google_client_id" name="google_client_id" placeholder="Google Client ID" value="<?= htmlspecialchars($clientId) ?>" required>
                        </div>
                    </div>
                    <!-- Google Client Secret -->
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label for="google_client_secret" class="form-label fw-semibold text-primary-light text-sm mb-8">Google Client Secret</label>
                            <input type="text" class="form-control radius-8" id="google_client_secret" name="google_client_secret" placeholder="Google Client Secret" value="<?= htmlspecialchars($clientSecret) ?>" required>
                        </div>
                    </div>
                    <!-- Google Redirect URI -->
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label for="google_redirect_uri" class="form-label fw-semibold text-primary-light text-sm mb-8">Google Redirect URI</label>
                            <input type="text" class="form-control radius-8" id="google_redirect_uri" name="google_redirect_uri" placeholder="Google Redirect URI" value="<?= htmlspecialchars($redirectUri) ?>" required>
                        </div>
                    </div>

                    <!-- Email Section Title -->
                    <div class="col-sm-12">
                        <h6 class="fw-bold mt-3 mb-3">Email</h6>
                    </div>
                    <!-- Email Username -->
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label for="email_username" class="form-label fw-semibold text-primary-light text-sm mb-8">Username/Email</label>
                            <input type="text" class="form-control radius-8" id="email_username" name="email_username" placeholder="Your Email" value="<?= htmlspecialchars($emailUsername) ?>" required>
                        </div>
                    </div>
                    <!-- Gmail App Password -->
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label for="gmail_app_password" class="form-label fw-semibold text-primary-light text-sm mb-8">Gmail App Password</label>
                            <input type="text" class="form-control radius-8" id="gmail_app_password" name="gmail_app_password" placeholder="App Password" value="<?= htmlspecialchars($gmailAppPassword) ?>" required>
                        </div>
                    </div>
                    <!-- NEW: Email Common Link -->
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label for="email_common_link" class="form-label fw-semibold text-primary-light text-sm mb-8">Email Common Link</label>
                            <input type="text" class="form-control radius-8" id="email_common_link" name="email_common_link" placeholder="Email Common Link" value="<?= htmlspecialchars($emailCommonLink) ?>" required>
                        </div>
                    </div>
                    <!-- Email Image Link -->
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label for="email_image_link" class="form-label fw-semibold text-primary-light text-sm mb-8">Email Image Link</label>
                            <input type="text" class="form-control radius-8" id="email_image_link" name="email_image_link" placeholder="Image URL" value="<?= htmlspecialchars($emailImageLink) ?>" required>
                        </div>
                    </div>

                    <!-- PayPal Section Title -->
                    <div class="col-sm-12">
                        <h6 class="fw-bold mt-3 mb-3">PayPal</h6>
                    </div>
                    <!-- PayPal Client ID -->
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label for="paypal_client_id" class="form-label fw-semibold text-primary-light text-sm mb-8">PayPal Client ID</label>
                            <input type="text" class="form-control radius-8" id="paypal_client_id" name="paypal_client_id" placeholder="PayPal Client ID" value="<?= htmlspecialchars($paypalClientId) ?>" required>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                        <button type="reset" class="btn btn-secondary text-md px-40 py-11 radius-8">
                            Reset
                        </button>
                        <button type="submit" class="btn text-md px-24 py-12 radius-8 lufera-bg">
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php' ?>