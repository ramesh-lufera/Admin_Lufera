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
    body {
        background: #f5f6fa !important;
        font-family: 'Segoe UI', sans-serif !important;
    }

    .form-wrapper {
        width: 75% !important;
        margin: 40px auto !important;
        background: #fff !important;
        padding: 40px !important;
        border-radius: 14px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
    }

    .form-title {
        font-size: 34px !important;
        font-weight: 900 !important;
        text-align: center !important;
        text-transform: uppercase !important;
        letter-spacing: 1.5px !important;
        color: #fec700 !important;
        position: relative !important;
        margin-bottom: 40px !important;
    }

    .form-title::after {
        content: '🛂' !important;
        font-size: 26px !important;
        display: block !important;
        margin: 10px auto 0 !important;
    }

    .card-group {
        background: #ffffff !important;
        padding: 30px !important;
        margin-bottom: 30px !important;
        border-radius: 12px !important;
        border: 1px solid #ddd !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 20px !important;
    }

    .card-group h4 {
        font-size: 22px !important;
        font-weight: 700 !important;
        margin-bottom: 10px !important;
        color: #444 !important;
        border-bottom: 2px dashed #ccc !important;
        padding-bottom: 8px !important;
    }

    .form-group {
        display: flex !important;
        flex-direction: column !important;
    }

    label {
        font-weight: 600 !important;
        color: #222 !important;
        margin-bottom: 6px !important;
    }

    .form-control,
    textarea,
    input[type="file"] {
        border-radius: 8px !important;
        border: 1px solid #ccc !important;
        padding: 12px 14px !important;
        font-size: 15px !important;
        width: 100% !important;
    }

    .form-control:focus,
    textarea:focus {
        border-color: #fec700 !important;
        box-shadow: 0 0 0 3px rgba(254, 199, 0, 0.25) !important;
        outline: none !important;
    }

    .form-check-group {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 16px !important;
        align-items: center !important;
    }

    .form-check-inline {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-size: 15px !important;
    }

    .form-check-inline input[type="radio"],
    .form-check-inline input[type="checkbox"] {
        width: 18px !important;
        height: 18px !important;
        accent-color: #fec700 !important;
        cursor: pointer !important;
    }

    .form-check-label {
        font-weight: 500 !important;
        color: #101010 !important;
        cursor: pointer !important;
    }

    .submit-btn {
        background-color: #fec700 !important;
        color: #fff !important;
        border: none !important;
        padding: 14px 32px !important;
        font-weight: 700 !important;
        border-radius: 10px !important;
        transition: all 0.3s ease !important;
        width: 100% !important;
    }

    .submit-btn:hover {
        background-color: #e6b800 !important;
    }

    .uploaded-preview img {
        max-height: 120px !important;
        border: 1px solid #ddd !important;
        padding: 6px !important;
        margin-top: 10px !important;
        border-radius: 8px !important;
        background: #fff !important;
    }

    @media (max-width: 768px) {
        .form-wrapper {
            width: 90% !important;
            padding: 25px !important;
        }

        .form-check-group {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
    }
</style>

<div class="dashboard-main-body">
    <div class="form-wrapper">
        <h2 class="form-title">Visa Application</h2>

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

            $prefill = [
                'name' => $name,
                'email' => $email,
                'has_phone' => $hasPhone,
                'website_name' => explode(', ', $websiteName),
                'address' => $address
            ];
        }
        ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="card-group">
                <h4>Personal Information</h4>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= $prefill['name'] ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= $prefill['email'] ?>">
                </div>
            </div>

            <div class="card-group">
                <h4>Contact Preferences</h4>
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
                    <label>Website Type</label>
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
            </div>

            <div class="card-group">
                <h4>Address & Logo</h4>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3" required><?= $prefill['address'] ?></textarea>
                </div>

                <div class="form-group">
                    <label>Logo</label>
                    <input type="file" name="logo" class="form-control" required>
                    <?php if (!empty($logoPath)): ?>
                        <div class="uploaded-preview">
                            <img src="<?= htmlspecialchars($logoPath); ?>" alt="Uploaded Logo">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="submit-btn">Submit Application</button>
        </form>
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php'; ?>
