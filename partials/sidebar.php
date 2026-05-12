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
                    height: 200px;
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
                        \$package_name = \$_POST['package_name'];                           
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

                        \$stmt = \$conn->prepare("INSERT INTO package (package_img, package_name, title, subtitle, short_description, description, cat_id, created_at, template, addon_service, addon_package, addon_product, gst_id, is_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        \$stmt->bind_param("ssssssissssssi", \$package_image, \$package_name, \$title, \$subtitle, \$short_description, \$description, \$cat_id, \$created_at, \$template, \$addons, \$addon_packages, \$addon_products, \$gst_id, \$is_login);

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

                        // LANDING content
                        \$landingContent = <<<'LANDING'
                        
                        <?php 
                        session_start();
                        \$loggedInUserId = isset(\$_SESSION['user_id']); // adjust based on your login system
                        include '../partials/connection.php'; ?>
                        <?php include 'head.php'; 
                        ?>
                        <!DOCTYPE html>
                        <html>
                        <head>
                        <meta charset="UTF-8">
                        <title>Product View</title>
                        <style>
                        .manage-top-btn {
                            position: static !important;
                            transform: none !important;
                            background: #fec700 !important;
                            padding: 8px 16px !important;
                            border-radius: 6px !important;
                            border: none !important;
                            font-weight: 600 !important;
                            cursor: pointer !important;
                            color:#4b5563 !important;
                        }
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
                            color: #fec700 !important;
                            font-size:24px !important;
                        }
                        
                        .breadcrumb-path {
                            font-size: 14px !important;
                            color: #ccc !important;
                        }
                        
                        .breadcrumb-path a {
                            color: #fec700 !important;
                            text-decoration: none !important;
                        }
                        .sec-heading{
                            font-size:20px !important;
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
                    
                        /* ICONS */
                        .features-col.inclusions li::before {
                            content: "✔ " !important;
                            font-weight: bold;
                        }
                    
                        .features-col.exclusions li::before {
                            content: "✖ " !important;
                            font-weight: bold;
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
                    
                        .feature-img {
                            width: 100% !important;
                            max-height: 300px !important;
                            object-fit: cover !important;
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
                            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);
                            text-align: center;
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
                        .swal2-container{
                            z-index:9999;
                        }
                        </style>
                        </head>
                        <body>
                    
                            <?php
                                \$sql = "SELECT * FROM company LIMIT 1";
                                \$result = \$conn->query(\$sql);
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
                                \$cat_id_sc = \$row['cat_id'];
                                \$title_sc = \$row['title'];
                            ?>
                            
                            <div class="content-wrapper" style="margin: 0px 15% 0px 15%;">
                                <!-- Header Navbar -->
                                <section class="top-header">
                                    <!-- LEFT: LOGO -->
                                    <div class="header-left">
                                        <img src="../uploads/company_logo/<?php echo \$logo; ?>" alt="Company Logo">
                                    </div>
                    
                                    <!-- RIGHT: CONTACT -->
                                    <div class="header-right">
                                        <span class="contact-item">📞 <?php echo \$phone; ?></span>
                                        <span class="contact-item">✉ <?php echo \$email; ?></span>
                                    </div>
                                </section>
                                
                                <!-- HERO -->
                                <section class="breadcrumb-hero position-relative">
                                    <!-- Manage Button -->
                                    <div style="position:absolute; top:50%; right:20px; transform:translateY(-50%); display:flex; gap:10px;">
                                        <button class="btn manage-top-btn" data-bs-toggle="modal" data-bs-target="#apiModal">API</button>
                                    </div>
                    
                                    <div class="breadcrumb-overlay">
                                        <h2 class="breadcrumb-title">
                                            <?php echo \$package_name; ?>
                                        </h2>
                                        <p class="breadcrumb-path">
                                            <span class="lufera-color">Packages</span> /
                                            <?php echo \$package_name; ?>
                                        </p>
                                    </div>
                                </section>
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
                                \$landingUrl = \$currentBaseUrl . "/pages/" . \$slug . ".php";
                    
                                // FULL PLAN SHORTCODE
                                \$fullPlanShortcode = "Package-Shortcode-" . \$cat_id_sc;
                                \$categoryShortcode = "Category-Shortcode-" . \$cat_id_sc;
                                \$indPlanShortcode = "Package-\$title_sc-Shortcode-" . \$Id;
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
                                                <input type="text" id="landingUrl" class="form-control" value="<?php echo \$landingUrl; ?>" readonly>
                                                <button class="btn copy-btn lufera-bg" onclick="copyText('landingUrl')">Copy</button>
                                            </div>
                                            <hr style="margin:15px 0 10px;">
                                            <!-- SHORTCODES TITLE -->
                                            <h5 style="font-size:18px !important">Shortcodes</h5>
                                            
                                            <h6 class="fs-16">Category Shortcode</h6>
                                            <div class="input-group mb-3">
                                                <input type="text" id="catShortcode" class="form-control" value="<?php echo \$categoryShortcode; ?>" readonly>
                                                <button class="btn copy-btn lufera-bg" onclick="copyText('catShortcode')">Copy</button>
                                            </div>
                                            
                                            <h6 class="fs-16">Full Plan Shortcode</h6>
                                            <div class="input-group mb-3">
                                                <input type="text" id="fullPlan" class="form-control" value="<?php echo \$fullPlanShortcode; ?>" readonly>
                                                <button class="btn copy-btn lufera-bg" onclick="copyText('fullPlan')">Copy</button>
                                            </div>
                
                                            <!-- FULL PLAN -->
                                            <h6 class="fs-16">Individual Plan Shortcode</h6>
                                            <?php echo \$title_sc ?>
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
                    
                                <!-- Package Image -->       
                                <div class="card image-banner">
                                    <img src="./uploads/products/<?php echo \$package_img; ?>" alt="Package Image" class="feature-img" style="border-radius:8px">
                                </div>
                                <div class="package-wrapper position-relative">
                                    <img src="../uploads/products/<?php echo \$package_img; ?>" alt="Package Image" class="feature-img">
                                    <h2 class="package-title">
                                        <?php echo \$package_name; ?>
                                    </h2>
                                </div>
                                
                                <!-- Description Section -->
                                <div class="card mt-20">
                                    <h6 class="sec-heading">Description</h6>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <p><?php echo \$row['description']; ?></p>
                                        </div>
                                    </div>
                                </div> 
                                
                                <!--Features Section -->
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
                                <div class="card mt-20">
                                    <h6 class="sec-heading">Features</h6>
                                    <div class="features-row">
                                        <!-- INCLUSIONS -->
                                        <div class="features-col inclusions">
                                            <ul class="custom-list">
                                                <?php if (!empty(\$included)): ?>
                                                    <?php foreach (\$included as \$inc): ?>
                                                        <li><?php echo htmlspecialchars(\$inc); ?></li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li>No inclusions available</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <!-- EXCLUSIONS -->
                                        <div class="features-col exclusions">
                                            <ul class="custom-list">
                                                <?php if (!empty(\$excluded)): ?>
                                                    <?php foreach (\$excluded as \$exc): ?>
                                                        <li><?php echo htmlspecialchars(\$exc); ?></li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li>No exclusions available</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div> 
                                </div>
                                
                                <!--Price Section -->
                                <?php

                                \$duration_sql = "
                                    SELECT d.*, 
                                        p.title, 
                                        p.subtitle, 
                                        p.description, 
                                        p.package_name,
                                        p.is_login,
                                        p.is_active AS pkg_active
                                    FROM durations d
                                    INNER JOIN package p ON d.package_id = p.id
                                    WHERE d.package_id = \$Id
                                    ORDER BY d.id ASC
                                ";
                                
                                \$dur_result = \$conn->query(\$duration_sql);
                                
                                // Group packages by duration
                                \$packagesByDuration = [];
                                
                                if (\$dur_result && \$dur_result->num_rows > 0) {
                                
                                    while (\$package = \$dur_result->fetch_assoc()) {
                                        \$packagesByDuration[\$package['duration']][] = \$package;
                                    }
                                }
                                \$sql_login = "select * from package where id = \$Id";
                                            \$result_login = \$conn->query(\$sql_login);
                                            \$row_login = \$result_login->fetch_assoc();
                                            \$isLoginRequired = (\$row_login['is_login'] == 1);
                                \$symbol = "\$";
                                ?>
                                
                                <div class="card">                                
                                    <h4 class="sec-heading">Packages Pricing Table</h4>                                
                                    <div class="card-body">                               
                                        <?php if (!empty(\$packagesByDuration)): ?>                               
                                            <?php if (\$isLoginRequired && !\$loggedInUserId): ?>                               
                                                <div class="col-12 text-center">
                                                    <p class="text-center" style="font-size:16px; font-weight:600; margin-top:10px;">
                                                        <a href="#" onclick="openLoginPopup()" class="btn mt-2">
                                                            🔒 Sign-In to See the Packages
                                                        </a>
                                                    </p>
                                                </div>                               
                                            <?php elseif (!\$isLoginRequired && !\$loggedInUserId): ?>                               
                                                <div class="col-12 text-center">
                                                    <p class="text-center" style="font-size:16px; font-weight:600; margin-top:10px;">
                                                        <a href="#" onclick="openLoginPopup()" class="btn mt-2">
                                                            🔒 Sign-In to See the Packages
                                                        </a>
                                                    </p>
                                                </div>                               
                                            <?php else: ?>                               
                                                <div class="row justify-content-center">                               
                                                    <div class="col-xxl-10">                               
                                                        <!-- TABS -->
                                                        <ul class="nav nav-pills button-tab mt-32 mb-32 justify-content-center" id="durationTabs" role="tablist">
                                                            <?php \$firstTab = true; ?>                                
                                                            <?php foreach (\$packagesByDuration as \$duration => \$packages): ?>                                
                                                                <li class="nav-item" role="presentation">                                
                                                                    <button class="rounded-pill nav-link <?= \$firstTab ? 'active' : '' ?>" id="tab-<?= md5(\$duration) ?>" data-bs-toggle="tab" data-bs-target="#content-<?= md5(\$duration) ?>" type="button" role="tab">                    
                                                                        <?= htmlspecialchars(\$duration) ?>                                
                                                                    </button>                                
                                                                </li>                                
                                                                <?php \$firstTab = false; ?>                                
                                                            <?php endforeach; ?>                                
                                                        </ul>                                
                                                        <!-- TAB CONTENT -->
                                                        <div class="tab-content" id="durationTabsContent">                                
                                                            <?php \$firstContent = true; ?>                                
                                                            <?php foreach (\$packagesByDuration as \$duration => \$packages): ?>                                
                                                                <div class="tab-pane fade <?= \$firstContent ? 'show active' : '' ?>" id="content-<?= md5(\$duration) ?>" role="tabpanel">                                
                                                                    <div class="row gy-4 mt-2">                            
                                                                        <?php foreach (\$packages as \$package): ?>                                
                                                                            <?php \$isActive = (\$package['pkg_active'] == 1); ?>                                
                                                                            <div class="col-xxl-4 col-sm-6">                                
                                                                                <div class="pricing-plan position-relative radius-24 overflow-hidden border">                                
                                                                                    <?php if (!\$isActive): ?>
                                                                                        <p class="text-danger float-end">Inactive</p>
                                                                                    <?php endif; ?>
                                                                                    <a href="">
                                                                                        <h5 class="mb-0 lufera-color" style="font-size:18px !important">
                                                                                            <?= htmlspecialchars(\$package['title']) ?>
                                                                                        </h5>
                                                                                    </a>
                                                                                    <p class="text-secondary-light mb-28">
                                                                                        <?= htmlspecialchars(\$package['subtitle']) ?>
                                                                                    </p>                                
                                                                                    <h4 class="mb-24 sec-heading">                                
                                                                                        <?php if (!empty(\$package['preview_price'])): ?>                                
                                                                                            <p class="text-sm text-muted mt-0 mb-10 text-decoration-line-through">                                
                                                                                                <?= \$symbol ?>
                                                                                                <?= number_format(\$package['preview_price']) ?>                                
                                                                                            </p>                                
                                                                                        <?php endif; ?>                                
                                                                                        <?= \$symbol ?>
                                                                                        <?= number_format(\$package['price']) ?>                                
                                                                                        <span class="fw-medium text-md text-secondary-light">
                                                                                            / <?= htmlspecialchars(\$package['duration']) ?>
                                                                                        </span>                                
                                                                                    </h4>                                
                                                                                    <p><?= htmlspecialchars(\$package['description']) ?></p>                                
                                                                                    <ul>
                                                                                        <?php                                
                                                                                        \$feature_sql = "
                                                                                            SELECT feature, feature_type
                                                                                            FROM features
                                                                                            WHERE cat_type = 1
                                                                                            AND package_id = ".\$package['package_id'];                                
                                                                                        \$feature_result = \$conn->query(\$feature_sql);                                
                                                                                        while (\$feat = \$feature_result->fetch_assoc()):                                
                                                                                            \$isInclude = (\$feat['feature_type'] == 'inclusive');                                
                                                                                        ?>                                
                                                                                            <li class="d-flex align-items-center gap-16 mb-16">                                
                                                                                                <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">                                
                                                                                                    <i class="text-sm fa <?= \$isInclude ? 'fa-check' : 'fa-check' ?> text-white"></i>                                
                                                                                                </span>                                
                                                                                                <?= htmlspecialchars(\$feat['feature']) ?>                                
                                                                                            </li>                                
                                                                                        <?php endwhile; ?>                                
                                                                                    </ul>                                
                                                                                    <form action="../cart.php" method="POST">                                
                                                                                        <input type="hidden" name="type" value="package">
                                                                                        <input type="hidden" name="id" value="<?= \$package['package_id'] ?>">
                                                                                        <input type="hidden" name="price" value="<?= \$package['price'] ?>">
                                                                                        <input type="hidden" name="duration" value="<?= \$package['duration'] ?>">
                                                                                        <input type="hidden" name="title" value="<?= htmlspecialchars(\$package['title']) ?>">                                
                                                                                        <button type="submit" class="lufera-bg text-white btn btn-sm w-100 mt-28" <?= !\$isActive ? 'disabled' : '' ?>>
                                                                                            Get Started
                                                                                        </button>        
                                                                                    </form>                                
                                                                                </div>                                
                                                                            </div>                                
                                                                        <?php endforeach; ?>                                
                                                                    </div>                                
                                                                </div>                                
                                                                <?php \$firstContent = false; ?>                                
                                                            <?php endforeach; ?>                                
                                                        </div>                                
                                                    </div>                                
                                                </div>                                
                                            <?php endif; ?>                                
                                        <?php else: ?>                                
                                            <div class="col-12 text-center">
                                                <p>No pricing available</p>
                                            </div>                                
                                        <?php endif; ?>                                
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
                                            "../sign-in.php?redirect=<?php echo urlencode(\$_SERVER['REQUEST_URI']); ?>";
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
                    
                                <section class="card mt-20 contact">
                                    <h6 class="sec-heading">Need Help?</h6>
                                    <button type="button" class="btn btn-default lufera-bg mt-10" onclick="openContactPopup()" style="width:120px">Contact Us</button>   
                                    
                                    <!-- ================= LANDING CONTACT POPUP ================= -->
                                    <div id="landingContactModal" class="landing-contact-modal">            
                                        <div class="landing-contact-modal-content">            
                                            <span class="landing-contact-close" onclick="closeContactPopup()">&times;</span>            
                                            <div class="landing-contact-container">        
                                                <!-- LEFT SIDE -->
                                                <div class="landing-contact-left">            
                                                    <h4 class="sec-heading">CALL US</h4>
                                                    <p><?php echo htmlspecialchars(\$phone ?? 'N/A'); ?></p>
            
                                                    <h4 class="sec-heading">LOCATION</h4>
                                                    <p><?php echo htmlspecialchars(\$address ?? 'N/A'); ?></p>
            
                                                    <h4 class="sec-heading">BUSINESS HOURS</h4>
                                                    <p>Mon - Fri: 10am - 6pm</p>            
                                                </div>
            
                                                <!-- RIGHT SIDE -->
                                                <div class="landing-contact-right">            
                                                    <h3 style="font-size: 22px !important">CONTACT US</h3>            
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
                                </section>
                                
                                <!-- ===== FOOTER ===== -->
                                    <footer class="d-footer mt-20" style="padding:15px 10px; border-top:1px solid #eee;">
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
                            <?php include 'scripts.php'; ?>
                        LANDING;
                        \$landingContent = str_replace('__PACKAGE_ID__', \$package_id, \$landingContent);
                        // Pages version (no change needed)
                        
                        \$rootContent = \$landingContent;


                        \$rootContent = preg_replace(
                            "/session_start\(\);.*?include 'head\.php';/s",
                            "include './partials/layouts/layoutTop.php';",
                            \$rootContent
                        );

                        \$rootContent = preg_replace(
                            "/<\?php\s+include\s+'scripts\.php';\s*\?>/",
                            "<?php include './partials/layouts/layoutBottom.php'; ?>",
                            \$rootContent
                        );

                        \$rootContent = str_replace(
                            "../uploads/products/",
                            "./uploads/products/",
                            \$rootContent
                        );
                        
                        \$rootContent = str_replace(
                            '<div class="content-wrapper" style="margin: 0px 15% 0px 15%;">',
                            '<div class="content-wrapper" style="padding: 10px 15px; margin: 20px 0">',
                            \$rootContent
                        );
                        
                        \$rootContent = str_replace(
                            "../cart.php",
                            "./cart.php",
                            \$rootContent
                        );
                        
                        \$rootContent = str_replace(
                            '<a href="../product-details.php?id=<?php echo \$Id; ?>" class="d-block">',
                            '<a href="product-details.php?id=<?php echo \$Id; ?>" class="d-block">',
                            \$rootContent
                        );
                        
                        \$rootContent = str_replace(
                            "../uploads/products/<?php echo \$package_img; ?>",
                            "./uploads/products/<?php echo \$package_img; ?>",
                            \$rootContent
                        );

                        \$rootContent = preg_replace(
                            '/<section class="top-header">.*?<\/section>/s',
                            '',
                            \$rootContent
                        );  
                        
                        \$rootContent = preg_replace(
                            '/<footer class="d-footer mt-20" style="padding:15px 10px; border-top:1px solid #eee;">.*?<\/footer>/s',
                            '',
                            \$rootContent
                        );  
                        
                        \$rootContent = preg_replace(
                            '/<section class="card mt-20 contact">.*?<\/section>/s',
                            '',
                            \$rootContent
                        );  
                        
                        \$rootContent = preg_replace(
                            '/<div class="package-wrapper position-relative">.*?<\/div>/s',
                            '',
                            \$rootContent
                        );  
                    
                        \$landingContent = preg_replace(
                            '/<section class="breadcrumb-hero position-relative">.*?<\/section>/s',
                            '',
                            \$landingContent
                        );
                        
                        \$landingContent = preg_replace(
                            '/<div class="card image-banner">.*?<\/div>/s',
                            '',
                            \$landingContent
                        );                          
                        
                        \$landingContent = str_replace(
                            '<div class="card-body product pricing">',
                            '<div class="product pricing">',
                            \$landingContent
                        );
                        
                        \$pagesContent = \$landingContent;
                        // Create file only if not exists
                        \$paths = [
                            ['dir' => realpath(__DIR__) . '/pages', 'content' => \$pagesContent],
                            ['dir' => realpath(__DIR__) . '/', 'content' => \$rootContent]
                        ];

                        foreach (\$paths as \$item) {
                            \$dir = \$item['dir'];
                            \$content = \$item['content'];

                            if (!is_dir(\$dir)) {
                                mkdir(\$dir, 0777, true);
                            }

                            \$filePath = \$dir . '/' . \$landingFileName;

                            if (!file_exists(\$filePath)) {
                                file_put_contents(\$filePath, \$content); // ✅ correct
                            }
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
                                    \$unit = \$_POST['duration_units'][\$index] ?? '';
                                    \$price = \$_POST['prices'][\$index] ?? '';
                                    \$pre_prices = \$_POST['pre_prices'][\$index] ?? '';
                                    
                                    if (!empty(\$value) && !empty(\$unit) && !empty(\$price)) {
                                        // Combine value + unit
                                        \$duration_text = \$value . ' ' . \$unit;
                                        \$durationStmt->bind_param("isdsd", \$package_id, \$duration_text, \$price, \$created_at, \$pre_prices);
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
                                        <div class="mb-2">
                                            <label class="form-label">Package image <span class="text-danger-600">*</span></label>
                                            <div class="has-validation">
                                                <input type="file" id="file-input" accept="image/*" name="package_image" required="">
                                                <label class="image-upload d-flex mw-100" for="file-input">
                                                <span>Click or Drag Image Here</span>
                                                <img id="preview" alt="Preview Image">
                                                </label>
                                                <div class="invalid-feedback">
                                                    Please upload a Package image.
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
                                                <label class="form-check-label ms-2 mb-0" for="isLogin">Require login to purchase</label>       
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <button type="submit" name="save_package" class="btn lufera-bg text-white text-md px-56 py-12 radius-8">
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
                
                const fileInput = document.getElementById('file-input');
                const preview = document.getElementById('preview');
                const uploadLabel = document.querySelector('.image-upload span');

                fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                    preview.style.display = 'block';
                    preview.setAttribute('src', e.target.result);
                    uploadLabel.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                    fileInput.setCustomValidity(''); // Clear custom validation
                } else {
                    preview.style.display = 'none';
                    uploadLabel.style.display = 'block';
                    fileInput.setCustomValidity('Please upload a product image'); // Set validation message
                }
                });

                // Add custom validation on submit
                document.querySelector('form').addEventListener('submit', function(e) {
                if (!fileInput.value) {
                    fileInput.setCustomValidity('Please upload a product image');
                } else {
                    fileInput.setCustomValidity('');
                }
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
            <?php if ($row['role'] == "1" || $row['role'] == "2") { ?>
            <li>
                <a href="form_dashboard.php">
                <iconify-icon icon="solar:clipboard-text-outline" class="menu-icon"></iconify-icon>
                    <span>Form Builder</span>
                </a>

                <a href="dashboard-sheets.php">
                    <iconify-icon icon="tabler:file-spreadsheet" class="menu-icon"></iconify-icon>
                    <span>Sheets</span>
                </a>
            </li>
            <?php } ?>
            </ul>
            <ul class="sidebar-menu bottom-menu" id="sidebar-menu" style="border-top: 1px solid #eee; ">
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
            <li>
                <a href="users.php">
                    <iconify-icon icon="hugeicons:user-03" class="menu-icon"></iconify-icon>
                    <span>Users</span>
                </a>
            </li>
            <?php } if ($row['role'] == "1") { ?>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
                        <span>Settings</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="add-on-service.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Add on Services</a></li>
                        <li><a href="bank_details.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Bank Details</a></li>
                        <li><a href="view_categories.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Categories</a></li>
                        <li><a href="company.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Company</a></li>
                        <li><a href="credentials.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Credentials</a></li>
                        <li><a href="currencies.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Currencies</a></li>
                        <li><a href="promotion.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Promotion</a></li>
                        <li><a href="add_policy.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Privacy policy</a></li>
                        <li><a href="view_packages.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Packages</a></li>
                        <li><a href="view_products.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Products</a></li>
                        <li><a href="role-access.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Roles</a></li>
                        <li><a href="taxes.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Taxes</a></li>
                        <li><a href="add_terms_conditions.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Terms and Conditions</a></li>
                    </ul>

                </li>
                <li>
                <a href="backup.php">
                    <iconify-icon icon="tabler:download" class="menu-icon"></iconify-icon>
                    <span>Backup</span>
                </a>
            </li>
            <?php } ?>
            <li>
                <a href="activity_log.php">
                    <iconify-icon icon="tabler:activity" class="menu-icon"></iconify-icon>
                    <span>Activity Log</span>
                </a>
            </li>
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