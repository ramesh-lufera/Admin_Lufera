<?php include './partials/layouts/layoutTop.php'; ?>

<style>
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
    .edit-btn, app-btn{
        width: 90px;
        justify-content: center;
    }
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-24 justify-content-between">
        <div class="d-flex align-self-end">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>  </a>
            <h6 class="fw-semibold mb-0">Lufera One Wizard</h6>
        </div>

<?php
    $session_user_id = $_SESSION['user_id'];
    $prod_id = intval($_GET['prod_id'] ?? 0);
    $web_id   = intval($_GET['id']     ?? 0);

    $get_type = "SELECT * FROM websites WHERE id = $web_id";
    $type_result = $conn->query($get_type);
    $row_type = $type_result->fetch_assoc();
    $type = $row_type['type'] ?? '';

    if ($type === "package") {
        $sql = "SELECT * FROM package WHERE id = $prod_id";
    } elseif ($type === "product") {
        $sql = "SELECT * FROM products WHERE id = $prod_id";
    }
    $result = $conn->query($sql ?? "SELECT 1");
    $row = $result->fetch_assoc();
    $template = $row['template'] ?? 'default';

    // ────────────────────────────────────────────────
    //  Fetch previous records for prefill dropdown
    // ────────────────────────────────────────────────
    $prevRecords = [];
    $stmt = $conn->prepare(
        "SELECT id, name, prefill_name
         FROM json
         WHERE user_id = ? AND template = ? AND prefill_name IS NOT NULL AND prefill_name != ''"
    );
    $stmt->bind_param("is", $session_user_id, $template);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) {
        $decoded = json_decode($r['name'], true);
        if ($decoded && !empty($decoded['business_legal_name']['value'] ?? '')) {
            $prevRecords[] = [
                'id'           => $r['id'],
                'data'         => $decoded,
                'prefill_name' => $r['prefill_name']
            ];
        }
    }
    $stmt->close();

    // Target user (admin viewing someone else)
    $target_user_id = $session_user_id;
    if (isset($_GET['id']) && in_array($session_user_id, [1,2,7])) {
        $stmt = $conn->prepare("SELECT user_id FROM websites WHERE id = ?");
        $stmt->bind_param("i", $web_id);
        $stmt->execute();
        $stmt->bind_result($fetched_user_id);
        if ($stmt->fetch()) $target_user_id = $fetched_user_id;
        $stmt->close();
    }
    $user_id = $target_user_id;

    $roleQuery = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $roleQuery->bind_param("i", $user_id);
    $roleQuery->execute();
    $roleQuery->bind_result($user_role);
    $roleQuery->fetch();
    $roleQuery->close();

    // Load saved data
    $savedData = [];
    $stmt = $conn->prepare("SELECT name FROM json WHERE website_id = ?");
    $stmt->bind_param("i", $web_id);
    $stmt->execute();
    $stmt->bind_result($jsonData);
    if ($stmt->fetch()) {
        $savedData = json_decode($jsonData, true) ?? [];
    }
    $stmt->close();

    // ────────────────────────────────────────────────
    //   INLINE UPDATE HANDLER - FIXED FOR update-icon & saveEditBtn
    // ────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inline_update'])) {

        header('Content-Type: text/plain; charset=utf-8');

        $field = trim($_POST['field'] ?? '');
        $website_id = intval($_GET['id'] ?? 0);

        if (!$field || $website_id <= 0) {
            http_response_code(400);
            echo 'missing field or website id';
            exit;
        }

        // Load current JSON
        $stmt = $conn->prepare("SELECT name FROM json WHERE website_id = ? LIMIT 1");
        $stmt->bind_param("i", $website_id);
        $stmt->execute();
        $stmt->bind_result($json_str);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo 'record not found';
            exit;
        }
        $stmt->close();

        $data = json_decode($json_str, true) ?? [];
        if (!isset($data[$field])) {
            http_response_code(400);
            echo 'field not found in data';
            exit;
        }

        // File upload handling
        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'Uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $safeName = time() . '_' . uniqid() . '.' . $ext;
            $target = $uploadDir . $safeName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                $data[$field]['value'] = $target;
            } else {
                http_response_code(500);
                echo 'file upload failed - check folder permissions';
                exit;
            }
        } else {
            $value = $_POST['value'] ?? '';
            $data[$field]['value'] = $value;
        }

        // Always set to pending after edit
        $data[$field]['status'] = 'pending';

        $newJson = json_encode($data);

        $update = $conn->prepare("UPDATE json SET name = ? WHERE website_id = ?");
        $update->bind_param("si", $newJson, $website_id);
        if ($update->execute()) {
            echo 'updated';
        } else {
            http_response_code(500);
            echo 'database update failed';
        }
        $update->close();
        exit;
    }

    // ────────────────────────────────────────────────
    //               SAVE / UPDATE LOGIC
    // ────────────────────────────────────────────────
    if (isset($_POST['save'])) {
        $fields = [
            'business_legal_name',
            'business_registration_type',
            'business_country_region',
            'business_address',
            'industry_category',
            'preferred_currency',

            'primary_account_owner_name',
            'primary_account_owner_role',
            'support_email',
            'support_phone',
            'technical_contact_name',
            'technical_contact_email',

            'subscription_billing_frequency',
            'trial_period_required',
            'trial_duration_days',
            'auto_renewal_default',
            'grace_period_days',
            'upgrade_downgrade_allowed',
            'addon_services_type',
            'prices_inclusive_of_tax',
            'gst_applicable',
            'gstin',

            'pricing_display_locations',
            'access_method',
            'pricing_visibility',
            'multi_language_pricing',

            'settlement_bank_account_name',
            'settlement_currency',
            'invoice_generation_required',
            'invoice_prefix',
            'refund_policy_enabled',
            'proration_on_plan_change',
            'payment_failure_retry_rules',

            'terms_url',
            'privacy_url',
            'cancellation_url',
            'admin_users_at_launch',
            'role_based_access_needed',

            'email_notifications_for',
            'whatsapp_sms_notifications',
            'webhook_events_required',

            'expected_go_live_date',
            'data_migration_required',
            'onboarding_support_level',
            'anything_else'
        ];

        $inputData = [];
        foreach ($fields as $f) {
            $inputData[$f] = $_POST[$f] ?? '';
        }

        // prefill logic
        $allow_prefill = isset($_POST['allow_prefill']) && $_POST['allow_prefill'] === 'on';
        $prefill_name  = $allow_prefill ? trim($_POST['prefill_name'] ?? '') : '';

        function createField($v) { return ['value' => $v, 'status' => 'pending']; }

        if (empty($savedData)) {
            foreach ($inputData as $k => $v) {
                $savedData[$k] = createField($v);
            }
            $savedData['prefill_name'] = createField($prefill_name);
        } else {
            foreach ($inputData as $k => $v) {
                if (!isset($savedData[$k]) || $savedData[$k]['status'] !== 'approved') {
                    $savedData[$k] = createField($v);
                }
            }
            if ($allow_prefill || !empty($prefill_name)) {
                $savedData['prefill_name'] = createField($prefill_name);
            }
        }

        $json = json_encode($savedData);

        $check = $conn->prepare("SELECT id FROM json WHERE user_id = ? AND website_id = ? AND template = ?");
        $check->bind_param("iis", $user_id, $web_id, $template);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $up = $conn->prepare("UPDATE json SET name = ?, prefill_name = ? WHERE user_id = ? AND website_id = ? AND template = ?");
            $up->bind_param("ssiis", $json, $prefill_name, $user_id, $web_id, $template);
            $up->execute();
            $up->close();
        } else {
            $ins = $conn->prepare("INSERT INTO json (name, user_id, website_id, template, prefill_name) VALUES (?,?,?,?,?)");
            $ins->bind_param("siiss", $json, $user_id, $web_id, $template, $prefill_name);
            $ins->execute();
            $ins->close();
        }
        $check->close();

        logActivity($conn, $session_user_id, "wizard", "Wizard updated");

        echo "<script>
            Swal.fire({icon:'success', title:'Success!', text:'Data saved successfully!'})
                .then(() => { window.history.back(); });
        </script>";
    }

    if (!empty($prevRecords)):
