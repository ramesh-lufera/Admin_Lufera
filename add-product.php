<?php $script = '<script>
        (() => {
            "use strict"

            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            const forms = document.querySelectorAll(".needs-validation")

            // Loop over them and prevent submission
            Array.from(forms).forEach(form => {
                form.addEventListener("submit", event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add("was-validated")
                }, false)
            })
        })()
        </script>';?>
<?php include './partials/layouts/layoutTop.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<style>
    .card-form {
      background: #fff;
      width: 100%;
      padding: 20px;
      /* border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
      text-align: center; */
      margin:auto;

    }

    .card-form h2 {
      margin-bottom: 20px;
      font-size: 1.5rem;
      color: #333;
    }

    .image-upload {
        position: relative;
        max-width:100%;
        width: 100%;
        height: 75px !important;
        border: 2px dashed #ccc;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        overflow: hidden;
        cursor: pointer;
        transition: 0.3s;
        }

        .image-upload:hover {
        border-color: #777;
        }

        .image-upload img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
        }

        .image-upload span {
        font-size: 1rem;
        color: #888;
        }

        .file-input {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }

    .form-group {
      text-align: left;
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
      color: #555;
    }

    .form-group input[type="text"],
    .form-group input[type="email"] {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      outline: none;
      transition: 0.3s;
    }

    .form-group input:focus {
      border-color: #5b9bd5;
    }

    .submit-btn{
        width:200px;
        margin:auto;
    }
    input[type=number] {
      -moz-appearance: textfield;
    }
</style>

