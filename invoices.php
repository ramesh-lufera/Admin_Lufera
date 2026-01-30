<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Orders</title>
<style>
    /* Styling for disabled button to appear blurred */
    .disabled {
        pointer-events: none;  /* Prevents clicking */
        opacity: 0.5;  /* Makes the button appear blurred */
    }
    .btn{
        width:130px;
    }
</style>
</head>

<?php 
    include './partials/layouts/layoutTop.php';

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    require_once __DIR__ . '/vendor/autoload.php';

    use Dotenv\Dotenv;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $Id = $_SESSION['user_id'];

    // Fetch users data from the database
    $sql = "SELECT * FROM users ORDER BY created_at ASC";
    $result = mysqli_query($conn, $sql);
    
    $sql1 = "select * from users where id = $Id";
    $result1 = $conn ->query($sql1);
    $row = $result1 ->fetch_assoc();
    $role = $row['role'];
    $UserId = $row['user_id'];
    $photo = !empty($row['photo']) ? $row['photo'] : 'assets/images/user1.png';

    // Get active symbol
    $result2 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result2->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }

    // ADMIN approves → Notify USER
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id']) && ($role === '1' || $role === '2')) {
        $orderId = intval($_POST['approve_id']);

        // Approve order
        $conn->query("UPDATE orders SET status = 'Approved' WHERE id = $orderId");

        // SweetAlert flag
        $_SESSION['order_approved'] = true;

        // For Notifications..
        date_default_timezone_set('Asia/Kolkata');

        // Get user_id for notification
        $res = $conn->query("SELECT user_id FROM orders WHERE id = $orderId");
        $user = $res->fetch_assoc();
        $IdFromOrders = $user['user_id'];

        // Get matching user_id from users table
        $resUser = $conn->query("SELECT user_id FROM users WHERE id = $IdFromOrders");
        $userRow = $resUser->fetch_assoc(); // fetch the row
        $userId = $userRow['user_id']; // matched user_id from users table

        // Add notification
        $msg = "Your payment has been approved.";
        $createdAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $userId, $msg, $photo, $createdAt);
        $stmt->execute();

        // Get order + user details
        $res = $conn->query("SELECT o.invoice_id, o.plan, o.amount, u.email, u.first_name, u.last_name 
                             FROM orders o 
                             INNER JOIN users u ON o.user_id = u.id 
                             WHERE o.id = $orderId");
        $order = $res->fetch_assoc();

        $userEmail = $order['email'];
        $userName  = $order['first_name'] . " " . $order['last_name'];
        $planName  = $order['plan'];
        $invoiceId = $order['invoice_id'];
        $amount    = $order['amount'];

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
        // ✅ Send Email to User
        try {
            $mail = new PHPMailer(true);
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
            $mail->addAddress($userEmail, $userName);
            $mail->isHTML(true);
            $mail->Subject = "Your Order Has Been Approved";
            $mail->ContentType = 'text/html; charset=UTF-8';

            $mail->Body = '
                <!DOCTYPE html>
                <html>
                <head>
                <meta charset="UTF-8">
                <title>Order Approved</title>
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
                            <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Order Approved</h3>
                            <p>Hello <b>' . htmlspecialchars($userName) . '</b>,</p>
                            <p>Your order has been approved by the admin. Here are the details:</p>
                            
                            <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                <tr><td><b>Plan</b></td><td>' . htmlspecialchars($planName) . '</td></tr>
                                <tr><td><b>Invoice ID</b></td><td>' . htmlspecialchars($invoiceId) . '</td></tr>
                                <tr><td><b>Total Paid</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($amount) . '</td></tr>
                            </table>

                            <p>You can now access your purchased service from your dashboard.</p>
                            
                            <div style="margin:30px 0;text-align:center;">
                                <a href="' . htmlspecialchars($_ENV['EMAIL_COMMON_LINK']) . '/orders.php" 
                                style="background:#fec700;color:#101010;text-decoration:none;
                                        padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">
                                View My Orders
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
                            You’re receiving this email because your payment was approved at <b>Admin Dashboard</b>.<br>
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
            error_log("Email not sent. Error: {$mail->ErrorInfo}");
        }
    }

    // ADMIN cancels → Update status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id']) && ($role === '1' || $role === '2')) {
        $orderId = intval($_POST['cancel_id']);
        $conn->query("UPDATE orders SET status = 'Cancelled' WHERE id = $orderId");
        $_SESSION['order_cancelled'] = true;

        // For Notifications..
        date_default_timezone_set('Asia/Kolkata');

        // Get user_id for notification
        $res = $conn->query("SELECT user_id FROM orders WHERE id = $orderId");
        $user = $res->fetch_assoc();
        $IdFromOrders1 = $user['user_id'];

        // Get matching user_id from users table
        $resUser = $conn->query("SELECT user_id FROM users WHERE id = $IdFromOrders1");
        $userRow = $resUser->fetch_assoc(); // fetch the row
        $userId = $userRow['user_id']; // matched user_id from users table

        // Add notification
        $msg = "Your order has been cancelled.";
        $createdAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $userId, $msg, $photo, $createdAt);
        $stmt->execute();

        // Fetch order + user info for email
        $sql = $conn->prepare("SELECT u.email, u.username, o.invoice_id, o.plan, o.amount 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            WHERE o.id = ?");
        $sql->bind_param("i", $orderId);
        $sql->execute();
        $result = $sql->get_result();
        $row = $result->fetch_assoc();

         if ($row) {
            $userEmail = $row['email'];
            $userName  = $row['username'];
            $plan  = $row['plan'];
            $invoiceId = $row['invoice_id'];
            $amount    = $row['amount'];

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
            // Send Cancelled Mail
            try {
                $mail = new PHPMailer(true);
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
                $mail->addAddress($userEmail, $userName);
                $mail->isHTML(true);
                $mail->Subject = "Your Order Has Been Cancelled";
                $mail->ContentType = 'text/html; charset=UTF-8';

                $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                    <meta charset="UTF-8">
                    <title>Order Cancelled</title>
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
                                <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;color:#d32f2f;">Order Cancelled</h3>
                                <p>Hello <b>' . htmlspecialchars($userName) . '</b>,</p>
                                <p>We regret to inform you that your order has been <b style="color:#d32f2f;">cancelled</b> by the admin. Here are the details:</p>
                                
                                <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                    <tr><td><b>Plan</b></td><td>' . htmlspecialchars($plan) . '</td></tr>
                                    <tr><td><b>Invoice ID</b></td><td>' . htmlspecialchars($invoiceId) . '</td></tr>
                                    <tr><td><b>Total Paid</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($amount) . '</td></tr>
                                </table>

                                <p>If this was unexpected, please contact our support team for clarification.</p>
                                
                                <div style="margin:30px 0;text-align:center;">
                                    <a href="' . htmlspecialchars($_ENV['EMAIL_COMMON_LINK']) . '/sign-in.php" 
                                    style="background:#d32f2f;color:#ffffff;text-decoration:none;
                                            padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">
                                    Contact Support
                                    </a>
                                </div>

                                <p>We’re sorry for any inconvenience caused.</p>
                                </td>
                            </tr>

                            <!-- Divider -->
                            <tr>
                                <td style="border-top:1px solid #eaeaea;"></td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                You’re receiving this email because your order was cancelled by the <b>Admin Dashboard</b>.<br>
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
                error_log("Cancel email not sent. Error: {$mail->ErrorInfo}");
            }
        }
    }
    
    // // JOIN orders with users
    // $query = "
    //     SELECT
    //         orders.*,
    //         users.username,
    //         users.first_name,
    //         users.last_name,
    //         users.photo,
    //         CASE 
    //             WHEN orders.type = 'package' THEN package.package_name
    //             WHEN orders.type = 'product' THEN products.name
    //             ELSE orders.plan
    //         END AS plan_name
    //     FROM orders
    //     INNER JOIN users ON orders.user_id = users.id
    //     LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
    //     LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
    // ";

    // JOIN orders + renewal_invoices with users + websites
    /*$query = "
        SELECT
            combined.*,
            combined.username,
            combined.first_name,
            combined.last_name,
            combined.photo,
            combined.plan_name,
            combined.expired_at
        FROM (
            SELECT
                orders.*,
                users.username,
                users.first_name,
                users.last_name,
                users.photo,
                websites.expired_at,
                CASE 
                    WHEN orders.type = 'package' THEN package.package_name
                    WHEN orders.type = 'product' THEN products.name
                    ELSE orders.plan
                END AS plan_name
            FROM orders
            INNER JOIN users ON orders.user_id = users.id
            LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
            LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
            LEFT JOIN websites ON orders.invoice_id = websites.invoice_id

            UNION ALL

            SELECT
                renewal_invoices.*,
                users.username,
                users.first_name,
                users.last_name,
                users.photo,
                websites.expired_at,
                CASE 
                    WHEN renewal_invoices.type = 'package' THEN package.package_name
                    WHEN renewal_invoices.type = 'product' THEN products.name
                    ELSE renewal_invoices.plan
                END AS plan_name
            FROM renewal_invoices
            INNER JOIN users ON renewal_invoices.user_id = users.id
            LEFT JOIN package ON (renewal_invoices.type = 'package' AND renewal_invoices.plan = package.id)
            LEFT JOIN products ON (renewal_invoices.type = 'product' AND renewal_invoices.plan = products.id)
            LEFT JOIN websites ON (renewal_invoices.user_id = websites.user_id AND renewal_invoices.plan = websites.plan)
        ) AS combined
    ";*/
    $query = "
    SELECT
        combined.*,
        combined.username,
        combined.first_name,
        combined.last_name,
        combined.photo,
        combined.plan_name,
        combined.expired_at
    FROM (
        SELECT
            orders.id,
            orders.user_id,
            orders.invoice_id,
            orders.plan,
            orders.duration,
            orders.price,
            orders.addon_price,
            orders.subtotal,
            orders.gst,
            orders.discount,
            orders.amount,
            orders.balance_due,
            orders.payment_made,
            orders.payment_method,
            orders.addon_service,
            orders.type,
            orders.discount_type,
            orders.status,
            orders.created_on,
            orders.is_Active,
            orders.coupon_code,
            orders.discount_amount,
            users.username,
            users.first_name,
            users.last_name,
            users.photo,
            websites.expired_at,
            CASE 
                WHEN orders.type = 'package' THEN package.package_name
                WHEN orders.type = 'product' THEN products.name
                ELSE orders.plan
            END AS plan_name
        FROM orders
        INNER JOIN users ON orders.user_id = users.id
        LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
        LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
        LEFT JOIN websites ON orders.invoice_id = websites.invoice_id

        UNION ALL

        SELECT
            renewal_invoices.id,
            renewal_invoices.user_id,
            renewal_invoices.invoice_id,
            renewal_invoices.plan,
            renewal_invoices.duration,
            renewal_invoices.price,
            renewal_invoices.addon_price,
            renewal_invoices.subtotal,
            renewal_invoices.gst,
            renewal_invoices.discount,
            renewal_invoices.amount,
            renewal_invoices.balance_due,
            renewal_invoices.payment_made,
            renewal_invoices.payment_method,
            renewal_invoices.addon_service,
            renewal_invoices.type,
            renewal_invoices.discount_type,
            renewal_invoices.status,
            renewal_invoices.created_on,
            renewal_invoices.is_Active,
            NULL AS coupon_code,
            NULL AS discount_amount,
            users.username,
            users.first_name,
            users.last_name,
            users.photo,
            websites.expired_at,
            CASE 
                WHEN renewal_invoices.type = 'package' THEN package.package_name
                WHEN renewal_invoices.type = 'product' THEN products.name
                ELSE renewal_invoices.plan
            END AS plan_name
        FROM renewal_invoices
        INNER JOIN users ON renewal_invoices.user_id = users.id
        LEFT JOIN package ON (renewal_invoices.type = 'package' AND renewal_invoices.plan = package.id)
        LEFT JOIN products ON (renewal_invoices.type = 'product' AND renewal_invoices.plan = products.id)
        LEFT JOIN websites ON (renewal_invoices.user_id = websites.user_id AND renewal_invoices.plan = websites.plan)
    ) AS combined