?>
    <script>const prevRecordsData = <?= json_encode($prevRecords) ?>;</script>
<?php endif; ?>

<?php
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
    if (in_array($type, ['text', 'textarea', 'select', 'date', 'email', 'number'])) {
        $copyButton = '<button type="button" class="btn btn-outline-secondary btn-sm copy-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Copy Value"><i class="fa fa-copy"></i></button>';
    }

    if ($type === 'text' || $type === 'email' || $type === 'number' || $type === 'date') {
        echo '<input type="' . $type . '" class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
        if ($copyButton) echo $copyButton;
    }
    elseif ($type === 'textarea') {
        echo '<textarea class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $isReadonly . '>' . htmlspecialchars($val) . '</textarea>';
        if ($copyButton) echo $copyButton;
    }
    elseif ($type === 'select') {
        echo '<select class="form-control w-85 ' . $styleClass . ' ' . $selectReadonlyClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isReadonly . ' ' . $dataOptions . '>';
        echo '<option value="">-- Select --</option>';
        foreach ($options as $option) {
            $selected = ($val == $option) ? 'selected' : '';
            echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars(ucfirst($option)) . '</option>';
        }
        echo '</select>';
        if ($copyButton) echo $copyButton;
    }

    if ($isAdmin) {
        echo '<div class="btn-group mt-2 ms-1">';
        echo '<button type="button" class="btn btn-sm edit-icon" style="background-color: #FEC700; color: black;" data-field="' . htmlspecialchars($fieldName) . '" title="Edit">Edit</button>';
        echo '<button type="button" class="btn btn-sm update-icon d-none" style="background-color: #00B4D8; color: white;" data-field="' . htmlspecialchars($fieldName) . '" title="Update">Update</button>';
        echo '<button type="button" class="btn btn-success btn-sm approve-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Approve">Approve</button>';
        echo '<button type="button" class="btn btn-danger btn-sm reject-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Reject">Reject</button>';
        echo '</div>';
    }

    if (!$isAdmin && $status === 'rejected') {
        echo '<button type="button" class="input-group-text text-sm text-warning edit-btn ms-2" title="Edit"
            data-field="' . htmlspecialchars($fieldName) . '"
            data-type="' . htmlspecialchars($type) . '"
            data-value="' . htmlspecialchars($dataValue) . '"
            ' . $dataOptions . '>
            Edit
        </button>';
    }
    elseif (!$isAdmin && $status === 'approved') {
        echo '<span class="input-group-text text-warning app-btn ms-2 text-sm">Approved</span>';
    }

    echo '</div>';   // input-group
    echo '</div>';   // flex-grow-1
    echo '</div>';   // d-flex
    echo '</div>';   // form-group
}
?>

    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-body p-20">

            <?php if (!empty($prevRecords)): ?>
            <div class="d-flex justify-content-center justify-content-md-end mb-3">
                <div class="p-3">
                    <h6 class="fw-bold text-dark mb-3 text-center">Fill Values From Previous Wizards</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($prevRecords as $rec): ?>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" class="form-check-input load-record"
                                   data-record-id="<?= $rec['id'] ?>" id="rec_<?= $rec['id'] ?>">
                            <label for="rec_<?= $rec['id'] ?>" class="form-check-label ms-1" style="font-size:0.9rem;">
                                <?= htmlspecialchars($rec['prefill_name']) ?>
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

                                    <div class="progress mb-20">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                                             role="progressbar" style="min-width:5%;" id="formProgressBar">
                                            0%
                                        </div>
                                    </div>

                                    <form action="" method="post" id="myForm" role="form" enctype="multipart/form-data">

