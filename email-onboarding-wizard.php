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
</style>

<?php
    $session_user_id = $_SESSION['user_id'];
    $prod_id = intval($_GET['prod_id']);
    $web_id = intval($_GET['id']);
    
    $get_type = "SELECT * FROM websites WHERE id = $web_id";
    $type_result = $conn->query($get_type);
    $row_type = $type_result->fetch_assoc();
    $type = $row_type['type'];
    
    if ($type == "package") {
        $sql = "SELECT * FROM package WHERE id = $prod_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $template = $row['template'];     
    } elseif ($type == "product") {
        $sql = "SELECT * FROM products WHERE id = $prod_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $template = $row['template'];   
    }

    // Fetch all past records of this user
    $prevRecords = [];
    $stmt = $conn->prepare("SELECT id, name FROM json WHERE user_id = ? AND template = ?");
    $stmt->bind_param("is", $session_user_id, $template);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $decoded = json_decode($row['name'], true);
        if ($decoded && isset($decoded['organization_name']['value'])) { // Updated key
            $prevRecords[] = [
                'id' => $row['id'],
                'data' => $decoded
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
        $organization_name = $_POST['organization_name'] ?? ''; // Updated key
        $primary_contact = $_POST['primary_contact'] ?? ''; // Updated key
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $website = $_POST['website'] ?? '';
        $service_needed = $_POST['service_needed'] ?? '';
        $details = $_POST['details'] ?? '';
        $industry = $_POST['industry'] ?? ''; // New field
        
        function createField($value) {
            return [
                'value' => $value,
                'status' => 'pending'
            ];
        }
        
        $data = json_encode([
            'organization_name' => createField($organization_name), // Updated key
            'primary_contact' => createField($primary_contact), // Updated key
            'email' => createField($email),
            'phone' => createField($phone),
            'website' => createField($website),
            'service_needed' => createField($service_needed),
            'details' => createField($details),
            'industry' => createField($industry), // New field
            'has_domain' => createField($_POST['has_domain'] ?? ''),
            'domain_name' => createField($_POST['domain_name'] ?? ''),
            'use_domain_for_email' => createField($_POST['use_domain_for_email'] ?? ''),
            'email_accounts' => createField($_POST['email_accounts'] ?? ''),
            'email_format' => createField($_POST['email_format'] ?? ''),
            'user_list' => createField($_POST['user_list'] ?? ''),
            'provider' => createField($_POST['provider'] ?? ''),
            'existing_settings' => createField($_POST['existing_settings'] ?? ''),
            'notes' => createField($_POST['notes'] ?? ''),
        ]);

        $website_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $check = $conn->prepare("SELECT id FROM json WHERE user_id = ? AND website_id = ? AND template = ?");
        $check->bind_param("iis", $user_id, $website_id, $template);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $update = $conn->prepare("UPDATE json SET name = ? WHERE user_id = ? AND website_id = ? AND template = ?");
            $update->bind_param("siis", $data, $user_id, $website_id, $template);
            $success = $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO json (name, user_id, website_id, template) VALUES (?, ?, ?, ?)");
            $insert->bind_param("siis", $data, $user_id, $website_id, $template);
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
        <div class="ms-10">
            <div class="form-check-group">
                <?php foreach ($prevRecords as $record): ?>
                    <div class="form-check form-check-inline">
                        <input type="checkbox" 
                               class="form-check-input load-record mt-4" 
                               data-record='<?php echo json_encode($record['data']); ?>'
                               id="rec_<?php echo $record['id']; ?>">
                        <label for="rec_<?php echo $record['id']; ?>" class="form-check-label">
                            <?php echo htmlspecialchars($record['data']['organization_name']['value']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
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

        if ($type === 'text' || $type === 'email' || $type === 'url' || $type === 'date') {
            echo '<input type="' . $type . '" class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
        } elseif ($type === 'textarea') {           
            echo '<textarea class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $isReadonly . '>' . htmlspecialchars($val) . '</textarea>';
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
            echo '<input type="file" class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isDisabled . '>';

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
        } elseif ($type === 'select') {
            echo '<select class="form-control w-85 h-auto ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isDisabled . '>';
            echo '<option value="">-- Select an option --</option>';
            foreach ($options as $option) {
                $selected = ($val == $option) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars(ucfirst($option)) . '</option>';
            }
            echo '</select>';
        }

        if ($isAdmin) {
            echo '<div class="btn-group mt-2 ms-1">';
            echo '<button type="button" class="btn btn-sm edit-icon" style="background-color: #FEC700; color: black;" data-field="' . htmlspecialchars($fieldName) . '" title="Edit">&#9998;</button>';
            echo '<button type="button" class="btn btn-sm update-icon d-none" style="background-color: #00B4D8; color: white;" data-field="' . htmlspecialchars($fieldName) . '" title="Update">&#128190;</button>';
            echo '<button type="button" class="btn btn-success btn-sm approve-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Approve">&#10004;</button>';
            echo '<button type="button" class="btn btn-danger btn-sm reject-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Reject">&#10006;</button>';
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

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Email Services Onboarding Form</h6>
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

                                    <h5>1. Company Information</h5>
                                    <?php
                                        renderFieldExtended('organization_name', $savedData, $user_role, 'Organization Name', '', 'text');
                                        renderFieldExtended('primary_contact', $savedData, $user_role, 'Primary Contact', '', 'text');
                                        renderFieldExtended('email', $savedData, $user_role, 'Email', '', 'email');
                                        renderFieldExtended('phone', $savedData, $user_role, 'Phone', '', 'text');
                                        renderFieldExtended('website', $savedData, $user_role, 'Website', '', 'url');
                                        renderFieldExtended('industry', $savedData, $user_role, 'Industry', 'e.g., Technology, Finance', 'text'); // New field
                                    ?>
                                    <h5>2. Purpose of Email Services</h5>
                                    <?php
                                        renderFieldExtended('service_needed', $savedData, $user_role, 'Type of Service Needed', '', 'select', ['New Email Setup', 'Email Migration', 'Email Reselling', 'Email Marketing Setup', 'Other']);
                                        renderFieldExtended('details', $savedData, $user_role, 'Details or Requirements', '', 'textarea');
                                    ?> 
                                    <h5>3. Domain Information</h5>
                                    <?php
                                        renderFieldExtended('has_domain', $savedData, $user_role, 'Do you have a domain?', '', 'select', ['Yes', 'No']);
                                        renderFieldExtended('domain_name', $savedData, $user_role, 'If Yes, provide domain name', '', 'text');
                                        renderFieldExtended('use_domain_for_email', $savedData, $user_role, 'Do you want to use this domain for email?', '', 'select', ['Yes', 'No']);
                                    ?>
                                    <h5>4. User Details</h5>
                                    <?php
                                        renderFieldExtended('email_accounts', $savedData, $user_role, 'How many email accounts do you need?', '', 'text');
                                        renderFieldExtended('email_format', $savedData, $user_role, 'Preferred email ID format (e.g. name@domain.com)', '', 'text');
                                        renderFieldExtended('user_list', $savedData, $user_role, 'List of users (if available)', '', 'textarea');
                                    ?>
                                    <h5>5. Technical Information</h5>
                                    <?php
                                        renderFieldExtended('provider', $savedData, $user_role, 'Preferred Email Provider (e.g. Zoho, Google Workspace, Microsoft 365, Others)', '', 'text');
                                        renderFieldExtended('existing_settings', $savedData, $user_role, 'Any existing email configuration or settings?', '', 'textarea');
                                    ?>
                                    <h5>6. Additional Notes or Questions</h5>
                                    <?php
                                        renderFieldExtended('notes', $savedData, $user_role, 'Anything else we should know?', '', 'textarea');
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
                } else {
                    fieldContainer.innerHTML = `<input type="text" id="modalInput" class="form-control" value="${value}" />`;
                }

                modal.style.display = 'flex';
            });
        });

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
    });
