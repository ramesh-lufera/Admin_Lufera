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

                        // LANDING content
                        \$landingContent = <<<'LANDING'
                        
                        <?php 
                        session_start();
                        include '../../partials/connection.php';
                        include '../head.php'; 
                        ?>
                        <!DOCTYPE html>
                        <html>
                        <head>
                        <meta charset="UTF-8">
                        <title>Product View</title>
                        <?php
                        \$sql = "SELECT * FROM company LIMIT 1";
                        \$result = \$conn->query(\$sql);
                        if (\$result && \$result->num_rows > 0) {
                            \$row_com = \$result->fetch_assoc();
                            \$company_name = \$row_com['full_name'];
                            \$logo = \$row_com['logo'];
                            \$email = \$row_com['email'];
                            \$phone = \$row_com['phone_no'];
                            \$address = \$row_com['address'];

                        }    
                        \$Id = __PACKAGE_ID__;
                        \$sql = "select * from package where id = \$Id";
                        \$result = \$conn ->query(\$sql);
                        \$row = \$result ->fetch_assoc();

                        // Decode image_data JSON
                        \$imageData = json_decode(\$row['image_data'], true);

                        \$breadcrumbImage = \$imageData['breadcrumb_image'] ?? '';
                        \$previewImages   = \$imageData['preview_images'] ?? [];

                        \$duration_sql = "SELECT * FROM durations WHERE package_id = \$Id ORDER BY id ASC LIMIT 1";
                        \$duration_result = \$conn->query(\$duration_sql);
                        \$duration = \$duration_result->fetch_assoc();

                        \$package_img = \$row['package_img'];
                        \$package_name = \$row['package_name'];
                        \$cat_id_sc = \$row['cat_id'];
                        \$title_sc = \$row['title'];
                        \$gst_id = \$row['gst_id'];
                        
                        // Get active symbol
                        \$result2 = \$conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
                        \$symbol = "$"; // default
                        if (\$row1 = \$result2->fetch_assoc()) {
                            \$symbol = \$row1['symbol'];
                        }
                        ?>
                        <style>
                        .feature-img {
                            width: 100%;
                            height: auto;
                            object-fit: cover;
                            border-radius:8px;
                            width: 512px;
                            height: 593px;
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
                            background-image: url('./uploads/products/<?php echo \$breadcrumbImage; ?>'); 
                            background-size: cover; 
                            background-position: center; 
                            height: 300px;
                        }
                        </style>
                        </head>
                        <body>
                            <div class="container-fluid">
                                <section class="breadcrumb-hero position-relative w-100 mt-20 mx-auto d-flex align-items-center m-20">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-xl-12 col-lg-12">
                                                <div class="breadcrumb-content text-center">
                                                    <h2 class="text-capitalize"><?php echo \$package_name; ?></h2>
                                                    <nav aria-label="breadcrumb">
                                                        <ol class="breadcrumb justify-content-center">
                                                            <li class="breadcrumb-item"><a href="admin-dashboard.php">Home</a></li>
                                                            <li class="breadcrumb-item active" aria-current="page"><?php echo \$package_name; ?></li>
                                                        </ol>                   
                                                    </nav>
                                                </div>
                                                <div style="position:absolute; background:#fec700; top:50%; right:20px; transform:translateY(-50%); display:flex; gap:10px;">
                                                    <button class="btn manage-top-btn" data-bs-toggle="modal" data-bs-target="#apiModal">API</button>
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
                            <div class="m-40">
                                <div class="container">
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-6">
                                            <div class="row">
                                                <div class="col-lg-3 text-end">
                                                    <ul>
                                                        <?php if (!empty(\$previewImages)): ?>
                                                            <?php foreach(\$previewImages as \$img): ?>
                                                                <li>
                                                                    <img src="./uploads/products/<?php echo \$img; ?>" class="preview-img" alt="Preview Image">
                                                                </li>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <li>
                                                                <img src="./uploads/products/<?php echo \$package_img; ?>" class="preview-img" alt="Package Image">
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                                <div class="col-lg-9">
                                                    <img src="./uploads/products/<?php echo \$package_img; ?>" alt="Package Image" class="feature-img">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-6">
                                            <div class="ms-20">
                                                <h2 class="mb-2 text-capitalize">
                                                    <?php echo \$package_name; ?>
                                                </h2>
                                                <p class="price">
                                                    <span class="amount fs-2 fw-semibold me-6">
                                                        <?= \$symbol ?><?= \$duration['price'] ?>
                                                    </span>
                                                    <?php if (!empty(\$duration['preview_price'])): ?>
                                                        <span class="text-decoration-line-through fs-2 fw-semibold">
                                                            <?= \$symbol ?><?= \$duration['preview_price'] ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <!-- <span class="package-period">
                                                        /<?= htmlspecialchars(\$duration['duration']) ?>
                                                    </span> -->
                                                </p>
                                                <p>
                                                    <?php echo \$row['short_description']; ?>
                                                </p>

                                                <form action="cart.php" method="POST">                                
                                                    <input type="hidden" name="type" value="package">
                                                    <input type="hidden" name="id" value="<?= \$Id ?>">
                                                    <input type="hidden" name="price" value="<?= \$duration['price'] ?>">
                                                    <input type="hidden" name="duration" value="<?= \$duration['duration'] ?>">
                                                    <input type="hidden" name="title" value="<?= htmlspecialchars(\$title_sc) ?>">  
                                                    <input type="hidden" name="gst_id" value="<?= htmlspecialchars(\$gst_id) ?>">                               
                                                    <button type="submit" class="btn btn-dark w-50 p-12" style="border-radius: 50px;"> 
                                                        Shop Now
                                                    </button>        
                                                </form>
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
                                                        <h6>Exclude</h6>
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
                            </div>
                            <?php include '../scripts.php'; ?>
                        LANDING;
                        \$landingContent = str_replace('__PACKAGE_ID__', \$package_id, \$landingContent);
                        // Pages version (no change needed)
                        
                        \$rootContent = \$landingContent;

                        \$rootContent = str_replace(
                            'session_start();',
                            '',
                            \$rootContent
                        );

                        \$rootContent = str_replace(
                            "include '../../partials/connection.php';",
                            "",
                            \$rootContent
                        );

                        \$rootContent = str_replace(
                            "include '../head.php';",
                            "include './partials/layouts/layoutTop.php';",
                            \$rootContent
                        );

                        \$rootContent = str_replace(
                            "<?php include '../scripts.php'; ?>",
                            "<?php include './partials/layouts/layoutBottom.php'; ?>",
                            \$rootContent
                        );

                        \$rootContent = str_replace(
                            "../../uploads/products/",
                            "./uploads/products/",
                            \$rootContent
                        );
                        
                        \$rootContent = str_replace(
                            '<div class="content-wrapper" style="margin: 0px 15% 0px 15%;">',
                            '<div class="content-wrapper" style="padding: 10px 15px; margin: 20px 0">',
                            \$rootContent
                        );
                        
                        \$rootContent = str_replace(
                            "../../cart.php",
                            "cart.php",
                            \$rootContent
                        );
                        
                        \$rootContent = str_replace(
                            '<a href="../product-details.php?id=<?php echo \$Id; ?>" class="d-block">',
                            '<a href="product-details.php?id=<?php echo \$Id; ?>" class="d-block">',
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
                        
                        \$landingContent = str_replace(
                            './uploads/products',
                            '../../uploads/products/',
                            \$landingContent
                        );
                                
                        \$landingContent = preg_replace(
                            '/<section class="breadcrumb-hero position-relative">.*?<\/section>/s',
                            '',
                            \$landingContent
                        );

                        \$landingContent = preg_replace(
                            '/<button class="btn manage-top-btn" data-bs-toggle="modal" data-bs-target="#apiModal">.*?<\/button>/s',
                            '',
                            \$landingContent
                        );
                         
                        \$landingContent = preg_replace(
                            '/<div class="card image-banner">.*?<\/div>/s',
                            '',
                            \$landingContent
                        );                          
                        
                        \$landingContent = str_replace(
                            '<img src="../uploads/company_logo/<?php echo \$logo; ?>" alt="Company Logo">',
                            '<img src="../../uploads/company_logo/<?php echo \$logo; ?>" alt="Company Logo">',
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
                            ['dir' => realpath(__DIR__) . '/pages/packages', 'content' => \$pagesContent],
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
                        <li><a href="invoice_settings.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Invoice Settings</a></li>
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