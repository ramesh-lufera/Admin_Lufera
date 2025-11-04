<?php
$script = '<script>
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
</script>';
?>

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

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Accept package ID
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $get_package_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $get_cat_id     = isset($_POST['product_category']) ? intval($_POST['product_category']) : 152;
    $get_module     = isset($_POST['template']) ? $_POST['template'] : 'website';
} else {
    $get_package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $get_cat_id     = 152;
    $get_module     = 'website';
}

// Fetch package
$query = $conn->prepare("SELECT * FROM package WHERE id = ?");
if ($query === false) {
    die("Prepare failed for package query: " . $conn->error);
}
$query->bind_param("i", $get_package_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die('Package not found');
}
$package = $result->fetch_assoc();

// Fetch features
$featuresQuery = $conn->prepare("SELECT feature FROM features WHERE package_id = ?");
if ($featuresQuery === false) {
    die("Prepare failed for features query: " . $conn->error);
}
$featuresQuery->bind_param("i", $get_package_id);
$featuresQuery->execute();
$featuresResult = $featuresQuery->get_result();
$features = [];
while ($row = $featuresResult->fetch_assoc()) {
    $features[] = $row['feature'];
}

// Fetch durations
$durationsQuery = $conn->prepare("SELECT duration, price, preview_price FROM durations WHERE package_id = ?");
if ($durationsQuery === false) {
    die("Prepare failed for durations query: " . $conn->error);
}
$durationsQuery->bind_param("i", $get_package_id);
$durationsQuery->execute();
$durationsResult = $durationsQuery->get_result();
$durations = [];
while ($row = $durationsResult->fetch_assoc()) {
    $durations[] = $row;
}

// Fetch packages list (excluding current package)
$packagesQuery = $conn->prepare("SELECT id, title FROM package WHERE id != ? ORDER BY package_name ASC");
if ($packagesQuery === false) {
    die("Prepare failed for packages query: " . $conn->error);
}
$packagesQuery->bind_param("i", $get_package_id);
$packagesQuery->execute();
$packagesResult = $packagesQuery->get_result();
$packages_list = [];
while ($row = $packagesResult->fetch_assoc()) $packages_list[] = $row;

// Fetch products and add-ons
$productsQuery = $conn->query("SELECT id, title FROM products ORDER BY title ASC");
$products_list = [];
while ($row = $productsQuery->fetch_assoc()) $products_list[] = $row;

$addonsQuery = $conn->query("SELECT id, name FROM `add-on-service` ORDER BY name ASC");
$addons_list = [];
while ($row = $addonsQuery->fetch_assoc()) $addons_list[] = $row;

// ✅ Fetch available GST rates from taxes table
$gstQuery = $conn->query("SELECT id, tax_name, rate FROM taxes ORDER BY rate ASC");
$gst_list = [];
while ($row = $gstQuery->fetch_assoc()) $gst_list[] = $row;

