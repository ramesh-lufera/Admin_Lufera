<?php include './partials/layouts/layoutTop.php';

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
?>

<?php
    $Id = $_SESSION['user_id'];
    $cat_id = $_SESSION['cat_id'] ?? null;
    
    $sql = "select user_id, username, role, photo from users where id = $Id";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();
    $role = $row['role'];
    $UserId = $row['user_id'];
    $username = $row['username'];
    $photo = !empty($row['photo']) ? $row['photo'] : 'assets/images/user1.png';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $plan_name = $_POST['plan_name'];
        $gst = $_POST['gst'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $receipt_id = $_POST['receipt_id'];
        $created_on = $_POST['created_on'];
        $get_addon = $_POST['get_addon'];
        $get_packages = $_POST['get_packages'] ?? '';
        $get_products = $_POST['get_products'] ?? '';
        $addon_total = $_POST['addon-total'];

        // For Renewal..
        if (isset($_POST['renewal']) && $_POST['renewal'] == 1 && !empty($id)) {
            $durationQuery = $conn->prepare("SELECT duration FROM websites WHERE id = ?");
            $durationQuery->bind_param("i", $id);
            $durationQuery->execute();
            $durationResult = $durationQuery->get_result();
            if ($durationResult && $durationResult->num_rows > 0) {
                $row = $durationResult->fetch_assoc();
                $original_duration = $row['duration'];
            } else {
                $original_duration = "N/A";
            }
            $durationQuery->close();

            // ✅ Update renewal info
            $renewal_period = $_POST['period'] ?? '';
            $duration = $renewal_period ?: $duration;
            $price = isset($_POST['total']) ? floatval($_POST['total']) : floatval($price);
            $gst = round($price * 0.18, 2);
            $total_price = round($price + $gst, 2);
            $created_on = $_POST['expiration_date'] ?? $CreatedAt;
            $receipt_id = $_POST['receipt_id'] ?? $receipt_id;
        } else {
            $original_duration = $duration; // normal case
        }
    }

    // ========== Packages with Prices ==========
    $package_details = [];
    if (!empty($get_packages)) {
        $package_ids = array_map('intval', explode(",", $get_packages));
        if (!empty($package_ids)) {
            $ids_str = implode(",", $package_ids);
            $sql_packages = "SELECT package_name, price FROM package WHERE id IN ($ids_str)";
            $result_packages = $conn->query($sql_packages);

            while ($row_pkg = $result_packages->fetch_assoc()) {
                $package_details[] = [
                    'name' => $row_pkg['package_name'],
                    'price' => floatval($row_pkg['price'])
                ];
            }
        }
    }

    // ========== Products with Prices ==========
    $product_details = [];
    if (!empty($get_products)) {
        $product_ids = array_map('intval', explode(",", $get_products));
        if (!empty($product_ids)) {
            $ids_str = implode(",", $product_ids);
            $sql_products = "SELECT name, price FROM products WHERE id IN ($ids_str)";
            $result_products = $conn->query($sql_products);

            while ($row_prod = $result_products->fetch_assoc()) {
                $product_details[] = [
                    'name' => $row_prod['name'],
                    'price' => floatval($row_prod['price'])
                ];
            }
        }
    }

    // ========== Add-On Services with Prices ==========
    $addon_details = [];
    if (!empty($get_addon)) {
        $addon_ids = array_map('intval', explode(",", $get_addon));
        if (!empty($addon_ids)) {
            $ids_str = implode(",", $addon_ids);
            $sql_addons = "SELECT name, cost FROM `add-on-service` WHERE id IN ($ids_str)";
            $result_addons = $conn->query($sql_addons);

            while ($row_addon = $result_addons->fetch_assoc()) {
                $addon_details[] = [
                    'name' => $row_addon['name'],
                    'price' => floatval($row_addon['cost'])
                ];
            }
        }
    }

    // Get active symbol
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row = $result1->fetch_assoc()) {
        $symbol = $row['symbol'];
    }

    if (isset($_POST['save'])) {
        $plan_id = $_POST['id'];
        $type = $_POST['type'];
        $pay_method = $_POST['pay_method'];
        $receipt_id = $_POST['receipt_id'];
        $plan_name = $_POST['plan_name'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $created_at = date("Y-m-d H:i:s");
        $price = floatval($_POST['price']);
        $gst = $_POST['gst'];
        $discount = $payment_made = "0";
        $get_addon = $_POST['get_addon'];
        $get_packages = $_POST['get_packages'] ?? '';
        $get_products = $_POST['get_products'] ?? '';
        $addon_total = floatval($_POST['addon-total']);
        $user_id = $_SESSION['user_id'];
        // $subtotal = $total_price;
        $subtotal = $price + $addon_total;

        $is_renewal = isset($_POST['renewal']) && $_POST['renewal'] == 1;

        // ===================== HANDLE RENEWAL =====================
        if ($is_renewal) {
            $renewal_duration = $_POST['period'];

            // Fetch current expired_at from DB
            $query = $conn->prepare("SELECT expired_at FROM websites WHERE id = ?");
            $query->bind_param("i", $id);
            $query->execute();
            $result = $query->get_result();
            $row = $result->fetch_assoc();
            $query->close();

            // Determine base date for renewal
            if (!empty($row['expired_at']) && strtotime($row['expired_at']) > time()) {
                // If previous expiration exists and is in the future, add renewal on top of it
                $baseDate = new DateTime($row['expired_at']);
            } else {
                // Otherwise, use created_at + original duration
                $baseDate = new DateTime($created_at);

                if (!empty($duration)) {
                    $baseDate->modify($duration); // e.g., "1 year"
                }
            }

            // Add current renewal duration
            if (!empty($renewal_duration)) {
                $baseDate->modify($renewal_duration);
            }

            $expiredAt = $baseDate->format('Y-m-d H:i:s');

            // Update website record for renewal
            $stmt = $conn->prepare("UPDATE websites SET renewal_duration = ?, expired_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $renewal_duration, $expiredAt, $id);

            if ($stmt->execute()) {
                // Fetch user info
                $sqlUser = "SELECT email, username FROM users WHERE id = ?";
                $userStmt = $conn->prepare($sqlUser);
                $userStmt->bind_param("i", $user_id);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $userData = $userResult->fetch_assoc();
                $userStmt->close();

                $toEmail = $userData['email'];
                $toName  = $userData['username'];

                // Show loader immediately
                echo "
                <script>
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we finalize your renewal.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            const spinner = document.querySelector('.swal2-loader');
                            if (spinner) {
                                spinner.style.borderColor = '#fec700 transparent #fec700 transparent';
                            }
                        }
                    });
                </script>";
                // flush response so browser shows the loader instantly
                ob_flush(); flush();

                $orders_link = rtrim($_ENV['EMAIL_COMMON_LINK'], '/') . '/orders.php';

                // ===================== SEND RENEWAL EMAIL =====================
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
                    $mail->addAddress($toEmail, $toName);

                    $mail->isHTML(true);
                    $mail->Subject = "Renewal Confirmation - Order #$receipt_id";
                    $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset="UTF-8"><title>Renewal Confirmation</title></head>
                        <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                            <tr><td align="center">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                                    style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                                    <tr><td style="padding:20px;text-align:center;">
                                        <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                                    </td></tr>
                                    <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                    <tr><td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                        <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Renewal Confirmation</h3>
                                        <p>Hello <b>' . htmlspecialchars($toName) . '</b>,</p>
                                        <p>Thank you for renewing your plan! Here are your updated order details:</p>
                                        <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                            <tr><td><b>Plan</b></td><td>' . htmlspecialchars($plan_name) . '</td></tr>
                                            <tr><td><b>Receipt ID</b></td><td>' . htmlspecialchars($receipt_id) . '</td></tr>
                                            <tr><td><b>Renewal Duration</b></td><td>' . htmlspecialchars($renewal_duration) . '</td></tr>
                                            <tr><td><b>Total Paid</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($total_price) . '</td></tr>
                                        </table>
                                        <p>Your renewal has been processed successfully. You can check your renewal details anytime in your dashboard.</p>
                                        <div style="margin:30px 0;text-align:center;">
                                            <a href="' . htmlspecialchars($orders_link) . '" style="background:#fec700;color:#101010;text-decoration:none;
                                                padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">View My Orders</a>
                                        </div>
                                        <p>If you have any questions, feel free to reply to this email.</p>
                                    </td></tr>
                                    <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                    <tr><td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                        You’re receiving this email because you renewed your plan at <b>Admin Dashboard</b>.<br>
                                        &copy; 2025 Lufera Infotech. All rights reserved.
                                    </td></tr>
                                </table>
                            </td></tr>
                        </table>
                        </body>
                        </html>';
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Renewal email failed: " . $mail->ErrorInfo);
                }

                echo "<script> 
                    Swal.fire({ 
                        icon: 'success',
                        title: 'Renewed Successfully',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        window.location.href = 'subscription.php'; 
                    }); 
                </script>"; 
            } else {
                echo "<script> 
                    Swal.fire({ 
                        icon: 'error',
                        title: 'Update Failed',
                        text: 'Database error: " . addslashes($stmt->error) . "',
                        confirmButtonColor: '#d33'
                    });
                </script>"; 
            }
            $stmt->close(); 
        } else {
            $sql    = "SELECT id, user_id, email, username FROM users WHERE id = $user_id LIMIT 1";
            $result2 = mysqli_query($conn, $sql);
            $row    = mysqli_fetch_assoc($result2);
            $client_id = $row['id'];
            $toEmail   = $row['email'];     // purchaser email
            $username  = $row['username'];  // purchaser username
            $toName    = $row['username'];

            // Only set addon_price if this is an addon row
            $insert_addon_price = !empty($get_addon) ? $addon_total : '';

            $main_subtotal = $price + floatval($insert_addon_price);
            $main_discount = $discount ?? 0;
            $main_gst      = $main_subtotal * 0.18; // 18% GST
            $main_amount   = $main_subtotal - $main_discount + $main_gst;
            $main_balance_due  = $main_amount - $payment_made;

            $sql = "INSERT INTO orders (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type) VALUES 
                    ('$client_id', '$receipt_id', '$plan_id', '$duration' ,'$main_amount', '$main_gst', '$price', '$insert_addon_price', 'Pending', '$pay_method', '$main_discount', '$payment_made', '$created_at', '$main_subtotal', '$main_amount', '$get_addon', '$type')";

            if (mysqli_query($conn, $sql)) {
                // ================= Packages =================
                if (!empty($get_packages)) {
                    $package_ids = array_map('intval', explode(',', $get_packages));
                    foreach ($package_ids as $pkg_id) {
                        $pkg_sql = "SELECT package_name, price, duration, cat_id FROM package WHERE id = $pkg_id";
                        $pkg_res = mysqli_query($conn, $pkg_sql);
                        if ($pkg_res && $pkg = mysqli_fetch_assoc($pkg_res)) {
                            $pkg_name     = $pkg['package_name'];  // ✅ package name for websites table
                            $pkg_price    = floatval($pkg['price']);
                            $pkg_duration = $pkg['duration'];
                            $pkg_cat_id   = $pkg['cat_id']; // ✅ package category

                            // calculations
                            $pkg_subtotal = $pkg_price;
                            $pkg_discount = 0;
                            $pkg_gst      = $pkg_subtotal * 0.18;
                            $pkg_amount   = $pkg_subtotal - $pkg_discount + $pkg_gst;
                            $pkg_balance  = $pkg_amount - $payment_made;

                            // Generate unique invoice id for package
                            $pkg_invoice_id = rand(10000000, 99999999);

                            // Insert into orders (plan = ID)
                            $sql_package = "INSERT INTO orders 
                                (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type) 
                                VALUES 
                                ('$client_id', '$pkg_invoice_id', '$pkg_id', '$pkg_duration', '$pkg_amount', '$pkg_gst', '$pkg_price', '$pkg_price', 'Pending', '$pay_method', '$pkg_discount', '$payment_made', '$created_at', '$pkg_subtotal', '$pkg_balance', '$pkg_id', 'package')";
                            mysqli_query($conn, $sql_package);

                            // Insert into websites (plan = NAME ✅)
                            $siteInsertPkg = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type) 
                                            VALUES ('$client_id', 'N/A', '$pkg_id', '$pkg_duration', '', NULL, 'Pending', '$pkg_cat_id', '$pkg_invoice_id', '$pkg_id', 'package')";
                            mysqli_query($conn, $siteInsertPkg);
                        }
                    }
                }

                // ================= Products =================
                if (!empty($get_products)) {
                    $product_ids = array_map('intval', explode(',', $get_products));
                    foreach ($product_ids as $prod_id) {
                        $prod_sql = "SELECT name, price, duration, cat_id FROM products WHERE id = $prod_id";
                        $prod_res = mysqli_query($conn, $prod_sql);
                        if ($prod_res && $prod = mysqli_fetch_assoc($prod_res)) {
                            $prod_name     = $prod['name'];   // ✅ product name for websites table
                            $prod_price    = floatval($prod['price']);
                            $prod_duration = $prod['duration'];
                            $prod_cat_id   = $prod['cat_id']; // ✅ product category

                            // calculations
                            $prod_subtotal = $prod_price;
                            $prod_discount = 0;
                            $prod_gst      = $prod_subtotal * 0.18;
                            $prod_amount   = $prod_subtotal - $prod_discount + $prod_gst;
                            $prod_balance  = $prod_amount - $payment_made;

                            // Generate unique invoice id for product
                            $prod_invoice_id = rand(10000000, 99999999);

                            // Insert into orders (plan = ID)
                            $sql_product = "INSERT INTO orders 
                                (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type) 
                                VALUES 
                                ('$client_id', '$prod_invoice_id', '$prod_id', '$prod_duration', '$prod_amount', '$prod_gst', '$prod_price', '$prod_price', 'Pending', '$pay_method', '$prod_discount', '$payment_made', '$created_at', '$prod_subtotal', '$prod_balance', '$prod_id', 'product')";
                            mysqli_query($conn, $sql_product);

                            // Insert into websites (plan = NAME ✅)
                            $siteInsertProd = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type) 
                                            VALUES ('$client_id', 'N/A', '$prod_id', '$prod_duration', '', NULL, 'Pending', '$prod_cat_id', '$prod_invoice_id', '$prod_id', 'product')";
                            mysqli_query($conn, $siteInsertProd);
                        }
                    }
                }

                $domain = "N/A";

                // Insert new website record
                $siteInsert = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type) 
                            VALUES ('$client_id', '$domain', '$plan_id', '$duration', '', NULL, 'Pending', '$cat_id', '$receipt_id', '$plan_id', '$type')";
                mysqli_query($conn, $siteInsert);

                // Show loader immediately
                echo "
                <script>
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we finalize your purchase.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            const spinner = document.querySelector('.swal2-loader');
                            if (spinner) {
                                spinner.style.borderColor = '#fec700 transparent #fec700 transparent';
                            }
                        }
                    });
                </script>";
                // flush response so browser shows the loader instantly
                ob_flush(); flush();

                $orders_link = rtrim($_ENV['EMAIL_COMMON_LINK'], '/') . '/orders.php';

                // ===================== SEND PURCHASE EMAIL =====================
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
                    $mail->addAddress($toEmail, $toName);

                    $mail->isHTML(true);
                    $mail->Subject = "Purchase Confirmation - Order #$receipt_id";
                    $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset="UTF-8"><title>Purchase Confirmation</title></head>
                        <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                            <tr><td align="center">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                                    style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                                    <tr><td style="padding:20px;text-align:center;">
                                        <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                                    </td></tr>
                                    <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                    <tr><td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                        <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Purchase Confirmation</h3>
                                        <p>Hello <b>' . htmlspecialchars($toName) . '</b>,</p>
                                        <p>Thank you for your purchase! Here are the details of your order:</p>
                                        <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                            <tr><td><b>Plan</b></td><td>' . htmlspecialchars($plan_name) . '</td></tr>
                                            <tr><td><b>Receipt ID</b></td><td>' . htmlspecialchars($receipt_id) . '</td></tr>
                                            <tr><td><b>Duration</b></td><td>' . htmlspecialchars($duration) . '</td></tr>
                                            <tr><td><b>Total Paid</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($total_price) . '</td></tr>
                                        </table>
                                        <p>Your service will be activated shortly. You can check your order status anytime in your dashboard.</p>
                                        <div style="margin:30px 0;text-align:center;">
                                            <a href="' . htmlspecialchars($orders_link) . '" style="background:#fec700;color:#101010;text-decoration:none;
                                                padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">View My Orders</a>
                                        </div>
                                        <p>If you have any questions, feel free to reply to this email.</p>
                                    </td></tr>
                                    <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                    <tr><td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                        You’re receiving this email because you made a purchase at <b>Admin Dashboard</b>.<br>
                                        &copy; 2025 Lufera Infotech. All rights reserved.
                                    </td></tr>
                                </table>
                            </td></tr>
                        </table>
                        </body>
                        </html>';
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Purchase email failed: " . $mail->ErrorInfo);
                }

                echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Purchased Successfully.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'subscription.php';
                        }
                    });
                </script>";
            } else {
                echo "<script>
                    alert('Error: " . $stmt->error . "');
                    window.history.back();
                </script>";
            }
        }
    }

    // USER sends payment request (→ notify all admins)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && ($role != '1' || $role != '2')) {
        date_default_timezone_set('Asia/Kolkata');
        $msg = "$username has sent a payment request.";

        $adminQuery = $conn->query("SELECT user_id, email, username FROM users WHERE role IN ('1', '2')");
        while ($adminRow = $adminQuery->fetch_assoc()) {
            $adminUserId = $adminRow['user_id'];
            $adminEmail  = $adminRow['email'];
            $adminName   = $adminRow['username'];
            $createdAt = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo, created_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $adminUserId, $msg, $photo, $createdAt);
            $stmt->execute();

            // Send email to admin/super admin
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['EMAIL_USERNAME'];
                $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom($toEmail, $username); // from purchaser
                $mail->addAddress($adminEmail, $adminName);

                $mail->isHTML(true);
                $mail->Subject = "Payment Request from $username";
                $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head><meta charset="UTF-8"><title>Payment Request</title></head>
                    <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                        <tr><td align="center">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                                style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                                <tr><td style="padding:20px;text-align:center;">
                                    <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                                </td></tr>
                                <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                <tr><td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                    <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Payment Request Email</h3>
                                    <p>Hello <b>' . htmlspecialchars($adminName) . '</b>,</p>
                                    <p>User <b>' . htmlspecialchars($username) . '</b> (' . htmlspecialchars($toEmail) . ') has sent a payment request. Order details are below:</p>
                                    <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                        <tr><td><b>Plan</b></td><td>' . htmlspecialchars($plan_name) . '</td></tr>
                                        <tr><td><b>Receipt ID</b></td><td>' . htmlspecialchars($receipt_id) . '</td></tr>
                                        <tr><td><b>Duration</b></td><td>' . htmlspecialchars($duration) . '</td></tr>
                                        <tr><td><b>Price</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($price) . '</td></tr>
                                        <tr><td><b>GST</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($gst) . '</td></tr>
                                        <tr><td><b>Total</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($total_price) . '</td></tr>
                                    </table>
                                    <div style="margin:30px 0;text-align:center;">
                                        <a href="' . htmlspecialchars($_ENV['EMAIL_COMMON_LINK']) . '/orders.php" 
                                        style="background:#fec700;color:#101010;text-decoration:none;
                                                padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">Review Payment Request</a>
                                    </div>
                                    <p>Thank you,<br><b>Admin Dashboard</b></p>
                                </td></tr>
                                <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                <tr><td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                    You’re receiving this email because a user submitted a payment request.<br>
                                    &copy; 2025 Lufera Infotech. All rights reserved.
                                </td></tr>
                            </table>
                        </td></tr>
                    </table>
                    </body>
                    </html>';
                $mail->send();
            } catch (Exception $e) {
                error_log("Admin email failed: " . $mail->ErrorInfo);
            }
        }
    }
