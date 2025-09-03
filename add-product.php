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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $tags = $_POST['tags'];
    $created_at = date("Y-m-d H:i:s");
    $cat_id = $_GET['id'];
    $template = $_GET['template'];
    $slug = isset($_GET['slug']) ? $_GET['slug'] : '';

    $duration_value = isset($_POST['duration_value']) ? intval($_POST['duration_value']) : 0;
    $duration_unit = isset($_POST['duration_unit']) ? $_POST['duration_unit'] : '';

    if ($duration_value > 0 && in_array($duration_unit, ['days', 'months', 'years'])) {
        $duration = $duration_value . ' ' . $duration_unit; // e.g., "10 days"
    } else {
        echo "<script>alert('Invalid duration input.'); window.history.back();</script>";
        exit;
    }


    // Image upload
    $product_image = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/products/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);  // create directory if it doesn't exist
        }

        $file_name = time() . '_' . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file type (only images)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $product_image = $file_name;  // Save file name in DB
            } else {
                echo "<script>alert('Failed to upload image.'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Invalid file type. Allowed: JPG, PNG, GIF, WEBP.'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Please upload a product image.'); window.history.back();</script>";
        exit;
    }
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO products 
    (name, title, subtitle, price, description, category, tags, product_image, cat_id, duration, template, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssssssisss", $name, $title, $subtitle, $price, $description, $category, $tags, $product_image, $cat_id, $duration, $template,  $created_at);

if ($stmt->execute()) {
    // --------- CREATE -det.php FILE IF NOT EXISTS ---------
    $det_file_path = $slug . "-det.php";

    if (!file_exists($det_file_path)) {
        // Base PHP content for all templates (connection + product fetching)
        $base_php = <<<'PHP'
        <?php 
        include './partials/connection.php';
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $product_id = $_GET['product_id'];

        $sql = "SELECT * FROM products WHERE id = $product_id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id = $row['id'];
            $template_product = $row['template'];
        }
        ?>

        PHP;
        
        $det_content = $base_php . "<?php include './category_details/' . \$template_product . '-details.php'; ?>";
        file_put_contents($det_file_path, $det_content);
            }

            // --------- SUCCESS MESSAGE ---------
            echo "<script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Product saved successfully.',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'view-$slug.php';
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
        <h6 class="fw-semibold mb-0">Add Product</h6>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="row justify-content-center">
                <div class="col-xxl-12 col-xl-8 col-lg-10">
                    <form method="POST" enctype="multipart/form-data" class="row gy-3 needs-validation card-form" novalidate autocomplete="off">
                        <div class="form-group text-start mb-2">
                            <label class="form-label">Product image <span class="text-danger-600">*</span></label>
                          <div class="has-validation">
                            <input type="file" id="file-input" accept="image/*" name="product_image" required>
                            <label class="image-upload d-flex" for="file-input">
                            <span>Click or Drag Image Here</span>
                            <img id="preview" alt="Preview Image">
                            </label>
                            <div class="invalid-feedback">
                                Please upload a product image.
                            </div>
                          </div>
                        </div>
                        <div class="form-group mb-2">
                          <label class="form-label">Product name <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                              <input type="text" class="form-control radius-8" id="name" name="name" required maxlength="100">
                              <div class="invalid-feedback">
                                Product name is required
                              </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Title <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="title" name="title" required maxlength="100">
                                <div class="invalid-feedback">
                                Title is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Subtitle <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="subtitle" name="subtitle" required maxlength="100">
                                <div class="invalid-feedback">
                                Subtitle is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Description <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="description" name="description" required>
                                </textarea>
                                <div class="invalid-feedback">
                                  Description is required
                                </div>
                            </div>
                        </div>
                       
                        <div class="form-group mb-2">
                            <label class="form-label">Price <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                              <input type="number" name="price" class="form-control radius-8" required onkeydown="return event.key !== 'e'" maxlength="10">
                                <div class="invalid-feedback">
                                Price is required
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-2">
                            <label class="form-label">Duration <span class="text-danger-600">*</span></label>
                            <div class="d-flex gap-2">
                                <input type="number" id="duration_value" name="duration_value" class="form-control radius-8" required min="1" style="width: 60%;">
                                <select id="duration_unit" name="duration_unit" class="form-control radius-8" required style="width: 40%;">
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

                        <div class="form-group mb-2">
                            <label class="form-label">Category <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="category" name="category" required maxlength="100">
                                <div class="invalid-feedback">
                                Category is required
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Tags <span class="text-danger-600">*</span></label>
                            <div class="has-validation">
                                <input type="text" class="form-control radius-8" id="tags" name="tags" required maxlength="100">
                                <div class="invalid-feedback">
                                Tags is required
                                </div>
                            </div>
                        </div>
                        <!-- Submit Button -->
                        <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 radius-8 mt-28 submit-btn">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const durationValue = document.getElementById('duration_value');
const durationUnit = document.getElementById('duration_unit');

// Options with singular/plural forms
const units = {
  singular: ["Day", "Month", "Year", "Hour"],
  plural: ["Days", "Months", "Years", "Hours"],
  values: ["days", "months", "years", "hours"] // keep values consistent for backend
};

// Function to update options
function updateDurationOptions() {
  const isSingular = durationValue.value == 1;
  durationUnit.innerHTML = ""; // clear existing options

  const list = isSingular ? units.singular : units.plural;
  list.forEach((label, index) => {
    const opt = document.createElement("option");
    opt.value = units.values[index]; // keep backend value constant
    opt.textContent = label;
    durationUnit.appendChild(opt);
  });
}

// Initial load
updateDurationOptions();

// Update on input change
durationValue.addEventListener("input", updateDurationOptions);
</script>

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
  if (!fileInput.value) {
    fileInput.setCustomValidity('Please upload a product image');
  } else {
    fileInput.setCustomValidity('');
  }
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>