</script>

<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content p-20 rounded" style="background:#fff; max-width:500px; margin:auto;">
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

        $('.edit-icon').click(function () {
            const field = $(this).data('field');
            const input = $('#field_' + field);
            input.prop('readonly', false).focus();
            $('input[type="radio"][name="' + field + '"]').prop('disabled', false);
            $('input[type="checkbox"][name="' + field + '[]"]').prop('disabled', false);
            input.prop('disabled', false);
            $('.update-icon[data-field="' + field + '"]').removeClass('d-none');
            $(this).addClass('d-none');
        });

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
                        Swal.fire('Success', 'File updated.', 'success').then(() => location.reload());
                    }
                });
                return;
            }

            if (input.length === 0 && $('input[name="' + field + '[]"]').length > 0) {
                let selected = [];
                $('input[name="' + field + '[]"]:checked').each(function () {
                    selected.push($(this).val());
                });
                value = selected.join(',');
            } else if ($('input[name="' + field + '"]:checked').length > 0) {
                value = $('input[name="' + field + '"]:checked').val();
            } else {
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
                    Swal.fire('Success', 'Field updated.', 'success').then(() => location.reload());
                }
            }).fail(function () {
                Swal.fire('Error', 'Server error occurred.', 'error');
            });
        });
    });
</script>

