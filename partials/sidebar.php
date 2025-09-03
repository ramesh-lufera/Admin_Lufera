<?php
    $userid = $_SESSION['user_id'];

    $sql = "SELECT role FROM users WHERE id = $userid";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

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
                        </style>

                        <?php include './partials/layouts/layoutTop.php' ?>
                        <?php
                        ini_set('display_errors', 1);
                        ini_set('display_startup_errors', 1);
                        error_reporting(E_ALL);

                        if (\$_SERVER['REQUEST_METHOD'] == 'POST') {
                            \$package_name = \$_POST['package_name'];                           
                            \$title = \$_POST['title'];
                            \$subtitle = \$_POST['subtitle'];
                            \$price = \$_POST['price'];
                            \$description = \$_POST['description'];
                            \$features = \$_POST['features'];
                            \$created_at = date("Y-m-d H:i:s");

                            \$duration_value = isset(\$_POST['duration_value']) ? intval(\$_POST['duration_value']) : 0;
                            \$duration_unit = isset(\$_POST['duration_unit']) ? \$_POST['duration_unit'] : '';

                            if (\$duration_value > 0 && in_array(\$duration_unit, ['days', 'months', 'years'])) {
                                \$duration = \$duration_value . ' ' . \$duration_unit;
                            } else {
                                echo "<script>alert('Invalid duration input.'); window.history.back();</script>";
                                exit;
                            }

                            \$cat_id = $product_category;
                            \$template = "$template";

                            \$stmt = \$conn->prepare("INSERT INTO package (package_name, title, subtitle, price, description, duration, cat_id, created_at, template) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            \$stmt->bind_param("ssssssiss", \$package_name, \$title, \$subtitle, \$price, \$description, \$duration, \$cat_id, \$created_at, \$template);

                            if (\$stmt->execute()) {
                                \$package_id = \$conn->insert_id;
                                \$stmt->close();

                                if (!empty(\$features) && is_array(\$features)) {
                                    \$featureStmt = \$conn->prepare("INSERT INTO features (package_id, feature) VALUES (?, ?)");
                                    foreach (\$features as \$feature) {
                                        \$cleaned_feature = trim(\$feature);
                                        if (\$cleaned_feature !== "") {
                                            \$featureStmt->bind_param("is", \$package_id, \$cleaned_feature);
                                            \$featureStmt->execute();
                                        }
                                    }
                                    \$featureStmt->close();
                                }

                                // create details file if missing
                                
                                \$slug = isset(\$_GET['slug']) ? \$_GET['slug'] : '';
                                \$det_file_path = \$slug . "-det.php";

                                if (!file_exists(\$det_file_path)) {
                            \$base_php = <<<'CODE'
                        <?php 
                        include './partials/connection.php';
                        ini_set('display_errors', 1);
                        ini_set('display_startup_errors', 1);
                        error_reporting(E_ALL);

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
                            <?php include "./category_details/{\$template_product}-details.php"; ?>
                        <?php endif; ?>
                        CODE;

                            file_put_contents(\$det_file_path, \$base_php);
                        }
                                echo "<script>
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Package and features saved successfully.',
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
                                        <form method="POST" class="row gy-3 needs-validation" novalidate autocomplete="off">
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
                                                <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Price <span class="text-danger-600">*</span></label>
                                                <div class="has-validation">
                                                    
                                                    <input type="number" class="form-control radius-8" name="price" required maxlength="20" onkeydown="return event.key !== 'e'">
                                                    <div class="invalid-feedback">
                                                        Price is required
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
                                                <label class="form-label">Duration <span class="text-danger-600">*</span></label>
                                                <div class="d-flex gap-2">
                                                    <input type="number" name="duration_value" class="form-control radius-8" required min="1" style="width: 60%;">
                                                    <select name="duration_unit" class="form-control radius-8" required style="width: 40%;">
                                                        <option value="days">Days</option>
                                                        <option value="months">Months</option>
                                                        <option value="years">Years</option>
                                                        <option value="hours">Hours</option>
                                                    </select>
                                                </div>
                                                <div class="invalid-feedback">
                                                    Duration is required
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">Features <span class="text-danger-600">*</span></label>
                                                <div id="feature-wrapper">
                                                    <div class="feature-group mb-2 d-flex gap-2">
                                                        <input type="text" name="features[]" class="form-control radius-8" required placeholder="Enter a feature" />
                                                        <button type="button" class="btn btn-sm btn-success add-feature">+</button>
                                                    </div>
                                                </div>
                                                <div class="invalid-feedback">
                                                    At least one feature is required.
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center justify-content-center gap-3">
                                                <button type="button" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-56 py-11 radius-8">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="btn lufera-bg text-white text-md px-56 py-12 radius-8">
                                                    Save
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
                            const featureWrapper = document.getElementById("feature-wrapper");

                            featureWrapper.addEventListener("click", function (e) {
                                if (e.target && e.target.classList.contains("add-feature")) {
                                    e.preventDefault();

                                    const newGroup = document.createElement("div");
                                    newGroup.className = "feature-group mb-2 d-flex gap-2";
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
            <img src="assets/images/logo_lufera.png" alt="site logo" class="light-logo">
            <img src="assets/images/Logo_dark.png" alt="site logo" class="dark-logo">
            <img src="assets/images/Image.jfif" alt="site logo" class="logo-icon">
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
            <a href="user-dashboard.php">
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
                            <!-- <div class="category-actions">
                                <button type="button" onclick="openEditModal('<?= $cat['cat_id'] ?>', '<?= htmlspecialchars($cat['cat_name']) ?>', '<?= htmlspecialchars($cat['cat_url']) ?>', '<?= htmlspecialchars($cat['cat_module']) ?>')" class="icon-btn">    
                                    <iconify-icon icon="mdi:pencil-outline"></iconify-icon>
                                </button>
                                <form method="post" class="delete-form">
                                    <input type="hidden" name="delete_cat_id" value="<?= $cat['cat_id'] ?>">
                                    <button type="button" class="icon-btn delete-btn" onclick="confirmDelete(this)">
                                        <iconify-icon icon="mdi:delete-outline"></iconify-icon>
                                    </button>
                                </form>
                            </div> -->
                        </div>
                    </li>
                <?php } ?>

                    <!-- <li class="add-category-menu">
                        <a href="javascript:void(0);" onclick="openCategoryModal();">
                            <span>+ Add New Category</span>
                        </a>
                        <a href="javascript:void(0);" onclick="openProductModal()">
                            <span>+ Add New Product</span>
                        </a>
                    </li> -->
            <?php } ?>
            </ul>
            <ul class="sidebar-menu bottom-menu" id="sidebar-menu" style="border-top: 1px solid #eee; ">
            <li>
                <a href="orders.php">
                    <iconify-icon icon="hugeicons:invoice-03" class="menu-icon"></iconify-icon>
                    <span>Orders</span>
                </a>
            </li>
            <?php if ($row['role'] == "1" || $row['role'] == "2") { ?>
            <li>
                <a href="users.php">
                    <iconify-icon icon="hugeicons:user-03" class="menu-icon"></iconify-icon>
                    <span>Users</span>
                </a>
            </li>
            <!-- <li>
                <a href="assign-role.php">
                <iconify-icon icon="mdi:account-cog-outline" class="menu-icon"></iconify-icon>
                <span>Role & Access</span>
                </a>
            </li> -->
            <?php } if ($row['role'] == "1") { ?>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
                        <span>Settings</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="role-access.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Roles</a>
                        </li>
                        <li>
                            <a href="view_categories.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Categories</a>
                        </li>
                        <li>
                            <a href="view_packages.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Packages</a>
                        </li>
                        <li>
                            <a href="view_products.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Products</a>
                        </li>
                        <li>
                            <a href="company.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Company</a>
                        </li>
                        <li>
                            <a href="credentials.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Credentials</a>
                        </li>
                        <li>
                            <a href="currencies.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Currencies</a>
                        </li>
                        <li>
                            <a href="bank_details.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Bank Details</a>
                        </li>
                        <li>
                            <a href="payment-gateway.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Payment Gateway</a>
                        </li>
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



<!-- JS to Toggle Modal -->
