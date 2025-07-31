<?php include './partials/layouts/layoutTop.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- <style>
    /* body {
        background: #f5f6fa !important;
        font-family: 'Segoe UI', sans-serif !important;
    } */

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
        margin-bottom: 40px !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 12px !important;
    }

    .form-icon {
        color: #fec700 !important;
        font-size: 32px !important;
        line-height: 1 !important;
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
        /* align-items: center !important; */
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

    /* .submit-btn {
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
    } */

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
</style> -->

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
        /* align-items: center !important; */
        gap: 8px !important;
    }

    .form-check-input {
        width: 18px !important;
        height: 18px !important;
        accent-color: #fec700 !important;
        /* margin: 0 !important; */
        appearance: auto !important;
    }

    .form-check-label {
        margin: 0 !important;
        color: #101010 !important;
        font-weight: 500 !important;
        cursor: pointer !important;
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
    .progress {
        height: 30px;
        background-color: #f3f3f3;
        border-radius: 8px;
        overflow: hidden;
    }
    .progress-bar {
        background-color: #fec700 !important; /* Match your form's primary color */
        color: #ffffff;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 0.6s ease;
    }

    /* Remove browser defaults for consistency */
    .custom-checkbox-yellow {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 22px;
        height: 22px;
        border: 2px solid #ccc;
        border-radius: 4px;
        cursor: pointer;
        position: relative;
        margin-top: 6px; /* vertical centering */
        background-color: #fff;
        transition: all 0.2s ease-in-out;
    }

    .custom-checkbox-yellow:checked {
        background-color: #fece1e;
        border-color: #020202;
    }

    /* .custom-checkbox-yellow:checked::after {
        content: '✔';
        color: #000;
        font-size: 14px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -56%);
        font-weight: bold;
    } */

     .edit-icon:hover {
        filter: brightness(1.1);
    }

    .update-icon:hover {
        filter: brightness(1.1);
    }

    .field-approved {
        background-color: #f6fff8 !important;
        border: 1px solid #d1f1dc !important;
        border-radius: 6px;
        transition: background-color 0.3s ease;
    }

    .field-rejected {
        background-color: #fff6f6 !important;
        border: 1px solid #f0caca !important;
        border-radius: 6px;
        transition: background-color 0.3s ease;
    }

    .form-wizard-submit {
        background-color: #fec700;
        color: #ffffff;
        display: inline-block;
        min-width: 100px;
        min-width: 120px;
        padding: 10px;
        text-align: center;
    }
</style>

<?php
    $session_user_id = $_SESSION['user_id'];

    // Determine if admin/dev is viewing another user's data
    $target_user_id = $session_user_id;

    if (isset($_GET['id']) && in_array($session_user_id, [1, 2, 7])) {
        $website_id = intval($_GET['id']);
        
        // Find the user_id from website table
        $stmt = $conn->prepare("SELECT user_id FROM websites WHERE id = ?");
        $stmt->bind_param("i", $website_id);
        $stmt->execute();
        $stmt->bind_result($fetched_user_id);
        if ($stmt->fetch()) {
            $target_user_id = $fetched_user_id;
        }
        $stmt->close();
    }

    $user_id = $target_user_id;

    $roleQuery = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $roleQuery->bind_param("i", $user_id);
    $roleQuery->execute();
    $roleQuery->bind_result($user_role);
    $roleQuery->fetch();
    $roleQuery->close();

    $savedData = [];

    // Load previously saved data for this user (if any)
    $website_id = $_GET['id'] ?? 0;
    $website_id = intval($website_id);

    $query = $conn->prepare("SELECT name FROM json WHERE website_id = ?");
    $query->bind_param("i", $website_id);
    $query->execute();
    $query->store_result();

    if ($query->num_rows > 0) {
        $query->bind_result($jsonData);
        $query->fetch();
        $savedData = json_decode($jsonData, true);
    }
    $query->close();

    // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $hasPhone = $_POST['has_phone'] ?? '';
        $websiteName = $_POST['website_name'] ?? [];
        // $websiteName = isset($_POST['website_name']) ? implode(", ", $_POST['website_name']) : '';
        $address = $_POST['address'] ?? '';
        // $logo = $_FILES['logo']['name'] ?? '';

        // $uploadDir = 'uploads/';
        // if (!is_dir($uploadDir)) {
        //     mkdir($uploadDir, 0777, true);
        // }
        // $logoPath = $uploadDir . uniqid() . '-' . basename($_FILES['logo']['name']);
        // move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath);

        $logo = $_FILES['logo']['name'] ?? '';

        $finalLogoPath = '';

        if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $uniqueName = uniqid() . '-' . basename($_FILES['logo']['name']);
            $finalLogoPath = $uploadDir . $uniqueName;
            move_uploaded_file($_FILES['logo']['tmp_name'], $finalLogoPath);
        }

        function createField($value) {
            return [
                'value' => $value,
                'status' => 'pending'
            ];
        }

        // $data = json_encode([
        //     'name' => $name,
        //     'email' => $email,
        //     'has_phone' => $hasPhone,
        //     'website_name' => $websiteName,
        //     'address' => $address,
        //     'logo' => $logoPath
        // ]);

        $data = json_encode([
            'name' => createField($name),
            'email' => createField($email),
            'has_phone' => createField($hasPhone),
            'website_name' => createField($websiteName),
            'address' => createField($address),
            'logo' => createField($finalLogoPath),
        ]);

        $website_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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

        echo '
            <script>
                Swal.fire({
                    icon: "success",
                    title: "Success!",
                    text: "Data saved successfully!"
                }).then(() => {
                    window.location.href = "website-wizard.php";
                });
            </script>';
    }

    function renderFieldExtended($fieldName, $savedData, $user_role, $label = '', $placeholder = '', $type = 'text', $options = []) {
        $val = $savedData[$fieldName]['value'] ?? '';
        $status = $savedData[$fieldName]['status'] ?? 'pending';
        $inputId = 'field_' . htmlspecialchars($fieldName);
        $isAdmin = in_array($user_role, [1, 2, 7]);
        $isReadonly = $isAdmin ? 'readonly' : '';
        $dataValue = is_array($val) ? implode(',', $val) : $val;
        $dataOptions = !empty($options) ? 'data-options="' . htmlspecialchars(implode(',', $options)) . '"' : '';

        echo '<div class="form-group mb-4">';
        echo '<div class="d-flex align-items-start">';

        // Admin checkbox
        if ($isAdmin) {
            echo '<div class="me-3 d-flex align-items-center pt-4">';
            echo '<input class="form-check-input bulk-approve-checkbox custom-checkbox custom-checkbox-yellow" type="checkbox" value="' . htmlspecialchars($fieldName) . '" id="chk_' . htmlspecialchars($fieldName) . '">';
            echo '</div>';
        }

        echo '<div class="flex-grow-1">';

        // Label
        if ($label) {
            echo '<label for="' . $inputId . '" class="form-label">' . htmlspecialchars($label) . '</label>';
        }

        $styleClass = $status === 'approved' ? 'field-approved' : ($status === 'rejected' ? 'field-rejected' : '');
        echo '<div class="input-group">';

        // === TEXT / EMAIL ===
        if ($type === 'text' || $type === 'email') {
            echo '<input type="' . $type . '" class="form-control ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
        }

        // === TEXTAREA ===
        elseif ($type === 'textarea') {
            echo '</div>';
            echo '<textarea class="form-control ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $isReadonly . '>' . htmlspecialchars($val) . '</textarea>';
        }

        // === RADIO ===
        elseif ($type === 'radio') {
            echo '</div>';
            foreach ($options as $option) {
                $checked = ($val == $option) ? 'checked' : '';
                echo '<div class="form-check form-check-inline">';
                echo '<input class="form-check-input" type="radio" id="' . $inputId . '_' . $option . '" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . ($isAdmin ? 'disabled' : '') . '>';
                echo '<label class="form-check-label" for="' . $inputId . '_' . $option . '">' . htmlspecialchars($option) . '</label>';
                echo '</div>';
            }
        }

        // === CHECKBOX ===
        elseif ($type === 'checkbox') {
            echo '</div>';
            $valArray = is_array($val) ? $val : explode(',', str_replace(' ', '', $val));
            foreach ($options as $option) {
                $checked = in_array($option, $valArray) ? 'checked' : '';
                echo '<div class="form-check form-check-inline">';
                echo '<input class="form-check-input" type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . ($isAdmin ? 'disabled' : '') . '>';
                echo '<label class="form-check-label">' . htmlspecialchars($option) . '</label>';
                echo '</div>';
            }
        }

        // === FILE ===
        elseif ($type === 'file') {
            echo '<input type="file" class="form-control ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . ($isAdmin ? 'disabled' : '') . '>';
            echo '</div>';
            if (!empty($val)) {
                echo '<div class="mt-3">';
                echo '<label class="d-block fw-bold">Uploaded File:</label>';
                $ext = strtolower(pathinfo($val, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    echo '<img src="' . htmlspecialchars($val) . '" alt="File" style="max-height: 120px; border: 1px solid #ccc; padding: 6px;">';
                } else {
                    echo '<a href="' . htmlspecialchars($val) . '" target="_blank">' . htmlspecialchars(basename($val)) . '</a>';
                }
                echo '</div>';
            }
        }

        // === Admin Buttons ===
        if ($isAdmin) {
            echo '<div class="btn-group mt-2 ms-1">';
            echo '<button type="button" class="btn btn-sm edit-icon" style="background-color: #FEC700; color: black;" data-field="' . htmlspecialchars($fieldName) . '" title="Edit">&#9998;</button>';
            echo '<button type="button" class="btn btn-sm update-icon d-none" style="background-color: #00B4D8; color: white;" data-field="' . htmlspecialchars($fieldName) . '" title="Update">&#128190;</button>';
            echo '<button type="button" class="btn btn-success btn-sm approve-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Approve">&#10004;</button>';
            echo '<button type="button" class="btn btn-danger btn-sm reject-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Reject">&#10006;</button>';
            echo '</div>';
        }

        // === USER Rejected Fields – Show Edit Icon ===
        if (!$isAdmin && $status === 'rejected') {
            echo '<button type="button" class="input-group-text text-warning edit-btn ms-2" title="Edit"
                data-field="' . htmlspecialchars($fieldName) . '"
                data-type="' . htmlspecialchars($type) . '"
                data-value="' . htmlspecialchars($dataValue) . '"
                ' . $dataOptions . '>
                &#9998;
            </button>';
        }

        // === USER Approved Fields – Show Checkmark ===
        elseif (!$isAdmin && $status === 'approved') {
            echo '<span class="input-group-text text-success">&#10004;</span>';
        }

        // No pending icon or button

        echo '</div>'; // .input-group or after field
        echo '</div>'; // .flex-grow-1
        echo '</div>'; // .d-flex
        echo '</div>'; // .form-group
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_field'])) {
        $field = $_POST['edit_field'];
        $value = $_POST['edit_value'] ?? '';
        $user_id = $_SESSION['user_id'];
        $website_id = $_GET['id'] ?? 0;

        $check = $conn->prepare("SELECT id, name FROM json WHERE user_id = ? AND website_id = ?");
        $check->bind_param("ii", $user_id, $website_id);
        $check->execute();
        $result = $check->get_result();
        $row = $result->fetch_assoc();
        $jsonData = json_decode($row['name'], true) ?? [];

        if (isset($_POST['edit_file_upload']) && isset($_FILES['file'])) {
            $uploadDir = 'uploads/';
            $filename = basename($_FILES['file']['name']);
            $targetPath = $uploadDir . uniqid() . '_' . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $value = $targetPath;
            } else {
                http_response_code(500);
                echo 'Upload failed';
                exit;
            }
        }

        $jsonData[$field]['value'] = $value;
        $jsonData[$field]['status'] = 'pending';

        $newJson = json_encode($jsonData);

        $update = $conn->prepare("UPDATE json SET name = ? WHERE id = ?");
        $update->bind_param("si", $newJson, $row['id']);
        $update->execute();

        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inline_update'])) {
        $field = $_POST['field'] ?? '';
        $website_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (!$field || !$website_id) {
            echo 'invalid request';
            exit;
        }

        // Fetch existing JSON
        $stmt = $conn->prepare("SELECT name FROM json WHERE website_id = ?");
        $stmt->bind_param("i", $website_id);
        $stmt->execute();
        $stmt->bind_result($jsonData);
        $stmt->fetch();
        $stmt->close();

        $decoded = json_decode($jsonData, true);

        if (!isset($decoded[$field])) {
            echo 'field not found';
            exit;
        }

        // === FILE upload ===
        if (!empty($_FILES['file'])) {
            $fileTmp = $_FILES['file']['tmp_name'];
            $fileName = basename($_FILES['file']['name']);
            $uploadDir = 'uploads/';
            $targetPath = $uploadDir . time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $fileName);

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (move_uploaded_file($fileTmp, $targetPath)) {
                $decoded[$field]['value'] = $targetPath;
                $decoded[$field]['status'] = 'pending';
            } else {
                echo 'file upload failed';
                exit;
            }
        }
        // === NORMAL text/radio/checkbox ===
        else {
            $value = $_POST['value'] ?? '';
            $decoded[$field]['value'] = $value;
            $decoded[$field]['status'] = 'pending';
        }

        // Update JSON
        $updatedJson = json_encode($decoded);
        $updateStmt = $conn->prepare("UPDATE json SET name = ? WHERE website_id = ?");
        $updateStmt->bind_param("si", $updatedJson, $website_id);
        $updateStmt->execute();
        $updateStmt->close();

        echo 'updated';
        exit;
    }
