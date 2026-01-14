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

    // ========== ALL PRODUCTS FETCH =========
    $prodQuery = $conn->query("
        SELECT * FROM products 
        WHERE is_deleted = 0 AND is_active = 1 
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

    // Total revenue (all time)
    $revenueTotal = $conn->query("
        SELECT COALESCE(SUM(price), 0) AS total 
        FROM orders
    ")->fetch_assoc()['total'];

    // Weekly revenue (last 7 days)
    $revenueWeek = $conn->query("
        SELECT COALESCE(SUM(price), 0) AS total 
        FROM orders
        WHERE created_on >= NOW() - INTERVAL 7 DAY
    ")->fetch_assoc()['total'];

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

    $ordersResult = $conn->query("
        SELECT 
            o.invoice_id,
            o.plan,
            o.type,
            o.price,
            o.status,
            u.username,
            u.photo,
            CASE 
                WHEN o.type = 'package' THEN pk.package_name
                WHEN o.type = 'product' THEN pr.name
            END AS item_name
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN package pk 
            ON o.type = 'package' AND pk.id = o.plan
        LEFT JOIN products pr 
            ON o.type = 'product' AND pr.id = o.plan
        ORDER BY o.created_on DESC
        LIMIT 5
    ");

    if ($ordersResult && $ordersResult->num_rows > 0) {
        while ($row = $ordersResult->fetch_assoc()) {
            $orders[] = $row;
        }
    }
?>

<script>
    window.revenueChartData = <?php echo json_encode(array_values($revenueChart)); ?>;
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

                <?php if ($isAdminUser): ?>
                    <div class="col-12" id="section-stats">
                        <h6 class="mb-16">Trending Stats</h6>

                        <div class="row gy-4">

                            <!-- ================= CARD 1: TOTAL SUBSCRIPTIONS ================= -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 radius-12 border h-100"
                                    style="background:#f0f6ff; border:1px solid #dbe4f3;"> <!-- Soft blue tint -->
                                    <div class="card-body p-0">
                                        <div class="d-flex gap-16 align-items-center">

                                            <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                                style="background:#dce7ff; color:#111827;"> <!-- Light blue circle + black icon -->
                                                <iconify-icon icon="flowbite:users-group-solid" class="icon"></iconify-icon>
                                            </span>

                                            <div class="flex-grow-1">
                                                <h6 class="fw-semibold mb-0"><?= $sub_total ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">Total Subscriptions</span>

                                                <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                    <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                        style="background:#e7f9e7; color:#166534;">
                                                        +<?= $sub_week ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    This week
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ================= CARD 2: TOTAL ORDERS ================= -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 radius-12 border h-100"
                                    style="background:#fffbea; border:1px solid #f5e8ae;"> <!-- Soft yellow warm tint -->
                                    <div class="card-body p-0">
                                        <div class="d-flex gap-16 align-items-center">

                                            <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                                style="background:#fff1b8; color:#111827;"> <!-- Yellow circle + black icon -->
                                                <iconify-icon icon="solar:cart-5-bold" class="icon"></iconify-icon>
                                            </span>

                                            <div class="flex-grow-1">
                                                <h6 class="fw-semibold mb-0"><?= $order_total ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">Total Orders</span>

                                                <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                    <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                        style="background:#ffe2e2; color:#b91c1c;">
                                                        +<?= $order_week ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    This week
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ================= CARD 3: TOTAL USERS ================= -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 radius-12 border h-100"
                                    style="background:#f9fafb; border:1px solid #e5e7eb;"> <!-- Light gray neutral theme -->
                                    <div class="card-body p-0">
                                        <div class="d-flex gap-16 align-items-center">

                                            <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                                style="background:#e8e9ec; color:#111827;"> <!-- Neutral circle + black icon -->
                                                <iconify-icon icon="solar:user-rounded-bold" class="icon"></iconify-icon>
                                            </span>

                                            <div class="flex-grow-1">
                                                <h6 class="fw-semibold mb-0"><?= $user_total ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">Total Users</span>

                                                <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                    <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                        style="background:#e7f9e7; color:#166534;">
                                                        +<?= $user_week ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    This week
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

                            <!-- Categories -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 shadow-none radius-12 border h-100 card-yellow">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center gap-16">
                                            <span class="w-40-px h-40-px icon-yellow d-flex justify-content-center align-items-center rounded-circle h6">
                                                <iconify-icon icon="solar:layers-bold"></iconify-icon>
                                            </span>
                                            <div>
                                                <h6 class="fw-semibold mb-0"><?php echo number_format($categoriesTotal); ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">Categories</span>
                                                <p class="text-sm mt-12 mb-0 d-flex align-items-center gap-8">
                                                    <span class="bg-success-focus px-6 py-2 rounded-2 text-success-main fw-medium">
                                                        +<?php echo $categoriesWeek; ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    Last week
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subscriptions -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 shadow-none radius-12 border h-100 card-blue">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center gap-16">
                                            <span class="w-40-px h-40-px icon-blue d-flex justify-content-center align-items-center rounded-circle h6">
                                                <iconify-icon icon="solar:shield-check-bold"></iconify-icon>
                                            </span>
                                            <div>
                                                <h6 class="fw-semibold mb-0"><?php echo number_format($websitesTotal); ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">Subscriptions</span>
                                                <p class="text-sm mt-12 mb-0 d-flex align-items-center gap-8">
                                                    <span class="bg-success-focus px-6 py-2 rounded-2 text-success-main fw-medium">
                                                        +<?php echo $websitesWeek; ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    Last week
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Orders -->
                            <div class="col-lg-4 col-sm-6">
                                <div class="card px-24 py-16 shadow-none radius-12 border h-100 card-green">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center gap-16">
                                            <span class="w-40-px h-40-px icon-green d-flex justify-content-center align-items-center rounded-circle h6">
                                                <iconify-icon icon="solar:cart-large-bold"></iconify-icon>
                                            </span>
                                            <div>
                                                <h6 class="fw-semibold mb-0"><?php echo number_format($ordersTotal); ?></h6>
                                                <span class="fw-medium text-secondary-light text-md">Orders</span>
                                                <p class="text-sm mt-12 mb-0 d-flex align-items-center gap-8">
                                                    <span class="bg-success-focus px-6 py-2 rounded-2 text-success-main fw-medium">
                                                        +<?php echo $ordersWeek; ?>
                                                        <i class="ri-arrow-up-line"></i>
                                                    </span>
                                                    Last week
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
                    <!-- Earning Static start -->
                    <div class="col-12" id="section-earning">
                        <div class="card h-100 radius-8 border-0">
                            <div class="card-body p-24">
                                <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                                    <div>
                                        <h6 class="mb-2 fw-bold text-lg">Earning Statistic</h6>
                                        <span class="text-sm fw-medium text-secondary-light">
                                            Yearly earning overview
                                        </span>
                                    </div>
                                    <div>
                                        <select class="form-select form-select-sm w-auto bg-base border text-secondary-light">
                                            <option>Yearly</option>
                                            <option>Monthly</option>
                                            <option>Weekly</option>
                                            <option>Today</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-20 d-flex justify-content-center flex-wrap gap-3">

                                    <div class="d-inline-flex align-items-center gap-2 p-2 radius-8 border pe-36 br-hover-primary group-item">
                                        <span class="bg-neutral-100 w-44-px h-44-px text-xxl radius-8 d-flex justify-content-center align-items-center text-secondary-light test">
                                            <iconify-icon icon="fluent:cart-16-filled" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="text-secondary-light text-sm fw-medium">Sales</span>
                                            <h6 class="text-md fw-semibold mb-0">$200k</h6>
                                        </div>
                                    </div>

                                    <div class="d-inline-flex align-items-center gap-2 p-2 radius-8 border pe-36 br-hover-primary group-item">
                                        <span class="bg-neutral-100 w-44-px h-44-px text-xxl radius-8 d-flex justify-content-center align-items-center text-secondary-light test">
                                            <iconify-icon icon="uis:chart" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="text-secondary-light text-sm fw-medium">Income</span>
                                            <h6 class="text-md fw-semibold mb-0">$2100k</h6>
                                        </div>
                                    </div>

                                    <div class="d-inline-flex align-items-center gap-2 p-2 radius-8 border pe-36 br-hover-primary group-item">
                                        <span class="bg-neutral-100 w-44-px h-44-px text-xxl radius-8 d-flex justify-content-center align-items-center text-secondary-light test">
                                            <iconify-icon icon="ph:arrow-fat-up-fill" class="icon"></iconify-icon>
                                        </span>
                                        <div>
                                            <span class="text-secondary-light text-sm fw-medium">Profit</span>
                                            <h6 class="text-md fw-semibold mb-0">$2600k</h6>
                                        </div>
                                    </div>

                                </div>

                                <div id="barChart" class="barChart"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Earning Static End -->
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
                                            <th>Qty</th>
                                            <th>Amount</th>
                                            <th class="text-center">Status</th>
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
                                            <td><?php echo htmlspecialchars($order['item_name']); ?></td>

                                            <!-- Qty -->
                                            <td>1</td>

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
                                        </tr>

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
                <!-- Revenue Growth start -->
                <div class="col-xxl-12 col-md-6" id="section-growth">
                    <div class="card h-100 radius-8 border">
                        <div class="card-body p-24">
                            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                                <div>
                                    <h6 class="mb-2 fw-bold text-lg">Orders Growth</h6>
                                    <span class="text-sm fw-medium text-secondary-light">
                                        Weekly Report
                                    </span>
                                </div>
                                <div class="text-end">
                                    <h6 class="mb-2 fw-bold text-lg">
                                        ₹<?php echo number_format($revenueTotal, 2); ?>
                                    </h6>
                                    <span class="bg-success-focus ps-12 pe-12 pt-2 pb-2 rounded-2 fw-medium text-success-main text-sm">
                                        ₹<?php echo number_format($revenueWeek / 1000, 1); ?>k
                                    </span>
                                </div>
                            </div>

                            <div id="revenue-chart" class="mt-28"></div>
                        </div>
                    </div>
                </div>
                <!-- Revenue Growth End -->

                <div class="col-xxl-12" id="section-products">
                    <div class="mb-16 mt-8 d-flex flex-wrap justify-content-between gap-16 align-items-center">
                        <h6 class="mb-0">Featured Products</h6>

                        <ul class="nav button-tab nav-pills mb-16 gap-12">
                            <li class="nav-item">
                                <button class="nav-link active fw-semibold text-secondary-light rounded-pill px-20 py-6 border"
                                        data-bs-toggle="pill" data-bs-target="#tab-all">All</button>
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

                        <!-- ALL PRODUCTS -->
                        <div class="tab-pane fade show active" id="tab-all">
                            <div class="products-scroll">
                                <div class="row g-3">

                                    <?php foreach ($allProducts as $p): ?>
                                        <?php $img = productImage($p['product_image']); ?>

                                        <div class="col-xxl-6 col-lg-6 col-md-6 col-sm-12">
                                            <div class="nft-card bg-base radius-16 overflow-hidden p-0"
                                                style="display:flex; flex-direction:column;">

                                                <!-- ✅ FIXED IMAGE -->
                                                <div class="product-img-box">
                                                    <img src="<?= $img ?>"
                                                        alt="Product Image"
                                                        onerror="this.src='assets/images/default-product.png'">
                                                </div>

                                                <!-- CONTENT -->
                                                <div class="p-10 d-flex flex-column">
                                                    <h6 class="text-md fw-bold text-primary-light mb-1">
                                                        <?= htmlspecialchars($p['title']) ?>
                                                    </h6>

                                                    <?php if (!empty($p['subtitle'])): ?>
                                                        <p class="text-xs text-secondary-light mb-1">
                                                            <?= htmlspecialchars($p['subtitle']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <div class="mt-8 d-flex justify-content-between text-sm">
                                                        <span>
                                                            Price:
                                                            <b>₹<?= number_format($p['price']) ?></b>
                                                        </span>
                                                        <span class="text-primary-600 fw-semibold">
                                                            <?= htmlspecialchars($p['category']) ?>
                                                        </span>
                                                    </div>

                                                    <div class="d-flex gap-8 mt-12">
                                                        <a href="product-details.php?id=<?= $p['id'] ?>"
                                                        class="btn rounded-pill border flex-grow-1">
                                                            View
                                                        </a>
                                                        <a href="buy.php?id=<?= $p['id'] ?>"
                                                        class="btn rounded-pill btn-primary-600 flex-grow-1">
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

                    </div>
                </div>

                <!-- <div class="col-xxl-12 col-md-6" id="section-recentOrders">
                    <div class="card h-100 shadow-sm radius-12 border-0" style="overflow:hidden;">
                        
                        <div class="card-header border-bottom-0 py-16 px-24 d-flex align-items-center justify-content-between"
                            style="background:#f8fafc;">
                            <h6 class="fw-bold text-lg mb-0">Recent Orders</h6>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0 align-middle" 
                                    style="border-collapse: separate; border-spacing: 0 6px;">

                                    <thead>
                                        <tr class="text-secondary-light text-sm">
                                            <th class="px-24 py-12">Order ID</th>
                                            <th class="py-12">User</th>
                                            <th class="py-12">Amount</th>
                                            <th class="py-12">Status</th>
                                            <th class="py-12 pe-24">Date</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php 
                                            $orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
                                            if ($orders->num_rows > 0):
                                                while ($o = $orders->fetch_assoc()):
                                        ?>

                                        <tr class="bg-white shadow-xs radius-8"
                                            style="transition:0.2s; cursor:pointer;"
                                            onmouseover="this.style.transform='scale(1.01)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.06)'"
                                            onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">

                                            <td class="px-24 py-14 fw-semibold">#<?= $o['id'] ?></td>
                                            <td class="fw-medium"><?= $o['username'] ?? 'N/A' ?></td>
                                            <td class="fw-semibold text-primary-600"><?= $o['amount'] ?? '0' ?></td>

                                            <td>
                                                <?php 
                                                    $status = strtolower($o['status'] ?? 'pending');
                                                    $badgeColor = [
                                                        'pending' => 'bg-warning-100 text-warning-700',
                                                        'success' => 'bg-success-100 text-success-700',
                                                        'failed'  => 'bg-danger-100 text-danger-700',
                                                    ][$status] ?? 'bg-neutral-200 text-neutral-700';
                                                ?>
                                                <span class="px-12 py-4 rounded-pill fw-semibold text-sm <?= $badgeColor ?>">
                                                    <?= ucfirst($status) ?>
                                                </span>
                                            </td>

                                            <td class="pe-24"><?= $o['created_on'] ?></td>
                                        </tr>

                                        <?php 
                                                endwhile;
                                            else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-20 text-secondary-light">
                                                No orders found.
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>

                                </table>
                            </div>
                        </div>

                    </div>
                </div> -->

                <?php if ($isAdminUser): ?>
                    <div class="col-xxl-12 col-md-12" id="section-users">
                        <div class="card h-100 shadow-sm radius-12 border-0">

                            <!-- UPDATED TITLE -->
                            <div class="card-header border-bottom-0 py-16 px-24 d-flex align-items-center justify-content-between" style="background:#f8fafc;">
                                <h6 class="fw-bold text-lg mb-0">Our Users</h6>
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

                <?php if (!$isAdminUser): ?>
                    <div class="col-12" id="section-workOverview">
                        <div class="card h-100 radius-8 border-0">
                            <div class="card-body p-24">

                                <h6 class="fw-bold text-lg mb-12">Work Overview</h6>

                                <div id="userOverviewDonutChart"></div>

                                <ul class="d-flex flex-wrap gap-20 mt-16">
                                    <li>Orders: <b><?= $userOrders ?></b></li>
                                    <li>Subscriptions: <b><?= $userSubs ?></b></li>
                                    <li>Packages: <b><?= $userPackages ?></b></li>
                                    <li>Products: <b><?= $userProducts ?></b></li>
                                </ul>

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

      <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="banner" id="chkStats1" checked>
          <label class="form-check-label" for="chkStats1">Lufera Banner</label>
        </div>

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

        
        <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="growth" id="ordersGrowth" checked>
          <label class="form-check-label" for="ordersGrowth">Orders Growth</label>
        </div>

        <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="products" id="chkProducts" checked>
          <label class="form-check-label" for="chkProducts">Featured Products</label>
        </div>

        <?php if ($isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox" value="users" id="chkUsers" checked>
                <label class="form-check-label" for="chkUsers">Our Users</label>
            </div>
        <?php endif; ?>

        <?php if (!$isAdminUser): ?>
            <div class="form-check">
                <input class="form-check-input sec-check" type="checkbox"
                    value="workOverview" id="chkWorkOverview" checked>
                <label class="form-check-label" for="chkWorkOverview">
                    Work Overview
                </label>
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

<?php include './partials/layouts/layoutBottom.php' ?>