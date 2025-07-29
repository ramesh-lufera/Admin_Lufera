<?php 
include './partials/layouts/layoutTop.php'; 

$user_id = $_SESSION['user_id'] ?? 0;
$website_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables to hold form values
$name = $facebook_id = $password = $address = '';
$websiteType = [];
$logoPath = '';

// Fetch existing data if website_id is present
if ($website_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM json WHERE user_id = ? AND website_id = ?");
    $stmt->bind_param("ii", $user_id, $website_id);
    $stmt->execute();
    $stmt->bind_result($json_data);
    if ($stmt->fetch()) {
        $decoded = json_decode($json_data, true);
        $name = $decoded['name'] ?? '';
        $facebook_id = $decoded['facebook_id'] ?? '';
        $password = $decoded['password'] ?? '';
        $websiteType = isset($decoded['website_type']) ? explode(", ", $decoded['website_type']) : [];
        $address = $decoded['address'] ?? '';
        $logoPath = $decoded['logo'] ?? '';
    }
    $stmt->close();
}
?>

<style>
    

    .form-wrapper {
        width: 100%;
        max-width: 750px;
        padding: 40px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.5s ease-in-out;
        margin: 50px auto;
    }

    .form-wrapper h4 {
        text-align: center;
        font-size: 1.8rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 30px;
        position: relative;
        padding-bottom: 8px;
    }

    .form-wrapper h4::after {
        content: "";
        display: block;
        height: 3px;
        background: #f6c90e;
        margin: 8px auto 0;
        border-radius: 2px;
    }

    .form-group {
        position: relative;
        margin-bottom: 25px;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px 0;
        border: none;
        border-bottom: 2px solid #ccc;
        background: transparent;
        color: #333;
        font-size: 1rem;
        outline: none;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #f6c90e;
    }

    .form-group label {
        position: absolute;
        top: 12px;
        left: 0;
        font-size: 0.9rem;
        color: #666;
        transition: 0.2s ease;
        pointer-events: none;
    }
    .form-group p {
    font-size: 0.9rem;
    color: #666;
    }
    .form-group input:focus + label,
    .form-group input:not(:placeholder-shown) + label,
    .form-group textarea:focus + label,
    .form-group textarea:not(:placeholder-shown) + label {
        top: -10px;
        font-size: 0.75rem;
        color: #f6c90e;
    }

    .form-check-inline {
        margin-right: 15px;
    }

    .form-check-label {
        margin-left: 5px;
        font-size: 0.9rem;
        color: #333;
    }

    .submit-btn {
        width: 100%;
        padding: 12px;
        background: #f6c90e;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
    }

    .submit-btn:hover {
        background: #ffdb4d;
        transform: translateY(-2px);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 576px) {
        .form-wrapper {
            padding: 25px;
        }
    }
</style>

<div class="form-wrapper">
    <h4 class="m-auto" style="width:max-content">Marketing Wizard</h4>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $facebook_id = $_POST['facebook_id'] ?? '';
        $password = $_POST['password'] ?? '';
        $websiteType = isset($_POST['website_type']) ? implode(", ", $_POST['website_type']) : '';
        $address = $_POST['address'] ?? '';

        // Handle logo upload
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!empty($_FILES['logo']['name'])) {
            $uniqueFileName = uniqid() . '-' . basename($_FILES['logo']['name']);
            $logoPath = $uploadDir . $uniqueFileName;
            move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath);
        }

        $data = json_encode([
            'name' => $name,
            'facebook_id' => $facebook_id,
            'password' => $password,
            'website_type' => $websiteType,
            'address' => $address,
            'logo' => $logoPath
        ]);

        $check = $conn->prepare("SELECT id FROM json WHERE user_id = ? AND website_id = ?");
        $check->bind_param("ii", $user_id, $website_id);
        $check->execute();
        $check->store_result();

        $action = '';
        if ($check->num_rows > 0) {
            $update = $conn->prepare("UPDATE json SET name = ? WHERE user_id = ? AND website_id = ?");
            $update->bind_param("sii", $data, $user_id, $website_id);
            $success = $update->execute();
            $update->close();
            $action = 'update';
        } else {
            $insert = $conn->prepare("INSERT INTO json (name, user_id, website_id) VALUES (?, ?, ?)");
            $insert->bind_param("sii", $data, $user_id, $website_id);
            $success = $insert->execute();
            $insert->close();
            $action = 'insert';
        }

        if ($success) {
            $msg = ($action == 'insert') ? 'Data inserted successfully!' : 'Data updated successfully!';
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '$msg',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.href = window.location.pathname + '?id=$website_id';
                });
            </script>";
        }
    }
    ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="form-group">
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder=" " required>
            <label for="name">Name</label>
        </div>

        <div class="form-group">
            <input type="text" name="facebook_id" value="<?php echo htmlspecialchars($facebook_id); ?>" placeholder=" " required>
            <label for="facebook_id">Facebook ID</label>
        </div>

        <div class="form-group">
            <input type="password" name="password" value="<?php echo htmlspecialchars($password); ?>" placeholder=" " required>
            <label for="password">Password</label>
        </div>

        <div class="form-group">
            <p>Website Name</p>
            <div class="form-check form-check-inline">
                <input class="form-check-input p-2 mt-2" type="checkbox" name="website_type[]" value="Static" <?php echo in_array('Static', $websiteType) ? 'checked' : ''; ?>>
                <p class="form-check-label d-inline">Static</p>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input p-2 mt-2" type="checkbox" name="website_type[]" value="Dynamic" <?php echo in_array('Dynamic', $websiteType) ? 'checked' : ''; ?>>
                <p class="form-check-label d-inline">Dynamic</p>
            </div>
        </div>

        <div class="form-group">
            <textarea name="address" rows="3" placeholder=" " required><?php echo htmlspecialchars($address); ?></textarea>
            <label for="address">Address</label>
        </div>

        <label for="logo">Logo</label>
        <?php if (!empty($logoPath)) { ?>
            <div>
                <img src="<?php echo $logoPath; ?>" alt="Logo" width="100" style="margin-bottom: 10px;">
            </div>
        <?php } ?>
        <div class="form-group">
            <input type="file" name="logo" <?php echo empty($logoPath) ? 'required' : ''; ?>>
        </div>

        <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block">Submit</button>
    </form>
</div>

<?php include './partials/layouts/layoutBottom.php' ?>
