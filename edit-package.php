<?php
$script = '<script>
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

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

<?php include './partials/layouts/layoutTop.php' ?>
<?php
$package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($package_id <= 0) {
    die('Invalid Package ID');
}

$query = $conn->prepare("SELECT * FROM package WHERE id = ?");
$query->bind_param("i", $package_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die('Package not found');
}

$package = $result->fetch_assoc();

// Fetch features
$featuresQuery = $conn->prepare("SELECT feature FROM features WHERE package_id = ?");
$featuresQuery->bind_param("i", $package_id);
$featuresQuery->execute();
$featuresResult = $featuresQuery->get_result();

$features = [];
while ($row = $featuresResult->fetch_assoc()) {
    $features[] = $row['feature'];
}
// Fetch all available add-on services
$servicesQuery = $conn->query("SELECT id, name FROM `add-on-service` ORDER BY name ASC");
$allServices = [];
while ($row = $servicesQuery->fetch_assoc()) {
    $allServices[] = $row;
}

// Existing package selected services
$selectedServices = !empty($package['addon_service']) ? explode(',', $package['addon_service']) : [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $package_name = $_POST['package_name'];
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $features = $_POST['features']; // Array of features
    $updated_at = date("Y-m-d H:i:s");
    $cat_id = $_POST['cat_id'];
    $duration_value = isset($_POST['duration_value']) ? intval($_POST['duration_value']) : 0;
    $duration_unit = isset($_POST['duration_unit']) ? $_POST['duration_unit'] : '';
    $duration = $duration_value . ' ' . $duration_unit;
    $addon_service = isset($_POST['addon_service']) ? implode(',', $_POST['addon_service']) : '';

    // $stmt = $conn->prepare("UPDATE package SET package_name=?, title=?, subtitle=?, price=?, description=?, duration=?, cat_id=?, created_at=? WHERE id=?");
    // $stmt->bind_param("ssssssisi", $package_name, $title, $subtitle, $price, $description, $duration, $cat_id, $updated_at, $package_id);
    $stmt = $conn->prepare("UPDATE package SET package_name=?, title=?, subtitle=?, price=?, description=?, duration=?, cat_id=?, created_at=?, addon_service=? WHERE id=?");
    $stmt->bind_param("ssssssissi", $package_name, $title, $subtitle, $price, $description, $duration, $cat_id, $updated_at, $addon_service, $package_id);

    if ($stmt->execute()) {
        $stmt->close();

        // Delete old features and insert new ones
        $conn->query("DELETE FROM features WHERE package_id = $package_id");

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

        echo "
        <script>
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
        echo "<script>
            alert('Error: " . $stmt->error . "');
            window.history.back();
        </script>";
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
                        <input type="hidden" class="form-control radius-8" name="cat_id" required maxlength="30"
                               value="<?php echo htmlspecialchars($package['cat_id']); ?>">
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Package name <span class="text-danger-600">*</span>
                            </label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" name="package_name" required maxlength="30"
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
                                <input type="text" class="form-control radius-8" name="title" required maxlength="30"
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
                                Price <span class="text-danger-600">*</span>
                            </label>
                            <div class="has-validation">
                                <input type="number" class="form-control radius-8" name="price" required maxlength="10"
                                       value="<?php echo htmlspecialchars($package['price']); ?>" onkeydown="return event.key !== 'e'">
                                <div class="invalid-feedback">
                                    Price is required
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

                        <?php
                        $duration_value = '';
                        $duration_unit = 'days'; // Default unit
                        if (!empty($package['duration'])) {
                            $duration_parts = explode(' ', trim($package['duration']));
                            $duration_value = isset($duration_parts[0]) && is_numeric($duration_parts[0]) ? intval($duration_parts[0]) : '';
                            $duration_unit = isset($duration_parts[1]) ? $duration_parts[1] : 'days';
                        }
                        ?>
                        <div class="form-group mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Duration <span class="text-danger-600">*</span>
                            </label>
                            <div class="d-flex gap-2">
                                <div class="has-validation" style="width: 60%;">
                                    <input type="number" id="duration_value" name="duration_value" class="form-control radius-8" required min="1"
                                        value="<?php echo htmlspecialchars($duration_value); ?>">
                                    <div class="invalid-feedback">
                                        Please enter a valid duration value.
                                    </div>
                                </div>
                                <div class="has-validation" style="width: 40%;">
                                    <select id="duration_unit" name="duration_unit" class="form-control radius-8" required>
                                        <option value="days" <?php echo $duration_unit === 'days' ? 'selected' : ''; ?>><?php echo $duration_value == 1 ? 'Day' : 'Days'; ?></option>
                                        <option value="months" <?php echo $duration_unit === 'months' ? 'selected' : ''; ?>><?php echo $duration_value == 1 ? 'Month' : 'Months'; ?></option>
                                        <option value="years" <?php echo $duration_unit === 'years' ? 'selected' : ''; ?>><?php echo $duration_value == 1 ? 'Year' : 'Years'; ?></option>
                                        <option value="hours" <?php echo $duration_unit === 'hours' ? 'selected' : ''; ?>><?php echo $duration_value == 1 ? 'Hour' : 'Hours'; ?></option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a duration unit.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Features <span class="text-danger-600">*</span>
                            </label>
                            <div id="feature-wrapper">
                                <?php if (!empty($features)) { ?>
                                    <?php foreach ($features as $feature) { ?>
                                        <div class="feature-group mb-10 d-flex gap-2">
                                            <input type="text" name="features[]" class="form-control radius-8" required value="<?php echo htmlspecialchars($feature); ?>" />
                                            <button type="button" class="btn btn-sm btn-success add-feature">+</button>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                                <!-- Empty input for adding new feature -->
                            </div>
                            <div class="invalid-feedback">
                                At least one feature is required.
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Add-on Services
                            </label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($allServices as $service) { ?>
                                    <div class="d-flex align-items-center gap-10 fw-medium text-lg">
                                        <div class="form-check style-check d-flex align-items-center">
                                            <input class="form-check-input" 
                                                type="checkbox" 
                                                name="addon_service[]" 
                                                value="<?php echo $service['id']; ?>" 
                                                id="service_<?php echo $service['id']; ?>"
                                                <?php echo in_array($service['id'], $selectedServices) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="service_<?php echo $service['id']; ?>">
                                                <?php echo htmlspecialchars($service['name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>


                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <button type="button" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-56 py-11 radius-8">
                                Cancel
                            </button>
                            <button type="submit" class="btn lufera-bg text-white text-md px-56 py-12 radius-8">
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
        });
    </script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const durationInput = document.getElementById("duration_value");
    const durationUnit = document.getElementById("duration_unit");

    function updateDurationLabels() {
        const val = parseInt(durationInput.value, 10);
        const isOne = (val === 1);

        durationUnit.querySelectorAll("option").forEach(opt => {
            switch (opt.value) {
                case "days":   opt.textContent = isOne ? "Day" : "Days"; break;
                case "months": opt.textContent = isOne ? "Month" : "Months"; break;
                case "years":  opt.textContent = isOne ? "Year" : "Years"; break;
                case "hours":  opt.textContent = isOne ? "Hour" : "Hours"; break;
            }
        });
    }

    // Run on page load
    updateDurationLabels();

    // Run whenever user changes the number
    durationInput.addEventListener("input", updateDurationLabels);
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>