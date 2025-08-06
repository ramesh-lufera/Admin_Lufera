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

          if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $package_name = $_POST['package_name'];
            $plan_type = $_POST['plan_type'];
            $title = $_POST['title'];
            $subtitle = $_POST['subtitle'];
            $price = $_POST['price'];
            $description = $_POST['description'];
            $duration = $_POST['duration'];
            $features = $_POST['features']; // Array of features
            $updated_at = date("Y-m-d H:i:s");
            $cat_id = $_POST['cat_id'];
        
            $stmt = $conn->prepare("UPDATE package SET package_name=?, plan_type=?, title=?, subtitle=?, price=?, description=?, duration=?, cat_id=?, created_at=? WHERE id=?");
            $stmt->bind_param("sssssssisi", $package_name, $plan_type, $title, $subtitle, $price, $description, $duration, $cat_id, $updated_at, $package_id);
        
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
                            window.location.href = 'view-website.php';
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
                                    Tagline <span class="text-danger-600">*</span>
                                </label>
                                <div class="has-validation">
                                    <input type="text" class="form-control radius-8" name="plan_type" required maxlength="30"
                                          value="<?php echo htmlspecialchars($package['plan_type']); ?>">
                                    <div class="invalid-feedback">
                                        Tagline is required
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

                            <div class="mb-2">
                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                    Duration <span class="text-danger-600">*</span>
                                </label>
                                <div class="has-validation">
                                    <input type="text" class="form-control radius-8" name="duration" required maxlength="20"
                                          value="<?php echo htmlspecialchars($package['duration']); ?>">
                                    <div class="invalid-feedback">
                                        Duration is required
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
                                            <div class="feature-group mb-2 d-flex gap-2">
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