?>

<style>
    .plan-details-table tbody tr td {
        padding: 15px .5rem;
        border-bottom: 1px solid #dadada;
        width: 50%;
    }
    .ad-box {
        background: lightgoldenrodyellow;
        padding: 2px;
        border: 1px solid;
        margin: 10px 0 0;
    }

    /* Payment Option Boxes */
    .payment-option-box {
        border: 1px solid black;
        border-radius: 6px;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        background-color: #fff;
        width: auto;
        max-width: 200px;
        cursor: pointer;
        flex-grow: 0;
        margin-right: 10px;
        margin-bottom: 10px;
        user-select: none;
        transition: background-color 0.2s ease;
    }
    .payment-option-box:hover {
        background-color: #fff8dc;
    }
    .payment-option-box input[type="radio"] {
        margin: 0;
        cursor: pointer;
    }
    .payment-option-box label {
        cursor: pointer;
        margin-left: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }
    .icon-circle {
        background-color: #fec700;
        color: white;
        border-radius: 50%;
        padding: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        font-size: 12px;
    }

    /* Card shadow and button styles */
    .custom-pay-btn {
        background-color: #fec700;
        color: black;
        border: 1px solid black;
        box-shadow: none;
        border-radius: 0;
        padding: 8px 20px;
        font-weight: 600;
    }
    .custom-pay-btn:hover {
        background-color: #ffd700;
    }
    .card-shadow {
        box-shadow: 0px 3px 3px 0px lightgray;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .payment-option-box {
            max-width: 180px;
            padding: 8px 14px;
            font-size: 0.95rem;
        }
    }
    @media (max-width: 576px) {
        .payment-option-box {
            max-width: 100%;
            margin-right: 0;
            padding: 12px 20px;
            font-size: 1rem;
        }
        .payment-option-box label {
            gap: 8px;
            font-weight: 700;
        }
    }
    .payment-detail { display:none; }
