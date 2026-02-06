<?php
    // Create a safe per-user key (no spaces, no symbols)
    $userKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_SESSION['username'] ?? 'guest');
?>

<?php $script = '<script src="assets/js/homeTwoChart.js"></script>';

    $script .= '<script>
            // ===================== Section Visibility Control =============================== 

            const SECTION_KEY = "dashboard_sections_<?php echo $userKey; ?>";

            // Ensure checkboxes + all sections are visible on load
            $(document).ready(function () {

                // Read saved layout for this user
                let savedSections = JSON.parse(localStorage.getItem(SECTION_KEY)) || [];

                $(".sec-check").each(function () {

                    let value = $(this).val();            // banner, stats, products
                    let sectionId = "#section-" + value;  // #section-banner etc

                    if (savedSections.includes(value)) {
                        $(this).prop("checked", true);
                        $(sectionId).show();
                    } else {
                        $(this).prop("checked", false);
                        $(sectionId).hide();
                    }

                });

            });

            $("#openSectionModal").on("click", function () {
                $("#sectionModal").modal("show");
            });

            $("#applySections").on("click", function () {

            let selectedSections = [];

            $(".sec-check").each(function () {

                let value = $(this).val();
                let sectionId = "#section-" + value;

                if ($(this).is(":checked")) {
                    $(sectionId).show();
                    selectedSections.push(value); // save
                } else {
                    $(sectionId).hide();
                }

            });

            // Save per user
            localStorage.setItem(SECTION_KEY, JSON.stringify(selectedSections));

            $("#sectionModal").modal("hide");
        });
</script>';?>