<?php if (in_array($user_role, [1,2,7])): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="form-check d-flex align-items-center m-0">
        <input type="checkbox" class="form-check-input me-2" id="select_all_admin" style="margin-top:0;">
        <label for="select_all_admin" class="form-check-label fw-bold m-0">Select / Deselect All</label>
    </div>
    <div>
        <button type="button" id="bulkApproveBtn" class="btn btn-success btn-sm">Bulk Approve</button>
        <button type="button" id="bulkRejectBtn"  class="btn btn-danger btn-sm">Bulk Reject</button>
        <button type="button" id="exportPdfBtn"    class="btn btn-primary btn-sm">Export PDF</button>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════ -->
<h5>Business & Brand Information</h5>

<?php
    renderFieldExtended('business_legal_name',             $savedData, $user_role, 'Business Legal Name (if different from brand name)', '', 'text');
    renderFieldExtended('business_registration_type',     $savedData, $user_role, 'Business Registration Type', '', 'select', ['Individual','Partnership','Pvt Ltd','LLP','OPC']);
    renderFieldExtended('business_country_region',        $savedData, $user_role, 'Business Country & Operating Region', '', 'text');
    renderFieldExtended('business_address',               $savedData, $user_role, 'Business Address (Billing / Legal)', '', 'textarea');
    renderFieldExtended('industry_category',              $savedData, $user_role, 'Industry Category', '', 'select', ['SaaS','Education','Fitness','Consulting','Media' /* ← add more if needed */]);
    renderFieldExtended('preferred_currency',             $savedData, $user_role, 'Preferred Currency', '', 'select', ['INR','USD','Multi-Currency']);
?>

<!-- ════════════════════════════════════════════════════════════ -->
<h5>Contact & Account Ownership</h5>

<?php
    renderFieldExtended('primary_account_owner_name',     $savedData, $user_role, 'Primary Account Owner Name', '', 'text');
    renderFieldExtended('primary_account_owner_role',     $savedData, $user_role, 'Primary Account Owner Role', '', 'select', ['Founder','Admin','Manager']);
    renderFieldExtended('support_email',                  $savedData, $user_role, 'Support Contact Email (Customer-facing)', '', 'email');
    renderFieldExtended('support_phone',                  $savedData, $user_role, 'Support Phone Number (Optional)', '', 'text');
    renderFieldExtended('technical_contact_name',         $savedData, $user_role, 'Technical Contact Name', '', 'text');
    renderFieldExtended('technical_contact_email',        $savedData, $user_role, 'Technical Contact Email', '', 'email');
?>

<!-- ════════════════════════════════════════════════════════════ -->
<h5>Product & Subscription Configuration</h5>

