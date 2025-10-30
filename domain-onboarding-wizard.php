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

    .marketing-header {
        font-size: 26px !important;
        font-weight: 700;
        color: #212529;
        text-align: center;
        padding: 18px 16px 14px;
        margin-bottom: 30px;
        border-top: 5px solid #fec700;
        background-color: #ffffff;
        border-radius: 0 0 8px 8px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.07);
    }

    .w-85{
        width:85% !important;
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

    .notice-yellow {
        background-color: #fff3cd;
        color: #000;
        border-left: 6px solid #ffc107;
        padding: 16px 20px;
        margin: 20px 0;
        font-size: 15px;
        border-radius: 4px;
        line-height: 1.6;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .notice-yellow strong {
        display: block;
        font-weight: bold;
        margin-bottom: 6px;
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
        if ($decoded && isset($decoded['full_name']['value'])) {
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
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $domain_name = $_POST['domain_name'] ?? '';
        $registered = $_POST['registered'] ?? '';
        $registrar = $_POST['registrar'] ?? '';
        $transfer_assistance = $_POST['transfer_assistance'] ?? '';
        $expected_price = $_POST['expected_price'] ?? '';
        $listed_elsewhere = $_POST['listed_elsewhere'] ?? '';
        $listing_platforms = $_POST['listing_platforms'] ?? '';
        $sale_type = $_POST['sale_type'] ?? '';
        $domain_niche = $_POST['domain_niche'] ?? '';
        $additional_assets = $_POST['additional_assets'] ?? '';
        $communication_mode = $_POST['communication_mode'] ?? '';
        $additional_notes = $_POST['additional_notes'] ?? '';
        $reg_first_name = $_POST['reg_first_name'] ?? '';
        $reg_last_name = $_POST['reg_last_name'] ?? '';
        $reg_email = $_POST['reg_email'] ?? '';
        $reg_company = $_POST['reg_company'] ?? '';
        $reg_address = $_POST['reg_address'] ?? '';
        $reg_city = $_POST['reg_city'] ?? '';
        $reg_state = $_POST['reg_state'] ?? '';
        $reg_country = $_POST['reg_country'] ?? '';
        $reg_zip = $_POST['reg_zip'] ?? '';
        $reg_phone_code = $_POST['reg_phone_code'] ?? '';
        $reg_phone = $_POST['reg_phone'] ?? '';
        $admin_first_name = $_POST['admin_first_name'] ?? '';
        $admin_last_name = $_POST['admin_last_name'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_company = $_POST['admin_company'] ?? '';
        $admin_address = $_POST['admin_address'] ?? '';
        $admin_city = $_POST['admin_city'] ?? '';
        $admin_state = $_POST['admin_state'] ?? '';
        $admin_country = $_POST['admin_country'] ?? '';
        $admin_zip = $_POST['admin_zip'] ?? '';
        $admin_phone_code = $_POST['admin_phone_code'] ?? '';
        $admin_phone = $_POST['admin_phone'] ?? '';
        $tech_first_name = $_POST['tech_first_name'] ?? '';
        $tech_last_name = $_POST['tech_last_name'] ?? '';
        $tech_email = $_POST['tech_email'] ?? '';
        $tech_company = $_POST['tech_company'] ?? '';
        $tech_address = $_POST['tech_address'] ?? '';
        $tech_city = $_POST['tech_city'] ?? '';
        $tech_state = $_POST['tech_state'] ?? '';
        $tech_country = $_POST['tech_country'] ?? '';
        $tech_zip = $_POST['tech_zip'] ?? '';
        $tech_phone_code = $_POST['tech_phone_code'] ?? '';
        $tech_phone = $_POST['tech_phone'] ?? '';
        $bill_first_name = $_POST['bill_first_name'] ?? '';
        $bill_last_name = $_POST['bill_last_name'] ?? '';
        $bill_email = $_POST['bill_email'] ?? '';
        $bill_company = $_POST['bill_company'] ?? '';
        $bill_address = $_POST['bill_address'] ?? '';
        $bill_city = $_POST['bill_city'] ?? '';
        $bill_state = $_POST['bill_state'] ?? '';
        $bill_country = $_POST['bill_country'] ?? '';
        $bill_zip = $_POST['bill_zip'] ?? '';
        $bill_phone_code = $_POST['bill_phone_code'] ?? '';
        $bill_phone = $_POST['bill_phone'] ?? '';

        function createField($value) {
            return [
                'value' => $value,
                'status' => 'pending'
            ];
        }

        $data = json_encode([
            'full_name' => createField($full_name),
            'email' => createField($email),
            'phone' => createField($phone),
            'domain_name' => createField($domain_name),
            'registered' => createField($registered),
            'registrar' => createField($registrar),
            'transfer_assistance' => createField($transfer_assistance),
            'expected_price' => createField($expected_price),
            'listed_elsewhere' => createField($listed_elsewhere),
            'listing_platforms' => createField($listing_platforms),
            'sale_type' => createField($sale_type),
            'domain_niche' => createField($domain_niche),
            'additional_assets' => createField($additional_assets),
            'communication_mode' => createField($communication_mode),
            'additional_notes' => createField($additional_notes),
            'reg_first_name' => createField($reg_first_name),
            'reg_last_name' => createField($reg_last_name),
            'reg_email' => createField($reg_email),
            'reg_company' => createField($reg_company),
            'reg_address' => createField($reg_address),
            'reg_city' => createField($reg_city),
            'reg_state' => createField($reg_state),
            'reg_country' => createField($reg_country),
            'reg_zip' => createField($reg_zip),
            'reg_phone_code' => createField($reg_phone_code),
            'reg_phone' => createField($reg_phone),
            'admin_first_name' => createField($admin_first_name),
            'admin_last_name' => createField($admin_last_name),
            'admin_email' => createField($admin_email),
            'admin_company' => createField($admin_company),
            'admin_address' => createField($admin_address),
            'admin_city' => createField($admin_city),
            'admin_state' => createField($admin_state),
            'admin_country' => createField($admin_country),
            'admin_zip' => createField($admin_zip),
            'admin_phone_code' => createField($admin_phone_code),
            'admin_phone' => createField($admin_phone),
            'tech_first_name' => createField($tech_first_name),
            'tech_last_name' => createField($tech_last_name),
            'tech_email' => createField($tech_email),
            'tech_company' => createField($tech_company),
            'tech_address' => createField($tech_address),
            'tech_city' => createField($tech_city),
            'tech_state' => createField($tech_state),
            'tech_country' => createField($tech_country),
            'tech_zip' => createField($tech_zip),
            'tech_phone_code' => createField($tech_phone_code),
            'tech_phone' => createField($tech_phone),
            'bill_first_name' => createField($bill_first_name),
            'bill_last_name' => createField($bill_last_name),
            'bill_email' => createField($bill_email),
            'bill_company' => createField($bill_company),
            'bill_address' => createField($bill_address),
            'bill_city' => createField($bill_city),
            'bill_state' => createField($bill_state),
            'bill_country' => createField($bill_country),
            'bill_zip' => createField($bill_zip),
            'bill_phone_code' => createField($bill_phone_code),
            'bill_phone' => createField($bill_phone),
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
        <div class="d-flex justify-content-center justify-content-md-end mt-3 me-md-5" style="margin-bottom:0;">
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
                                <?php echo htmlspecialchars($record['data']['full_name']['value']); ?>
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
        if ($type === 'text' || $type === 'email') {
            echo '<input type="' . $type . '" class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($val) . '" ' . $isReadonly . '>';
        }

        // === TEXTAREA ===
        elseif ($type === 'textarea') {           
            echo '<textarea class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $isReadonly . '>' . htmlspecialchars($val) . '</textarea>';
        }

        // // === SELECT (Dropdown) ===
        // elseif ($type === 'select') {
        //     echo '<select class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isDisabled . '>';
        //     foreach ($options as $option) {
        //         $selected = ($val == $option) ? 'selected' : '';
        //         echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
        //     }
        //     echo '</select>';
        // }

        // === SELECT (Dropdown) ===
        elseif ($type === 'select') {
            echo '<select class="form-control w-85 ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . $isDisabled . '>';

            // Default placeholder option
            echo '<option value="" disabled ' . (empty($val) ? 'selected' : '') . '>Select an option</option>';

            foreach ($options as $option) {
                $selected = ($val == $option) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
            }

            echo '</select>';
        }

        // // === RADIO ===
        // elseif ($type === 'radio') {
        //     foreach ($options as $option) {
        //         $checked = ($val == $option) ? 'checked' : '';
        //         echo '<div class="form-check form-check-inline">';
        //         // echo '<input class="form-check-input" type="radio" id="' . $inputId . '_' . $option . '" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . ($isAdmin ? 'disabled' : '') . '>';
        //         echo '<input class="form-check-input" type="radio" id="' . $inputId . '_' . $option . '" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . $isDisabled . '>';
        //         echo '<label class="form-check-label" for="' . $inputId . '_' . $option . '">' . htmlspecialchars($option) . '</label>';
        //         echo '</div>';
        //     }
        // }

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
            // echo '<input type="file" class="form-control ' . $styleClass . '" id="' . $inputId . '" name="' . htmlspecialchars($fieldName) . '" ' . ($isAdmin ? 'disabled' : '') . '>';
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
            echo '<button type="button" class="btn btn-sm edit-icon" style="background-color: #FEC700; color: black;" data-field="' . htmlspecialchars($fieldName) . '" title="Edit">&#9998;</button>';
            echo '<button type="button" class="btn btn-sm update-icon d-none" style="background-color: #00B4D8; color: white;" data-field="' . htmlspecialchars($fieldName) . '" title="Update">&#128190;</button>';
            echo '<button type="button" class="btn btn-success btn-sm approve-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Approve">&#10004;</button>';
            echo '<button type="button" class="btn btn-danger btn-sm reject-btn" data-field="' . htmlspecialchars($fieldName) . '" title="Reject">&#10006;</button>';
            echo '</div>';
        }

        // === USER Rejected Fields – Show Edit Icon ===
        // if (!$isAdmin && $status === 'rejected') {
        //     echo '<button type="button" class="input-group-text text-warning edit-btn ms-2" title="Edit"
        //         data-field="' . htmlspecialchars($fieldName) . '"
        //         data-type="' . htmlspecialchars($type) . '"
        //         data-value="' . htmlspecialchars($dataValue) . '"
        //         ' . $dataOptions . '>
        //         &#9998;
        //     </button>';
        // }

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
    <div class="d-flex flex-wrap align-items-center gap-3 mb-24">
    <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; </a>     
        <h6 class="fw-semibold mb-0">Domain Sales Client Onboarding Form</h6>
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

                                    <h5>1. Client Information</h5>
                                        <?php
                                            renderFieldExtended('full_name', $savedData, $user_role, 'Full Name', '', 'text');
                                            renderFieldExtended('email', $savedData, $user_role, 'Email Address', '', 'text');
                                            renderFieldExtended('phone', $savedData, $user_role, 'Phone Number', '', 'text');
                                        ?>

                                    <h5>2. Domain Details</h5>
                                        <?php
                                            renderFieldExtended('domain_name', $savedData, $user_role, 'Domain Name', 'example.com', 'text');
                                            renderFieldExtended('registered', $savedData, $user_role, 'Is the domain already registered?', '', 'select', ['Yes', 'No']);
                                            renderFieldExtended('registrar', $savedData, $user_role, 'Registrar (if registered)', '', 'text');
                                            renderFieldExtended('transfer_assistance', $savedData, $user_role, 'Do you want us to handle the transfer process?', '', 'select', ['Yes', 'No']);
                                        ?>

                                    <h5>3. Sale Details</h5>
                                    <?php
                                        renderFieldExtended('expected_price', $savedData, $user_role, 'Expected Selling Price (INR/USD)', '', 'text');
                                        renderFieldExtended('listed_elsewhere', $savedData, $user_role, 'Is the domain listed elsewhere?', '', 'select', ['No', 'Yes']);
                                        renderFieldExtended('listing_platforms', $savedData, $user_role, 'If yes, mention platforms (e.g., Sedo, Afternic, Flippa)', '', 'textarea');
                                        renderFieldExtended('sale_type', $savedData, $user_role, 'Is it part of a bundle or single domain sale?', '', 'select', ['Single Domain', 'Part of a Bundle']);
                                    ?>

                                    <h5>4. Domain Use and Branding</h5>
                                    <?php
                                        renderFieldExtended('domain_niche', $savedData, $user_role, 'Describe the value or niche of the domain', '', 'textarea');
                                        renderFieldExtended('additional_assets', $savedData, $user_role, 'Any additional assets included? (e.g., logo, website)', '', 'textarea');
                                    ?>

                                    <h5>5. Communication & Support</h5>
                                    <?php
                                        renderFieldExtended('communication_mode', $savedData, $user_role, 'Preferred Mode of Communication', '', 'select', ['Email','WhatsApp','Phone']);
                                        renderFieldExtended('additional_notes', $savedData, $user_role, 'Additional Notes / Instructions', '', 'textarea');
                                    ?>

                                    <h5>6. Domain Contact Details (Wizard)</h5>
                                    <div class="notice-yellow">
                                        <strong>Important:</strong> Accurate details are crucial to maintain your domain's security and compliance with ICANN (domain name regulator). Incorrect information can lead to your domain registration getting cancelled under the terms of your registration agreement.
                                        <br><br>
                                        If any of the stated information is out of date or incorrect, please make sure to update your information through our Wizard at any time.
                                    </div>

                                    <!-- Registrant Contact -->
                                    <h6>Registrant Contact</h6>
                                    <?php
                                        renderFieldExtended('reg_first_name', $savedData, $user_role, 'First Name', '', 'text');
                                        renderFieldExtended('reg_last_name', $savedData, $user_role, 'Last Name', '', 'text');
                                        renderFieldExtended('reg_email', $savedData, $user_role, 'Email', '', 'text');
                                        renderFieldExtended('reg_company', $savedData, $user_role, 'Company Name', '', 'text');
                                        renderFieldExtended('reg_address', $savedData, $user_role, 'Address', '', 'text');
                                        renderFieldExtended('reg_city', $savedData, $user_role, 'City', '', 'text');
                                        renderFieldExtended('reg_state', $savedData, $user_role, 'State', '', 'text');
                                        renderFieldExtended('reg_country', $savedData, $user_role, 'Country Code', '', 'text');
                                        renderFieldExtended('reg_zip', $savedData, $user_role, 'Zip', '', 'text');
                                        renderFieldExtended('reg_phone_code', $savedData, $user_role, 'Phone Country Code', '', 'text');
                                        renderFieldExtended('reg_phone', $savedData, $user_role, 'Phone Number', '', 'text');
                                    ?>

                                    <!-- Admin Contact -->
                                    <div class="d-flex justify-content-between"><h6>Administrative Contact</h6> <span><input type="checkbox" class="form-check-input mt-4" name="admin_same"> Copy if same as Registrant</span></div>
                                    <?php
                                        renderFieldExtended('admin_first_name', $savedData, $user_role, 'First Name', '', 'text');
                                        renderFieldExtended('admin_last_name', $savedData, $user_role, 'Last Name', '', 'text');
                                        renderFieldExtended('admin_email', $savedData, $user_role, 'Email', '', 'text');
                                        renderFieldExtended('admin_company', $savedData, $user_role, 'Company Name', '', 'text');
                                        renderFieldExtended('admin_address', $savedData, $user_role, 'Address', '', 'text');
                                        renderFieldExtended('admin_city', $savedData, $user_role, 'City', '', 'text');
                                        renderFieldExtended('admin_state', $savedData, $user_role, 'State', '', 'text');
                                        renderFieldExtended('admin_country', $savedData, $user_role, 'Country Code', '', 'text');
                                        renderFieldExtended('admin_zip', $savedData, $user_role, 'Zip', '', 'text');
                                        renderFieldExtended('admin_phone_code', $savedData, $user_role, 'Phone Country Code', '', 'text');
                                        renderFieldExtended('admin_phone', $savedData, $user_role, 'Phone Number', '', 'text');
                                    ?>

                                    <!-- Tech Contact -->
                                    <div class="d-flex justify-content-between"><h6>Technical Contact</h6> <span><input type="checkbox" class="form-check-input mt-4" name="tech_same"> Copy if same as Registrant</span></div>
                                    <?php
                                        renderFieldExtended('tech_first_name', $savedData, $user_role, 'First Name', '', 'text');
                                        renderFieldExtended('tech_last_name', $savedData, $user_role, 'Last Name', '', 'text');
                                        renderFieldExtended('tech_email', $savedData, $user_role, 'Email', '', 'text');
                                        renderFieldExtended('tech_company', $savedData, $user_role, 'Company Name', '', 'text');
                                        renderFieldExtended('tech_address', $savedData, $user_role, 'Address', '', 'text');
                                        renderFieldExtended('tech_city', $savedData, $user_role, 'City', '', 'text');
                                        renderFieldExtended('tech_state', $savedData, $user_role, 'State', '', 'text');
                                        renderFieldExtended('tech_country', $savedData, $user_role, 'Country Code', '', 'text');
                                        renderFieldExtended('tech_zip', $savedData, $user_role, 'Zip', '', 'text');
                                        renderFieldExtended('tech_phone_code', $savedData, $user_role, 'Phone Country Code', '', 'text');
                                        renderFieldExtended('tech_phone', $savedData, $user_role, 'Phone Number', '', 'text');
                                    ?>

                                    <!-- Billing Contact -->
                                    <div class="d-flex justify-content-between"><h6>Billing Contact</h6> <span><input type="checkbox" class="form-check-input mt-4" name="billing_same"> Copy if same as Registrant</span></div>
                                    <?php
                                        renderFieldExtended('bill_first_name', $savedData, $user_role, 'First Name', '', 'text');
                                        renderFieldExtended('bill_last_name', $savedData, $user_role, 'Last Name', '', 'text');
                                        renderFieldExtended('bill_email', $savedData, $user_role, 'Email', '', 'text');
                                        renderFieldExtended('bill_company', $savedData, $user_role, 'Company Name', '', 'text');
                                        renderFieldExtended('bill_address', $savedData, $user_role, 'Address', '', 'text');
                                        renderFieldExtended('bill_city', $savedData, $user_role, 'City', '', 'text');
                                        renderFieldExtended('bill_state', $savedData, $user_role, 'State', '', 'text');
                                        renderFieldExtended('bill_country', $savedData, $user_role, 'Country Code', '', 'text');
                                        renderFieldExtended('bill_zip', $savedData, $user_role, 'Zip', '', 'text');
                                        renderFieldExtended('bill_phone_code', $savedData, $user_role, 'Phone Country Code', '', 'text');
                                        renderFieldExtended('bill_phone', $savedData, $user_role, 'Phone Number', '', 'text');
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
                // else {
                //     // Default input (text, email, number etc.)
                //     fieldContainer.innerHTML = `<input type="text" id="modalInput" class="form-control" value="${value}" />`;
                // }
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

<!-- Progress Bar -->
<script>
    const fieldNames = [
        'full_name',
        'email',
        'phone',
        'domain_name',
        'registered',
        'registrar',
        'transfer_assistance',
        'expected_price',
        'listed_elsewhere',
        'listing_platforms',
        'sale_type',
        'domain_niche',
        'additional_assets',
        'communication_mode',
        'additional_notes',
        'reg_first_name',
        'reg_last_name',
        'reg_email',
        'reg_company',
        'reg_address',
        'reg_city',
        'reg_state',
        'reg_country',
        'reg_zip',
        'reg_phone_code',
        'reg_phone',
        'admin_first_name',
        'admin_last_name',
        'admin_email',
        'admin_company',
        'admin_address',
        'admin_city',
        'admin_state',
        'admin_country',
        'admin_zip',
        'admin_phone_code',
        'admin_phone',
        'tech_first_name',
        'tech_last_name',
        'tech_email',
        'tech_company',
        'tech_address',
        'tech_city',
        'tech_state',
        'tech_country',
        'tech_zip',
        'tech_phone_code',
        'tech_phone',
        'bill_first_name',
        'bill_last_name',
        'bill_email',
        'bill_company',
        'bill_address',
        'bill_city',
        'bill_state',
        'bill_country',
        'bill_zip',
        'bill_phone_code',
        'bill_phone'
    ];

    function normalizeName(name) {
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
        $(document).on('input change', 'input, select, textarea', updateProgressBar);
    });
</script>

<script>
    $(document).ready(function () {
    // Object to store original values for each section
    const originalValues = {
        admin: {},
        tech: {},
        bill: {}
    };

    // Function to save original values for a section
    function saveOriginalValues(prefix) {
        const fields = ['first_name', 'last_name', 'email', 'company', 'address', 'city', 'state', 'country', 'zip', 'phone_code', 'phone'];
        originalValues[prefix] = {};
        fields.forEach(field => {
            const $input = $(`[name="${prefix}_${field}"]`);
            originalValues[prefix][field] = $input.val() || '';
        });
    }

    // Function to copy Registrant values to a section
    function copyRegistrantTo(prefix) {
        const fields = ['first_name', 'last_name', 'email', 'company', 'address', 'city', 'state', 'country', 'zip', 'phone_code', 'phone'];
        fields.forEach(field => {
            const regVal = $(`[name="reg_${field}"]`).val();
            $(`[name="${prefix}_${field}"]`).val(regVal).trigger('input');
        });
    }

    // Function to restore original values for a section
    function restoreOriginalValues(prefix) {
        const fields = ['first_name', 'last_name', 'email', 'company', 'address', 'city', 'state', 'country', 'zip', 'phone_code', 'phone'];
        fields.forEach(field => {
            $(`[name="${prefix}_${field}"]`).val(originalValues[prefix][field] || '').trigger('input');
        });
    }

    // Initialize original values for all sections on page load
    ['admin', 'tech', 'bill'].forEach(prefix => {
        saveOriginalValues(prefix);
    });

    // Handle Admin Contact checkbox
    $('input[name="admin_same"]').on('change', function () {
        if ($(this).is(':checked')) {
            saveOriginalValues('admin'); // Save current values before overwriting
            copyRegistrantTo('admin');
        } else {
            restoreOriginalValues('admin');
        }
    });

    // Handle Tech Contact checkbox
    $('input[name="tech_same"]').on('change', function () {
        if ($(this).is(':checked')) {
            saveOriginalValues('tech'); // Save current values before overwriting
            copyRegistrantTo('tech');
        } else {
            restoreOriginalValues('tech');
        }
    });

    // Handle Billing Contact checkbox
    $('input[name="billing_same"]').on('change', function () {
        if ($(this).is(':checked')) {
            saveOriginalValues('bill'); // Save current values before overwriting
            copyRegistrantTo('bill');
        } else {
            restoreOriginalValues('bill');
        }
    });
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
    document.querySelectorAll('.load-record').forEach(cb => {
        cb.addEventListener('change', function () {
            const form = document.getElementById('myForm');

            // Uncheck all other checkboxes
            document.querySelectorAll('.load-record').forEach(other => {
                if (other !== this) other.checked = false;
            });

            if (this.checked) {
                const data = JSON.parse(this.dataset.record);

                // List of all fields to populate
                const fields = [
                    'full_name', 'email', 'phone', 'domain_name', 'registered', 'registrar',
                    'transfer_assistance', 'expected_price', 'listed_elsewhere', 'listing_platforms',
                    'sale_type', 'domain_niche', 'additional_assets', 'communication_mode',
                    'additional_notes', 'reg_first_name', 'reg_last_name', 'reg_email',
                    'reg_company', 'reg_address', 'reg_city', 'reg_state', 'reg_country',
                    'reg_zip', 'reg_phone_code', 'reg_phone', 'admin_first_name',
                    'admin_last_name', 'admin_email', 'admin_company', 'admin_address',
                    'admin_city', 'admin_state', 'admin_country', 'admin_zip',
                    'admin_phone_code', 'admin_phone', 'tech_first_name', 'tech_last_name',
                    'tech_email', 'tech_company', 'tech_address', 'tech_city', 'tech_state',
                    'tech_country', 'tech_zip', 'tech_phone_code', 'tech_phone',
                    'bill_first_name', 'bill_last_name', 'bill_email', 'bill_company',
                    'bill_address', 'bill_city', 'bill_state', 'bill_country', 'bill_zip',
                    'bill_phone_code', 'bill_phone'
                ];

                // Populate text, email, textarea, and select fields
                fields.forEach(field => {
                    const input = document.getElementById(`field_${field}`);
                    if (input) {
                        const value = data[field]?.value || '';
                        if (input.tagName.toLowerCase() === 'select') {
                            // Handle select fields
                            Array.from(input.options).forEach(option => {
                                option.selected = option.value === value;
                            });
                        } else {
                            // Handle text, email, textarea
                            input.value = value;
                        }
                    }
                });

                // Trigger input event to update progress bar
                if (typeof updateProgressBar === 'function') {
                    updateProgressBar();
                }

            } else {
                // Reset the form if unchecked
                form.reset();
                if (typeof updateProgressBar === 'function') {
                    updateProgressBar();
                }
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
<?php include './partials/layouts/layoutBottom.php' ?>