// Existing selections
$selectedPackages = !empty($package['addon_package']) ? explode(',', $package['addon_package']) : [];
$selectedProducts = !empty($package['addon_product']) ? explode(',', $package['addon_product']) : [];
$selectedAddons   = !empty($package['addon_service']) ? explode(',', $package['addon_service']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $package_id = $_POST['id'];
    $package_name = $_POST['package_name'];
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $description = $_POST['description'];
    $features = $_POST['features'] ?? [];
    $updated_at = date("Y-m-d H:i:s");
    $cat_id = $_POST['cat_id'];
    $module = $_POST['module'];
    $gst_id = $_POST['gst_id']; // ✅ added gst_id field
    $addon_package = isset($_POST['packages']) && is_array($_POST['packages']) ? implode(',', $_POST['packages']) : '';
    $addon_product = isset($_POST['products']) && is_array($_POST['products']) ? implode(',', $_POST['products']) : '';
    $addon_service = isset($_POST['addons']) && is_array($_POST['addons']) ? implode(',', $_POST['addons']) : '';

    // Update package
    $stmt = $conn->prepare("UPDATE package SET package_name=?, title=?, subtitle=?, description=?, cat_id=?, template=?, created_at=?, addon_package=?, addon_product=?, addon_service=?, gst_id=? WHERE id=?");
    if ($stmt === false) {
        die("Prepare failed for update package: " . $conn->error);
    }
    $stmt->bind_param("ssssisssssii", $package_name, $title, $subtitle, $description, $cat_id, $module, $updated_at, $addon_package, $addon_product, $addon_service, $gst_id, $package_id);

    if ($stmt->execute()) {
        $stmt->close();

        // Delete and insert features
        $deleteFeatureStmt = $conn->prepare("DELETE FROM features WHERE package_id = ?");
        if ($deleteFeatureStmt === false) {
            die("Prepare failed for delete features: " . $conn->error);
        }
        $deleteFeatureStmt->bind_param("i", $package_id);
        $deleteFeatureStmt->execute();
        $deleteFeatureStmt->close();

        if (!empty($features) && is_array($features)) {
            $featureStmt = $conn->prepare("INSERT INTO features (package_id, feature, created_at) VALUES (?, ?, ?)");
            if ($featureStmt === false) {
                die("Prepare failed for features insert: " . $conn->error);
            }
            foreach ($features as $feature) {
                $cleaned_feature = trim($feature);
                if ($cleaned_feature !== "") {
                    $featureStmt->bind_param("iss", $package_id, $cleaned_feature, $updated_at);
                    $featureStmt->execute();
                }
            }
            $featureStmt->close();
        }

        // Delete and insert durations
        $deleteDurationStmt = $conn->prepare("DELETE FROM durations WHERE package_id = ?");
        if ($deleteDurationStmt === false) {
            die("Prepare failed for delete durations: " . $conn->error);
        }
        $deleteDurationStmt->bind_param("i", $package_id);
        $deleteDurationStmt->execute();
        $deleteDurationStmt->close();

        if (!empty($_POST['duration_values']) && is_array($_POST['duration_values'])) {
            $durationStmt = $conn->prepare("INSERT INTO durations (package_id, duration, price, created_at, preview_price) VALUES (?, ?, ?, ?, ?)");
            if ($durationStmt === false) {
                die("Prepare failed for durations insert: " . $conn->error);
            }
            foreach ($_POST['duration_values'] as $index => $value) {
                $unit = $_POST['duration_units'][$index] ?? '';
                $price = $_POST['prices'][$index] ?? '';
                $pre_price = $_POST['pre_prices'][$index] ?? '';
                if (!empty($value) && !empty($unit) && !empty($price)) {
                    $duration_text = $value . ' ' . $unit;
                    $durationStmt->bind_param("isdsd", $package_id, $duration_text, $price, $updated_at, $pre_price);
                    $durationStmt->execute();
                }
            }
            $durationStmt->close();
        }

        echo "<script>
            Swal.fire({
                title: 'Success!',
                text: 'Package updated successfully.',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'view_packages.php';
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
        <h6 class="fw-semibold mb-0">Edit Package</h6>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="row justify-content-center">
                <div class="col-xxl-12 col-xl-8 col-lg-10">
                    <form method="POST" class="row gy-3 needs-validation" novalidate autocomplete="off">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($get_package_id); ?>">
                        <input type="hidden" name="cat_id" value="<?php echo htmlspecialchars($get_cat_id); ?>">
                        <input type="hidden" name="module" value="<?php echo htmlspecialchars($get_module); ?>">
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Package name <span class="text-danger-600">*</span>
                            </label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" name="package_name" required maxlength="100"
                                       value="<?php echo htmlspecialchars($package['package_name']); ?>">
                                <div class="invalid-feedback">
                                    Package name is required
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Title <span class="text-danger-600">*</span>
                            </label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" name="title" required maxlength="100"
                                       value="<?php echo htmlspecialchars($package['title']); ?>">
                                <div class="invalid-feedback">
                                    Title is required
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Subtitle <span class="text-danger-600">*</span>
                            </label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" name="subtitle" required maxlength="100"
                                       value="<?php echo htmlspecialchars($package['subtitle']); ?>">
                                <div class="invalid-feedback">
                                    Subtitle is required
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Description <span class="text-danger-600">*</span>
                            </label>
                            <div class="has-validation">
                                <textarea class="form-control radius-8" name="description" required><?php echo htmlspecialchars($package['description']); ?></textarea>
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
                                <?php if (!empty($durations)): ?>
                                    <?php foreach ($durations as $index => $duration): ?>
                                        <?php
                                            $duration_parts = explode(' ', trim($duration['duration']));
                                            $duration_value = isset($duration_parts[0]) && is_numeric($duration_parts[0]) ? intval($duration_parts[0]) : '';
                                            $duration_unit = isset($duration_parts[1]) ? $duration_parts[1] : 'days';
                                        ?>
                                        <div class="duration-group mb-10 d-flex gap-2 align-items-center">
                                            <input type="number" name="duration_values[]" class="form-control radius-8" required min="1" style="width: 25%;" onkeydown="return event.key !== 'e'" placeholder="Value" value="<?php echo htmlspecialchars($duration_value); ?>">
                                            <select name="duration_units[]" class="form-control radius-8" required style="width: 25%;">
                                                <option value="">Select Unit</option>
                                                <option value="days" <?php echo $duration_unit === 'days' ? 'selected' : ''; ?>>Days</option>
                                                <option value="months" <?php echo $duration_unit === 'months' ? 'selected' : ''; ?>>Months</option>
                                                <option value="years" <?php echo $duration_unit === 'years' ? 'selected' : ''; ?>>Years</option>
                                            </select>
                                            <input type="number" name="prices[]" class="form-control radius-8" required min="0" style="width: 25%;" onkeydown="return event.key !== 'e'" placeholder="Enter price" value="<?php echo htmlspecialchars($duration['price']); ?>">
                                            <input type="number" name="pre_prices[]" class="form-control radius-8" required min="0" style="width: 25%;" onkeydown="return event.key !== 'e'" placeholder="Enter preview price" value="<?php echo htmlspecialchars($duration['preview_price']); ?>">
                                            <?php if ($index === 0): ?>
                                                <button type="button" class="btn btn-sm btn-success add-duration">+</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-danger remove-duration">−</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
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
                                <?php endif; ?>
                            </div>
                            <div class="invalid-feedback">
                                At least one duration and price pair is required.
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Features <span class="text-danger-600">*</span>
                            </label>
                            <div id="feature-wrapper">
                                <?php if (!empty($features)): ?>
                                    <?php foreach ($features as $feature): ?>
                                        <div class="feature-group mb-10 d-flex gap-2">
                                            <input type="text" name="features[]" class="form-control radius-8" required placeholder="Enter a feature" value="<?php echo htmlspecialchars($feature); ?>" />
                                            <button type="button" class="btn btn-sm btn-success add-feature">+</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="feature-group mb-10 d-flex gap-2">
                                        <input type="text" name="features[]" class="form-control radius-8" required placeholder="Enter a feature" />
                                        <button type="button" class="btn btn-sm btn-success add-feature">+</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="invalid-feedback">
                                At least one feature is required.
                            </div>
                        </div>

                        <!-- Add-ons Section -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Add-Ons <span class="text-danger-600">*</span></label>
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
                                        <?php foreach ($packages_list as $p): ?>
                                            <div class="form-check d-flex align-items-center me-3">
                                                <input class="form-check-input" type="checkbox" name="packages[]" value="<?php echo $p['id']; ?>" id="package_<?php echo $p['id']; ?>" <?php echo in_array($p['id'], $selectedPackages) ? 'checked' : ''; ?>>
                                                <label class="form-check-label ms-2 mb-0" for="package_<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></label>
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
                                        <?php foreach ($products_list as $prod): ?>
                                            <div class="form-check d-flex align-items-center me-3">
                                                <input class="form-check-input" type="checkbox" name="products[]" value="<?php echo $prod['id']; ?>" id="product_<?php echo $prod['id']; ?>" <?php echo in_array($prod['id'], $selectedProducts) ? 'checked' : ''; ?>>
                                                <label class="form-check-label ms-2 mb-0" for="product_<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['title']); ?></label>
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
                                        <?php foreach ($addons_list as $a): ?>
                                            <div class="form-check d-flex align-items-center me-3">
                                                <input class="form-check-input" type="checkbox" name="addons[]" value="<?php echo $a['id']; ?>" id="addon_<?php echo $a['id']; ?>" <?php echo in_array($a['id'], $selectedAddons) ? 'checked' : ''; ?>>
                                                <label class="form-check-label ms-2 mb-0" for="addon_<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No add-on services available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ✅ GST Dropdown (new section) -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                GST <span class="text-danger-600">*</span>
                            </label>
                            <select class="form-control radius-8" name="gst_id" required>
                                <option value="">Select GST</option>
                                <?php foreach ($gst_list as $gst): ?>
                                    <option value="<?php echo $gst['id']; ?>"
                                        <?php echo ($package['gst_id'] == $gst['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($gst['rate']) . '% (' . htmlspecialchars($gst['tax_name']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a GST.
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <button type="button" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-56 py-11 radius-8">
                                Cancel
                            </button>
                            <button type="submit" class="btn lufera-bg text-white text-md px-56 py-12 radius-8" name="save">
                                Update
                            </button>
                        </div>
                    </form>
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

            document.querySelectorAll(".toggle-section").forEach(checkbox => {
                checkbox.addEventListener("change", function () {
                    const target = document.querySelector(this.dataset.target);
                    if (!target) return;
                    if (this.checked) {
                        target.classList.remove("d-none");
                    } else {
                        target.classList.add("d-none");
                        target.querySelectorAll("input[type=checkbox]").forEach(ch => ch.checked = false);
                    }
                });
            });
        });
    </script>

<?php include './partials/layouts/layoutBottom.php' ?>