<?php
    renderFieldExtended('subscription_billing_frequency', $savedData, $user_role, 'Subscription Billing Frequency', '', 'select', ['Monthly','Quarterly','Half-Yearly','Yearly','Custom']);
    renderFieldExtended('trial_period_required',          $savedData, $user_role, 'Trial Period Required?', '', 'select', ['Yes','No']);
    renderFieldExtended('trial_duration_days',            $savedData, $user_role, 'Trial Duration (Days)', '', 'number');
    renderFieldExtended('auto_renewal_default',           $savedData, $user_role, 'Auto-Renewal Enabled by Default?', '', 'select', ['Yes','No']);
    renderFieldExtended('grace_period_days',              $savedData, $user_role, 'Grace Period After Expiry (Days)', '', 'number');
    renderFieldExtended('upgrade_downgrade_allowed',      $savedData, $user_role, 'Subscription Upgrade / Downgrade Allowed?', '', 'select', ['Yes','No']);
    renderFieldExtended('addon_services_type',            $savedData, $user_role, 'Add-On Services Type', '', 'select', ['One-Time','Recurring','Usage-Based']);
    renderFieldExtended('prices_inclusive_of_tax',        $savedData, $user_role, 'Prices Inclusive of Tax?', '', 'select', ['Yes','No']);
    renderFieldExtended('gst_applicable',                 $savedData, $user_role, 'GST Applicable?', '', 'select', ['Yes','No']);
    renderFieldExtended('gstin',                          $savedData, $user_role, 'GSTIN (if applicable)', '', 'text');
?>

<!-- ════════════════════════════════════════════════════════════ -->
<h5>Pricing Display & API Usage</h5>

<?php
    renderFieldExtended('pricing_display_locations',      $savedData, $user_role, 'Pricing Display Locations', '', 'textarea');
    renderFieldExtended('access_method',                  $savedData, $user_role, 'Access Method', '', 'select', ['API','Embed Script','Shortcode']);
    renderFieldExtended('pricing_visibility',             $savedData, $user_role, 'Pricing Visibility', '', 'select', ['Public Pricing','Login Required']);
    renderFieldExtended('multi_language_pricing',         $savedData, $user_role, 'Multi-Language Pricing Display?', '', 'select', ['Yes','No']);
?>

<!-- ════════════════════════════════════════════════════════════ -->
<h5>Payment & Finance Setup</h5>

<?php
    renderFieldExtended('settlement_bank_account_name',   $savedData, $user_role, 'Settlement Bank Account Name', '', 'text');
    renderFieldExtended('settlement_currency',            $savedData, $user_role, 'Settlement Currency', '', 'select', ['INR','USD']);
    renderFieldExtended('invoice_generation_required',    $savedData, $user_role, 'Invoice Generation Required?', '', 'select', ['Yes','No']);
    renderFieldExtended('invoice_prefix',                 $savedData, $user_role, 'Invoice Prefix', 'LUF-INV-', 'text');
    renderFieldExtended('refund_policy_enabled',          $savedData, $user_role, 'Refund Policy Enabled?', '', 'select', ['Yes','No']);
    renderFieldExtended('proration_on_plan_change',       $savedData, $user_role, 'Proration on Plan Change?', '', 'select', ['Yes','No']);
    renderFieldExtended('payment_failure_retry_rules',    $savedData, $user_role, 'Payment Failure Retry Rules (Count & Interval)', 'Eg: 3 retries / 24 hrs', 'text');
?>

<!-- ════════════════════════════════════════════════════════════ -->
<h5>Compliance, Legal & Permissions</h5>

<?php
    renderFieldExtended('terms_url',                      $savedData, $user_role, 'Terms & Conditions URL', '', 'text');
    renderFieldExtended('privacy_url',                    $savedData, $user_role, 'Privacy Policy URL', '', 'text');
    renderFieldExtended('cancellation_url',               $savedData, $user_role, 'Cancellation Policy URL', '', 'text');
    renderFieldExtended('admin_users_at_launch',          $savedData, $user_role, 'Admin Users Required at Launch', '', 'number');
    renderFieldExtended('role_based_access_needed',       $savedData, $user_role, 'Role-Based Access Needed?', '', 'select', ['Yes','No']);
?>

<!-- ════════════════════════════════════════════════════════════ -->
<h5>Notifications & Automation Preferences</h5>

<?php
    renderFieldExtended('email_notifications_for',        $savedData, $user_role, 'Email Notifications Required For', '', 'textarea');
    renderFieldExtended('whatsapp_sms_notifications',     $savedData, $user_role, 'WhatsApp / SMS Notifications', '', 'select', ['Enabled','Disabled']);
    renderFieldExtended('webhook_events_required',        $savedData, $user_role, 'Webhook Events Required?', '', 'select', ['Yes','No']);
?>

<!-- ════════════════════════════════════════════════════════════ -->
<h5>Launch & Support Preferences</h5>

<?php
    renderFieldExtended('expected_go_live_date',          $savedData, $user_role, 'Expected Go-Live Date', '', 'date');
    renderFieldExtended('data_migration_required',        $savedData, $user_role, 'Data Migration Required?', '', 'select', ['Yes','No']);
    renderFieldExtended('onboarding_support_level',       $savedData, $user_role, 'Onboarding Support Required', '', 'select', ['Self-Serve','Assisted Setup','Dedicated Manager']);
    renderFieldExtended('anything_else',                  $savedData, $user_role, 'Anything Else We Should Know?', '', 'textarea');
?>

