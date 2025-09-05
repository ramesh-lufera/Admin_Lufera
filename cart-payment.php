<?php include './partials/layouts/layoutTop.php';
      
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
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $receipt_id = $_POST['receipt_id'];

        // $gst = $price * 0.18; // 10% GST
        // $total_price = $price + $gst;

        $gst = $total_price - $price;
        $created_on = $_POST['created_on'];
    }

    // Get active symbol
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row = $result1->fetch_assoc()) {
        $symbol = $row['symbol'];
    }

    if (isset($_POST['save'])) {
        $product_id = $_POST['id'];
        $type = $_POST['type'];
        $pay_method = $_POST['pay_method'];
        $receipt_id = $_POST['receipt_id'];
        $plan_name = $_POST['plan_name'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $created_at = date("Y-m-d H:i:s");
        $price = $_POST['price'];
        $gst = $_POST['gst'];
        $discount = $payment_made = "0";

        $user_id = $_SESSION['user_id'];

        $sql    = "SELECT user_id, email, username FROM users WHERE id = $user_id LIMIT 1";
        $result2 = mysqli_query($conn, $sql);
        $row    = mysqli_fetch_assoc($result2);
        $client_id = $row['user_id'];
        $toEmail   = $row['email'];     // purchaser email
        $username  = $row['username'];  // purchaser username
        $toName    = $row['username'];
 
        $sql = "INSERT INTO orders (user_id, invoice_id, plan, duration, amount, gst, price, status, payment_method, discount, payment_made, created_on, subtotal, balance_due) VALUES 
                ('$client_id', '$receipt_id', '$plan_name', '$duration' ,'$total_price', '$gst', '$price', 'Pending', '$pay_method', '$discount', '$payment_made', '$created_at', '$total_price', '$total_price')";

        if (mysqli_query($conn, $sql)) {
            // Generate a domain from the username
            // $domain = strtolower(preg_replace('/\s+/', '', $username)) . ".lufera.com";

            $domain = "N/A";

            // Insert new website record
            $siteInsert = "INSERT INTO websites (user_id, domain, plan, duration, status, cat_id, invoice_id, product_id, type) 
                        VALUES ('$client_id', '$domain', '$plan_name', '$duration', 'Pending', '$cat_id', '$receipt_id', '$product_id', '$type')";
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
                        window.location.href = 'orders.php';
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
                            <p class="mb-0">Perfect plan to get started for your own Website</p>
                        </div>
                        <div class="card-body p-16">
                            <table class="plan-details-table mb-0 w-100">
                                <tbody>
                                    <tr>
                                        <td>Period</td>
                                        <td><?php echo $duration; ?></td>
                                    </tr>
                                    <tr>
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
                                    </tr>
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
                                <h6 class="mb-0">Sub Total</h6>
                                <p class="mb-0">Sub total does not include applicable taxes</p>
                            </div>
                            <div class="align-content-center">
                                <h4 class="mb-0" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?php echo $price; ?></h4>
                            </div>
                        </div>
                        <div class="card-body p-16">
                            <table class="plan-details-table mb-0 w-100">
                                <tbody>
                                    <tr>
                                        <td>Discount</td>
                                        <td class="text-end">N/A</td>
                                    </tr>
                                    <tr>
                                        <td>Tax (GST 18%)</td>
                                        <td class="text-end" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?php echo $gst; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="border-0">Estimated Total</td>
                                        <td class="border-0 text-end" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?php echo $total_price; ?></td>
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
                loadUserData(); // Reload user data after update
            },
            error: function(xhr) {
                $('#result').html("Error updating data.");
            }
        });
    });

    loadUserData();
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