?>

<div class="dashboard-main-body">

    <div class="card h-100 p-0 radius-12 overflow-hidden">               
        <div class="card-body p-40">
            
            <!-- Progress Bar -->
            <div class="progress mb-4">
                <div class="progress-bar bg-success" role="progressbar" style="min-width: 10%; width: <?= $progress_percentage ?>%;" aria-valuenow="<?= $progress_percentage ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= $progress_percentage ?>% Complete
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-xxl-10">
                <section class="wizard-section">
                    <div class="row no-gutters">
                        <div class="col-lg-12">
                            <div class="form-wizard">
                                <form action="" method="post" id="myForm" role="form" enctype="multipart/form-data">
                                    
                                    <input type="submit" name="save" class="form-wizard-submit" value="Save" style="float:right">
                                    
                                    <?php if (in_array($user_role, [1, 2, 7])): ?>
                                        <div class="mb-5">
                                            <button type="button" id="bulkApproveBtn" class="btn btn-success btn-sm">Bulk Approve</button>
                                            <button type="button" id="bulkRejectBtn" class="btn btn-danger btn-sm">Bulk Reject</button>
                                        </div>
                                    <?php endif; ?>

                                    <fieldset class="wizard-fieldset show">
                                        <h5>Website</h5>
                                        <?php
                                            renderFieldExtended('name', $savedData, $user_role, 'Name', 'Enter your name', 'text');

                                            renderFieldExtended('email', $savedData, $user_role, 'Email', 'Enter your email', 'email');

                                            renderFieldExtended('has_phone', $savedData, $user_role, 'Do you have a phone?', '', 'radio', ['Yes', 'No']);

                                            renderFieldExtended('website_name', $savedData, $user_role, 'Website Name', '', 'checkbox', ['Static', 'Dynamic']);

                                            renderFieldExtended('address', $savedData, $user_role, 'Address', 'Enter your address', 'textarea');

                                            renderFieldExtended('logo', $savedData, $user_role, 'Logo', '', 'file');
                                        ?>
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        function updateProgressBar() {
            const form = $('#myForm');

            // Collect all unique required input types
            const textInputs = form.find('input[type="text"]');
            const emailInputs = form.find('input[type="email"]');
            const textareas = form.find('textarea');
            const radios = form.find('input[type="radio"][name="has_phone"]');
            const checkboxes = form.find('input[type="checkbox"][name="website_name[]"]');
            const fileInput = form.find('input[type="file"]');

            let totalFields = 5; // name, email, has_phone (radio), website_name (checkbox), address
            let filledFields = 0;

            // Check text input
            if (textInputs.val().trim() !== '') filledFields++;

            // Check email input
            if (emailInputs.val().trim() !== '') filledFields++;

            // Check textarea
            if (textareas.val().trim() !== '') filledFields++;

            // Check radio
            if (radios.filter(':checked').length > 0) filledFields++;

            // Check checkboxes (at least one)
            if (checkboxes.filter(':checked').length > 0) filledFields++;

            // Check file input (if there's a file selected OR already uploaded logo shown)
            if (fileInput[0].files.length > 0 || $('img[src*="uploads/"]').length > 0) {
                totalFields++; // only count file if it's required
                filledFields++;
            } else {
                totalFields++;
            }

            const percentage = Math.round((filledFields / totalFields) * 100);
            $('.progress-bar')
                .css('width', percentage + '%')
                .attr('aria-valuenow', percentage)
                .text(percentage + '% Complete');
        }
 
        // Update progress bar on input change (only inside the form)
        $('#myForm').find('input, textarea, select').on('input change', updateProgressBar);
        updateProgressBar(); // on load
    });
