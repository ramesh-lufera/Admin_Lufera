<?php
    $userid = $_SESSION['user_id'];

    $sql = "SELECT role FROM users WHERE id = $userid";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $company_sql = "SELECT * FROM company";
    $company_result = $conn->query($company_sql);
    $company_row = $company_result->fetch_assoc();
    $logo = $company_row['logo'];

    // Handle packages (or) products creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_category'], $_POST['product_type'], $_POST['template'])) {
        $product_category = intval($_POST['product_category']);
        $product_type = $_POST['product_type'];
        $template = $_POST['template'];

        if ($product_type === 'Package') {
            $stmt = $conn->prepare("SELECT cat_url FROM categories WHERE cat_id = ?");
            $stmt->bind_param("i", $product_category);
            $stmt->execute();
            $stmt->bind_result($cat_url);
            $stmt->fetch();
            $stmt->close();

            $cat_url_Slug = pathinfo($cat_url, PATHINFO_FILENAME);
            $pack_cat_url_Slug = strtolower(preg_replace('/\s+/', '-', $cat_url_Slug));

            $add_file_name = "add-$pack_cat_url_Slug.php";
            $add_file_path = dirname(__DIR__) . '/' . $add_file_name;
            
            $add_content = <<<PHP
                <?php \$script = '<script>
                    (() => {
                        "use strict"

                        const forms = document.querySelectorAll(".needs-validation");
                        Array.from(forms).forEach(form => {
                            form.addEventListener("submit", event => {
                                if (!form.checkValidity()) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                }
                                form.classList.add("was-validated");
                            }, false);
                        });
                    })()
                </script>';?>

                <style>
                    .toggle-icon-pass {
                        position: absolute;
                        top: 22px;
                        right: 28px;
                        transform: translateY(-50%);
                        cursor: pointer;
                        user-select: none;
                        font-size: 20px;
                    }
                    input::-webkit-outer-spin-button,
                    input::-webkit-inner-spin-button {
                        -webkit-appearance: none;
                        margin: 0;
                    }
                    input[type=number] {
                        -moz-appearance: textfield;
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

                    input[type="file"] {
                    display: none;
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
                </style>

                <?php include './partials/layouts/layoutTop.php' ?>
                    <?php
                    // Fetch packages
                    \$packages_list = [];
                    \$result = \$conn->query("SELECT * FROM package where is_deleted = 0");
                    if (\$result && \$result->num_rows > 0) {
                        while (\$row = \$result->fetch_assoc()) {
                            \$packages_list[] = \$row;
                        }
                    }

                    // Fetch products
                    \$products_list = [];
                    \$result = \$conn->query("SELECT id, title FROM products where is_deleted = 0");
                    if (\$result && \$result->num_rows > 0) {
                        while (\$row = \$result->fetch_assoc()) {
                            \$products_list[] = \$row;
                        }
                    }

                    // Fetch add-ons
                    \$addons_list = [];
                    \$result = \$conn->query("SELECT id, name FROM `add-on-service`");
                    if (\$result && \$result->num_rows > 0) {
                        while (\$row = \$result->fetch_assoc()) {
                            \$addons_list[] = \$row;
                        }
                    }

                    // ✅ Fetch GST (Taxes)
                    \$gst_list = [];
                    \$result = \$conn->query("SELECT id, tax_name, rate FROM taxes");
                    if (\$result && \$result->num_rows > 0) {
                        while (\$row = \$result->fetch_assoc()) {
                            \$gst_list[] = \$row;
                        }
                    }
                    if (isset(\$_POST['save_package'])) {   
                        \$package_name = trim(\$_POST['package_name']);                       
                        \$title = \$_POST['title'];
                        \$subtitle = \$_POST['subtitle'];
                        \$description = \$_POST['description'];
                        \$short_description = \$_POST['short_description'];
                        \$created_at = date("Y-m-d H:i:s");

                        \$addons = isset(\$_POST['addons']) && is_array(\$_POST['addons']) ? implode(',', \$_POST['addons']) : '';
                        \$addon_packages = isset(\$_POST['packages']) && is_array(\$_POST['packages']) ? implode(',', \$_POST['packages']) : '';
                        \$addon_products = isset(\$_POST['products']) && is_array(\$_POST['products']) ? implode(',', \$_POST['products']) : '';
                        
                        \$cat_id = $product_category;
                        \$template = "$template";

                        \$gst_id = !empty(\$_POST['gst_id']) ? \$_POST['gst_id'] : NULL;
                        \$is_login = isset(\$_POST['is_login']) ? 1 : 0;

                        // Image upload
                        \$package_image = '';
                        if (isset(\$_FILES['package_image']) && \$_FILES['package_image']['error'] == 0) {
                            \$target_dir = "uploads/products/";
                            if (!is_dir(\$target_dir)) {
                                mkdir(\$target_dir, 0777, true);  // create directory if it doesn't exist
                            }

                            \$file_name = time() . '_' . basename(\$_FILES["package_image"]["name"]);
                            \$target_file = \$target_dir . \$file_name;
                            \$imageFileType = strtolower(pathinfo(\$target_file, PATHINFO_EXTENSION));

                            // Validate file type (only images)
                            \$allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            if (in_array(\$imageFileType, \$allowed_types)) {
                                if (move_uploaded_file(\$_FILES["package_image"]["tmp_name"], \$target_file)) {
                                    \$package_image = \$file_name;  // Save file name in DB
                                } else {
                                    echo "<script>alert('Failed to upload image.'); window.history.back();</script>";
                                    exit;
                                }
                            } else {
                                echo "<script>alert('Invalid file type. Allowed: JPG, PNG, GIF, WEBP.'); window.history.back();</script>";
                                exit;
                            }
                        } else {
                            echo "<script>alert('Please upload a package image.'); window.history.back();</script>";
                            exit;
                        }
                        \$image_data = [
                            'breadcrumb_image' => '',
                            'preview_images' => []
                        ];

                        \$target_dir = "uploads/products/";

                        if (!is_dir(\$target_dir)) {
                            mkdir(\$target_dir, 0777, true);
                        }

                        /* ==========================
                        Breadcrumb Image Upload
                        ========================== */
                        if (
                            isset(\$_FILES['breadcrumb_image']) &&
                            \$_FILES['breadcrumb_image']['error'] == 0
                        ) {
                            \$breadcrumb_name = time() . '_breadcrumb_' . basename(\$_FILES['breadcrumb_image']['name']);
                            \$breadcrumb_path = \$target_dir . \$breadcrumb_name;

                            if (move_uploaded_file(\$_FILES['breadcrumb_image']['tmp_name'], \$breadcrumb_path)) {
                                \$image_data['breadcrumb_image'] = \$breadcrumb_name;
                            }
                        }

                        /* ==========================
                        Preview Images Upload
                        ========================== */

                        \$preview_fields = [
                            'preview_images1',
                            'preview_images2',
                            'preview_images3',
                            'preview_images4'
                        ];

                        foreach (\$preview_fields as \$field) {

                            if (
                                isset(\$_FILES[\$field]) &&
                                \$_FILES[\$field]['error'] == 0
                            ) {

                                \$preview_name = time() . '_' . \$field . '_' . basename(\$_FILES[\$field]['name']);
                                \$preview_path = \$target_dir . \$preview_name;

                                if (move_uploaded_file(\$_FILES[\$field]['tmp_name'], \$preview_path)) {
                                    \$image_data['preview_images'][] = \$preview_name;
                                }
                            }
                        }

                        /* First Preview Image Required */
                        if (empty(\$image_data['preview_images'])) {
                            echo "<script>
                                alert('Please upload at least one preview image.');
                                window.history.back();
                            </script>";
                            exit;
                        }

                        \$image_json = json_encode(\$image_data);
                        \$stmt = \$conn->prepare("INSERT INTO package (package_img, image_data, package_name, title, subtitle, short_description, description, cat_id, created_at, template, addon_service, addon_package, addon_product, gst_id, is_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        \$stmt->bind_param("sssssssisssssii", \$package_image, \$image_json, \$package_name, \$title, \$subtitle, \$short_description, \$description, \$cat_id, \$created_at, \$template, \$addons, \$addon_packages, \$addon_products, \$gst_id, \$is_login);

                        if (\$stmt->execute()) {
                        \$package_id = \$conn->insert_id;
                        
                        logActivity(
                            \$conn,
                            \$userid,
                            "Package",                   // module
                            "New package created - \$package_name"  // description
                        );
        
                            \$stmt->close();
                        // ===== CREATE LANDING PAGE FILE =====
                        \$landingSlug = strtolower(preg_replace('/\s+/', '-', \$package_name));
                        \$landingFileName = \$landingSlug . ".php";                    

                        // =====================================================
                        // CREATE LANDING FILE CONTENT
                        // =====================================================

                        \$landingContent = <<<'LANDING'
                        <?php
                        include '../../partials/connection.php';
                        include '../head.php';
                        include '../scripts.php';
                        session_start();
                        \$isLoggedIn = isset(\$_SESSION['user_id']) && \$_SESSION['user_id'] > 0;
                        \$product_id = __PACKAGE_ID__; // or your generated product id
                        \$stmt = \$conn->prepare("
                            SELECT *
                            FROM package
                            WHERE id = ? AND is_deleted = 0
                        ");
                        \$stmt->bind_param("i", \$product_id);
                        \$stmt->execute();
                        \$product = \$stmt->get_result()->fetch_assoc();
                        \$inclusive = [];
                        \$exclusive = [];
                        \$stmt = \$conn->prepare("
                            SELECT feature_type, feature
                            FROM features
                            WHERE package_id = ?
                            AND cat_type = 1
                            ORDER BY id ASC
                        ");
                        \$stmt->bind_param("i", \$product['id']);
                        \$stmt->execute();
                        \$result = \$stmt->get_result();
                        while (\$row = \$result->fetch_assoc()) {

                            if (strtolower(trim(\$row['feature_type'])) == 'inclusive') {
                                \$inclusive[] = \$row['feature'];
                            } elseif (strtolower(trim(\$row['feature_type'])) == 'exclusive') {
                                \$exclusive[] = \$row['feature'];
                            }

                        }
                        \$stmt->close();

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
                        ?>
                        <!DOCTYPE html>
                        <html lang="en">
                            <head>
                            <meta charset="UTF-8">
                            <title>Package</title>
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
                                /* RESPONSIVE */
                                @media (max-width: 768px) {
                                    .row {
                                        flex-direction: column !important;
                                    }
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
                                /* SWEET ALERT FIX */
                                .swal2-container {
                                    z-index: 1000000 !important;
                                }
                                /* ===== STATIC SECTION ===== */
                                .static-section {
                                    margin-top: 30px;
                                    text-align: left;
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
                                .swal2-container {
                                    z-index: 1000000 !important;
                                }
                                .btn-yellow {
                                    background: var(--yellow) !important;
                                    color: #000 !important;
                                    width: 10%;
                                }
                            </style>
                            </head>
                            <body>
                                <div class="container">
                                    <!-- HERO -->
                                    <div class="top-header">
                                        <!-- LEFT: LOGO -->
                                        <div class="header-left">
                                            <?php if (!empty(\$logo)): ?>
                                                <img src="../../uploads/company_logo/<?php echo htmlspecialchars(\$logo); ?>" alt="Company Logo">
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
                                        <?php if (!empty(\$product['package_name'])): ?>
                                            <img src="../../uploads/products/<?php echo htmlspecialchars(\$product['package_img']); ?>" class="banner-img">
                                        <?php endif; ?>
                                        <div class="banner-overlay">
                                            <h2 class="banner-title">
                                                <?php echo htmlspecialchars(\$product['package_name']); ?>
                                            </h2>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- LEFT -->
                                        <div class="col-left" style="flex: 100% !important;">
                                            <div class="card">
                                                <h4>Description</h4>
                                                <p><?php echo nl2br(htmlspecialchars(\$product['description'])); ?></p>
                                            </div>
                                            <div class="card">
                                                <h4>Features</h4>
                                                <div class="features-row">
                                                    <!-- INCLUSIONS -->
                                                    <div class="features-col inclusions">
                                                        <ul class="custom-list">
                                                            <?php if (!empty(\$inclusive)): ?>
                                                                <?php foreach (\$inclusive as \$item): ?>
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
                                                            <?php if (!empty(\$exclusive)): ?>
                                                                <?php foreach (\$exclusive as \$item): ?>
                                                                    <li><?php echo htmlspecialchars(\$item); ?></li>
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
                                                    \$symbol = "$";
                                                    \$r = \$conn->query("SELECT symbol FROM currencies WHERE is_active=1 LIMIT 1");
                                                    if(\$row = \$r->fetch_assoc()){
                                                        \$symbol = \$row['symbol'];
                                                    }
                                                    \$loginRequired = (\$product['is_login'] == 1);
                                                ?>
                                                <?php if(\$loginRequired && !\$isLoggedIn): ?>
                                                    <div class="card">
                                                        <h4>Products Pricing Table</h4>
                                                        <p class="text-center" style="font-size:16px;font-weight:600;margin-top:15px;">
                                                            <a href="#" onclick="openLoginPopup()" class="btn mt-2"> 🔒 Sign-In to See the Product</a>
                                                        </p>
                                                    </div>
                                                    <?php else: ?>
                                                    <?php
                                                        \$packages = [];
                                                        \$durations = [];
                                                        \$stmt = \$conn->prepare("
                                                            SELECT * FROM package
                                                            WHERE id = ? AND is_deleted = 0 AND is_active = 1
                                                        ");
                                                        \$stmt->bind_param("i", \$product['id']);
                                                        \$stmt->execute();
                                                        \$res = \$stmt->get_result();
                                                        \$data = [];
                                                        while (\$row = \$res->fetch_assoc()) {
                                                            \$data[\$row['id']] = \$row;
                                                        }
                                                        \$stmt->close();
                                                        if (!empty(\$data)) {
                                                            \$ids = implode(',', array_keys(\$data));
                                                            \$sql = "SELECT d.*, p.title, p.subtitle, p.description,
                                                                        p.package_name, p.is_active pkg_active
                                                                    FROM durations d
                                                                    JOIN package p ON d.package_id = p.id
                                                                    WHERE d.package_id IN (\$ids)";
                                                            \$r = \$conn->query(\$sql);
                                                            while (\$row = \$r->fetch_assoc()) {
                                                                \$dur = \$row['duration'];
                                                                \$packages[\$dur][] = \$row;
                                                                \$durations[\$dur] = \$dur;
                                                            }
                                                        }
                                                        // currency
                                                        \$symbol="$";
                                                        \$r=\$conn->query("SELECT symbol FROM currencies WHERE is_active=1 LIMIT 1");
                                                        if(\$row=\$r->fetch_assoc()) 
                                                        \$symbol=\$row['symbol'];
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
                                                                                <button class="nav-link px-24 py-10 text-md rounded-pill text-secondary-light fw-medium <?= \$first ? 'active' : '' ?>" id="tab-<?= md5(\$duration_name) ?>" data-bs-toggle="pill" 
                                                                                    data-bs-target="#tab-pane-<?= md5(\$duration_name) ?>" type="button" role="tab" aria-controls="tab-pane-<?= md5(\$duration_name) ?>" aria-selected="<?= \$first ? 'true' : 'false' ?>">
                                                                                    <?= htmlspecialchars(\$duration_name) ?>
                                                                                </button>
                                                                            </li>
                                                                        <?php \$first = false; endforeach; ?>
                                                                    </ul>

                                                                    <!-- Duration Tab Content -->
                                                                    <div class="tab-content" id="pills-tabContent">
                                                                        <?php \$first = true; foreach (\$durations as \$duration_name): ?>
                                                                            <div class="tab-pane fade <?= \$first ? 'show active' : '' ?>" id="tab-pane-<?= md5(\$duration_name) ?>" role="tabpanel" aria-labelledby="tab-<?= md5(\$duration_name) ?>" tabindex="0">
                                                                                <div class="row gy-4">
                                                                                    <?php foreach (\$packages[\$duration_name] as \$package): ?>
                                                                                        <div class="col-xxl-4 col-sm-6">
                                                                                            <div class="pricing-plan position-relative radius-24 overflow-hidden border">
                                                                                                <?php \$isActive = (\$package['pkg_active'] == 1); ?>
                                                                                                <?php if (!\$isActive): ?>
                                                                                                    <p class="mb-0 text-sm text-danger fw-semibold mt-2 float-end">Inactive</p>
                                                                                                <?php endif; ?> 
                                                                                                <?php
                                                                                                    // Generate SEO URL from package_name
                                                                                                    \$packageSlug = strtolower(trim(\$package['package_name']));
                                                                                                    \$packageSlug = preg_replace('/[^a-z0-9]+/i', '-', \$packageSlug);
                                                                                                    \$packageSlug = trim(\$packageSlug, '-');
                                                                                                    // Final URL
                                                                                                    \$packageUrl = "../../pages/packages/" . \$packageSlug . ".php";
                                                                                                ?>
                                                                                                <h5 class="mb-0 lufera-color">
                                                                                                    <a href="<?= htmlspecialchars(\$packageUrl) ?>" style="text-decoration:none; color:inherit;"> <?= htmlspecialchars(\$package['title']) ?></a>
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
                                                                                                <form action="../../cart.php" method="POST">
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
                                            "../../sign-in.php?redirect=<?php echo urlencode(\$_SERVER['REQUEST_URI']); ?>";
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
                        LANDING;
                        \$landingContent = str_replace('__PACKAGE_ID__',\$package_id,\$landingContent);


                        // =====================================================
                        // CREATE ROOT FILE CONTENT
                        // =====================================================

                        \$rootContent = <<<'ROOT'

                        <?php include './partials/layouts/layoutTop.php'; ?>
                        <!DOCTYPE html>
                        <html>
                        <head>
                        <meta charset="UTF-8">
                        <title>Product View</title>
                        <?php
                            \$sql = "SELECT * FROM company LIMIT 1";
                            \$result =\$conn->query(\$sql);
                            if (\$result && \$result->num_rows > 0) {
                                \$row = \$result->fetch_assoc();
                                \$company_name = \$row['full_name'];
                                \$logo = \$row['logo'];
                                \$email = \$row['email'];
                                \$phone = \$row['phone_no'];
                                \$address = \$row['address'];

                            }    
                            \$Id = __PACKAGE_ID__;
                            \$sql = "select * from package where id = \$Id";
                            \$result = \$conn ->query(\$sql);
                            \$row = \$result ->fetch_assoc();
                            \$package_img = \$row['package_img'];
                            \$package_name = \$row['package_name'];
                            \$title = \$row['title'];
                            \$subtitle = \$row['subtitle'];
                            \$duration = \$row['duration'];
                            \$gst = \$row['gst_id'];
                            \$cat_id_sc = \$row['cat_id'];

                            // Decode image_data JSON
                            \$imageData = json_decode(\$row['image_data'], true);

                            \$breadcrumbImage = \$imageData['breadcrumb_image'] ?? '';
                            \$previewImages   = \$imageData['preview_images'] ?? [];

                            \$duration_sql = "SELECT * FROM durations WHERE package_id = \$Id ORDER BY id ASC LIMIT 1";
                            \$duration_result = \$conn->query(\$duration_sql);
                            \$duration = \$duration_result->fetch_assoc();
                        ?>
                        <style>
                        .custom-list {
                            list-style: none !important;
                            padding: 0 !important;
                        }
                        .custom-list li {
                            padding: 6px 0 !important;
                            font-size: 15px !important;
                        }
                        .feature-img {
                            width: 100%;
                            height: 400px;
                            object-fit: cover;
                            border-radius:8px;
                        }
                        .features-row {
                            display: flex !important;
                            gap: 25px !important;
                            margin-top: 10px !important;
                        }
                        @media (max-width: 768px) {
                            .features-row {
                                flex-direction: column !important;
                            }
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
                            background-image: url('./uploads/products/<?php echo \$breadcrumbImage; ?>'); 
                            background-size: cover; 
                            background-position: center; 
                            height: 300px;
                        }
                        @media (min-width: 768px) {
                            .api-btn{    
                                top: 50%;
                                right: 20px;
                                transform: translateY(-50%);
                                display: flex;
                                gap: 10px;
                            }
                        }
                        @media (max-width: 768px) {
                            .api-btn{
                                justify-self: center;
                            }
                        }
                        .breadcrumb-title{
                            text-shadow: 0px 0px 3px var(--lufera-text-color);
                        }
                        </style>
                        </head>
                        <body>    
                            <!-- Header Navbar -->
                            <div class="container">
                                <section class="breadcrumb-hero position-relative w-100 mt-20 mx-auto d-flex align-items-center m-20">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-xl-12 col-lg-12">
                                                <div class="breadcrumb-content text-center">
                                                    <h2 class="text-capitalize breadcrumb-title"><?php echo \$package_name; ?></h2>
                                                    <nav aria-label="breadcrumb">
                                                        <ol class="breadcrumb justify-content-center">
                                                            <li class="breadcrumb-item">Package</li>
                                                            <li class="breadcrumb-item" aria-current="page"><?php echo \$package_name; ?></li>
                                                        </ol>                   
                                                    </nav>
                                                </div>
                                                <div class="api-btn position-absolute">
                                                    <button class="btn lufera-bg lufera-text" data-bs-toggle="modal" data-bs-target="#apiModal">API</button>
                                                </div>  
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>        
                                <!-- ===== API DATA PREPARATION ===== -->
                                <?php
                                // BASE URL (dynamic)
                                \$protocol = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                                \$host = \$_SERVER['HTTP_HOST'];
                                //\$basePath = dirname(\$_SERVER['SCRIPT_NAME']);
                                \$basePath = rtrim(dirname(\$_SERVER['SCRIPT_NAME']), '');

                                //\$currentBaseUrl = \$protocol . \$host . \$basePath;
                                \$currentBaseUrl = rtrim(\$protocol . \$host . \$basePath, '/');

                                \$slug = strtolower(trim(\$package_name));        // lowercase + trim
                                \$slug = preg_replace('/\s+/', '-', \$slug);      // replace spaces with hyphens
                                \$slug = preg_replace('/[^a-z0-9\-]/', '', \$slug); // remove special chars

                                // FINAL LANDING URL
                                \$landingUrl = \$currentBaseUrl . "/pages/packages/" . \$slug . ".php";

                                // FULL PLAN SHORTCODE
                                \$fullPlanShortcode = "Product-Shortcode-" . \$cat_id_sc;
                                \$categoryShortcode = "Category-Shortcode-" . \$cat_id_sc;
                                \$indPlanShortcode = "Product-\$title-Shortcode-" . \$Id;
                                
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
                                                <h6>Landing URL</h6>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="landingUrl" class="form-control" value="<?php echo \$landingUrl; ?>" readonly>
                                                    <button class="btn copy-btn lufera-bg" onclick="copyText('landingUrl')">Copy</button>
                                                </div>
                                                <hr style="margin:15px 0 10px;">
                                                <!-- SHORTCODES TITLE -->
                                                <h5 style="font-size:18px !important">Shortcodes</h5>
                                                <h6>Category Shortcode</h6>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="catShortcode" class="form-control" value="<?php echo \$categoryShortcode; ?>" readonly>
                                                    <button class="btn copy-btn lufera-bg" onclick="copyText('catShortcode')">Copy</button>
                                                </div>
                                                <h6>Full Plan Shortcode</h6>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="fullPlan" class="form-control" value="<?php echo \$fullPlanShortcode; ?>" readonly>
                                                    <button class="btn copy-btn lufera-bg" onclick="copyText('fullPlan')">Copy</button>
                                                </div>
                                                <!-- FULL PLAN -->
                                                <h6>Individual Plan Shortcode</h6>
                                                <?php echo \$title; ?>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="ind_Plan" class="form-control" value="<?php echo \$indPlanShortcode; ?>" readonly>
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
                                \$result = \$conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
                                \$symbol = "₹";
                                if (\$row_symbol = \$result->fetch_assoc()) {
                                    \$symbol = \$row_symbol['symbol'];
                                }

                                \$sql_login = "select * from products where id = \$Id";
                                \$result_login = \$conn->query(\$sql_login);
                                \$row_login = \$result_login->fetch_assoc();
                                \$isLoginRequired = (\$row_login['is_login'] == 1);
                                ?>        
                                
                            <div class="container">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6">
                                        <div class="row">
                                            <!--<div class="col-lg-3 text-end">
                                                <ul>
                                                    <?php if (!empty(\$previewImages)): ?>
                                                        <?php foreach(\$previewImages as \$img): ?>
                                                            <li>
                                                                <img src="./uploads/products/<?php echo \$img; ?>" class="preview-img" alt="Preview Image">
                                                            </li>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <li>
                                                            <img src="./uploads/products/<?php echo \$package_img; ?>" class="preview-img" alt="Product Image">
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>-->
                                            <div class="col-lg-12">
                                                <img src="./uploads/products/<?php echo \$package_img; ?>" alt="Product Image" class="feature-img mb-20">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6">
                                        <div class="mb-20">
                                            <h2 class="mb-4 text-capitalize">
                                                <?php echo \$package_name; ?>
                                            </h2>
                                            <p class="price">
                                                <span class="amount fs-2 fw-semibold me-6">
                                                    <?= \$symbol ?><?= \$duration['price']; ?>
                                                </span>
                                                <?php if (!empty('preview_price')): ?>
                                                <span class="text-decoration-line-through fs-2 fw-semibold">
                                                    <?= \$symbol ?><?= \$duration['preview_price']; ?>
                                                </span>
                                            <?php endif; ?>
                                            </p>
                                            
                                            <p>
                                                <?php echo \$row['short_description']; ?>
                                            </p>

                                            <form action="cart.php" method="POST">                                
                                                <input type="hidden" name="type" value="product">  
                                                <input type="hidden" name="id" value="<?= htmlspecialchars(\$Id) ?>">
                                                <input type="hidden" name="plan_name" value="<?= htmlspecialchars(\$package_name) ?>">
                                                <input type="hidden" name="title" value="<?= htmlspecialchars(\$title) ?>">
                                                <input type="hidden" name="subtitle" value="<?= htmlspecialchars(\$subtitle) ?>">
                                                <input type="hidden" name="price" value="<?= \$duration['price']; ?>">
                                                <input type="hidden" name="duration" value="<?= \$duration['duration']; ?>">
                                                <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                                <input type="hidden" name="gst" value="<?= htmlspecialchars(\$gst) ?>">                            
                                                <button type="submit" class="btn lufera-bg lufera-text w-50 p-12 mb-3" style="border-radius: 50px;"> 
                                                    Shop Now
                                                </button>        
                                            </form>
                                            <p><b>Duration</b> :
                                                <?php echo \$duration['duration']; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            
                                <div class="mb-20">
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
                                                <p class="text-secondary-light mb-0"><?php echo \$row['description']; ?></p>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="pills-details" role="tabpanel" aria-labelledby="pills-details-tab" tabindex="0">
                                            <div>
                                                <?php
                                                    \$features_sql = "SELECT feature_type, feature FROM features WHERE cat_type = 1 AND package_id = \$Id";
                                                    \$features_result = \$conn->query(\$features_sql);
                                                    
                                                    \$included = [];
                                                    \$excluded = [];
                                                    
                                                    while (\$frow = \$features_result->fetch_assoc()) {
                                                        if (\$frow['feature_type'] == 'inclusive') {
                                                            \$included[] = \$frow['feature'];
                                                        } else {
                                                            \$excluded[] = \$frow['feature'];
                                                        }
                                                    }
                                                ?>
                                                
                                                <div class="features-row">
                                                    <!-- INCLUSIONS -->
                                                    <div class="features-col inclusions">
                                                        <h6>Included</h6>
                                                        <ul class="custom-list">
                                                            <?php if (!empty(\$included)): ?>
                                                                <?php foreach (\$included as \$inc): ?>
                                                                    <li><i class="fa fa-check"></i> <?php echo htmlspecialchars(\$inc); ?></li>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <li>No inclusions available</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                    <!-- EXCLUSIONS -->
                                                    <div class="features-col exclusions">
                                                    <h6>Excluded</h6>
                                                        <ul class="custom-list">
                                                            <?php if (!empty(\$excluded)): ?>
                                                                <?php foreach (\$excluded as \$exc): ?>
                                                                    <li><i class="fa fa-close"></i> <?php echo htmlspecialchars(\$exc); ?></li>
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
                        <?php include './partials/layouts/layoutBottom.php'; ?>

                        ROOT;

                        \$rootContent = str_replace(
                            '__PACKAGE_ID__',
                            \$package_id,
                            \$rootContent
                        );


                        // =====================================================
                        // FILE NAME
                        // =====================================================

                        \$landingFileName = \$landingSlug . '.php';


                        // =====================================================
                        // CREATE LANDING FILE
                        // pages/packages/package-name.php
                        // =====================================================

                        \$pagesDir = realpath(__DIR__) . '/pages/packages';

                        if (!is_dir(\$pagesDir)) {
                            mkdir(\$pagesDir, 0777, true);
                        }

                        \$landingFilePath = \$pagesDir . '/' . \$landingFileName;

                        if (!file_exists(\$landingFilePath)) {
                            file_put_contents(\$landingFilePath, \$landingContent);
                        }


                        // =====================================================
                        // CREATE ROOT FILE
                        // package-name.php
                        // =====================================================

                        \$rootDir = realpath(__DIR__);

                        \$rootFilePath = \$rootDir . '/' . \$landingFileName;

                        if (!file_exists(\$rootFilePath)) {
                            file_put_contents(\$rootFilePath, \$rootContent);
                        }
                            \$featureStmt = \$conn->prepare("INSERT INTO features (package_id, feature, feature_type, cat_type) VALUES (?, ?, ?, ?)");

                            // Inclusive
                            if (!empty(\$_POST['inclusive_features'])) {
                                foreach (\$_POST['inclusive_features'] as \$feature) {
                                    \$feature = trim(\$feature);
                                    if (\$feature !== "") {
                                        \$type = 'inclusive';
                                        \$cat_type = 1;
                                        \$featureStmt->bind_param("issi", \$package_id, \$feature, \$type, \$cat_type);
                                        \$featureStmt->execute();
                                    }
                                }
                            }

                            // Exclusive
                            if (!empty(\$_POST['exclusive_features'])) {
                                foreach (\$_POST['exclusive_features'] as \$feature) {
                                    \$feature = trim(\$feature);
                                    if (\$feature !== "") {
                                        \$type = 'exclusive';
                                        \$cat_type = 1;
                                        \$featureStmt->bind_param("issi", \$package_id, \$feature, \$type, \$cat_type);
                                        \$featureStmt->execute();
                                    }
                                }
                            }

                            \$featureStmt->close();
                            
                            
                            // 3️⃣ Insert duration+price pairs
                            if (!empty(\$_POST['duration_values']) && is_array(\$_POST['duration_values'])) {
                                \$durationStmt = \$conn->prepare("INSERT INTO durations (package_id, duration, price, created_at, preview_price) VALUES (?, ?, ?, ?, ?)");
                                
                                foreach (\$_POST['duration_values'] as \$index => \$value) {
                                    // \$unit = \$_POST['duration_units'][\$index] ?? '';
                                    // \$price = \$_POST['prices'][\$index] ?? '';
                                    // \$pre_prices = \$_POST['pre_prices'][\$index] ?? '';
                                    
                                    // if (!empty(\$value) && !empty(\$unit) && !empty(\$price)) {
                                    //     // Combine value + unit
                                    //     \$duration_text = \$value . ' ' . \$unit;
                                    //     \$durationStmt->bind_param("isdsd", \$package_id, \$duration_text, \$price, \$created_at, \$pre_prices);
                                    //     \$durationStmt->execute();
                                    // }

                                    \$unit = strtolower(trim(\$_POST['duration_units'][\$index] ?? ''));
                                    \$price = \$_POST['prices'][\$index] ?? '';
                                    \$pre_prices = \$_POST['pre_prices'][\$index] ?? '';

                                    if (!empty(\$value) && !empty(\$unit) && !empty(\$price)) {

                                        // Convert singular/plural and capitalize first letter
                                        if (\$unit == "days") {

                                            \$unitText = (\$value == 1) ? "Day" : "Days";

                                        } elseif (\$unit == "months") {

                                            \$unitText = (\$value == 1) ? "Month" : "Months";

                                        } elseif (\$unit == "years") {

                                            \$unitText = (\$value == 1) ? "Year" : "Years";

                                        } else {

                                            \$unitText = ucfirst(\$unit);

                                        }

                                        // Final duration text
                                        \$duration_text = \$value . " " . \$unitText;

                                        \$durationStmt->bind_param(
                                            "isdsd",
                                            \$package_id,
                                            \$duration_text,
                                            \$price,
                                            \$created_at,
                                            \$pre_prices
                                        );

                                        \$durationStmt->execute();
                                    }

                                }
                                \$durationStmt->close();
                            }

                            // create details file if missing
                            
                            \$slug = isset(\$_GET['slug']) ? \$_GET['slug'] : '';
                            \$det_file_path = \$slug . "-det.php";

                            if (!file_exists(\$det_file_path)) {
                                \$base_php = <<<'CODE'
                                    <?php 
                                        include './partials/connection.php';
                                    
                                        \$product_id = isset(\$_GET['product_id']) ? (int) \$_GET['product_id'] : 0;
                                        \$template = \$_GET['template'] ?? '';

                                        \$sql = "SELECT * FROM package WHERE id = " . \$product_id; 
                                        \$result = \$conn->query(\$sql);

                                        if (\$result && \$result->num_rows > 0) {
                                            \$row = \$result->fetch_assoc();
                                            \$id = \$row['id'];
                                            \$template_product = \$row['template'];
                                        }
                                    ?>

                                    <?php if (!empty(\$template_product)): ?>
                                        <?php include "./category_details/" . \$template_product . "-details.php"; ?>
                                    <?php endif; ?>
                                CODE;

                                file_put_contents(\$det_file_path, \$base_php);
                            }
                            echo "<script>
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Package saved successfully',
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'view-$pack_cat_url_Slug.php';
                                    }
                                });
                            </script>";
                        } else {
                            echo "<script>alert('Error: " . \$stmt->error . "'); window.history.back();</script>";
                        }
                    }
                ?>

                <div class="dashboard-main-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                        <h6 class="fw-semibold mb-0">Add Package</h6>
                    </div>

                    <div class="card h-100 p-0 radius-12">
                        <div class="card-body p-24">
                            <div class="row justify-content-center">
                                <div class="col-xxl-12 col-xl-8 col-lg-10">
                                    <form method="POST" enctype="multipart/form-data" class="row gy-3 needs-validation" novalidate autocomplete="off">
                                        <div class="row mb-2">
                                            <!-- Package Image -->
                                            <div class="col-lg-6">
                                                <label class="form-label">
                                                    Package Image <span class="text-danger-600">*</span>
                                                </label>

                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" class="file-input" accept="image/*" name="package_image" required>

                                                    <label class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>

                                                    <div class="invalid-feedback">
                                                        Please upload a Package image.
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Breadcrumb Image -->
                                            <div class="col-lg-6">
                                                <label class="form-label">
                                                    Breadcrumb Image <span class="text-danger-600">*</span>
                                                </label>

                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" class="file-input" accept="image/*" name="breadcrumb_image" required>

                                                    <label class="image-upload d-flex mw-100">
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
                                                    <input type="file" class="file-input" accept="image/*" name="preview_images1" required>

                                                    <label class="image-upload d-flex mw-100">
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
                                                    <input type="file" class="file-input" accept="image/*" name="preview_images2">

                                                    <label class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" class="file-input" accept="image/*" name="preview_images3">

                                                    <label class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="has-validation image-upload-wrapper">
                                                    <input type="file" class="file-input" accept="image/*" name="preview_images4">

                                                    <label class="image-upload d-flex mw-100">
                                                        <span>Click or Drag Image Here</span>
                                                        <img class="preview-image" alt="Preview Image">
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Package name <span class="text-danger-600">*</span></label>
                                            <div class="has-validation">
                                                <input type="text" class="form-control radius-8" name="package_name" required maxlength="100">
                                                <div class="invalid-feedback">
                                                    Package name is required
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Title <span class="text-danger-600">*</span></label>
                                            <div class="has-validation">
                                                <input type="text" class="form-control radius-8" name="title" required maxlength="100">
                                                <div class="invalid-feedback">
                                                    Title is required
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Subtitle <span class="text-danger-600">*</span></label>
                                            <div class="has-validation">
                                                
                                                <input type="text" class="form-control radius-8" name="subtitle" required maxlength="100">
                                                <div class="invalid-feedback">
                                                    Subtitle is required
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Description <span class="text-danger-600">*</span></label>
                                            <div class="has-validation">
                                                <textarea class="form-control radius-8" name="description" required></textarea>
                                                <div class="invalid-feedback">
                                                    Description is required
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Short Description <span class="text-danger-600">*</span></label>
                                            <div class="has-validation">
                                                <textarea class="form-control radius-8" name="short_description" required></textarea>
                                                <div class="invalid-feedback">
                                                    Short Description is required
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                                Duration & Price <span class="text-danger-600">*</span>
                                            </label>
                                            <div id="duration-wrapper">
                                                <div class="duration-group mb-10 d-flex gap-2 align-items-center">
                                                    <input type="number" name="duration_values[]" class="form-control radius-8" required min="1" style="width: 25%;" onkeydown="return event.key !== 'e'" placeholder="Value">
                                                    <select name="duration_units[]" class="form-control radius-8" required style="width: 25%;">
                                                        <option value="">Select Unit</option>
                                                        <option value="days">Days</option>
                                                        <option value="months">Months</option>
                                                        <option value="years">Years</option>
                                                    </select>
                                                    <input type="number" name="prices[]" class="form-control radius-8" required min="0" style="width: 25%;" onkeydown="return event.key !== 'e'" placeholder="Enter price">
                                                    <input type="number" name="pre_prices[]" class="form-control radius-8" required min="0" style="width: 25%;" onkeydown="return event.key !== 'e'" placeholder="Enter preview price">
                                                    <button type="button" class="btn btn-sm btn-success add-duration">+</button>
                                                </div>
                                            </div>
                                            <div class="invalid-feedback">
                                                At least one duration and price pair is required.
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <div class="row">
                                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Features <span class="text-danger-600">*</span></label>   
                                                <div class="col-6">
                                                    <label>Inclusive</label>
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
                                                    <label>Exclusive</label>
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

                                        <!-- Add-ons Section -->
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold">Add-Ons <span class="text-danger-600">*</span></label>

                                            <!-- Master Toggles -->
                                            <div class="d-flex flex-wrap gap-4 mb-3">
                                                <div class="form-check d-flex align-items-center">
                                                    <input class="form-check-input toggle-section" type="checkbox" id="showPackages" data-target="#packagesSection">
                                                    <label class="form-check-label ms-2 mb-0" for="showPackages">Packages</label>
                                                </div>
                                                <div class="form-check d-flex align-items-center">
                                                    <input class="form-check-input toggle-section" type="checkbox" id="showProducts" data-target="#productsSection">
                                                    <label class="form-check-label ms-2 mb-0" for="showProducts">Products</label>
                                                </div>
                                                <div class="form-check d-flex align-items-center">
                                                    <input class="form-check-input toggle-section" type="checkbox" id="showAddons" data-target="#addonsSection">
                                                    <label class="form-check-label ms-2 mb-0" for="showAddons">Add-on Services</label>
                                                </div>
                                            </div>

                                            <!-- Packages -->
                                            <div id="packagesSection" class="d-none border p-3 radius-8 mb-3">
                                                <h6 class="fw-semibold" style="font-size: 1rem !important;">Available Packages</h6>
                                                <div class="d-flex flex-wrap gap-3">
                                                    <?php if (!empty(\$packages_list)): ?>
                                                        <?php foreach (\$packages_list as \$package): ?>
                                                            <div class="form-check d-flex align-items-center me-3">
                                                                <input class="form-check-input" type="checkbox" name="packages[]" 
                                                                    value="<?php echo \$package['id']; ?>" 
                                                                    id="package_<?php echo \$package['id']; ?>">
                                                                <label class="form-check-label ms-2 mb-0" for="package_<?php echo \$package['id']; ?>">
                                                                    <?php echo htmlspecialchars(\$package['title']); ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <p>No packages available.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Products -->
                                            <div id="productsSection" class="d-none border p-3 radius-8 mb-3">
                                                <h6 class="fw-semibold" style="font-size: 1rem !important;">Available Products</h6>
                                                <div class="d-flex flex-wrap gap-3">
                                                    <?php if (!empty(\$products_list)): ?>
                                                        <?php foreach (\$products_list as \$product): ?>
                                                            <div class="form-check d-flex align-items-center me-3">
                                                                <input class="form-check-input" type="checkbox" name="products[]" value="<?php echo \$product['id']; ?>" id="product_<?php echo \$product['id']; ?>">
                                                                <label class="form-check-label ms-2 mb-0" for="product_<?php echo \$product['id']; ?>">
                                                                    <?php echo htmlspecialchars(\$product['title']); ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <p>No products available.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Add-ons -->
                                            <div id="addonsSection" class="d-none border p-3 radius-8 mb-3">
                                                <h6 class="fw-semibold" style="font-size: 1rem !important;">Available Add-on Services</h6>
                                                <div class="d-flex flex-wrap gap-3">
                                                    <?php if (!empty(\$addons_list)): ?>
                                                        <?php foreach (\$addons_list as \$addon): ?>
                                                            <div class="form-check d-flex align-items-center me-3">
                                                                <input class="form-check-input" type="checkbox" name="addons[]" 
                                                                    value="<?php echo \$addon['id']; ?>" 
                                                                    id="addon_<?php echo \$addon['id']; ?>">
                                                                <label class="form-check-label ms-2 mb-0" for="addon_<?php echo \$addon['id']; ?>">
                                                                    <?php echo htmlspecialchars(\$addon['name']); ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <p>No add-on services available.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ GST Dropdown -->
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">GST (Tax)</label>
                                            <select class="form-control radius-8" name="gst_id">
                                                <option value="">Select GST</option>
                                                <?php if (!empty(\$gst_list)): ?>
                                                    <?php foreach (\$gst_list as \$gst): ?>
                                                        <option value="<?= \$gst['id']; ?>">
                                                            <?= htmlspecialchars(\$gst['rate']) . '% (' . htmlspecialchars(\$gst['tax_name']) . ')'; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="">No taxes found</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Is Login?</label>
                                            <div class="form-check d-flex align-items-center">
                                                <input class="form-check-input" type="checkbox" name="is_login" id="isLogin">  
                                                <label class="form-check-label ms-2 mb-0" for="isLogin">Require login to show package pricing</label>       
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <button type="submit" name="save_package" class="btn lufera-bg lufera-text text-md px-56 py-12 radius-8">
                                                Submit
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        // Add/remove features dynamically
                        const featureWrapper = document.getElementById("feature-wrapper");
                        featureWrapper.addEventListener("click", function (e) {
                            if (e.target && e.target.classList.contains("add-feature")) {
                                e.preventDefault();
                                const newGroup = document.createElement("div");
                                newGroup.className = "feature-group mb-10 d-flex gap-2";
                                newGroup.innerHTML = `
                                    <input type="text" name="features[]" class="form-control radius-8" required placeholder="Enter a feature" />
                                    <button type="button" class="btn btn-sm btn-danger remove-feature">−</button>
                                `;
                                featureWrapper.appendChild(newGroup);
                            }
                            if (e.target && e.target.classList.contains("remove-feature")) {
                                e.preventDefault();
                                e.target.parentElement.remove();
                            }
                        });                                
                    });
            
                    // Toggle sections
                        document.querySelectorAll(".toggle-section").forEach(checkbox => {
                            checkbox.addEventListener("change", function () {
                                const target = document.querySelector(this.dataset.target);
                                if (!target) return; // safety
                                if (this.checked) {
                                    target.classList.remove("d-none");
                                } else {
                                    target.classList.add("d-none");
                                    // Optional: uncheck all children when hiding
                                    target.querySelectorAll("input[type=checkbox]").forEach(ch => ch.checked = false);
                                }
                            });
                        });

                    // Add/remove duration+price rows with value/unit combination
                    const durationWrapper = document.getElementById("duration-wrapper");
                    durationWrapper.addEventListener("click", function (e) {
                        if (e.target && e.target.classList.contains("add-duration")) {
                            e.preventDefault();
                            const newGroup = document.createElement("div");
                            newGroup.className = "duration-group mb-10 d-flex gap-2 align-items-center";
                            newGroup.innerHTML = `
                                <input type="number" name="duration_values[]" class="form-control radius-8" placeholder="Value" required min="1" style="width: 25%;" onkeydown="return event.key !== 'e'">
                                <select name="duration_units[]" class="form-control radius-8" required style="width: 25%;">
                                    <option value="">Select Unit</option>
                                    <option value="days">Days</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
                                <input type="number" name="prices[]" class="form-control radius-8" placeholder="Enter price" required min="0" style="width: 25%;" onkeydown="return event.key !== 'e'">
                                <input type="number" name="pre_prices[]" class="form-control radius-8" placeholder="Enter preview price" required min="0" style="width: 25%;" onkeydown="return event.key !== 'e'">
                                <button type="button" class="btn btn-sm btn-danger remove-duration">−</button>
                            `;
                            durationWrapper.appendChild(newGroup);
                        }
                        if (e.target && e.target.classList.contains("remove-duration")) {
                            e.preventDefault();
                            e.target.parentElement.remove();
                        }
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
                
                document.querySelectorAll('.file-input').forEach(input => {

                const wrapper = input.closest('.image-upload-wrapper');
                const preview = wrapper.querySelector('.preview-image');
                const labelText = wrapper.querySelector('.image-upload span');

                // Open file picker when upload area clicked
                wrapper.querySelector('.image-upload').addEventListener('click', () => {
                    input.click();
                });

                input.addEventListener('change', function () {

                    const file = this.files[0];

                    if (file) {
                        const reader = new FileReader();

                        reader.onload = function (e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            labelText.style.display = 'none';
                        };

                        reader.readAsDataURL(file);

                        input.setCustomValidity('');
                    } else {
                        preview.style.display = 'none';
                        labelText.style.display = 'block';
                        input.setCustomValidity('Please upload an image');
                    }
                });
            });

            // Validation
            document.querySelector('form').addEventListener('submit', function () {

                document.querySelectorAll('.file-input[required]').forEach(input => {

                    if (!input.files.length) {
                        input.setCustomValidity('Please upload an image');
                    } else {
                        input.setCustomValidity('');
                    }

                });

            });
                </script>
                <?php include './partials/layouts/layoutBottom.php' ?>
            PHP;

            file_put_contents($add_file_path, $add_content);
            $stmt = $conn->prepare("SELECT cat_id, cat_url FROM categories WHERE cat_id = ?");
            $stmt->bind_param("i", $product_category);
            $stmt->execute();
            $stmt->bind_result($cat_id1, $cat_url1);
            $stmt->fetch();
            $stmt->close();

            $cat_url1 = pathinfo($cat_url1, PATHINFO_FILENAME);
            $catSlug1 = strtolower(preg_replace('/\s+/', '-', $cat_url1));
            //header("Location: add-$pack_cat_url_Slug.php");
            header("Location: add-$pack_cat_url_Slug.php?id=$cat_id1&slug=$catSlug1&template=$template");

            $view_file_name = "view-$pack_cat_url_Slug.php";
            $view_file_path = dirname(__DIR__) . '/' . $view_file_name;
            $view_content = <<<PHP
            <?php \$_GET['product_category'] = $product_category; ?>
            <?php include './view-package.php'; ?>
            PHP;
            file_put_contents($view_file_path, $view_content);

            exit;
        } elseif ($product_type === 'Product') {
            $stmt = $conn->prepare("SELECT cat_id, cat_url FROM categories WHERE cat_id = ?");
            $stmt->bind_param("i", $product_category);
            $stmt->execute();
            $stmt->bind_result($cat_id1, $cat_url1);
            $stmt->fetch();
            $stmt->close();

            $cat_url1 = pathinfo($cat_url1, PATHINFO_FILENAME);
            $catSlug1 = strtolower(preg_replace('/\s+/', '-', $cat_url1));

            $add_file_name1 = "add-$catSlug1.php";
            $add_file_path1 = dirname(__DIR__) . '/' . $add_file_name1;
            $add_content1 = <<<PHP
                <?php include './add-product.php' ?>
            PHP;
            file_put_contents($add_file_path1, $add_content1);
            
            header("Location: add-$catSlug1.php?id=$cat_id1&slug=$catSlug1&template=$template");

            $view_file_name1 = "view-$catSlug1.php";
            $view_file_path1 = dirname(__DIR__) . '/' . $view_file_name1;
            $view_content1 = <<<PHP
                <?php \$_GET['product_category'] = $product_category; ?>
                <?php include './view-product.php'; ?>
            PHP;
            file_put_contents($view_file_path1, $view_content1);

            exit;
        } else {
            $_SESSION['swal_error'] = "Invalid product type selected.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .sidebar-menu-wrapper {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        max-height: calc(100vh - 100px); /* Adjust based on your header height */
        overflow-y: auto;
    }
    .top-menu,
    .bottom-menu {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .sidebar-menu-area {
        height: 100%;
        overflow: hidden;
    }
    .top-menu {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        height: 100%;
    }
    /* .top-menu .add-category-menu {
        margin-top: auto;
        padding-top: 10px;
        border-bottom: 1px solid #eee; 
    } */
    .add-category-menu{
        position: sticky;
        bottom: 0;
        background: #fff;
    }
</style>

<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="admin-dashboard.php" class="sidebar-logo">
            <img src="uploads/company_logo/<?php echo $logo; ?>" alt="site logo" class="light-logo">
            <img src="uploads/company_logo/<?php echo $logo; ?>" alt="site logo" class="dark-logo">
            <img src="assets/images/Lufera-icon.png" alt="site logo" class="logo-icon">
        </a>
    </div>
    <div class="sidebar-menu-area">
        <div class="sidebar-menu-wrapper">
            <ul class="sidebar-menu top-menu overflow-y-auto" id="sidebar-menu">
                <!-- Dashboard + Dynamic Categories + Add New Category go here -->
                <li>
            <?php if ($row['role'] == "1") { ?>
                <a href="admin-dashboard.php">
                <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            <?php } else { ?>
            <a href="admin-dashboard.php">
                <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            <?php } ?>
            </li>
            <?php
                $query = "
                    SELECT 
                        categories.cat_id, 
                        categories.cat_name, 
                        categories.cat_url 
                    FROM users 
                    INNER JOIN roles ON users.role = roles.id 
                    INNER JOIN permission ON roles.id = permission.role_id 
                    INNER JOIN categories ON permission.category_id = categories.cat_id 
                    WHERE users.id = ?
                    ORDER BY categories.cat_id DESC";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $userid);
                $stmt->execute();
                $cat_results = $stmt->get_result();

                while ($cat = $cat_results->fetch_assoc()) { ?>
                <li>
                    <a href="<?= htmlspecialchars($cat['cat_url']) ?>?cat_id=<?= urlencode($cat['cat_id']) ?>">
                        <iconify-icon icon="mdi:tag-outline" class="menu-icon"></iconify-icon>
                        <span><?= htmlspecialchars($cat['cat_name']) ?></span>
                    </a>
                </li>
            <?php } ?>

            <?php if ($row['role'] == "1") {
                // Fetch categories
                $cat_result = $conn->query("SELECT cat_id, cat_name, cat_url FROM categories ORDER BY cat_id DESC");

                while ($cat = $cat_result->fetch_assoc()) { ?>
                    <li>
                        <div class="category-item-wrapper">
                            <a href="<?= htmlspecialchars($cat['cat_url']) ?>?cat_id=<?= urlencode($cat['cat_id']) ?>" class="category-link">
                                <iconify-icon icon="mdi:tag-outline" class="menu-icon"></iconify-icon>
                                <span><?= htmlspecialchars($cat['cat_name']) ?></span>
                            </a>
                        </div>
                    </li>
                <?php } ?>
            <?php } ?>            
            </ul>
            <ul class="sidebar-menu bottom-menu" id="sidebar-menu" style="border-top: 1px solid #eee; ">
            <?php if ($row['role'] == "1" || $row['role'] == "2") { ?>
            <li class="dropdown">
                    <a href="javascript:void(0)">
                        <!-- <iconify-icon icon="hugeicons:invoice-03" class="menu-icon"></iconify-icon> -->
                        <iconify-icon icon="hugeicons:table" class="menu-icon"></iconify-icon>
                        <span>LuferaSheets</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="dashboard-sheets.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Sheets</a>
                        </li>
                        <li>
                            <a href="form_dashboard.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Form Builder</a>
                        </li>
                    </ul>
                </li>
            <?php } ?>
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="hugeicons:invoice-03" class="menu-icon"></iconify-icon>
                    <span>Billing</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="subscription.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Subscriptions</a>
                    </li>
                    <li>
                        <a href="invoices.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Invoice</a>
                    </li>
                    <li>
                        <a href="payment_history.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Payment History</a>
                    </li>
                </ul>
            </li>
            <?php if ($row['role'] == "1" || $row['role'] == "2") { ?>
            <!-- <li>
                <a href="users.php">
                    <iconify-icon icon="hugeicons:user-03" class="menu-icon"></iconify-icon>
                    <span>Users</span>
                </a>
            </li> -->
            <?php } if ($row['role'] == "1") { ?>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <!-- <iconify-icon icon="hugeicons:invoice-03" class="menu-icon"></iconify-icon> -->
                        <iconify-icon icon="hugeicons:package" class="menu-icon"></iconify-icon>
                        <span>Inventory</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="add-on-service.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Add on Services</a>
                        </li>
                        <li>
                            <a href="view_categories.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Category</a>
                        </li>
                        <li>
                            <a href="view_packages.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Package</a>
                        </li>
                        <li>
                            <a href="view_products.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Product</a>
                        </li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
                        <span>Settings</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="bank_details.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Bank Details</a></li>
                        <li><a href="buy_for_customer.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Buy For Customer</a></li>
                        <li><a href="company.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Company</a></li>
                        <li><a href="credentials.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Credentials</a></li>
                        <li><a href="currencies.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Currencies</a></li>
                        <li><a href="invoice_settings.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Invoice Settings</a></li>
                        <li><a href="marketplace-settings.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Marketplace</a></li>
                        <li><a href="promotion.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Promotion</a></li>
                        <li><a href="add_policy.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Privacy policy</a></li>
                        <li><a href="role-access.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Roles</a></li>
                        <li><a href="taxes.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Taxes</a></li>
                        <li><a href="add_terms_conditions.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Terms and Conditions</a></li>
                        <li><a href="theme_colors.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Theme Colors</a></li>
                    </ul>

                </li>
                
            <?php } ?>
            <li>
                <a href="logout.php" class="hover-bg-transparent hover-text-danger">
                    <iconify-icon icon="bi:x-circle" class="menu-icon"></iconify-icon>
                    <span>Log-Out</span>
                </a>
            </li>
            </ul>
        </div>
    </div>
</aside>

<!-- SweetAlert for success -->
<?php if (isset($_SESSION['swal_success'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "success",
                title: "Success",
                text: "<?= $_SESSION['swal_success'] ?>",
                confirmButtonColor: "#3085d6",
            });
        });
    </script>
    <?php unset($_SESSION['swal_success']); ?>
<?php endif; ?>