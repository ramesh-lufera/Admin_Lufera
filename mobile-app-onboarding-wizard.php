<?php include './partials/layouts/layoutTop.php'; ?>
<style>
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

    .form-section-title {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        padding: 12px 16px;
        margin-bottom: 25px;
        border-left: 5px solid #fec700;
        background-color: #fffdf3;
        border-radius: 6px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
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
    .progress {
        height: 40px;
        background-color: #f3f3f3;
        border-radius: 8px;
        overflow: hidden;
    }
    .progress-bar {
        background-color: #fec700 !important; /* Match your form's primary color */
        color: #000;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 0.6s ease;
        height: 40px;
        font-size:20px;
    }
    .w-85{
        width:85% !important;
    }
.edit-icon, .update-icon, .approve-btn, .reject-btn{
	width:45px;
	height:45px;
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

    if (isset($_POST['save'])) {
        $company_name = $_POST['company_name'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $website = $_POST['website'] ?? '';
    
        $app_name = $_POST['app_name'] ?? '';
        $platform = $_POST['platform'] ?? '';
        $app_description = $_POST['app_description'] ?? '';
        $core_features = $_POST['core_features'] ?? '';
    
        $logo_provided = $_POST['logo_provided'] ?? '';
        $color_style = $_POST['color_style'] ?? '';
        $screenshots = $_FILES['screenshots']['name'] ?? '';
    
        $user_login = $_POST['user_login'] ?? '';
        $backend_details = $_POST['backend_details'] ?? '';
        $push_notifications = $_POST['push_notifications'] ?? '';
        $payment_integration = $_POST['payment_integration'] ?? '';
    
        $admin_panel = $_POST['admin_panel'] ?? '';
        $admin_functions = $_POST['admin_functions'] ?? '';
    
        $google_dev_account = $_POST['google_dev_account'] ?? '';
        $apple_dev_account = $_POST['apple_dev_account'] ?? '';
    
        $budget = $_POST['budget'] ?? '';
        $launch_date = $_POST['launch_date'] ?? '';
        $timeline_constraints = $_POST['timeline_constraints'] ?? '';
    
        $extra_notes = $_POST['extra_notes'] ?? '';
    
        // Handle file upload for screenshots
        if (!empty($_FILES['screenshots']['name'])) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $_FILES['screenshots']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['screenshots']['tmp_name'], $targetPath)) {
                $screenshots = $targetPath;
            }
        } else {
            // Keep existing file if no new one was chosen
            $screenshots = $_POST['screenshots_existing'] ?? '';
        }

    
        function createField($value) {
            return [
                'value' => $value,
                'status' => 'pending'
            ];
        }
    
        $data = json_encode([
            // 1. Client & Business Info
            'company_name' => createField($company_name),
            'contact_person' => createField($contact_person),
            'email' => createField($email),
            'phone' => createField($phone),
            'website' => createField($website),
    
            // 2. App Overview
            'app_name' => createField($app_name),
            'platform' => createField($platform),
            'app_description' => createField($app_description),
            'core_features' => createField($core_features),
    
            // 3. Design & Branding
            'logo_provided' => createField($logo_provided),
            'color_style' => createField($color_style),
            'screenshots' => createField($screenshots),
    
            // 4. Functionality
            'user_login' => createField($user_login),
            'backend_details' => createField($backend_details),
            'push_notifications' => createField($push_notifications),
            'payment_integration' => createField($payment_integration),
    
            // 5. Admin Panel
            'admin_panel' => createField($admin_panel),
            'admin_functions' => createField($admin_functions),
    
            // 6. App Publishing
            'google_dev_account' => createField($google_dev_account),
            'apple_dev_account' => createField($apple_dev_account),
    
            // 7. Budget & Timeline
            'budget' => createField($budget),
            'launch_date' => createField($launch_date),
            'timeline_constraints' => createField($timeline_constraints),
    
            // 8. Notes or Questions
            'extra_notes' => createField($extra_notes),
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
                    window.history.back();
                });
            </script>';
    }
    

    function renderFieldExtended($fieldName, $savedData, $user_role, $label = '', $placeholder = '', $type = 'text', $options = []) {
        $val = $savedData[$fieldName]['value'] ?? '';
        $status = $savedData[$fieldName]['status'] ?? 'pending';
        $inputId = 'field_' . htmlspecialchars($fieldName);
        $isAdmin = in_array($user_role, [1, 2, 7]);
        // $isReadonly = $isAdmin ? 'readonly' : '';
        $isReadonly = ($isAdmin || (!$isAdmin && ($status === 'approved' || $status === 'rejected'))) ? 'readonly' : '';
        $isDisabled = ($isAdmin || (!$isAdmin && ($status === 'approved' || $status === 'rejected'))) ? 'disabled' : '';
        $dataValue = is_array($val) ? implode(',', $val) : $val;
        $dataOptions = !empty($options) ? 'data-options="' . htmlspecialchars(implode(',', $options)) . '"' : '';

        echo '<div class="form-group mb-4">';
        echo '<div class="d-flex align-items-start">';

        // Admin checkbox
        if ($isAdmin) {
            echo '<div class="me-3 d-flex align-items-center pt-4">';
            echo '<input class="form-check-input bulk-approve-checkbox custom-checkbox custom-checkbox-yellow mt-0" type="checkbox" value="' . htmlspecialchars($fieldName) . '" id="chk_' . htmlspecialchars($fieldName) . '">';
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
        if ($type === 'text' || $type === 'email' || $type === 'url' || $type === 'date') {
            echo '<input type="' . $type . '" class="form-control  w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
        }

        // === TEXTAREA ===
        elseif ($type === 'textarea') {           
            echo '<textarea class="form-control  w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $isReadonly . '>' . htmlspecialchars($val) . '</textarea>';
        }

        // === RADIO ===
        elseif ($type === 'radio') {
            foreach ($options as $option) {
                $checked = ($val == $option) ? 'checked' : '';
                echo '<div class="form-check form-check-inline">';
                // echo '<input class="form-check-input" type="radio" id="' . $inputId . '_' . $option . '" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . ($isAdmin ? 'disabled' : '') . '>';
                echo '<input class="form-check-input mt-4" type="radio" id="' . $inputId . '_' . $option . '" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . $isDisabled . '>';
                echo '<label class="form-check-label" for="' . $inputId . '_' . $option . '">' . htmlspecialchars($option) . '</label>';
                echo '</div>';
            }
        }

        // === CHECKBOX ===
        elseif ($type === 'checkbox') {
            $valArray = is_array($val) ? $val : explode(',', str_replace(' ', '', $val));
            foreach ($options as $option) {
                $checked = in_array($option, $valArray) ? 'checked' : '';
                echo '<div class="form-check form-check-inline">';
                // echo '<input class="form-check-input" type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . ($isAdmin ? 'disabled' : '') . '>';
                echo '<input class="form-check-input mt-4" type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . $isDisabled . '>';
                echo '<label class="form-check-label">' . htmlspecialchars($option) . '</label>';
                echo '</div>';
            }
        }

        // === FILE ===
        elseif ($type === 'file') {
            echo '<input type="file" class="form-control w-100 ' . $styleClass . '" 
                    id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isDisabled . '>';
        
            // Hidden input to store existing file path
            if (!empty($val)) {
                echo '<input type="hidden" name="' . htmlspecialchars($fieldName) . '_existing" value="' . htmlspecialchars($val) . '">';
                
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

        // === DATE ===
         elseif ($type === 'date') {
            echo '<input type="' . $type . '" class="form-control  w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
        }
    
        // === SELECT ===
        elseif ($type === 'select') {
            echo '<select class="form-control w-85 h-auto ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isDisabled . '>';
            echo '<option value="">-- Select an option --</option>';
            foreach ($options as $option) {
                $selected = ($val == $option) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars(ucfirst($option)) . '</option>';
            }
            echo '</select>';
        }

        // === Admin Buttons ===
        if ($isAdmin) {
            echo '<div class="btn-group ms-1">';
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
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Mobile App Development Onboarding Form
        </h6>
    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">               
        <div class="card-body p-40">
            <div class="row justify-content-center">
                <div class="col-xxl-10">
                <section class="wizard-section">
                    <div class="row no-gutters">
                        <div class="col-lg-12">
                            <div class="form-wizard">
                            <!-- Progress Bar -->   
                                <div class="progress mb-20">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                                        role="progressbar"
                                        style="min-width: 5%;"
                                        id="formProgressBar">
                                        0%
                                    </div>
                                </div>

                                <form action="" method="post" id="myForm" role="form" enctype="multipart/form-data">
                                    <?php if (in_array($user_role, [1, 2, 7])): ?>
                                        <div class="mb-5">
                                            <button type="button" id="bulkApproveBtn" class="btn btn-success btn-sm">Bulk Approve</button>
                                            <button type="button" id="bulkRejectBtn" class="btn btn-danger btn-sm">Bulk Reject</button>
                                        </div>
                                    <?php endif; ?>
                                    <h5>1. Client & Business Info</h5>
                                        <?php
                                        renderFieldExtended('company_name', $savedData, $user_role, 'Company Name', '', 'text');
                                        renderFieldExtended('contact_person', $savedData, $user_role, 'Contact Person', '', 'text');
                                        renderFieldExtended('email', $savedData, $user_role, 'Email', '', 'email');
                                        renderFieldExtended('phone', $savedData, $user_role, 'Phone', '', 'text');
                                        renderFieldExtended('website', $savedData, $user_role, 'Website (if any)', '', 'url');
                                        ?>

                                        <h5>2. App Overview</h5>
                                        <?php
                                        renderFieldExtended('app_name', $savedData, $user_role, 'App Name (Tentative)', '', 'text');
                                        renderFieldExtended('platform', $savedData, $user_role, 'Platform', '', 'select', ['Android','iOS','Both Android & iOS']);
                                        renderFieldExtended('app_description', $savedData, $user_role, 'Brief Description of the App', '', 'textarea');
                                        renderFieldExtended('core_features', $savedData, $user_role, 'Core Features / Functionalities', '', 'textarea');
                                        ?>

                                        <h5>3. Design & Branding</h5>
                                        <?php
                                        renderFieldExtended('logo_provided', $savedData, $user_role, 'Logo Provided?', '', 'select', ['Yes','No','Need Help with Logo Design']);
                                        renderFieldExtended('color_style', $savedData, $user_role, 'Preferred Colors / Style', '', 'text');
                                        renderFieldExtended('screenshots', $savedData, $user_role, 'Upload Screens / Sketches (if any)', '', 'file');
                                        ?>

                                        <h5>4. Functionality</h5>
                                        <?php
                                        renderFieldExtended('user_login', $savedData, $user_role, 'Will the app require user login?', '', 'select', ['Yes','No']);
                                        renderFieldExtended('backend_details', $savedData, $user_role, 'Any backend/database/API required?', '', 'textarea');
                                        renderFieldExtended('push_notifications', $savedData, $user_role, 'Push Notifications?', '', 'select', ['Yes','No']);
                                        renderFieldExtended('payment_integration', $savedData, $user_role, 'Payment Integration Needed?', '', 'select', ['Yes','No']);
                                        ?>

                                        <h5>5. Admin Panel</h5>
                                        <?php
                                        renderFieldExtended('admin_panel', $savedData, $user_role, 'Do you need a web-based admin panel?', '', 'select', ['Yes','No']);
                                        renderFieldExtended('admin_functions', $savedData, $user_role, 'What should the admin be able to do?', '', 'textarea');
                                        ?>

                                        <h5>6. App Publishing</h5>
                                        <?php
                                        renderFieldExtended('google_dev_account', $savedData, $user_role, 'Do you have a Google Play Developer Account?', '', 'select', ['Yes','No','Need Help Creating One']);
                                        renderFieldExtended('apple_dev_account', $savedData, $user_role, 'Do you have an Apple Developer Account?', '', 'select', ['Yes','No','Need Help Creating One']);
                                        ?>

                                        <h5>7. Budget & Timeline</h5>
                                        <?php
                                        renderFieldExtended('budget', $savedData, $user_role, 'Approximate Budget', '', 'text');
                                        renderFieldExtended('launch_date', $savedData, $user_role, 'Expected Launch Date', '', 'date');
                                        renderFieldExtended('timeline_constraints', $savedData, $user_role, 'Any Deadline or Time Constraints?', '', 'textarea');
                                        ?>

                                        <h5>8. Notes or Questions</h5>
                                        <?php
                                        renderFieldExtended('extra_notes', $savedData, $user_role, 'Anything else you want to share?', '', 'textarea');
                                        ?>

 
                                        <?php if (in_array($user_role, [8])): ?>
                                        <input type="submit" name="save" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block" value="Save" >
                                    <?php endif; ?>
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
                                <input class="form-check-input mt-4" type="radio" name="modalInput" value="${opt.trim()}" ${checked}>
                                <label class="form-check-label">${opt.trim()}</label>
                            </div>`;
                    });
                } else if (currentType === 'checkbox') {
                    const selected = value.split(',').map(v => v.trim());
                    options.forEach(opt => {
                        const checked = selected.includes(opt.trim()) ? 'checked' : '';
                        fieldContainer.innerHTML += `
                            <div class="form-check">
                                <input class="form-check-input mt-4" type="checkbox" name="modalInput" value="${opt.trim()}" ${checked}>
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
                        // Swal.fire('Error', 'File upload failed.', 'error');
                        Swal.fire('Success', 'File updated.', 'success').then(() => location.reload());
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
                    // Swal.fire('Error', 'Failed to update: ' + res, 'error');
                    Swal.fire('Success', 'Field updated.', 'success').then(() => location.reload());
                }
            }).fail(function () {
                Swal.fire('Error', 'Server error occurred.', 'error');
            });
        });
    });