</script>

<script>
    $(document).ready(function() {
        function updateProgressBar() {
            const form = $('#myForm'); // Target your form here
            
            // Select only fields inside the form
            const totalFields = form.find('input[type="text"], textarea, select, input[type="radio"]').length;
            const filledFields = form.find('input[type="text"], textarea, select, input[type="radio"]:checked').filter(function() {
                return $(this).val().trim() !== '';
            }).length;
 
            const percentage = totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0;
            $('.progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage).text(percentage + '% Complete');
        }
 
        // Update progress bar on input change (only inside the form)
        $('#myForm').find('input, textarea, select').on('input change', updateProgressBar);
 
        // Initial calculation
        updateProgressBar();
    });
</script>

<style>
    .modal {
        position: fixed;
        z-index: 1050;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background: #fff;
        padding: 25px 20px;
        border-radius: 8px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
        position: relative;
    }
    .close-btn {
        position: absolute;
        right: 15px;
        top: 12px;
        font-size: 20px;
        cursor: pointer;
        color: #aaa;
    }
    .close-btn:hover {
        color: #000;
    }
    h5 {
       font-size: 1.25rem !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let currentField = '';
        let currentType = 'text';
        const modal = document.getElementById('editModal');
        const fieldContainer = document.getElementById('editFieldContainer');
        const saveBtn = document.getElementById('saveEditBtn');
        const closeBtn = document.querySelector('.close-btn');

        // Open modal
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentField = btn.dataset.field;
                currentType = btn.dataset.type || 'text';
                const value = btn.dataset.value || '';
                const options = btn.dataset.options ? btn.dataset.options.split(',') : [];

                fieldContainer.innerHTML = ''; // Clear previous field content

                if (currentType === 'textarea') {
                    fieldContainer.innerHTML = `<textarea id="modalInput" class="form-control" rows="4">${value}</textarea>`;
                } else if (currentType === 'radio') {
                    options.forEach(opt => {
                        const checked = opt.trim() === value ? 'checked' : '';
                        fieldContainer.innerHTML += `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modalInput" value="${opt.trim()}" ${checked}>
                                <label class="form-check-label">${opt.trim()}</label>
                            </div>`;
                    });
                } else if (currentType === 'checkbox') {
                    const selected = value.split(',').map(v => v.trim());
                    options.forEach(opt => {
                        const checked = selected.includes(opt.trim()) ? 'checked' : '';
                        fieldContainer.innerHTML += `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="modalInput" value="${opt.trim()}" ${checked}>
                                <label class="form-check-label">${opt.trim()}</label>
                            </div>`;
                    });
                } else if (currentType === 'file') {
                    let filePreview = '';
                    if (value) {
                        const ext = value.split('.').pop().toLowerCase();
                        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                            filePreview = `<img src="${value}" class="img-fluid mb-2" style="max-height:150px;">`;
                        } else {
                            filePreview = `<p class="mb-2"><a href="${value}" target="_blank">${value}</a></p>`;
                        }
                    }
                    fieldContainer.innerHTML = `
                        ${filePreview}
                        <input type="file" class="form-control" id="modalInput">`;
                } else {
                    // Default input (text, email, number etc.)
                    fieldContainer.innerHTML = `<input type="text" id="modalInput" class="form-control" value="${value}" />`;
                }

                modal.style.display = 'flex';
            });
        });

        // Save edit
        saveBtn.addEventListener('click', () => {
            const formData = new FormData();
            formData.append('edit_field', currentField);

            if (currentType === 'checkbox') {
                const values = Array.from(document.querySelectorAll('input[name="modalInput"]:checked'))
                    .map(el => el.value);
                formData.append('edit_value', values.join(','));
            } else if (currentType === 'radio') {
                const val = document.querySelector('input[name="modalInput"]:checked')?.value || '';
                formData.append('edit_value', val);
            } else if (currentType === 'file') {
                const file = document.getElementById('modalInput').files[0];
                if (!file) {
                    alert("Please choose a file.");
                    return;
                }
                formData.append('edit_file_upload', 'true');
                formData.append('file', file);
            } else {
                formData.append('edit_value', document.getElementById('modalInput').value);
            }

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(() => {
                modal.style.display = 'none';
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Your changes have been saved.',
                    confirmButtonColor: '#ffc107'
                }).then(() => {
                    location.reload();
                });
            });
        });

        // Close modal
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });
    });
