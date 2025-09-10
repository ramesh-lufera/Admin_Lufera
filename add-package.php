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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$addons_query = $conn->query("SELECT id, name FROM `add-on-service`");

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Existing package fields
        $plan_type = $_POST['plan_type'];
        $title = $_POST['title'];
        $subtitle = $_POST['subtitle'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $duration = $_POST['duration'];
        $addons = isset($_POST['addons']) && is_array($_POST['addons']) ? implode(',', $_POST['addons']) : '';
        $features = $_POST['features']; // Array of features
        $created_at = date("Y-m-d H:i:s");

        $stmt = $conn->prepare("INSERT INTO package (plan_type, title, subtitle, price, description, duration, cat_id, `add-on-service`, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssiss", $plan_type, $title, $subtitle, $price, $description, $duration, $category_id, $addons, $created_at);
    
        if ($stmt->execute()) {
            $package_id = $conn->insert_id;
            $stmt->close();
    
            // Insert features
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
                    text: 'Package and features saved successfully.',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'view-$catSlug.php';
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
                <h6 class="fw-semibold mb-0">Add User</h6>
            </div>

            <div class="card h-100 p-0 radius-12">
                <div class="card-body p-24">
                    <div class="row justify-content-center">
                        <div class="col-xxl-12 col-xl-8 col-lg-10">
                            <h6 class="text-md text-primary-light mb-16" style="font-size: 20px !important">Profile</h6>
                            <form method="POST" class="row gy-3 needs-validation" novalidate>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Plan Type <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <input type="text" class="form-control radius-8" name="plan_type" required maxlength="20">
                                        <div class="invalid-feedback">
                                            Plan type is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Title <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        
                                        <input type="text" class="form-control radius-8" name="title" required maxlength="20">
                                        <div class="invalid-feedback">
                                            Title is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Subtitle <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        
                                        <input type="text" class="form-control radius-8" name="subtitle" required maxlength="100">
                                        <div class="invalid-feedback">
                                            Subtitle is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Price <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        
                                        <input type="text" class="form-control radius-8" name="price" required maxlength="5">
                                        <div class="invalid-feedback">
                                            Price is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Description <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        
                                        <textarea class="form-control radius-8" name="description" required></textarea>
                                        <div class="invalid-feedback">
                                            Description is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Duration  <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        
                                        <input type="text" class="form-control radius-8" name="duration" required maxlength="20">
                                        <div class="invalid-feedback">
                                            Duration is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Featuressss <span class="text-danger-600">*</span></label>
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
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Add-on Service <span class="text-danger-600">*</span></label>
                                    <div class="mb-3">
                                        <?php if ($addons_query && $addons_query->num_rows > 0): ?>
                                            <?php while($addon = $addons_query->fetch_assoc()): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="addons[]" value="<?php echo $addon['id']; ?>" id="addon_<?php echo $addon['id']; ?>">
                                                <label class="form-check-label" for="addon_<?php echo $addon['id']; ?>">
                                                    <?php echo htmlspecialchars($addon['name']); ?>
                                                </label>
                                            </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <p>No add-on services available.</p>
                                        <?php endif; ?>
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

<?php include './partials/layouts/layoutBottom.php' ?>