</script>

<!-- Progress bar -->
<script>
    function updateProgressBar() {
        let filled = 0;
        const totalFields = 23;

        // 1. Client & Business Info
        if ($('#field_company_name').val()?.trim()) filled++;
        if ($('#field_contact_person').val()?.trim()) filled++;
        if ($('#field_email').val()?.trim()) filled++;
        if ($('#field_phone').val()?.trim()) filled++;
        if ($('#field_website').val()?.trim()) filled++;

        // 2. App Overview
        if ($('#field_app_name').val()?.trim()) filled++;
        if ($('#field_platform').val()?.trim()) filled++;
        if ($('#field_app_description').val()?.trim()) filled++;
        if ($('#field_core_features').val()?.trim()) filled++;

        // 3. Design & Branding
        if ($('#field_logo_provided').val()?.trim()) filled++;
        if ($('#field_color_style').val()?.trim()) filled++;
        if ($('#field_screenshots').val()) filled++; // file input

        // 4. Functionality
        if ($('#field_user_login').val()?.trim()) filled++;
        if ($('#field_backend_details').val()?.trim()) filled++;
        if ($('#field_push_notifications').val()?.trim()) filled++;
        if ($('#field_payment_integration').val()?.trim()) filled++;

        // 5. Admin Panel
        if ($('#field_admin_panel').val()?.trim()) filled++;
        if ($('#field_admin_functions').val()?.trim()) filled++;

        // 6. App Publishing
        if ($('#field_google_dev_account').val()?.trim()) filled++;
        if ($('#field_apple_dev_account').val()?.trim()) filled++;

        // 7. Budget & Timeline
        if ($('#field_budget').val()?.trim()) filled++;
        if ($('#field_launch_date').val()?.trim()) filled++;
        if ($('#field_timeline_constraints').val()?.trim()) filled++;

        // 8. Notes or Questions
        if ($('#field_extra_notes').val()?.trim()) filled++;

        // Calculate percentage
        const percent = Math.round((filled / totalFields) * 100);
        $('#formProgressBar').css('width', percent + '%').text(percent + '%');
    }

    $(document).ready(function () {
        // Initial load
        updateProgressBar();

        // Attach events for all text/textarea/date
        $('#field_company_name, #field_contact_person, #field_email, #field_phone, #field_website, #field_app_name, #field_app_description, #field_core_features, #field_color_style, #field_backend_details, #field_admin_functions, #field_budget, #field_launch_date, #field_timeline_constraints, #field_extra_notes')
            .on('input', updateProgressBar);

        // Attach events for all select dropdowns
        $('#field_platform, #field_logo_provided, #field_user_login, #field_push_notifications, #field_payment_integration, #field_admin_panel, #field_google_dev_account, #field_apple_dev_account')
            .on('change', updateProgressBar);

        // File upload change
        $('#field_screenshots').on('change', updateProgressBar);
    });
</script>


<?php include './partials/layouts/layoutBottom.php'; ?>
