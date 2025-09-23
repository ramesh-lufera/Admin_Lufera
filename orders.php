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

        date_default_timezone_set('Asia/Kolkata');

        // Get order + user details
        $res = $conn->query("SELECT o.invoice_id, o.plan, o.amount, u.email, u.first_name, u.last_name 
                             FROM orders o 
                             INNER JOIN users u ON o.user_id = u.id 
                             WHERE o.id = $Id");
        $order = $res->fetch_assoc();

        $userEmail = $order['email'];
        $userName  = $order['first_name'] . " " . $order['last_name'];
        $planName  = $order['plan'];
        $invoiceId = $order['invoice_id'];
        $amount    = $order['amount'];

        // Get user_id for notification
        $res = $conn->query("SELECT user_id FROM orders WHERE id = $orderId");
        $user = $res->fetch_assoc();
        $userId = $user['user_id'];

        // Add notification
        $msg = "Your payment has been approved.";
        $createdAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $userId, $msg, $photo, $createdAt);
        $stmt->execute();

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
    
    // JOIN orders with users
    $query = "
        SELECT
            orders.id, 
            orders.invoice_id,
            orders.plan,
            orders.amount,
            orders.status,
            orders.created_on,
            users.username,
            users.first_name,
            users.last_name,
            users.photo
        FROM orders
        INNER JOIN users ON orders.user_id = users.id
    ";

    if ($role !== '1' && $role !== '2') {
        if (!empty($UserId)) {
            $query .= " WHERE orders.user_id = '$Id'";
        } else {
            $query .= " WHERE 1 = 0";
        }
    }

    $result = mysqli_query($conn, $query);
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-24">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            <h6 class="fw-semibold mb-0 m-auto">Orders</h6>
        </div>
        <div class="card">
            <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
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
                            <td class="text-center"><?php echo $row['invoice_id']; ?></td>
                            <td class="text-center"><?= date('d M Y', strtotime($row['created_on'])) ?></td>
                            <td class="text-center" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['amount'], 2) ?></td>
                           

                            <td class="text-center">
                                <?php if (($role === '1' || $role === '2') && $row['status'] === 'Pending'){ ?>
                                    <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                    <button class="btn btn-danger btn-sm fw-medium text-white me-2">
                                            New Order
                                    </button>
                                <?php } else if(($role != '1' || $role != '2') && $row['status'] === 'Pending'){ ?>
                                    <button class="btn btn-danger btn-sm fw-medium text-white me-2">
                                        Pending Confirmation
                                    </button>
                                <?php } ?>
                                <?php if (($role === '1' || $role === '2') && $row['status'] === 'Approved'){ ?>
                                    <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                    <button class="btn btn-success btn-sm fw-medium text-white me-2">
                                    Approved
                                    </button>
                                <?php } else if(($role != '1' || $role != '2') && $row['status'] === 'Approved'){ ?>
                                    <button class="btn btn-success btn-sm fw-medium text-white me-2">
                                    Approved
                                    </button>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <a href="order-summary.php?id=<?php echo $row['invoice_id']; ?>" class="fa fa-eye view-user-btn bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle">
                                </a>
                                <a href="invoice-preview.php?id=<?php echo $row['invoice_id']; ?>" class="fa fa-file view-user-btn bg-warning-focus text-warning-600 bg-hover-warning-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle">
                                </a>
                                <?php if (($role === '1' || $role === '2') && $row['status'] === 'Pending'){ ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="fa fa-check-square view-user-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle">
                                        
                                        </button>
                                    </form>
                                <?php } ?>
                               
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

</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>