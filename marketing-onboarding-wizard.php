<?php include './partials/layouts/layoutTop.php'; ?>

<style>
     .readonly-select {
        pointer-events: none;
        background-color: #f8f9fa;
    }
    .readonly-select:focus {
        pointer-events: none;
    }
    .readonly-select {
        pointer-events: none;
        background-color: #f8f9fa;
    }
    .readonly-select:focus {
        pointer-events: none;
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
            <h6 class="fw-semibold mb-0">Digital Marketing Client Onboarding Form</h6>
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
    $stmt = $conn->prepare(
        "SELECT id, name, prefill_name 
         FROM json 
         WHERE user_id = ? AND template = ? AND prefill_name IS NOT NULL AND prefill_name != ''"
    );
    $stmt->bind_param("is", $session_user_id, $template);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $decoded = json_decode($row['name'], true);
        if ($decoded && isset($decoded['business_name']['value'])) {
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
        $Business_name = $_POST['business_name'] ?? '';
        $Industry_niche = $_POST['industry_niche'] ?? '';
        $Target_audience = $_POST['target_audience'] ?? '';
        $Unique_selling_proposition = $_POST['unique_selling_proposition'] ?? '';
        $Main_competitors = $_POST['main_competitors'] ?? '';
        $Logo_brand_guidelines = $_POST['logo_brand_guidelines'] ?? '';
        $Website_url = $_POST['website_url'] ?? '';
        $Social_media_links = $_POST['social_media_links'] ?? '';
        $Google_analytics_tag_manager_IDs = $_POST['google_analytics_tag_manager_IDs'] ?? '';
        $Other_platform_access = $_POST['other_platform_access'] ?? '';
        $Main_goals = $_POST['main_goals'] ?? '';
        $Preferred_channels = $_POST['preferred_channels'] ?? '';
        $Monthly_marketing_budget = $_POST['monthly_marketing_budget'] ?? '';
        $Products_services_to_promote = $_POST['products_services_to_promote'] ?? '';
        $Any_existing_offers_campaigns = $_POST['any_existing_offers_campaigns'] ?? '';
        $Content_bank = $_POST['content_bank'] ?? '';
        $Primary_point_of_contact = $_POST['primary_point_of_contact'] ?? '';
        $Email_phone = $_POST['email_phone'] ?? '';
        $Preferred_communication_channel = $_POST['preferred_communication_channel'] ?? '';
        $Reporting_frequency = $_POST['reporting_frequency'] ?? '';
        $Past_campaigns_tools_used = $_POST['past_campaigns_tools_used'] ?? '';
        $Top_performing_content = $_POST['top_performing_content'] ?? '';
        $allow_prefill = isset($_POST['allow_prefill']) && $_POST['allow_prefill'] === 'on';
        $prefill_name = $allow_prefill ? ($_POST['prefill_name'] ?? '') : '';

        function createField($value) {
            return [
                'value' => $value,
                'status' => 'pending'
            ];
        }

        $inputFields = [
            'business_name' => $Business_name,
            'industry_niche' => $Industry_niche,
            'target_audience' => $Target_audience,
            'unique_selling_proposition' => $Unique_selling_proposition,
            'main_competitors' => $Main_competitors,
            'logo_brand_guidelines' => $Logo_brand_guidelines,
            'website_url' => $Website_url,
            'social_media_links' => $Social_media_links,
            'google_analytics_tag_manager_IDs' => $Google_analytics_tag_manager_IDs,
            'other_platform_access' => $Other_platform_access,
            'main_goals' => $Main_goals,
            'preferred_channels' => $Preferred_channels,
            'monthly_marketing_budget' => $Monthly_marketing_budget,
            'products_services_to_promote' => $Products_services_to_promote,
            'any_existing_offers_campaigns' => $Any_existing_offers_campaigns,
            'content_bank' => $Content_bank,
            'primary_point_of_contact' => $Primary_point_of_contact,
            'email_phone' => $Email_phone,
            'preferred_communication_channel' => $Preferred_communication_channel,
            'reporting_frequency' => $Reporting_frequency,
            'past_campaigns_tools_used' => $Past_campaigns_tools_used,
            'top_performing_content' => $Top_performing_content,
            'prefill_name' => $prefill_name,
        ];

        if (empty($savedData)) {
            // New entry: create all fields as pending
            foreach ($inputFields as $key => $value) {
                $savedData[$key] = createField($value);
            }
        } else {
            // Existing entry: update only non-approved fields
            foreach ($inputFields as $key => $value) {
                if (!isset($savedData[$key]) || ($savedData[$key]['status'] ?? '') !== 'approved') {
                    $savedData[$key] = createField($value);
                }
            }
        }

        $data = json_encode($savedData);

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
        logActivity(
            $conn,
            $session_user_id,
            "wizard",
            "Wizard updated"
        );
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
        $isReadonly = ($isAdmin || (!$isAdmin && ($status === 'approved' || $status === 'rejected'))) ? 'readonly' : '';
        $isDisabled = ($isAdmin || (!$isAdmin && ($status === 'approved' || $status === 'rejected'))) ? 'disabled' : '';
        $dataValue = is_array($val) ? implode(',', $val) : $val;
        $dataOptions = !empty($options) ? 'data-options="' . htmlspecialchars(implode(',', $options)) . '"' : '';
        $selectReadonlyClass = ($type === 'select' && $isReadonly) ? 'readonly-select' : '';

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

        $copyButton = '';
        if (in_array($type, ['text', 'textarea', 'select', 'date'])) {
            $copyButton = '<button type="button" class="btn btn-outline-secondary btn-sm copy-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Copy Value"><i class="fa fa-copy"></i></button>';
        }

        // === TEXT / EMAIL ===
        if ($type === 'text' || $type === 'email') {
            echo '<input type="' . $type . '" class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
            if ($copyButton) echo $copyButton;
        }

        // === TEXTAREA ===
        elseif ($type === 'textarea') {           
            echo '<textarea class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $isReadonly . '>' . htmlspecialchars($val) . '</textarea>';
            if ($copyButton) echo $copyButton;
        }

        // === SELECT (Dropdown) ===
        elseif ($type === 'select') {
            //echo '<select class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isReadonly . ' ' . $dataOptions . '>';
            echo '<select class="form-control w-85 h-auto ' . $styleClass . ' ' . $selectReadonlyClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isReadonly . ' ' . $dataOptions . '>';
            // Default placeholder option
            echo '<option value="" disabled ' . (empty($val) ? 'selected' : '') . '>Select an option</option>';

            foreach ($options as $option) {
                $selected = ($val == $option) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
            }

            echo '</select>';
            if ($copyButton) echo $copyButton;
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

        // === Admin Buttons ===
        if ($isAdmin) {
            echo '<div class="btn-group mt-2 ms-1">';
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
                data-value="' . htmlspecialchars(is_array($val) ? implode(',', $val) : $val) . '"'
                . (!empty($options) ? ' data-options="' . htmlspecialchars(implode(',', $options)) . '"' : '') . '>
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
        // ⬇️ ADD THIS
        logActivity(
            $conn,
            $user_id,
            "wizard",
            "Wizard updated"
        );
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

        // Fetch prefill_name for logging
        $stmt_prefill = $conn->prepare("SELECT prefill_name FROM json WHERE website_id = ?");
        $stmt_prefill->bind_param("i", $website_id);
        $stmt_prefill->execute();
        $stmt_prefill->bind_result($prefill_name_db);
        $stmt_prefill->fetch();
        $stmt_prefill->close();
    
        $prefillLabel = !empty($prefill_name_db) ? $prefill_name_db : null;
        $actionText = "Field updated for " . $field;
        if ($prefillLabel) {
            $safePrefill = htmlspecialchars($prefillLabel, ENT_QUOTES, 'UTF-8');
            $actionText .= " in {$safePrefill}";
        }
    
        // Log activity
        logActivity(
            $conn,
            $session_user_id,
            "wizard",
            $actionText
        );

        echo 'updated';
        exit;
    }
    // Check if all main fields are approved
    $mainFields = [
        'bussiness_name', 'industry_niche', 'target_audience',
        'unique_selling_proposition', 'main_competitors', 'logo_brand_guidelines',
        'website_url', 'social_media_links', 'google_analytics_tag_manager_IDs',
        'other_platform_access', 'main_goals', 'preferred_channels',
        'monthly_marketing_budget', 'products_services_to_promote', 'any_existing_offers_campaigns', 'content_bank',
        'primary_point_of_contact', 'email_phone', 'preferred_communication_channel', 'reporting_frequency', 
        'past_campaigns_tools_used', 'top_performing_content'
    ];
    $allApproved = true;
    foreach ($mainFields as $field) {
        if (empty($savedData[$field]) || ($savedData[$field]['status'] ?? 'pending') !== 'approved') {
            $allApproved = false;
            break;
        }
    }

    // Output savedData to JS for PDF export
    echo '<script>const savedData = ' . json_encode($savedData) . ';</script>';
    echo '<script>const websiteId = ' . $website_id . ';</script>';
?>

</div>
    <div class="card h-100 p-0 radius-12 overflow-hidden">               
        <div class="card-body p-20">
            <?php if (!empty($prevRecords)): ?>
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
            <?php endif; ?>
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
                                                <button type="button" id="exportPdfBtn" class="btn btn-primary btn-sm">Export PDF</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <h5>1. Business Information</h5>
                                    <?php
                                        renderFieldExtended('business_name', $savedData, $user_role, 'Business Name', '', 'text');
                                        renderFieldExtended('industry_niche', $savedData, $user_role, 'Industry / Niche', '', 'text');
                                        renderFieldExtended('target_audience', $savedData, $user_role, 'Target Audience', '', 'textarea');
                                        renderFieldExtended('unique_selling_proposition', $savedData, $user_role, 'Unique Selling Proposition (USP)', '', 'textarea');
                                        renderFieldExtended('main_competitors', $savedData, $user_role, 'Main Competitors', '', 'textarea');
                                    ?>

                                    <h5>2. Branding & Assets</h5>
                                    <?php
                                        renderFieldExtended('logo_brand_guidelines', $savedData, $user_role, 'Logo / Brand Guidelines (Upload separately)', 'Describe brand colors, fonts, voice, etc.', 'textarea');
                                    ?>

                                    <h5>3. Digital Presence</h5>
                                    <?php
                                        renderFieldExtended('website_url', $savedData, $user_role, 'Website URL', '', 'text');
                                        renderFieldExtended('social_media_links', $savedData, $user_role, 'Social Media Links', '', 'textarea');
                                        renderFieldExtended('google_analytics_tag_manager_IDs', $savedData, $user_role, 'Google Analytics / Tag Manager IDs', '', 'textarea');
                                        renderFieldExtended('other_platform_access', $savedData, $user_role, 'Other Platform Access (e.g., Facebook Ads, Email Tools)', '', 'textarea');
                                    ?>

                                    <h5>4. Marketing Objectives</h5>
                                    <?php
                                        renderFieldExtended('main_goals', $savedData, $user_role, 'Main Goals', 'E.g., Increase traffic, generate leads, grow followers', 'textarea');
                                        renderFieldExtended('preferred_channels', $savedData, $user_role, 'Preferred Channels', '', 'select', ['SEO', 'Google Ads (PPC)', 'Social Media Marketing', 'Email Marketing', 'Content Marketing', 'Influencer Marketing']);
                                        renderFieldExtended('monthly_marketing_budget', $savedData, $user_role, 'Monthly Marketing Budget', '', 'textarea');
                                    ?>

                                    <h5>5. Content & Offers</h5>
                                    <?php
                                        renderFieldExtended('products_services_to_promote', $savedData, $user_role, 'Products/Services to Promote', '', 'textarea');
                                        renderFieldExtended('any_existing_offers_campaigns', $savedData, $user_role, 'Any Existing Offers / Campaigns', '', 'textarea');
                                        renderFieldExtended('content_bank', $savedData, $user_role, 'Content Bank (Blog links, brochures, etc.)', '', 'textarea');
                                    ?>

                                    <h5>6. Communication & Legal</h5>
                                    <?php
                                        renderFieldExtended('primary_point_of_contact', $savedData, $user_role, 'Primary Point of Contact', '', 'text');
                                        renderFieldExtended('email_phone', $savedData, $user_role, 'Email & Phone', '', 'text');
                                        renderFieldExtended('preferred_communication_channel', $savedData, $user_role, 'Preferred Communication Channel', '', 'select', ['Email', 'WhatsApp', 'Phone Call', 'Zoom / Meet']);
                                        renderFieldExtended('reporting_frequency', $savedData, $user_role, 'Reporting Frequency', '', 'select', ['Weekly', 'Bi-Weekly', 'Monthly']);
                                    ?>
                                    
                                    <h5>7. Past Marketing Data (Optional)</h5>
                                    <?php
                                        renderFieldExtended('past_campaigns_tools_used', $savedData, $user_role, 'Past Campaigns / Tools Used', '', 'textarea');
                                        renderFieldExtended('top_performing_content', $savedData, $user_role, 'Top-Performing Content (if known)', '', 'textarea');
                                    ?>
                            <!-- NEW: Allow Prefill Data -->
                                <?php
                                    if ($user_role != 1 && $user_role != 2) {
                                    $prefillName = $savedData['prefill_name']['value'] ?? '';
                                    $allowPrefill = !empty($prefillName);
                                    ?>
                                    <div class="">
                                        <div class="form-check">
                                            <input class="form-check-input mt-4 me-10" type="checkbox" id="allow_prefill" name="allow_prefill" <?= $allowPrefill ? 'checked' : '' ?>>
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
                                        <input type="submit" id="saveBtn" name="save" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block mt-4" value="Save" >
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
    document.getElementById('allow_prefill').addEventListener('change', function () {
        document.getElementById('prefill_name_wrapper').style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            const inp = document.getElementById('field_prefill_name');
            if (inp) inp.value = '';
        }
    });
</script>

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
                } 
                else if (currentType === 'select') {
                    let selectHTML = `<select id="modalInput" class="form-control">`;
                    options.forEach(opt => {
                        const selected = opt.trim() === value ? 'selected' : '';
                        selectHTML += `<option value="${opt.trim()}" ${selected}>${opt.trim()}</option>`;
                    });
                    selectHTML += `</select>`;
                    fieldContainer.innerHTML = selectHTML;
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

        // PDF Export functionality
        $('#exportPdfBtn').click(function() {
            // Define sections and fields mapping (label, fieldKey)
            const sections = [
                {
                    title: '1. Business Information',
                    fields: [
                        { label: 'Business Name', key: 'business_name' },
                        { label: 'Industry / Niche', key: 'industry_niche' },
                        { label: 'Target Audience', key: 'target_audience' },
                        { label: 'Unique Selling Proposition (USP)', key: 'unique_selling_proposition' },
                        { label: 'Main Competitors', key: 'main_competitors' }
                    ]
                },
                {
                    title: '2. Branding & Assets',
                    fields: [
                        { label: 'Logo / Brand Guidelines (Upload separately)', key: 'logo_brand_guidelines' }
                    ]
                },
                {
                    title: '3. Digital Presence',
                    fields: [
                        { label: 'Website URL', key: 'website_url' },
                        { label: 'Social Media Links', key: 'social_media_links' },
                        { label: 'Google Analytics / Tag Manager IDs', key: 'google_analytics_tag_manager_IDs' },
                        { label: 'Other Platform Access (e.g., Facebook Ads, Email Tools)', key: 'other_platform_access' }
                    ]
                },
                {
                    title: '4. Marketing Objectives',
                    fields: [
                        { label: 'Main Goals', key: 'main_goals' },
                        { label: 'Preferred Channels', key: 'preferred_channels' },
                        { label: 'Monthly Marketing Budget', key: 'monthly_marketing_budget' }
                    ]
                },
                {
                    title: '5. Content & Offers',
                    fields: [
                        { label: 'Products/Services to Promote', key: 'products_services_to_promote' },
                        { label: 'Any Existing Offers / Campaigns', key: 'any_existing_offers_campaigns' },
                        { label: 'Content Bank (Blog links, brochures, etc.)', key: 'content_bank' }
                    ]
                },
                {
                    title: '6. Communication & Legal',
                    fields: [
                        { label: 'Primary Point of Contact', key: 'primary_point_of_contact' },
                        { label: 'Email & Phone', key: 'email_phone' },
                        { label: 'Preferred Communication Channel', key: 'preferred_communication_channel' },
                        { label: 'Reporting Frequency', key: 'reporting_frequency' }
                    ]
                },
                {
                    title: '7. Past Marketing Data (Optional)',
                    fields: [
                        { label: 'Past Campaigns / Tools Used', key: 'past_campaigns_tools_used' },
                        { label: 'Top-Performing Content (if known)', key: 'top_performing_content' }
                    ]
                }
            ];

            let html = '<html><head><title>Digital Marketing Client Onboarding Form</title>';
            html += '<style>';
            html += 'body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; margin: 0; }';
            html += 'h1 { text-align: center; color: #333; border-bottom: 2px solid #fec700; padding-bottom: 10px; margin-bottom: 20px; }';
            html += 'h2 { color: #fec700; font-size: 18px; margin-top: 30px; margin-bottom: 15px; }';
            html += '.field-item { margin-bottom: 15px; padding: 10px; border-left: 3px solid #eee; background-color: #f9f9f9; }';
            html += '.field-label { font-weight: bold; color: #555; display: block; margin-bottom: 5px; }';
            html += '.field-value { color: #000; word-wrap: break-word; }';
            html += '.status-approved { color: #28a745; font-style: italic; }';
            html += '.status-rejected { color: #dc3545; font-style: italic; }';
            html += '.status-pending { color: #ffc107; font-style: italic; }';
            html += '@media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }';
            html += '</style></head><body>';
            html += '<h1>Digital Marketing Client Onboarding Form</h1>';
            html += '<p><strong>Generated on:</strong> ' + new Date().toLocaleDateString() + '</p>';
            
            // Populate sections
            sections.forEach(section => {
                let sectionHtml = '';
                section.fields.forEach(field => {
                    const data = savedData[field.key];
                    if (data && data.value !== undefined) {
                        let value = data.value || 'N/A';
                        if (typeof value === 'string' && value.includes(',')) {
                            value = value.split(',').map(v => v.trim()).join(', ');
                        }
                        const status = data.status || 'pending';
                        const statusClass = `status-${status}`;
                        sectionHtml += `
                            <div class="field-item">
                                <span class="field-label">${field.label}:</span>
                                <span class="field-value">${value}</span>
                                <br><small class="${statusClass}">Status: ${status}</small>
                            </div>
                        `;
                    }
                });
                if (sectionHtml) {
                    html += `<h2>${section.title}</h2>` + sectionHtml;
                }
            });

            html += '</body></html>';

            const printWindow = window.open('', '_blank');
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
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
            //input.prop('readonly', false).focus();
            input.prop('readonly', false).prop('disabled', false);
            if (input.is('select')) {
                input.removeClass('readonly-select');
            }
            input.focus();
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
    const fieldNames = [
        'business_name',
        'industry_niche',
        'target_audience',
        'unique_selling_proposition',
        'main_competitors',
        'logo_brand_guidelines',
        'website_url',
        'social_media_links',
        'google_analytics_tag_manager_IDs',
        'other_platform_access',
        'main_goals',
        'preferred_channels',
        'monthly_marketing_budget',
        'products_services_to_promote',
        'any_existing_offers_campaigns',
        'content_bank',
        'primary_point_of_contact',
        'email_phone',
        'preferred_communication_channel',
        'reporting_frequency',
        'past_campaigns_tools_used',
        'top_performing_content'
    ];

    function normalizeName(name) {
        // Convert names like "industry_niche" to a jQuery-safe selector
        return name.replace(/[\[\]\/]/g, "\\$&");
    }

    function isFieldFilled($field) {
        const tag = $field.prop('tagName').toLowerCase();
        const type = $field.attr('type');

        if (tag === 'select' || tag === 'textarea') {
            return !!$field.val()?.trim();
        }

        if (type === 'file') {
            const filesPresent = $field[0]?.files?.length > 0;
            const hasExisting = $field.closest('.form-group').find('img, a').length > 0;
            return filesPresent || hasExisting;
        }

        if (type === 'checkbox' || type === 'radio') {
            return $(`input[name="${$field.attr('name')}"]:checked`).length > 0;
        }

        // Default case: input[type=text], input[type=email], etc.
        return !!$field.val()?.trim();
    }

    function updateProgressBar() {
        let filled = 0;
        const total = fieldNames.length;

        for (const name of fieldNames) {
            const $field = $(`[name="${normalizeName(name)}"]`);
            if ($field.length && isFieldFilled($field)) {
                filled++;
            }
        }

        const percent = Math.round((filled / total) * 100);
        $('#formProgressBar')
            .css('width', percent + '%')
            .text(percent + '%');
    }

    $(document).ready(function () {
        updateProgressBar();

        // Watch all relevant field changes
        $(document).on('input change', 'input, select, textarea', updateProgressBar);
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

            // Optional: If all are manually selected/deselected, update the "Select All" checkbox
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
    if (typeof prevRecordsData === 'undefined') return;
    document.querySelectorAll('.load-record').forEach(cb => {
        cb.addEventListener('change', function () {
            const form = document.getElementById('myForm');

            // Uncheck all other checkboxes
            document.querySelectorAll('.load-record').forEach(other => {
                if (other !== this) other.checked = false;
            });

            if (this.checked) {
                const recordId = parseInt(this.dataset.recordId);
                const record = prevRecordsData.find(r => r.id === recordId);
                if (!record) {
                    console.warn('Record not found:', recordId);
                    return;
                }
                const data = record.data;

                // === Fill fields ===
                if (data.business_name?.value) document.getElementById('field_business_name').value = data.business_name.value;
                if (data.industry_niche?.value) document.getElementById('field_industry_niche').value = data.industry_niche.value;
                if (data.target_audience?.value) document.getElementById('field_target_audience').value = data.target_audience.value;
                if (data.unique_selling_proposition?.value) document.getElementById('field_unique_selling_proposition').value = data.unique_selling_proposition.value;
                if (data.main_competitors?.value) document.getElementById('field_main_competitors').value = data.main_competitors.value;
                if (data.logo_brand_guidelines?.value) document.getElementById('field_logo_brand_guidelines').value = data.logo_brand_guidelines.value;
                if (data.website_url?.value) document.getElementById('field_website_url').value = data.website_url.value;
                if (data.social_media_links?.value) document.getElementById('field_social_media_links').value = data.social_media_links.value;
                if (data.google_analytics_tag_manager_IDs?.value) document.getElementById('field_google_analytics_tag_manager_IDs').value = data.google_analytics_tag_manager_IDs.value;
                if (data.other_platform_access?.value) document.getElementById('field_other_platform_access').value = data.other_platform_access.value;
                if (data.main_goals?.value) document.getElementById('field_main_goals').value = data.main_goals.value;
                if (data.preferred_channels?.value) document.getElementById('field_preferred_channels').value = data.preferred_channels.value;
                if (data.monthly_marketing_budget?.value) document.getElementById('field_monthly_marketing_budget').value = data.monthly_marketing_budget.value;
                if (data.products_services_to_promote?.value) document.getElementById('field_products_services_to_promote').value = data.products_services_to_promote.value;
                if (data.any_existing_offers_campaigns?.value) document.getElementById('field_any_existing_offers_campaigns').value = data.any_existing_offers_campaigns.value;
                if (data.content_bank?.value) document.getElementById('field_content_bank').value = data.content_bank.value;
                if (data.primary_point_of_contact?.value) document.getElementById('field_primary_point_of_contact').value = data.primary_point_of_contact.value;
                if (data.email_phone?.value) document.getElementById('field_email_phone').value = data.email_phone.value;
                if (data.preferred_communication_channel?.value) document.getElementById('field_preferred_communication_channel').value = data.preferred_communication_channel.value;
                if (data.reporting_frequency?.value) document.getElementById('field_reporting_frequency').value = data.reporting_frequency.value;
                if (data.past_campaigns_tools_used?.value) document.getElementById('field_past_campaigns_tools_used').value = data.past_campaigns_tools_used.value;
                if (data.top_performing_content?.value) document.getElementById('field_top_performing_content').value = data.top_performing_content.value;

                if (data.prefill_name?.value) {
                    const prefillInput = document.getElementById('field_prefill_name');
                    if (prefillInput) prefillInput.value = data.prefill_name.value;
                    const allowPrefillCb = document.getElementById('allow_prefill');
                    if (allowPrefillCb) allowPrefillCb.checked = true;
                    const wrapper = document.getElementById('prefill_name_wrapper');
                    if (wrapper) wrapper.style.display = 'block';
                }

                // Update progress bar if exists
                if (typeof updateProgressBar === 'function') updateProgressBar();

            } else {
                // === If unchecked → Reset the form ===
                form.reset();

                // Remove uploaded file preview if any
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
<script>
$(document).ready(function() {
    $('#saveBtn').on('click', function(e) {
        const approvedElements = $('input.field-approved, textarea.field-approved, select.field-approved').filter(function() {
            return $(this).attr('name') !== 'prefill_name';
        });
        const approvedCount = approvedElements.length;
        const totalMainFields = 22;

        if (approvedCount === totalMainFields) {
            e.preventDefault();
            Swal.fire({
                icon: "info",
                title: "All Fields Approved!",
                text: "All records are already approved. No need to save."
            });
            return false;
        }
        // else, allow normal submission
    });
});
</script>
<?php include './partials/layouts/layoutBottom.php' ?>