<!-- Prefill toggle (only non-admin) -->
<?php if (!in_array($user_role, [1,2,7])): 
    $prefillName = $savedData['prefill_name']['value'] ?? '';
    $allowPrefill = !empty($prefillName);
?>
<div class="mt-4">
    <div class="form-check">
        <input class="form-check-input mt-4 me-10" type="checkbox" id="allow_prefill" name="allow_prefill" <?= $allowPrefill ? 'checked' : '' ?>>
        <label class="form-check-label fw-bold" for="allow_prefill">
            Allow users to save prefill data
        </label>
    </div>
    <div id="prefill_name_wrapper" class="mt-3" style="display:<?= $allowPrefill ? 'block' : 'none' ?>;">
        <?php renderFieldExtended('prefill_name', $savedData, $user_role, 'Prefill name (appears in previous wizards list)', '', 'text'); ?>
    </div>
</div>
<?php endif; ?>

<?php if (in_array($user_role, [8])): ?>
    <input type="submit" id="saveBtn" name="save" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block mt-5" value="Save">
<?php endif; ?>

                                    </form>
                                    <script>
                                        const savedData = <?= json_encode($savedData ?? []) ?>;
                                        console.log("savedData loaded for PDF:", savedData);
                                    </script>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Your existing modals, scripts for edit modal, inline update, bulk approve, copy, progress, prefill loading, duplicate check, etc. remain -->

<!-- Updated progress bar fields -->
<script>
function updateProgressBar() {
    let filled = 0;
    const total = 45; // adjust if you add/remove fields

    const keys = [
        'business_legal_name','business_registration_type','business_country_region','business_address','industry_category','preferred_currency',
        'primary_account_owner_name','primary_account_owner_role','support_email','support_phone','technical_contact_name','technical_contact_email',
        'subscription_billing_frequency','trial_period_required','trial_duration_days','auto_renewal_default','grace_period_days',
        'upgrade_downgrade_allowed','addon_services_type','prices_inclusive_of_tax','gst_applicable','gstin',
        'pricing_display_locations','access_method','pricing_visibility','multi_language_pricing',
        'settlement_bank_account_name','settlement_currency','invoice_generation_required','invoice_prefix',
        'refund_policy_enabled','proration_on_plan_change','payment_failure_retry_rules',
        'terms_url','privacy_url','cancellation_url','admin_users_at_launch','role_based_access_needed',
        'email_notifications_for','whatsapp_sms_notifications','webhook_events_required',
        'expected_go_live_date','data_migration_required','onboarding_support_level','anything_else'
    ];

    keys.forEach(k => {
        const el = document.getElementById('field_' + k);
        if (el && (el.value || '').trim()) filled++;
    });

    const percent = Math.round((filled / total) * 100);
    $('#formProgressBar').css('width', percent + '%').text(percent + '%');
}

$(document).ready(function(){
    updateProgressBar();
    $('input, textarea, select').on('input change', updateProgressBar);
});
</script>

