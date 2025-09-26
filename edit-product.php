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

if (isset($_POST['save'])) {
  $name = $_POST['name'];
  $title = $_POST['title'];
  $subtitle = $_POST['subtitle'];
  $price = $_POST['price'];
  $description = $_POST['description'];
  $category = $_POST['category'];
  $tags = $_POST['tags'];
  $updated_at = date("Y-m-d H:i:s");
  $duration_value = isset($_POST['duration_value']) ? intval($_POST['duration_value']) : 0;
  $duration_unit = isset($_POST['duration_unit']) ? $_POST['duration_unit'] : '';

  $duration = $duration_value . ' ' . $duration_unit;
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

  $stmt = $conn->prepare("UPDATE products SET name=?, title=?, subtitle=?, price=?, description=?, category=?, tags=?, product_image=?, duration=?, cat_id=?, created_at=?, template=? WHERE id=?");
  $stmt->bind_param("sssssssssissi", $name, $title, $subtitle, $price, $description, $category, $tags, $product_image, $duration, $cat_id, $updated_at, $module, $get_product_id);


  if ($stmt->execute()) {
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
        <h6 class="fw-semibold mb-0">Edit Product</h6>
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
                            <label class="form-label">Price <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                              <input type="number" class="form-control radius-8" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" onkeydown="return event.key !== 'e'" required maxlength="30">
                                <div class="invalid-feedback">
                                Price is required
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
                            <input 
                              type="number" 
                              name="duration_value" 
                              id="duration_value"
                              class="form-control radius-8" 
                              required 
                              min="1" 
                              style="width: 60%;" 
                              value="<?php echo htmlspecialchars($duration_value); ?>"
                            >
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
</script>

<?php include './partials/layouts/layoutBottom.php' ?>

