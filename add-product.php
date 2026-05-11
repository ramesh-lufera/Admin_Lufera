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
?>

<style>
    .card-form {
      background: #fff;
      width: 100%;
      padding: 20px;
      /* border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); */
      text-align: center;
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
    $name = $_POST['name'];
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $short_description = $_POST['short_description'];
    $preview_price = $_POST['preview_price'];
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


    // Image upload
    $product_image = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/products/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);  // create directory if it doesn't exist
        }

        $file_name = time() . '_' . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file type (only images)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $product_image = $file_name;  // Save file name in DB
            } else {
                echo "<script>alert('Failed to upload image.'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Invalid file type. Allowed: JPG, PNG, GIF, WEBP.'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Please upload a product image.'); window.history.back();</script>";
        exit;
    }
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO products 
    (name, title, subtitle, price, description, category, tags, feature_item, product_image, cat_id, duration, template, created_at, is_login, short_description, preview_price) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssdsssssisssisd", $name, $title, $subtitle, $price, $description, $category, $tags, $feature_item, $product_image, $cat_id, $duration, $template,  $created_at, $is_login, $short_description, $preview_price);

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
    session_start();
    $loggedInUserId = isset($_SESSION['user_id']); // adjust based on your login system
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
    }

    .package-wrapper {
        position: relative;
        width: 100%;
        height: 280px;
        margin-top: 20px;
        margin-bottom: 20px;
        overflow: hidden;
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
    </style>
    </head>
    <body>

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
            $cat_id_sc = $row['cat_id'];
        ?>
        
        <div class="content-wrapper" style="margin: 0px 15% 0px 15%;">
            <!-- Header Navbar -->
            <section class="top-header">
                <!-- LEFT: LOGO -->
                <div class="header-left">
                    <img src="../uploads/company_logo/<?php echo $logo; ?>" alt="Company Logo">
                </div>

                <!-- RIGHT: CONTACT -->
                <div class="header-right">
                    <span class="contact-item">📞 <?php echo $phone; ?></span>
                    <span class="contact-item">✉ <?php echo $email; ?></span>
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
                        <?php echo $package_name; ?>
                    </h2>
                    <p class="breadcrumb-path">
                        <a href="">Products</a> /
                        <?php echo $package_name; ?>
                    </p>
                </div>
            </section>
            <!-- ===== API DATA PREPARATION ===== -->
            <?php
            // BASE URL (dynamic)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $basePath = dirname($_SERVER['SCRIPT_NAME']);

            $currentBaseUrl = $protocol . $host . $basePath;

            $slug = strtolower(trim($package_name));        // lowercase + trim
            $slug = preg_replace('/\s+/', '-', $slug);      // replace spaces with hyphens
            $slug = preg_replace('/[^a-z0-9\-]/', '', $slug); // remove special chars

            // FINAL LANDING URL
            $landingUrl = $currentBaseUrl . "pages/" . $slug . ".php";

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

            <!-- Package Image -->       
            <div class="card image-banner">
                <img src="./uploads/products/<?php echo $package_img; ?>" alt="Package Image" class="feature-img" style="border-radius:8px">
            </div>
            <div class="package-wrapper">
                <img src="../uploads/products/<?php echo $package_img; ?>" alt="Package Image" class="feature-img">
                <h2 class="package-title">
                    <?php echo $package_name; ?>
                </h2>
            </div>
            
            <!-- Description Section -->
            <div class="card mt-20">
                <h6 class="sec-heading">Description</h6>
                <div class="row">
                    <div class="col-md-8">
                        <p><?php echo $row['description']; ?></p>
                    </div>
                </div>
            </div> 
            
            <!--Features Section -->
            <div class="card mt-20">
                <h6 class="sec-heading">Features</h6>
                <div class="features-row">
                    <!-- INCLUSIONS -->
                    <div class="features-col inclusions">
                        <ul class="custom-list">
                            <?php if (!empty($included)): ?>
                                <?php foreach ($included as $inc): ?>
                                    <li><?php echo htmlspecialchars($inc); ?></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>No inclusions available</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <!-- EXCLUSIONS -->
                    <div class="features-col exclusions">
                        <ul class="custom-list">
                            <?php if (!empty($excluded)): ?>
                                <?php foreach ($excluded as $exc): ?>
                                    <li><?php echo htmlspecialchars($exc); ?></li>
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
                
                // Currency
                $result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
                $symbol = "₹";
                if ($row_symbol = $result->fetch_assoc()) {
                    $symbol = $row_symbol['symbol'];
                }

                // Login check
                $sql_login = "select * from products where id = $Id";
                $result_login = $conn->query($sql_login);
                $row_login = $result_login->fetch_assoc();
                $isLoginRequired = ($row_login['is_login'] == 1);
                ?>

                <div class="card">
                    <div class="card-body product pricing">
                        <h4 class="mb-20 sec-heading">Products Pricing Table</h4>
    
                        <div class="row gy-4">
                            <?php if ($isLoginRequired && !$loggedInUserId): ?>
    
                                <!-- LOGIN REQUIRED -->
                                <div class="col-12 text-center">
                                    <p class="text-center" style="font-size:16px; font-weight:600; margin-top:10px;">
                                        <a href="#" onclick="openLoginPopup()" class="btn mt-2">
                                            🔒 Sign-In to See the Packages
                                        </a>
                                    </p>
                                </div>
    
                            <?php elseif (!$isLoginRequired && !$loggedInUserId): ?>
    
                                <!-- OPTIONAL LOGIN -->
                                <div class="col-12 text-center">
                                    <p class="text-center" style="font-size:16px; font-weight:600; margin-top:10px;">
                                        <a href="#" onclick="openLoginPopup()" class="btn mt-2">
                                            🔒 Sign-In to See the Packages
                                        </a>
                                    </p>
                                </div>
    
                            <?php else: ?>                                                                                               
                                <div class="col-lg-4 col-md-4 col-sm-6">
                                    <a href="../product-details.php?id=<?php echo $Id; ?>" class="d-block">
                                        <div class="pb-16 hover-scale-img border radius-16 overflow-hidden ">
                                            <div class="max-h-266-px overflow-hidden">
                                                <img src="../uploads/products/<?php echo $package_img; ?>" class="hover-scale-img__img w-100 object-fit-cover ">
                                            </div>
                                            <div class="py-16 px-24">
                                                <h6 class="mb-4" style="font-size:16px !important"><?php echo $title; ?></h6>
                                                <p class="mb-0 text-sm text-secondary-light">
                                                    <b>Price</b> : <?= $symbol ?> <?= number_format($price); ?> 
                                                </p>
                                                <p class="mb-0 text-sm text-secondary-light float-start">
                                                    <b>Validity</b> : <?= htmlspecialchars($duration); ?>                                                           
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
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

            <section class="card mt-20 contact">
                <h6 class="sec-heading">Need Help?</h6>
                <button type="button" class="btn btn-default lufera-bg mt-10" onclick="openContactPopup()" style="width:10%">Contact Us</button>   
                
                <!-- ================= LANDING CONTACT POPUP ================= -->
                        <div id="landingContactModal" class="landing-contact-modal">

                            <div class="landing-contact-modal-content">

                                <span class="landing-contact-close" onclick="closeContactPopup()">&times;</span>

                                <div class="landing-contact-container">

                                    <!-- LEFT SIDE -->
                                    <div class="landing-contact-left">

                                        <h4 class="sec-heading">CALL US</h4>
                                        <p><?php echo htmlspecialchars($phone ?? 'N/A'); ?></p>

                                        <h4 class="sec-heading">LOCATION</h4>
                                        <p><?php echo htmlspecialchars($address ?? 'N/A'); ?></p>

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

    $rootContent = $productLandingContent;


                        $rootContent = preg_replace(
                            "/session_start\(\);.*?include 'head\.php';/s",
                            "include './partials/layouts/layoutTop.php';",
                            $rootContent
                        );

                        $rootContent = preg_replace(
                            "/<\?php\s+include\s+'scripts\.php';\s*\?>/",
                            "<?php include './partials/layouts/layoutBottom.php'; ?>",
                            $rootContent
                        );

                        $rootContent = str_replace(
                            "../uploads/products/",
                            "./uploads/products/",
                            $rootContent
                        );
                        
                        $rootContent = str_replace(
                            '<div class="content-wrapper" style="margin: 0px 15% 0px 15%;">',
                            '<div class="content-wrapper" style="padding: 10px 15px; margin: 20px 0">',
                            $rootContent
                        );
                        
                        $rootContent = str_replace(
                            "../cart.php",
                            "./cart.php",
                            $rootContent
                        );
                        
                        $rootContent = str_replace(
                            '<a href="../product-details.php?id=<?php echo $Id; ?>" class="d-block">',
                            '<a href="product-details.php?id=<?php echo $Id; ?>" class="d-block">',
                            $rootContent
                        );
                        
                        $rootContent = str_replace(
                            "../uploads/products/<?php echo $package_img; ?>",
                            "./uploads/products/<?php echo $package_img; ?>",
                            $rootContent
                        );

                        $rootContent = preg_replace(
                            '/<section class="top-header">.*?<\/section>/s',
                            '',
                            $rootContent
                        );  
                        
                        $rootContent = preg_replace(
                            '/<footer class="d-footer mt-20" style="padding:15px 10px; border-top:1px solid #eee;">.*?<\/footer>/s',
                            '',
                            $rootContent
                        );  
                        
                        $rootContent = preg_replace(
                            '/<section class="card mt-20 contact">.*?<\/section>/s',
                            '',
                            $rootContent
                        );  
                        
                        $rootContent = preg_replace(
                            '/<div class="package-wrapper">.*?<\/div>/s',
                            '',
                            $rootContent
                        );  
                    
                        $productLandingContent = preg_replace(
                            '/<section class="breadcrumb-hero position-relative">.*?<\/section>/s',
                            '',
                            $productLandingContent
                        );
                        
                        $productLandingContent = str_replace(
                            '<div class="card-body product pricing">',
                            '<div class="product pricing">',
                            $productLandingContent
                        );
                        
                        $productLandingContent = preg_replace(
                            '/<div class="card image-banner">.*?<\/div>/s',
                            '',
                            $productLandingContent
                        );

    // =====================================================
    // CREATE BOTH FILES
    // =====================================================

    $paths = [
        [
            'dir' => $_SERVER['DOCUMENT_ROOT'] . '/pages',
            'content' => $productLandingContent
        ],
        [
            'dir' => $_SERVER['DOCUMENT_ROOT'] . '/',
            'content' => $rootContent
        ]
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
                        <div class="form-group text-start mb-2">
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
                                </textarea>
                                <div class="invalid-feedback">
                                  Description is required
                                </div>
                            </div>
                        </div>
                       
                        <div class="form-group mb-2">
                            <label class="form-label">Short Description <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="short_description" name="short_description" required>
                                </textarea>
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
                              <input type="number" name="preview_price" class="form-control radius-8" required onkeydown="return event.key !== 'e'" maxlength="10">
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