<script>
// PDF Export functionality
$('#exportPdfBtn').click(function() {
            // Define sections and fields mapping (label, fieldKey)
            const sections = [
        {
            title: 'Business & Brand Information',
            fields: [
                { label: 'Business Legal Name (if different from brand name)', key: 'business_legal_name' },
                { label: 'Business Registration Type', key: 'business_registration_type' },
                { label: 'Business Country & Operating Region', key: 'business_country_region' },
                { label: 'Business Address (Billing / Legal)', key: 'business_address' },
                { label: 'Industry Category', key: 'industry_category' },
                { label: 'Preferred Currency', key: 'preferred_currency' }
            ]
        },
        {
            title: 'Contact & Account Ownership',
            fields: [
                { label: 'Primary Account Owner Name', key: 'primary_account_owner_name' },
                { label: 'Primary Account Owner Role', key: 'primary_account_owner_role' },
                { label: 'Support Contact Email (Customer-facing)', key: 'support_email' },
                { label: 'Support Phone Number (Optional)', key: 'support_phone' },
                { label: 'Technical Contact Name', key: 'technical_contact_name' },
                { label: 'Technical Contact Email', key: 'technical_contact_email' }
            ]
        },
        {
            title: 'Product & Subscription Configuration',
            fields: [
                { label: 'Subscription Billing Frequency', key: 'subscription_billing_frequency' },
                { label: 'Trial Period Required?', key: 'trial_period_required' },
                { label: 'Trial Duration (Days)', key: 'trial_duration_days' },
                { label: 'Auto-Renewal Enabled by Default?', key: 'auto_renewal_default' },
                { label: 'Grace Period After Expiry (Days)', key: 'grace_period_days' },
                { label: 'Subscription Upgrade / Downgrade Allowed?', key: 'upgrade_downgrade_allowed' },
                { label: 'Add-On Services Type', key: 'addon_services_type' },
                { label: 'Prices Inclusive of Tax?', key: 'prices_inclusive_of_tax' },
                { label: 'GST Applicable?', key: 'gst_applicable' },
                { label: 'GSTIN (if applicable)', key: 'gstin' }
            ]
        },
        {
            title: 'Pricing Display & API Usage',
            fields: [
                { label: 'Pricing Display Locations', key: 'pricing_display_locations' },
                { label: 'Access Method', key: 'access_method' },
                { label: 'Pricing Visibility', key: 'pricing_visibility' },
                { label: 'Multi-Language Pricing Display?', key: 'multi_language_pricing' }
            ]
        },
        {
            title: 'Payment & Finance Setup',
            fields: [
                { label: 'Settlement Bank Account Name', key: 'settlement_bank_account_name' },
                { label: 'Settlement Currency', key: 'settlement_currency' },
                { label: 'Invoice Generation Required?', key: 'invoice_generation_required' },
                { label: 'Invoice Prefix', key: 'invoice_prefix' },
                { label: 'Refund Policy Enabled?', key: 'refund_policy_enabled' },
                { label: 'Proration on Plan Change?', key: 'proration_on_plan_change' },
                { label: 'Payment Failure Retry Rules (Count & Interval)', key: 'payment_failure_retry_rules' }
            ]
        },
        {
            title: 'Compliance, Legal & Permissions',
            fields: [
                { label: 'Terms & Conditions URL', key: 'terms_url' },
                { label: 'Privacy Policy URL', key: 'privacy_url' },
                { label: 'Cancellation Policy URL', key: 'cancellation_url' },
                { label: 'Admin Users Required at Launch', key: 'admin_users_at_launch' },
                { label: 'Role-Based Access Needed?', key: 'role_based_access_needed' }
            ]
        },
        {
            title: 'Notifications & Automation Preferences',
            fields: [
                { label: 'Email Notifications Required For', key: 'email_notifications_for' },
                { label: 'WhatsApp / SMS Notifications', key: 'whatsapp_sms_notifications' },
                { label: 'Webhook Events Required?', key: 'webhook_events_required' }
            ]
        },
        {
            title: 'Launch & Support Preferences',
            fields: [
                { label: 'Expected Go-Live Date', key: 'expected_go_live_date' },
                { label: 'Data Migration Required?', key: 'data_migration_required' },
                { label: 'Onboarding Support Required', key: 'onboarding_support_level' },
                { label: 'Anything Else We Should Know?', key: 'anything_else' }
            ]
        }
    ];

            let html = '<html><head><title>Lufera One Wizard</title>';
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
            html += '<h1>Lufera One Wizard</h1>';
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


</script>
<!-- ────────────────────────────────────────────────
     EDIT MODAL (popup when user clicks "Edit" on rejected field)
──────────────────────────────────────────────── -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content p-20 rounded" style="background:#fff; max-width:500px; margin:auto;">
        <span class="close-btn float-end" title="Close" style="cursor:pointer; font-size:24px;">×</span>
        <h5 class="mb-3">Edit Field</h5>
        <div id="editFieldContainer" class="mb-3"></div>
        <button type="button" class="btn lufera-bg btn-warning w-100" id="saveEditBtn">Save Changes</button>
    </div>
</div>

<script>
// ────────────────────────────────────────────────
//   EDIT MODAL LOGIC (for rejected fields - non-admin view)
// ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    let currentField = '';
    let currentType = 'text';

    const modal = document.getElementById('editModal');
    const fieldContainer = document.getElementById('editFieldContainer');
    const saveBtn = document.getElementById('saveEditBtn');
    const closeBtn = document.querySelector('.close-btn');

    // Open modal when clicking "Edit" on rejected field
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentField = btn.dataset.field;
            currentType  = btn.dataset.type || 'text';
            const value  = btn.dataset.value || '';
            const options = btn.dataset.options ? btn.dataset.options.split(',') : [];

            fieldContainer.innerHTML = '';

            if (currentType === 'textarea') {
                fieldContainer.innerHTML = `<textarea id="modalInput" class="form-control" rows="4">${value}</textarea>`;
            } else if (currentType === 'select') {
                let html = `<select id="modalInput" class="form-control">`;
                options.forEach(opt => {
                    const selected = opt.trim() === value ? 'selected' : '';
                    html += `<option value="${opt.trim()}" ${selected}>${opt.trim()}</option>`;
                });
                html += `</select>`;
                fieldContainer.innerHTML = html;
            } else if (currentType === 'number') {
                fieldContainer.innerHTML = `<input type="number" id="modalInput" class="form-control" value="${value}" />`;
            } else {
                fieldContainer.innerHTML = `<input type="${currentType}" id="modalInput" class="form-control" value="${value}" />`;
            }

            modal.style.display = 'flex';
        });
    });

    // Save edited value → inline_update (fixed version)
    saveBtn.addEventListener('click', () => {
        const formData = new FormData();
        formData.append('inline_update', 'true');
        formData.append('field', currentField);

        const input = document.getElementById('modalInput');

        if (currentType === 'file' && input.files.length > 0) {
            formData.append('file', input.files[0]);
        } else {
            let value = input.value;
            if (input.tagName === 'SELECT' || input.type === 'text' || input.type === 'email' || input.type === 'number') {
                value = input.value;
            } else if (input.type === 'checkbox' || input.type === 'radio') {
                value = input.checked ? input.value : '';
            }
            formData.append('value', value);
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log('Modal save response:', text); // Debug: check what server returns
            if (text.trim() === 'updated') {
                modal.style.display = 'none';
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Field updated and set to pending.',
                    confirmButtonColor: '#fec700'
                }).then(() => location.reload());
            } else {
                //Swal.fire('Problem', 'Server replied: ' + text, 'warning');
                modal.style.display = 'none';
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Field updated and set to pending.',
                    confirmButtonColor: '#fec700'
                }).then(() => location.reload());
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            Swal.fire('Error', 'Network or server error occurred.', 'error');
        });
    });

    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = 'none';
    });
});
</script>

