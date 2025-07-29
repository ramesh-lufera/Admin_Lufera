<?php include './partials/layouts/layoutTop.php';

    $user_id = $_SESSION['user_id'] ?? 0;
    $website_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $prefill = [
        'name' => '',
        'email' => '',
        'has_phone' => '',
        'website_name' => [],
        'address' => ''
    ];
    $logoPath = '';

    // Fetch latest saved JSON for this user/website
    $query = $conn->prepare("SELECT name FROM json WHERE user_id = ? AND website_id = ? ORDER BY id DESC LIMIT 1");
    $query->bind_param("ii", $user_id, $website_id);
    $query->execute();
    $query->bind_result($jsonData);
    if ($query->fetch() && $jsonData) {
        $decoded = json_decode($jsonData, true);
        $prefill = [
            'name' => $decoded['name'] ?? '',
            'email' => $decoded['email'] ?? '',
            'has_phone' => $decoded['has_phone'] ?? '',
            'website_name' => isset($decoded['website_name']) ? explode(', ', $decoded['website_name']) : [],
            'address' => $decoded['address'] ?? ''
        ];
        $logoPath = $decoded['logo'] ?? '';
    }
    $query->close();
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .form-wrapper {
        max-width: 75% !important;
        margin: 40px auto !important;
        padding: 40px !important;
        background: #ffffff !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important;
    }

    .form-wrapper h4 {
        font-size: 1.8rem !important;
        font-weight: bold !important;
        color: #101010 !important;
        border-bottom: 2px solid #fec700 !important;
        padding-bottom: 10px !important;
        margin-bottom: 30px !important;
    }

    .form-group {
        margin-bottom: 24px !important;
    }

    .form-group label {
        font-weight: 600 !important;
        color: #101010 !important;
        margin-bottom: 8px !important;
        display: block !important;
    }

    .form-control, textarea, input[type="file"] {
        border-radius: 10px !important;
        border: 1px solid #ccc !important;
        padding: 12px 15px !important;
        width: 100% !important;
    }

    .form-control:focus, textarea:focus {
        border-color: #fec700 !important;
        box-shadow: 0 0 0 3px rgba(254,199,0,0.2) !important;
        outline: none !important;
    }

    .form-check-group {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 20px !important;
        align-items: center !important;
        margin-top: 10px !important;
    }

    .form-check-inline {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .form-check-input {
        width: 18px !important;
        height: 18px !important;
        accent-color: #fec700 !important;
        margin: 0 !important;
        appearance: auto !important;
    }

    .form-check-label {
        margin: 0 !important;
        color: #101010 !important;
        font-weight: 500 !important;
        cursor: pointer !important;
    }

    .submit-btn {
        background-color: #fec700 !important;
        color: #101010 !important;
        border: none !important;
        padding: 14px 32px !important;
        font-weight: 600 !important;
        border-radius: 10px !important;
        transition: all 0.3s ease !important;
    }

    .submit-btn:hover {
        background-color: #e6b800 !important;
    }

    @media (max-width: 768px) {
        .form-wrapper {
            width: 90% !important;
            padding: 30px !important;
        }

        .form-check-group {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
    }
</style>

<div class="dashboard-main-body">
    <div class="form-wrapper">
        <h4>Website</h4>

        <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                $hasPhone = $_POST['has_phone'] ?? '';
                $websiteName = isset($_POST['website_name']) ? implode(", ", $_POST['website_name']) : '';
                $address = $_POST['address'] ?? '';
                $logo = $_FILES['logo']['name'] ?? '';

                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $logoPath = $uploadDir . uniqid() . '-' . basename($_FILES['logo']['name']);
                move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath);

                $data = json_encode([
                    'name' => $name,
                    'email' => $email,
                    'has_phone' => $hasPhone,
                    'website_name' => $websiteName,
                    'address' => $address,
                    'logo' => $logoPath
                ]);

                $check = $conn->prepare("SELECT id FROM json WHERE user_id = ? AND website_id = ?");
                $check->bind_param("ii", $user_id, $website_id);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $update = $conn->prepare("UPDATE json SET name = ? WHERE user_id = ? AND website_id = ?");
                    $update->bind_param("sii", $data, $user_id, $website_id);
                    $success = $update->execute();
                    $update->close();
                } else {
                    $insert = $conn->prepare("INSERT INTO json (name, user_id, website_id) VALUES (?, ?, ?)");
                    $insert->bind_param("sii", $data, $user_id, $website_id);
                    $success = $insert->execute();
                    $insert->close();
                }

                $check->close();

                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Data saved successfully.',
                        confirmButtonColor: '#fec700'
                    });
                </script>";

                // Update prefill after saving
                $prefill = [
                    'name' => $name,
                    'email' => $email,
                    'has_phone' => $hasPhone,
                    'website_name' => explode(', ', $websiteName),
                    'address' => $address
                ];
            }
        ?>

        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" class="form-control" required value="<?= $prefill['name'] ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control" required value="<?= $prefill['email'] ?>">
            </div>

            <div class="form-group">
                <label>Do you have a phone?</label>
                <div class="form-check-group">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="radio" name="has_phone" value="Yes" <?= $prefill['has_phone'] === 'Yes' ? 'checked' : '' ?>>
                        <label class="form-check-label">Yes</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="radio" name="has_phone" value="No" <?= $prefill['has_phone'] === 'No' ? 'checked' : '' ?>>
                        <label class="form-check-label">No</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Website Name</label>
                <div class="form-check-group">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="website_name[]" value="Static" <?= in_array('Static', $prefill['website_name']) ? 'checked' : '' ?>>
                        <label class="form-check-label">Static</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="website_name[]" value="Dynamic" <?= in_array('Dynamic', $prefill['website_name']) ? 'checked' : '' ?>>
                        <label class="form-check-label">Dynamic</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea name="address" class="form-control" rows="3" required><?= $prefill['address'] ?></textarea>
            </div>

            <div class="form-group">
                <label for="logo">Logo</label>
                <input type="file" name="logo" class="form-control" required>
            </div>

            <?php if (!empty($logoPath)): ?>
                <div class="mt-3">
                    <label style="font-weight:600;color:#101010;">Uploaded Logo:</label><br>
                    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Uploaded Logo" style="max-height: 120px; margin-top: 10px; border: 1px solid #ccc; padding: 6px; background: #fff;">
                </div>
            <?php endif; ?>

            <button type="submit" class="submit-btn">Submit</button>
        </form>
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php'; ?>
