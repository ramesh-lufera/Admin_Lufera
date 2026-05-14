<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Categories</title>
<style>
    /* Styling for disabled button to appear blurred */
    .disabled {
        pointer-events: none;  /* Prevents clicking */
        opacity: 0.5;  /* Makes the button appear blurred */
    }

    input[type="checkbox"] {
        appearance: auto !important;
        -webkit-appearance: checkbox !important;
        opacity: 1 !important;
        display: inline-block !important;
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
    input[type="radio"] {
        appearance: auto !important;
        -webkit-appearance: radio !important;
        opacity: 1 !important;
        display: inline-block !important;
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
    .custom-modal {
        max-width: 900px !important;
    }

    #packageFields,
    #productFields {
        display: none;
    }
    #editPackageFields,
    #editProductFields {
        display: none;
    }
</style>
</head>

<?php 
    include './partials/layouts/layoutTop.php';

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    $Id = $_SESSION['user_id'];
    $query = "SELECT * FROM categories ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);

    // Handle category creation
    if (isset($_POST['save'])) {
        $cat_name = trim($_POST['cat_name']);
        $cat_url = trim($_POST['cat_url']);
        $cat_type = trim($_POST['cat_type']);
        $cat_des = $_POST['cat_des'] ?? null;

        $uploadDir = 'uploads/categories/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $cat_img = null;

        if (!empty($_FILES['cat_img']['name'])) {
            $fileName = time() . '_' . $_FILES['cat_img']['name'];
            $path = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['cat_img']['tmp_name'], $path)) {
                $cat_img = $path;
            }
        }

        $cat_inc = isset($_POST['cat_inc']) 
            ? json_encode(array_filter($_POST['cat_inc'])) 
            : null;

        $cat_exc = isset($_POST['cat_exc']) 
            ? json_encode(array_filter($_POST['cat_exc'])) 
            : null;

        $pkg_with_login = 0;
        $pkg_without_login = 0;

        if (isset($_POST['package_login_type'])) {
            if ($_POST['package_login_type'] === 'with_login') {
                $pkg_with_login = 1;
            } elseif ($_POST['package_login_type'] === 'without_login') {
                $pkg_without_login = 1;
            }
        }

        $prod_with_login = 0;
        $prod_without_login = 0;

        if (isset($_POST['product_login_type'])) {
            if ($_POST['product_login_type'] === 'with_login') {
                $prod_with_login = 1;
            } elseif ($_POST['product_login_type'] === 'without_login') {
                $prod_without_login = 1;
            }
        }

        $has_package = ($pkg_with_login || $pkg_without_login) ? 1 : 0;
        $has_product = ($prod_with_login || $prod_without_login) ? 1 : 0;

        if (!str_ends_with($cat_url, '.php')) {
            $cat_url .= '.php';
        }

        $catSlug = strtolower(preg_replace('/\s+/', '-', $cat_url));

        if (!empty($cat_name)) {
            $stmt = $conn->prepare("
            INSERT INTO categories 
            (cat_name, cat_url, cat_type, cat_img, cat_des, cat_inc, cat_exc,
            has_package, has_product,
            pkg_with_login, pkg_without_login,
            prod_with_login, prod_without_login)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
            "sssssssiiiiii",
            $cat_name,
            $catSlug,
            $cat_type,
            $cat_img,
            $cat_des,
            $cat_inc,
            $cat_exc,
            $has_package,
            $has_product,
            $pkg_with_login,
            $pkg_without_login,
            $prod_with_login,
            $prod_without_login
            );

            $stmt->execute();

            $cat_id = $conn->insert_id;

            $newCatId = (int)$cat_id;

            $file_path = realpath(__DIR__) . '/' . $catSlug;

            $catSlug1 = pathinfo($catSlug, PATHINFO_FILENAME);
            $det_file_name = $catSlug1 . '-det.php'; 
            $det_file_path = realpath(__DIR__) . '/' . $det_file_name;

            $manageLink = $det_file_name;
            $pageTitle = ucwords($cat_name);

            if (!file_exists($file_path)) {
                $default_content = <<<PHP
                    <?php include './partials/layouts/layoutTop.php'; ?>

                        <div id="categoryPage">
                            <?php
                                \$Id = \$_SESSION['user_id'];
                                
                                if (isset(\$_GET['cat_id']) && intval(\$_GET['cat_id']) > 0) {
                                    \$_SESSION['cat_id'] = intval(\$_GET['cat_id']);
                                } elseif (!isset(\$_GET['cat_id'])) {
                                    // Clear the old cat_id from session when not present in URL
                                    unset(\$_SESSION['cat_id']);
                                }

                                \$cat_id = \$_SESSION['cat_id'] ?? 0;

                                \$user = "select * from users where id = \$Id";
                                \$res = \$conn ->query(\$user);
                                \$row = \$res ->fetch_assoc();
                                \$UserId = \$row['id'];
                                \$role = \$row['role'];

                                \$category = "select * from categories where cat_id = \$cat_id";
                                \$cat_query = \$conn ->query(\$category);
                                \$cat_row = \$cat_query ->fetch_assoc();

                                // ===== API DATA PREPARATION =====

                                // BASE URL (dynamic)
                                \$protocol = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                                \$host = \$_SERVER['HTTP_HOST'];
                                \$basePath = dirname(\$_SERVER['SCRIPT_NAME']);

                                \$currentBaseUrl = \$protocol . \$host . \$basePath;

                                // CATEGORY
                                \$cat_id = \$cat_row['cat_id'];
                                \$cat_url = \$cat_row['cat_url'];

                                // Remove ending slash from current base URL
                                \$currentBaseUrl = rtrim(\$currentBaseUrl, '/');

                                // Remove starting slash from category URL if exists
                                \$cat_url = ltrim(\$cat_url, '/');

                                // FINAL LANDING URL
                                \$landingUrl = \$currentBaseUrl . "/pages/" . \$cat_url;

                                // SHORTCODES
                                \$categoryShortcode = "Category-Shortcode-" . \$cat_id;

                                // TYPE
                                \$type = strtolower(trim(\$cat_row['cat_type']));

                                // FULL PLAN SHORTCODE
                                \$fullPlanShortcode = (\$type === 'package')
                                    ? "Package-Shortcode-" . \$cat_id
                                    : "Product-Shortcode-" . \$cat_id;

                                // FETCH ITEMS
                                \$items = [];

                                if (\$type === 'package') {
                                    \$stmt = \$conn->prepare("SELECT id, title FROM package WHERE cat_id=? AND is_deleted=0");
                                } else {
                                    \$stmt = \$conn->prepare("SELECT id, title FROM products WHERE cat_id=? AND is_deleted != 1");
                                }

                                \$stmt->bind_param("i", \$cat_id);
                                \$stmt->execute();
                                \$res = \$stmt->get_result();

                                while (\$row = \$res->fetch_assoc()) {
                                    \$items[] = \$row;
                                }
                                
                                if (\$role == '1' || \$role == '2' || \$role == '7') {
                                    \$sql = "
                                        SELECT 
                                            websites.id AS web_id,
                                            users.user_id,
                                            users.id,
                                            users.business_name,
                                            CASE 
                                                WHEN websites.type = 'package' THEN package.package_name
                                                WHEN websites.type = 'product' THEN products.name
                                                ELSE websites.plan
                                            END AS plan_name,
                                            websites.domain,
                                            websites.access_www,
                                            websites.status,
                                            websites.created_at,
                                            websites.expired_at,
                                            websites.duration,
                                            websites.product_id,
                                            websites.type,
                                            JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '\$.name.value')) AS json_name
                                        FROM 
                                            users 
                                        JOIN 
                                            websites ON users.id = websites.user_id
                                        LEFT JOIN
                                            `json` ON `json`.website_id = websites.id
                                        LEFT JOIN 
                                            package ON (websites.type = 'package' AND websites.plan = package.id)
                                        LEFT JOIN 
                                            products ON (websites.type = 'product' AND websites.plan = products.id)
                                        WHERE 
                                            websites.cat_id = ? AND websites.is_active = 1
                                    ";
                                    \$stmt = \$conn->prepare(\$sql);
                                    if (!\$stmt) {
                                        die("Prepare failed: " . \$conn->error);
                                    }
                                    \$stmt->bind_param("i", \$cat_id);
                                } else {
                                    \$sql = "
                                        SELECT 
                                            websites.id AS web_id,
                                            users.user_id,
                                            users.id,
                                            users.business_name,
                                            CASE 
                                                WHEN websites.type = 'package' THEN package.package_name
                                                WHEN websites.type = 'product' THEN products.name
                                                ELSE websites.plan
                                            END AS plan_name,
                                            websites.domain,
                                            websites.access_www,
                                            websites.status,
                                            websites.created_at,
                                            websites.expired_at,
                                            websites.duration,
                                            websites.product_id,
                                            websites.type,
                                            JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '\$.name.value')) AS json_name
                                        FROM 
                                            users
                                        JOIN 
                                            websites ON users.id = websites.user_id
                                        LEFT JOIN 
                                            `json` ON `json`.website_id = websites.id
                                        LEFT JOIN 
                                            package ON (websites.type = 'package' AND websites.plan = package.id)
                                        LEFT JOIN 
                                            products ON (websites.type = 'product' AND websites.plan = products.id)
                                        WHERE 
                                            websites.user_id = ? AND websites.cat_id = ? AND websites.is_active = 1
                                    ";
                                    \$stmt = \$conn->prepare(\$sql);
                                    if (!\$stmt) {
                                        die("Prepare failed: " . \$conn->error);
                                    }
                                    \$stmt->bind_param("ii", \$UserId, \$cat_id);
                                }

                                \$stmt->execute();
                                \$result = \$stmt->get_result();

                                \$websites = [];
                                while (\$row = mysqli_fetch_assoc(\$result)) {
                                    \$websites[] = \$row;
                                }

                                // Number of websites per page
                                \$websitesPerPage = 5;

                                // Get the current page from URL, default is 1
                                \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;

                                // Calculate the starting index for the websites to display on this page
                                \$startIndex = (\$page - 1) * \$websitesPerPage;

                                // Slice the websites array to get only the websites for the current page
                                \$websitesOnPage = array_slice(\$websites, \$startIndex, \$websitesPerPage);

                                // Calculate the total number of pages
                                \$totalPages = ceil(count(\$websites) / \$websitesPerPage);
                            ?>
                            <!DOCTYPE html>
                            <html lang="en">
                            <head>
                            <meta charset="UTF-8">
                            <title>Category</title>
                            <style>
                                :root {
                                    --yellow: #fec700;
                                    --black: #101010;
                                }

                                /* GLOBAL TEXT SIZE */
                                body {
                                    font-size: 15px !important;
                                }

                                /* HEADINGS */
                                h1 { font-size: 26px !important; }
                                h2 { font-size: 24px !important; }
                                h3 { font-size: 22px !important; }
                                h4 { font-size: 20px !important; }
                                h5 { font-size: 18px !important; }
                                h6 { font-size: 16px !important; }

                                /* TEXT */
                                p {
                                    font-size: 15px !important;
                                    line-height: 1.6 !important;
                                }

                                /* HERO */
                                .breadcrumb-hero {
                                    width: 100% !important;
                                    height: 150px !important;
                                    background: linear-gradient(135deg, #101010, #2b2b2b) !important;
                                    border-radius: 10px !important;
                                    margin-bottom: 20px !important;
                                    display: flex !important;
                                    align-items: center !important;
                                    justify-content: center !important;
                                }

                                .breadcrumb-overlay {
                                    text-align: center !important;
                                    color: #fff !important;
                                }

                                /* TITLE COLOR */
                                .breadcrumb-title {
                                    font-weight: 700 !important;
                                    color: var(--yellow) !important;
                                }

                                .breadcrumb-path {
                                    font-size: 14px !important;
                                    color: #ccc !important;
                                }

                                .breadcrumb-path a {
                                    color: var(--yellow) !important;
                                    text-decoration: none !important;
                                }

                                .row {
                                    display: flex !important;
                                    gap: 20px !important;
                                }

                                .col-left {
                                    flex: 2 !important;
                                }

                                .col-right {
                                    flex: 1 !important;
                                }

                                /* CARD */
                                .card {
                                    background: #fff !important;
                                    border-radius: 10px !important;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
                                    padding: 18px !important;
                                    margin-bottom: 20px !important;
                                    line-height: 1.6 !important;
                                }

                                /* IMAGE */
                                .feature-img {
                                    width: 100% !important;
                                    border-radius: 8px !important;
                                    max-height: 300px !important;
                                    object-fit: cover !important;
                                }

                                /* LIST */
                                .custom-list {
                                    list-style: none !important;
                                    padding: 0 !important;
                                }

                                .custom-list li {
                                    padding: 6px 0 !important;
                                    font-size: 15px !important;
                                }

                                /* RIGHT PANEL */
                                .stat-box {
                                    background: #f9f9f9 !important;
                                    padding: 10px !important;
                                    border-radius: 6px !important;
                                    margin-bottom: 8px !important;
                                    font-size: 14px !important;
                                }

                                .quick-btn {
                                    display: block !important;
                                    width: 100% !important;
                                    padding: 10px !important;
                                    text-align: center !important;
                                    border-radius: 5px !important;
                                    margin-top: 10px !important;
                                    text-decoration: none !important;
                                    font-size: 14px !important;
                                    font-weight: 600 !important;
                                }

                                .btn-yellow {
                                    background: var(--yellow) !important;
                                    color: #000 !important;
                                }

                                .btn-outline {
                                    border: 1px solid #ccc !important;
                                    color: #333 !important;
                                }

                                /* RESPONSIVE */
                                @media (max-width: 768px) {
                                    .row {
                                        flex-direction: column !important;
                                    }
                                }

                                .manage-top-btn {
                                    position: static !important;   /* ✅ FIX */
                                    transform: none !important;    /* ✅ FIX */
                                    background: #fec700 !important;
                                    padding: 8px 16px !important;
                                    border-radius: 6px !important;
                                    border: none !important;
                                    font-weight: 600 !important;
                                    cursor: pointer !important;
                                }

                                .list-item {
                                    display: flex;
                                    justify-content: space-between;
                                    padding: 12px;
                                    border: 1px solid #eee;
                                    margin-bottom: 10px;
                                    border-radius: 6px;
                                }

                                /* ===== FEATURES SECTION ===== */

                                .features-row {
                                    display: flex !important;
                                    gap: 25px !important;
                                    margin-top: 10px !important;
                                }

                                .features-col {
                                    flex: 1 !important;
                                    padding: 15px !important;
                                    border-radius: 10px !important;
                                }

                                /* 🟢 INCLUSIONS */
                                // .features-col.inclusions {
                                //     background: #f6fff7 !important;
                                //     border: 1px solid #d4edda !important;
                                // }

                                // .features-col.inclusions h5 {
                                //     color: #28a745 !important;
                                // }

                                /* 🔴 EXCLUSIONS */
                                // .features-col.exclusions {
                                //     background: #fff6f6 !important;
                                //     border: 1px solid #f5c6cb !important;
                                // }

                                // .features-col.exclusions h5 {
                                //     color: #dc3545 !important;
                                // }

                                /* LIST */
                                .custom-list {
                                    list-style: none !important;
                                    padding: 0 !important;
                                }

                                .custom-list li {
                                    padding: 6px 0 !important;
                                    font-size: 15px !important;
                                }

                                /* ICONS */
                                .features-col.inclusions li::before {
                                    content: "✔ " !important;
                                    // color: #28a745 !important;
                                    font-weight: bold;
                                }

                                .features-col.exclusions li::before {
                                    content: "✖ " !important;
                                    // color: #dc3545 !important;
                                    font-weight: bold;
                                }

                                /* MOBILE */
                                @media (max-width: 768px) {
                                    .features-row {
                                        flex-direction: column !important;
                                    }
                                }

                                .copy-btn {
                                    background: #fec700 !important;
                                    color: #000 !important;
                                    border: none !important;
                                    font-weight: 600;
                                }

                                .copy-btn:hover {
                                    background: #e5b800 !important;
                                    color: #000 !important;
                                }

                                /* Input + button alignment */
                                .input-group .form-control {
                                    height: 45px;
                                }

                                .input-group .btn {
                                    height: 45px;
                                    display: flex;
                                    align-items: center;
                                }

                                /* Clean spacing for section */
                                .modal-body h5 {
                                    font-weight: 600;
                                }
                            </style>
                            </head>
                            <body>
                                <div class="content-wrapper">

                                    <!-- HERO -->
                                    <div class="breadcrumb-hero position-relative">

                                        <!-- Manage Button -->

                                        <div style="
                                            position:absolute; 
                                            top:50%; 
                                            right:20px; 
                                            transform:translateY(-50%); 
                                            display:flex; 
                                            gap:10px;
                                        ">

                                            <button 
                                                class="manage-top-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#apiModal">
                                                API
                                            </button>

                                            <button onclick="showManagePage()" class="manage-top-btn">
                                                Manage
                                            </button>

                                        </div>

                                        <div class="breadcrumb-overlay">
                                            <h2 class="breadcrumb-title">
                                                <?php echo htmlspecialchars(\$cat_row['cat_name']); ?>
                                            </h2>
                                            <p class="breadcrumb-path">
                                                <a>Categories</a> /
                                                <?php echo htmlspecialchars(\$cat_row['cat_name']); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- LEFT -->
                                        <div class="col-left" style="flex: 100% !important;">

                                            <?php if (!empty(\$cat_row['cat_img'])): ?>
                                                <div class="card">
                                                    <img src="<?php echo htmlspecialchars(\$cat_row['cat_img']); ?>" class="feature-img">
                                                </div>
                                            <?php endif; ?>

                                            <div class="card">
                                                <h4>Description</h4>
                                                <p><?php echo nl2br(htmlspecialchars(\$cat_row['cat_des'] ?? 'No description available')); ?></p>
                                            </div>

                                            <?php 
                                            \$inc = json_decode(\$cat_row['cat_inc'] ?? '[]', true); 
                                            \$exc = json_decode(\$cat_row['cat_exc'] ?? '[]', true); 
                                            ?>

                                            <div class="card">
                                                <h4>Features</h4>

                                                <div class="features-row">

                                                    <!-- INCLUSIONS -->
                                                    <div class="features-col inclusions">
                                                        <ul class="custom-list">
                                                            <?php if (!empty(\$inc)): ?>
                                                                <?php foreach (\$inc as \$item): ?>
                                                                    <li><?php echo htmlspecialchars(\$item); ?></li>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <li>No inclusions available</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>

                                                    <!-- EXCLUSIONS -->
                                                    <div class="features-col exclusions">
                                                        <ul class="custom-list">
                                                            <?php if (!empty(\$exc)): ?>
                                                                <?php foreach (\$exc as \$item): ?>
                                                                    <li><?php echo htmlspecialchars(\$item); ?></li>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <li>No exclusions available</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>

                                                </div>
                                            </div>

                                            <!-- ================= PRICING TABLE ================= -->
                                            <?php
                                                \$cat_type = strtolower(trim(\$cat_row['cat_type'] ?? ''));

                                                \$package_enabled = !empty(\$cat_row['package_login_type']);
                                                \$product_enabled = !empty(\$cat_row['product_login_type']);

                                                \$showPackages = (\$cat_type === 'package');
                                                \$showProducts = (\$cat_type === 'product');
                                            ?>
                                            <?php if (\$showPackages): ?>
                                                <?php
                                                    \$packages = [];
                                                    \$durations = [];
                                                    \$product_category = \$cat_row['cat_id'] ?? 0;

                                                    // Fetch packages
                                                    \$stmt = \$conn->prepare("
                                                        SELECT * FROM package 
                                                        WHERE is_deleted=0 AND is_active=1 AND cat_id=?
                                                    ");
                                                    \$stmt->bind_param("i", \$product_category);
                                                    \$stmt->execute();
                                                    \$res = \$stmt->get_result();

                                                    \$data=[];
                                                    while(\$row=\$res->fetch_assoc()){ \$data[\$row['id']]=\$row; }
                                                    \$stmt->close();

                                                    if(!empty(\$data)){
                                                        \$ids = implode(',', array_keys(\$data));

                                                        \$sql = "SELECT d.*, p.title, p.subtitle, p.description, p.package_name, p.is_active pkg_active
                                                                FROM durations d
                                                                JOIN package p ON d.package_id=p.id
                                                                WHERE d.package_id IN (\$ids)";
                                                        \$r=\$conn->query(\$sql);

                                                        while(\$row=\$r->fetch_assoc()){
                                                            \$dur=\$row['duration'];
                                                            \$packages[\$dur][]=\$row;
                                                            \$durations[\$dur]=\$dur;
                                                        }
                                                    }

                                                    // currency
                                                    \$symbol="\$";
                                                    \$r=\$conn->query("SELECT symbol FROM currencies WHERE is_active=1 LIMIT 1");
                                                    if(\$row=\$r->fetch_assoc()) \$symbol=\$row['symbol'];
                                                ?>
                                                <div class="card">
                                                    <h4>Packages Pricing Table</h4>
                                                    <div class="card-body">
                                                        <div class="row justify-content-center">
                                                            <div class="col-xxl-10">

                                                            <?php if (!empty(\$packages)): ?>
                                                                <!-- Duration Tabs -->
                                                                <ul class="nav nav-pills button-tab mt-32 mb-32 justify-content-center" id="pills-tab" role="tablist">
                                                                    <?php \$first = true; foreach (\$durations as \$duration_name): ?>
                                                                        <li class="nav-item" role="presentation">
                                                                            <button 
                                                                                class="nav-link px-24 py-10 text-md rounded-pill text-secondary-light fw-medium <?= \$first ? 'active' : '' ?>" 
                                                                                id="tab-<?= md5(\$duration_name) ?>" 
                                                                                data-bs-toggle="pill" 
                                                                                data-bs-target="#tab-pane-<?= md5(\$duration_name) ?>" 
                                                                                type="button" 
                                                                                role="tab" 
                                                                                aria-controls="tab-pane-<?= md5(\$duration_name) ?>" 
                                                                                aria-selected="<?= \$first ? 'true' : 'false' ?>">
                                                                                <?= htmlspecialchars(\$duration_name) ?>
                                                                            </button>
                                                                        </li>
                                                                    <?php \$first = false; endforeach; ?>
                                                                </ul>

                                                                <!-- Duration Tab Content -->
                                                                <div class="tab-content" id="pills-tabContent">
                                                                    <?php \$first = true; foreach (\$durations as \$duration_name): ?>
                                                                        <div class="tab-pane fade <?= \$first ? 'show active' : '' ?>" 
                                                                            id="tab-pane-<?= md5(\$duration_name) ?>" 
                                                                            role="tabpanel" 
                                                                            aria-labelledby="tab-<?= md5(\$duration_name) ?>" 
                                                                            tabindex="0">

                                                                            <div class="row gy-4">
                                                                                <?php foreach (\$packages[\$duration_name] as \$package): ?>
                                                                                    <div class="col-xxl-4 col-sm-6">
                                                                                        <div class="pricing-plan position-relative radius-24 overflow-hidden border">
                                                                                            <?php \$isActive = (\$package['pkg_active'] == 1); ?>
                                                                                            <?php if (!\$isActive): ?>
                                                                                                <p class="mb-0 text-sm text-danger fw-semibold mt-2 float-end">Inactive</p>
                                                                                            <?php endif; ?> 

                                                                                            <?php
                                                                                                // Get package_name from database
                                                                                                \$packageName = \$package['package_name'] ?? '';

                                                                                                // Convert package_name to lowercase
                                                                                                \$packageSlug = strtolower(trim(\$packageName));

                                                                                                // Replace spaces and special characters with "-"
                                                                                                \$packageSlug = preg_replace('/[^a-z0-9]+/i', '-', \$packageSlug);

                                                                                                // Remove extra "-" from beginning/end
                                                                                                \$packageSlug = trim(\$packageSlug, '-');

                                                                                                // Final dynamic URL
                                                                                                \$packageUrl = \$packageSlug . ".php";
                                                                                            ?>

                                                                                            <h5 class="mb-0 lufera-color">
                                                                                                <a href="<?= htmlspecialchars(\$packageUrl) ?>"
                                                                                                style="text-decoration:none; color:inherit;">

                                                                                                    <?= htmlspecialchars(\$package['title']) ?>

                                                                                                </a>
                                                                                            </h5>

                                                                                            <p class="mb-0 text-secondary-light mb-28"><?= htmlspecialchars(\$package['subtitle']) ?></p>

                                                                                            <h4 class="mb-24">
                                                                                            <p class="text-sm text-muted mt-0 mb-10 text-decoration-line-through"><?= htmlspecialchars(\$symbol) ?> <?= number_format((float)\$package['preview_price'], 0, '.', ',') ?></p>
                                                                                                <?= htmlspecialchars(\$symbol) ?>
                                                                                                <?= number_format((float)\$package['price'], 0, '.', ',') ?>
                                                                                                <span class="fw-medium text-md text-secondary-light">/
                                                                                                    <?= htmlspecialchars(\$package['duration']) ?>
                                                                                                </span>
                                                                                                
                                                                                            </h4>

                                                                                            <span class="mb-20 fw-medium"><?= htmlspecialchars(\$package['description']) ?></span>

                                                                                            <ul>
                                                                                                <?php
                                                                                                \$package_id = \$package['package_id'];
                                                                                                \$feature_sql = "SELECT feature FROM features WHERE package_id = \$package_id";
                                                                                                \$feature_result = \$conn->query(\$feature_sql);
                                                                                                if (\$feature_result && \$feature_result->num_rows > 0):
                                                                                                    while (\$feat = \$feature_result->fetch_assoc()):
                                                                                                ?>
                                                                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>
                                                                                                        </span>
                                                                                                        <span class="text-secondary-light text-lg"><?= htmlspecialchars(\$feat['feature']) ?></span>
                                                                                                    </li>
                                                                                                <?php endwhile; endif; ?>
                                                                                            </ul>

                                                                                            <form action="cart.php" method="POST">
                                                                                                <input type="hidden" name="type" value="package">
                                                                                                <input type="hidden" name="id" value="<?= htmlspecialchars(\$package['package_id']) ?>">
                                                                                                <input type="hidden" name="plan_name" value="<?= htmlspecialchars(\$package['package_name']) ?>">
                                                                                                <input type="hidden" name="title" value="<?= htmlspecialchars(\$package['title']) ?>">
                                                                                                <input type="hidden" name="subtitle" value="<?= htmlspecialchars(\$package['subtitle']) ?>">
                                                                                                <input type="hidden" name="price" value="<?= htmlspecialchars(\$package['price']) ?>">
                                                                                                <input type="hidden" name="duration" value="<?= htmlspecialchars(\$package['duration']) ?>">
                                                                                                <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                                                                                <!-- <input type="hidden" name="addon_service" value="<?= htmlspecialchars(\$package['addon_service']) ?>">
                                                                                                <input type="hidden" name="addon_package" value="<?= htmlspecialchars(\$package['addon_package']) ?>">
                                                                                                <input type="hidden" name="addon_product" value="<?= htmlspecialchars(\$package['addon_product']) ?>">
                                                                                                <input type="hidden" name="gst_id" value="<?= htmlspecialchars(\$package['gst_id']) ?>"> -->

                                                                                                <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28" <?= !\$isActive ? 'disabled' : '' ?>>Get started</button>
                                                                                            </form>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    <?php \$first = false; endforeach; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="text-center py-32">
                                                                    <div class="radius-12 p-12">
                                                                        <h6 class="mb-0" style="color: #000; font-size: 1.125rem; font-weight: 600;">
                                                                            No packages available.
                                                                        </h6>
                                                                        <div style="height: 3px; width: 60px; background-color: #fdc701; margin: 12px auto 0; border-radius: 2px;"></div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (\$showProducts): ?>
                                                <style>
                                                    .hover-scale-img__img{
                                                        height:200px;
                                                    }
                                                </style>
                                                <?php
                                                    \$products = [];
                                                    \$product_category = \$cat_row['cat_id'] ?? 0;

                                                    \$stmt = \$conn->prepare("
                                                        SELECT * FROM products 
                                                        WHERE cat_id = ? AND is_deleted != 1
                                                    ");
                                                    \$stmt->bind_param("i", \$product_category);
                                                    \$stmt->execute();
                                                    \$res = \$stmt->get_result();

                                                    while (\$row = \$res->fetch_assoc()) {
                                                        \$products[] = \$row;
                                                    }
                                                    \$stmt->close();

                                                    // currency
                                                    \$symbol = "\$";
                                                    \$r = \$conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
                                                    if (\$row = \$r->fetch_assoc()) \$symbol = \$row['symbol'];
                                                ?>
                                                <div class="card">
                                                    <h4>Products Pricing Table</h4>
                                                    <div class="card-body">
                                                        <div class="row gy-4">
                                                            <?php if (!empty(\$products)): ?>
                                                                <?php foreach (\$products as \$row): ?>
                                                                <?php \$isActive = (\$row['is_active'] == 1); ?>

                                                                <div class="col-lg-4 col-md-4 col-sm-6">

                                                                    <?php if (\$isActive): ?>
                                                                        <a href="product-details.php?id=<?php echo \$row['id']; ?>" class="d-block">
                                                                    <?php endif; ?>

                                                                    <div class="pb-16 hover-scale-img border radius-16 overflow-hidden <?php echo !\$isActive ? 'inactive-product' : ''; ?>">

                                                                        <div class="max-h-266-px overflow-hidden">
                                                                            <img src="uploads/products/<?php echo \$row['product_image']; ?>"
                                                                                class="hover-scale-img__img w-100 object-fit-cover <?php echo !\$isActive ? 'grayscale' : ''; ?>">
                                                                        </div>

                                                                        <div class="py-16 px-24">

                                                                            <?php
                                                                                // Get product name from database
                                                                                \$productName = \$row['name'] ?? '';

                                                                                // Convert product name to lowercase
                                                                                \$productSlug = strtolower(trim(\$productName));

                                                                                // Replace spaces and special characters with "-"
                                                                                \$productSlug = preg_replace('/[^a-z0-9]+/i', '-', \$productSlug);

                                                                                // Remove extra "-" from beginning/end
                                                                                \$productSlug = trim(\$productSlug, '-');

                                                                                // Final dynamic URL
                                                                                \$productUrl = \$productSlug . ".php";
                                                                            ?>

                                                                            <h6 class="mb-4">
                                                                                <a href="<?= htmlspecialchars(\$productUrl) ?>"
                                                                                style="text-decoration:none; color:inherit;">

                                                                                    <?php echo htmlspecialchars(\$row['name']); ?>

                                                                                </a>
                                                                            </h6>

                                                                            <p class="mb-0 text-sm text-secondary-light">
                                                                                <b>Price</b> : <?= \$symbol ?><?php echo \$row['price']; ?>
                                                                            </p>

                                                                            <p class="mb-0 text-sm text-secondary-light float-start">
                                                                                <b>Validity</b> : <?php echo \$row['duration']; ?>
                                                                            </p>

                                                                            <?php if (!\$isActive): ?>
                                                                                <p class="text-danger fw-semibold float-end mt-2">Inactive</p>
                                                                            <?php endif; ?>
                                                                        </div>

                                                                    </div>

                                                                    <?php if (\$isActive): ?>
                                                                        </a>
                                                                    <?php endif; ?>

                                                                </div>

                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <div class="text-center py-32">
                                                                    <div class="radius-12 p-12">
                                                                        <h6 class="mb-0" style="color: #000; font-size: 1.125rem; font-weight: 600;">
                                                                            No products available.
                                                                        </h6>
                                                                        <div style="height: 3px; width: 60px; background-color: #fdc701; margin: 12px auto 0; border-radius: 2px;"></div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                        </div>
                                                    </div>    
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                                <!-- ================= API MODAL ================= -->
                                <div class="modal fade" id="apiModal" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered custom-modal">
                                        <div class="modal-content p-4">

                                            <!-- HEADER -->
                                            <div class="modal-header">
                                                <h5 class="modal-title">API Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <!-- BODY -->
                                            <div class="modal-body">

                                                <!-- LANDING URL -->
                                                <h6>Landing URL</h6>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="landingUrl" class="form-control" value="<?php echo \$landingUrl; ?>" readonly>
                                                    <button class="btn copy-btn" onclick="copyText('landingUrl')">Copy</button>
                                                </div>

                                                <!-- SHORTCODES TITLE -->
                                                <hr style="margin:15px 0 10px;">
                                                <h5 style="margin:0 0 10px;">Shortcodes</h5>

                                                <!-- CATEGORY SHORTCODE -->
                                                <h6>Category Shortcode</h6>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="catShortcode" class="form-control" value="<?php echo \$categoryShortcode; ?>" readonly>
                                                    <button class="btn copy-btn" onclick="copyText('catShortcode')">Copy</button>
                                                </div>

                                                <!-- FULL PLAN -->
                                                <h6>Full Plan Shortcode</h6>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="fullPlan" class="form-control" value="<?php echo \$fullPlanShortcode; ?>" readonly>
                                                    <button class="btn copy-btn" onclick="copyText('fullPlan')">Copy</button>
                                                </div>

                                                <!-- INDIVIDUAL -->
                                                <h6>Individual Plan Shortcodes</h6>

                                                <?php if (empty(\$items)): ?>

                                                    <p style="color:#888; margin-top:10px;">
                                                        <?php echo (\$type === 'package') ? 'No packages found.' : 'No products found.'; ?>
                                                    </p>

                                                <?php else: ?>

                                                    <?php foreach (\$items as \$index => \$item): 
                                                        \$short = (\$type === 'package')
                                                            ? "Package-" . \$item['title'] . "-Shortcode-" . \$item['id']
                                                            : "Product-" . \$item['title'] . "-Shortcode-" . \$item['id'];

                                                        \$inputId = "ind_" . \$index;
                                                    ?>
                                                        <label><?php echo htmlspecialchars(\$item['title']); ?></label>
                                                        <div class="input-group mb-2">
                                                            <input type="text" id="<?php echo \$inputId; ?>" class="form-control" value="<?php echo \$short; ?>" readonly>
                                                            <button class="btn copy-btn" onclick="copyText('<?php echo \$inputId; ?>')">Copy</button>
                                                        </div>
                                                    <?php endforeach; ?>

                                                <?php endif; ?>

                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <script>
                                    function copyText(id) {
                                        const input = document.getElementById(id);
                                        const value = input.value;

                                        navigator.clipboard.writeText(value).then(() => {

                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Copied!',
                                                text: value,
                                                timer: 1500,
                                                showConfirmButton: false
                                            });

                                        }).catch(() => {

                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Copy failed',
                                                text: 'Unable to copy text',
                                            });

                                        });
                                    }
                                </script>
                            </body>
                            </html>
                        </div>

                        <div id="managePage" style="display:none;">
                            <?php
                                \$Id = \$_SESSION['user_id'];
                                
                                if (isset(\$_GET['cat_id']) && intval(\$_GET['cat_id']) > 0) {
                                    \$_SESSION['cat_id'] = intval(\$_GET['cat_id']);
                                } elseif (!isset(\$_GET['cat_id'])) {
                                    // Clear the old cat_id from session when not present in URL
                                    unset(\$_SESSION['cat_id']);
                                }

                                \$cat_id = \$_SESSION['cat_id'] ?? 0;

                                \$user = "select * from users where id = \$Id";
                                \$res = \$conn ->query(\$user);
                                \$row = \$res ->fetch_assoc();
                                \$UserId = \$row['id'];
                                \$role = \$row['role'];

                                \$category = "select * from categories where cat_id = \$cat_id";
                                \$cat_query = \$conn ->query(\$category);
                                \$cat_row = \$cat_query ->fetch_assoc();
                                
                                if (\$role == '1' || \$role == '2' || \$role == '7') {
                                    \$sql = "
                                        SELECT 
                                            websites.id AS web_id,
                                            users.user_id,
                                            users.id,
                                            users.business_name,
                                            CASE 
                                                WHEN websites.type = 'package' THEN package.package_name
                                                WHEN websites.type = 'product' THEN products.name
                                                ELSE websites.plan
                                            END AS plan_name,
                                            websites.domain,
                                            websites.access_www,
                                            websites.status,
                                            websites.created_at,
                                            websites.expired_at,
                                            websites.duration,
                                            websites.product_id,
                                            websites.type,
                                            JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '$.name.value')) AS json_name
                                        FROM 
                                            users 
                                        JOIN 
                                            websites ON users.id = websites.user_id
                                        LEFT JOIN
                                            `json` ON `json`.website_id = websites.id
                                        LEFT JOIN 
                                            package ON (websites.type = 'package' AND websites.plan = package.id)
                                        LEFT JOIN 
                                            products ON (websites.type = 'product' AND websites.plan = products.id)
                                        WHERE 
                                            websites.cat_id = ? AND websites.is_active = 1
                                    ";
                                    \$stmt = \$conn->prepare(\$sql);
                                    if (!\$stmt) {
                                        die("Prepare failed: " . \$conn->error);
                                    }
                                    \$stmt->bind_param("i", \$cat_id);
                                } else {
                                    \$sql = "
                                        SELECT 
                                            websites.id AS web_id,
                                            users.user_id,
                                            users.id,
                                            users.business_name,
                                            CASE 
                                                WHEN websites.type = 'package' THEN package.package_name
                                                WHEN websites.type = 'product' THEN products.name
                                                ELSE websites.plan
                                            END AS plan_name,
                                            websites.domain,
                                            websites.access_www,
                                            websites.status,
                                            websites.created_at,
                                            websites.expired_at,
                                            websites.duration,
                                            websites.product_id,
                                            websites.type,
                                            JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '$.name.value')) AS json_name
                                        FROM 
                                            users
                                        JOIN 
                                            websites ON users.id = websites.user_id
                                        LEFT JOIN 
                                            `json` ON `json`.website_id = websites.id
                                        LEFT JOIN 
                                            package ON (websites.type = 'package' AND websites.plan = package.id)
                                        LEFT JOIN 
                                            products ON (websites.type = 'product' AND websites.plan = products.id)
                                        WHERE 
                                            websites.user_id = ? AND websites.cat_id = ? AND websites.is_active = 1
                                    ";
                                    \$stmt = \$conn->prepare(\$sql);
                                    if (!\$stmt) {
                                        die("Prepare failed: " . \$conn->error);
                                    }
                                    \$stmt->bind_param("ii", \$UserId, \$cat_id);
                                }

                                \$stmt->execute();
                                \$result = \$stmt->get_result();

                                \$websites = [];
                                while (\$row = mysqli_fetch_assoc(\$result)) {
                                    \$websites[] = \$row;
                                }

                                // Number of websites per page
                                \$websitesPerPage = 5;

                                // Get the current page from URL, default is 1
                                \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;

                                // Calculate the starting index for the websites to display on this page
                                \$startIndex = (\$page - 1) * \$websitesPerPage;

                                // Slice the websites array to get only the websites for the current page
                                \$websitesOnPage = array_slice(\$websites, \$startIndex, \$websitesPerPage);

                                // Calculate the total number of pages
                                \$totalPages = ceil(count(\$websites) / \$websitesPerPage);
                            ?>
                            <!DOCTYPE html>
                            <html lang="en">
                            <head>
                            <meta charset="UTF-8">
                            <title>Websites</title>
                            <style>
                                :root {
                                --yellow: #fec700;
                                --black: #101010;
                                --mild-blue: #e6f0ff;
                                }

                                .content-wrapper {
                                width: 100%;
                                /* max-width: 1200px; */
                                margin: 20px auto;
                                padding: 10px 15px;
                                }

                                .search-card {
                                background-color: #fff;
                                border-radius: 8px;
                                padding: 15px 20px;
                                margin-bottom: 20px;
                                box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                                display: flex;
                                flex-wrap: wrap;
                                justify-content: space-between;
                                align-items: center;
                                gap: 10px;
                                }

                                .search-container {
                                position: relative;
                                flex: 1 1 300px;
                                max-width: 400px;
                                }

                                .search-icon {
                                position: absolute;
                                left: 10px;
                                top: 50%;
                                transform: translateY(-50%);
                                font-size: 16px;
                                color: #999;
                                pointer-events: none;
                                }

                                .search-container input[type="text"] {
                                width: 100%;
                                padding: 10px 10px 10px 35px;
                                font-size: 16px;
                                border: 2px solid var(--yellow);
                                border-radius: 5px;
                                box-sizing: border-box;
                                }

                                .add-btn {
                                padding: 10px 16px;
                                background-color: var(--yellow);
                                color: var(--black);
                                border: none;
                                border-radius: 5px;
                                font-weight: bold;
                                text-decoration: none;
                                cursor: pointer;
                                white-space: nowrap;
                                flex-shrink: 0;
                                }

                                .list-section {
                                background-color: #fff;
                                border-radius: 8px;
                                padding: 20px;
                                box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                                }

                                .list-section h5 {
                                margin-top: 0;
                                margin-bottom: 15px;
                                font-size: 20px;
                                }

                                .list-wrapper {
                                display: flex;
                                flex-direction: column;
                                gap: 15px;
                                }

                                .list-item {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                padding: 15px 20px;
                                border: 1px solid #eee;
                                border-left: 5px solid var(--yellow);
                                border-radius: 6px;
                                background-color: #fff;
                                transition: box-shadow 0.2s ease;
                                flex-wrap: wrap;
                                gap: 10px;
                                }

                                .list-item:hover {
                                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                                }

                                .site-info {
                                flex: 1 1 60%;
                                display: flex;
                                flex-direction: column;
                                gap: 5px;
                                min-width: 0;
                                }

                                .site-info-header {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                }

                                .site-info-header h6 {
                                margin: 0 0 8px 0;
                                font-weight: bold;
                                font-size: 20px;
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                max-width: 60%;
                                }

                                .site-info-header .plan {
                                font-size: 14px;
                                color: #555;
                                white-space: nowrap;
                                }

                                .site-info-meta {
                                font-size: 14px;
                                color: #555;
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                margin-top: 5px;
                                }

                                .manage-btn-wrapper {
                                flex-shrink: 0;
                                display: flex;
                                flex-direction: column;
                                align-items: flex-end;
                                gap: 8px;
                                }

                                .manage-btn-wrapper .plan {
                                font-size: 14px;
                                color: #555;
                                }

                                .dashboard-btn {
                                background-color: var(--yellow);
                                color: var(--black);
                                padding: 8px 16px;
                                border-radius: 5px;
                                font-weight: bold;
                                text-decoration: none;
                                white-space: nowrap;
                                transition: background-color 0.3s ease;
                                }

                                .dashboard-btn:hover {
                                background-color: #e5b800;
                                }

                                .pagination {
                                display: flex;
                                justify-content: flex-end;
                                gap: 10px;
                                margin-top: 20px;
                                }

                                .pagination a {
                                padding: 8px 15px;
                                background-color: var(--yellow);
                                color: var(--black);
                                border-radius: 5px;
                                text-decoration: none;
                                }

                                .status-active {
                                border-left: 5px solid var(--yellow);
                                /* border-left: 5px solid #4caf50; Green */
                                }

                                .status-pending {
                                border-left: 5px solid #ff9800; /* Orange */
                                }

                                .status-expired {
                                border-left: 5px solid #f44336; /* Red */
                                }

                                .domain-text-approved {
                                    color: #fec700;
                                }
                                .domain-text-pending {
                                    color: orange;
                                }

                                /* Increase font sizes */
                                .site-info-header h6 {
                                font-size: 20px !important;
                                }

                                .site-info-meta {
                                font-size: 16px;
                                }

                                .manage-btn-wrapper .plan {
                                font-size: 16px;
                                }

                                .dashboard-btn {
                                font-size: 16px;
                                }

                                /* Status-based colors for domain only */
                                .domain-text-active {
                                /* color: #4caf50; */
                                color: var(--yellow);
                                }

                                .domain-text-pending {
                                color: #ff9800;
                                }

                                .domain-text-expired {
                                color: #f44336;
                                }

                                .no-website {
                                justify-content: center;
                                font-size: 18px; 
                                color: #888;
                                }

                                /* Responsive */
                                @media (max-width: 700px) {
                                .list-item {
                                    flex-direction: column;
                                    align-items: flex-start;
                                }

                                .site-info {
                                    flex: 1 1 100%;
                                }

                                .site-info-header {
                                    flex-direction: column;
                                    align-items: flex-start;
                                    gap: 3px;
                                }

                                .site-info-header h6 {
                                    max-width: 100%;
                                }

                                .site-info-header .plan {
                                    font-size: 14px;
                                }

                                .manage-btn-wrapper {
                                    width: 100%;
                                    margin-top: 10px;
                                    align-items: flex-start;
                                }
                                }
                            </style>
                            </head>
                            <body>
                                <div class="content-wrapper">

                                <!-- Title -->
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                                    <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
                                    <h6 class="fw-semibold mb-0">$pageTitle</h6>
                                    <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>                                 
                                </div>
                                
                                <!-- Search + Add -->
                                <div class="search-card bg-base">
                                    <div class="search-container">
                                    <span class="search-icon">&#128269;</span>
                                    <input type="text" id="searchInput" placeholder="Search $pageTitle..." />
                                    </div>
                                    <a href="view-$catSlug1.php" class="add-btn">+ Add New $pageTitle</a>
                                </div>

                                <!-- Website List -->
                                <!-- <div class="list-section" id="websiteList"> -->
                                    <!-- <h5>Business WordPress Hosting</h5> -->

                                    <div class="list-wrapper" id="websiteList">
                                    <?php if (empty(\$websitesOnPage)): ?>
                                        <div class="list-item bg-base no-website">
                                        No $pageTitle found.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach (\$websitesOnPage as \$site): ?>
                                        <?php
                                            \$status = strtolower(\$site['status']);
                                            \$CreatedAt = \$site['created_at'];
                                            \$Duration = \$site['duration'];
                                            \$expiredAt = \$site['expired_at'];

                                            // \$startDate = new DateTime(\$CreatedAt);
                                            // \$endDate = (clone \$startDate)->modify("+{\$Duration}");
                                            // \$Validity = \$startDate->format("d-m-Y") . " to " . \$endDate->format("d-m-Y"); 

                                            \$startDate = new DateTime(\$CreatedAt);
                                            \$endDate = (clone \$startDate)->modify("+{\$Duration}");
                                            \$calculatedEnd = \$endDate->format("d-m-Y");

                                            // If renewed, use expired_at from database
                                            if (!empty(\$expiredAt) && \$expiredAt !== '0000-00-00 00:00:00') {
                                                \$Validity = (new DateTime(\$expiredAt))->format("d-m-Y");
                                            } else {
                                                \$Validity = \$calculatedEnd;
                                            }

                                            \$statusClass = 'status-pending';
                                            if (\$status === 'approved') \$statusClass = 'status-active';
                                            elseif (\$status === 'cancelled') \$statusClass = 'status-expired';

                                            if (\$status === 'approved') {

                                                // \$expiresText = htmlspecialchars(\$Validity);
                                                \$color = '#89836f'; // keep existing approved color

                                            } elseif (\$status === 'cancelled') {

                                                // \$expiresText = 'N/A';
                                                \$color = 'red';

                                            } elseif (\$status === 'pending') {

                                                // \$expiresText = 'N/A';
                                                \$color = 'orange';
                                            }
                                        ?>
                                        <div class="list-item bg-base <?php echo \$statusClass; ?>">
                                            <div class="site-info">
                                            <!-- Domain Title -->
                                            <div class="site-info-header">
                                                <h6>
                                                <!-- <?php echo htmlspecialchars(\$site['plan']); ?> -->
                                                <?php echo htmlspecialchars(\$site['plan_name']); ?>
                                                <span style="visibility:hidden"><?php echo htmlspecialchars(\$site['user_id']); ?></span>
                                                </h6>
                                            </div>
                                            
                                            <div class="site-info-meta">
                                                Expires:
                                                <span style="color: <?php echo \$color; ?>;">
                                                    <?php echo \$Validity; ?>
                                                </span>
                                            </div>
                                            </div>
                                            <div class="manage-btn-wrapper">
                                                <?php if (\$role == '1' || \$role == '2' || \$role == '7') { ?>
                                                    <p class="mb-0 view-user-btn cursor-pointer" data-id=<?php echo htmlspecialchars(\$site['id']); ?> data-bs-toggle="modal" data-bs-target="#viewUserModal">
                                                        <?php echo htmlspecialchars(\$site['business_name']); ?>
                                                    </p>
                                                <?php } ?>
                                                <a href="$manageLink?website_id=<?php echo (int)\$site['web_id']; ?>&product_id=<?php echo (int)\$site['product_id']; ?>&type=<?php echo \$site['type']; ?>" class="dashboard-btn">Manage</a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="pagination">
                                    <?php if (\$page > 1): ?>
                                        <a href="?cat_id=?<?php echo \$cat_id; ?>&page=<?php echo \$page - 1; ?>">Previous</a> 
                                    <?php endif; ?>

                                    <?php for (\$i = 1; \$i <= \$totalPages; \$i++): ?>
                                        <a href="?cat_id=?<?php echo \$cat_id; ?>&page=<?php echo \$i; ?>" class="<?php echo \$i === \$page ? 'active' : ''; ?>">
                                        <?php echo \$i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if (\$page < \$totalPages): ?>
                                        <a href="?cat_id=?<?php echo \$cat_id; ?>&page=<?php echo \$page + 1; ?>">Next</a>
                                    <?php endif; ?>
                                    </div>

                                <!-- </div> -->
                                </div>
                                <div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content radius-12 p-4">
                                        <div class="modal-header">
                                            <h5 class="modal-title">View User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body" id="userDetailContent">
                                            <!-- User info will be loaded here -->
                                            <p>Loading...</p>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                const searchInput = document.getElementById('searchInput');
                                searchInput.addEventListener('keyup', function () {
                                    const filter = searchInput.value.toLowerCase();
                                    const items = document.querySelectorAll("#websiteList .list-item");
                                    items.forEach(item => {
                                    const text = item.innerText.toLowerCase();
                                    item.style.display = text.includes(filter) ? '' : 'none';
                                    });
                                });
                                </script>
                                <script>
                                    $(document).ready(function () {
                                        $('.view-user-btn').click(function () {
                                            var userId = $(this).data('id');
                                
                                            $.ajax({
                                                url: 'fetch-user.php',
                                                type: 'POST',
                                                data: { id: userId },
                                                success: function (response) {
                                                    $('#userDetailContent').html(response);
                                                },
                                                error: function () {
                                                    $('#userDetailContent').html('Error loading user details.');
                                                }
                                            });
                                        });
                                    });
                                </script>

                            </body>
                            </html>
                        </div>

                        <!-- ================= JS ================= -->
                        <script>
                            function showManagePage() {
                                document.getElementById('categoryPage').style.display = 'none';
                                document.getElementById('managePage').style.display = 'block';
                            }

                            function showCategoryPage() {
                                document.getElementById('categoryPage').style.display = 'block';
                                document.getElementById('managePage').style.display = 'none';
                            }
                        </script>

                    <?php include './partials/layouts/layoutBottom.php' ?>
                PHP;

                file_put_contents($file_path, $default_content);

                // ===== CREATE LANDING PAGE FILE =====
                $landingDir = realpath(__DIR__) . '/pages';

                // Create folder if not exists
                if (!is_dir($landingDir)) {
                    mkdir($landingDir, 0777, true);
                }

                // File name same as category page
                $landingFilePath = $landingDir . '/' . $catSlug;

                // Content
                $landingContent = <<<PHP
                    <?php
                        include '../partials/connection.php';
                        include 'head.php';
                        include 'scripts.php';

                        session_start();

                        \$isLoggedIn = isset(\$_SESSION['user_id']) && \$_SESSION['user_id'] > 0;

                        // AUTO GENERATED CATEGORY ID
                        \$cat_id = {$newCatId};

                        \$category = "select * from categories where cat_id = \$cat_id";
                        \$cat_query = \$conn ->query(\$category);
                        \$cat_row = \$cat_query ->fetch_assoc();

                        // ================= COMPANY LOGO =================
                        
                        \$company = \$conn->query("SELECT logo, phone_no, email FROM company LIMIT 1");
                        \$companyRow = \$company->fetch_assoc();

                        \$logo = \$companyRow['logo'] ?? '';
                        \$phone_no = \$companyRow['phone_no'] ?? '';
                        \$email = \$companyRow['email'] ?? '';

                        // ✅ COMPANY DATA
                        \$company = [];
                        \$res = \$conn->query("SELECT phone_no, address FROM company LIMIT 1");
                        if (\$res && \$res->num_rows > 0) {
                            \$company = \$res->fetch_assoc();
                        }
                        
                        \$sql = "
                            SELECT 
                                websites.id AS web_id,
                                users.user_id,
                                users.id,
                                users.business_name,
                                CASE 
                                    WHEN websites.type = 'package' THEN package.package_name
                                    WHEN websites.type = 'product' THEN products.name
                                    ELSE websites.plan
                                END AS plan_name,
                                websites.domain,
                                websites.access_www,
                                websites.status,
                                websites.created_at,
                                websites.expired_at,
                                websites.duration,
                                websites.product_id,
                                websites.type,
                                JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '\$.name.value')) AS json_name
                            FROM 
                                users 
                            JOIN 
                                websites ON users.id = websites.user_id
                            LEFT JOIN
                                `json` ON `json`.website_id = websites.id
                            LEFT JOIN 
                                package ON (websites.type = 'package' AND websites.plan = package.id)
                            LEFT JOIN 
                                products ON (websites.type = 'product' AND websites.plan = products.id)
                            WHERE 
                                websites.cat_id = ? AND websites.is_active = 1
                        ";

                        \$stmt = \$conn->prepare(\$sql);
                        \$stmt->bind_param("i", \$cat_id);

                        \$stmt->execute();
                        \$result = \$stmt->get_result();

                        \$websites = [];
                        while (\$row = mysqli_fetch_assoc(\$result)) {
                            \$websites[] = \$row;
                        }

                        // Number of websites per page
                        \$websitesPerPage = 5;

                        // Get the current page from URL, default is 1
                        \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;

                        // Calculate the starting index for the websites to display on this page
                        \$startIndex = (\$page - 1) * \$websitesPerPage;

                        // Slice the websites array to get only the websites for the current page
                        \$websitesOnPage = array_slice(\$websites, \$startIndex, \$websitesPerPage);

                        // Calculate the total number of pages
                        \$totalPages = ceil(count(\$websites) / \$websitesPerPage);
                    ?>
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                    <meta charset="UTF-8">
                    <title>Category</title>
                    <style>
                        :root {
                            --yellow: #fec700;
                            --black: #101010;
                        }

                        /* GLOBAL TEXT SIZE */
                        body {
                            font-size: 15px !important;
                        }

                        .content-wrapper {
                            margin: 0px 15% 0px 15%;
                        }

                        /* HEADINGS */
                        h1 { font-size: 26px !important; }
                        h2 { font-size: 24px !important; }
                        h3 { font-size: 22px !important; }
                        h4 { font-size: 20px !important; }
                        h5 { font-size: 18px !important; }
                        h6 { font-size: 16px !important; }

                        /* TEXT */
                        p {
                            font-size: 15px !important;
                            line-height: 1.6 !important;
                        }

                        /* HERO */
                        
                        /* ===== HEADER ===== */
                        
                        /* ===== HEADER FULL WIDTH ===== */
                        .top-header {
                            width: 100%;
                            display: flex;
                            justify-content: space-between; /* left + right */
                            align-items: center;
                            padding: 12px 30px;
                            box-sizing: border-box;
                        }

                        /* LEFT LOGO */
                        .header-left img {
                            height: 50px;
                            object-fit: contain;
                        }

                        /* RIGHT PHONE */
                        
                        .header-right {
                            display: flex;
                            align-items: center;
                            gap: 15px; /* space between phone & email */
                            font-size: 14px;
                            font-weight: 500;
                        }

                        /* EACH ITEM */
                        .contact-item {
                            white-space: nowrap;
                        }

                        /* ===== BANNER ===== */
                        
                        .banner-section {
                            position: relative;
                            width: 100%;
                            height: 280px;

                            margin-top: 20px;   /* 🔥 spacing from header */
                            margin-bottom: 20px;

                            overflow: hidden;
                        }

                        /* FULL WIDTH IMAGE */
                        .banner-img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                        }

                        /* OVERLAY */
                        .banner-overlay {
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;

                            display: flex;
                            align-items: center;
                            justify-content: center;

                            background: rgba(0,0,0,0.4);
                        }

                        /* TITLE CENTER */
                        .banner-title {
                            color: #fff;
                            font-size: 28px;
                            font-weight: 700;
                            text-align: center;
                            text-shadow: 0 2px 8px rgba(0,0,0,0.7);
                        }

                        .breadcrumb-path {
                            font-size: 14px !important;
                            color: #ccc !important;
                        }

                        .breadcrumb-path a {
                            color: var(--yellow) !important;
                            text-decoration: none !important;
                        }

                        .row {
                            display: flex !important;
                            gap: 20px !important;
                        }

                        .col-left {
                            flex: 2 !important;
                        }

                        .col-right {
                            flex: 1 !important;
                        }

                        /* CARD */
                        .card {
                            background: #fff !important;
                            border-radius: 10px !important;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
                            padding: 18px !important;
                            margin-bottom: 20px !important;
                            line-height: 1.6 !important;
                        }

                        /* IMAGE */
                        .feature-img {
                            width: 100% !important;
                            border-radius: 8px !important;
                            max-height: 300px !important;
                            object-fit: cover !important;
                        }

                        /* LIST */
                        .custom-list {
                            list-style: none !important;
                            padding: 0 !important;
                        }

                        .custom-list li {
                            padding: 6px 0 !important;
                            font-size: 15px !important;
                        }

                        /* RIGHT PANEL */
                        .stat-box {
                            background: #f9f9f9 !important;
                            padding: 10px !important;
                            border-radius: 6px !important;
                            margin-bottom: 8px !important;
                            font-size: 14px !important;
                        }

                        .quick-btn {
                            display: block !important;
                            width: 100% !important;
                            padding: 10px !important;
                            text-align: center !important;
                            border-radius: 5px !important;
                            margin-top: 10px !important;
                            text-decoration: none !important;
                            font-size: 14px !important;
                            font-weight: 600 !important;
                        }

                        .btn-yellow {
                            background: var(--yellow) !important;
                            color: #000 !important;
                            width: 10%;
                        }

                        .btn-outline {
                            border: 1px solid #ccc !important;
                            color: #333 !important;
                        }

                        /* RESPONSIVE */
                        @media (max-width: 768px) {
                            .row {
                                flex-direction: column !important;
                            }
                        }

                        .manage-top-btn {
                            position: absolute !important;
                            top: 50% !important;
                            right: 20px !important;
                            transform: translateY(-50%) !important;
                            background: #fec700 !important;
                            padding: 8px 16px !important;
                            border-radius: 6px !important;
                            border: none !important;
                            font-weight: 600 !important;
                            cursor: pointer !important;
                        }

                        .list-item {
                            display: flex;
                            justify-content: space-between;
                            padding: 12px;
                            border: 1px solid #eee;
                            margin-bottom: 10px;
                            border-radius: 6px;
                        }

                        /* ===== FEATURES SECTION ===== */

                        .features-row {
                            display: flex !important;
                            gap: 25px !important;
                            margin-top: 10px !important;
                        }

                        .features-col {
                            flex: 1 !important;
                            padding: 15px !important;
                            border-radius: 10px !important;
                        }

                        /* 🟢 INCLUSIONS */
                        // .features-col.inclusions {
                        //     background: #f6fff7 !important;
                        //     border: 1px solid #d4edda !important;
                        // }

                        // .features-col.inclusions h5 {
                        //     color: #28a745 !important;
                        // }

                        /* 🔴 EXCLUSIONS */
                        // .features-col.exclusions {
                        //     background: #fff6f6 !important;
                        //     border: 1px solid #f5c6cb !important;
                        // }

                        // .features-col.exclusions h5 {
                        //     color: #dc3545 !important;
                        // }

                        /* LIST */
                        .custom-list {
                            list-style: none !important;
                            padding: 0 !important;
                        }

                        .custom-list li {
                            padding: 6px 0 !important;
                            font-size: 15px !important;
                        }

                        /* ICONS */
                        .features-col.inclusions li::before {
                            content: "✔ " !important;
                            // color: #28a745 !important;
                            font-weight: bold;
                        }

                        .features-col.exclusions li::before {
                            content: "✖ " !important;
                            // color: #dc3545 !important;
                            font-weight: bold;
                        }

                        /* MOBILE */
                        @media (max-width: 768px) {
                            .features-row {
                                flex-direction: column !important;
                            }
                        }

                        /* ===== COMPANY LOGO ===== */
                        
                        /* ===== LOGIN POPUP ===== */

                        .login-modal {
                            display: none;
                            position: fixed;
                            z-index: 9999;
                            left: 0;
                            top: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0,0,0,0.7);
                        }

                        .login-modal-content {
                            position: relative;
                            width: 95%;           /* more width */
                            max-width: 900px;     /* increased from 500px */
                            height: 80vh;         /* responsive height */
                            margin: 3% auto;      /* less top gap */
                            background: #fff;
                            border-radius: 10px;
                            overflow: hidden;
                        }

                        /* iframe */
                        #loginFrame {
                            width: 100%;
                            height: 100%;
                            border: none;
                        }

                        /* close button */
                        .close-btn {
                            position: absolute;
                            top: 10px;
                            right: 15px;
                            font-size: 22px;
                            font-weight: bold;
                            cursor: pointer;
                            z-index: 10;
                        }

                        /* ===== CONTACT SECTION ===== */
                        .contact-section {
                            margin-top: 30px;
                            text-align: left;
                        }

                        .contact-btn {
                            display: block;
                            width: 100%;
                            background-color: #fec700;
                            color: #000;
                            padding: 12px;
                            text-align: center;
                            border: none;
                            border-radius: 6px;
                            font-weight: 600;
                            cursor: pointer;
                        }

                        /* ===== MODAL ===== */
                        .contact-modal {
                            display: none;
                            position: fixed;
                            z-index: 9999;
                            left: 0;
                            top: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0,0,0,0.6);

                            align-items: center;
                            justify-content: center;
                        }

                        /* ===== MODAL BOX ===== */
                        .contact-modal-content {
                            background: #fff;
                            width: 600px;
                            max-width: 90%;
                            border-radius: 10px;
                            display: flex;
                            overflow: hidden;
                            position: relative;
                        }

                        /* CLOSE BUTTON */
                        .close-btn {
                            position: absolute;
                            right: 15px;
                            top: 10px;
                            font-size: 22px;
                            cursor: pointer;
                        }

                        /* LEFT INFO */
                        .contact-left {
                            width: 40%;
                            background: #fec700;
                            padding: 20px;
                            color: #000;
                        }

                        /* RIGHT FORM */
                        .contact-right {
                            width: 60%;
                            padding: 20px;
                        }

                        .contact-right input,
                        .contact-right textarea {
                            width: 100%;
                            margin-bottom: 10px;
                            padding: 10px;
                            border: 1px solid #ccc;
                            border-radius: 6px;
                        }

                        /* SUBMIT BUTTON */
                        .submit-btn {
                            width: 100%;
                            background: #fec700;
                            border: none;
                            padding: 10px;
                            font-weight: 600;
                            cursor: pointer;
                        }

                        /* SWEET ALERT FIX */
                        .swal2-container {
                            z-index: 1000000 !important;
                        }

                        /* ===== STATIC SECTION ===== */
                        .static-section {
                            margin-top: 30px;
                            text-align: left;
                        }
                    </style>
                    <style>
                        /* ===== LANDING CONTACT MODAL ===== */
                        .landing-contact-modal {
                            display: none;
                            position: fixed;
                            z-index: 9999;
                            left: 0;
                            top: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0,0,0,0.6);

                            align-items: center;
                            justify-content: center;
                        }

                        .landing-contact-modal-content {
                            width: 80%;
                            max-width: 900px;
                            background: #fff;
                            border-radius: 10px;
                            overflow: hidden;
                            position: relative;
                        }

                        .landing-contact-container {
                            display: flex;
                        }

                        /* LEFT */
                        .landing-contact-left {
                            width: 40%;
                            background: #f5f5f5;
                            padding: 20px;
                        }

                        /* RIGHT */
                        .landing-contact-right {
                            width: 60%;
                            padding: 20px;
                        }

                        .landing-contact-right input,
                        .landing-contact-right textarea {
                            width: 100%;
                            padding: 10px;
                            margin-bottom: 10px;
                            border: 1px solid #ccc;
                        }

                        .landing-contact-right button {
                            background: #fec700;
                            border: none;
                            padding: 10px;
                            width: 100%;
                            border-radius: 20px;
                            cursor: pointer;
                            font-weight: 600;
                        }

                        .landing-contact-close {
                            position: absolute;
                            top: 10px;
                            right: 15px;
                            font-size: 26px;
                            font-weight: bold;
                            cursor: pointer;
                        }

                        /* SWEET ALERT FIX */
                        .swal2-container {
                            z-index: 1000000 !important;
                        }
                    </style>
                    </head>
                    <body>
                        <div class="content-wrapper">

                            <!-- HERO -->

                            <div class="top-header">

                                <!-- LEFT: LOGO -->
                                <div class="header-left">
                                    <?php if (!empty(\$logo)): ?>
                                        <img src="../uploads/company_logo/<?php echo htmlspecialchars(\$logo); ?>" alt="Company Logo">
                                    <?php endif; ?>
                                </div>

                                <!-- RIGHT: CONTACT -->

                                <div class="header-right">

                                    <?php if (!empty(\$phone_no)): ?>
                                        <span class="contact-item">📞 <?php echo htmlspecialchars(\$phone_no); ?></span>
                                    <?php endif; ?>

                                    <?php if (!empty(\$email)): ?>
                                        <span class="contact-item">✉ <?php echo htmlspecialchars(\$email); ?></span>
                                    <?php endif; ?>

                                </div>

                            </div>
                            
                            <div class="banner-section">

                                <?php if (!empty(\$cat_row['cat_img'])): ?>
                                    <img src="../<?php echo htmlspecialchars(\$cat_row['cat_img']); ?>" class="banner-img">
                                <?php endif; ?>

                                <div class="banner-overlay">
                                    <h2 class="banner-title">
                                        <?php echo htmlspecialchars(\$cat_row['cat_name']); ?>
                                    </h2>
                                </div>

                            </div>

                            <div class="row">
                                <!-- LEFT -->
                                <div class="col-left" style="flex: 100% !important;">

                                    <div class="card">
                                        <h4>Description</h4>
                                        <p><?php echo nl2br(htmlspecialchars(\$cat_row['cat_des'] ?? 'No description available')); ?></p>
                                    </div>

                                    <?php 
                                    \$inc = json_decode(\$cat_row['cat_inc'] ?? '[]', true); 
                                    \$exc = json_decode(\$cat_row['cat_exc'] ?? '[]', true); 
                                    ?>

                                    <div class="card">
                                        <h4>Features</h4>

                                        <div class="features-row">

                                            <!-- INCLUSIONS -->
                                            <div class="features-col inclusions">
                                                <ul class="custom-list">
                                                    <?php if (!empty(\$inc)): ?>
                                                        <?php foreach (\$inc as \$item): ?>
                                                            <li><?php echo htmlspecialchars(\$item); ?></li>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <li>No inclusions available</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>

                                            <!-- EXCLUSIONS -->
                                            <div class="features-col exclusions">
                                                <ul class="custom-list">
                                                    <?php if (!empty(\$exc)): ?>
                                                        <?php foreach (\$exc as \$item): ?>
                                                            <li><?php echo htmlspecialchars(\$item); ?></li>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <li>No exclusions available</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- ================= PRICING TABLE ================= -->
                                    <?php
                                        \$cat_type = strtolower(trim(\$cat_row['cat_type'] ?? ''));

                                        \$package_enabled = !empty(\$cat_row['package_login_type']);
                                        \$product_enabled = !empty(\$cat_row['product_login_type']);

                                        \$showPackages = (\$cat_type === 'package');
                                        \$showProducts = (\$cat_type === 'product');
                                    ?>
                                    <?php if (\$showPackages): ?>
                                        <?php if (!empty(\$cat_row['pkg_with_login']) && !\$isLoggedIn): ?>
                                            <!-- 🔒 LOGIN REQUIRED MESSAGE -->
                                            <div class="card">
                                                <h4>Packages Pricing Table</h4>
                                                <p class="text-center" style="font-size:16px; font-weight:600; margin-top:10px;">
                                                    <a href="#" onclick="openLoginPopup()" class="btn mt-2">
                                                        🔒 Sign-In to See the Packages
                                                    </a>
                                                </p>
                                            </div>
                                        <?php else: ?>
                                            <?php
                                                \$packages = [];
                                                \$durations = [];
                                                \$product_category = \$cat_row['cat_id'] ?? 0;

                                                // Fetch packages
                                                \$stmt = \$conn->prepare("
                                                    SELECT * FROM package 
                                                    WHERE is_deleted=0 AND is_active=1 AND cat_id=?
                                                ");
                                                \$stmt->bind_param("i", \$product_category);
                                                \$stmt->execute();
                                                \$res = \$stmt->get_result();

                                                \$data=[];
                                                while(\$row=\$res->fetch_assoc()){ \$data[\$row['id']]=\$row; }
                                                \$stmt->close();

                                                if(!empty(\$data)){
                                                    \$ids = implode(',', array_keys(\$data));

                                                    \$sql = "SELECT d.*, p.title, p.subtitle, p.description, p.package_name, p.is_active pkg_active
                                                            FROM durations d
                                                            JOIN package p ON d.package_id=p.id
                                                            WHERE d.package_id IN (\$ids)";
                                                    \$r=\$conn->query(\$sql);

                                                    while(\$row=\$r->fetch_assoc()){
                                                        \$dur=\$row['duration'];
                                                        \$packages[\$dur][]=\$row;
                                                        \$durations[\$dur]=\$dur;
                                                    }
                                                }

                                                // currency
                                                \$symbol="\$";
                                                \$r=\$conn->query("SELECT symbol FROM currencies WHERE is_active=1 LIMIT 1");
                                                if(\$row=\$r->fetch_assoc()) \$symbol=\$row['symbol'];
                                            ?>
                                            <div class="card">
                                                <h4>Packages Pricing Table</h4>
                                                <div class="card-body">
                                                    <div class="row justify-content-center">
                                                        <div class="col-xxl-10">

                                                        <?php if (!empty(\$packages)): ?>
                                                            <!-- Duration Tabs -->
                                                            <ul class="nav nav-pills button-tab mt-32 mb-32 justify-content-center" id="pills-tab" role="tablist">
                                                                <?php \$first = true; foreach (\$durations as \$duration_name): ?>
                                                                    <li class="nav-item" role="presentation">
                                                                        <button 
                                                                            class="nav-link px-24 py-10 text-md rounded-pill text-secondary-light fw-medium <?= \$first ? 'active' : '' ?>" 
                                                                            id="tab-<?= md5(\$duration_name) ?>" 
                                                                            data-bs-toggle="pill" 
                                                                            data-bs-target="#tab-pane-<?= md5(\$duration_name) ?>" 
                                                                            type="button" 
                                                                            role="tab" 
                                                                            aria-controls="tab-pane-<?= md5(\$duration_name) ?>" 
                                                                            aria-selected="<?= \$first ? 'true' : 'false' ?>">
                                                                            <?= htmlspecialchars(\$duration_name) ?>
                                                                        </button>
                                                                    </li>
                                                                <?php \$first = false; endforeach; ?>
                                                            </ul>

                                                            <!-- Duration Tab Content -->
                                                            <div class="tab-content" id="pills-tabContent">
                                                                <?php \$first = true; foreach (\$durations as \$duration_name): ?>
                                                                    <div class="tab-pane fade <?= \$first ? 'show active' : '' ?>" 
                                                                        id="tab-pane-<?= md5(\$duration_name) ?>" 
                                                                        role="tabpanel" 
                                                                        aria-labelledby="tab-<?= md5(\$duration_name) ?>" 
                                                                        tabindex="0">

                                                                        <div class="row gy-4">
                                                                            <?php foreach (\$packages[\$duration_name] as \$package): ?>
                                                                                <div class="col-xxl-4 col-sm-6">
                                                                                    <div class="pricing-plan position-relative radius-24 overflow-hidden border">
                                                                                        <?php \$isActive = (\$package['pkg_active'] == 1); ?>
                                                                                        <?php if (!\$isActive): ?>
                                                                                            <p class="mb-0 text-sm text-danger fw-semibold mt-2 float-end">Inactive</p>
                                                                                        <?php endif; ?> 

                                                                                        <h5 class="mb-0 lufera-color"><?= htmlspecialchars(\$package['title']) ?></h5>
                                                                                        <p class="mb-0 text-secondary-light mb-28"><?= htmlspecialchars(\$package['subtitle']) ?></p>

                                                                                        <h4 class="mb-24">
                                                                                        <p class="text-sm text-muted mt-0 mb-10 text-decoration-line-through"><?= htmlspecialchars(\$symbol) ?> <?= number_format((float)\$package['preview_price'], 0, '.', ',') ?></p>
                                                                                            <?= htmlspecialchars(\$symbol) ?>
                                                                                            <?= number_format((float)\$package['price'], 0, '.', ',') ?>
                                                                                            <span class="fw-medium text-md text-secondary-light">/
                                                                                                <?= htmlspecialchars(\$package['duration']) ?>
                                                                                            </span>
                                                                                            
                                                                                        </h4>

                                                                                        <span class="mb-20 fw-medium"><?= htmlspecialchars(\$package['description']) ?></span>

                                                                                        <ul>
                                                                                            <?php
                                                                                            \$package_id = \$package['package_id'];
                                                                                            \$feature_sql = "SELECT feature FROM features WHERE package_id = \$package_id";
                                                                                            \$feature_result = \$conn->query(\$feature_sql);
                                                                                            if (\$feature_result && \$feature_result->num_rows > 0):
                                                                                                while (\$feat = \$feature_result->fetch_assoc()):
                                                                                            ?>
                                                                                                <li class="d-flex align-items-center gap-16 mb-16">
                                                                                                    <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                                                                        <iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>
                                                                                                    </span>
                                                                                                    <span class="text-secondary-light text-lg"><?= htmlspecialchars(\$feat['feature']) ?></span>
                                                                                                </li>
                                                                                            <?php endwhile; endif; ?>
                                                                                        </ul>

                                                                                        <form action="../cart.php" method="POST">
                                                                                            <input type="hidden" name="type" value="package">
                                                                                            <input type="hidden" name="id" value="<?= htmlspecialchars(\$package['package_id']) ?>">
                                                                                            <input type="hidden" name="plan_name" value="<?= htmlspecialchars(\$package['package_name']) ?>">
                                                                                            <input type="hidden" name="title" value="<?= htmlspecialchars(\$package['title']) ?>">
                                                                                            <input type="hidden" name="subtitle" value="<?= htmlspecialchars(\$package['subtitle']) ?>">
                                                                                            <input type="hidden" name="price" value="<?= htmlspecialchars(\$package['price']) ?>">
                                                                                            <input type="hidden" name="duration" value="<?= htmlspecialchars(\$package['duration']) ?>">
                                                                                            <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                                                                            <!-- <input type="hidden" name="addon_service" value="<?= htmlspecialchars(\$package['addon_service']) ?>">
                                                                                            <input type="hidden" name="addon_package" value="<?= htmlspecialchars(\$package['addon_package']) ?>">
                                                                                            <input type="hidden" name="addon_product" value="<?= htmlspecialchars(\$package['addon_product']) ?>">
                                                                                            <input type="hidden" name="gst_id" value="<?= htmlspecialchars(\$package['gst_id']) ?>"> -->

                                                                                            <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28" <?= !\$isActive ? 'disabled' : '' ?>>Get started</button>
                                                                                        </form>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php \$first = false; endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-center py-32">
                                                                <div class="radius-12 p-12">
                                                                    <h6 class="mb-0" style="color: #000; font-size: 1.125rem; font-weight: 600;">
                                                                        No packages available.
                                                                    </h6>
                                                                    <div style="height: 3px; width: 60px; background-color: #fdc701; margin: 12px auto 0; border-radius: 2px;"></div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (\$showProducts): ?>
                                        <?php if (!empty(\$cat_row['prod_with_login']) && !\$isLoggedIn): ?>

                                            <!-- 🔒 LOGIN REQUIRED MESSAGE -->
                                            <div class="card">
                                                <h4>Products Pricing Table</h4>
                                                <p class="text-center" style="font-size:16px; font-weight:600; margin-top:10px;">
                                                    <a href="#" onclick="openLoginPopup()" class="btn mt-2">
                                                        🔒 Sign-In to See the Products
                                                    </a>
                                                </p>
                                            </div>

                                        <?php else: ?>
                                            
                                            <style>
                                                .hover-scale-img__img{
                                                    height:200px;
                                                }
                                            </style>

                                            <?php
                                                \$products = [];
                                                \$product_category = \$cat_row['cat_id'] ?? 0;

                                                \$stmt = \$conn->prepare("
                                                    SELECT * FROM products 
                                                    WHERE cat_id = ? AND is_deleted != 1
                                                ");
                                                \$stmt->bind_param("i", \$product_category);
                                                \$stmt->execute();
                                                \$res = \$stmt->get_result();

                                                while (\$row = \$res->fetch_assoc()) {
                                                    \$products[] = \$row;
                                                }
                                                \$stmt->close();

                                                // currency
                                                \$symbol = "\$";
                                                \$r = \$conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
                                                if (\$row = \$r->fetch_assoc()) \$symbol = \$row['symbol'];
                                            ?>

                                            <div class="card">
                                                <h4 class="mb-20">Products Pricing Table</h4>
                
                                                <div class="row gy-4">
                                                    <?php if (!empty(\$products)): ?>
                                                        <?php foreach (\$products as \$row): ?>
                                                        <?php \$isActive = (\$row['is_active'] == 1); ?>

                                                        <div class="col-lg-4 col-md-4 col-sm-6">

                                                            <?php if (\$isActive): ?>
                                                                <a href="../product-details.php?id=<?php echo \$row['id']; ?>" class="d-block">
                                                            <?php endif; ?>

                                                            <div class="pb-16 hover-scale-img border radius-16 overflow-hidden <?php echo !\$isActive ? 'inactive-product' : ''; ?>">

                                                                <div class="max-h-266-px overflow-hidden">
                                                                    <img src="../uploads/products/<?php echo \$row['product_image']; ?>"
                                                                        class="hover-scale-img__img w-100 object-fit-cover <?php echo !\$isActive ? 'grayscale' : ''; ?>">
                                                                </div>

                                                                <div class="py-16 px-24">
                                                                    <h6 class="mb-4"><?php echo htmlspecialchars(\$row['name']); ?></h6>

                                                                    <p class="mb-0 text-sm text-secondary-light">
                                                                        <b>Price</b> : <?= \$symbol ?><?php echo \$row['price']; ?>
                                                                    </p>

                                                                    <p class="mb-0 text-sm text-secondary-light float-start">
                                                                        <b>Validity</b> : <?php echo \$row['duration']; ?>
                                                                    </p>

                                                                    <?php if (!\$isActive): ?>
                                                                        <p class="text-danger fw-semibold float-end mt-2">Inactive</p>
                                                                    <?php endif; ?>
                                                                </div>

                                                            </div>

                                                            <?php if (\$isActive): ?>
                                                                </a>
                                                            <?php endif; ?>

                                                        </div>

                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="text-center py-32">
                                                            <div class="radius-12 p-12">
                                                                <h6 class="mb-0" style="color: #000; font-size: 1.125rem; font-weight: 600;">
                                                                    No products available.
                                                                </h6>
                                                                <div style="height: 3px; width: 60px; background-color: #fdc701; margin: 12px auto 0; border-radius: 2px;"></div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                </div> 
                                            </div>

                                        <?php endif; ?>

                                    <?php endif; ?>

                                    <!-- ================= CONTACT BUTTON SECTION ================= -->
                                    <div class="card" style="text-align:left;">
                                        <h4>Need Help?</h4>
                                        <button onclick="openContactPopup()" class="btn btn-yellow" style="margin-top:10px;">
                                            Contact Us
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== FOOTER ===== -->
                            <footer class="d-footer" style="padding:15px 10px; border-top:1px solid #eee;">
                                <div class="row align-items-center justify-content-between">
                                    <div class="col-auto">
                                        <p class="mb-0">© <?php echo date("Y"); ?> Lufera Infotech. All Rights Reserved.</p>
                                    </div>
                                    <div class="col-auto" style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
                                        <a href="privacy_policy.php">Privacy Policy</a>
                                        <span>|</span>
                                        <a href="terms_conditions.php">Terms and Conditions</a>
                                        <span>|</span>
                                        <a href="https://luferatech.com" style="display:flex; align-items:center; gap:5px;">
                                            <span>Made by</span>
                                            <span style="color:#fec700;">Lufera Infotech</span>
                                        </a>
                                    </div>
                                </div>
                            </footer>
                        </div>

                        <!-- LOGIN POPUP -->
                        <div id="loginModal" class="login-modal">
                            <div class="login-modal-content">

                                <!-- CLOSE BUTTON -->
                                <span class="close-btn" onclick="closeLoginPopup()">&times;</span>

                                <!-- LOGIN PAGE -->
                                <iframe id="loginFrame" src=""></iframe>

                            </div>
                        </div>

                        <script>

                            function openLoginPopup() {
                                document.getElementById("loginModal").style.display = "block";

                                // Load login page inside popup
                                document.getElementById("loginFrame").src =
                                    "../sign-in.php?redirect=<?php echo urlencode(\$_SERVER['REQUEST_URI']); ?>";
                            }

                            function closeLoginPopup() {
                                document.getElementById("loginModal").style.display = "none";
                            }

                            // Close when clicking outside
                            window.onclick = function(event) {
                                let modal = document.getElementById("loginModal");
                                if (event.target === modal) {
                                    closeLoginPopup();
                                }
                            };

                        </script>

                        <!-- ================= LANDING CONTACT POPUP ================= -->
                        <div id="landingContactModal" class="landing-contact-modal">

                            <div class="landing-contact-modal-content">

                                <span class="landing-contact-close" onclick="closeContactPopup()">&times;</span>

                                <div class="landing-contact-container">

                                    <!-- LEFT SIDE -->
                                    <div class="landing-contact-left">

                                        <h4>CALL US</h4>
                                        <p><?php echo htmlspecialchars(\$company['phone_no'] ?? 'N/A'); ?></p>

                                        <h4>LOCATION</h4>
                                        <p><?php echo htmlspecialchars(\$company['address'] ?? 'N/A'); ?></p>

                                        <h4>BUSINESS HOURS</h4>
                                        <p>Mon - Fri: 10am - 6pm</p>

                                    </div>

                                    <!-- RIGHT SIDE -->
                                    <div class="landing-contact-right">

                                        <h3>CONTACT US</h3>

                                        <input type="text" id="contactName" placeholder="Enter your name" required>
                                        <input type="text" id="contactPhone" placeholder="Enter your phone number" required>
                                        <input type="email" id="contactEmail" placeholder="Enter your email address" required>
                                        <textarea id="contactMessage" placeholder="Enter your message" rows="4" required></textarea>

                                        <button onclick="submitContact()">SUBMIT</button>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                        <script>
                            function openContactPopup() {
                                document.getElementById("landingContactModal").style.display = "flex";
                            }

                            function closeContactPopup() {
                                document.getElementById("landingContactModal").style.display = "none";
                            }

                            function submitContact() {

                                const name = document.getElementById("contactName").value.trim();
                                const phone = document.getElementById("contactPhone").value.trim();
                                const email = document.getElementById("contactEmail").value.trim();
                                const message = document.getElementById("contactMessage").value.trim();

                                const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;

                                // ✅ 1. Email format check FIRST (only if user typed something)
                                if (email && !emailPattern.test(email)) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Invalid Email',
                                        text: 'Please enter a valid email address (e.g. john@gmail.com)',
                                        confirmButtonColor: '#fec700'
                                    });
                                    return;
                                }

                                // ✅ 2. Required fields check
                                if (!name || !phone || !email || !message) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Missing Fields',
                                        text: 'Please fill all fields',
                                        confirmButtonColor: '#fec700'
                                    });
                                    return;
                                }

                                // ✅ 3. Proceed
                                Swal.fire({
                                    title: 'Submitting...',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                setTimeout(() => {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Submitted!',
                                        text: 'Our team will contact you.',
                                        confirmButtonColor: '#fec700'
                                    }).then(() => {

                                        closeContactPopup();

                                        document.getElementById("contactName").value = "";
                                        document.getElementById("contactPhone").value = "";
                                        document.getElementById("contactEmail").value = "";
                                        document.getElementById("contactMessage").value = "";
                                    });
                                }, 1000);
                            }
                        </script>
                        
                    </body>
                    </html>
                PHP;

                // Create file only if not exists
                if (!file_exists($landingFilePath)) {
                    file_put_contents($landingFilePath, $landingContent);
                }

                $view_file_name = "view-$catSlug1.php";
                $view_file_path = realpath(__DIR__) . '/' . $view_file_name;

                if (!file_exists($view_file_path)) {
                    $view_content = <<<PHP
                        <?php include './view-package.php'; ?>
                    PHP;

                    file_put_contents($view_file_path, $view_content);
                }
            }
            // Log the action
            logActivity(
                $conn,
                $Id,
                "Category",                   // module
                "New category created - $cat_name"  // description
            );
            echo "
                <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Category Created',
                    showConfirmButton: true
                }).then(() => {
                    window.history.back();
                });
                </script>";
        }
        exit;
    }

    // Edit category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_cat_id'], $_POST['edit_cat_name'], $_POST['edit_cat_url'], $_POST['edit_cat_type'])) {
        $cat_id = intval($_POST['edit_cat_id']);
        $edit_cat_name = trim($_POST['edit_cat_name']);
        $edit_cat_url = trim($_POST['edit_cat_url']);
        $edit_cat_type = trim($_POST['edit_cat_type']);

        // ===== IMAGE UPLOAD =====
        $uploadDir = 'uploads/categories/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $cat_img = $_POST['existing_cat_img'] ?? null;

        if (!empty($_FILES['cat_img']['name'])) {
            $fileName = time() . '_' . $_FILES['cat_img']['name'];
            $path = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['cat_img']['tmp_name'], $path)) {
                $cat_img = $path;
            }
        }

        // ===== DESCRIPTION =====
        $cat_des = $_POST['cat_des'] ?? null;

        // ===== INCLUSIONS =====
        $cat_inc = isset($_POST['cat_inc']) 
            ? json_encode(array_filter($_POST['cat_inc'])) 
            : null;

        // ===== EXCLUSIONS =====
        $cat_exc = isset($_POST['cat_exc']) 
            ? json_encode(array_filter($_POST['cat_exc'])) 
            : null;

        // ===== PACKAGE LOGIC =====
        $has_package = isset($_POST['has_package']) ? 1 : 0;

        $pkg_with_login = 0;
        $pkg_without_login = 0;

        if ($has_package && isset($_POST['package_login_type'])) {
            if ($_POST['package_login_type'] === 'with_login') {
                $pkg_with_login = 1;
            } elseif ($_POST['package_login_type'] === 'without_login') {
                $pkg_without_login = 1;
            }
        }

        // ===== PRODUCT LOGIC =====
        $has_product = isset($_POST['has_product']) ? 1 : 0;

        $prod_with_login = 0;
        $prod_without_login = 0;

        if ($has_product && isset($_POST['product_login_type'])) {
            if ($_POST['product_login_type'] === 'with_login') {
                $prod_with_login = 1;
            } elseif ($_POST['product_login_type'] === 'without_login') {
                $prod_without_login = 1;
            }
        }
        
        if (!str_ends_with($edit_cat_url, '.php')) {
            $edit_cat_url .= '.php';
        }

        $editcatSlug = strtolower(preg_replace('/\s+/', '-', $edit_cat_url));
        $file_path = realpath(__DIR__) . '/' . $editcatSlug;

        // ===== FINAL UPDATE QUERY =====
        $stmt = $conn->prepare("
            UPDATE categories SET 
                cat_name = ?, 
                cat_url = ?, 
                cat_type = ?, 
                cat_img = ?, 
                cat_des = ?, 
                cat_inc = ?, 
                cat_exc = ?, 
                has_package = ?, 
                has_product = ?, 
                pkg_with_login = ?, 
                pkg_without_login = ?, 
                prod_with_login = ?, 
                prod_without_login = ?
            WHERE cat_id = ?
        ");

        $stmt->bind_param(
            "sssssssiiiiiii",
            $edit_cat_name,
            $editcatSlug,
            $edit_cat_type,
            $cat_img,
            $cat_des,
            $cat_inc,
            $cat_exc,
            $has_package,
            $has_product,
            $pkg_with_login,
            $pkg_without_login,
            $prod_with_login,
            $prod_without_login,
            $cat_id
        );

        $stmt->execute();

        $editcatSlug1 = pathinfo($editcatSlug, PATHINFO_FILENAME);
        $det_file_name1 = $editcatSlug1 . '-det.php'; 
        $det_file_path1 = realpath(__DIR__) . '/' . $det_file_name1;

        $manageLink1 = $det_file_name1;
        $pageTitle1 = ucwords($edit_cat_name);

        if (!file_exists($file_path)) {
            $default_content = <<<PHP
                <?php include './partials/layouts/layoutTop.php'; ?>
                    <?php
                        \$Id = \$_SESSION['user_id'];
                        
                        if (isset(\$_GET['cat_id']) && intval(\$_GET['cat_id']) > 0) {
                            \$_SESSION['cat_id'] = intval(\$_GET['cat_id']);
                        } elseif (!isset(\$_GET['cat_id'])) {
                            // Clear the old cat_id from session when not present in URL
                            unset(\$_SESSION['cat_id']);
                        }

                        \$cat_id = \$_SESSION['cat_id'] ?? 0;

                        \$user = "select * from users where id = \$Id";
                        \$res = \$conn ->query(\$user);
                        \$row = \$res ->fetch_assoc();
                        \$UserId = \$row['id'];
                        \$role = \$row['role'];

                        if (\$role == '1' || \$role == '2' || \$role == '7') {
                        \$stmt = \$conn->prepare("
                            SELECT 
                                websites.id,
                                users.user_id,
                                users.id,
                                users.business_name,
                                websites.plan,
                                websites.domain,
                                websites.access_www,
                                websites.status,
                                websites.created_at,
                                websites.duration
                            FROM 
                                users 
                            JOIN 
                                websites ON users.id = websites.user_id
                            WHERE 
                                websites.cat_id = ?
                        ");
                        \$stmt->bind_param("i", \$cat_id);
                        } else {
                            \$stmt = \$conn->prepare("
                                SELECT 
                                    websites.id,
                                    users.user_id,
                                    users.id,
                                    users.business_name,
                                    websites.plan,
                                    websites.domain,
                                    websites.access_www,
                                    websites.status,
                                    websites.created_at,
                                    websites.duration 
                                FROM 
                                    users 
                                JOIN 
                                    websites ON users.id = websites.user_id 
                                WHERE 
                                    websites.user_id = ? AND websites.cat_id = ?
                            ");
                            \$stmt->bind_param("ii", \$UserId, \$cat_id);
                        }

                        \$stmt->execute();
                        \$result = \$stmt->get_result();

                        \$websites = [];
                        while (\$row = mysqli_fetch_assoc(\$result)) {
                            \$websites[] = \$row;
                        }

                        // Number of websites per page
                        \$websitesPerPage = 5;

                        // Get the current page from URL, default is 1
                        \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;

                        // Calculate the starting index for the websites to display on this page
                        \$startIndex = (\$page - 1) * \$websitesPerPage;

                        // Slice the websites array to get only the websites for the current page
                        \$websitesOnPage = array_slice(\$websites, \$startIndex, \$websitesPerPage);

                        // Calculate the total number of pages
                        \$totalPages = ceil(count(\$websites) / \$websitesPerPage);
                    ?>
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                    <meta charset="UTF-8">
                    <title>Websites</title>
                    <style>
                        :root {
                        --yellow: #fec700;
                        --black: #101010;
                        --mild-blue: #e6f0ff;
                        }

                        .content-wrapper {
                        width: 100%;
                        /* max-width: 1200px; */
                        margin: 20px auto;
                        padding: 10px 15px;
                        }

                        .search-card {
                        background-color: #fff;
                        border-radius: 8px;
                        padding: 15px 20px;
                        margin-bottom: 20px;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: space-between;
                        align-items: center;
                        gap: 10px;
                        }

                        .search-container {
                        position: relative;
                        flex: 1 1 300px;
                        max-width: 400px;
                        }

                        .search-icon {
                        position: absolute;
                        left: 10px;
                        top: 50%;
                        transform: translateY(-50%);
                        font-size: 16px;
                        color: #999;
                        pointer-events: none;
                        }

                        .search-container input[type="text"] {
                        width: 100%;
                        padding: 10px 10px 10px 35px;
                        font-size: 16px;
                        border: 2px solid var(--yellow);
                        border-radius: 5px;
                        box-sizing: border-box;
                        }

                        .add-btn {
                        padding: 10px 16px;
                        background-color: var(--yellow);
                        color: var(--black);
                        border: none;
                        border-radius: 5px;
                        font-weight: bold;
                        text-decoration: none;
                        cursor: pointer;
                        white-space: nowrap;
                        flex-shrink: 0;
                        }

                        .list-section {
                        background-color: #fff;
                        border-radius: 8px;
                        padding: 20px;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                        }

                        .list-section h5 {
                        margin-top: 0;
                        margin-bottom: 15px;
                        font-size: 20px;
                        }

                        .list-wrapper {
                        display: flex;
                        flex-direction: column;
                        gap: 15px;
                        }

                        .list-item {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 15px 20px;
                        border: 1px solid #eee;
                        border-left: 5px solid var(--yellow);
                        border-radius: 6px;
                        background-color: #fff;
                        transition: box-shadow 0.2s ease;
                        flex-wrap: wrap;
                        gap: 10px;
                        }

                        .list-item:hover {
                        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                        }

                        .site-info {
                        flex: 1 1 60%;
                        display: flex;
                        flex-direction: column;
                        gap: 5px;
                        min-width: 0;
                        }

                        .site-info-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        }

                        .site-info-header h6 {
                        margin: 0 0 8px 0;
                        font-weight: bold;
                        font-size: 20px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        max-width: 60%;
                        }

                        .site-info-header .plan {
                        font-size: 14px;
                        color: #555;
                        white-space: nowrap;
                        }

                        .site-info-meta {
                        font-size: 14px;
                        color: #555;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        margin-top: 5px;
                        }

                        .manage-btn-wrapper {
                        flex-shrink: 0;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                        gap: 8px;
                        }

                        .manage-btn-wrapper .plan {
                        font-size: 14px;
                        color: #555;
                        }

                        .dashboard-btn {
                        background-color: var(--yellow);
                        color: var(--black);
                        padding: 8px 16px;
                        border-radius: 5px;
                        font-weight: bold;
                        text-decoration: none;
                        white-space: nowrap;
                        transition: background-color 0.3s ease;
                        }

                        .dashboard-btn:hover {
                        background-color: #e5b800;
                        }

                        .pagination {
                        display: flex;
                        justify-content: flex-end;
                        gap: 10px;
                        margin-top: 20px;
                        }

                        .pagination a {
                        padding: 8px 15px;
                        background-color: var(--yellow);
                        color: var(--black);
                        border-radius: 5px;
                        text-decoration: none;
                        }

                        .status-active {
                        border-left: 5px solid var(--yellow);
                        /* border-left: 5px solid #4caf50; Green */
                        }

                        .status-pending {
                        border-left: 5px solid #ff9800; /* Orange */
                        }

                        .status-expired {
                        border-left: 5px solid #f44336; /* Red */
                        }

                        .domain-text-approved {
                            color: #fec700;
                        }
                        .domain-text-pending {
                            color: orange;
                        }

                        /* Increase font sizes */
                        .site-info-header h6 {
                        font-size: 20px !important;
                        }

                        .site-info-meta {
                        font-size: 16px;
                        }

                        .manage-btn-wrapper .plan {
                        font-size: 16px;
                        }

                        .dashboard-btn {
                        font-size: 16px;
                        }

                        /* Status-based colors for domain only */
                        .domain-text-active {
                        /* color: #4caf50; */
                        color: var(--yellow);
                        }

                        .domain-text-pending {
                        color: #ff9800;
                        }

                        .domain-text-expired {
                        color: #f44336;
                        }

                        .no-website {
                        justify-content: center;
                        font-size: 18px; 
                        color: #888;
                        }

                        /* Responsive */
                        @media (max-width: 700px) {
                        .list-item {
                            flex-direction: column;
                            align-items: flex-start;
                        }

                        .site-info {
                            flex: 1 1 100%;
                        }

                        .site-info-header {
                            flex-direction: column;
                            align-items: flex-start;
                            gap: 3px;
                        }

                        .site-info-header h6 {
                            max-width: 100%;
                        }

                        .site-info-header .plan {
                            font-size: 14px;
                        }

                        .manage-btn-wrapper {
                            width: 100%;
                            margin-top: 10px;
                            align-items: flex-start;
                        }
                        }
                    </style>
                    </head>
                    <body>
                        <div class="content-wrapper">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
                                <h6 class="fw-semibold mb-0">$pageTitle1</h6>
                                <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
                            </div>
                        <!-- Search + Add -->
                        <div class="search-card bg-base">
                            <div class="search-container">
                            <span class="search-icon">&#128269;</span>
                            <input type="text" id="searchInput" placeholder="Search $pageTitle1..." />
                            </div>
                            <a href="view-$editcatSlug1.php" class="add-btn">+ Add New $pageTitle1</a>
                        </div>

                        <!-- Website List -->
                        <!-- <div class="list-section" id="websiteList"> -->
                            <!-- <h5>Business WordPress Hosting</h5> -->

                            <div class="list-wrapper" id="websiteList">
                            <?php if (empty(\$websitesOnPage)): ?>
                                <div class="list-item bg-base no-website">
                                No $pageTitle1 found.
                                </div>
                            <?php else: ?>
                                <?php foreach (\$websitesOnPage as \$site): ?>
                                <?php
                                        \$status = strtolower(\$site['status']);
                                    \$CreatedAt = \$site['created_at'];
                                    \$Duration = \$site['duration'];

                                    
                                        \$statusClass = 'status-pending';
                                        if (\$status === 'active') \$statusClass = 'status-active';
                                        elseif (\$status === 'expired') \$statusClass = 'status-expired';

                                    
                                        \$domainColorClass = 'domain-text-pending'; // default
                                        if (\$status === 'approved') {
                                            \$domainColorClass = 'domain-text-approved';
                                        }

                                        

                            \$startDate = new DateTime(\$CreatedAt);
                            \$endDate = (clone \$startDate)->modify("+{\$Duration}");
                            \$Validity = \$startDate->format("d-m-Y") . " to " . \$endDate->format("d-m-Y");

                            if (\$status === 'approved') {
                                \$expiresText = htmlspecialchars(\$Validity);
                                \$color = '#89836f'; // yellow
                            } else {
                                \$expiresText = 'N/A';
                                \$color = 'orange'; // fallback
                            }
                                ?>
                                <div class="list-item bg-base <?php echo \$statusClass; ?>">
                                    <div class="site-info">
                                    <!-- Domain Title -->
                                    <div class="site-info-header">
                                        <h6>
                                        <?php echo htmlspecialchars(\$site['plan']); ?>
                                        <span style="visibility:hidden"><?php echo htmlspecialchars(\$site['user_id']); ?></span>
                                        </h6>
                                    </div>
                                    <!-- Website (no link, color applied only to domain text) -->
                                    <div class="site-info-meta">
                                        $pageTitle1: 
                                        <span class="<?php echo \$domainColorClass; ?>">
                                            <?php echo htmlspecialchars(\$site['access_www']); ?>
                                        </span>
                                    </div>
                                    <div class="site-info-meta" style="color: <?php echo \$color; ?>;">
                                        <strong>Expires:</strong>
                                        <?php echo \$Validity; ?>
                                    </div>
                                    </div>
                                    <div class="manage-btn-wrapper">
                                    <!-- <a href="dashboard.php?site=<?php echo urlencode(\$site['domain']); ?>" class="dashboard-btn">Manage</a> -->
                                    <a href="$manageLink1?website_id=<?php echo (int)\$site['id']; ?>" class="dashboard-btn">Manage</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination">
                            <?php if (\$page > 1): ?>
                                <a href="?page=<?php echo \$page - 1; ?>">Previous</a>
                            <?php endif; ?>

                            <?php for (\$i = 1; \$i <= \$totalPages; \$i++): ?>
                                <a href="?page=<?php echo \$i; ?>" class="<?php echo \$i === \$page ? 'active' : ''; ?>">
                                <?php echo \$i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if (\$page < \$totalPages): ?>
                                <a href="?page=<?php echo \$page + 1; ?>">Next</a>
                            <?php endif; ?>
                            </div>

                        <!-- </div> -->
                        </div>

                        <script>
                            const searchInput = document.getElementById('searchInput');
                            searchInput.addEventListener('keyup', function () {
                                const filter = searchInput.value.toLowerCase();
                                const items = document.querySelectorAll("#websiteList .list-item");
                                items.forEach(item => {
                                const text = item.innerText.toLowerCase();
                                item.style.display = text.includes(filter) ? '' : 'none';
                                });
                            });
                        </script>
                    </body>
                    </html>
                <?php include './partials/layouts/layoutBottom.php' ?>
            PHP;

            file_put_contents($file_path, $default_content);
        }

        logActivity(
            $conn,
            $Id,
            "Category",                   // module
            "Category updated - $edit_cat_name."  // description
        );

        echo "
        <script>
        Swal.fire({
            icon: 'success',
            title: 'Category Updated',
            showConfirmButton: true
        }).then(() => {
            window.history.back();
        });
        </script>";
        exit;
    }

    // Delete category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cat_id'])) {
        $cat_id = intval($_POST['delete_cat_id']);

        $stmt = $conn->prepare("SELECT cat_url, cat_name FROM categories WHERE cat_id = ?");
        $stmt->bind_param("i", $cat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cat = $result->fetch_assoc();
        $catName = $cat['cat_name'];
        $catUrlRaw = $cat['cat_url'] ?? null;

        if ($catUrlRaw) {
            $catUrlRawEdit = strtolower(preg_replace('/\s+/', '-', $catUrlRaw));
            $catUrlFinal = pathinfo($catUrlRawEdit, PATHINFO_FILENAME);

            //$baseDir = dirname(__DIR__);
            $baseDir = realpath(__DIR__); 
            $filesToDelete = [
                "$baseDir/{$catUrlFinal}.php",
                "$baseDir/{$catUrlFinal}-det.php",
                "$baseDir/add-{$catUrlFinal}.php",
                "$baseDir/view-{$catUrlFinal}.php",
                // "$baseDir/{$catUrlFinal}-wizard.php"
                "$baseDir/pages/{$catUrlFinal}.php",
            ];

            foreach ($filesToDelete as $file) {
                if (file_exists($file)) {
                    unlink($file); // Delete the file
                }
            }
        }

        $stmt = $conn->prepare("DELETE FROM categories WHERE cat_id = ?");
        $stmt->bind_param("i", $cat_id);
        $stmt->execute();

        logActivity(
            $conn,
            $Id,
            "Category",               // module
            "Category Deleted - $catName has been removed."  // description
        );

        echo "
        <script>
        Swal.fire({
            icon: 'success',
            title: 'Category deleted',
            showConfirmButton: true
        }).then(() => {
            window.history.back();
        });
        </script>";
        exit;
    }
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            <h6 class="fw-semibold mb-0">Categories</h6>
            <a data-bs-toggle="modal" 
            data-bs-target="#add-category-modal" class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" >
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                Add New Category
            </a>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table mb-0" id="productPackageTable">
                        <thead>
                            <tr>
                                <th scope="col">Category Name</th>
                                <th scope="col">Category URL</th>
                                <th scope="col">Category Type</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['cat_name']) ?></td>
                                <td><?= htmlspecialchars($row['cat_url']) ?></td>
                                <td><?= htmlspecialchars($row['cat_type']) ?></td>
                                <td class="text-center">
                                    <a 
                                    onclick='openEditModal(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>)' 
                                    class="fa fa-edit fw-medium w-32-px h-32-px bg-warning-focus text-warning-main rounded-circle d-inline-flex align-items-center justify-content-center cursor-pointer">
                                    </a>
                                    <form method="post" class="delete-form d-inline">
                                    <input type="hidden" name="delete_cat_id" value="<?= $row['cat_id'] ?>">
                                    <a onclick="confirmDelete(this)" class="fa fa-trash-alt fw-medium w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center cursor-pointer">
                                    </a>
                                </form>
                                    
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="add-category-modal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered custom-modal">
            <div class="modal-content">
                <form method="post" action="" enctype="multipart/form-data" id="add-category-form">    
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Image -->
                        <div class="mb-8">
                            <label for="cat_img" class="form-label">Featured Image</label>
                            <input type="file" name="cat_img" class="form-control" accept="image/*" required >
                        </div>
                        <div class="mb-8">
                            <label for="cat_name" class="form-label">Name</label>
                            <input type="text" class="form-control" name="cat_name" id="cat_name" required >
                        </div>
                        <!-- Description -->
                        <div class="mb-8">
                            <label for="cat_des" class="form-label">Description</label>
                            <textarea name="cat_des" class="form-control" required></textarea>
                        </div>
                        <div class="mb-8">
                            <label for="cat_url" class="form-label">URL</label>
                            <input type="text" class="form-control" name="cat_url" id="cat_url" required >
                        </div>
                        <div class="mb-8">
                            <label for="cat_type" class="form-label">Type</label>
                            <select class="form-control" name="cat_type" id="cat_type" required>
                                <option value="">Select type</option>
                                <option value="package">Package</option>
                                <option value="product">Product</option>
                            </select>
                        </div>
                        <!-- Inclusions -->
                        <div class="mb-8">
                            <label for="cat_inc[]" class="form-label">Inclusions</label>
                            <div id="inc-wrapper">
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" name="cat_inc[]" class="form-control" required>
                                    <button type="button" class="btn btn-success btn-sm" onclick="addInc()">+</button>
                                </div>
                            </div>
                        </div>
                        <!-- Exclusions -->
                        <div class="mb-8">
                            <label for="cat_exc[]" class="form-label">Exclusions</label>
                            <div id="exc-wrapper">
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" name="cat_exc[]" class="form-control" required>
                                    <button type="button" class="btn btn-success btn-sm" onclick="addExc()">+</button>
                                </div>
                            </div>
                        </div>
                        <div id="packageFields">
                            <div class="mb-8">
                                <label class="form-label">Packages</label><br>

                                <label style="display:flex; align-items:center; gap:8px;">
                                    <input type="checkbox" id="packageCheck" required>
                                    Enable Packages
                                </label>

                                <div id="packageOptions" style="display:none; margin-left:20px;">
                                    <label>
                                        <input type="radio" name="package_login_type" value="with_login" required>
                                        With Login
                                    </label><br>

                                    <label>
                                        <input type="radio" name="package_login_type" value="without_login" required>
                                        Without Login
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div id="productFields">
                            <div class="mb-8">
                                <label class="form-label">Products</label><br>

                                <label style="display:flex; align-items:center; gap:8px;">
                                    <input type="checkbox" id="productCheck" required>
                                    Enable Products
                                </label>

                                <div id="productOptions" style="display:none; margin-left:20px;">
                                    <label>
                                        <input type="radio" name="product_login_type" value="with_login" required>
                                        With Login
                                    </label><br>

                                    <label>
                                        <input type="radio" name="product_login_type" value="without_login" required>
                                        Without Login
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="submit" class="btn lufera-bg" name="save" value="Save">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="edit-category-modal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered custom-modal">    
            <div class="modal-content">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="edit_cat_id" id="edit_cat_id">
                        <!-- Image -->
                        <div class="mb-8">
                            <label class="form-label">Featured Image</label>
                            <img id="edit_preview_img" src="" width="80" style="margin-bottom: .5rem;display:none;">
                            <input type="hidden" id="existing_cat_img" name="existing_cat_img">
                            <input type="file" name="cat_img" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-8">
                            <label for="edit_cat_name" class="form-label">Name</label>
                            <input type="text" class="form-control" name="edit_cat_name" id="edit_cat_name" required>
                        </div>
                        <!-- Description -->
                        <div class="mb-8">
                            <label class="form-label">Description</label>
                            <textarea name="cat_des" id="edit_cat_des" class="form-control"></textarea>
                        </div>
                        <div class="mb-8">
                            <label for="edit_cat_url" class="form-label">URL</label>
                            <input type="text" class="form-control" name="edit_cat_url" id="edit_cat_url" required readonly>
                        </div>
                        <div class="mb-8">
                            <label for="edit_cat_type" class="form-label">Type</label>
                            <select class="form-control" name="edit_cat_type" id="edit_cat_type" required>
                                <option value="package">Package</option>
                                <option value="product">Product</option>
                            </select>
                        </div>
                        <!-- Inclusions -->
                        <div class="mb-8">
                            <label class="form-label">Inclusions</label>
                            <div id="edit-inc-wrapper">
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" name="cat_inc[]" class="form-control">
                                    <button type="button" class="btn btn-success btn-sm" onclick="addEditInc()">+</button>
                                </div>
                            </div>
                        </div>
                        <!-- Exclusions -->
                        <div class="mb-8">
                            <label class="form-label">Exclusions</label>
                            <div id="edit-exc-wrapper">
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" name="cat_exc[]" class="form-control">
                                    <button type="button" class="btn btn-success btn-sm" onclick="addEditExc()">+</button>
                                </div>
                            </div>
                        </div>
                        <!-- Package Fields -->
                        <div id="editPackageFields">
                            <div class="mb-8">
                                <label class="form-label">Packages</label><br>

                                <label style="display:flex; align-items:center; gap:8px;">
                                    <input type="checkbox" id="edit_packageCheck" name="has_package" value="1">
                                    Enable Packages
                                </label>

                                <div id="edit_packageOptions" style="display:none; margin-left:20px;">
                                    <label>
                                        <input type="radio" name="package_login_type" value="with_login">
                                        With Login
                                    </label><br>

                                    <label>
                                        <input type="radio" name="package_login_type" value="without_login">
                                        Without Login
                                    </label>
                                </div>
                            </div>
                        </div>
                        <!-- Product Fields -->
                        <div id="editProductFields">
                            <div class="mb-8">
                                <label class="form-label">Products</label><br>

                                <label style="display:flex; align-items:center; gap:8px;">
                                    <input type="checkbox" id="edit_productCheck" name="has_product" value="1">
                                    Enable Products
                                </label>

                                <div id="edit_productOptions" style="display:none; margin-left:20px;">
                                    <label>
                                        <input type="radio" name="product_login_type" value="with_login">
                                        With Login
                                    </label><br>

                                    <label>
                                        <input type="radio" name="product_login_type" value="without_login">
                                        Without Login
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="submit" class="btn lufera-bg" value="Update">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content radius-12 p-4">
                <div class="modal-header">
                    <h5 class="modal-title">View User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="userDetailContent">
                    <!-- User info will be loaded here -->
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#productPackageTable').DataTable();

            // Toggle Active/Inactive with SweetAlert
            $(document).on('click', '.toggle-status', function() {
                let button = $(this);
                let id = button.data('id');
                let currentStatus = button.data('status');
                let newStatusText = currentStatus == 1 ? 'Inactive' : 'Active';

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Change status to ${newStatusText}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, change it!',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'product-handler.php',
                            type: 'POST',
                            data: { action: 'toggle_status', id: id, status: currentStatus },
                            success: function(response) {
                                if (response.success) {
                                    if (currentStatus == 1) {
                                        button.removeClass('btn-success').addClass('btn-secondary').text('Inactive').data('status', 0);
                                    } else {
                                        button.removeClass('btn-secondary').addClass('btn-success').text('Active').data('status', 1);
                                    }
                                    Swal.fire('Updated!', `Status changed to ${newStatusText}.`, 'success');
                                } else {
                                    Swal.fire('Error!', 'Failed to update status.', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                            }
                        });
                    }
                });
            });

            // Delete Product with SweetAlert
            $(document).on('click', '.delete-product', function() {
                let button = $(this);
                let id = button.data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This product will be deleted (soft delete).",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'product-handler.php',
                            type: 'POST',
                            data: { action: 'delete_product', id: id },
                            success: function(response) {
                                if (response.success) {
                                    button.closest('tr').fadeOut();
                                    Swal.fire('Deleted!', 'The product has been deleted.', 'success');
                                } else {
                                    Swal.fire('Error!', 'Failed to delete product.', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>

    <script>
        function safeParse(json) {
            try { return JSON.parse(json || "[]"); } catch { return []; }
        }

        function openEditModal(data) {

            const modal = document.getElementById('edit-category-modal');

            // ===== BASIC =====
            modal.querySelector("#edit_cat_id").value = data.cat_id || '';
            modal.querySelector("#edit_cat_name").value = data.cat_name || '';
            modal.querySelector("#edit_cat_url").value = (data.cat_url || '').replace(".php", "");
            modal.querySelector("#edit_cat_type").value = data.cat_type || '';

            toggleEditTypeFields(data.cat_type);

            // ===== DESCRIPTION =====
            modal.querySelector("#edit_cat_des").value = data.cat_des || '';

            // ===== IMAGE =====
            let preview = modal.querySelector("#edit_preview_img");
            if (data.cat_img) {
                preview.src = data.cat_img;
                preview.style.display = 'block';
                modal.querySelector("#existing_cat_img").value = data.cat_img;
            } else {
                preview.style.display = 'none';
            }

            // ===== INCLUSIONS =====
            let incWrapper = modal.querySelector("#edit-inc-wrapper");
            let firstInc = incWrapper.querySelector('input');

            incWrapper.querySelectorAll('.d-flex').forEach((r,i)=>{ if(i!==0) r.remove(); });
            firstInc.value = '';

            let incArr = safeParse(data.cat_inc);
            if (incArr.length) {
                firstInc.value = incArr[0];
                incArr.slice(1).forEach(v => addEditInc(v));
            }

            // ===== EXCLUSIONS =====
            let excWrapper = modal.querySelector("#edit-exc-wrapper");
            let firstExc = excWrapper.querySelector('input');

            excWrapper.querySelectorAll('.d-flex').forEach((r,i)=>{ if(i!==0) r.remove(); });
            firstExc.value = '';

            let excArr = safeParse(data.cat_exc);
            if (excArr.length) {
                firstExc.value = excArr[0];
                excArr.slice(1).forEach(v => addEditExc(v));
            }

            // ================= RESET =================
            let pkgCheck = modal.querySelector("#edit_packageCheck");
            let prodCheck = modal.querySelector("#edit_productCheck");

            let pkgOptions = modal.querySelector("#edit_packageOptions");
            let prodOptions = modal.querySelector("#edit_productOptions");

            pkgCheck.checked = false;
            prodCheck.checked = false;

            pkgOptions.style.display = "none";
            prodOptions.style.display = "none";

            modal.querySelectorAll('[name="package_login_type"]').forEach(el => el.checked = false);
            modal.querySelectorAll('[name="product_login_type"]').forEach(el => el.checked = false);

            // ================= PACKAGE =================
            if (data.cat_type === 'package') {

                modal.querySelector("#editPackageFields").style.display = "block";
                modal.querySelector("#editProductFields").style.display = "none";

                if (data.pkg_with_login == 1 || data.pkg_without_login == 1) {
                    pkgCheck.checked = true;
                    pkgOptions.style.display = "block";
                }

                if (data.pkg_with_login == 1) {
                    modal.querySelector('[name="package_login_type"][value="with_login"]').checked = true;
                } 
                else if (data.pkg_without_login == 1) {
                    modal.querySelector('[name="package_login_type"][value="without_login"]').checked = true;
                }
            }

            // ================= PRODUCT =================
            if (data.cat_type === 'product') {

                modal.querySelector("#editPackageFields").style.display = "none";
                modal.querySelector("#editProductFields").style.display = "block";

                if (data.prod_with_login == 1 || data.prod_without_login == 1) {
                    prodCheck.checked = true;
                    prodOptions.style.display = "block";
                }

                if (data.prod_with_login == 1) {
                    modal.querySelector('[name="product_login_type"][value="with_login"]').checked = true;
                } 
                else if (data.prod_without_login == 1) {
                    modal.querySelector('[name="product_login_type"][value="without_login"]').checked = true;
                }
            }

            new bootstrap.Modal(modal).show();
        }

        // ADD ROWS
        function addEditInc(value=''){
            document.getElementById("edit-inc-wrapper").insertAdjacentHTML(
                'beforeend',
                `<div class="d-flex gap-2 mb-2">
                    <input type="text" name="cat_inc[]" class="form-control" value="${value}">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">-</button>
                </div>`
            );
        }

        function addEditExc(value=''){
            document.getElementById("edit-exc-wrapper").insertAdjacentHTML(
                'beforeend',
                `<div class="d-flex gap-2 mb-2">
                    <input type="text" name="cat_exc[]" class="form-control" value="${value}">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">-</button>
                </div>`
            );
        }

        // TOGGLE UI
        document.getElementById('edit_packageCheck').addEventListener('change', function () {
            document.getElementById('edit_packageOptions').style.display = this.checked ? 'block' : 'none';
        });

        document.getElementById('edit_productCheck').addEventListener('change', function () {
            document.getElementById('edit_productOptions').style.display = this.checked ? 'block' : 'none';
        });

        function toggleEditTypeFields(type) {

            const pkg = document.getElementById("editPackageFields");
            const prod = document.getElementById("editProductFields");

            // safety reset
            pkg.style.display = "none";
            prod.style.display = "none";

            if (type === 'package') {
                pkg.style.display = "block";
            } 
            else if (type === 'product') {
                prod.style.display = "block";
            }
        }

        document.getElementById("edit_cat_type").addEventListener("change", function () {
            toggleEditTypeFields(this.value);
        });
    </script>

    <script>
        function confirmDelete(button) {
            Swal.fire({
                title: 'Delete Category?',
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e3342f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it',
                customClass: {
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'swal2-cancel'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = button.closest('form');
                    if (form) form.submit();
                }
            });
        }

        $(document).ready(function() {
            $('[data-bs-target="#add-category-modal"]').on('click', function() {
                // Replace '#add-category-form' with your actual form's ID
                $('#add-category-form')[0].reset();
            });
        });

    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            // Toggle Package
            document.getElementById('packageCheck').addEventListener('change', function() {
                document.getElementById('packageOptions').style.display = this.checked ? 'block' : 'none';
            });

            // Toggle Product
            document.getElementById('productCheck').addEventListener('change', function() {
                document.getElementById('productOptions').style.display = this.checked ? 'block' : 'none';
            });

        });

        // Add Inclusion
        function addInc() {
            const wrapper = document.getElementById('inc-wrapper');

            const div = document.createElement('div');
            div.className = 'd-flex gap-2 mb-2';

            div.innerHTML = `
                <input type="text" name="cat_inc[]" class="form-control">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeField(this)">-</button>
            `;

            wrapper.appendChild(div);
        }

        // Add Exclusion
        function addExc() {
            const wrapper = document.getElementById('exc-wrapper');

            const div = document.createElement('div');
            div.className = 'd-flex gap-2 mb-2';

            div.innerHTML = `
                <input type="text" name="cat_exc[]" class="form-control">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeField(this)">-</button>
            `;

            wrapper.appendChild(div);
        }

        // Remove Field
        function removeField(btn) {
            btn.parentElement.remove();
        }
    </script>

    <script>
        document.getElementById('packageCheck').addEventListener('change', function() {
            const options = document.getElementById('packageOptions');
            options.style.display = this.checked ? 'block' : 'none';

            if (!this.checked) {
                document.querySelectorAll('input[name="package_login_type"]').forEach(el => el.checked = false);
            }
        });

        document.getElementById('productCheck').addEventListener('change', function() {
            const options = document.getElementById('productOptions');
            options.style.display = this.checked ? 'block' : 'none';

            if (!this.checked) {
                document.querySelectorAll('input[name="product_login_type"]').forEach(el => el.checked = false);
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const typeSelect = document.getElementById("cat_type");
            const packageFields = document.getElementById("packageFields");
            const productFields = document.getElementById("productFields");

            if (!typeSelect || !packageFields || !productFields) return;

            function toggleFields(value) {

                if (value === "package") {
                    packageFields.style.display = "block";
                    productFields.style.display = "none";

                    // ✅ enable package required
                    packageFields.querySelectorAll("input").forEach(el => el.disabled = false);

                    // ❌ disable product fields
                    productFields.querySelectorAll("input").forEach(el => {
                        el.checked = false;
                        el.disabled = true;
                    });

                } else if (value === "product") {
                    productFields.style.display = "block";
                    packageFields.style.display = "none";

                    productFields.querySelectorAll("input").forEach(el => el.disabled = false);

                    packageFields.querySelectorAll("input").forEach(el => {
                        el.checked = false;
                        el.disabled = true;
                    });

                } else {
                    packageFields.style.display = "none";
                    productFields.style.display = "none";

                    packageFields.querySelectorAll("input").forEach(el => el.disabled = true);
                    productFields.querySelectorAll("input").forEach(el => el.disabled = true);
                }
            }

            typeSelect.addEventListener("change", function () {
                toggleFields(this.value);
            });

        });
    </script>

    <script>
        $('#add-category-modal').on('shown.bs.modal', function () {
            document.getElementById('cat_type').value = "";

            document.getElementById('packageFields').style.display = "none";
            document.getElementById('productFields').style.display = "none";

            document.querySelectorAll('input[name="package_login_type"]').forEach(el => el.checked = false);
            document.querySelectorAll('input[name="product_login_type"]').forEach(el => el.checked = false);
        });
    </script>
</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>