<?php include './partials/layouts/layoutTop.php';
    require_once __DIR__ . '/vendor/autoload.php';
    use Dotenv\Dotenv;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $Id = $_SESSION['user_id'];

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

    // Default role = normal user
    $userRole = 8;
    $userId   = null;

    if (!empty($_SESSION['username'])) {
        $uname = $conn->real_escape_string($_SESSION['username']);

        $res = $conn->query("
            SELECT id, role 
            FROM users 
            WHERE username = '$uname'
            LIMIT 1
        ");

        if ($res && $res->num_rows === 1) {
            $row      = $res->fetch_assoc();
            $userId   = (int)$row['id'];
            $userRole = (int)$row['role'];
        }
    }

    // Admin or Super Admin
    $isAdminUser = in_array($userRole, [1, 2]);

    // Defaults
    $userOrders = 0;
    $userSubs = 0;
    $userPackages = 0;
    $userProducts = 0;

    if (!$isAdminUser && isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];

        // Orders
        $userOrders = $conn->query("
            SELECT COUNT(*) c 
            FROM orders 
            WHERE user_id = $uid
        ")->fetch_assoc()['c'];

        // Subscriptions
        $userSubs = $conn->query("
            SELECT COUNT(*) c 
            FROM websites 
            WHERE user_id = $uid
        ")->fetch_assoc()['c'];

        // Packages (via websites.plan)
        $userPackages = $conn->query("
            SELECT COUNT(DISTINCT p.id) c
            FROM websites w
            JOIN package p ON p.id = w.plan
            WHERE w.user_id = $uid
        ")->fetch_assoc()['c'];

        // Products (via websites.plan)
        $userProducts = $conn->query("
            SELECT COUNT(DISTINCT pr.id) c
            FROM websites w
            JOIN products pr ON pr.id = w.plan
            WHERE w.user_id = $uid
        ")->fetch_assoc()['c'];
    }

    // Detect if real data exists
    $hasUserData = (
        $userOrders > 0 ||
        $userSubs > 0 ||
        $userPackages > 0 ||
        $userProducts > 0
    );

    // Total subscriptions
    $sub_total = $conn->query("SELECT COUNT(*) AS c FROM websites")->fetch_assoc()['c'];

    // Total orders
    $order_total = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];

    // Total users
    $user_total = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];

    // ================== THIS WEEK COUNTS ==================
    $sub_week = $conn->query("
        SELECT COUNT(*) AS c 
        FROM websites 
        WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)
    ")->fetch_assoc()['c'];

    $order_week = $conn->query("
        SELECT COUNT(*) AS c 
        FROM orders 
        WHERE YEARWEEK(created_on, 1) = YEARWEEK(NOW(), 1)
    ")->fetch_assoc()['c'];

    $user_week = $conn->query("
        SELECT COUNT(*) AS c 
        FROM users 
        WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)
    ")->fetch_assoc()['c'];

    // ================== MONTHLY STATS (NEW) ==================

    // New users this month
    $new_users_month = $conn->query("
        SELECT COUNT(*) AS c 
        FROM users
        WHERE MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
    ")->fetch_assoc()['c'];

    // New subscriptions this month
    $new_subs_month = $conn->query("
        SELECT COUNT(*) AS c 
        FROM websites
        WHERE MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
    ")->fetch_assoc()['c'];

    // Upcoming renewals in next 2 months
    $upcoming_renewals = $conn->query("
        SELECT COUNT(*) AS c
        FROM websites
        WHERE expired_at IS NOT NULL
        AND expired_at BETWEEN CURDATE() 
        AND DATE_ADD(CURDATE(), INTERVAL 2 MONTH)
    ")->fetch_assoc()['c'];

    // ========== IMAGE HANDLER (robust for subfolder setups) ==========
    function productImage($img)
    {
        $default = "assets/images/default-product.png";

        if (!$img || trim($img) === "") {
            return $default;
        }

        $img = trim($img);

        // If full URL (CDN, Google etc.)
        if (filter_var($img, FILTER_VALIDATE_URL)) {
            return $img;
        }

        // If path already contains uploads/products/
        if (strpos($img, "uploads/products/") === 0) {
            $candidate = $img;
        } else {
            // likely a filename only
            $candidate = "uploads/products/" . basename($img);
        }

        // Try a few relative locations to handle subfolder dashboard files.
        $attempts = [
            $candidate,                      // relative to current file: uploads/products/xxx
            "../" . $candidate,              // one level up: ../uploads/products/xxx
            "../../" . $candidate,           // two levels up
            rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $candidate // absolute server path
        ];

        foreach ($attempts as $p) {
            // If absolute server path
            if (strpos($p, $_SERVER['DOCUMENT_ROOT']) === 0) {
                if (file_exists($p)) {
                    // convert to web path relative to document root
                    return substr($p, strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/')) + 1);
                }
                continue;
            }

            // relative path check (relative to current PHP file)
            if (file_exists($p)) {
                return $p;
            }

            // If path looks like ../uploads/products/... but exists relative to document root
            $abs = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($p, '/');
            if (file_exists($abs)) {
                return ltrim($p, '/');
            }
        }

        // last-resort: try trimming and checking only filename at document root uploads/products/
        $final = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/products/' . basename($img);
        if (file_exists($final)) {
            return 'uploads/products/' . basename($img);
        }

        return $default;
    }

    // ========== CATEGORY FETCH =========
    $catQuery = $conn->query("
        SELECT DISTINCT category 
        FROM products 
        WHERE is_deleted = 0 AND is_active = 1
        ORDER BY category ASC
    ");

    $categories = [];
    while ($row = $catQuery->fetch_assoc()) {
        $categories[] = $row['category'];
    }

    // ========== FEATURED PRODUCTS FETCH ONLY ==========
    $prodQuery = $conn->query("
        SELECT * FROM products 
        WHERE is_deleted = 0 
        AND is_active = 1
        AND feature_item = 'Yes'
        ORDER BY id DESC
    ");

    $allProducts = [];
    while ($row = $prodQuery->fetch_assoc()) {
        $allProducts[] = $row;
    }

    // safe slugify (no iconv)
    function slugify($text)
    {
        // Replace non-alphanumeric with dashes
        $text = preg_replace('/[^A-Za-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return strtolower($text);
    }
?>

<?php
    // ===== Categories =====
    $categoriesTotal = $conn->query("SELECT COUNT(*) AS total FROM categories")->fetch_assoc()['total'];
    $categoriesWeek  = $conn->query("
        SELECT COUNT(*) AS total 
        FROM categories 
        WHERE created_at >= NOW() - INTERVAL 7 DAY
    ")->fetch_assoc()['total'];

    // ===== Subscriptions (websites) =====
    $websitesTotal = $conn->query("SELECT COUNT(*) AS total FROM websites")->fetch_assoc()['total'];
    $websitesWeek  = $conn->query("
        SELECT COUNT(*) AS total 
        FROM websites 
        WHERE created_at >= NOW() - INTERVAL 7 DAY
    ")->fetch_assoc()['total'];

    // ===== Orders =====
    $ordersTotal = $conn->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'];
    $ordersWeek  = $conn->query("
        SELECT COUNT(*) AS total 
        FROM orders 
        WHERE created_on >= NOW() - INTERVAL 7 DAY
    ")->fetch_assoc()['total'];

    $products = [];
    $result = $conn->query("SELECT name, price, product_image FROM products");

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    // ===== Latest Registered Users =====
    $latestUsers = [];
    $latestUsersResult = $conn->query("
        SELECT username, email, photo, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ");
    if ($latestUsersResult) {
        while ($row = $latestUsersResult->fetch_assoc()) {
            $latestUsers[] = $row;
        }
    }
    $latestUsersCount = $conn->query("SELECT COUNT(*) AS total FROM users")
                            ->fetch_assoc()['total'];


    // ===== Latest Subscribed Websites =====
    $latestWebsites = [];
    $latestWebsitesResult = $conn->query("
        SELECT w.created_at, u.username, u.email, u.photo
        FROM websites w
        JOIN users u ON u.id = w.user_id
        ORDER BY w.created_at DESC
        LIMIT 5
    ");
    if ($latestWebsitesResult) {
        while ($row = $latestWebsitesResult->fetch_assoc()) {
            $latestWebsites[] = $row;
        }
    }
    $latestWebsitesCount = $conn->query("SELECT COUNT(*) AS total FROM websites")
                                ->fetch_assoc()['total'];

    // ===== Revenue Growth (Orders) =====

    // ===== Revenue filters (price column only) =====

    // Today
    $revenueToday = $conn->query("
        SELECT COALESCE(SUM(price),0) AS total
        FROM orders
        WHERE DATE(created_on) = CURDATE()
    ")->fetch_assoc()['total'];

    // Weekly
    $revenueWeekly = $conn->query("
        SELECT COALESCE(SUM(price),0) AS total
        FROM orders
        WHERE created_on >= NOW() - INTERVAL 7 DAY
    ")->fetch_assoc()['total'];

    // Monthly
    $revenueMonthly = $conn->query("
        SELECT COALESCE(SUM(price),0) AS total
        FROM orders
        WHERE MONTH(created_on)=MONTH(CURDATE())
        AND YEAR(created_on)=YEAR(CURDATE())
    ")->fetch_assoc()['total'];

    // Yearly
    $revenueYearly = $conn->query("
        SELECT COALESCE(SUM(price),0) AS total
        FROM orders
        WHERE YEAR(created_on)=YEAR(CURDATE())
    ")->fetch_assoc()['total'];

    // ===== Earning Statistic Values (UPDATED TO NEW COLUMNS) =====

    // TODAY
    $sales_today   = $conn->query("SELECT COALESCE(SUM(price),0) t FROM orders WHERE DATE(created_on)=CURDATE()")->fetch_assoc()['t'];
    $paid_today    = $conn->query("SELECT COALESCE(SUM(payment_made),0) t FROM orders WHERE DATE(created_on)=CURDATE()")->fetch_assoc()['t'];
    $pending_today = $conn->query("SELECT COALESCE(SUM(balance_due),0) t FROM orders WHERE DATE(created_on)=CURDATE()")->fetch_assoc()['t'];

    // WEEKLY
    $sales_week   = $conn->query("SELECT COALESCE(SUM(price),0) t FROM orders WHERE created_on >= NOW()-INTERVAL 7 DAY")->fetch_assoc()['t'];
    $paid_week    = $conn->query("SELECT COALESCE(SUM(payment_made),0) t FROM orders WHERE created_on >= NOW()-INTERVAL 7 DAY")->fetch_assoc()['t'];
    $pending_week = $conn->query("SELECT COALESCE(SUM(balance_due),0) t FROM orders WHERE created_on >= NOW()-INTERVAL 7 DAY")->fetch_assoc()['t'];

    // MONTHLY
    $sales_month   = $conn->query("SELECT COALESCE(SUM(price),0) t FROM orders WHERE MONTH(created_on)=MONTH(CURDATE()) AND YEAR(created_on)=YEAR(CURDATE())")->fetch_assoc()['t'];
    $paid_month    = $conn->query("SELECT COALESCE(SUM(payment_made),0) t FROM orders WHERE MONTH(created_on)=MONTH(CURDATE()) AND YEAR(created_on)=YEAR(CURDATE())")->fetch_assoc()['t'];
    $pending_month = $conn->query("SELECT COALESCE(SUM(balance_due),0) t FROM orders WHERE MONTH(created_on)=MONTH(CURDATE()) AND YEAR(created_on)=YEAR(CURDATE())")->fetch_assoc()['t'];

    // YEARLY
    $sales_year   = $conn->query("SELECT COALESCE(SUM(price),0) t FROM orders WHERE YEAR(created_on)=YEAR(CURDATE())")->fetch_assoc()['t'];
    $paid_year    = $conn->query("SELECT COALESCE(SUM(payment_made),0) t FROM orders WHERE YEAR(created_on)=YEAR(CURDATE())")->fetch_assoc()['t'];
    $pending_year = $conn->query("SELECT COALESCE(SUM(balance_due),0) t FROM orders WHERE YEAR(created_on)=YEAR(CURDATE())")->fetch_assoc()['t'];

    // Total revenue (all time)
    $revenueTotal = $conn->query("
        SELECT COALESCE(SUM(price), 0) AS total 
        FROM orders
    ")->fetch_assoc()['total'];

    // Weekly revenue (last 7 days)
    // $revenueWeek = $conn->query("
    //     SELECT COALESCE(SUM(price), 0) AS total 
    //     FROM orders
    //     WHERE created_on >= NOW() - INTERVAL 7 DAY
    // ")->fetch_assoc()['total'];

    // ===== REAL WEEKLY DATA (last 7 days individually) =====

    $weeklyData = [];

    $weekResult = $conn->query("
        SELECT DATE(created_on) d, SUM(price) total
        FROM orders
        WHERE created_on >= CURDATE() - INTERVAL 6 DAY
        GROUP BY DATE(created_on)
    ");

    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $weeklyData[$day] = 0;
    }

    if ($weekResult) {
        while ($r = $weekResult->fetch_assoc()) {
            $weeklyData[$r['d']] = (float)$r['total'];
        }
    }

    $revenueWeekArray = array_values($weeklyData);

    // ===== REAL TODAY DATA (morning / afternoon / evening) =====

    $todayData = [0, 0, 0];

    $todayResult = $conn->query("
        SELECT HOUR(created_on) h, SUM(price) total
        FROM orders
        WHERE DATE(created_on)=CURDATE()
        GROUP BY HOUR(created_on)
    ");

    if ($todayResult) {
        while ($r = $todayResult->fetch_assoc()) {
            $h = (int)$r['h'];

            if ($h < 12) $todayData[0] += $r['total'];
            elseif ($h < 18) $todayData[1] += $r['total'];
            else $todayData[2] += $r['total'];
        }
    }

    // ===== REAL MONTHLY DATA (day by day) =====

    $monthlyData = [];

    $monthResult = $conn->query("
        SELECT DATE(created_on) d, SUM(price) total
        FROM orders
        WHERE MONTH(created_on)=MONTH(CURDATE())
        AND YEAR(created_on)=YEAR(CURDATE())
        GROUP BY DATE(created_on)
    ");

    $daysInMonth = date('t');

    for ($i = 1; $i <= $daysInMonth; $i++) {
        $day = date('Y-m-') . str_pad($i,2,'0',STR_PAD_LEFT);
        $monthlyData[$day] = 0;
    }

    if ($monthResult) {
        while ($r = $monthResult->fetch_assoc()) {
            $monthlyData[$r['d']] = (float)$r['total'];
        }
    }

    $revenueMonthArray = array_values($monthlyData);

    // Monthly revenue data for chart (current year)
    $revenueChart = [];
    $chartResult = $conn->query("
        SELECT 
            MONTH(created_on) AS month,
            SUM(price) AS total
        FROM orders
        WHERE YEAR(created_on) = YEAR(CURDATE())
        GROUP BY MONTH(created_on)
        ORDER BY MONTH(created_on)
    ");

    for ($i = 1; $i <= 12; $i++) {
        $revenueChart[$i] = 0;
    }

    if ($chartResult) {
        while ($row = $chartResult->fetch_assoc()) {
            $revenueChart[(int)$row['month']] = (float)$row['total'];
        }
    }

    $orders = [];

    $where = $isAdminUser ? "" : "WHERE o.user_id = $userId";

    $ordersResult = $conn->query("
        SELECT 
            o.id AS order_id,
            o.invoice_id,
            o.plan,
            o.type,
            o.price,
            o.status,
            o.created_on,
            o.payment_made,
            o.balance_due,

            u.id AS user_id,
            u.username,
            u.photo,
            u.business_name,


            w.created_at,
            w.expired_at,
            w.duration,
            w.renewal_duration,

            CASE 
                WHEN o.type = 'package' THEN pk.package_name
                WHEN o.type = 'product' THEN pr.name
            END AS plan_name

        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN package pk ON o.type = 'package' AND pk.id = o.plan
        LEFT JOIN products pr ON o.type = 'product' AND pr.id = o.plan
        LEFT JOIN websites w ON o.invoice_id = w.invoice_id

        $where
        ORDER BY o.created_on DESC
        LIMIT 5
    ");

    if ($ordersResult && $ordersResult->num_rows > 0) {
        while ($row = $ordersResult->fetch_assoc()) {
            $orders[] = $row;
        }
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
                text: 'Please wait while we approve your order.',
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
                    text: 'Please wait while we reject your order.',
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
        // $total_amount = $amount + $payment_made;
        $total_amount = floatval($payment_made) + floatval($amount);
        $created_at = date("Y-m-d H:i:s");
        $remarks = $_POST['remarks'];
        $balance_due = $_POST['balance_due'];
        $payment_id = generatePaymentID($conn);

        $sql = "INSERT INTO record_payment (payment_id, orders_id, invoice_no, payment_method, amount, balance, remarks, paid_date) 
                        VALUES ('$payment_id', '$order_id', '$invoice_no', '$payment_method', '$amount', '$balance_due', '$remarks', '$created_at')";
            
            if (mysqli_query($conn, $sql)) {

                // ✅ Check whether this invoice belongs to renewal_invoices or orders
                $renewalCheck = $conn->query("SELECT id FROM renewal_invoices WHERE invoice_id = '$invoice_no' LIMIT 1");

                    if ($renewalCheck && $renewalCheck->num_rows > 0) {
                        // 🔍 Get existing paid
                        $getOld = $conn->query("SELECT payment_made, balance_due FROM renewal_invoices WHERE invoice_id = '$invoice_no'");
                        $old = $getOld->fetch_assoc();
                        $old_paid = floatval($old['payment_made']);
                        $old_balance = floatval($old['balance_due']);

                        // 🔁 Add new payment
                        $total_amount = $old_paid + floatval($amount);

                        // 📌 Correct new balance = old balance - new amount paid
                        $new_balance = $old_balance - floatval($amount);
                        if ($new_balance < 0) { $new_balance = 0; }  // avoid negative

                        // 🆕 Update payment + balance properly
                        $updateSql = "
                            UPDATE renewal_invoices
                            SET payment_made = '$total_amount', balance_due = '$new_balance'
                            WHERE invoice_id = '$invoice_no'
                        ";
                        mysqli_query($conn, $updateSql);
                    }
                    
                    else {
                        $updateSql = "UPDATE orders
                                        SET payment_made = $total_amount, balance_due = $balance_due
                                        WHERE invoice_id = '$invoice_no'";
                        mysqli_query($conn, $updateSql);
                    }

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

<?php
    $uid = (int)$_SESSION['user_id'];

    /* ===== Active Subscriptions (this month) ===== */
    $activeSubsMonth = $conn->query("
        SELECT COUNT(*) c 
        FROM websites 
        WHERE user_id = $uid
        AND MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
    ")->fetch_assoc()['c'];

    /* ===== Upcoming Renewals (next 2 months) ===== */
    $upcomingUserRenewals = $conn->query("
        SELECT COUNT(*) c
        FROM websites
        WHERE user_id = $uid
        AND expired_at IS NOT NULL
        AND expired_at BETWEEN CURDATE() 
        AND DATE_ADD(CURDATE(), INTERVAL 2 MONTH)
    ")->fetch_assoc()['c'];

    /* ===== Pending Payments (this month non-zero balance) ===== */
    $pendingPaymentsMonth = $conn->query("
    SELECT 
        (
            SELECT COUNT(*) 
            FROM orders
            WHERE user_id = $uid
            AND balance_due > 0
            AND MONTH(created_on) = MONTH(CURDATE())
            AND YEAR(created_on) = YEAR(CURDATE())
        )
        +
        (
            SELECT COUNT(*) 
            FROM renewal_invoices
            WHERE user_id = $uid
            AND balance_due > 0
            AND MONTH(created_on) = MONTH(CURDATE())
            AND YEAR(created_on) = YEAR(CURDATE())
        ) AS c
    ")->fetch_assoc()['c'];
?>

<?php
    $uid = (int)$_SESSION['user_id'];

    $jsonRows = [];

    $resJson = $conn->query("
        SELECT template, name 
        FROM json 
        WHERE user_id = $uid
    ");

    if ($resJson) {
        while ($row = $resJson->fetch_assoc()) {

            $data = json_decode($row['name'], true);

            $totalFields  = 0;
            $filledFields = 0;

            if (is_array($data)) {
                foreach ($data as $item) {
                    if (isset($item['value'])) {
                        $totalFields++;

                        if (trim($item['value']) !== '') {
                            $filledFields++;
                        }
                    }
                }
            }

            // ✅ FIXED — matches wizard calculation (no rounding down)
            $percentage = $totalFields > 0
                ? ceil(($filledFields / $totalFields) * 100)
                : 0;

            $jsonRows[] = [
                'template'   => $row['template'],
                'percentage'=> $percentage
            ];
        }
    }
?>

<?php
    function templateIcon($name) {

        $name = strtolower($name);

        if (str_contains($name, 'email'))
            return ['icon'=>'majesticons:mail','text'=>'text-orange','bar'=>'bg-orange'];

        if (str_contains($name, 'website'))
            return ['icon'=>'eva:globe-2-fill','text'=>'text-success-main','bar'=>'bg-success-main'];

        if (str_contains($name, 'marketing'))
            return ['icon'=>'fa6-brands:square-facebook','text'=>'text-info-main','bar'=>'bg-info-main'];

        if (str_contains($name, 'onboard'))
            return ['icon'=>'fluent:location-off-20-filled','text'=>'text-indigo','bar'=>'bg-indigo'];

        return ['icon'=>'solar:layers-bold','text'=>'text-secondary-main','bar'=>'bg-secondary-main'];
    }
?>

<script>
    window.revenueData = {
    yearly: <?php echo json_encode(array_values($revenueChart)); ?>,
    monthly: <?php echo json_encode($revenueMonthArray); ?>,
    weekly: <?php echo json_encode($revenueWeekArray); ?>,
    today: <?php echo json_encode($todayData); ?>
    };

    window.revenueTotals = {
    yearly: <?= $revenueYearly ?>,
    monthly: <?= $revenueMonthly ?>,
    weekly: <?= $revenueWeekly ?>,
    today: <?= $revenueToday ?>
    };

    window.earningStats = {
    yearly: { sales: <?= $sales_year ?>, paid: <?= $paid_year ?>, pending: <?= $pending_year ?> },
    monthly:{ sales: <?= $sales_month ?>, paid: <?= $paid_month ?>, pending: <?= $pending_month ?> },
    weekly: { sales: <?= $sales_week ?>, paid: <?= $paid_week ?>, pending: <?= $pending_week ?> },
    today:  { sales: <?= $sales_today ?>, paid: <?= $paid_today ?>, pending: <?= $pending_today ?> }
    };
</script>

<style>
    /* Make checkboxes visible (theme hides them) */
    .sec-check {
        display: inline-block !important;
        opacity: 1 !important;
        visibility: visible !important;
        width: 16px;
        height: 16px;
        accent-color: #007bff;
    }

    /* Align checkbox + text in one line */
    .form-check {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 0;
        padding: 0;
    }

    .form-check-label {
        cursor: pointer;
        margin: 0;
        padding: 0;
        line-height: 1.3 !important;
    }
    #userOverviewDonutChart {
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>

<style>
    /* ===== Card Themes ===== */
    .card-yellow { background-color: #FFFBEB !important; }
    .card-blue   { background-color: #EFF6FF !important; }
    .card-green  { background-color: #ECFDF5 !important; }

    /* ===== Icon Themes ===== */
    .icon-yellow {
        background-color: #FEF3C7 !important;
        color: #B45309 !important;
    }

    .icon-blue {
        background-color: #DBEAFE !important;
        color: #1D4ED8 !important;
    }

    .icon-green {
        background-color: #D1FAE5 !important;
        color: #047857 !important;
    }

    .btn-yellow {
        background-color: #fec700 !important;
        border-color: #fec700 !important;
        color: #111 !important;
    }

    .btn-yellow:hover {
        background-color: #e6b800 !important;
        border-color: #e6b800 !important;
        color: #111 !important;
    }

    /* ===== Earning Statistic Yellow Theme ===== */

    /* Hover background */
    .group-item:hover {
        border-color: #fec700 !important;
    }

    /* Icon hover background */
    .group-item:hover .test{
        background-color: #fec700 !important;
        color: #111 !important;
    }

    /* Text hover (optional but consistent) */
    .group-item:hover h6,
    .group-item:hover span.text-secondary-light {
        color: #111 !important;
    }
</style>

<style>
    /* ===== Featured Products Scroll ===== */
    .products-scroll {
        max-height: 280px; /* shows ~2 product rows */
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 6px;
    }

    .products-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .products-scroll::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 6px;
    }
</style>

<style>
    /* Show full product image without cropping */
    .product-img-box {
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f9fafb;
    }

    .product-img-box img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }
</style>

<style>
    /* ===== FORCE white section background ===== */
    #section-products {
        background: #ffffff !important;
        border-radius: 18px;
        padding: 22px 22px 26px;
        border: 1px solid #e5e7eb;
    }

    /* ===== Featured Products title ===== */
    #section-products h6.mb-0 {
        font-size: 20px !important;
        font-weight: 600 !important;
        line-height: 1.3 !important;
        margin: 0 !important;
        display: flex;
        align-items: center;
    }

    /* ===== Fix header row alignment ===== */
    #section-products > div:first-child {
        align-items: center !important;
    }

    /* ===== Professional product cards ===== */
    #section-products .nft-card.bg-base {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        transition: all 0.25s ease;
        overflow: hidden;
    }

    #section-products .nft-card.bg-base:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 22px rgba(0,0,0,0.12);
    }

    /* ===== Image area ===== */
    #section-products .product-img-box {
        background: #ffffff !important;
        border-bottom: 1px solid #e5e7eb;
    }

    /* ===== Buy button ===== */
    #section-products .btn-primary-600 {
        background-color: #fec700 !important;
        border-color: #fec700 !important;
        color: #111 !important;
        width: 100% !important;
    }

    #section-products .btn-primary-600:hover {
        background-color: #e6b800 !important;
        border-color: #e6b800 !important;
        color: #111 !important;
    }

    /* ===== Empty message ===== */
    #section-products .no-products {
        text-align: center;
        padding: 24px;
        font-size: 14px;
        color: #6b7280;
    }
</style>

<style>
    #section-subscription .card-body{
        padding: 32px !important;
    }

    #section-subscription h6{
        font-size: 20px !important;
        margin-bottom: 20px !important;
    }

    .subscription-title{
        font-size: 15px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #section-subscription .text-xxl{
        font-size: 26px !important;
    }

    #section-subscription .progress{
        height: 10px;
        background:#eceef2;
    }

    #section-subscription .progress-bar{
        height: 10px;
    }

    #section-subscription .mb-12{
        margin-bottom: 18px !important;
    }

    #section-subscription .font-xs{
        font-size: 14px !important;
        font-weight: 600;
    }
</style>

<div class="dashboard-main-body nft-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <!-- Bind the username dynamically.. -->
        <?php if (isset($_SESSION['username'])): ?>
            <h6 class="fw-semibold mb-0">Hello <?php echo $_SESSION['username']; ?>!</h6>
        <?php else: ?>
            <h6 class="fw-semibold mb-0">Hello none!</h6>
        <?php endif; ?>

        <button class="btn  rounded-pill px-20 py-8 lufera-bg" id="openSectionModal">
            + Create Section
        </button>

        <!-- <h6 class="fw-semibold mb-0">Dashboard</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">NFT & Gaming </li>
        </ul> -->
    </div>

    <div class="row gy-4">
        <div class="col-xxl-8">
            <div class="row gy-4">
                <?php if ($isAdminUser): ?>
                    <div class="col-12" id="section-banner">
                        <div class="nft-promo-card card radius-12 overflow-hidden position-relative z-1">
                            <img src="assets/images/yb-background.avif" class="position-absolute start-0 top-0 w-100 h-100 z-n1" alt="">
                            <div class="nft-promo-card__inner d-flex align-items-center">
                                <div class="nft-promo-card__thumb w-100">
                                    <img src="assets/images/laptop.webp" alt="" class="w-100 h-100 object-fit-cover">
                                </div>
                                <div class="flex-grow-1">
                                    <h4 class="mb-16 text-white">Discover Our Lufera Marketplace</h4>
                                    <p class="text-white text-md">Your one-stop destination for unique products and unbeatable deals. Browse a vibrant marketplace filled with trusted sellers and handpicked selections—all in one place.</p>
                                    <!-- <div class="d-flex align-items-center flex-wrap mt-24 gap-16">
                                        <a href="#" class="btn rounded-pill border br-white text-white radius-8 px-32 py-11 hover-bg-white text-hover-neutral-900">Explore</a>
                                        <a href="#" class="btn rounded-pill btn-primary-600 radius-8 px-28 py-11">Create Now</a>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$isAdminUser): ?>
                    <div class="col-12" id="section-banner1">
                        <div class="nft-promo-card card radius-12 overflow-hidden position-relative z-1">
                            <img src="assets/images/yb-background.avif" class="position-absolute start-0 top-0 w-100 h-100 z-n1" alt="">
                            <div class="nft-promo-card__inner d-flex align-items-center">
                                <div class="nft-promo-card__thumb w-100">
                                    <img src="assets/images/user-dashboard.png" alt="" class="w-100 h-100 object-fit-cover">
                                </div>
                                <div class="flex-grow-1">
                                    <h4 class="mb-16 text-white">Discover Our Lufera Marketplace</h4>
                                    <p class="text-white text-md">Your one-stop destination for unique products and unbeatable deals. Browse a vibrant marketplace filled with trusted sellers and handpicked selections—all in one place.</p>
                                    <!-- <div class="d-flex align-items-center flex-wrap mt-24 gap-16">
                                        <a href="#" class="btn rounded-pill border br-white text-white radius-8 px-32 py-11 hover-bg-white text-hover-neutral-900">Explore</a>
                                        <a href="#" class="btn rounded-pill btn-primary-600 radius-8 px-28 py-11">Create Now</a>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($isAdminUser): ?>
                    <div class="col-12" id="section-stats">
                        <h6 class="mb-16">Trending Stats</h6>

                        <div class="row gy-4">

                            <!-- NEW USERS -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 radius-12 border h-100"
                                    style="background:#f0f6ff;border:1px solid #dbe4f3;">
                                    <div class="card-body p-0">
                                        <div class="d-flex gap-16 align-items-center">

                                            <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                                style="background:#dce7ff;color:#111827;">
                                                <iconify-icon icon="solar:user-plus-bold"></iconify-icon>
                                            </span>

                                            <div class="flex-grow-1">
                                                <h6 class="fw-semibold mb-0"><?= $new_users_month ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">
                                                    New Users
                                                </span>

                                                <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                    <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                        style="background:#e7f9e7;color:#166534;">
                                                        +<?= $new_users_month ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    This month
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- NEW SUBSCRIPTIONS -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 radius-12 border h-100"
                                    style="background:#fffbea;border:1px solid #f5e8ae;">
                                    <div class="card-body p-0">
                                        <div class="d-flex gap-16 align-items-center">

                                            <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                                style="background:#fff1b8;color:#111827;">
                                                <iconify-icon icon="solar:shield-check-bold"></iconify-icon>
                                            </span>

                                            <div class="flex-grow-1">
                                                <h6 class="fw-semibold mb-0"><?= $new_subs_month ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">
                                                    New Subscriptions
                                                </span>

                                                <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                    <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                        style="background:#e7f9e7;color:#166534;">
                                                        +<?= $new_subs_month ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    This month
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- UPCOMING RENEWALS -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 radius-12 border h-100"
                                    style="background:#f9fafb;border:1px solid #e5e7eb;">
                                    <div class="card-body p-0">
                                        <div class="d-flex gap-16 align-items-center">

                                            <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                                style="background:#e8e9ec;color:#111827;">
                                                <iconify-icon icon="solar:calendar-bold"></iconify-icon>
                                            </span>

                                            <div class="flex-grow-1">
                                                <h6 class="fw-semibold mb-0"><?= $upcoming_renewals ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">
                                                    Upcoming Renewals
                                                </span>

                                                <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                    <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                        style="background:#e7f9e7;color:#166534;">
                                                        +<?= $upcoming_renewals ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    Expiring soon
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$isAdminUser): ?>
                    <div class="col-12" id="section-stats1">
                        <h6 class="mb-16">Trending Stats</h6>

                        <div class="row gy-4">

                            <!-- ACTIVE SUBSCRIPTIONS -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 shadow-none radius-12 border h-100 card-yellow">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center gap-16">
                                            <span class="w-40-px h-40-px icon-yellow d-flex justify-content-center align-items-center rounded-circle h6">
                                                <iconify-icon icon="solar:layers-bold"></iconify-icon>
                                            </span>
                                            <div>
                                                <h6 class="fw-semibold mb-0">
                                                    <?= number_format($activeSubsMonth) ?>
                                                </h6>
                                                <span class="fw-medium text-secondary-light text-md">
                                                    Active Subscriptions
                                                </span>
                                                <p class="text-sm mt-12 mb-0 d-flex align-items-center gap-8">
                                                    <span class="bg-success-focus px-6 py-2 rounded-2 text-success-main fw-medium">
                                                        +<?= $activeSubsMonth ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    This month
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- UPCOMING RENEWALS -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 shadow-none radius-12 border h-100 card-blue">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center gap-16">
                                            <span class="w-40-px h-40-px icon-blue d-flex justify-content-center align-items-center rounded-circle h6">
                                                <iconify-icon icon="solar:shield-check-bold"></iconify-icon>
                                            </span>
                                            <div>
                                                <h6 class="fw-semibold mb-0">
                                                    <?= number_format($upcomingUserRenewals) ?>
                                                </h6>
                                                <span class="fw-medium text-secondary-light text-md">
                                                    Upcoming Renewals
                                                </span>
                                                <p class="text-sm mt-12 mb-0 d-flex align-items-center gap-8">
                                                    <span class="bg-success-focus px-6 py-2 rounded-2 text-success-main fw-medium">
                                                        +<?= $upcomingUserRenewals ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    Upcoming 2 months
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PENDING PAYMENTS -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 shadow-none radius-12 border h-100 card-green">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center gap-16">
                                            <span class="w-40-px h-40-px icon-green d-flex justify-content-center align-items-center rounded-circle h6">
                                                <iconify-icon icon="solar:cart-large-bold"></iconify-icon>
                                            </span>
                                            <div>
                                                <h6 class="fw-semibold mb-0">
                                                    <?= number_format($pendingPaymentsMonth) ?>
                                                </h6>
                                                <span class="fw-medium text-secondary-light text-md">
                                                    Pending Payments
                                                </span>
                                                <p class="text-sm mt-12 mb-0 d-flex align-items-center gap-8">
                                                    <span class="bg-success-focus px-6 py-2 rounded-2 text-success-main fw-medium">
                                                        +<?= $pendingPaymentsMonth ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    This month
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($isAdminUser): ?>
                    <!-- Earning Statistic start -->
                        <div class="col-12" id="section-earning">
                            <div class="card h-100 radius-8 border-0">
                                <div class="card-body p-24">

                                    <!-- Header -->
                                    <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                                        <div>
                                            <h6 class="mb-2 fw-bold text-lg">Earning Statistic</h6>
                                            <span class="text-sm fw-medium text-secondary-light">
                                                Revenue overview by period
                                            </span>
                                        </div>

                                        <!-- Dropdown -->
                                        <div>
                                            <select id="earningFilter" class="form-select form-select-sm w-auto bg-base border text-secondary-light">
                                                <option value="yearly" selected>Yearly</option>
                                                <option value="monthly">Monthly</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="today">Today</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Stat Cards -->
                                    <div class="mt-20 d-flex justify-content-center flex-wrap gap-3">

                                        <!-- TOTAL SALES -->
                                        <div class="d-inline-flex align-items-center gap-2 p-2 radius-8 border pe-36 br-hover-primary group-item">
                                            <span class="bg-neutral-100 w-44-px h-44-px text-xxl radius-8 d-flex justify-content-center align-items-center text-secondary-light test">
                                                <iconify-icon icon="fluent:cart-16-filled"></iconify-icon>
                                            </span>
                                            <div>
                                                <span class="text-secondary-light text-sm fw-medium">Total Sales</span>
                                                <h6 class="text-md fw-semibold mb-0" id="salesValue">
                                                    ₹<?= number_format($sales_year, 2) ?>
                                                </h6>
                                            </div>
                                        </div>

                                        <!-- PAYMENTS RECEIVED -->
                                        <div class="d-inline-flex align-items-center gap-2 p-2 radius-8 border pe-36 br-hover-primary group-item">
                                            <span class="bg-neutral-100 w-44-px h-44-px text-xxl radius-8 d-flex justify-content-center align-items-center text-secondary-light test">
                                                <iconify-icon icon="uis:chart"></iconify-icon>
                                            </span>
                                            <div>
                                                <span class="text-secondary-light text-sm fw-medium">Payments Received</span>
                                                <h6 class="text-md fw-semibold mb-0" id="paidValue">
                                                    ₹<?= number_format($paid_year, 2) ?>
                                                </h6>
                                            </div>
                                        </div>

                                        <!-- PENDING PAYMENTS -->
                                        <div class="d-inline-flex align-items-center gap-2 p-2 radius-8 border pe-36 br-hover-primary group-item">
                                            <span class="bg-neutral-100 w-44-px h-44-px text-xxl radius-8 d-flex justify-content-center align-items-center text-secondary-light test">
                                                <iconify-icon icon="ph:arrow-fat-up-fill"></iconify-icon>
                                            </span>
                                            <div>
                                                <span class="text-secondary-light text-sm fw-medium">Pending Payments</span>
                                                <h6 class="text-md fw-semibold mb-0" id="pendingValue">
                                                    ₹<?= number_format($pending_year, 2) ?>
                                                </h6>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Bar Chart -->
                                    <div id="barChart" class="barChart mt-24"></div>

                                </div>
                            </div>
                        </div>
                    <!-- Earning Statistic end -->
                <?php endif; ?>

                <div class="col-12" id="section-recent">
                    <div class="card h-100">
                        <div class="card-body p-24">

                            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-20">
                                <h6 class="mb-0 fw-bold text-lg">Recent Orders</h6>
                                <a href="subscription.php"
                                class="text-primary-600 hover-text-primary d-flex align-items-center gap-1">
                                    View All
                                    <iconify-icon icon="solar:alt-arrow-right-linear"></iconify-icon>
                                </a>
                            </div>

                            <div class="table-responsive scroll-sm">
                                <table class="table bordered-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Users</th>
                                            <th>Invoice</th>
                                            <th>Items</th>
                                            <th>Amount</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (!empty($orders)): ?>
                                        <?php foreach ($orders as $order): ?>

                                        <tr>
                                            <!-- Users -->
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img
                                                        src="<?php echo !empty($order['photo']) ? $order['photo'] : 'assets/images/users/default.png'; ?>"
                                                        class="w-40-px h-40-px rounded-circle me-12 object-fit-cover"
                                                        alt="User"
                                                    >
                                                    <span class="text-secondary-light fw-semibold">
                                                        <?php echo htmlspecialchars($order['username']); ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Invoice -->
                                            <td>#<?php echo htmlspecialchars($order['invoice_id']); ?></td>

                                            <!-- Items -->
                                            <td><?php echo htmlspecialchars($order['plan_name']); ?></td>

                                            <!-- Amount -->
                                            <td>₹<?php echo number_format($order['price'], 2); ?></td>

                                            <!-- Status -->
                                            <td class="text-center">
                                                <?php
                                                    switch ($order['status']) {
                                                        case 'Approved':
                                                            echo '<span class="bg-success-focus text-success-main px-24 py-4 rounded-pill fw-medium text-sm">Approved</span>';
                                                            break;

                                                        case 'Pending':
                                                            echo '<span class="bg-warning-focus text-warning-main px-24 py-4 rounded-pill fw-medium text-sm">Pending</span>';
                                                            break;

                                                        case 'Expired':
                                                            echo '<span class="bg-info-focus text-info-main px-24 py-4 rounded-pill fw-medium text-sm">Expired</span>';
                                                            break;

                                                        case 'Cancelled':
                                                            echo '<span class="bg-danger-focus text-danger-main px-24 py-4 rounded-pill fw-medium text-sm">Cancelled</span>';
                                                            break;

                                                        default:
                                                            echo '<span class="bg-neutral-200 text-neutral-600 px-24 py-4 rounded-pill fw-medium text-sm">Unknown</span>';
                                                    }
                                                ?>
                                            </td>

                                            <!-- Action -->
                                            <td class="text-center">
                                                <a class="fa fa-chevron-right ms-10 text-sm lufera-color"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#offcanvas-<?php echo $order['order_id']; ?>">
                                                    </a>

                                            </td>
                                        </tr>
                                        <?php
                                        // $createdOn = new DateTime($order['created_on']);
                                        // $duration  = $order['duration'];
                                        // $expiryDate = (clone $createdOn)->modify("+$duration");
                                        // $expiryFormatted = $expiryDate->format("Y-m-d");

                                        if (!empty($order['expired_at']) && $order['expired_at'] !== '0000-00-00 00:00:00') {
                                            // Renewal case: use expired_at from websites table
                                            $expiryFormatted = date("Y-m-d", strtotime($order['expired_at']));
                                        } else {
                                            // Normal case: calculate expiry based on created_on + duration
                                            $createdOn = new DateTime($order['created_on']);
                                            $duration  = $order['duration'];
                                            $expiryDate = (clone $createdOn)->modify("+$duration");
                                            $expiryFormatted = $expiryDate->format("Y-m-d");
                                        }

                                        $orderId = $order['order_id']; // unique identifier

                                        $statusColor = "";
                                        $statusText = "";

                                        if ($order['status'] === 'Approved') {
                                            $statusColor = "text-warning"; // yellow
                                            $statusText = "Approved";
                                        } elseif ($order['status'] === 'Cancelled') {
                                            $statusColor = "text-danger"; // red
                                            $statusText = "Rejected";
                                        }
                                    ?>
                                        <!-- unique offcanvas for this row -->
                                    <div class="offcanvas offcanvas-end" id="offcanvas-<?php echo $orderId; ?>">
                                        <div class="offcanvas-header pb-0">
                                            <h6>Subscription details</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                                        </div>
                                        <div class="offcanvas-body">
                                            <h6 class="text-lg"><?php echo htmlspecialchars($order['plan_name']); ?></h6>
                                            <p class="text-sm"><?php echo htmlspecialchars($order['business_name']); ?></p>
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
                                            <!-- <div class="d-flex justify-content-between my-3">
                                                <span>Renewal price</span>
                                                <span></span>
                                            </div>
                                            <hr /> -->
                                            <!-- <div class="d-flex justify-content-between my-3">
                                                <span>Next billing period</span>
                                                <span><?php echo $duration; ?></span>
                                            </div> -->
                                            <div class="d-flex justify-content-between my-3">
                                                <span>Period</span>
                                                <span>
                                                    <?php
                                                        if (!empty($order['renewal_duration'])) {
                                                            echo htmlspecialchars($order['renewal_duration']);
                                                        } else {
                                                            echo htmlspecialchars($duration);
                                                        }
                                                    ?>
                                                </span>
                                            </div>
                                            <hr /> 
                                            <div class="d-flex justify-content-between my-3">
                                                <span>Auto renewal</span>
                                                <span>Off</span>
                                            </div>
                                            <hr />
                                            
                                            <h6 class="text-md mt-20">ADD-ONS</h6>
                                            <?php
                                                if (!empty($order['addon_service'])) {
                                                    $addon_ids = explode(",", $order['addon_service']);
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
                                                $invoice_id = $order['invoice_id'];
                                                $id = $order['order_id'];
                                                $payment_made = $order['payment_made'];
                                                $balance_due = $order['balance_due'];

                                                $todayDate = date('Y-m-d');

                                                // Website fields already fetched from main SQL JOIN
                                                $createdAt = $order['created_at'];        
                                                $durationStr = $order['duration'];        
                                                $expiredAt = $order['expired_at'];        

                                                $isRenewal = false;

                                                // Ensure created_at and duration exist
                                                if (!empty($createdAt) && !empty($durationStr)) {

                                                    // Calculate (created_at + duration)
                                                    $expiryCalc = date('Y-m-d', strtotime("$createdAt + $durationStr"));

                                                    // Condition 1: today > created_at + duration
                                                    $cond1 = ($todayDate > $expiryCalc);

                                                    // Condition 2: expired_at is NOT NULL/0000
                                                    $cond2 = (!empty($expiredAt) && $expiredAt != '0000-00-00 00:00:00');

                                                    // Condition 3: expired_at > created_at + duration
                                                    $cond3 = ($expiredAt > $expiryCalc);

                                                    // Apply renewal rule
                                                    if ($cond1 && $cond2 && $cond3) {
                                                        $isRenewal = true;
                                                    }
                                                }

                                                // If renewal → use renewal invoice_id
                                                if ($isRenewal) {
                                                    $userId = $order['user_id'];
                                                    $planId = $order['plan'];

                                                    $renewalSql = "
                                                        SELECT invoice_id 
                                                        FROM renewal_invoices 
                                                        WHERE user_id = '$userId' 
                                                        AND plan = '$planId'
                                                        ORDER BY id DESC 
                                                        LIMIT 1
                                                    ";

                                                    $renewalResult = $conn->query($renewalSql);
                                                    if ($renewalResult && $renewalResult->num_rows > 0) {
                                                        $renewalRow = $renewalResult->fetch_assoc();
                                                        $invoice_id = $renewalRow['invoice_id']; 
                                                    }
                                                }

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

                                            <?php
                                                /* ---- get web_id, prod_id, duration ---- */
                                                $webId = 0;
                                                $inv   = $order['invoice_id'];
                                                $webQ  = $conn->query("SELECT id FROM websites WHERE invoice_id = '$inv' LIMIT 1");
                                                if ($webQ && $webQ->num_rows) {
                                                    $webId = $webQ->fetch_assoc()['id'];
                                                }
                                                $dur   = $order['duration'];
                                                $parts = explode(' ', trim($dur));
                                                $num   = $parts[0] ?? 1;
                                                $unit  = ($parts[1] ?? 'year') === 'year' ? 'years' : 'months';
                                                $durationStr = "$num $unit";
                                                $prodId = $order['plan'];
                                            ?>
                                            <!-- ... existing table row ... -->

                                            <div class="mt-20">
                                                <?php if($role == "1" || $role == "2") {?>  
                                                    <?php
                                                        // Determine renewal conditions using websites table values already in $order
                                                        $todayDate     = date('Y-m-d');
                                                        $createdAt     = $order['created_at'];   // from websites table
                                                        $durationStr   = $order['duration'];     // from websites table
                                                        $expiredAt     = $order['expired_at'];   // from websites table

                                                        // Default: normal record payment flow
                                                        $invoiceToUse       = $order['invoice_id'];
                                                        $paymentMadeToUse   = $order['payment_made'];
                                                        $balanceDueToUse    = $order['balance_due'];

                                                        $isRenewal = false;

                                                        // Ensure created_at & duration exist before calculations
                                                        if (!empty($createdAt) && !empty($durationStr)) {

                                                            // Calculate (created_at + duration)
                                                            $expiryCalc = date('Y-m-d', strtotime("$createdAt + $durationStr"));

                                                            // Condition 1: today > (created_at + duration)
                                                            $cond1 = ($todayDate > $expiryCalc);

                                                            // Condition 2: expired_at is not null/zero
                                                            $cond2 = (!empty($expiredAt) && $expiredAt != '0000-00-00 00:00:00');

                                                            // Condition 3: expired_at > (created_at + duration)
                                                            $cond3 = ($expiredAt > $expiryCalc);

                                                            // Only if all 3 conditions true → renewal mode
                                                            if ($cond1 && $cond2 && $cond3) {
                                                                $isRenewal = true;
                                                            }
                                                        }

                                                        // If renewal → fetch renewal invoice and override payment fields
                                                        if ($isRenewal) {
                                                            $renewalCheck = $conn->query("
                                                                SELECT invoice_id
                                                                FROM renewal_invoices
                                                                WHERE user_id = '{$order['user_id']}'
                                                                AND plan = '{$order['plan']}'
                                                                ORDER BY id DESC
                                                                LIMIT 1
                                                            ");

                                                            if ($renewalCheck && $renewalCheck->num_rows > 0) {
                                                                $renewal = $renewalCheck->fetch_assoc();
                                                                $invoiceToUse = $renewal['invoice_id'];

                                                                // Reset payment for renewal period
                                                                $paymentMadeToUse = 0;
                                                                $balanceDueToUse  = $order['amount'];
                                                            }
                                                        }
                                                    ?>
                                                    <button class="btn text-white btn-primary text-sm mb-10 record-payment-btn"
                                                            data-bs-toggle="modal" data-bs-target="#exampleModal"
                                                            data-invoice="<?= htmlspecialchars($invoiceToUse) ?>"
                                                            data-order="<?= htmlspecialchars($order['order_id']) ?>"
                                                            data-balance="<?= htmlspecialchars($balanceDueToUse) ?>"
                                                            data-payment="<?= htmlspecialchars($paymentMadeToUse) ?>">
                                                        Record Payment
                                                    </button>
                                                <?php } ?>

                                                <button class="btn text-white lufera-bg text-sm mb-10">Renew</button>

                                                <!-- *** UPGRADE BUTTON *** -->
                                                <a href="upgrade_plan.php?web_id=<?= $webId ?>&prod_id=<?= $prodId ?>&duration=<?= $duration ?>">
                                                    <button class="btn text-white btn-warning text-sm mb-10">Upgrade</button>
                                                </a>

                                                <!-- <a href="invoice-preview.php?id=<?=$order['invoice_id']?>">
                                                    <button class="btn text-white btn-success text-sm mb-10">Invoice</button>
                                                </a> -->

                                                <!-- <a href="order-summary.php?id=<?=$order['invoice_id']?>">
                                                    <button class="btn text-white btn-danger text-sm mb-10">View More</button>
                                                </a> -->

                                                <?php
                                                    // Renewal logic using websites table values already in $order
                                                    $todayDate   = date('Y-m-d');
                                                    $createdAt   = $order['created_at'];     // from websites table
                                                    $durationStr = $order['duration'];       // from websites table
                                                    $expiredAt   = $order['expired_at'];     // from websites table

                                                    $isRenewal = false;

                                                    // Ensure required fields exist
                                                    if (!empty($createdAt) && !empty($durationStr)) {

                                                        // Calculate (created_at + duration)
                                                        $expiryCalc = date('Y-m-d', strtotime("$createdAt + $durationStr"));

                                                        // Condition 1: today > (created_at + duration)
                                                        $cond1 = ($todayDate > $expiryCalc);

                                                        // Condition 2: expired_at is not null/zero
                                                        $cond2 = (!empty($expiredAt) && $expiredAt != '0000-00-00 00:00:00');

                                                        // Condition 3: expired_at > (created_at + duration)
                                                        $cond3 = ($expiredAt > $expiryCalc);

                                                        if ($cond1 && $cond2 && $cond3) {
                                                            $isRenewal = true;
                                                        }
                                                    }

                                                    // Output invoice button URL based on renewal condition
                                                    if ($isRenewal) {
                                                    ?>
                                                        <a href="invoice-preview.php?id=<?php echo $invoice_id; ?>&type=renewal">
                                                            <button class="btn text-white btn-success text-sm mb-10">Invoice</button>
                                                        </a>

                                                        <a href="order-summary.php?id=<?php echo $invoice_id; ?>&type=renewal">
                                                            <button class="btn text-white btn-danger text-sm mb-10">View More</button>
                                                        </a>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <a href="invoice-preview.php?id=<?php echo $invoice_id; ?>&type=normal">
                                                            <button class="btn text-white btn-success text-sm mb-10">Invoice</button>
                                                        </a>

                                                        <a href="order-summary.php?id=<?php echo $invoice_id; ?>&type=normal">
                                                            <button class="btn text-white btn-danger text-sm mb-10">View More</button>
                                                        </a>
                                                    <?php
                                                    }
                                                ?>
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
                                                                <?= ($order['status'] === 'Approved') ? 'disabled' : ''; ?>>
                                                                <i class="fa fa-check me-2"></i> Approve
                                                            </button>
                                                        </form>

                                                        <!-- Reject Button -->
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="cancel_id" value="<?= $orderId; ?>">
                                                            <button type="submit" class="btn btn-danger text-white text-sm d-flex align-items-center"
                                                                <?= ($order['status'] === 'Cancelled') ? 'disabled' : ''; ?>>
                                                                <i class="fa fa-times me-2"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                    </div>

                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6"
                                                class="text-center py-24 text-secondary-light fw-medium">
                                                No records found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="row gy-4">
                <?php if ($isAdminUser): ?>
                    <!-- Revenue Growth start -->
                    <div class="col-xxl-12 col-md-6" id="section-growth">
                        <div class="card h-100 radius-8 border">
                            <div class="card-body p-24">

                                <!-- Row 1: title left, dropdown right -->
                                <div class="d-flex justify-content-between align-items-center mb-8">

                                    <h6 class="fw-bold text-lg mb-0">
                                        Orders Growth
                                    </h6>

                                    <select id="revenueFilter"
                                        class="form-select form-select-sm w-auto bg-base border text-secondary-light">
                                        <option value="yearly">Yearly</option>
                                        <option value="monthly" selected>Monthly</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="today">Today</option>
                                    </select>

                                </div>

                                <!-- Row 2: amount right aligned -->
                                <div class="d-flex justify-content-end mb-12">
                                    <h6 class="fw-bold text-lg mb-0" id="revenueTotalText">
                                        ₹<?php echo number_format($revenueMonthly, 2); ?>
                                    </h6>
                                </div>

                                <!-- Chart -->
                                <div id="revenue-chart" class="mt-28"></div>

                            </div>
                        </div>
                    </div>
                    <!-- Revenue Growth End -->
                <?php endif; ?>

                <?php if (!$isAdminUser): ?>
                    <div class="col-xxl-12 col-sm-6" id="section-subscription">
                        <div class="card h-100 radius-8 border-0">
                            <div class="card-body">

                                <!-- TITLE -->
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="fw-bold text-lg">Subscriptions</h6>
                                </div>

                                <div class="mt-3">

                                    <?php if (empty($jsonRows)): ?>

                                        <!-- EMPTY STATE -->
                                        <div class="text-center text-secondary-light py-32 fw-medium">
                                            No Subscriptions Found
                                        </div>

                                    <?php else: ?>

                                        <?php foreach ($jsonRows as $row): 
                                            $style = templateIcon($row['template']);
                                        ?>

                                        <div class="d-flex align-items-center justify-content-between gap-4 mb-12">

                                            <!-- LEFT SIDE -->
                                            <div class="d-flex align-items-center">
                                                <span class="text-xxl line-height-1 d-flex align-content-center flex-shrink-0 <?= $style['text'] ?>">
                                                    <iconify-icon icon="<?= $style['icon'] ?>" class="icon"></iconify-icon>
                                                </span>

                                                <span class="text-primary-light fw-medium text-sm ps-12 subscription-title">
                                                    <?= htmlspecialchars(
                                                        ucwords(str_replace(['_','-'],' ', $row['template']))
                                                    ) ?>
                                                </span>
                                            </div>

                                            <!-- RIGHT SIDE -->
                                            <div class="d-flex align-items-center gap-2 w-100">

                                                <div class="w-100 max-w-66 ms-auto">
                                                    <div class="progress progress-sm rounded-pill">
                                                        <div class="progress-bar <?= $style['bar'] ?> rounded-pill"
                                                            style="width: <?= $row['percentage'] ?>%;">
                                                        </div>
                                                    </div>
                                                </div>

                                                <span class="text-secondary-light font-xs fw-semibold">
                                                    <?= $row['percentage'] ?>%
                                                </span>

                                            </div>
                                        </div>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </div>

                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-xxl-12" id="section-products">
                    <div class="mb-16 mt-8 d-flex flex-wrap justify-content-between gap-16 align-items-center">
                        <h6 class="mb-0">Featured Products</h6>

                        <ul class="nav button-tab nav-pills mb-16 gap-12">
                            <li class="nav-item">
                                <button class="nav-link active fw-semibold text-secondary-light rounded-pill px-20 py-6 border"
                                        data-bs-toggle="pill" data-bs-target="#tab-all">
                                    All
                                </button>
                            </li>

                            <?php foreach ($categories as $cat): ?>
                                <?php $slug = slugify($cat); ?>
                                <li class="nav-item">
                                    <button class="nav-link fw-semibold text-secondary-light rounded-pill px-20 py-6 border"
                                            data-bs-toggle="pill" data-bs-target="#tab-<?= $slug ?>">
                                        <?= htmlspecialchars($cat) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="tab-content">

                        <!-- ================= ALL FEATURED ================= -->
                        <div class="tab-pane fade show active" id="tab-all">
                            <div class="products-scroll">
                                <div class="row g-3">

                                    <?php if (empty($allProducts)): ?>
                                        <div class="no-products">No products found</div>
                                    <?php endif; ?>

                                    <?php foreach ($allProducts as $p): ?>
                                        <?php $img = productImage($p['product_image']); ?>

                                        <div class="col-xxl-6 col-lg-6 col-md-6 col-sm-12">
                                            <div class="nft-card bg-base p-0 d-flex flex-column">

                                                <div class="product-img-box">
                                                    <img src="<?= $img ?>"
                                                        onerror="this.src='assets/images/default-product.png'">
                                                </div>

                                                <div class="p-10 d-flex flex-column">
                                                    <h6 class="text-md fw-bold mb-1">
                                                        <?= htmlspecialchars($p['title']) ?>
                                                    </h6>

                                                    <?php if (!empty($p['subtitle'])): ?>
                                                        <p class="text-xs text-secondary-light mb-1">
                                                            <?= htmlspecialchars($p['subtitle']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <div class="mt-8 d-flex justify-content-between text-sm">
                                                        <span>Price: <b>₹<?= number_format($p['price']) ?></b></span>
                                                        <span class="fw-semibold"><?= htmlspecialchars($p['category']) ?></span>
                                                    </div>

                                                    <div class="mt-12">
                                                        <a href="product-details.php?id=<?= $p['id'] ?>"
                                                        class="btn btn-primary-600 rounded-pill">
                                                            Buy
                                                        </a>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                </div>
                            </div>
                        </div>

                        <!-- ================= CATEGORY WISE ================= -->
                        <?php foreach ($categories as $cat): ?>
                        <?php $slug = slugify($cat); ?>

                        <div class="tab-pane fade" id="tab-<?= $slug ?>">
                            <div class="products-scroll">
                                <div class="row g-3">

                                    <?php
                                        $found = false;
                                        foreach ($allProducts as $p):
                                        if ($p['category'] !== $cat) continue;
                                        $found = true;
                                        $img = productImage($p['product_image']);
                                    ?>

                                    <div class="col-xxl-6 col-lg-6 col-md-6 col-sm-12">
                                        <div class="nft-card bg-base p-0 d-flex flex-column">

                                            <div class="product-img-box">
                                                <img src="<?= $img ?>"
                                                    onerror="this.src='assets/images/default-product.png'">
                                            </div>

                                            <div class="p-10 d-flex flex-column">
                                                <h6 class="text-md fw-bold mb-1">
                                                    <?= htmlspecialchars($p['title']) ?>
                                                </h6>

                                                <?php if (!empty($p['subtitle'])): ?>
                                                    <p class="text-xs text-secondary-light mb-1">
                                                        <?= htmlspecialchars($p['subtitle']) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <div class="mt-8 d-flex justify-content-between text-sm">
                                                    <span>Price: <b>₹<?= number_format($p['price']) ?></b></span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($p['category']) ?></span>
                                                </div>

                                                <div class="mt-12">
                                                    <a href="product-details.php?id=<?= $p['id'] ?>"
                                                    class="btn btn-primary-600 rounded-pill">
                                                        Buy
                                                    </a>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <?php endforeach; ?>

                                    <?php if (!$found): ?>
                                        <div class="no-products">No products found</div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>

                        <?php endforeach; ?>

                    </div>
                </div>

                <?php if ($isAdminUser): ?>
                    <div class="col-xxl-12 col-md-12" id="section-users">
                        <div class="card h-100 shadow-sm radius-12 border-0">

                            <!-- UPDATED TITLE -->
                            <div class="card-header border-bottom-0 py-16 px-24 d-flex align-items-center justify-content-between" style="background:#f8fafc;">
                                <h6 class="fw-bold text-lg mb-0">New Users</h6>
                                <a href="#" class="text-primary-600 hover-text-primary d-flex align-items-center gap-1">
                                    View All
                                    <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                                </a>
                            </div>

                            <div class="card-body p-24">
                                <div class="row gy-4">

                                    <?php
                                        // Fetch latest users
                                        $users = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 4");

                                        // FIXED: Google, Facebook, Local, Uploads photos
                                        function userPhoto($value)
                                        {
                                            $default = "assets/images/default-user.png";

                                            if (!$value) return $default;

                                            // ⭐ FIX: Remove invisible spaces, newlines, tabs
                                            $value = trim($value);

                                            // 1. Valid full URL (Google, Facebook, any HTTP/HTTPS)
                                            if (filter_var($value, FILTER_VALIDATE_URL)) {
                                                return $value;
                                            }

                                            // 2. Google photos missing protocol
                                            if (strpos($value, '//lh3.googleusercontent.com') === 0) {
                                                return "https:" . $value;
                                            }

                                            // 3. Facebook photos missing protocol
                                            if (strpos($value, '//graph.facebook.com') === 0) {
                                                return "https:" . $value;
                                            }

                                            // 4. Local assets folder (assets/test.jpg)
                                            if (strpos($value, "assets/") === 0) {
                                                return $value;
                                            }

                                            // 5. Local uploads
                                            $clean = ltrim($value, '/');
                                            $absolute = $_SERVER['DOCUMENT_ROOT'] . '/' . $clean;

                                            if (file_exists($absolute)) {
                                                return $clean;
                                            }

                                            // 6. Default placeholder
                                            return $default;
                                        }

                                        if ($users->num_rows > 0):
                                            while ($u = $users->fetch_assoc()):
                                                $photo = userPhoto($u['photo']);
                                    ?>

                                    <!-- USER CARD -->
                                    <div class="col-12">
                                        <div class="d-flex bg-white radius-12 border shadow-sm p-16 align-items-center"
                                            style="border-color:#e1e5eb; min-height:120px;">

                                            <!-- USER PHOTO (REDUCED SIZE) -->
                                            <div class="me-20">
                                                <img src="<?= htmlspecialchars($photo) ?>"
                                                    class="rounded-4 shadow-sm object-fit-cover"
                                                    style="width:70px; height:70px;"
                                                    alt="User Photo">
                                            </div>

                                            <!-- USER DETAILS -->
                                            <div class="flex-grow-1">

                                                <h6 class="fw-semibold mb-2" style="font-size:13px;">
                                                    <?= htmlspecialchars($u['username']) ?>
                                                </h6>

                                                <p class="text-secondary-light mb-6" style="font-size:11px;">
                                                    <?= htmlspecialchars($u['email']) ?>
                                                </p>

                                                <!-- USER TAGS -->
                                                <div class="d-flex align-items-center gap-2 mb-4">

                                                    <span class="px-10 py-3 bg-primary-50 text-primary-600 rounded-pill fw-semibold"
                                                        style="font-size:10px;">
                                                        ID: <?= $u['id'] ?>
                                                    </span>

                                                    <span class="px-10 py-3 bg-neutral-100 text-neutral-700 rounded-pill fw-semibold"
                                                        style="font-size:10px;">
                                                        Joined: <?= date("M d, Y", strtotime($u['created_at'])) ?>
                                                    </span>

                                                </div>

                                            </div>

                                        </div>
                                    </div>

                                    <?php endwhile; else: ?>

                                    <!-- NO USERS FOUND -->
                                    <div class="col-12 text-center text-secondary-light py-20">
                                        No users found.
                                    </div>

                                    <?php endif; ?>

                                </div>
                            </div>

                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL START ===================== -->
<div class="modal fade" id="sectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content radius-12 p-20">

      <h5 class="fw-semibold mb-16" style="font-size: 1.25rem !important">+ Add Section</h5>

      <div class="d-flex flex-column gap-12">

        <?php if ($isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="banner" id="adminBanner" checked>
                <label class="form-check-label" for="adminBanner">Lufera Banner</label>
            </div>
        <?php endif; ?>

        <?php if (!$isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="banner1" id="userBanner" checked>
                <label class="form-check-label" for="userBanner">Lufera Banner</label>
            </div>
        <?php endif; ?>

        <?php if ($isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="stats" id="chkStats" checked>
                <label class="form-check-label" for="chkStats">Trending Stats</label>
            </div>
        <?php endif; ?>

        <?php if (!$isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="stats1" id="chkStats12" checked>
                <label class="form-check-label" for="chkStats12">Trending Stats</label>
            </div>
        <?php endif; ?>

        <?php if ($isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="earning" id="ear" checked>
                <label class="form-check-label" for="ear">Earning Statistic</label>
            </div>
        <?php endif; ?>

        <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="recent" id="chkRecentOrders" checked>
          <label class="form-check-label" for="chkRecentOrders">Recent Orders</label>
        </div>

        <?php if ($isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="growth" id="ordersGrowth" checked>
                <label class="form-check-label" for="ordersGrowth">Orders Growth</label>
            </div>
        <?php endif; ?>

        <?php if (!$isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="subscription" id="showSubscription" checked>
                <label class="form-check-label" for="showSubscription">Subscription</label>
            </div>
        <?php endif; ?>

        <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="products" id="chkProducts" checked>
          <label class="form-check-label" for="chkProducts">Featured Products</label>
        </div>

        <?php if ($isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="users" id="chkUsers" checked>
                <label class="form-check-label" for="chkUsers">New Users</label>
            </div>
        <?php endif; ?>
      </div>

      <div class="mt-20 d-flex justify-content-end gap-12">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn lufera-bg" id="applySections">Apply</button>
      </div>

    </div>
  </div>
</div>
<!-- ===================== MODAL END ===================== -->

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
            $('#inactiveUserTable').DataTable();
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

<?php include './partials/layouts/layoutBottom.php' ?>