<?php include './partials/layouts/layoutTop.php' ?>

<?php
    $envPath = __DIR__ . '/.env';
    $clientId = '';
    $clientSecret = '';
    $message = '';

    // Load existing values from .env
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'GOOGLE_CLIENT_ID=') === 0) {
                $clientId = trim(explode('=', $line, 2)[1], "\"");
            } elseif (strpos($line, 'GOOGLE_CLIENT_SECRET=') === 0) {
                $clientSecret = trim(explode('=', $line, 2)[1], "\"");
            }
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newClientId = trim($_POST['google_client_id']);
        $newClientSecret = trim($_POST['google_client_secret']);

        $updatedLines = [];
        $foundClientId = $foundClientSecret = false;

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

        file_put_contents($envPath, implode("\n", $updatedLines) . "\n");

        // Update displayed values
        $clientId = $newClientId;
        $clientSecret = $newClientSecret;
        $message = "Credentials updated successfully.";
    }
?>

        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Credentials</h6>
                <ul class="d-flex align-items-center gap-2">
                    <li class="fw-medium">
                        <a href="admin-dashboard.php" class="d-flex align-items-center gap-1 hover-text-primary">
                            <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                            Dashboard
                        </a>
                    </li>
                    <li>-</li>
                    <li class="fw-medium">Settings - API</li>
                </ul>
            </div>

            <div class="card h-100 p-0 radius-12 overflow-hidden">
                <div class="card-body p-40">
    
                    <?php if ($message): ?>
                        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <!-- <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="firebaseSecretKey" class="form-label fw-semibold text-primary-light text-sm mb-8">Firebase secret key</label>
                                    <input type="text" class="form-control radius-8" id="firebaseSecretKey" placeholder="Firebase secret key" value="AAAAxGHw9lE:APA91bHKj6OsrD6EhnG5p26oTiQkXvOxTZwZEfVuuuipyUSNM-a8NB_CugVwfvvaosOvWgFAhQJOLMvxtv7e3Sw8DYpaWKwJIN3kjyIPoNRAe541sBz3x7E6sXZkA-ebueqnQiqNtbdP">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="firebasePublicVapidKey" class="form-label fw-semibold text-primary-light text-sm mb-8">Firebase public vapid key (key pair)</label>
                                    <input type="text" class="form-control radius-8" id="firebasePublicVapidKey" placeholder="Firebase public vapid key (key pair)" value="BKAvKJbnB3QATdp8n1aUo_uhoNK3exVKLVzy7MP8VKydjjzthdlAWdlku6LQISxm4zA7dWoRACI9AHymf4V64kA">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="firebaseAPIKey" class="form-label fw-semibold text-primary-light text-sm mb-8">Firebase API Key</label>
                                    <input type="text" class="form-control radius-8" id="firebaseAPIKey" placeholder="Firebase  API Key" value="AIzaSyDg1xBSwmHKV0usIKxTFL5a6fFTb4s3XVM">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="firebaseAuthDomain" class="form-label fw-semibold text-primary-light text-sm mb-8">Firebase AUTH Domain</label>
                                    <input type="text" class="form-control radius-8" id="firebaseAuthDomain" placeholder="Firebase  AUTH Domain" value="wowdash.firebaseapp.com">
                                </div>
                            </div> -->
                            <!-- <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="firebaseProjectID" class="form-label fw-semibold text-primary-light text-sm mb-8">Firebase Project ID</label>
                                    <input type="text" class="form-control radius-8" id="firebaseProjectID" placeholder="Firebase Project ID" value="wowdash.com">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="firebaseStorageBucket" class="form-label fw-semibold text-primary-light text-sm mb-8">Firebase Storage Bucket</label>
                                    <input type="text" class="form-control radius-8" id="firebaseStorageBucket" placeholder="Firebase Storage Bucket" value="wowdash.appsport.com">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="firebaseMessageSenderID" class="form-label fw-semibold text-primary-light text-sm mb-8">Firebase Message Sender ID</label>
                                    <input type="text" class="form-control radius-8" id="firebaseMessageSenderID" placeholder="Firebase  Message Sender ID" value="52362145">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="firebaseAppID" class="form-label fw-semibold text-primary-light text-sm mb-8">Firebase App ID</label>
                                    <input type="text" class="form-control radius-8" id="firebaseAppID" placeholder="Firebase  App ID" value="1:843456771665:web:ac1e3115e9e17ee1582a70">
                                </div>
                            </div> -->

                            <div class="col-sm-12">
                                <div class="mb-20">
                                    <label for="google_client_id" class="form-label fw-semibold text-primary-light text-sm mb-8">Google Client ID</label>
                                    <input type="text" class="form-control radius-8" id="google_client_id" name="google_client_id" placeholder="Google Client ID" value="<?= htmlspecialchars($clientId) ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                    <label for="google_client_secret" class="form-label fw-semibold text-primary-light text-sm mb-8">Google Client Secret</label>
                                    <input type="text" class="form-control radius-8" id="google_client_secret" name="google_client_secret" placeholder="Google Client Secret" value="<?= htmlspecialchars($clientSecret) ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                    <label for="Payment API" class="form-label fw-semibold text-primary-light text-sm mb-8">Payment API</label>
                                    <input type="text" class="form-control radius-8" id="Payment API" name="Payment API" placeholder="Payment API" value="4343hfdfdfhgd4545455" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                    <label for="Other Credentials" class="form-label fw-semibold text-primary-light text-sm mb-8">Other Credentials</label>
                                    <input type="text" class="form-control radius-8" id="Other Credentials" name="Other Credentials" placeholder="Other Credentials" value="54545gfhgsdfgd45454gffd%^%$gg5454" required>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                                <button type="reset" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                                    Reset
                                </button>
                                <button type="submit" class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8 lufera-bg">
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<?php include './partials/layouts/layoutBottom.php' ?>