<?php include './partials/layouts/layoutTop.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

?>

<?php
    $newUserEmail = '';
    $newBusinessName = '';
    $newPhone = '';
    $newPassword = '';

    $showUserCreated = false;

    if (isset($_POST['create_user'])) {

        $newUserEmail = trim($_POST['new_user_email']);
        
        $newBusinessName = trim($_POST['new_business_name'] ?? '');
        $newPhone = trim($_POST['new_phone'] ?? '');
        $newPassword = trim($_POST['new_user_password'] ?? '');
        
        $businessName = mysqli_real_escape_string($conn, $newBusinessName);
        $phone = mysqli_real_escape_string($conn, $newPhone);
        $password = mysqli_real_escape_string($conn, $newPassword);
        
        $email = mysqli_real_escape_string($conn, $newUserEmail);

        if (
            !empty($email)
            && filter_var($email, FILTER_VALIDATE_EMAIL)
            && !empty($newPassword)
        ) {

            // Check whether email already exists
            $checkUser = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1");

            if (mysqli_num_rows($checkUser) == 0) {

                function generateUserId()
                {
                    $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
                    $numbers = substr(str_shuffle('0123456789'), 0, 3);

                    return $letters . $numbers;
                }

                $userId = generateUserId();

                $username = explode('@', $email)[0];

                $created_at = date("Y-m-d H:i:s");

                $role = 8;

                $method = 1;

                $fname = null;
                $lname = null;
                $address = null;
                $city = null;
                $state = null;
                $country = null;
                $pincode = null;
                $dob = null;
                $photo = null;

                $insert = mysqli_query($conn, "
                INSERT INTO users
                (
                    user_id,
                    username,
                    email,
                    phone,
                    password,
                    first_name,
                    last_name,
                    business_name,
                    address,
                    city,
                    state,
                    country,
                    pincode,
                    dob,
                    created_at,
                    method,
                    role,
                    photo
                )
                VALUES
                (
                    '$userId',
                    '$username',
                    '$email',
                    '$phone',
                    '$password',
                    NULL,
                    NULL,
                    '$businessName',
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    '$created_at',
                    '$method',
                    '$role',
                    NULL
                )
                ");

                if ($insert) {

                    // ================= WELCOME EMAIL =================

                    $mail = new PHPMailer(true);

                    try {

                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $_ENV['EMAIL_USERNAME'];
                        $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->CharSet    = 'UTF-8';
                        $mail->Encoding   = 'base64';

                        $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');

                        $mail->addAddress($email);

                        $mail->isHTML(true);
                        $mail->Subject = "Welcome to Lufera Infotech!";
                        $mail->ContentType = 'text/html; charset=UTF-8';

                        $login_link = 'https://app.luferatech.com/sign-in.php'; // Change if your login URL is different

                        $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <title>Welcome</title>
                        </head>
                        <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">

                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                            <tr>
                                <td align="center">

                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600"
                                        style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">

                                        <tr>
                                            <td style="padding:20px;text-align:center;">
                                                <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '"
                                                    alt="Lufera Infotech Logo"
                                                    style="width:250px;display:block;margin:auto;">
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="border-top:1px solid #eaeaea;"></td>
                                        </tr>

                                        <tr>
                                            <td style="padding:30px 40px;font-size:15px;line-height:1.6;color:#101010;">

                                                <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">
                                                    Welcome!
                                                </h3>

                                                <p>Your account has been successfully created in <b>Lufera Infotech</b>.</p>

                                                <p>
                                                    You can now log in using your email address:
                                                </p>

                                                <p>
                                                    <strong>' . htmlspecialchars($email) . '</strong>
                                                </p>

                                                <div style="margin:30px 0;text-align:center;">

                                                    <a href="' . htmlspecialchars($login_link) . '"
                                                        style="background:#1c8a8a;color:#fff;text-decoration:none;padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">

                                                        Go to Login

                                                    </a>

                                                </div>

                                                <p>
                                                    Thank you for choosing Lufera Infotech.
                                                </p>

                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="border-top:1px solid #eaeaea;"></td>
                                        </tr>

                                        <tr>
                                            <td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                                © 2026 Lufera Infotech. All rights reserved.
                                            </td>
                                        </tr>

                                    </table>

                                </td>
                            </tr>
                        </table>

                        </body>
                        </html>';

                        $mail->send();

                    } catch (Exception $e) {

                        error_log("Welcome email failed: {$mail->ErrorInfo}");

                    }

                    // ================= END WELCOME EMAIL =================

                    $showUserCreated = true;
                }

            } else {

                echo "
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        icon: 'warning',
                        title: 'User Already Exists',
                        text: 'This email is already registered.',
                        confirmButtonText: 'OK'
                    });
                });
                </script>";

            }

        }

    }

    if ($showUserCreated) {
        echo "
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'User Created',
                text: 'User Created',
                confirmButtonText: 'OK'
            });
        });
        </script>";
    }
