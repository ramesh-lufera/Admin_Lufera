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
    $stmt = $conn->prepare("SELECT id, name FROM json WHERE user_id = ? AND template = ?");
    $stmt->bind_param("is", $session_user_id, $template);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $decoded = json_decode($row['name'], true);
        if ($decoded && isset($decoded['bussiness_name']['value'])) { // Updated key
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
        $bussiness_name = $_POST['bussiness_name'] ?? ''; // Updated key
        $industry_niche = $_POST['industry_niche'] ?? '';
        $business_description = $_POST['business_description'] ?? ''; // Updated key
        $target_audience = $_POST['target_audience'] ?? '';

        $existing_website = $_POST['existing_website'] ?? '';
        $website_purpose = $_POST['website_purpose'] ?? '';
        $top_goals = $_POST['top_goals'] ?? '';
        
        $has_logo = $_POST['has_logo'] ?? '';
        $has_branding = $_POST['has_branding'] ?? '';
        $reference_websites = $_POST['reference_websites'] ?? '';

        $content_ready = $_POST['content_ready'] ?? '';
        $page_count = $_POST['page_count'] ?? '';
        $features = $_POST['features'] ?? '';

        $has_domain = $_POST['has_domain'] ?? '';
        $domain_name = $_POST['domain_name'] ?? '';
        $has_hosting = $_POST['has_hosting'] ?? '';
        $platform_preference = $_POST['platform_preference'] ?? '';

        $launch_date = $_POST['launch_date'] ?? '';
        $budget_range = $_POST['budget_range'] ?? '';

        $contact_name = $_POST['contact_name'] ?? '';
        $contact_info = $_POST['contact_info'] ?? '';
        $communication_method = $_POST['communication_method'] ?? '';

        function createField($value) {
            return [
                'value' => $value,
                'status' => 'pending'
            ];
        }
        
        $data = json_encode([
            'bussiness_name' => createField($bussiness_name), // Updated key
            'industry_niche' => createField($industry_niche),
            'business_description' => createField($business_description), // Updated key
            'target_audience' => createField($target_audience),

            'existing_website' => createField($existing_website),
            'website_purpose' => createField(str_replace(' \/ ', '/', $website_purpose)), // Clean value
            'top_goals' => createField($top_goals),

            'has_logo' => createField($has_logo),
            'has_branding' => createField($has_branding),
            'reference_websites' => createField($reference_websites),

            'content_ready' => createField($content_ready),
            'page_count' => createField($page_count),
            'features' => createField($features),

            'has_domain' => createField($has_domain),
            'domain_name' => createField($domain_name),
            'has_hosting' => createField($has_hosting),
            'platform_preference' => createField($platform_preference),

            'launch_date' => createField($launch_date),
            'budget_range' => createField($budget_range),

            'contact_name' => createField($contact_name),
            'contact_info' => createField($contact_info),
            'communication_method' => createField($communication_method),
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
                            <?php echo htmlspecialchars($record['data']['bussiness_name']['value']); ?>
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
        if ($type === 'text' || $type === 'email') {
            echo '<input type="' . $type . '" class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
        }

        // === TEXTAREA ===
        elseif ($type === 'textarea') {           
            echo '<textarea class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $isReadonly . '>' . htmlspecialchars($val) . '</textarea>';
        }

        // === RADIO ===
        elseif ($type === 'radio') {
            foreach ($options as $option) {
                $checked = ($val == $option) ? 'checked' : '';
                echo '<div class="form-check form-check-inline">';
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
                echo '<input class="form-check-input mt-4" type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . $isDisabled . '>';
                echo '<label class="form-check-label">' . htmlspecialchars($option) . '</label>';
                echo '</div>';
            }
        }

        // === FILE ===
        elseif ($type === 'file') {
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
                echo '</div>';
            }
        }

        // === DATE ===
        elseif ($type === 'date') {
            echo '<input type="' . $type . '" class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
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
        <h6 class="fw-semibold mb-0">Web Development Client Onboarding Form</h6>
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
                                    
                                    <h5>1. Business Information</h5>
                                    <?php
                                        renderFieldExtended('bussiness_name', $savedData, $user_role, 'Business Name', '', 'text');
                                        renderFieldExtended('industry_niche', $savedData, $user_role, 'Industry / Niche', '', 'text');
                                        renderFieldExtended('business_description', $savedData, $user_role, 'Describe Your Business', '', 'textarea');
                                        renderFieldExtended('target_audience', $savedData, $user_role, 'Target Audience', '', 'textarea');
                                    ?>
                                    <h5>2. Website Objectives</h5>
                                    <?php
                                        renderFieldExtended('website_purpose', $savedData, $user_role, 'Primary Purpose of Website', '', 'select', ['Informational/Brochure Site', 'ecommerce', 'portfolio', 'Booking/Appointment', 'Custom Functionality']);
                                        renderFieldExtended('top_goals', $savedData, $user_role, 'Top 3 Goals for Website', '', 'textarea');
                                        renderFieldExtended('existing_website', $savedData, $user_role, 'Do you have an existing website?', '', 'text');
                                    ?> 
                                    <h5>3. Design & Branding</h5>
                                    <?php
                                        renderFieldExtended('has_logo', $savedData, $user_role, 'Do you have a logo?', '', 'select', ['yes', 'no']);
                                        renderFieldExtended('has_branding', $savedData, $user_role, 'Do you have brand colors / guidelines?', '', 'select', ['yes', 'no']);
                                        renderFieldExtended('reference_websites', $savedData, $user_role, 'Reference Websites You Like', 'Add links and what you like about them', 'textarea');
                                    ?>
                                    <h5>4. Content</h5>
                                    <?php
                                        renderFieldExtended('content_ready', $savedData, $user_role, 'Do you have content ready? (Text, images, videos)', '', 'select', ['yes', 'no', 'partially']);
                                        renderFieldExtended('page_count', $savedData, $user_role, 'How many pages do you expect?', 'E.g., Home, About, Services, Contact, Blog', 'text');
                                        renderFieldExtended('features', $savedData, $user_role, 'Special Features Needed', 'E.g., Blog, Contact Form, Chat, Member Login, Payment Gateway', 'textarea');
                                    ?>
                                    <h5>5. Technical & Access</h5>
                                    <?php
                                        renderFieldExtended('has_domain', $savedData, $user_role, 'Do you already have a domain?', '', 'select', ['yes', 'no']);
                                        renderFieldExtended('domain_name', $savedData, $user_role, 'Domain Name (if any)', '', 'text');
                                        renderFieldExtended('has_hosting', $savedData, $user_role, 'Do you have hosting?', '', 'select', ['yes', 'no']);
                                        renderFieldExtended('platform_preference', $savedData, $user_role, 'Any specific platform preference?', '', 'select', ['wordpress', 'shopify', 'custom', 'not_sure']);
                                    ?>
                                    <h5>6. Timeline & Budget</h5>
                                    <?php
                                        renderFieldExtended('launch_date', $savedData, $user_role, 'Expected Launch Date', '', 'date');
                                        renderFieldExtended('budget_range', $savedData, $user_role, 'Budget Range', '', 'text');
                                    ?>
                                    <h5>7. Contact & Communication</h5>
                                    <?php
                                        renderFieldExtended('contact_name', $savedData, $user_role, 'Point of Contact Name', '', 'text');
                                        renderFieldExtended('contact_info', $savedData, $user_role, 'Email & Phone', '', 'text');
                                        renderFieldExtended('communication_method', $savedData, $user_role, 'Preferred Communication Method', '', 'select', ['email', 'whatsapp', 'phone', 'zoom']);
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
                } else if (currentType === 'select') {
                    let selectHTML = `<select id="modalInput" class="form-control">`;
                    options.forEach(opt => {
                        const selected = opt.trim() === value ? 'selected' : '';
                        selectHTML += `<option value="${opt.trim()}" ${selected}>${opt.trim()}</option>`;
                    });
                    selectHTML += `</select>`;
                    fieldContainer.innerHTML = selectHTML;
                } 
                else if (currentType === 'date') {
                    fieldContainer.innerHTML = `<input type="date" id="modalInput" class="form-control" value="${value}" />`;
                }
                else {
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
        const totalFields = 22;
 
        const bussiness_name = $('#field_bussiness_name').val()?.trim();
        if (bussiness_name) filled++;

        const industry_niche = $('#field_industry_niche').val()?.trim();
        if (industry_niche) filled++;

        const business_description = $('#field_business_description').val()?.trim();
        if (business_description) filled++;

        const target_audience = $('#field_target_audience').val()?.trim();
        if (target_audience) filled++;

        const top_goals = $('#field_top_goals').val()?.trim();
        if (top_goals) filled++;

        const existing_website = $('#field_existing_website').val()?.trim();
        if (existing_website) filled++;
        
        const website_purpose = $('#field_website_purpose').val()?.trim();
        if (website_purpose) filled++;

        const has_logo = $('#field_has_logo').val()?.trim();
        if (has_logo) filled++;

        const has_branding = $('#field_has_branding').val()?.trim();
        if (has_branding) filled++;

        const reference_websites = $('#field_reference_websites').val()?.trim();
        if (reference_websites) filled++;

        const content_ready = $('#field_content_ready').val()?.trim();
        if (content_ready) filled++;

        const page_count = $('#field_page_count').val()?.trim();
        if (page_count) filled++;

        const features = $('#field_features').val()?.trim();
        if (features) filled++;

        const has_domain = $('#field_has_domain').val()?.trim();
        if (has_domain) filled++;

        const domain_name = $('#field_domain_name').val()?.trim();
        if (domain_name) filled++;

        const has_hosting = $('#field_has_hosting').val()?.trim();
        if (has_hosting) filled++;

        const platform_preference = $('#field_platform_preference').val()?.trim();
        if (platform_preference) filled++;

        const launch_date = $('#field_launch_date').val()?.trim();
        if (launch_date) filled++;

        const budget_range = $('#field_budget_range').val()?.trim();
        if (budget_range) filled++;

        const contact_name = $('#field_contact_name').val()?.trim();
        if (contact_name) filled++;

        const contact_info = $('#field_contact_info').val()?.trim();
        if (contact_info) filled++;

        const communication_method = $('#field_communication_method').val()?.trim();
        if (communication_method) filled++;
 
        const percent = Math.round((filled / totalFields) * 100);
        $('#formProgressBar').css('width', percent + '%').text(percent + '%');
    }
 
    $(document).ready(function () {
        updateProgressBar();
        $('#field_bussiness_name, #field_industry_niche, #field_existing_website, #field_business_description, #field_top_goals, #field_target_audience, #field_reference_websites, #field_page_count, #field_features, #field_domain_name, #field_budget_range, #field_contact_name, #field_contact_info')
            .on('input', updateProgressBar);
        $('#field_website_purpose, #field_has_logo, #field_has_branding, #field_content_ready, #field_has_domain, #field_has_hosting, #field_platform_preference, #field_launch_date, #field_communication_method')
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
    document.querySelectorAll('.load-record').forEach(cb => {
        cb.addEventListener('change', function () {
            const form = document.getElementById('myForm');
            if (this.checked) {
                const data = JSON.parse(this.dataset.record);
                if (data.bussiness_name?.value) document.getElementById('field_bussiness_name').value = data.bussiness_name.value;
                if (data.industry_niche?.value) document.getElementById('field_industry_niche').value = data.industry_niche.value;
                if (data.business_description?.value) document.getElementById('field_business_description').value = data.business_description.value;
                if (data.target_audience?.value) document.getElementById('field_target_audience').value = data.target_audience.value;
                if (data.website_purpose?.value) {
                    document.querySelectorAll('select[name="website_purpose"] option').forEach(opt => {
                        opt.selected = (opt.value === data.website_purpose.value);
                    });
                }
                if (data.top_goals?.value) document.getElementById('field_top_goals').value = data.top_goals.value;
                if (data.existing_website?.value) document.getElementById('field_existing_website').value = data.existing_website.value;
                if (data.has_logo?.value) {
                    document.querySelectorAll('select[name="has_logo"] option').forEach(opt => {
                        opt.selected = (opt.value === data.has_logo.value);
                    });
                }
                if (data.has_branding?.value) {
                    document.querySelectorAll('select[name="has_branding"] option').forEach(opt => {
                        opt.selected = (opt.value === data.has_branding.value);
                    });
                }
                if (data.reference_websites?.value) document.getElementById('field_reference_websites').value = data.reference_websites.value;
                if (data.content_ready?.value) {
                    document.querySelectorAll('select[name="content_ready"] option').forEach(opt => {
                        opt.selected = (opt.value === data.content_ready.value);
                    });
                }
                if (data.page_count?.value) document.getElementById('field_page_count').value = data.page_count.value;
                if (data.features?.value) document.getElementById('field_features').value = data.features.value;
                if (data.has_domain?.value) {
                    document.querySelectorAll('select[name="has_domain"] option').forEach(opt => {
                        opt.selected = (opt.value === data.has_domain.value);
                    });
                }
                if (data.domain_name?.value) document.getElementById('field_domain_name').value = data.domain_name.value;
                if (data.has_hosting?.value) {
                    document.querySelectorAll('select[name="has_hosting"] option').forEach(opt => {
                        opt.selected = (opt.value === data.has_hosting.value);
                    });
                }
                if (data.platform_preference?.value) {
                    document.querySelectorAll('select[name="platform_preference"] option').forEach(opt => {
                        opt.selected = (opt.value === data.platform_preference.value);
                    });
                }
                if (data.launch_date?.value) document.getElementById('field_launch_date').value = data.launch_date.value;
                if (data.budget_range?.value) document.getElementById('field_budget_range').value = data.budget_range.value;
                if (data.contact_name?.value) document.getElementById('field_contact_name').value = data.contact_name.value;
                if (data.contact_info?.value) document.getElementById('field_contact_info').value = data.contact_info.value;
                if (data.communication_method?.value) {
                    document.querySelectorAll('select[name="communication_method"] option').forEach(opt => {
                        opt.selected = (opt.value === data.communication_method.value);
                    });
                }
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
<?php include './partials/layouts/layoutBottom.php'; ?>