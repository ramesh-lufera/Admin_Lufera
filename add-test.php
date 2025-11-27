    <?php $script = '<script>
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

        // Fetch packages
        $packages_list = [];
        $result = $conn->query("SELECT * FROM package");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $packages_list[] = $row;
            }
        }

        // Fetch products
        $products_list = [];
        $result = $conn->query("SELECT id, title FROM products");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products_list[] = $row;
            }
        }

        // Fetch add-ons
        $addons_list = [];
        $result = $conn->query("SELECT id, name FROM `add-on-service`");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $addons_list[] = $row;
            }
        }

        // ✅ Fetch GST (Taxes)
        $gst_list = [];
        $result = $conn->query("SELECT id, tax_name, rate FROM taxes");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gst_list[] = $row;
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $package_name = $_POST['package_name'];                           
            $title = $_POST['title'];
            $subtitle = $_POST['subtitle'];
            $description = $_POST['description'];
            $features = $_POST['features'];
            $created_at = date("Y-m-d H:i:s");

            $addons = isset($_POST['addons']) && is_array($_POST['addons']) ? implode(',', $_POST['addons']) : '';
            $addon_packages = isset($_POST['packages']) && is_array($_POST['packages']) ? implode(',', $_POST['packages']) : '';
            $addon_products = isset($_POST['products']) && is_array($_POST['products']) ? implode(',', $_POST['products']) : '';
            
            $cat_id = 177;
            $template = "website";

            $gst_id = !empty($_POST['gst_id']) ? $_POST['gst_id'] : NULL;

            $stmt = $conn->prepare("INSERT INTO package (package_name, title, subtitle, description, cat_id, created_at, template, addon_service, addon_package, addon_product, gst_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssissssss", $package_name, $title, $subtitle, $description, $cat_id, $created_at, $template, $addons, $addon_packages, $addon_products, $gst_id);

            if ($stmt->execute()) {
                $package_id = $conn->insert_id;
                $stmt->close();

                if (!empty($features) && is_array($features)) {
                    $featureStmt = $conn->prepare("INSERT INTO features (package_id, feature) VALUES (?, ?)");
                    foreach ($features as $feature) {
                        $cleaned_feature = trim($feature);
                        if ($cleaned_feature !== "") {
                            $featureStmt->bind_param("is", $package_id, $cleaned_feature);
                            $featureStmt->execute();
                        }
                    }
                    $featureStmt->close();
                }
                
                
                // 3️⃣ Insert duration+price pairs
                if (!empty($_POST['duration_values']) && is_array($_POST['duration_values'])) {
                    $durationStmt = $conn->prepare("INSERT INTO durations (package_id, duration, price, created_at, preview_price) VALUES (?, ?, ?, ?, ?)");
                    
                    foreach ($_POST['duration_values'] as $index => $value) {
                        $unit = $_POST['duration_units'][$index] ?? '';
                        $price = $_POST['prices'][$index] ?? '';
                        $pre_prices = $_POST['pre_prices'][$index] ?? '';
                        
                        if (!empty($value) && !empty($unit) && !empty($price)) {
                            // Combine value + unit
                            $duration_text = $value . ' ' . $unit;
                            $durationStmt->bind_param("isdsd", $package_id, $duration_text, $price, $created_at, $pre_prices);
                            $durationStmt->execute();
                        }
                    }
                    $durationStmt->close();
                }

                // create details file if missing
                
                $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
                $det_file_path = $slug . "-det.php";

                if (!file_exists($det_file_path)) {
                    $base_php = <<<'CODE'
                        <?php 
                            include './partials/connection.php';
                            ini_set('display_errors', 1);
                            ini_set('display_startup_errors', 1);
                            error_reporting(E_ALL);

                            $product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
                            $template = $_GET['template'] ?? '';

                            $sql = "SELECT * FROM package WHERE id = " . $product_id; 
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                $id = $row['id'];
                                $template_product = $row['template'];
                            }
                        ?>

                        <?php if (!empty($template_product)): ?>
                            <?php include "./category_details/{$template_product}-details.php"; ?>
                        <?php endif; ?>
                    CODE;

                    file_put_contents($det_file_path, $base_php);
                }
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Package saved successfully.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'view-test.php';
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
                                <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Description <span class="text-danger-600">*</span></label>
                                <div class="has-validation">
                                    
                                    <textarea class="form-control radius-8" name="description" required></textarea>
                                    <div class="invalid-feedback">
                                        Description is required
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
                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">Features <span class="text-danger-600">*</span></label>
                                <div id="feature-wrapper">
                                    <div class="feature-group mb-10 d-flex gap-2">
                                        <input type="text" name="features[]" class="form-control radius-8" required placeholder="Enter a feature" />
                                        <button type="button" class="btn btn-sm btn-success add-feature">+</button>
                                    </div>
                                </div>
                                <div class="invalid-feedback">
                                    At least one feature is required.
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
                                        <?php if (!empty($packages_list)): ?>
                                            <?php foreach ($packages_list as $package): ?>
                                                <div class="form-check d-flex align-items-center me-3">
                                                    <input class="form-check-input" type="checkbox" name="packages[]" 
                                                        value="<?php echo $package['id']; ?>" 
                                                        id="package_<?php echo $package['id']; ?>">
                                                    <label class="form-check-label ms-2 mb-0" for="package_<?php echo $package['id']; ?>">
                                                        <?php echo htmlspecialchars($package['title']); ?>
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
                                        <?php if (!empty($products_list)): ?>
                                            <?php foreach ($products_list as $product): ?>
                                                <div class="form-check d-flex align-items-center me-3">
                                                    <input class="form-check-input" type="checkbox" name="products[]" 
                                                        value="<?php echo $product['id']; ?>" 
                                                        id="product_<?php echo $product['id']; ?>">
                                                    <label class="form-check-label ms-2 mb-0" for="product_<?php echo $product['id']; ?>">
                                                        <?php echo htmlspecialchars($product['title']); ?>
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
                                        <?php if (!empty($addons_list)): ?>
                                            <?php foreach ($addons_list as $addon): ?>
                                                <div class="form-check d-flex align-items-center me-3">
                                                    <input class="form-check-input" type="checkbox" name="addons[]" 
                                                        value="<?php echo $addon['id']; ?>" 
                                                        id="addon_<?php echo $addon['id']; ?>">
                                                    <label class="form-check-label ms-2 mb-0" for="addon_<?php echo $addon['id']; ?>">
                                                        <?php echo htmlspecialchars($addon['name']); ?>
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
                                    <?php if (!empty($gst_list)): ?>
                                        <?php foreach ($gst_list as $gst): ?>
                                            <option value="<?= $gst['id']; ?>">
                                                <?= htmlspecialchars($gst['rate']) . '% (' . htmlspecialchars($gst['tax_name']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No taxes found</option>
                                    <?php endif; ?>
                                </select>
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
    </script>

    <?php include './partials/layouts/layoutBottom.php' ?>