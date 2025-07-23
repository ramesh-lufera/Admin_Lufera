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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $subtitle = $_POST['subtitle'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $tags = $_POST['tags'];
    $created_at = date("Y-m-d H:i:s");
    $cat_id = $_GET['id'];
    $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
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
    $stmt = $conn->prepare("INSERT INTO products (name, subtitle, price, description, category, tags, product_image, cat_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssis", $name, $subtitle, $price, $description, $category, $tags, $product_image,$cat_id, $created_at);

    if ($stmt->execute()) {
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
                    <form method="POST" enctype="multipart/form-data" class="row gy-3 needs-validation card-form" novalidate>
                        <input type="file" id="file-input" accept="image/*" name="product_image">
                        <label class="image-upload" for="file-input">
                        <span>Click or Drag Image Here</span>
                        <img id="preview" alt="Preview Image">
                        </label>
                        <input type="file" id="file-input" accept="image/*">
                        <!-- Text Fields -->
                        <div class="form-group mb-2">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" class="form-control radius-8" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Product Subtitle</label>
                            <input type="text" name="subtitle" class="form-control radius-8" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Product Description</label>
                            <textarea name="description" class="form-control radius-8" required></textarea>
                        </div>
                       
                        <div class="form-group mb-2">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" class="form-control radius-8" required onkeydown="return event.key !== 'e'" maxlength="10">
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control radius-8" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags" class="form-control radius-8" required>
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
      } else {
        preview.style.display = 'none';
        uploadLabel.style.display = 'block';
      }
    });
  </script>
<?php include './partials/layouts/layoutBottom.php' ?>