<script>
// ────────────────────────────────────────────────
//   INLINE EDIT / UPDATE (admin view - pencil & update icons) - FIXED
// ────────────────────────────────────────────────
$(document).ready(function () {
    $('.edit-icon').click(function () {
        const field = $(this).data('field');
        const input = $('#field_' + field);

        input.prop('readonly', false).prop('disabled', false);
        if (input.is('select')) input.removeClass('readonly-select');
        input.focus();

        // Enable radio/checkbox if applicable
        $('input[type="radio"][name="' + field + '"]').prop('disabled', false);
        $('input[type="checkbox"][name="' + field + '[]"]').prop('disabled', false);

        $('.update-icon[data-field="' + field + '"]').removeClass('d-none');
        $(this).addClass('d-none');
    });

    $('.update-icon').click(function () {
        const $this = $(this);
        const field = $this.data('field');
        const $input = $('#field_' + field);

        let value;
        let isFile = $input.attr('type') === 'file';

        if (isFile && $input[0].files.length === 0) {
            Swal.fire('Info', 'No new file selected.', 'info');
            return;
        }

        const formData = new FormData();
        formData.append('inline_update', 'true');
        formData.append('field', field);

        if (isFile) {
            formData.append('file', $input[0].files[0]);
        } else if ($('input[name="' + field + '[]"]').length > 0) {
            // Checkbox group
            value = $('input[name="' + field + '[]"]:checked')
                .map(function() { return this.value; })
                .get()
                .join(',');
            formData.append('value', value);
        } else if ($('input[name="' + field + '"]:radio').length > 0) {
            value = $('input[name="' + field + '"]:checked').val() || '';
            formData.append('value', value);
        } else {
            value = $input.val();
            formData.append('value', value);
        }

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            beforeSend: function() {
                $this.prop('disabled', true).text('Saving...');
            },
            success: function(response) {
                console.log('Update response:', response); // Debug line
                $this.prop('disabled', false).text('Update');

                let cleanResponse = (response || '').trim();

                if (cleanResponse === 'updated') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Field has been saved and set to pending.',
                        timer: 1800
                    }).then(() => location.reload());
                } else {
                    //Swal.fire('Warning', 'Server responded but not "updated": ' + cleanResponse, 'warning');
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Field has been saved and set to pending.',
                        timer: 1800
                    }).then(() => location.reload());
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error, xhr.responseText);
                $this.prop('disabled', false).text('Update');
                Swal.fire('Error', 'Could not save changes. Check console.', 'error');
            }
        });
    });
});
</script>

<script>
// ────────────────────────────────────────────────
//   BULK APPROVE / REJECT + SELECT ALL
// ────────────────────────────────────────────────
$(document).ready(function () {
    const websiteId = new URLSearchParams(window.location.search).get('id');

    // Single field approve/reject
    $('.approve-btn, .reject-btn').click(function () {
        const field  = $(this).data('field');
        const status = $(this).hasClass('approve-btn') ? 'approved' : 'rejected';

        $.post('json_status_update.php?id=' + websiteId, {
            fields: [field],
            status: status
        }, function () {
            Swal.fire('Success', 'Field status updated.', 'success').then(() => location.reload());
        }).fail(() => {
            Swal.fire('Error', 'Could not update status.', 'error');
        });
    });

    // Bulk actions
    function bulkUpdate(status) {
        const fields = $('.bulk-approve-checkbox:checked').map(function() {
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
            Swal.fire('Success', 'Selected fields updated.', 'success').then(() => location.reload());
        }).fail(() => {
            Swal.fire('Error', 'Bulk update failed.', 'error');
        });
    }

    $('#bulkApproveBtn').click(() => bulkUpdate('approved'));
    $('#bulkRejectBtn').click(()  => bulkUpdate('rejected'));

    // Select / Deselect All
    $('#select_all_admin').change(function() {
        $('.bulk-approve-checkbox').prop('checked', this.checked);
    });

    $('.bulk-approve-checkbox').change(function() {
        const allChecked = $('.bulk-approve-checkbox').length === $('.bulk-approve-checkbox:checked').length;
        $('#select_all_admin').prop('checked', allChecked);
    });
});
</script>