";

    // if ($role !== '1' && $role !== '2') {
    //     if (!empty($UserId)) {
    //         $query .= " WHERE orders.user_id = '$Id'";
    //     } else {
    //         $query .= " WHERE 1 = 0";
    //     }
    // }

    if ($role !== '1' && $role !== '2') {
        if (!empty($UserId)) {
            $query .= " WHERE combined.user_id = '$Id'";
        } else {
            $query .= " WHERE 1 = 0";
        }
    }

    $query .= " ORDER BY combined.created_on DESC";

    $result = mysqli_query($conn, $query);
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-24">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            <h6 class="fw-semibold mb-0 m-auto">Invoice</h6>
            <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        </div>
        <div class="card">
            <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Plan</th>
                            <th scope="col" class="text-center">Invoice ID</th>
                            <th scope="col" class="text-center">Date</th>
                            <th scope="col" class="text-center">Amount</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        $photo = !empty($row['photo']) ? $row['photo'] : 'assets/images/user1.png';
                        $status = $row['status'];
                    ?>
                        <tr>
                            <td>
                                <div class="fw-medium">
                                    <img src="<?= $photo ?>" alt="" class="flex-shrink-0 me-12 radius-8" style="width: 30px; height: 30px;">
                                    <?php echo $row['first_name']; ?> <?php echo $row['last_name']; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                            <td class="text-center"><?php echo $row['invoice_id']; ?></td>
                            <td class="text-center"><?= date('d M Y', strtotime($row['created_on'])) ?></td>
                            <td class="text-center" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['amount'], 2) ?></td>
                           
                            <td class="text-center">
                                <?php 
                                if ($row['balance_due'] === '0.00'){ ?>
                                    Fully Paid
                                <?php } else { ?>
                                    Proforma Invoice
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $invoice_id = $row['invoice_id'];
                                    $expiredAt = isset($row['expired_at']) ? $row['expired_at'] : null;
                                    $todayDate = date('Y-m-d');

                                    $checkOrder = mysqli_query($conn, "SELECT id FROM orders WHERE invoice_id = '$invoice_id' LIMIT 1");

                                    if (mysqli_num_rows($checkOrder) > 0) {
                                        // invoice_id exists in orders → normal
                                        $type = 'normal';
                                    } else {
                                        // invoice_id not found → renewal
                                        $type = 'renewal';
                                    }
                                ?>
                                <!-- <a href="order-summary.php?id=<?php echo $row['invoice_id']; ?>" class="fa fa-eye view-user-btn bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle"> -->
                                <a href="order-summary.php?id=<?php echo $invoice_id; ?>&type=<?php echo $type; ?>" class="fa fa-eye view-user-btn bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle">
                                </a>
                                <!-- <a href="invoice-preview.php?id=<?php echo $row['invoice_id']; ?>" class="fa fa-file view-user-btn bg-warning-focus text-warning-600 bg-hover-warning-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle"> -->
                                <a href="invoice-preview.php?id=<?php echo $invoice_id; ?>&type=<?php echo $type; ?>" class="fa fa-file view-user-btn bg-warning-focus text-warning-600 bg-hover-warning-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle">
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#userTable').DataTable();
    } );

    function approveAction() {
        // Blur the Approve button by adding a "disabled" class and prevent it from being clicked
        let approveButton = document.getElementById('approveButton');
        approveButton.classList.add('disabled');  // Add "disabled" class for visual effect
        approveButton.setAttribute('disabled', true);  // Disable the button to prevent further clicks
    }
</script>

<?php if (isset($_SESSION['order_approved']) && $_SESSION['order_approved'] === true): ?>
    <script>
        Swal.fire({
            title: "Order Approved",
            // text: "The order has been successfully approved!",
            icon: "success",
            confirmButtonText: "OK"
        });
    </script>
    <?php unset($_SESSION['order_approved']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['order_cancelled']) && $_SESSION['order_cancelled'] === true): ?>
    <script>
        Swal.fire({
            title: "Order Cancelled",
            icon: "warning",
            confirmButtonText: "OK"
        });
    </script>
    <?php unset($_SESSION['order_cancelled']); ?>
<?php endif; ?>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>