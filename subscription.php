<?php
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
?>
<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Subscription</title>
<style>
    /* Styling for disabled button to appear blurred */
    .disabled {
        pointer-events: none;  /* Prevents clicking */
        opacity: 0.5;  /* Makes the button appear blurred */
    }
</style>
</head>

<?php 
    include './partials/layouts/layoutTop.php';
    require_once __DIR__ . '/vendor/autoload.php';
    use Dotenv\Dotenv;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $Id = $_SESSION['user_id'];

    // Get role of logged-in user
    $roleQuery = "SELECT role FROM users WHERE id = '$Id' LIMIT 1";
    $roleResult = mysqli_query($conn, $roleQuery);
    $roleRow = mysqli_fetch_assoc($roleResult);
    $role = $roleRow['role'];

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

            $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
            $mail->addAddress($userEmail, $userName);

            $mail->isHTML(true);
            $mail->Subject = "Your Order Has Been Approved";

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

                $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
                $mail->addAddress($userEmail, $userName);

                $mail->isHTML(true);
                $mail->Subject = "Your Order Has Been Cancelled";

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
    
    // JOIN orders with users
    $query = "
        SELECT
            orders.*,
            users.username,
            users.first_name,
            users.last_name,
            users.photo,
            users.business_name,

            CASE 
                WHEN orders.type = 'package' THEN package.package_name
                WHEN orders.type = 'product' THEN products.name
                ELSE orders.plan
            END AS plan_name

            FROM orders
            INNER JOIN users ON orders.user_id = users.id
            LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
            LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
    ";

    // Add condition only if role is NOT 1 or 2
    if ($role != 1 && $role != 2) {
        $query .= " WHERE orders.user_id = '$Id'";
    }
    $result = mysqli_query($conn, $query);

    function generatePaymentID($conn) {
        do {
            $randomNumber = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $paymentID = "L_" . $randomNumber;
    
            // Check uniqueness in DB
            $check = mysqli_query($conn, "SELECT payment_id FROM record_payment WHERE payment_id = '$paymentID'");
        } while (mysqli_num_rows($check) > 0);
    
        return $paymentID;
    }

    if (isset($_POST['save'])) {
        $order_id = $_POST['order_id'];
        $invoice_no = $_POST['invoice_no'];
        $payment_method = $_POST['payment_method'];
        $amount = $_POST['amount'];

        $payment_made = $_POST['payment_made'];

        $total_amount = $amount + $payment_made;
        $created_at = date("Y-m-d H:i:s");
        $remarks = $_POST['remarks'];
        $balance_due = $_POST['balance_due'];
        $payment_id = generatePaymentID($conn);
        $sql = "INSERT INTO record_payment (payment_id, orders_id, invoice_no, payment_method, amount, balance, remarks, paid_date) 
                        VALUES ('$payment_id', '$order_id', '$invoice_no', '$payment_method', '$amount', '$balance_due', '$remarks', '$created_at')";
            if (mysqli_query($conn, $sql)) {

                $siteInsert = "UPDATE orders
                                SET payment_made = $total_amount, balance_due = $balance_due
                                WHERE invoice_id = '$invoice_no'";
                    mysqli_query($conn, $siteInsert);
                echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Payment Record Created Successfully.',
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
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>    
            <h6 class="fw-semibold mb-0">Subscriptions</h6>
            <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        </div>
        <div class="card">
            <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col">Subscription</th>
                            <th scope="col">Invoice Id</th>
                            <th scope="col">Bussiness name</th>
                            <th scope="col" class="text-center">Expiration date</th>
                            <th scope="col" class="text-center">Auto-renewal</th>
                            <th scope="col" class="text-center">-</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                $createdOn = new DateTime($row['created_on']);
                                $duration  = $row['duration'];
                                $expiryDate = (clone $createdOn)->modify("+$duration");
                                $expiryFormatted = $expiryDate->format("Y-m-d");
                                $orderId = $row['id']; // unique identifier

                                $statusColor = "";
                                $statusText = "";

                                if ($row['status'] === 'Approved') {
                                    $statusColor = "text-warning"; // yellow
                                    $statusText = "Approved";
                                } elseif ($row['status'] === 'Cancelled') {
                                    $statusColor = "text-danger"; // red
                                    $statusText = "Rejected";
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['invoice_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['business_name']); ?></td>
                                <td class="text-center"><?php echo $expiryFormatted; ?></td>
                                <td class="text-center">Off</td>
                                <td class="text-center">
                                    <!-- link points to unique offcanvas -->
                                    <a class="fa fa-chevron-right ms-10 text-sm lufera-color" 
                                    data-bs-toggle="offcanvas" 
                                    data-bs-target="#offcanvas-<?php echo $orderId; ?>"></a>
                                </td>
                            </tr>

                            <!-- unique offcanvas for this row -->
                            <div class="offcanvas offcanvas-end" id="offcanvas-<?php echo $orderId; ?>">
                                <div class="offcanvas-header pb-0">
                                    <h6>Subscription details</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                                </div>
                                <div class="offcanvas-body">
                                    <h6 class="text-lg"><?php echo htmlspecialchars($row['plan_name']); ?></h6>
                                    <p class="text-sm"><?php echo htmlspecialchars($row['business_name']); ?></p>
                                    <!-- <div class="d-flex justify-content-between my-3">
                                        <span>Status</span>
                                        <span><i class="fa-regular fa-circle-check text-success me-2"></i>Active</span>
                                    </div> -->
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Status</span>
                                        <span class="<?= $statusColor; ?> fw-semibold"><?= $statusText; ?></span>
                                    </div>
                                    <hr />
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Expiration date</span>
                                        <span><?php echo $expiryFormatted; ?></span>
                                    </div>
                                    <hr />
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Renewal price</span>
                                        <span></span>
                                    </div>
                                    <hr />
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Auto renewal</span>
                                        <span>Off</span>
                                    </div>
                                    <hr />
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Next billing period</span>
                                        <span><?php echo $duration; ?></span>
                                    </div>
                                    <hr />

                                    <h6 class="text-md mt-20">ADD-ONS</h6>
                                    <?php
                                    if (!empty($row['addon_service'])) {
                                        $addon_ids = explode(",", $row['addon_service']);
                                        $ids_str = implode(",", array_map('intval', $addon_ids));

                                        $sql_addons = "SELECT name FROM `add-on-service` WHERE id IN ($ids_str)";
                                        $res_addons = $conn->query($sql_addons);

                                        if ($res_addons && $res_addons->num_rows > 0) {
                                            while ($addon = $res_addons->fetch_assoc()) {
                                                ?>
                                                <h6 class="text-lg my-20"><?= htmlspecialchars($addon['name']) ?></h6>
                                                <div class="d-flex justify-content-between my-3">
                                                    <span>Renewal price</span>
                                                    <span></span>
                                                </div>
                                                <hr />
                                                <?php
                                            }
                                        } else {
                                            echo "<p class='text-muted'>No add-ons found</p>";
                                        }
                                    } else {
                                        echo "<p class='text-muted'>No add-ons selected</p>";
                                    }
                                    ?>
                                    <h6 class="text-md mt-20">Payment Received</h6>

                                    <div class="d-flex justify-content-between mt-3 p-4" style="background:lightgray">
                                        <span class="fw-semibold">Date</span>
                                        <span class="fw-semibold">Amount</span>
                                    </div>
                                    <hr />
                                    
                                    <?php
                                        $invoice_id = $row['invoice_id'];
                                        $id = $row['id'];
                                        $payment_made = $row['payment_made'];
                                        $balance_due = $row['balance_due'];
                                        // Get role of logged-in user
                                        $invoiceQuery = "SELECT * FROM record_payment WHERE invoice_no = '$invoice_id'";
                                        $invoiceResult = mysqli_query($conn, $invoiceQuery);
                                        if (mysqli_num_rows($invoiceResult) > 0) {
                                            while ($invoiceRow = mysqli_fetch_assoc($invoiceResult)) {
                                                $date = $invoiceRow['paid_date'];
                                                $amount = $invoiceRow['amount'];
                                                ?>
                                                <div class="d-flex justify-content-between my-2 p-4">
                                                    <span><?php echo $date; ?></span>
                                                    <span><?php echo number_format($amount, 2); ?></span>
                                                </div>
                                                <hr />
                                                <?php
                                            }
                                        } else {
                                            echo "<div>No payments found.</div>";
                                        }
                                    ?>

                                    <div class="mt-20">
                                    <?php 
                                        if($role == "1" || $role == "2") {?>  
                                        <!-- <button class="btn text-white btn-primary text-sm mb-10" data-bs-toggle="modal" data-bs-target="#exampleModal">Record Payment</button> -->
                                        <button
                                            class="btn text-white btn-primary text-sm mb-10 record-payment-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#exampleModal"
                                            data-invoice="<?php echo htmlspecialchars($row['invoice_id']); ?>"
                                            data-order="<?php echo htmlspecialchars($row['id']); ?>"
                                            data-balance="<?php echo htmlspecialchars($row['balance_due']); ?>"
                                            data-payment="<?php echo htmlspecialchars($row['payment_made']); ?>"
                                        >
                                            Record Payment
                                        </button>
                                    <?php } ?>
                                        <button class="btn text-white lufera-bg text-sm mb-10">Renew</button>
                                        <a href="invoice-preview.php?id=<?php echo $invoice_id; ?>"><button class="btn text-white btn-success text-sm mb-10">Invoice</button> </a>   
                                        <a href="order-summary.php?id=<?php echo $invoice_id; ?>"><button class="btn text-white btn-danger text-sm mb-10">View More</button></a>
                                    </div>

                                    <!-- New Order Approvals Section (Admin only) -->
                                    <?php if ($role === '1' || $role === '2'): ?>
                                        <div class="mt-20">
                                            <h6 class="text-md mb-10">Order Approvals Management</h6>
                                            <p class="text-muted">This section will display all order approval and rejected buttons for this section.</p>
                                            <div class="d-flex gap-3 mt-10">
                                                <!-- Approve Button -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="approve_id" value="<?= $orderId; ?>">
                                                    <button type="submit" class="btn btn-success text-white text-sm d-flex align-items-center"
                                                        <?= ($row['status'] === 'Approved') ? 'disabled' : ''; ?>>
                                                        <i class="fa fa-check me-2"></i> Approve
                                                    </button>
                                                </form>

                                                <!-- Reject Button -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="cancel_id" value="<?= $orderId; ?>">
                                                    <button type="submit" class="btn btn-danger text-white text-sm d-flex align-items-center"
                                                        <?= ($row['status'] === 'Cancelled') ? 'disabled' : ''; ?>>
                                                        <i class="fa fa-times me-2"></i> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="post">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Record Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row">
            <div class="col-sm-6 mb-20">
              <label class="form-label fw-semibold text-primary-light text-sm mb-8">Invoice No: <span class="text-danger-600">*</span></label>
              <input type="hidden" name="order_id" id="modal_order_id">
              <input type="text" class="form-control radius-8" name="invoice_no" id="modal_invoice_no" required readonly>
            </div>

            <div class="col-sm-6 mb-20">
              <label class="form-label fw-semibold text-primary-light text-sm mb-8">Payment Method <span class="text-danger-600">*</span></label>
              <select class="form-control" name="payment_method" id="modal_payment_method" required>
                <option value="">Select payment method</option>
                <option value="Cash">Cash</option>
                <option value="Card">Card</option>
                <option value="UPI">UPI</option>
                <option value="Bank">Bank</option>
              </select>
            </div>

            <div class="col-sm-6 mb-20">
              <label class="form-label fw-semibold text-primary-light text-sm mb-8">Enter Amount <span class="text-danger-600">*</span></label>

              <input type="hidden" name="payment_made" id="modal_payment_made">
              <input type="text" class="form-control radius-8" name="amount" id="modal_amount" required>
              <small id="amountError" class="text-danger d-none">Amount cannot be greater than Balance Due.</small>
              <input type="hidden" name="balance_due" id="modal_balance_due">
            </div>

            <div class="col-sm-6 mb-20">
              <label class="form-label fw-semibold text-primary-light text-sm mb-8">Remarks <span class="text-danger-600">*</span></label>
              <input type="text" class="form-control radius-8" name="remarks" id="modal_remarks" required>
            </div>

            <div class="col-12">
              <p id="fullyPaidMessage" class="text-danger d-none mt-2">Payment fully paid</p>
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" id="modal_submit" class="btn lufera-bg text-white" name="save">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>
 

<script>
    $(document).ready(function() {
        $('#userTable').DataTable();
    } );
    
(function () {
  // Allow only digits and one dot
  function sanitizeNumberInput(el) {
    el.value = el.value.replace(/[^0-9.]/g, '');
    el.value = el.value.replace(/(\..*)\./g, '$1');
  }

  var exampleModal = document.getElementById('exampleModal');

  exampleModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    if (!button) return;

    // Get data from the clicked button
    var invoice = button.getAttribute('data-invoice') || '';
    var orderId = button.getAttribute('data-order') || '';
    var balanceRaw = button.getAttribute('data-balance') || '0';
    var paymentRaw = button.getAttribute('data-payment') || '0';

    var balance = parseFloat(balanceRaw);
    if (isNaN(balance)) balance = 0;
    var payment = parseFloat(paymentRaw);
    if (isNaN(payment)) payment = 0;

    // Fill in modal fields
    document.getElementById('modal_invoice_no').value = invoice;
    document.getElementById('modal_order_id').value = orderId;
    document.getElementById('modal_balance_due').value = balance.toFixed(2);
    document.getElementById('modal_payment_made').value = payment.toFixed(2);
    document.getElementById('modal_amount').value = '';
    document.getElementById('modal_remarks').value = '';

    var amountInput = document.getElementById('modal_amount');
    var submitBtn = document.getElementById('modal_submit');
    var paymentMethod = document.getElementById('modal_payment_method');
    var fullyPaid = document.getElementById('fullyPaidMessage');
    var amountError = document.getElementById('amountError');

    // Disable everything if fully paid
    if (balance <= 0) {
      amountInput.setAttribute('readonly', 'readonly');
      paymentMethod.setAttribute('disabled', 'disabled');
      submitBtn.disabled = true;
      fullyPaid.classList.remove('d-none');
      amountError.classList.add('d-none');
    } else {
      amountInput.removeAttribute('readonly');
      paymentMethod.removeAttribute('disabled');
      submitBtn.disabled = false;
      fullyPaid.classList.add('d-none');
      amountError.classList.add('d-none');
    }

    // When user types amount
    amountInput.oninput = function () {
      sanitizeNumberInput(this);

      var entered = parseFloat(this.value || '0');
      if (isNaN(entered)) entered = 0;

      var newBalance = balance - entered;

      // Don’t allow negative balance
      if (newBalance < 0) {
        amountError.classList.remove('d-none');
        submitBtn.disabled = true;
        newBalance = 0; // optional — keep 0 in hidden field if overpaid
      } else {
        amountError.classList.add('d-none');
        submitBtn.disabled = false;
      }

      // Update hidden balance_due field live
      document.getElementById('modal_balance_due').value = newBalance.toFixed(2);
    };
  });

  // Cleanup when modal closes
  exampleModal.addEventListener('hidden.bs.modal', function () {
    document.getElementById('modal_amount').value = '';
    document.getElementById('modal_remarks').value = '';
    document.getElementById('amountError').classList.add('d-none');
    document.getElementById('fullyPaidMessage').classList.add('d-none');
    document.getElementById('modal_submit').disabled = false;
    document.getElementById('modal_payment_method').removeAttribute('disabled');
  });
})();
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