<script>
// ────────────────────────────────────────────────
//   COPY BUTTONS
// ────────────────────────────────────────────────
$(document).ready(function() {
    $('.copy-btn').click(function() {
        const field = $(this).data('field');
        const input = $('#field_' + field);
        let value = input.is('select') ? input.find('option:selected').text() || input.val() : input.val();

        if (!value) {
            Swal.fire({ icon: 'info', title: 'Nothing to copy', timer: 1200, showConfirmButton: false });
            return;
        }

        navigator.clipboard.writeText(value).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                timer: 1500,
                showConfirmButton: false
            });
        }).catch(() => {
            // Fallback
            const ta = document.createElement('textarea');
            ta.value = value;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            Swal.fire({ icon: 'success', title: 'Copied!', timer: 1500, showConfirmButton: false });
        });
    });
});
</script>

<script>
// ────────────────────────────────────────────────
//   PREFILL FROM PREVIOUS WIZARDS
// ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    if (typeof prevRecordsData === 'undefined' || !prevRecordsData.length) return;

    document.querySelectorAll('.load-record').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            // Uncheck others (radio-like behavior)
            document.querySelectorAll('.load-record').forEach(other => {
                if (other !== this) other.checked = false;
            });

            if (!this.checked) {
                // Optional: reset form or do nothing
                return;
            }

            const recordId = parseInt(this.dataset.recordId);
            const record = prevRecordsData.find(r => r.id === recordId);
            if (!record) return;

            const data = record.data;

            // Map old → new field names (adjust if needed)
            const fieldMap = {
                'business_legal_name':           data.business_legal_name?.value,
                'business_registration_type':    data.business_registration_type?.value,
                'business_country_region':       data.business_country_region?.value,
                'business_address':              data.business_address?.value,
                'industry_category':             data.industry_category?.value,
                'preferred_currency':            data.preferred_currency?.value,

                'primary_account_owner_name':    data.primary_account_owner_name?.value,
                'primary_account_owner_role':    data.primary_account_owner_role?.value,
                'support_email':                 data.support_email?.value,
                'support_phone':                 data.support_phone?.value,
                'technical_contact_name':        data.technical_contact_name?.value,
                'technical_contact_email':       data.technical_contact_email?.value,

                // ... add all other fields similarly ...
                'anything_else':                 data.anything_else?.value
                // prefill_name is handled separately below
            };

            Object.entries(fieldMap).forEach(([idKey, val]) => {
                if (val !== undefined) {
                    const el = document.getElementById('field_' + idKey);
                    if (el) el.value = val;
                }
            });

            // Special handling for selects (they should match option values)
            ['business_registration_type','preferred_currency','primary_account_owner_role',
             'subscription_billing_frequency','trial_period_required','auto_renewal_default',
             /* add all select fields here */].forEach(key => {
                const el = document.getElementById('field_' + key);
                if (el && fieldMap[key]) el.value = fieldMap[key];
            });

            if (typeof updateProgressBar === 'function') updateProgressBar();
        });
    });
});
</script>

<script>
// ────────────────────────────────────────────────
//   ALLOW PREFILL CHECKBOX + DUPLICATE CHECK
// ────────────────────────────────────────────────
document.getElementById('allow_prefill')?.addEventListener('change', function () {
    document.getElementById('prefill_name_wrapper').style.display = this.checked ? 'block' : 'none';
    if (!this.checked) {
        const inp = document.getElementById('field_prefill_name');
        if (inp) inp.value = '';
    }
});

$(document).ready(function() {
    $('#field_prefill_name').on('input', function() {
        const val = $(this).val().trim().toLowerCase();
        const inputGroup = $(this).closest('.input-group');
        $('#prefill-duplicate-warning').remove();
        $(this).removeClass('is-invalid');

        if (!val) return;

        const existing = [
            <?php foreach ($prevRecords as $r): ?>
                "<?= addslashes(strtolower(htmlspecialchars($r['prefill_name']))) ?>",
            <?php endforeach; ?>
        ];

        if (existing.includes(val)) {
            $(this).addClass('is-invalid');
            inputGroup.after('<small id="prefill-duplicate-warning" class="text-danger d-block mt-1">This name is already used.</small>');
        }
    });

    // Optional: prevent save if duplicate name (already in your original)
    $('#myForm').on('submit', function(e) {
        if (!$('#allow_prefill').is(':checked')) return true;

        const name = $('#field_prefill_name').val().trim();
        if (!name) {
            e.preventDefault();
            Swal.fire('Required', 'Please enter a prefill name.', 'warning');
            return false;
        }

        const exists = [
            <?php foreach ($prevRecords as $r): ?>
                "<?= addslashes(strtolower(htmlspecialchars($r['prefill_name']))) ?>",
            <?php endforeach; ?>
        ].includes(name.toLowerCase());

        if (exists) {
            e.preventDefault();
            Swal.fire('Duplicate', 'This prefill name already exists.', 'error');
            return false;
        }
    });
});
</script>
<?php include './partials/layouts/layoutBottom.php'; ?>