</script>

<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content p-4 rounded" style="background:#fff; max-width:500px; margin:auto;">
        <span class="close-btn float-end" title="Close" style="cursor:pointer;">&times;</span>
        <h5 class="mb-3">Edit Field</h5>
        <div id="editFieldContainer" class="mb-3"></div>
        <button type="button" class="btn lufera-bg btn-warning w-100" id="saveEditBtn">Save</button>
    </div>
</div>

<script>
    jQuery('.approve-btn, .reject-btn').click(function () {
        const field = jQuery(this).data('field');
        const status = jQuery(this).hasClass('approve-btn') ? 'approved' : 'rejected';
        const websiteId = new URLSearchParams(window.location.search).get('id');

        jQuery.ajax({
            url: 'json_status_update.php?id=' + websiteId,
            method: 'POST',
            data: { field, status },
            success: function () {
                Swal.fire('Status updated!', '', 'success').then(() => location.reload());
            },
            error: function () {
                Swal.fire('Error updating status', '', 'error');
            }
        });
    });
</script>

<script>
    $(document).ready(function () {
        const websiteId = new URLSearchParams(window.location.search).get('id');

        // Single field approve/reject
        $('.approve-btn, .reject-btn').click(function () {
            const field = $(this).data('field');
            const status = $(this).hasClass('approve-btn') ? 'approved' : 'rejected';

            $.post('json_status_update.php?id=' + websiteId, {
                fields: [field],
                status: status
            }, function () {
                Swal.fire('Success', 'Field updated.', 'success').then(() => location.reload());
            }).fail(function () {
                Swal.fire('Error', 'Could not update field.', 'error');
            });
        });

        // Bulk approve/reject
        function bulkUpdate(status) {
            const fields = $('.bulk-approve-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            if (fields.length === 0) {
                Swal.fire('No fields selected', '', 'info');
                return;
            }

            $.post('json_status_update.php?id=' + websiteId, {
                fields: fields,
                status: status
            }, function () {
                Swal.fire('Success', 'Fields updated.', 'success').then(() => location.reload());
            }).fail(function () {
                Swal.fire('Error', 'Bulk update failed.', 'error');
            });
        }

        $('#bulkApproveBtn').click(function () {
            bulkUpdate('approved');
        });

        $('#bulkRejectBtn').click(function () {
            bulkUpdate('rejected');
        });
    });