?>

<div class="container-fluid px-4 py-4">

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="mt-16 mb-8 text-center">
                <h5 class="fw-bold">
                    Buy For Customer
                </h5>
            
                <p class="text-muted mb-0">
                    Create a customer purchase by selecting an existing customer or creating a new one.
                </p>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- Section 1 -->
    <!-- ========================================= -->

    <div class="card shadow-sm m-13 p-13 pt-4 mt-10">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold">
                Customer Selection
            </h6>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <!-- Existing User -->
                <div class="col-lg-6">

                    <label class="form-label small fw-semibold text-dark">
                        Available Users
                    </label>

                    <select class="form-select" name="user_id" id="user_id">
                        <option value="">Select Existing User</option>

                        <?php
                        $usersQuery = mysqli_query($conn, "SELECT
                                                id,
                                                user_id,
                                                first_name,
                                                last_name,
                                                username,
                                                email,
                                                business_name,
                                                phone,
                                                password
                                            FROM users
                                            WHERE is_deleted = 0
                                            ORDER BY first_name ASC, username ASC");

                        if ($usersQuery && mysqli_num_rows($usersQuery) > 0) {
                            while ($user = mysqli_fetch_assoc($usersQuery)) {

                                $displayName = trim($user['first_name'] . ' ' . $user['last_name']);

                                if ($displayName == '') {
                                    $displayName = $user['username'];
                                }

                                echo '<option
                    value="' . $user['id'] . '"
                    data-business-name="' . htmlspecialchars($user['business_name']) . '"
                    data-phone="' . htmlspecialchars($user['phone']) . '"
                    data-password="' . htmlspecialchars($user['password']) . '">
                    '
                    . htmlspecialchars($displayName)
                    . ' (' . htmlspecialchars($user['email']) . ')
                </option>';
                            }
                        }
                        ?>
                    </select>

                    <div class="form-text">
                        Select an existing customer from your dashboard.
                    </div>

                </div>

                <!-- Create User -->
                <div class="col-lg-6">

                    <label class="form-label small fw-semibold text-dark">
                        Create User
                    </label>

                    <form method="POST">

                        <div class="d-flex">

                            <div class="flex-grow-1 me-2">

                                <input
                                    type="email"
                                    name="new_user_email"
                                    class="form-control"
                                    placeholder="Enter customer email address"
                                    value="<?php echo htmlspecialchars($newUserEmail); ?>"
                                    required>

                            </div>

                            <button
                                class="btn lufera-bg lufera-text"
                                type="submit"
                                name="create_user">

                                Create User

                            </button>

                        </div>

                        <div class="form-text">
                            If the customer doesn't exist, create a new account using their email address.
                        </div>

                </div>

                <!-- Business Name -->
                <div class="col-lg-4">

                    <label class="form-label small fw-semibold text-dark">
                        Business Name
                    </label>

                    <input
                    type="text"
                    class="form-control"
                    name="new_business_name"
                    id="business_name"
                    placeholder="Enter Business Name"
                    value="<?php echo htmlspecialchars($newBusinessName); ?>">

                </div>

                <!-- Phone -->
                <div class="col-lg-4">

                    <label class="form-label small fw-semibold text-dark">
                        Phone
                    </label>

                <input
                    type="text"
                    class="form-control"
                    name="new_phone"
                    id="phone"
                    placeholder="Enter Phone Number"
                    value="<?php echo htmlspecialchars($newPhone); ?>">

                </div>

                <!-- Password -->
                <div class="col-lg-4">

                    <label class="form-label small fw-semibold text-dark">
                        Password
                    </label>

                    <input
                    type="text"
                    class="form-control"
                    name="new_user_password"
                    id="password"
                    placeholder="Enter Password"
                    value="<?php echo htmlspecialchars($newPassword); ?>"
                    required>
                
                    </form>

                </div>

            </div>

        </div>
    </div>

    <!-- ========================================= -->
    <!-- Section 2 -->
    <!-- ========================================= -->

    <div class="card shadow-sm mt-20 m-13 p-13 pt-4">

        <div class="card-header bg-white py-3">

            <h6 class="mb-0 fw-semibold">
                Product Selection
            </h6>

        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-xl-3 col-lg-6 col-md-6">

                    <label class="form-label small fw-semibold text-dark">
                        All Categories
                    </label>

                    <select class="form-select" name="category_id" id="category_id">

                        <option value="">Select Category</option>
                    
                        <?php
                        $categoryQuery = mysqli_query($conn, "SELECT cat_id, cat_name, cat_type
                                                              FROM categories
                                                              ORDER BY cat_id ASC");
                    
                        while ($category = mysqli_fetch_assoc($categoryQuery)) {
                        ?>
                    
                            <option
                                value="<?php echo $category['cat_id']; ?>"
                                data-type="<?php echo strtolower($category['cat_type']); ?>">
                    
                                <?php echo htmlspecialchars($category['cat_name']); ?>
                    
                            </option>
                    
                        <?php } ?>
                    
                    </select>

                </div>

                <div class="col-xl-3 col-lg-6 col-md-6" id="packageSection">

                    <label class="form-label small fw-semibold text-dark">
                        All Packages
                    </label>

                    <select class="form-select" name="package_id" id="package_id">

                        <option value="">Select Package</option>
                    
                        <?php
                        $packageQuery = mysqli_query($conn, "
                        SELECT
                            p.id,
                            p.cat_id,
                            p.package_name,
                            p.title,
                            p.subtitle,
                            p.created_at,
                            p.gst_id,
                            p.addon_service,

                            d.price,
                            d.duration

                        FROM package p

                        LEFT JOIN durations d
                            ON d.package_id = p.id

                        WHERE p.is_active = 1
                        AND p.is_deleted = 0

                        ORDER BY p.id ASC
                        ");
                    
                        while ($package = mysqli_fetch_assoc($packageQuery)) {
                        ?>
                           <option

                            value="<?php echo $package['id']; ?>"

                            data-id="<?php echo $package['id']; ?>"
                            
                            data-category-id="<?php echo $package['cat_id']; ?>"

                            data-type="package"

                            data-plan-name="<?php echo htmlspecialchars($package['package_name']); ?>"

                            data-title="<?php echo htmlspecialchars($package['title']); ?>"

                            data-subtitle="<?php echo htmlspecialchars($package['subtitle']); ?>"

                            data-price="<?php echo $package['price']; ?>"

                            data-duration="<?php echo htmlspecialchars($package['duration']); ?>"

                            data-created-on="<?php echo $package['created_at']; ?>"

                            data-gst-id="<?php echo $package['gst_id']; ?>"

                            data-addon-service="<?php echo htmlspecialchars($package['addon_service']); ?>"

                        >

                            <?php
                        echo htmlspecialchars($package['package_name']);
                        ?>

                        (<?php echo htmlspecialchars($package['duration']); ?>)

                        </option>
                        <?php } ?>
                    
                    </select>

                </div>

                <div class="col-xl-3 col-lg-6 col-md-6" id="productSection">

                    <label class="form-label small fw-semibold text-dark">
                        All Products
                    </label>

                    <select class="form-select" name="product_id" id="product_id">

                        <option value="">Select Product</option>
                    
                        <?php
                        $productQuery = mysqli_query($conn, "
                        SELECT
                            id,
                            name,
                            title,
                            subtitle,
                            price,
                            duration,
                            created_at,
                            gst
                        FROM products
                        WHERE is_active = 1
                        AND is_deleted = 0
                        ORDER BY id ASC
                        ");
                    
                        while ($product = mysqli_fetch_assoc($productQuery)) {
                        ?>
                            <option

                            value="<?php echo $product['id']; ?>"

                            data-id="<?php echo $product['id']; ?>"

                            data-type="product"

                            data-plan-name="<?php echo htmlspecialchars($product['name']); ?>"

                            data-title="<?php echo htmlspecialchars($product['title']); ?>"

                            data-subtitle="<?php echo htmlspecialchars($product['subtitle']); ?>"

                            data-price="<?php echo $product['price']; ?>"

                            data-duration="<?php echo htmlspecialchars($product['duration']); ?>"

                            data-created-on="<?php echo $product['created_at']; ?>"

                            data-gst="<?php echo $product['gst']; ?>"

                        >

                            <?php echo htmlspecialchars($product['name']); ?>

                        </option>
                        <?php } ?>
                    
                    </select>

                </div>

                <div class="col-xl-3 col-lg-6 col-md-6" id="addonSection">

                    <label class="form-label small fw-semibold text-dark">
                        All Add-On Services
                    </label>

                    <select class="form-select" name="addon_service_id" id="addon_service_id">

                        <option value="">Select Add-On Service</option>
                    
                        <?php
                        $addonQuery = mysqli_query($conn, "SELECT id, name
                                                           FROM `add-on-service`
                                                           WHERE is_Active = 1
                                                           ORDER BY name ASC");
                    
                        while ($addon = mysqli_fetch_assoc($addonQuery)) {
                        ?>
                            <option value="<?php echo $addon['id']; ?>">
                                <?php echo htmlspecialchars($addon['name']); ?>
                            </option>
                        <?php } ?>
                    
                    </select>

                </div>

            </div>

        </div>

    </div>

    <!-- ========================================= -->
    <!-- Section 3 -->
    <!-- ========================================= -->

    <div class="card shadow-sm mt-40 m-13 p-20">

        <div class="card-body py-4">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                <div class="mb-3 mb-md-0">

                    <h6 class="mb-1">
                        Continue to Cart
                    </h6>

                    <p class="text-muted mb-0">
                        Proceed to the shopping cart to review and complete the purchase.
                    </p>

                </div>

                <div>

                    <button
                        type="button"
                        id="goToCartBtn"
                        class="btn btn-success btn-lg px-4 lufera-bg lufera-text">

                        <i class="ti ti-shopping-cart me-2"></i>

                        Go to Cart ->

                    </button>

                    <form
                        id="cartForm"
                        method="POST"
                        action="cart.php"
                        style="display:none;">

                        <input type="hidden" name="type" id="cart_type">

                        <input type="hidden" name="id" id="cart_id">

                        <input type="hidden" name="plan_name" id="cart_plan_name">

                        <input type="hidden" name="title" id="cart_title">

                        <input type="hidden" name="subtitle" id="cart_subtitle">

                        <input type="hidden" name="price" id="cart_price">

                        <input type="hidden" name="duration" id="cart_duration">

                        <input type="hidden" name="created_on" id="cart_created_on">

                        <input type="hidden" name="gst" id="cart_gst">

                        <input type="hidden" name="gst_id" id="cart_gst_id">

                        <input type="hidden" name="addon_service" id="cart_addon_service">

                        <input type="hidden" name="addon_package" id="cart_addon_package">

                        <input type="hidden" name="addon_product" id="cart_addon_product">
                        
                        <input
                        type="hidden"
                        name="selected_user_id"
                        id="cart_selected_user_id">

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        const category = document.getElementById("category_id");

        const packageSection = document.getElementById("packageSection");
        const productSection = document.getElementById("productSection");

        category.addEventListener("change", function () {

            let selected = this.options[this.selectedIndex];
            let type = selected.getAttribute("data-type");
            
            const selectedCategoryId = this.value;
            const packageSelect = document.getElementById("package_id");

            // Initially both visible
            if (type === null || type === "") {

                packageSection.style.display = "";
                productSection.style.display = "";

                return;
            }
            
            // ==========================
            // Filter Packages
            // ==========================

            Array.from(packageSelect.options).forEach(function (option, index) {

                // Keep the first "Select Package" option
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                if (selectedCategoryId === "") {
                    option.hidden = false;
                    return;
                }

                option.hidden =
                    option.dataset.categoryId !== selectedCategoryId;
            });

            // Reset package selection
            packageSelect.selectedIndex = 0;

            if (type === "package") {

                packageSection.style.display = "";
                productSection.style.display = "none";

            }
            else if (type === "product") {

                packageSection.style.display = "none";
                productSection.style.display = "";

            }
            else {

                packageSection.style.display = "";
                productSection.style.display = "";

            }

        });
        
        // ==========================
        // ADD THIS NEW CODE HERE
        // ==========================

        const userSelect = document.getElementById("user_id");

        userSelect.addEventListener("change", function () {

            const selectedOption = this.options[this.selectedIndex];

            document.getElementById("business_name").value =
                selectedOption.dataset.businessName || "";

            document.getElementById("phone").value =
                selectedOption.dataset.phone || "";

            document.getElementById("password").value =
        selectedOption.dataset.password
            ? selectedOption.dataset.password
            : "No";

        });
        
        const goToCartBtn = document.getElementById("goToCartBtn");

    goToCartBtn.addEventListener("click", function () {

        const selectedUser =
            document.getElementById("user_id").value;

        const packageSelect = document.getElementById("package_id");
        const productSelect = document.getElementById("product_id");

        const selectedPackage =
            packageSelect.options[packageSelect.selectedIndex];

        const selectedProduct =
            productSelect.options[productSelect.selectedIndex];

        let selected = null;

        if (packageSelect.value !== "") {

            selected = selectedPackage;

        }
        else if (productSelect.value !== "") {

            selected = selectedProduct;

        }

        if (selected == null) {

            Swal.fire({

                icon: "warning",

                title: "Select Package or Product",

                text: "Please select a Package or Product first."

            });

            return;

        }
        
        if (selectedUser === "") {

            Swal.fire({
        
                icon: "warning",
        
                title: "Select Customer",
        
                text: "Please select an Available User first."
        
            });
        
            return;
        
        }

        document.getElementById("cart_type").value =
            selected.dataset.type;

        document.getElementById("cart_id").value =
            selected.dataset.id;

        document.getElementById("cart_plan_name").value =
            selected.dataset.planName;

        document.getElementById("cart_title").value =
            selected.dataset.title;

        document.getElementById("cart_subtitle").value =
            selected.dataset.subtitle;

        document.getElementById("cart_price").value =
            selected.dataset.price;

        document.getElementById("cart_duration").value =
            selected.dataset.duration;

        document.getElementById("cart_created_on").value =
            selected.dataset.createdOn;

        if (selected.dataset.type === "package") {

            document.getElementById("cart_gst_id").value =
                selected.dataset.gstId;

            document.getElementById("cart_gst").value = "";

            const addonSelect =
        document.getElementById("addon_service_id");

    if (addonSelect.value !== "") {

        document.getElementById("cart_addon_service").value =
            addonSelect.value;

    } else {

        document.getElementById("cart_addon_service").value =
            selected.dataset.addonService;
    }

        }

        if (selected.dataset.type === "product") {

        document.getElementById("cart_gst").value =
            selected.dataset.gst;

        document.getElementById("cart_gst_id").value = "";

        const addonSelect =
            document.getElementById("addon_service_id");

        document.getElementById("cart_addon_service").value =
            addonSelect.value;
    }

        document.getElementById("cart_addon_package").value = "";

        document.getElementById("cart_addon_product").value = "";
        
        document.getElementById("cart_selected_user_id").value =
        selectedUser;

        document.getElementById("cartForm").submit();

    });

    });

</script>

<?php include './partials/layouts/layoutBottom.php'; ?>