</style>

<div class="dashboard-main-body">
    <form method="post">
        <input type="hidden" value="<?php echo $id; ?>" name="id">
        <input type="hidden" value="<?php echo $type; ?>" name="type">
        <input type="hidden" value="<?php echo $duration; ?>" name="duration">
        <input type="hidden" value="<?php echo $receipt_id; ?>" name="receipt_id">
        <input type="hidden" value="<?php echo $plan_name; ?>" name="plan_name">
        <input type="hidden" value="<?php echo $price; ?>" name="price">
        <input type="hidden" value="<?php echo $gst; ?>" name="gst">
        <input type="hidden" value="<?php echo $total_price; ?>" name="total_price">
        <input type="hidden" value="<?php echo $created_on; ?>" name="created_on">
        <input type="hidden" value="<?php echo $get_addon; ?>" name="get_addon">
        <input type="hidden" value="<?php echo $get_packages ?? ''; ?>" name="get_packages">
        <input type="hidden" value="<?php echo $get_products ?? ''; ?>" name="get_products">
        <input type="hidden" value="<?php echo $addon_total; ?>" name="addon-total">

        <?php if (isset($_POST['renewal']) && $_POST['renewal'] == 1): ?>
            <input type="hidden" name="renewal" value="1">
            <input type="hidden" name="id" value="<?= htmlspecialchars($_POST['id']) ?>">
            <input type="hidden" name="period" value="<?= htmlspecialchars($_POST['period']) ?>">
            <input type="hidden" name="expiration_date" value="<?= htmlspecialchars($_POST['expiration_date']) ?>">
            <input type="hidden" name="total" value="<?= htmlspecialchars($_POST['total']) ?>">
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Your Cart</h6>
            <button type="submit" name="save" id="continuePayBtn" class="lufera-bg text-center btn-sm px-12 py-10 float-end" style="width:150px; border: 1px solid #000" value="Submit">Continue to Pay</button>
        </div>
        
        <div class="mb-40">
            <div class="row gy-4">
                <!-- First Card -->
                <div class="col-lg-6 col-md-12">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none card-shadow">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo $plan_name; ?></h6>
                                <span class="text-muted small">Receipt ID: <?php echo $receipt_id; ?></span>
                            </div>
                            <!-- <p class="mb-0">Perfect plan to get started for your own Website</p> -->
                        </div>
                        <div class="card-body p-16">
                            <table class="plan-details-table mb-0 w-100">
                                <tbody>
                                    <!-- <tr>
                                        <td>Period</td>
                                        <td><?php echo $duration; ?></td>
                                    </tr> -->
                                    <tr>
                                        <td>Period</td>
                                        <td>
                                            <?php
                                                if (isset($_POST['renewal']) && $_POST['renewal'] == 1 && !empty($_POST['period'])) {
                                                    echo htmlspecialchars($original_duration) . " + Renewal (" . htmlspecialchars($_POST['period']) . ")";
                                                } else {
                                                    echo htmlspecialchars($original_duration);
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <!-- <tr>
                                        <td>Validity</td>
                                        <td>
                                            <?php
                                                $start_date = new DateTime($created_on);

                                                $duration_str = $duration;
                                                try {
                                                    $interval = DateInterval::createFromDateString($duration_str);
                                                    $end_date = clone $start_date;
                                                    $end_date->add($interval);

                                                    echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                                } catch (Exception $e) {
                                                    echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                                }
                                            ?>
                                        </td>
                                    </tr> -->
                                    <tr>
                                        <td>Validity</td>
                                        <td>
                                            <?php
                                                $start_date = new DateTime($created_on);
                                                $duration_str = $original_duration ?? $duration;

                                                try {
                                                    // Original period end date
                                                    $interval_original = DateInterval::createFromDateString($original_duration);
                                                    $original_end_date = clone $start_date;
                                                    $original_end_date->add($interval_original);

                                                    if (isset($_POST['renewal']) && $_POST['renewal'] == 1 && !empty($_POST['period'])) {
                                                        // Renewal period
                                                        $renewal_period = $_POST['period'];
                                                        $interval_renewal = DateInterval::createFromDateString($renewal_period);
                                                        $renewal_end_date = clone $original_end_date;
                                                        $renewal_end_date->add($interval_renewal);

                                                        echo $start_date->format('d-m-Y') . " to " . $original_end_date->format('d-m-Y') .
                                                            " + Renewal (" . $original_end_date->format('d-m-Y') . " to " . $renewal_end_date->format('d-m-Y') . ")";
                                                    } else {
                                                        // No renewal
                                                        echo $start_date->format('d-m-Y') . " to " . $original_end_date->format('d-m-Y');
                                                    }
                                                } catch (Exception $e) {
                                                    echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <!-- <tr>
                                        <td>Add-Ons</td>
                                        <td>
                                            <?php
                                                $output_parts = [];

                                                if (!empty($package_names)) {
                                                    $output_parts[] = "<b>Packages:</b> " . htmlspecialchars($package_names);
                                                }
                                                if (!empty($product_names)) {
                                                    $output_parts[] = "<b>Products:</b> " . htmlspecialchars($product_names);
                                                }
                                                if (!empty($service_name)) {
                                                    $output_parts[] = "<b>Add-On Services:</b> " . htmlspecialchars($service_name);
                                                }

                                                echo !empty($output_parts) ? implode(" | ", $output_parts) : "None";
                                            ?>
                                        </td>
                                    </tr> -->
                                    <!-- <tr>
                                        <td class="border-0" colspan="2" id="currency-symbol-display">Renews at <?= htmlspecialchars($symbol) ?>1500/year for 3 Years
                                            <p class="text-sm ad-box">Great news! Your FREE domain + 3 months FREE are included with this order</p>
                                        </td>
                                    </tr> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Second Card -->
                <div class="col-lg-6 col-md-12">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none d-flex justify-content-between card-shadow">
                            <div>
                                <h6 class="mb-0">Price</h6>
                                <!-- <p class="mb-0">Sub total does not include applicable taxes</p> -->
                            </div>
                            <div class="align-content-center">
                                <h6 class="mb-0" id="currency-symbol-display"><?php echo htmlspecialchars($symbol) . number_format($price, 2); ?></h6>
                            </div>
                        </div>
                        <div class="card-body p-16">
    <table class="table plan-details-table mb-0 w-100">
        <tbody>
           
                <p class="fw-semibold px-10 mb-0">Add-ons :</p>
            
            
            <!-- Plan Price -->
            <!-- <tr>
                <td><?php echo htmlspecialchars($plan_name); ?></td>
                <td class="text-end" id="currency-symbol-display"><?php echo htmlspecialchars($symbol) . number_format($price, 2); ?></td>
            </tr> -->
            <!-- Packages -->
            <?php if (!empty($package_details)): ?>
                <?php foreach ($package_details as $package): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($package['name']); ?> (Package)</td>
                        <td class="text-end" id="currency-symbol-display"><?php echo htmlspecialchars($symbol) . number_format($package['price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Products -->
            <?php if (!empty($product_details)): ?>
                <?php foreach ($product_details as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?> (Product)</td>
                        <td class="text-end" id="currency-symbol-display"><?php echo htmlspecialchars($symbol) . number_format($product['price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Add-On Services -->
            <?php if (!empty($addon_details)): ?>
                <?php foreach ($addon_details as $addon): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($addon['name']); ?> (Service)</td>
                        <td class="text-end" id="currency-symbol-display"><?php echo htmlspecialchars($symbol) . number_format($addon['price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Tax (GST 18%) -->
            <tr>
                <td>Tax (GST 18%)</td>
                <td class="text-end" id="currency-symbol-display"><?php echo htmlspecialchars($symbol) . number_format($gst, 2); ?></td>
            </tr>
            <!-- Estimated Total -->
            <tr>
                <td class="border-0 fw-semibold">Total</td>
                <td class="border-0 text-end fw-semibold text-xl" id="currency-symbol-display"><?php echo htmlspecialchars($symbol) . number_format($total_price, 2); ?></td>
            </tr>
        </tbody>
    </table>
</div>
                    </div>
                </div>
            </div>

            <!-- Payment Method Card -->
            <div class="row">
                <div class="col-12 mt-3">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none card-shadow">
                            <h6 class="mb-0">Select Payment Mode</h6>
                            <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p>
                        </div>
                        <div class="card-body p-16">
                            <p class="text-muted fw-medium mb-3">How would you like to make the payment? <span class="text-danger-600">*</span></p>

                            <div class="d-flex flex-wrap gap-3 justify-content-start">
                                <?php
                                    $payments = [
                                        'Bank Transfer' => 'bank-transfer',
                                        'Direct Pay'    => 'direct-pay',
                                        'PayPal'        => 'paypal-button-container'
                                    ];
                                    foreach ($payments as $label => $target): ?>
                                        <div class="payment-option-box">
                                            <input
                                                type="radio"
                                                class="form-check-input m-0"
                                                name="pay_method"
                                                id="pay<?= $target ?>"
                                                value="<?= $label ?>"
                                                data-target="<?= $target ?>"                   
                                            >
                                            <label for="pay<?= $target ?>">
                                                <?= $label ?>
                                                <span class="icon-circle">
                                                    <i class="fas fa-chevron-down"></i>
                                                </span>
                                            </label>
                                        </div>
                                <?php endforeach; ?>                            
                            </div>
                            <?php
                                $sql = "SELECT * FROM bank_details LIMIT 1";
                                $result3 = $conn->query($sql);
                                if ($result3->num_rows > 0) {
                                    $row = $result3->fetch_assoc();
                                    $id = $row['id'];
                                    $bank_name = $row['bank_name'];
                                    $ac_name = $row['ac_name'];
                                    $ac_no = $row['ac_no'];
                                    $branch = $row['branch'];
                                    $ifsc_code = $row['ifsc_code'];
                                    $micr = $row['micr'];
                                    $swift_code = $row['swift_code'];
                                }
                            ?>
                            <div id="bank-transfer" class="payment-detail">
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card h-100 radius-12">
                                        <div class="card-header py-10 border-none card-shadow">
                                            <h6 class="mb-0">Bank Transfer</h6>
                                            <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p>
                                        </div>
                                        <div class="card-body p-16">
                                            <div class="fw-semibold my-3">Bank A/C Details:</div>
                                            <div class="row gy-4 align-items-start">
                                            <div class="col-lg-5 col-md-6">
                                                <div class="table-responsive">
                                                <table class="table table-bordered small mb-0">
                                                    <tbody>
                                                    <tr><td>Bank Name</td><td><?php echo htmlspecialchars($bank_name); ?></td></tr>
                                                    <tr><td>Account Name</td><td><?php echo htmlspecialchars($ac_name); ?></td></tr>
                                                    <tr><td>Account No</td><td><?php echo htmlspecialchars($ac_no); ?></td></tr>
                                                    <tr><td>Account Branch</td><td><?php echo htmlspecialchars($branch); ?></td></tr>
                                                    <tr><td>IFSC</td><td><?php echo htmlspecialchars($ifsc_code); ?></td></tr>
                                                    <tr><td>MICR</td><td><?php echo htmlspecialchars($micr); ?></td></tr>
                                                    <tr><td>Swift Code</td><td><?php echo htmlspecialchars($swift_code); ?></td></tr>
                                                    </tbody>
                                                </table>
                                                </div>
                                            </div>
                                            <div class="col-lg-7 col-md-6 d-flex align-items-start">
                                                <div class="ms-lg-5 pt-0 w-100">
                                                <p class="mb-1 fw-medium">Please let us know!</p>
                                                <p class="mb-1 text-muted small">Once you are done with your payment please let us know.</p>
                                                <p class="mb-3 text-muted small">Thank You.</p>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="direct-pay" class="payment-detail">
                                <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card h-100 radius-12">
                                            <div class="card-header py-10 border-none card-shadow">
                                                <h6 class="mb-0">Direct Pay</h6>
                                                <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p>
                                            </div>
                                            <div class="card-body p-16">
                                                <div class="fw-semibold my-3">Thank You!</div>

                                                <div class="row gy-4 align-items-start">
                                                    <div class="col-lg-7 col-md-6 d-flex align-items-start">
                                                        <div class="ms-lg-5 pt-0 w-100">
                                                            <p class="mb-1 fw-medium">Please confirm your payment with one of our representative.</p>
                                                            <p class="mb-1 text-muted small">Contact your Relationship manager or call us at +91 -86-80808-204 or write to us at info@luferatech.com.</p>
                                                            <p class="mb-3 text-muted small">For futher support.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="paypal-button-container" class="payment-detail mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
    </form>
</div>

<!-- PayPal -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($_ENV['PAYPAL_CLIENT_ID']) ?>&currency=USD"></script>

<script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        // value: '1.00'
                        value: '<?= $total_price ?>'
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                alert('Payment successful! Thank you, ' + details.payer.name.given_name + '!');
                console.log('Capture result', details, JSON.stringify(details, null, 2));
                // You can redirect or save order details here

                document.getElementById('continuePayBtn').click();
            });
        }
    }).render('#paypal-button-container');
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const payBtn = document.getElementById('continuePayBtn');
        const paypalContainer = document.getElementById('paypal-button-container');

        // Always hide PayPal section initially
        paypalContainer.style.display = 'none';

        // Listen for changes in payment method radio buttons
        document.querySelectorAll('input[name="pay_method"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const selectedTarget = this.getAttribute('data-target');

                if (selectedTarget === 'paypal-button-container') {
                    // Hide continue button (inline style) and show PayPal section
                    payBtn.style.display = 'none';
                    paypalContainer.style.display = 'block';
                } else {
                    // Show continue button (inline style) and hide PayPal section
                    payBtn.style.display = 'inline-block';
                    paypalContainer.style.display = 'none';
                }
            });
        });
    });