</script>

<script>
    $(document).ready(function () {
        const websiteId = new URLSearchParams(window.location.search).get('id');

        // Enable inline editing for all fields
        $('.edit-icon').click(function () {
            const field = $(this).data('field');

            // Enable text/email/textarea
            const input = $('#field_' + field);
            input.prop('readonly', false).focus();

            // Enable radio buttons
            $('input[type="radio"][name="' + field + '"]').prop('disabled', false);

            // Enable checkboxes
            $('input[type="checkbox"][name="' + field + '[]"]').prop('disabled', false);

            // Enable file input
            input.prop('disabled', false);

            // Show update icon
            $('.update-icon[data-field="' + field + '"]').removeClass('d-none');
            $(this).addClass('d-none');
        });

        // Handle update/save click
        $('.update-icon').click(function () {
            const field = $(this).data('field');
            const input = $('#field_' + field);
            const inputType = input.attr('type');
            let value;

            if (inputType === 'file') {
                const file = input[0].files[0];
                const formData = new FormData();
                formData.append('inline_update', true);
                formData.append('field', field);
                formData.append('file', file);

                $.ajax({
                    type: 'POST',
                    url: '',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function () {
                        Swal.fire('Success', 'File updated.', 'success').then(() => location.reload());
                    },
                    error: function () {
                        Swal.fire('Error', 'File upload failed.', 'error');
                    }
                });
                return;
            }

            // Checkbox (multiple)
            if (input.length === 0 && $('input[name="' + field + '[]"]').length > 0) {
                let selected = [];
                $('input[name="' + field + '[]"]:checked').each(function () {
                    selected.push($(this).val());
                });
                value = selected.join(',');
            }
            // Radio
            else if ($('input[name="' + field + '"]:checked').length > 0) {
                value = $('input[name="' + field + '"]:checked').val();
            }
            // Text, email, textarea
            else {
                value = input.val();
            }

            $.post('', {
                inline_update: true,
                field: field,
                value: value
            }, function (res) {
                if (res === 'updated') {
                    Swal.fire('Success', 'Field updated.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', 'Failed to update: ' + res, 'error');
                }
            }).fail(function () {
                Swal.fire('Error', 'Server error occurred.', 'error');
            });
        });
    });
</script>

<?php include './partials/layouts/layoutBottom.php'; ?>