<script>
    function updateProgressBar() {
        let filled = 0;
        const totalFields = 17; // Updated to include new 'industry' field

        if ($('#field_organization_name').val()?.trim()) filled++;
        if ($('#field_primary_contact').val()?.trim()) filled++;
        if ($('#field_email').val()?.trim()) filled++;
        if ($('#field_phone').val()?.trim()) filled++;
        if ($('#field_website').val()?.trim()) filled++;
        if ($('#field_service_needed').val()?.trim()) filled++;
        if ($('#field_details').val()?.trim()) filled++;
        if ($('#field_industry').val()?.trim()) filled++; // New field
        if ($('#field_has_domain').val()?.trim()) filled++;
        if ($('#field_domain_name').val()?.trim()) filled++;
        if ($('#field_use_domain_for_email').val()?.trim()) filled++;
        if ($('#field_email_accounts').val()?.trim()) filled++;
        if ($('#field_email_format').val()?.trim()) filled++;
        if ($('#field_user_list').val()?.trim()) filled++;
        if ($('#field_provider').val()?.trim()) filled++;
        if ($('#field_existing_settings').val()?.trim()) filled++;
        if ($('#field_notes').val()?.trim()) filled++;

        const percent = Math.round((filled / totalFields) * 100);
        $('#formProgressBar').css('width', percent + '%').text(percent + '%');
    }

    $(document).ready(function () {
        updateProgressBar();

        $('#field_organization_name, #field_primary_contact, #field_email, #field_phone, #field_website, #field_details, #field_industry, #field_domain_name, #field_email_accounts, #field_email_format, #field_user_list, #field_provider, #field_existing_settings, #field_notes')
            .on('input', updateProgressBar);

        $('#field_service_needed, #field_has_domain, #field_use_domain_for_email')
            .on('change', updateProgressBar);
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const emailField = document.getElementById('field_email');
    const urlField = document.getElementById('field_website');
    const form = document.getElementById('myForm');

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
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.load-record').forEach(cb => {
        cb.addEventListener('change', function () {
            const form = document.getElementById('myForm');

            document.querySelectorAll('.load-record').forEach(other => {
                if (other !== this) other.checked = false;
            });

            if (this.checked) {
                const data = JSON.parse(this.dataset.record);

                if (data.organization_name?.value) document.getElementById('field_organization_name').value = data.organization_name.value;
                if (data.primary_contact?.value) document.getElementById('field_primary_contact').value = data.primary_contact.value;
                if (data.email?.value) document.getElementById('field_email').value = data.email.value;
                if (data.phone?.value) document.getElementById('field_phone').value = data.phone.value;
                if (data.website?.value) document.getElementById('field_website').value = data.website.value;
                if (data.industry?.value) document.getElementById('field_industry').value = data.industry.value;
                if (data.service_needed?.value) document.getElementById('field_service_needed').value = data.service_needed.value;
                if (data.details?.value) document.getElementById('field_details').value = data.details.value;
                if (data.has_domain?.value) document.getElementById('field_has_domain').value = data.has_domain.value;
                if (data.domain_name?.value) document.getElementById('field_domain_name').value = data.domain_name.value;
                if (data.use_domain_for_email?.value) document.getElementById('field_use_domain_for_email').value = data.use_domain_for_email.value;
                if (data.email_accounts?.value) document.getElementById('field_email_accounts').value = data.email_accounts.value;
                if (data.email_format?.value) document.getElementById('field_email_format').value = data.email_format.value;
                if (data.user_list?.value) document.getElementById('field_user_list').value = data.user_list.value;
                if (data.provider?.value) document.getElementById('field_provider').value = data.provider.value;
                if (data.existing_settings?.value) document.getElementById('field_existing_settings').value = data.existing_settings.value;
                if (data.notes?.value) document.getElementById('field_notes').value = data.notes.value;

                if (typeof updateProgressBar === 'function') updateProgressBar();
            } else {
                form.reset();
                document.querySelectorAll('.record-preview').forEach(el => el.remove());
                if (typeof updateProgressBar === 'function') updateProgressBar();
            }
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('myForm');
    if (form) {
        form.addEventListener('submit', function () {
            document.querySelectorAll('.load-record').forEach(cb => cb.checked = false);
        });
    }
});
</script>
<?php include './partials/layouts/layoutBottom.php'; ?>