</script>

<script>
    $('#updateForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: 'update.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#result').html(response);
           },
            error: function(xhr) {
                $('#result').html("Error updating data.");
            }
        });
    });

</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const radios   = document.querySelectorAll('input[name="pay_method"]');
    const details  = document.querySelectorAll('.payment-detail');

    // convenience function
    function showDetail(id) {
        details.forEach(el => { el.style.display = 'none'; });
        const chosen = document.getElementById(id);
        if (chosen) { chosen.style.display = 'block'; }
    }

    // run once so the default-checked radio shows its panel on load
    const checked = document.querySelector('input[name="pay_method"]:checked');
    if (checked) { showDetail(checked.dataset.target); }

    // change handler
    radios.forEach(radio =>
        radio.addEventListener('change', e => showDetail(e.target.dataset.target))
    );
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById("continuePayBtn");

        btn.addEventListener("click", function (e) {
            const selected = document.querySelector('input[name="pay_method"]:checked');

            if (!selected) {
                e.preventDefault(); // Stop form submission

                Swal.fire({
                    icon: 'warning',
                    title: 'Select Payment Method',
                    // text: 'Please select a payment method before continuing.',
                    confirmButtonColor: '#fec700'
                });
            }
        });
    });
</script>

<?php include './partials/layouts/layoutBottom.php' ?>