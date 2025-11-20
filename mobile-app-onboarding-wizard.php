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
        /* border-radius: 10px !important; */
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
</style>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-24 justify-content-between">
        <div class="d-flex align-self-end">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; </a>     
            <h6 class="fw-semibold mb-0">Mobile App Development Onboarding Form</h6>
        </div>
<?php
    $session_user_id = $_SESSION['user_id'];
    $prod_id = intval($_GET['prod_id']);
    $web_id = intval($_GET['id']);
    
    $get_type = "SELECT * FROM websites where id = $web_id";
    $type_result = $conn->query($get_type);
    $row_type = $type_result->fetch_assoc();
    $type = $row_type['type'];
    
    if($type == "package"){
        $sql = "SELECT * FROM package where id = $prod_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $template = $row['template'];     
    }
    elseif($type == "product"){
        $sql = "SELECT * FROM products where id = $prod_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $template = $row['template'];   
    }

    // Fetch all past records of this user
    $prevRecords = [];
    //$stmt = $conn->prepare("SELECT id, name FROM json WHERE user_id = ? AND template = ?");
    $stmt = $conn->prepare("SELECT id, name, prefill_name FROM json WHERE user_id = ? AND template = ? AND prefill_name IS NOT NULL AND prefill_name != ''");
    $stmt->bind_param("is", $session_user_id, $template);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $decoded = json_decode($row['name'], true);
        if ($decoded && isset($decoded['company_name']['value'])) {
            $prevRecords[] = [
                'id' => $row['id'],
                'data' => $decoded,
                'prefill_name'=> $row['prefill_name']
            ];
        }
    }
    $stmt->close();

    // Determine if admin/dev is viewing another user's data
    $target_user_id = $session_user_id;

    if (isset($_GET['id']) && in_array($session_user_id, [1, 2, 7])) {
        $website_id = intval($_GET['id']);
        
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

        $allow_prefill = isset($_POST['allow_prefill']) && $_POST['allow_prefill'] === 'on';
        $prefill_name = $allow_prefill ? ($_POST['prefill_name'] ?? '') : '';

        // Handle file upload for screenshots
        if (!empty($_FILES['screenshots']['name'])) {
            $uploadDir = 'Uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $_FILES['screenshots']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['screenshots']['tmp_name'], $targetPath)) {
                $screenshots = $targetPath;
            }
        } else {
            $screenshots = $_POST['screenshots_existing'] ?? '';
        }

        function createField($value) {
            return [
                'value' => $value,
                'status' => 'pending'
            ];
        }
    
        $data = json_encode([
            'company_name' => createField($company_name),
            'contact_person' => createField($contact_person),
            'email' => createField($email),
            'phone' => createField($phone),
            'website' => createField($website),
            'app_name' => createField($app_name),
            'platform' => createField($platform),
            'app_description' => createField($app_description),
            'core_features' => createField($core_features),
            'logo_provided' => createField($logo_provided),
            'color_style' => createField($color_style),
            'screenshots' => createField($screenshots),
            'user_login' => createField($user_login),
            'backend_details' => createField($backend_details),
            'push_notifications' => createField($push_notifications),
            'payment_integration' => createField($payment_integration),
            'admin_panel' => createField($admin_panel),
            'admin_functions' => createField($admin_functions),
            'google_dev_account' => createField($google_dev_account),
            'apple_dev_account' => createField($apple_dev_account),
            'budget' => createField($budget),
            'launch_date' => createField($launch_date),
            'timeline_constraints' => createField($timeline_constraints),
            'extra_notes' => createField($extra_notes),
            'prefill_name' => createField($prefill_name),
        ]);
    
        $website_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
        $check = $conn->prepare("SELECT id FROM json WHERE user_id = ? AND website_id = ? AND template = ?");
        $check->bind_param("iis", $user_id, $website_id, $template);
        $check->execute();
        $check->store_result();
    
        if ($check->num_rows > 0) {
            $update = $conn->prepare("UPDATE json SET name = ?, prefill_name = ? WHERE user_id = ? AND website_id = ? AND template = ?");
            $update->bind_param("ssiis", $data, $prefill_name, $user_id, $website_id, $template);
            $success = $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO json (name, user_id, website_id, template, prefill_name) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("siiss", $data, $user_id, $website_id, $template, $prefill_name);
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

    if (!empty($prevRecords)): ?>
        <div class="d-flex justify-content-center justify-content-md-end" style="margin-bottom:0;">
            <div class="p-3 rounded shadow-sm w-100 w-md-40" 
                style="font-size: 0.85rem; background-color: #fffbea; max-width: 600px; text-align:left;">
                <h6 class="fw-bold text-dark mb-3" style="font-size: 0.9rem;">
                    Fill Values From Previous Wizards
                </h6>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($prevRecords as $record): ?>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" 
                                class="form-check-input load-record" 
                                style="transform: scale(1.1);" 
                                data-record='<?php echo json_encode($record['data']); ?>'
                                id="rec_<?php echo $record['id']; ?>">
                            <label for="rec_<?php echo $record['id']; ?>" class="form-check-label ms-1" style="font-size: 0.9rem;">
                                <?php echo htmlspecialchars($record['prefill_name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif;

    function renderFieldExtended($fieldName, $savedData, $user_role, $label = '', $placeholder = '', $type = 'text', $options = []) {
        $val = $savedData[$fieldName]['value'] ?? '';
        $status = $savedData[$fieldName]['status'] ?? 'pending';
        $inputId = 'field_' . htmlspecialchars($fieldName);
        $isAdmin = in_array($user_role, [1, 2, 7]);
        $isReadonly = ($isAdmin || (!$isAdmin && ($status === 'approved' || $status === 'rejected'))) ? 'readonly' : '';
        $isDisabled = ($isAdmin || (!$isAdmin && ($status === 'approved' || $status === 'rejected'))) ? 'disabled' : '';
        $dataValue = is_array($val) ? implode(',', $val) : $val;
        $dataOptions = !empty($options) ? 'data-options="' . htmlspecialchars(implode(',', $options)) . '"' : '';

        echo '<div class="form-group mb-4">';
        echo '<div class="d-flex align-items-start">';

        if ($isAdmin) {
            echo '<div class="me-3 d-flex align-items-center pt-4">';
            echo '<input class="form-check-input bulk-approve-checkbox custom-checkbox custom-checkbox-yellow mt-0" type="checkbox" value="' . htmlspecialchars($fieldName) . '" id="chk_' . htmlspecialchars($fieldName) . '">';
            echo '</div>';
        }

        echo '<div class="flex-grow-1">';

        if ($label) {
            echo '<label for="' . $inputId . '" class="form-label">' . htmlspecialchars($label) . '</label>';
        }

        $styleClass = $status === 'approved' ? 'field-approved' : ($status === 'rejected' ? 'field-rejected' : '');
        echo '<div class="input-group">';

        $copyButton = '';
        if (in_array($type, ['text', 'textarea', 'select', 'date'])) {
            $copyButton = '<button type="button" class="btn btn-outline-secondary btn-sm copy-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Copy Value"><i class="fa fa-copy"></i></button>';
        }

        if ($type === 'text' || $type === 'email' || $type === 'url' || $type === 'date') {
            echo '<input type="' . $type . '" class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
            if ($copyButton) echo $copyButton;
        } elseif ($type === 'textarea') {           
            echo '<textarea class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $isReadonly . '>' . htmlspecialchars($val) . '</textarea>';
            if ($copyButton) echo $copyButton;
        } elseif ($type === 'radio') {
            foreach ($options as $option) {
                $checked = ($val == $option) ? 'checked' : '';
                echo '<div class="form-check form-check-inline">';
                echo '<input class="form-check-input mt-4" type="radio" id="' . $inputId . '_' . $option . '" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . $isDisabled . '>';
                echo '<label class="form-check-label" for="' . $inputId . '_' . $option . '">' . htmlspecialchars($option) . '</label>';
                echo '</div>';
            }
        } elseif ($type === 'checkbox') {
            $valArray = is_array($val) ? $val : explode(',', str_replace(' ', '', $val));
            foreach ($options as $option) {
                $checked = in_array($option, $valArray) ? 'checked' : '';
                echo '<div class="form-check form-check-inline">';
                echo '<input class="form-check-input mt-4" type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . $isDisabled . '>';
                echo '<label class="form-check-label">' . htmlspecialchars($option) . '</label>';
                echo '</div>';
            }
        } elseif ($type === 'file') {
            echo '<input type="file" class="form-control w-100 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isDisabled . '>';
            echo '<input type="hidden" name="' . htmlspecialchars($fieldName) . '_existing" value="' . htmlspecialchars($val) . '">';
            if (($val)) {
                echo '<div class="mt-3 file-preview">';
                echo '<label class="d-block fw-bold">Uploaded File:</label>';
                $ext = strtolower(pathinfo($val, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    echo '<img src="' . htmlspecialchars($val) . '" alt="File" style="max-height: 120px; border: 1px solid #ccc; padding: 6px;">';
                } else {
                    echo '<a href="' . htmlspecialchars($val) . '" target="_blank">' . htmlspecialchars(basename($val)) . '</a>';
                }
                echo '</div>';
            }
        } elseif ($type === 'select') {
            echo '<select class="form-control w-85 h-auto ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isDisabled . '>';
            echo '<option value="">-- Select an option --</option>';
            foreach ($options as $option) {
                $selected = ($val == $option) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars(ucfirst($option)) . '</option>';
            }
            echo '</select>';
            if ($copyButton) echo $copyButton;
        }

        if ($isAdmin) {
            echo '<div class="btn-group ms-1 mt-2">';
            echo '<button type="button" class="btn btn-sm edit-icon" style="background-color: #FEC700; color: #fff;" data-field="' . htmlspecialchars($fieldName) . '" title="Edit">Edit</button>';
            echo '<button type="button" class="btn btn-sm update-icon d-none" style="background-color: #00B4D8; color: white;" data-field="' . htmlspecialchars($fieldName) . '" title="Update">Update</button>';
            echo '<button type="button" class="btn btn-success btn-sm approve-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Approve">Approve</button>';
            echo '<button type="button" class="btn btn-danger btn-sm reject-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Reject">Reject</button>';
            echo '</div>';
        }

        if (!$isAdmin && $status === 'rejected') {
            echo '<button type="button" class="input-group-text text-warning edit-btn ms-2" title="Edit"
                data-field="' . htmlspecialchars($fieldName) . '"
                data-type="' . htmlspecialchars($type) . '"
                data-value="' . htmlspecialchars($dataValue) . '"
                ' . $dataOptions . '>
                &#9998;
            </button>';
        } elseif (!$isAdmin && $status === 'approved') {
            echo '<span class="input-group-text text-success">&#10004;</span>';
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
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
            $uploadDir = 'Uploads/';
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

        if (!empty($_FILES['file'])) {
            $fileTmp = $_FILES['file']['tmp_name'];
            $fileName = basename($_FILES['file']['name']);
            $uploadDir = 'Uploads/';
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
        } else {
            $value = $_POST['value'] ?? '';
            $decoded[$field]['value'] = $value;
            $decoded[$field]['status'] = 'pending';
        }

        $updatedJson = json_encode($decoded);
        $updateStmt = $conn->prepare("UPDATE json SET name = ? WHERE website_id = ?");
        $updateStmt->bind_param("si", $updatedJson, $website_id);
        $updateStmt->execute();
        $updateStmt->close();

        echo 'updated';
        exit;
    }
?>

    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">               
        <div class="card-body p-40">
            <div class="row justify-content-center">
                <div class="col-xxl-10">
                <section class="wizard-section">
                    <div class="row no-gutters">
                        <div class="col-lg-12">
                            <div class="form-wizard">
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
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="form-check d-flex align-items-center m-0">
                                                <input type="checkbox" class="form-check-input me-2" id="select_all_admin" style="margin-top: 0;">
                                                <label for="select_all_admin" class="form-check-label fw-bold m-0">Select / Deselect All</label>
                                            </div>
                                            <div>
                                                <button type="button" id="bulkApproveBtn" class="btn btn-success btn-sm">Bulk Approve</button>
                                                <button type="button" id="bulkRejectBtn" class="btn btn-danger btn-sm">Bulk Reject</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <h5>1. Client & Business Info</h5>
                                    <?php
                                    renderFieldExtended('company_name', $savedData, $user_role, 'Business Name', '', 'text');
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
                                        <!-- NEW: Allow Prefill Data -->
                                        <?php
                                        if ($user_role != 1 && $user_role != 2) {
                                        $prefillName = $savedData['prefill_name']['value'] ?? '';
                                        $allowPrefill = !empty($prefillName);
                                        ?>
                                        <div class="mt-5 p-20">
                                            <div class="form-check">
                                                <input class="form-check-input mt-4 me-4" type="checkbox" id="allow_prefill" name="allow_prefill" <?= $allowPrefill ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold" for="allow_prefill">
                                                    Allow users to save prefill data
                                                </label>
                                            </div>

                                            <div id="prefill_name_wrapper" class="mt-3" style="display:<?= $allowPrefill ? 'block' : 'none' ?>;">
                                                <?php
                                                renderFieldExtended(
                                                    'prefill_name',
                                                    $savedData,
                                                    $user_role,
                                                    'Prefill name (will appear in “Fill Values From Previous Wizards”)',
                                                    '',
                                                    'text'
                                                );
                                            }
                                                ?>
                                            </div>
                                        </div>
                                    <?php if (in_array($user_role, [8])): ?>
                                        <input type="submit" name="save" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block mt-4" value="Save" >
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

<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content p-20 rounded" style="background:#fff; max-width:500px; margin:auto;">
        <span class="close-btn float-end" title="Close" style="cursor:pointer;">&times;</span>
        <h5 class="mb-3">Edit Field</h5>
        <div id="editFieldContainer" class="mb-3"></div>
        <button type="button" class="btn lufera-bg btn-warning w-100" id="saveEditBtn">Save</button>
    </div>
</div>
<script>
    document.getElementById('allow_prefill').addEventListener('change', function () {
        document.getElementById('prefill_name_wrapper').style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            const inp = document.getElementById('field_prefill_name');
            if (inp) inp.value = '';
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    let currentField = '';
    let currentType = 'text';
    const modal = document.getElementById('editModal');
    const fieldContainer = document.getElementById('editFieldContainer');
    const saveBtn = document.getElementById('saveEditBtn');
    const closeBtn = document.querySelector('.close-btn');
    const form = document.getElementById('myForm');
    const websiteId = new URLSearchParams(window.location.search).get('id');

    // Store initial form values
    const initialValues = {};
    const fields = [
        { id: 'company_name', type: 'text' },
        { id: 'contact_person', type: 'text' },
        { id: 'email', type: 'email' },
        { id: 'phone', type: 'text' },
        { id: 'website', type: 'url' },
        { id: 'app_name', type: 'text' },
        { id: 'platform', type: 'select' },
        { id: 'app_description', type: 'textarea' },
        { id: 'core_features', type: 'textarea' },
        { id: 'logo_provided', type: 'select' },
        { id: 'color_style', type: 'text' },
        { id: 'screenshots', type: 'file' },
        { id: 'user_login', type: 'select' },
        { id: 'backend_details', type: 'textarea' },
        { id: 'push_notifications', type: 'select' },
        { id: 'payment_integration', type: 'select' },
        { id: 'admin_panel', type: 'select' },
        { id: 'admin_functions', type: 'textarea' },
        { id: 'google_dev_account', type: 'select' },
        { id: 'apple_dev_account', type: 'select' },
        { id: 'budget', type: 'text' },
        { id: 'launch_date', type: 'date' },
        { id: 'timeline_constraints', type: 'textarea' },
        { id: 'extra_notes', type: 'textarea' }
    ];

    fields.forEach(field => {
        if (field.type === 'file') {
            const hiddenInput = document.querySelector(`input[name="${field.id}_existing"]`);
            initialValues[field.id] = hiddenInput ? hiddenInput.value : '';
        } else {
            const input = document.getElementById(`field_${field.id}`);
            initialValues[field.id] = input ? input.value : '';
        }
    });

    // Edit button click handler for non-admin users
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentField = btn.dataset.field;
            currentType = btn.dataset.type || 'text';
            const value = btn.dataset.value || '';
            const options = btn.dataset.options ? btn.dataset.options.split(',') : [];

            fieldContainer.innerHTML = '';

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
            } else if (currentType === 'select') {
                let selectHTML = `<select id="modalInput" class="form-control">`;
                options.forEach(opt => {
                    const selected = opt.trim() === value ? 'selected' : '';
                    selectHTML += `<option value="${opt.trim()}" ${selected}>${opt.trim()}</option>`;
                });
                selectHTML += `</select>`;
                fieldContainer.innerHTML = selectHTML;
            } else if (currentType === 'date') {
                fieldContainer.innerHTML = `<input type="date" id="modalInput" class="form-control" value="${value}" />`;
            } else {
                fieldContainer.innerHTML = `<input type="text" id="modalInput" class="form-control" value="${value}" />`;
            }

            modal.style.display = 'flex';
        });
    });

    // Save button for modal
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

    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    // Approve/Reject button handlers
    
    $(document).ready(function () {
        const websiteId = new URLSearchParams(window.location.search).get('id');

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
    });
    // Bulk approve/reject
    jQuery(document).ready(function () {
        function bulkUpdate(status) {
            const fields = jQuery('.bulk-approve-checkbox:checked').map(function () {
                return jQuery(this).val();
            }).get();

            if (fields.length === 0) {
                Swal.fire('No fields selected', '', 'info');
                return;
            }

            jQuery.post('json_status_update.php?id=' + websiteId, {
                fields: fields,
                status: status
            }, function () {
                Swal.fire('Success', 'Fields updated.', 'success').then(() => location.reload());
            }).fail(function () {
                Swal.fire('Error', 'Bulk update failed.', 'error');
            });
        }

        jQuery('#bulkApproveBtn').click(function () {
            bulkUpdate('approved');
        });

        jQuery('#bulkRejectBtn').click(function () {
            bulkUpdate('rejected');
        });

        // Edit/Update icons for admin
        jQuery('.edit-icon').click(function () {
            const field = jQuery(this).data('field');
            const input = jQuery('#field_' + field);
            const inputType = input.attr('type');
            input.prop('readonly', false).focus();
            jQuery('input[type="radio"][name="' + field + '"]').prop('disabled', false);
            jQuery('input[type="checkbox"][name="' + field + '[]"]').prop('disabled', false);
            input.prop('disabled', false);
            jQuery('.update-icon[data-field="' + field + '"]').removeClass('d-none');
            jQuery(this).addClass('d-none');
        });

        jQuery('.update-icon').click(function () {
            const field = jQuery(this).data('field');
            const input = jQuery('#field_' + field);
            const inputType = input.attr('type');
            let value;

            if (inputType === 'file') {
                const file = input[0].files[0];
                const formData = new FormData();
                formData.append('inline_update', true);
                formData.append('field', field);
                formData.append('file', file);

                jQuery.ajax({
                    type: 'POST',
                    url: '',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function () {
                        Swal.fire('Success', 'File updated.', 'success').then(() => location.reload());
                    },
                    error: function () {
                        Swal.fire('Error', 'File update failed.', 'error');
                    }
                });
                return;
            }

            if (input.length === 0 && jQuery('input[name="' + field + '[]"]').length > 0) {
                let selected = [];
                jQuery('input[name="' + field + '[]"]:checked').each(function () {
                    selected.push(jQuery(this).val());
                });
                value = selected.join(',');
            } else if (jQuery('input[name="' + field + '"]:checked').length > 0) {
                value = jQuery('input[name="' + field + '"]:checked').val();
            } else {
                value = input.val();
            }

            jQuery.post('', {
                inline_update: true,
                field: field,
                value: value
            }, function (res) {
                if (res === 'updated') {
                    Swal.fire('Success', 'Field updated.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Success', 'Field updated.', 'success').then(() => location.reload());
                }
            }).fail(function () {
                Swal.fire('Error', 'Server error occurred.', 'error');
            });
        });
    });

    // Progress bar update
    function updateProgressBar() {
        let filled = 0;
        const totalFields = 24;

        function isFilled(selector, isFile = false) {
            if (isFile) {
                const input = document.querySelector(selector);
                const existing = document.querySelector('input[name="' + selector.replace('#field_', '') + '_existing"]');
                return (input && input.value.trim()) || (existing && existing.value.trim());
            }
            const element = document.querySelector(selector);
            return element && element.value.trim();
        }

        if (isFilled('#field_company_name')) filled++;
        if (isFilled('#field_contact_person')) filled++;
        if (isFilled('#field_email')) filled++;
        if (isFilled('#field_phone')) filled++;
        if (isFilled('#field_website')) filled++;
        if (isFilled('#field_app_name')) filled++;
        if (isFilled('#field_platform')) filled++;
        if (isFilled('#field_app_description')) filled++;
        if (isFilled('#field_core_features')) filled++;
        if (isFilled('#field_logo_provided')) filled++;
        if (isFilled('#field_color_style')) filled++;
        if (isFilled('#field_screenshots', true)) filled++;
        if (isFilled('#field_user_login')) filled++;
        if (isFilled('#field_backend_details')) filled++;
        if (isFilled('#field_push_notifications')) filled++;
        if (isFilled('#field_payment_integration')) filled++;
        if (isFilled('#field_admin_panel')) filled++;
        if (isFilled('#field_admin_functions')) filled++;
        if (isFilled('#field_google_dev_account')) filled++;
        if (isFilled('#field_apple_dev_account')) filled++;
        if (isFilled('#field_budget')) filled++;
        if (isFilled('#field_launch_date')) filled++;
        if (isFilled('#field_timeline_constraints')) filled++;
        if (isFilled('#field_extra_notes')) filled++;

        const percent = Math.round((filled / totalFields) * 100);
        const progressBar = document.getElementById('formProgressBar');
        progressBar.style.width = percent + '%';
        progressBar.textContent = percent + '%';
    }

    // Load record handler
    document.querySelectorAll('.load-record').forEach(cb => {
        cb.addEventListener('change', function () {
            // Uncheck all other checkboxes
            document.querySelectorAll('.load-record').forEach(other => {
                if (other !== this) other.checked = false;
            });

            // Clear existing previews
            document.querySelectorAll('.file-preview, .record-preview').forEach(el => el.remove());

            if (this.checked) {
                try {
                    const data = JSON.parse(this.dataset.record);
                    fields.forEach(field => {
                        const input = document.getElementById(`field_${field.id}`);
                        const value = data[field.id]?.value || '';
                        
                        if (field.type !== 'file' && input) {
                            input.value = value;
                        } else if (field.id === 'screenshots' && value) {
                            const logoInput = document.getElementById('field_screenshots');
                            const logoGroup = logoInput.closest('.form-group');
                            logoGroup.querySelectorAll('.file-preview, .record-preview').forEach(el => el.remove());
                            logoInput.value = ''; // Clear file input
                            const ext = value.split('.').pop().toLowerCase();
                            let previewHtml = '';
                            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                                previewHtml = `<div class="record-preview mt-3"><label class="d-block fw-bold">Uploaded File:</label><img src="${value}" alt="File" style="max-height:120px; border:1px solid #ccc; padding:6px;"></div>`;
                            } else {
                                previewHtml = `<div class="record-preview mt-3"><label class="d-block fw-bold">Uploaded File:</label><a href="${value}" target="_blank">${value}</a></div>`;
                            }
                            logoGroup.insertAdjacentHTML('beforeend', previewHtml);
                            const hiddenLogo = document.querySelector('input[name="screenshots_existing"]');
                            if (hiddenLogo) hiddenLogo.value = value;
                        }
                    });
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load record data. Please try again.'
                    });
                }
            } else {
                // Restore initial values
                fields.forEach(field => {
                    const input = document.getElementById(`field_${field.id}`);
                    const value = initialValues[field.id] || '';

                    if (field.type !== 'file' && input) {
                        input.value = value;
                    } else if (field.id === 'screenshots') {
                        const logoInput = document.getElementById('field_screenshots');
                        const logoGroup = logoInput.closest('.form-group');
                        logoGroup.querySelectorAll('.file-preview, .record-preview').forEach(el => el.remove());
                        logoInput.value = ''; // Clear file input
                        const hiddenLogo = document.querySelector('input[name="screenshots_existing"]');
                        if (hiddenLogo) hiddenLogo.value = value;
                        if (value) {
                            const ext = value.split('.').pop().toLowerCase();
                            let previewHtml = '';
                            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                                previewHtml = `<div class="file-preview mt-3"><label class="d-block fw-bold">Uploaded File:</label><img src="${value}" alt="File" style="max-height:120px; border:1px solid #ccc; padding:6px;"></div>`;
                            } else {
                                previewHtml = `<div class="file-preview mt-3"><label class="d-block fw-bold">Uploaded File:</label><a href="${value}" target="_blank">${value}</a></div>`;
                            }
                            logoGroup.insertAdjacentHTML('beforeend', previewHtml);
                        }
                    }
                });
            }

            updateProgressBar();
        });
    });

    // Select all checkbox handler
    const selectAllCheckbox = document.getElementById('select_all_admin');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.bulk-approve-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
        });

        document.querySelectorAll('.bulk-approve-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                const allChecked = document.querySelectorAll('.bulk-approve-checkbox:checked').length === document.querySelectorAll('.bulk-approve-checkbox').length;
                selectAllCheckbox.checked = allChecked;
            });
        });
    }

    // Form validation
    const emailField = document.getElementById('field_email');
    const urlField = document.getElementById('field_website');

    emailField.addEventListener('invalid', function (e) {
        e.preventDefault();
        if (!emailField.validity.valid) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Email',
                text: 'Please type a valid email address.'
            });
        }
    });

    urlField.addEventListener('invalid', function (e) {
        e.preventDefault();
        if (!urlField.validity.valid) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid URL',
                text: 'Please type a valid website URL (e.g. https://example.com).'
            });
        }
    });

    form.addEventListener('submit', function (e) {
        if (!emailField.checkValidity() || !urlField.checkValidity()) {
            e.preventDefault();
        }
        document.querySelectorAll('.load-record').forEach(cb => cb.checked = false);
    });

    // Update progress bar on input changes
    document.querySelectorAll('#field_company_name, #field_contact_person, #field_email, #field_phone, #field_website, #field_app_name, #field_app_description, #field_core_features, #field_color_style, #field_backend_details, #field_admin_functions, #field_budget, #field_launch_date, #field_timeline_constraints, #field_extra_notes')
        .forEach(el => el.addEventListener('input', updateProgressBar));
    document.querySelectorAll('#field_platform, #field_logo_provided, #field_user_login, #field_push_notifications, #field_payment_integration, #field_admin_panel, #field_google_dev_account, #field_apple_dev_account')
        .forEach(el => el.addEventListener('change', updateProgressBar));
    document.querySelector('#field_screenshots').addEventListener('change', updateProgressBar);

    // Initial progress bar update
    updateProgressBar();
});
</script>
<script>
    $(document).ready(function() {
        $('.copy-btn').click(function() {
            const field = $(this).data('field');
            const input = $('#field_' + field);
            let value = '';

            if (input.is('select')) {
                value = input.find('option:selected').text() || input.val();
            } else {
                value = input.val();
            }

            if (value) {
                navigator.clipboard.writeText(value).then(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: 'Value copied to clipboard.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = value;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: 'Value copied to clipboard.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                });
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'No value',
                    text: 'No value to copy.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    });
</script>
<?php include './partials/layouts/layoutBottom.php'; ?>