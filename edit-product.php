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
        
<?php include './partials/layouts/layoutTop.php' ?>

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
      object-fit: contain;
      display: none;
      background: #f5f6fa;
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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Accept package ID from either POST (preferred) or GET (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $get_product_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
  $get_cat_id     = isset($_POST['product_category']) ? intval($_POST['product_category']) : 0;
  $get_module     = isset($_POST['template']) ? $_POST['template'] : '';
} else {
  $get_product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
  $get_cat_id     = null;
  $get_module     = null;
}

if ($get_product_id <= 0) {
    die('Invalid product ID');
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $get_product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die('Product not found');
}

$inclusive_features = [];
$exclusive_features = [];

$featureQuery = $conn->prepare("SELECT * FROM features WHERE package_id = ?");
$featureQuery->bind_param("i", $get_product_id);
$featureQuery->execute();

$featureResult = $featureQuery->get_result();

while ($row = $featureResult->fetch_assoc()) {
    if ($row['feature_type'] == 'inclusive') {
        $inclusive_features[] = $row['feature'];
    } else {
        $exclusive_features[] = $row['feature'];
    }
}

if (isset($_POST['save'])) {
  $name = $_POST['name'];
  $title = $_POST['title'];
  $subtitle = $_POST['subtitle'];
  $price = $_POST['price'];
  $description = $_POST['description'];
  $short_description = $_POST['short_description'];
  $preview_price = $_POST['preview_price'];
  $is_login = isset($_POST['is_login']) ? 1 : 0;
  $category = $_POST['category'];
  $tags = $_POST['tags'];
  $feature_item = isset($_POST['feature_item']) ? 'Yes' : 'No';
  $updated_at = date("Y-m-d H:i:s");
  $duration_value = isset($_POST['duration_value']) ? intval($_POST['duration_value']) : 0;
  $duration_unit = isset($_POST['duration_unit']) ? $_POST['duration_unit'] : '';

  if ($duration_value > 0 && in_array($duration_unit, ['days', 'months', 'years', 'day', 'month', 'year'])) {
    $duration = $duration_value . ' ' . $duration_unit;
  } else {
      echo "<script>alert('Invalid duration input.'); window.history.back();</script>";
      exit;
  }
  $cat_id = $_POST['cat_id'];
  $module = $_POST['module'];
  $product_image = $product['product_image']; // Keep old image if new one not uploaded

  // If a new image is uploaded
  if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
      $target_dir = "uploads/products/";
      if (!is_dir($target_dir)) {
          mkdir($target_dir, 0777, true);
      }

      $file_name = time() . '_' . basename($_FILES["product_image"]["name"]);
      $target_file = $target_dir . $file_name;
      $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
      $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

      if (in_array($imageFileType, $allowed_types)) {
          if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
              $product_image = $file_name;
          } else {
              echo "<script>alert('Failed to upload image.'); window.history.back();</script>";
              exit;
          }
      } else {
          echo "<script>alert('Invalid file type. Allowed: JPG, PNG, GIF, WEBP.'); window.history.back();</script>";
          exit;
      }
  }

  $stmt = $conn->prepare("UPDATE products SET name=?, title=?, subtitle=?, price=?, description=?, category=?, tags=?, feature_item=?, product_image=?, duration=?, cat_id=?, created_at=?, template=?, short_description=?, preview_price=?, is_login=?  WHERE id=?");
  $stmt->bind_param("sssdssssssisssdii", $name, $title, $subtitle, $price, $description, $category, $tags, $feature_item, $product_image, $duration, $cat_id, $updated_at, $module, $short_description, $preview_price, $is_login, $get_product_id);

  if ($stmt->execute()) {
    // Delete old features
$deleteFeature = $conn->prepare("DELETE FROM features WHERE package_id = ?");
$deleteFeature->bind_param("i", $get_product_id);
$deleteFeature->execute();

// Insert updated inclusive features
$featureStmt = $conn->prepare("INSERT INTO features (package_id, feature, feature_type, cat_type) VALUES (?, ?, ?, ?)");

// Inclusive
if (!empty($_POST['inclusive_features'])) {
    foreach ($_POST['inclusive_features'] as $feature) {
        $feature = trim($feature);
        if ($feature !== "") {
            $type = 'inclusive';
            $cat_type = 2;
            $featureStmt->bind_param("issi", $get_product_id, $feature, $type, $cat_type);
            $featureStmt->execute();
        }
    }
}

// Exclusive
if (!empty($_POST['exclusive_features'])) {
    foreach ($_POST['exclusive_features'] as $feature) {
        $feature = trim($feature);
        if ($feature !== "") {
            $type = 'exclusive';
            $cat_type = 2;
            $featureStmt->bind_param("issi", $get_product_id, $feature, $type, $cat_type);
            $featureStmt->execute();
        }
    }
}
$featureStmt->close();

    logActivity(
      $conn,
      $loggedInUserId,
      "Product",                   // module
      "Product updated successfully - $name"  // description
    );
      echo "<script>
          Swal.fire({
              title: 'Success!',
              text: 'Product updated successfully.',
              confirmButtonText: 'OK'
          }).then((result) => {
              if (result.isConfirmed) {
                  window.location.href = 'view_products.php';
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
      <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0 m-auto">Edit Product</h6>
      <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="row justify-content-center">
                <div class="col-xxl-12 col-xl-8 col-lg-10">
                    <form method="POST" enctype="multipart/form-data" class="row gy-3 needs-validation card-form" novalidate autocomplete="off">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($get_product_id); ?>">
                        <input type="hidden" class="form-control radius-8" name="cat_id" required maxlength="30"
                               value="<?php echo htmlspecialchars($get_cat_id); ?>">
                        <input type="hidden" class="form-control radius-8" name="module" required maxlength="30"
                               value="<?php echo htmlspecialchars($get_module); ?>">
                        <div class="form-group text-start mb-2">
                            <label class="form-label">Product image <span class="text-danger-600">*</span></label>
                          <div class="has-validation">
                          <input type="file" id="file-input" accept="image/*" name="product_image">
                          <label class="image-upload d-flex" for="file-input">
                          <span><?php echo $product['product_image'] ? '' : 'Click or Drag Image Here'; ?></span>
                          <img id="preview" src="uploads/products/<?php echo htmlspecialchars($product['product_image']); ?>" alt="Preview Image" style="<?php echo $product['product_image'] ? 'display:block;' : 'display:none;'; ?>">
                          </label>
                            <div class="invalid-feedback">
                                Please upload a product image.
                            </div>
                          </div>
                        </div>
                        <div class="form-group mb-2">
                          <label class="form-label">Product name <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                            <input type="text" class="form-control radius-8" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required maxlength="30">
                              <div class="invalid-feedback">
                                Product name is required
                              </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Title <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="title" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required maxlength="30">
                                <div class="invalid-feedback">
                                Title is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Subtitle <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($product['subtitle']); ?>" required maxlength="30">
                                <div class="invalid-feedback">
                                Subtitle is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Description <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="description" name="description" value="<?php echo htmlspecialchars($product['description']); ?>" required maxlength="30">
                                </textarea>
                                <div class="invalid-feedback">
                                  Description is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                          <label class="form-label">Short Description <span class="text-danger-600">*</span></label>
                          <div class="has-validation">
                            <input type="text" 
                                  class="form-control radius-8" 
                                  id="short_description" 
                                  name="short_description"
                                  value="<?php echo htmlspecialchars($product['short_description']); ?>" 
                                  required>
                            <div class="invalid-feedback">
                                Short Description is required
                            </div>
                          </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Price <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                              <input type="number" class="form-control radius-8" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" onkeydown="return event.key !== 'e'" required maxlength="30">
                                <div class="invalid-feedback">
                                Price is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Preview Price <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="number" class="form-control radius-8" id="preview_price" name="preview_price" value="<?php echo htmlspecialchars($product['preview_price']); ?>" required onkeydown="return event.key !== 'e'">
                                <div class="invalid-feedback">
                                    Preview Price is required
                                </div>
                            </div>
                        </div>
                        <?php 
                          $duration_parts = explode(' ', $product['duration']);
                          $duration_value = $duration_parts[0] ?? '';
                          $duration_unit = $duration_parts[1] ?? 'days';
                        ?>
                        <div class="form-group mb-2">
                          <label class="form-label">Duration <span class="text-danger-600">*</span></label>
                          <div class="d-flex gap-2">
                            <input type="number" name="duration_value" id="duration_value" class="form-control radius-8" required min="1" style="width: 60%;" value="<?php echo htmlspecialchars($duration_value); ?>">
                            <select name="duration_unit" id="duration_unit" class="form-control radius-8" required style="width: 40%;">
                              <!-- Options will be populated by JS -->
                            </select>
                          </div>
                          <div class="invalid-feedback">
                            Duration is required
                          </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label">Category <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="category" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required maxlength="30">
                                <div class="invalid-feedback">
                                Category is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Tags <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="tags" name="tags" value="<?php echo htmlspecialchars($product['tags']); ?>" required maxlength="30">
                                <div class="invalid-feedback">
                                Tags is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                          <label class="form-label">Features <span class="text-danger-600">*</span></label>   
                          <div class="row">
                              <!-- Inclusive -->
                              <div class="col-6">
                                  <p class="mb-0">Inclusive</p>
                                  <div id="inclusive-wrapper">
                                      <?php if (!empty($inclusive_features)) : ?>
                                          <?php foreach ($inclusive_features as $index => $feature) : ?>
                                              <div class="feature-group d-flex gap-2 mb-10">
                                                  <input type="text" name="inclusive_features[]" class="form-control" value="<?php echo htmlspecialchars($feature); ?>" required>
                                                  <?php if ($index == 0) : ?>
                                                      <button type="button" class="btn btn-success add-inclusive">+</button>
                                                  <?php else : ?>
                                                      <button type="button" class="btn btn-danger remove-feature">−</button>
                                                  <?php endif; ?>
                                              </div>
                                          <?php endforeach; ?>
                                      <?php else : ?>

                                          <div class="feature-group d-flex gap-2 mb-10">
                                              <input type="text" name="inclusive_features[]" class="form-control" required>
                                              <button type="button" class="btn btn-success add-inclusive">+</button>
                                          </div>
                                      <?php endif; ?>
                                  </div>
                              </div>

                              <!-- Exclusive -->
                              <div class="col-6">
                                  <p class="mb-0">Exclusive</p>
                                  <div id="exclusive-wrapper">
                                      <?php if (!empty($exclusive_features)) : ?>
                                          <?php foreach ($exclusive_features as $index => $feature) : ?>
                                              <div class="feature-group d-flex gap-2 mb-10">
                                                  <input type="text" name="exclusive_features[]" class="form-control" value="<?php echo htmlspecialchars($feature); ?>" required>
                                                  <?php if ($index == 0) : ?>
                                                      <button type="button" class="btn btn-success add-exclusive">+</button>
                                                  <?php else : ?>
                                                      <button type="button" class="btn btn-danger remove-feature">−</button>
                                                  <?php endif; ?>
                                              </div>
                                          <?php endforeach; ?>
                                      <?php else : ?>
                                          <div class="feature-group d-flex gap-2 mb-10">
                                              <input type="text" name="exclusive_features[]" class="form-control" required>
                                              <button type="button" class="btn btn-success add-exclusive">+</button>
                                          </div>
                                      <?php endif; ?>
                                  </div>
                              </div>
                          </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Feature item</label>

                            <div class="form-check d-flex align-items-center gap-2">
                                <input type="checkbox" id="feature_item" name="feature_item" value="Yes" class="form-check-input" <?php echo ($product['feature_item'] === 'Yes') ? 'checked' : ''; ?>>
                                <label for="feature_item" class="form-check-label mb-0">
                                    Mark as featured product
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Is Login?</label>
                            <div class="form-check d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="is_login" id="isLogin" <?php echo ($product['is_login'] == 1) ? 'checked' : ''; ?>>                              
                                <label class="form-check-label ms-2 mb-0" for="isLogin">
                                    Require login to purchase
                                </label>
                            </div>
                        </div>
                        <!-- Submit Button -->
                        <button type="submit" name="save" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 radius-8 mt-28 submit-btn">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
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
  if (!fileInput.value && !preview.getAttribute('src')) {
    // No new file chosen AND no existing image → block submit
    fileInput.setCustomValidity('Please upload a product image');
  } else {
    // Either existing image OR new file chosen → allow submit
    fileInput.setCustomValidity('');
  }
});

const durationValueInput = document.getElementById('duration_value');
const durationUnitSelect = document.getElementById('duration_unit');

// PHP value for preselection
const selectedUnit = "<?php echo $duration_unit; ?>";

function updateDurationOptions() {
  const value = parseInt(durationValueInput.value, 10);
  const isSingular = value === 1;

  const units = isSingular 
    ? ['day', 'month', 'year'] 
    : ['days', 'months', 'years'];

  durationUnitSelect.innerHTML = ''; // clear existing options

  units.forEach(unit => {
    const option = document.createElement('option');
    option.value = unit;
    option.textContent = unit.charAt(0).toUpperCase() + unit.slice(1);
    if (unit === selectedUnit) {
      option.selected = true;
    }
    durationUnitSelect.appendChild(option);
  });
}

// Run once on page load
updateDurationOptions();

// Update whenever the value changes
durationValueInput.addEventListener('input', updateDurationOptions);

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

// Remove
document.addEventListener("click", function(e) {
    if (e.target.classList.contains("remove-feature")) {
        e.target.parentElement.remove();
    }
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>