<?php
if (isset($_POST['save_product'])) {
    $name = trim($_POST['name']);
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $price = $_POST['price'];
    $preview_price = $_POST['preview_price'];
    $description = $_POST['description'];
    $short_description = $_POST['short_description'];
    $category = $_POST['category'];
    $tags = $_POST['tags'];
    $created_at = date("Y-m-d H:i:s");
    $feature_item = isset($_POST['feature_item']) ? 'Yes' : 'No';
    $cat_id = $_GET['id'];
    $template = $_GET['template'];
    $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
    $is_login = isset($_POST['is_login']) ? 1 : 0;
    $duration_value = isset($_POST['duration_value']) ? intval($_POST['duration_value']) : 0;
    $duration_unit = isset($_POST['duration_unit']) ? $_POST['duration_unit'] : '';

    if ($duration_value > 0 && in_array($duration_unit, ['days', 'months', 'years'])) {
        $duration = $duration_value . ' ' . $duration_unit; // e.g., "10 days"
    } else {
        echo "<script>alert('Invalid duration input.'); window.history.back();</script>";
        exit;
    }
    function uploadImage($fieldName, $folder = "uploads/products/")
{
    if (
        isset($_FILES[$fieldName]) &&
        $_FILES[$fieldName]['error'] == 0
    ) {

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $fileName = time() . "_" . $fieldName . "_" . basename($_FILES[$fieldName]["name"]);

        move_uploaded_file(
            $_FILES[$fieldName]["tmp_name"],
            $folder . $fileName
        );

        return $fileName;
    }

    return "";
}
$package_image = uploadImage("package_image");

$breadcrumb_image = uploadImage("breadcrumb_image");

$preview_images = [];

for ($i = 1; $i <= 4; $i++) {

    $img = uploadImage("preview_images".$i);

    if ($img != "") {
        $preview_images[] = $img;
    }

}

    $imageData = json_encode([
        "breadcrumb_image" => $breadcrumb_image,
        "preview_images"   => $preview_images
    ]);
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO products 
    (name, title, subtitle, price, description, category, tags, feature_item, product_image, image_data, cat_id, duration, template, short_description, preview_price, created_at, is_login) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssdssssssisssdsi", $name, $title, $subtitle, $price, $description, $category, $tags, $feature_item, $package_image, $imageData, $cat_id, $duration, $template, $short_description, $preview_price, $created_at, $is_login);

if ($stmt->execute()) {

    $package_id = $stmt->insert_id; // ✅ Get inserted product ID

    // Prepare feature insert
    $featureStmt = $conn->prepare("INSERT INTO features (package_id, feature, feature_type, cat_type) VALUES (?, ?, ?, ?)");

    // -------- Inclusive --------
    if (!empty($_POST['inclusive_features'])) {
        foreach ($_POST['inclusive_features'] as $feature) {
            $feature = trim($feature);
            if ($feature !== "") {
                $type = 'inclusive';
                $cat_type = 2;
                $featureStmt->bind_param("issi", $package_id, $feature, $type, $cat_type);
                $featureStmt->execute();
            }
        }
    }

    // -------- Exclusive --------
    if (!empty($_POST['exclusive_features'])) {
        foreach ($_POST['exclusive_features'] as $feature) {
            $feature = trim($feature);
            if ($feature !== "") {
                $type = 'exclusive';
                $cat_type = 2;
                $featureStmt->bind_param("issi", $package_id, $feature, $type, $cat_type);
                $featureStmt->execute();
            }
        }
    }

    $featureStmt->close();

    logActivity(
      $conn,
      $loggedInUserId,
      "Product",                   // module
      "Product Created",                   // action
      "New product created successfully - $name"  // description
    );

    // =====================================================
    // CREATE PRODUCT LANDING PAGE
    // =====================================================

    // Slug
    $productSlug = strtolower(trim($name));
    $productSlug = preg_replace('/[^a-z0-9\s-]/', '', $productSlug);
    $productSlug = preg_replace('/\s+/', '-', $productSlug);

    $productFileName = $productSlug . ".php";

    // =====================================================
    // PAGES VERSION
    // =====================================================

    $productLandingContent = <<<'PRODUCT'
        <?php
        include '../../partials/connection.php';
        include '../head.php';
        include '../scripts.php';

        session_start();

        $isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;

        $product_id = __PRODUCT_ID__; // or your generated product id

        $stmt = $conn->prepare("
            SELECT *
            FROM products
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $product = $stmt->get_result()->fetch_assoc();

        $inclusive = [];
        $exclusive = [];

        $stmt = $conn->prepare("
            SELECT feature_type, feature
            FROM features
            WHERE package_id = ?
            AND cat_type = 2
            ORDER BY id ASC
        ");

        $stmt->bind_param("i", $product['id']);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            if (strtolower(trim($row['feature_type'])) == 'inclusive') {
                $inclusive[] = $row['feature'];
            } elseif (strtolower(trim($row['feature_type'])) == 'exclusive') {
                $exclusive[] = $row['feature'];
            }

        }

        $stmt->close();

                // ================= COMPANY LOGO =================
                
                $company = $conn->query("SELECT logo, phone_no, email FROM company LIMIT 1");
                $companyRow = $company->fetch_assoc();

                $logo = $companyRow['logo'] ?? '';
                $phone_no = $companyRow['phone_no'] ?? '';
                $email = $companyRow['email'] ?? '';

                // ✅ COMPANY DATA
                $company = [];
                $res = $conn->query("SELECT phone_no, address FROM company LIMIT 1");
                if ($res && $res->num_rows > 0) {
                    $company = $res->fetch_assoc();
                }
                
                $sql = "
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

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $cat_id);

                $stmt->execute();
                $result = $stmt->get_result();

                $websites = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $websites[] = $row;
                }

                // Number of websites per page
                $websitesPerPage = 5;

                // Get the current page from URL, default is 1
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

                // Calculate the starting index for the websites to display on this page
                $startIndex = ($page - 1) * $websitesPerPage;

                // Slice the websites array to get only the websites for the current page
                $websitesOnPage = array_slice($websites, $startIndex, $websitesPerPage);

                // Calculate the total number of pages
                $totalPages = ceil(count($websites) / $websitesPerPage);
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
                            <?php if (!empty($logo)): ?>
                                <img src="../../uploads/company_logo/<?php echo htmlspecialchars($logo); ?>" alt="Company Logo">
                            <?php endif; ?>
                        </div>

                        <!-- RIGHT: CONTACT -->

                        <div class="header-right">

                            <?php if (!empty($phone_no)): ?>
                                <span class="contact-item">📞 <?php echo htmlspecialchars($phone_no); ?></span>
                            <?php endif; ?>

                            <?php if (!empty($email)): ?>
                                <span class="contact-item">✉ <?php echo htmlspecialchars($email); ?></span>
                            <?php endif; ?>

                        </div>

                    </div>
                    
                    <div class="banner-section">

                        <?php if (!empty($product['product_image'])): ?>
                            <img src="../../uploads/products/<?php echo htmlspecialchars($product['product_image']); ?>" class="banner-img">
                        <?php endif; ?>

                        <div class="banner-overlay">
                            <h2 class="banner-title">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h2>
                        </div>

                    </div>

                    <div class="row">
                        <!-- LEFT -->
                        <div class="col-left" style="flex: 100% !important;">

                            <div class="card">
                                <h4>Description</h4>
                                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            </div>

                            <div class="card">
            <h4>Features</h4>

            <div class="features-row">

                <!-- INCLUSIONS -->
                <div class="features-col inclusions">
                    
                    <ul class="custom-list">
                        <?php if (!empty($inclusive)): ?>
                            <?php foreach ($inclusive as $item): ?>
                                <li><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No inclusions available</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- EXCLUSIONS -->
                <div class="features-col exclusions">
                    
                    <ul class="custom-list">
                        <?php if (!empty($exclusive)): ?>
                            <?php foreach ($exclusive as $item): ?>
                                <li><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No exclusions available</li>
                        <?php endif; ?>
                    </ul>
                </div>

            </div>
        </div>

        <?php

        // Currency
        $symbol = "$";
        $r = $conn->query("SELECT symbol FROM currencies WHERE is_active=1 LIMIT 1");
        if($row = $r->fetch_assoc()){
            $symbol = $row['symbol'];
        }

        $loginRequired = ($product['is_login'] == 1);

        ?>

        <?php if($loginRequired && !$isLoggedIn): ?>

        <div class="card">

            <h4>Products Pricing Table</h4>

            <p class="text-center"
            style="font-size:16px;font-weight:600;margin-top:15px;">

                <a href="#"
                onclick="openLoginPopup()"
                class="btn mt-2">

                    🔒 Sign-In to See the Product

                </a>

            </p>

        </div>

        <?php else: ?>

        <!-- Product Card Here -->
        <div class="card">
            <h4 class="mb-20">Products Pricing Table</h4>

            <div class="row gy-4">

                <?php $isActive = ($product['is_active'] == 1); ?>

                <div class="col-lg-4 col-md-4 col-sm-6">

                    <?php if ($isActive): ?>
                        <a href="#" class="d-block">
                    <?php endif; ?>

                    <div class="pb-16 hover-scale-img border radius-16 overflow-hidden <?= !$isActive ? 'inactive-product' : ''; ?>">

                        <div class="max-h-266-px overflow-hidden">

                            <img
                                src="../../uploads/products/<?= htmlspecialchars($product['product_image']); ?>"
                                class="hover-scale-img__img w-100 object-fit-cover <?= !$isActive ? 'grayscale' : ''; ?>">

                        </div>

                        <div class="py-16 px-24">

                            <h6 class="mb-4">
                                <?= htmlspecialchars($product['name']); ?>
                            </h6>

                            <?php if(!empty($product['subtitle'])): ?>
                                <p class="text-secondary-light mb-2">
                                    <?= htmlspecialchars($product['subtitle']); ?>
                                </p>
                            <?php endif; ?>

                            <p class="mb-0 text-sm text-secondary-light">
                                <b>Price :</b>
                                <?= $symbol ?>
                                <?= number_format($product['price'],2) ?>
                            </p>

                            <?php if(!$isActive): ?>
                                <p class="text-danger fw-semibold mt-2">
                                    Inactive
                                </p>
                            <?php endif; ?>

                        </div>

                    </div>

                    <?php if ($isActive): ?>
                        </a>
                    <?php endif; ?>

                </div>

            </div>

        </div>

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
                            "../../sign-in.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>";
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
                                <p><?php echo htmlspecialchars($company['phone_no'] ?? 'N/A'); ?></p>

                                <h4>LOCATION</h4>
                                <p><?php echo htmlspecialchars($company['address'] ?? 'N/A'); ?></p>

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
    PRODUCT;

    // Replace product id
    $productLandingContent = str_replace(
        '__PRODUCT_ID__',
        $package_id,
        $productLandingContent
    );

    // =====================================================
    // ROOT VERSION
    // =====================================================

    $rootContent = <<<'ROOT'

    <?php include './partials/layouts/layoutTop.php'; ?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <title>Product View</title>
    <?php
        $sql = "SELECT * FROM company LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $company_name = $row['full_name'];
            $logo = $row['logo'];
            $email = $row['email'];
            $phone = $row['phone_no'];
            $address = $row['address'];

        }    
        $Id = __PRODUCT_ID__;
        $sql = "select * from products where id = $Id";
        $result = $conn ->query($sql);
        $row = $result ->fetch_assoc();
        $package_img = $row['product_image'];
        $package_name = $row['name'];
        $title = $row['title'];
        $subtitle = $row['subtitle'];
        $price = $row['price'];
        $preview_price = $row['preview_price'];
        $duration = $row['duration'];
        $prod_category = $row['category'];
        $prod_tags = $row['tags'];
        $gst = $row['gst'];
        $cat_id_sc = $row['cat_id'];

        // Decode image_data JSON
        $imageData = json_decode($row['image_data'], true);

        $breadcrumbImage = $imageData['breadcrumb_image'] ?? '';
        $previewImages   = $imageData['preview_images'] ?? [];
    ?>
    <style>
    .header-left img {
        height: 50px;
        object-fit: contain;
    }
    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 14px;
        font-weight: 500;
    }
    .image-banner{
        background: #fff !important;
        border-radius: 10px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        padding: 18px !important;
        margin-bottom: 20px !important;
        line-height: 1.6 !important;
    }

    .card{
        background: #fff !important;
        border-radius: 10px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        padding: 18px !important;
        margin-bottom: 20px !important;
        line-height: 1.6 !important;
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

    /* LIST */
    .custom-list {
        list-style: none !important;
        padding: 0 !important;
    }

    .custom-list li {
        padding: 6px 0 !important;
        font-size: 15px !important;
    }

    /* MOBILE */
    @media (max-width: 768px) {
        .features-row {
            flex-direction: column !important;
        }
    }
    .hover-scale-img__img{
        height: 200px;
    }
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
    </style>
    <style>
    .top-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 30px;
        margin-bottom: 20px;
    }

    .header-left img {  
        height: 50px;
    }

    .header-center {
        text-align: center;
        flex: 1;
    }

    .header-center h3 {
        margin: 0;
        font-weight: bold;
    }

    .header-right {
        text-align: right;
        font-size: 14px;
    }

    .header-right p {
        margin: 0;
    }

    /* Center text on image */
    .package-title {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #fff;
        font-size: 24px !important;
        font-weight: 700;  
        text-align: center;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);
    }

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
    .fs-16 {
        font-size: 16px !important;
    }
    p {
        font-size: 15px !important;
        line-height: 1.6 !important;
    }
    .swal2-container{
        z-index:9999;
    }
        /* ===== MODERN PRICING CARD ===== */

    .pricing-wrapper {
        margin-top: 20px;
    }

    .pricing-card {
        background: #1d908d;
        border-radius: 14px;
        padding: 30px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    /* TOP */
    .pricing-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid rgba(255,255,255,0.15);
        padding-bottom: 18px;
    }

    .pricing-top h2 {
        font-size: 28px !important;
        font-weight: 600;
        margin: 0;
        color: #fff;
    }

    .pricing-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #fff;
        color: #111;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: bold;
    }

    /* BODY */
    .pricing-body {
        display: flex;
    }

    /* LEFT */
    .pricing-left {
        height: 200px;
        width: 50%;
    }

    .pricing-subtitle {
        font-size: 18px !important;
        margin-bottom: 15px;
        color: #e9f7f7;
    }

    .pricing-price {
        display: flex;
        align-items: flex-start;
        margin-bottom: 50px;
    }

    .pricing-price .currency {
        font-size: 28px;
        font-weight: 400;
        margin-top: -20px;
    }

    .pricing-price .amount {
        font-size: 40px;
        font-weight: 700;
        line-height: 1;
        margin: 0 6px;
    }

    .pricing-price .duration {
        font-size: 16px;
        margin-top: 12px;
    }

    /* BUTTON */
    .choose-plan-btn {
        background: #fff;
        color: #111;
        border: none;
        border-radius: 50px;
        padding: 10px 30px;
        font-size: 16px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: 0.3s ease;
        justify-content: center;
        margin-top: 30px;
        position: absolute;
        bottom: 30px;
    }

    .choose-plan-btn span {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #062c3d;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .choose-plan-btn:hover {
        transform: translateY(-2px);
    }

    /* RIGHT */
    .pricing-right {
        width: 50%;
        border-left: 1px solid rgba(255, 255, 255, 0.15);
        padding-left: 20px;
    }

    .pricing-right ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .pricing-right ul li {
        position: relative;
        margin-bottom: 20px;
        font-size: 17px;
        line-height: 1.5;
    }

    .pricing-right ul li::before {
        /* content: "◖◖"; */
        position: absolute;
        left: 0;
        top: 0;
        color: #fff;
        font-size: 18px;
    }

    /* MOBILE */
    @media (max-width: 768px) {

        .pricing-body {
            flex-direction: column;
        }

        .pricing-left,
        .pricing-right {
            width: 100%;
        }

        .pricing-left {
            border-right: none;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            padding-right: 0;
            padding-bottom: 25px;
            margin-bottom: 25px;
        }

        .pricing-price .amount {
            font-size: 65px;
        }

        .pricing-price .duration {
            margin-top: 28px;
            font-size: 18px;
        }
    }
    .feature-img {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius:8px;
    }
    /* ===== FEATURES SECTION ===== */
    .features-row {
        display: flex !important;
        gap: 25px !important;
        margin-top: 10px !important;
    }

    .features-col {
        flex: 1 !important;
        border-radius: 10px !important;
    }
    .preview-img{
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    .breadcrumb-hero{
        border-radius:12px; 
        background-image: url('./uploads/products/<?php echo $breadcrumbImage; ?>'); 
        background-size: cover; 
        background-position: center; 
        height: 300px;
    }
    .breadcrumb-overlay{
    transform: translateY(100%);
    text-align:center;
    }
    </style>
    </head>
    <body>

        <div class="content-wrapper" style="padding: 10px 15px; margin: 20px 0">
            <!-- Header Navbar -->
            
            
            <!-- HERO -->
            <section class="breadcrumb-hero position-relative">
                <!-- Manage Button -->
                <div style="position:absolute; top:50%; right:20px; transform:translateY(-50%); display:flex; gap:10px; z-index:9">
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#apiModal">API</button>
                </div>

                <div class="breadcrumb-overlay">
                    <h2 class="breadcrumb-title">
                        <?php echo $package_name; ?>
                    </h2>
                    <p class="breadcrumb-path">
                        <span class="lufera-color">Products</span> /
                        <?php echo $package_name; ?>
                    </p>
                </div>
            </section>
            <!-- ===== API DATA PREPARATION ===== -->
            <?php
            // BASE URL (dynamic)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            //$basePath = dirname($_SERVER['SCRIPT_NAME']);
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '');

            //$currentBaseUrl = $protocol . $host . $basePath;
            $currentBaseUrl = rtrim($protocol . $host . $basePath, '/');

            $slug = strtolower(trim($package_name));        // lowercase + trim
            $slug = preg_replace('/\s+/', '-', $slug);      // replace spaces with hyphens
            $slug = preg_replace('/[^a-z0-9\-]/', '', $slug); // remove special chars

            // FINAL LANDING URL
            $landingUrl = $currentBaseUrl . "/pages/products/" . $slug . ".php";

            // FULL PLAN SHORTCODE
            $fullPlanShortcode = "Product-Shortcode-" . $cat_id_sc;
            $categoryShortcode = "Category-Shortcode-" . $cat_id_sc;
            $indPlanShortcode = "Product-$title-Shortcode-" . $Id;
            
            ?>

            <div class="modal fade" id="apiModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered custom-modal">
                    <div class="modal-content p-4">

                        <!-- HEADER -->
                        <div class="modal-header">
                            <h5 class="modal-title" style="font-size:18px !important">API Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <!-- BODY -->
                        <div class="modal-body">

                            <!-- LANDING URL -->
                            <h6 class="fs-16">Landing URL</h6>
                            <div class="input-group mb-3">
                                <input type="text" id="landingUrl" class="form-control" value="<?php echo $landingUrl; ?>" readonly>
                                <button class="btn copy-btn lufera-bg" onclick="copyText('landingUrl')">Copy</button>
                            </div>
                            <hr style="margin:15px 0 10px;">
                            <!-- SHORTCODES TITLE -->
                            <h5 style="font-size:18px !important">Shortcodes</h5>
                            
                            <h6 class="fs-16">Category Shortcode</h6>
                            <div class="input-group mb-3">
                                <input type="text" id="catShortcode" class="form-control" value="<?php echo $categoryShortcode; ?>" readonly>
                                <button class="btn copy-btn lufera-bg" onclick="copyText('catShortcode')">Copy</button>
                            </div>
                            
                            <h6 class="fs-16">Full Plan Shortcode</h6>
                            <div class="input-group mb-3">
                                <input type="text" id="fullPlan" class="form-control" value="<?php echo $fullPlanShortcode; ?>" readonly>
                                <button class="btn copy-btn lufera-bg" onclick="copyText('fullPlan')">Copy</button>
                            </div>

                            <!-- FULL PLAN -->
                            <h6 class="fs-16">Individual Plan Shortcode</h6>
                            <?php echo $title ?>
                            <div class="input-group mb-3">
                                <input type="text" id="ind_Plan" class="form-control" value="<?php echo $indPlanShortcode; ?>" readonly>
                                <button class="btn copy-btn lufera-bg" onclick="copyText('ind_Plan')">Copy</button>
                            </div>
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

            <!--Price Section -->
            <?php

            // Currency
            $result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
            $symbol = "₹";
            if ($row_symbol = $result->fetch_assoc()) {
                $symbol = $row_symbol['symbol'];
            }

            $sql_login = "select * from products where id = $Id";
            $result_login = $conn->query($sql_login);
            $row_login = $result_login->fetch_assoc();
            $isLoginRequired = ($row_login['is_login'] == 1);
            ?>

            
            <div class="m-40">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-6 col-lg-6">
                            <div class="row">
                                <!--<div class="col-lg-3 text-end">
                                    <ul>
                                        <?php if (!empty($previewImages)): ?>
                                            <?php foreach($previewImages as $img): ?>
                                                <li>
                                                    <img src="./uploads/products/<?php echo $img; ?>" class="preview-img" alt="Preview Image">
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li>
                                                <img src="./uploads/products/<?php echo $package_img; ?>" class="preview-img" alt="Product Image">
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>-->
                                <div class="col-lg-12">
                                    <img src="./uploads/products/<?php echo $package_img; ?>" alt="Product Image" class="feature-img">
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-6">
                            <div class="ms-20">
                                <h3 class="mb-2 text-capitalize">
                                    <?php echo $package_name; ?>
                                </h3>
                                <p class="price">
                                    <span class="amount fs-3 fw-semibold me-6">
                                        <?= $symbol ?><?= $price; ?>
                                    </span>
                                    <?php if (!empty('preview_price')): ?>
                                    <span class="text-decoration-line-through fs-3 fw-semibold">
                                        <?= $symbol ?><?= $preview_price; ?>
                                    </span>
                                <?php endif; ?>
                                </p>
                                
                                <p>
                                    <?php echo $row['short_description']; ?>
                                </p>

                                <form action="cart.php" method="POST">                                
                                    <input type="hidden" name="type" value="product">  
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                                    <input type="hidden" name="plan_name" value="<?= htmlspecialchars($package_name) ?>">
                                    <input type="hidden" name="title" value="<?= htmlspecialchars($title) ?>">
                                    <input type="hidden" name="subtitle" value="<?= htmlspecialchars($subtitle) ?>">
                                    <input type="hidden" name="price" value="<?= htmlspecialchars($price) ?>">
                                    <input type="hidden" name="duration" value="<?= htmlspecialchars($duration) ?>">
                                    <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                    <input type="hidden" name="gst" value="<?= htmlspecialchars($gst) ?>">                            
                                    <button type="submit" class="btn btn-dark w-50 p-12 mb-3" style="border-radius: 50px;"> 
                                        Shop Now
                                    </button>        
                                </form>
                    <p><b>Duration</b> :
                                    <?php echo $duration; ?>
                                </p>
                                <p><b>Category</b> :
                                    <?php echo $prod_category; ?>
                                </p>
                                <p><b>Tags</b> :
                                    <?php echo $prod_tags; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                
                    <div class="mt-40">
                        <ul class="nav bordered-tab border border-top-0 border-start-0 border-end-0 d-inline-flex nav-pills mb-16 w-100 gap-50" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-0 py-10 active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">Description</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-0 py-10" id="pills-details-tab" data-bs-toggle="pill" data-bs-target="#pills-details" type="button" role="tab" aria-controls="pills-details" aria-selected="false">Features</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">
                                <div>
                                    <p class="text-secondary-light mb-0"><?php echo $row['description']; ?></p>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-details" role="tabpanel" aria-labelledby="pills-details-tab" tabindex="0">
                                <div>
                                    <?php
                                        $features_sql = "SELECT feature_type, feature FROM features WHERE cat_type = 2 AND package_id = $Id";
                                        $features_result = $conn->query($features_sql);
                                        
                                        $included = [];
                                        $excluded = [];
                                        
                                        while ($frow = $features_result->fetch_assoc()) {
                                            if ($frow['feature_type'] == 'inclusive') {
                                                $included[] = $frow['feature'];
                                            } else {
                                                $excluded[] = $frow['feature'];
                                            }
                                        }
                                    ?>
                                    
                                    <div class="features-row">
                                        <!-- INCLUSIONS -->
                                        <div class="features-col inclusions">
                                            <h6>Included</h6>
                                            <ul class="custom-list">
                                                <?php if (!empty($included)): ?>
                                                    <?php foreach ($included as $inc): ?>
                                                        <li><i class="fa fa-check"></i> <?php echo htmlspecialchars($inc); ?></li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li>No inclusions available</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <!-- EXCLUSIONS -->
                                        <div class="features-col exclusions">
                                        <h6>Exclude</h6>
                                            <ul class="custom-list">
                                                <?php if (!empty($excluded)): ?>
                                                    <?php foreach ($excluded as $exc): ?>
                                                        <li><i class="fa fa-close"></i> <?php echo htmlspecialchars($exc); ?></li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li>No exclusions available</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>                                                     
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- LOGIN MODAL (MOVED OUTSIDE ROW) -->
            <div id="loginModal" class="login-modal">
                <div class="login-modal-content">
                    <span class="close-btn" onclick="closeLoginPopup()">&times;</span>
                    <iframe id="loginFrame"></iframe>
                </div>
            </div>

            <script>
            function openLoginPopup() {
                document.getElementById("loginModal").style.display = "block";
                document.getElementById("loginFrame").src =
                    "../sign-in.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>";
            }

            function closeLoginPopup() {
                document.getElementById("loginModal").style.display = "none";
            }

            window.onclick = function(event) {
                let modal = document.getElementById("loginModal");
                if (event.target === modal) {
                    closeLoginPopup();
                }
            };
            </script>
            
            <!-- ===== FOOTER ===== -->          
        </div>
        <?php include './partials/layouts/layoutBottom.php'; ?>

    ROOT;

    $rootContent = str_replace("__PRODUCT_ID__", $package_id, $rootContent);
                        
    // =====================================================
    // CREATE BOTH FILES
    // =====================================================

    $paths = [
        ['dir' => realpath(__DIR__) . '/pages/products', 'content' => $productLandingContent],
        ['dir' => realpath(__DIR__) . '/', 'content' => $rootContent]
    ];

    foreach ($paths as $item) {

        $dir = $item['dir'];
        $content = $item['content'];

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = $dir . '/' . $productFileName;

        file_put_contents($filePath, $content);
    }
    // --------- CREATE -det.php FILE IF NOT EXISTS ---------
    $det_file_path = $slug . "-det.php";

    if (!file_exists($det_file_path)) {
        // Base PHP content for all templates (connection + product fetching)
        $base_php = <<<'PHP'
        <?php 
        include './partials/connection.php';

        $product_id = $_GET['product_id'];

        $sql = "SELECT * FROM products WHERE id = $product_id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id = $row['id'];
            $template_product = $row['template'];
        }
        ?>

        PHP;
        
        $det_content = $base_php . "<?php include './category_details/' . \$template_product . '-details.php'; ?>";
        file_put_contents($det_file_path, $det_content);
            }

            // --------- SUCCESS MESSAGE ---------
            echo "<script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Product saved successfully.',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'view-$slug.php';
                    }
                });
            </script>";
        } else {
            echo "<script>alert('Error: " . $stmt->error . "'); window.history.back();</script>";
        }  
    }
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Add Product</h6>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="row justify-content-center">
                <div class="col-xxl-12 col-xl-8 col-lg-10">
                    <form method="POST" enctype="multipart/form-data" class="row gy-3 needs-validation card-form" novalidate autocomplete="off">
                        <!-- <div class="form-group text-start mb-2">
                            <label class="form-label">Product image <span class="text-danger-600">*</span></label>
                          <div class="has-validation">
                            <input type="file" id="file-input" accept="image/*" name="product_image" required>
                            <label class="image-upload d-flex" for="file-input">
                            <span>Click or Drag Image Here</span>
                            <img id="preview" alt="Preview Image">
                            </label>
                            <div class="invalid-feedback">
                                Please upload a product image.
                            </div>
                          </div>
                        </div> -->

                                        <div class="row mb-2">
                                            <!-- Product Image -->
                                            <div class="col-lg-6">
                                                <label class="form-label">
                                                    Product Image <span class="text-danger-600">*</span>
                                                </label>

                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" class="file-input" id="package_image" accept="image/*" name="package_image" required>

                                                    <label for="package_image" class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>

                                                    <div class="invalid-feedback">
                                                        Please upload a Product image.
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Breadcrumb Image -->
                                            <div class="col-lg-6">
                                                <label class="form-label">
                                                    Breadcrumb Image <span class="text-danger-600">*</span>
                                                </label>

                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" id="breadcrumb_image" class="file-input" accept="image/*" name="breadcrumb_image" required>

                                                    <label for="breadcrumb_image" class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>

                                                    <div class="invalid-feedback">
                                                        Please upload a Breadcrumb image.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                                Preview Images <span class="text-danger-600">*</span>
                                            </label>

                                            <div class="col-md-3">
                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" id="preview_images1" class="file-input" accept="image/*" name="preview_images1" required>

                                                    <label for="preview_images1" class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>

                                                    <div class="invalid-feedback">
                                                        Please upload at least one preview image.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" id="preview_images2" class="file-input" accept="image/*" name="preview_images2">

                                                    <label for="preview_images2" class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" id="preview_images3" class="file-input" accept="image/*" name="preview_images3">

                                                    <label for="preview_images3" class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" id="preview_images4" class="file-input" accept="image/*" name="preview_images4">

                                                    <label for="preview_images4" class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>
                                                </div>
                                            </div>
                                        </div>



                        <div class="form-group mb-2">
                          <label class="form-label">Product name <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                              <input type="text" class="form-control radius-8" id="name" name="name" required maxlength="100">
                              <div class="invalid-feedback">
                                Product name is required
                              </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Title <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="title" name="title" required maxlength="100">
                                <div class="invalid-feedback">
                                Title is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Subtitle <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="subtitle" name="subtitle" required maxlength="100">
                                <div class="invalid-feedback">
                                Subtitle is required
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-2">
                            <label class="form-label">Description <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="description" name="description" required>
                                <div class="invalid-feedback">
                                  Description is required
                                </div>
                            </div>
                        </div> 
                        
                        <div class="form-group mb-2">
                            <label class="form-label">Short Description <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="short_description" name="short_description" required>
                                <div class="invalid-feedback">
                                  Short Description is required
                                </div>
                            </div>
                        </div> 

                        <div class="form-group mb-2">
                            <label class="form-label">Price <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                              <input type="number" name="price" class="form-control radius-8" required onkeydown="return event.key !== 'e'" maxlength="10">
                                <div class="invalid-feedback">
                                Price is required
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label">Preview Price <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="number" class="form-control radius-8" id="preview_price" name="preview_price" value="<?php echo htmlspecialchars($product['preview_price']); ?>" required onkeydown="return event.key !== 'e'">
                                <div class="invalid-feedback">
                                    Preview Price is required
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-2">
                            <label class="form-label">Duration <span class="text-danger-600">*</span></label>
                            <div class="d-flex gap-2">
                                <input type="number" id="duration_value" name="duration_value" class="form-control radius-8" required min="1" style="width: 60%;">
                                <select id="duration_unit" name="duration_unit" class="form-control radius-8" required style="width: 40%;">
                                    <option value="days">Days</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                    <!-- <option value="hours">Hours</option> -->
                                </select>
                            </div>
                            <div class="invalid-feedback">
                                Duration is required
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label">Category <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="category" name="category" required maxlength="100">
                                <div class="invalid-feedback">
                                Category is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Tags <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="tags" name="tags" required maxlength="100">
                                <div class="invalid-feedback">
                                Tags is required
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label">Features <span class="text-danger-600">*</span></label>   
                              <div class="row">
                                <div class="col-6">
                                    <p class="mb-0">Inclusive</p>
                                    <div id="inclusive-wrapper">
                                        <div class="feature-group d-flex gap-2 mb-10">
                                            <input type="text" name="inclusive_features[]" class="form-control" required>
                                            <button type="button" class="btn btn-success add-inclusive">+</button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback">
                                        At least one feature is required.
                                    </div>
                                </div>

                                <div class="col-6">
                                    <p class="mb-0">Exclusive</p>
                                    <div id="exclusive-wrapper">
                                        <div class="feature-group d-flex gap-2 mb-10">
                                            <input type="text" name="exclusive_features[]" class="form-control" required>
                                            <button type="button" class="btn btn-success add-exclusive">+</button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback">
                                        At least one feature is required.
                                    </div>
                                </div>
                              </div>
                        </div>       
                        
                        <div class="form-group mb-2">
                            <label class="form-label">Feature item</label>

                            <div class="form-check d-flex align-items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    id="feature_item" 
                                    name="feature_item" 
                                    value="Yes"
                                    class="form-check-input"
                                >
                                <label for="feature_item" class="form-check-label mb-0">
                                    Mark as featured product
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label">Is Login?</label>
                            <div class="form-check d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="is_login" id="isLogin">  
                                <label class="form-check-label ms-2 mb-0" for="isLogin">Require login to purchase</label>       
                            </div>
                        </div>
                        <!-- Submit Button -->
                        <button type="submit" name="save_product" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 radius-8 mt-28 submit-btn">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const durationValue = document.getElementById('duration_value');
const durationUnit = document.getElementById('duration_unit');

// Options with singular/plural forms
const units = {
  singular: ["Day", "Month", "Year"],
  plural: ["Days", "Months", "Years"],
  values: ["days", "months", "years"] // keep values consistent for backend
};

// Function to update options
function updateDurationOptions() {
  const isSingular = durationValue.value == 1;
  durationUnit.innerHTML = ""; // clear existing options

  const list = isSingular ? units.singular : units.plural;
  list.forEach((label, index) => {
    const opt = document.createElement("option");
    opt.value = units.values[index]; // keep backend value constant
    opt.textContent = label;
    durationUnit.appendChild(opt);
  });
}

// Initial load
updateDurationOptions();

// Update on input change
durationValue.addEventListener("input", updateDurationOptions);
</script>

<script>
document.querySelectorAll(".image-upload-wrapper").forEach(wrapper => {
const input = wrapper.querySelector(".file-input");
const img = wrapper.querySelector(".preview-image");
const text = wrapper.querySelector("span");
input.addEventListener("change", function () {
    if (this.files.length) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.style.display = "block";
            text.style.display = "none";
        };
        reader.readAsDataURL(input.files[0]);
    }
});
});

// Inclusive
document.getElementById("inclusive-wrapper").addEventListener("click", function(e) {
    if (e.target.classList.contains("add-inclusive")) {
        const div = document.createElement("div");
        div.className = "feature-group d-flex gap-2 mb-10";
        div.innerHTML = `
            <input type="text" name="inclusive_features[]" class="form-control" required>
            <button type="button" class="btn btn-danger remove-feature">−</button>
        `;
        this.appendChild(div);
    }
});

// Exclusive
document.getElementById("exclusive-wrapper").addEventListener("click", function(e) {
    if (e.target.classList.contains("add-exclusive")) {
        const div = document.createElement("div");
        div.className = "feature-group d-flex gap-2 mb-10";
        div.innerHTML = `
            <input type="text" name="exclusive_features[]" class="form-control" required>
            <button type="button" class="btn btn-danger remove-feature">−</button>
        `;
        this.appendChild(div);
    }
});

// Remove (common)
document.addEventListener("click", function(e) {
    if (e.target.classList.contains("remove-feature")) {
        e.target.parentElement.remove();
    }
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>
