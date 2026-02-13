<?php
    $isViewMode = isset($_GET['mode']) && $_GET['mode'] === 'view';

    $sheet_id_from_url = isset($_GET['sheet_id']) && is_numeric($_GET['sheet_id'])
    ? (int)$_GET['sheet_id']: 0;

    if ($isViewMode) {
        include './partials/connection.php';
    } else {
        include './partials/layouts/layoutTop.php';
    }

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
?>

<?php
    /* ======================================================
    SHARE FORM EMAIL HANDLER
    ====================================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_email'])) {

        $to      = trim($_POST['to'] ?? '');
        $subject = trim($_POST['subject'] ?? 'Shared Form');
        $message = trim($_POST['message'] ?? '');
        $link    = trim($_POST['link'] ?? '');

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            exit;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['EMAIL_USERNAME'];
            $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
            $mail->addAddress($to);

            if (!empty($_POST['cc'])) {
                $mail->addCC($_ENV['EMAIL_USERNAME']);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body =
                nl2br(htmlspecialchars($message)) .
                "<br><br><a href='{$link}'>{$link}</a>";

            $mail->send();
            http_response_code(200);

        } catch (Exception $e) {
            error_log("Share email failed: " . $mail->ErrorInfo);
            http_response_code(500);
        }

        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sheet_data'])) {

        $formId   = (int)$_POST['form_id'];
        $newData  = json_decode($_POST['sheet_data'], true);

        /* =========================================
        FILE UPLOAD HANDLING FOR SHEET
        ========================================= */

        $uploadDir = __DIR__ . '/uploads/sheet_attachments/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!empty($_FILES['uploaded_files'])) {

            foreach ($_FILES['uploaded_files']['name'] as $index => $fileName) {

                if ($_FILES['uploaded_files']['error'][$index] === UPLOAD_ERR_OK) {

                    $tmpName = $_FILES['uploaded_files']['tmp_name'][$index];

                    // Keep original filename (sanitize only)
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

                    $targetPath = $uploadDir . $safeName;

                    // Overwrite if file exists
                    if (file_exists($targetPath)) {
                        unlink($targetPath);
                    }

                    if (move_uploaded_file($tmpName, $targetPath)) {

                        foreach ($newData['cells'] as $cellKey => $cellValue) {

                            if ($cellValue === $fileName) {

                                $newData['cells'][$cellKey] =
                                    'uploads/sheet_attachments/' . $safeName;
                            }
                        }
                    }
                }
            }
        }

        /* ===============================
        FETCH CONFIRM MESSAGE (FIXED)
        =============================== */
        $confirmMessage = "We've captured your response.";
         $formTitle      = 'Form Submission'; // ✅ ALWAYS DEFINED

        $stmtMsg = $conn->prepare(
            "SELECT form_title, form_settings FROM form_builder WHERE id = ? LIMIT 1"
        );
        $stmtMsg->bind_param("i", $formId);
        $stmtMsg->execute();
        $msgResult = $stmtMsg->get_result();

        if ($msgResult && $msgResult->num_rows === 1) {
            $settingsRow = $msgResult->fetch_assoc();

            $formTitle = $settingsRow['form_title'] ?: $formTitle;

            $settings = json_decode($settingsRow['form_settings'], true) ?? [];

            $userEmailEnabled  = !empty($settings['userEmailEnabled']);
            $userEmailMessage  = $settings['userEmailMessage'] ?? '';

            $adminEmailEnabled = !empty($settings['adminEmailEnabled']);
            $adminEmailRaw = trim($settings['adminEmail'] ?? '');

            /* Split by comma and clean */
            $adminEmails = array_filter(array_map(
                fn($e) => trim($e),
                explode(',', $adminEmailRaw)
            ));

            $adminEmailMessage = $settings['adminEmailMessage'] ?? '';

            $afterSubmitAction = $settings['afterSubmitAction'] ?? 'message';
            $redirectUrl       = trim($settings['redirectUrl'] ?? '');
            $submissionPosition = $settings['submissionPosition'] ?? 'Bottom of the sheet';

            if (!empty($settings['confirmMessage'])) {
                $confirmMessage = $settings['confirmMessage'];
            }
        }

        /* STEP 1: Check if sheet already exists */
        $stmt = $conn->prepare(
            "SELECT id, data FROM sheets WHERE form_id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $formId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            /* FIRST SUBMISSION → CREATE SHEET */

            /* Fetch form title from form_builder */
            $formTitle = 'Sheet1';

            $stmtTitle = $conn->prepare(
                "SELECT form_title FROM form_builder WHERE id = ? LIMIT 1"
            );
            $stmtTitle->bind_param("i", $formId);
            $stmtTitle->execute();
            $titleResult = $stmtTitle->get_result();

            if ($titleResult && $titleResult->num_rows === 1) {
                $rowTitle  = $titleResult->fetch_assoc();
                $formTitle = $rowTitle['form_title'];
            }

            $sheetName = $formTitle;

            $usedCols = [];
            foreach ($newData['cells'] as $cellKey => $_) {
                if (preg_match('/^([A-Z]+)/', $cellKey, $m)) {
                    $usedCols[$m[1]] = true;
                }
            }

            $newData['cols'] = empty($usedCols)
                ? 0
                : (max(array_map(
                    fn($c) => ord($c) - 64,
                    array_keys($usedCols)
                )));

            $sheetJSON = json_encode($newData);

            $stmt = $conn->prepare(
                "INSERT INTO sheets (form_id, name, data, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())"
            );
            $stmt->bind_param(
                "iss",
                $formId,
                $sheetName,
                $sheetJSON
            );
            $stmt->execute();
        } else {
            /* NEXT SUBMISSIONS → APPEND ROW */
            $row = $result->fetch_assoc();
            $sheetId   = $row['id'];
            $sheetData = json_decode($row['data'], true);

            /* STEP 2: Ensure structure exists */
            $sheetData['cells']   = $sheetData['cells']   ?? [];
            $sheetData['headers'] = $sheetData['headers'] ?? [];

            /* STEP 3: Set headers ONCE */
            if (empty($sheetData['headers']) && !empty($newData['headers'])) {
                $sheetData['headers'] = $newData['headers'];
            }

            /* STEP 4: Row calculation — FIXED */
            /* FIND NEXT AVAILABLE ROW BASED ON FILLED CELLS */
            $maxFilledRow = 0;
            foreach ($sheetData['cells'] as $cellKey => $_) {
                if (preg_match('/\d+$/', $cellKey, $m)) {
                    $maxFilledRow = max($maxFilledRow, (int)$m[0]);
                }
            }

            // $nextRow = $maxFilledRow + 1;

            /* INSERT POSITION LOGIC */
            if ($submissionPosition === 'Top of the sheet') {

                /* Shift existing rows down by 1 */
                $shiftedCells = [];

                foreach ($sheetData['cells'] as $cellKey => $value) {

                    if (preg_match('/^([A-Z]+)(\d+)$/', $cellKey, $m)) {

                        $col = $m[1];
                        $row = (int)$m[2];

                        $newRow = $row + 1;
                        $shiftedCells[$col . $newRow] = $value;
                    }
                }

                $sheetData['cells'] = $shiftedCells;

                $nextRow = 1;  // Insert new submission at top

            } else {

                /* Default = Bottom */
                $nextRow = $maxFilledRow + 1;
            }

            /* STEP 5: Append cells (no overwrite) */
            foreach ($newData['cells'] as $cellKey => $value) {
                preg_match('/^([A-Z]+)/', $cellKey, $m);
                $col = $m[1];
                $sheetData['cells'][$col . $nextRow] = $value;
            }

            /* STEP 6: Update rows counter */
            $sheetData['rows'] = max($sheetData['rows'], $nextRow);

            /* STEP 6.1: REBUILD COLUMNS FROM ACTUAL CELLS (REMOVE EMPTY COLUMNS) */
            $usedCols = [];

            foreach ($sheetData['cells'] as $cellKey => $_) {
                if (preg_match('/^([A-Z]+)/', $cellKey, $m)) {
                    $usedCols[$m[1]] = true;
                }
            }

            $sheetData['cols'] = empty($usedCols)
                ? 0
                : (max(array_map(
                    fn($c) => ord($c) - 64,
                    array_keys($usedCols)
                )));

            /* STEP 5: Save */
            $sheetJSON = json_encode($sheetData);

            $stmt = $conn->prepare(
                "UPDATE sheets SET data = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->bind_param("si", $sheetJSON, $sheetId);
            $stmt->execute();
        }

        /* ===============================
        EMAIL TO USER (FIXED + STABLE)
        =============================== */
        if (
            $userEmailEnabled &&
            isset($_POST['send_copy']) &&
            !empty($_POST['user_email'])
        ) {


                $fieldRowsHtml = '';
                $headers = $newData['headers'] ?? [];
                $cells   = $newData['cells'] ?? [];
                $alphabet = range('A', 'Z');

                foreach ($headers as $i => $label) {
                    $col = $alphabet[$i + 1] ?? null;
                    if (!$col) continue;

                    $value = trim($cells[$col . '1'] ?? '');
                    if ($value === '') continue;

                    $fieldRowsHtml .= "
                        <tr>
                            <td style='padding:6px 0;font-weight:600;width:220px'>
                                " . htmlspecialchars($label) . "
                            </td>
                            <td style='padding:6px 0'>
                                " . nl2br(htmlspecialchars($value)) . "
                            </td>
                        </tr>";
                }

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['EMAIL_USERNAME'];
                    $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
                    $mail->addAddress($_POST['user_email']);

                    $mail->isHTML(true);
                    $mail->Subject = "Submission: {$formTitle}";

                    $mail->Body = "
                    <html><body style='background:#f6f6f6;font-family:Arial'>
                    <table width='100%' style='padding:30px'>
                        <tr><td>
                        <table width='640'
            style='background:#fff;
                    border-radius:6px;
                    margin:0;
                    text-align:left;'>
                            <tr>
                                <td style='background:#fec600;padding:18px;font-size:22px;font-weight:700'>
                                    Lufera Infotech
                                </td>
                            </tr>
                            <tr><td style='padding:18px'>
                                " . nl2br(htmlspecialchars($userEmailMessage)) . "
                            </td></tr>
                            <tr><td style='padding:0 18px;font-size:20px;font-weight:700'>
                                {$formTitle}
                            </td></tr>
                            <tr><td style='padding:18px'>
                                <table width='100%'>{$fieldRowsHtml}</table>
                            </td></tr>
                        </table>
                        </td></tr>
                    </table>
                    </body></html>";

                    $mail->send();

                } catch (Exception $e) {
                    error_log("User email failed: " . $mail->ErrorInfo);
                }
        }

        /* ===============================
        EMAIL TO ADMIN (SAME TEMPLATE)
        =============================== */
        if (
            $adminEmailEnabled &&
            !empty($adminEmails)
        ) {



            // Reuse already-built $fieldRowsHtml
            // (If user email was not sent, build it once here)
            if (!isset($fieldRowsHtml)) {

                $fieldRowsHtml = '';
                $headers = $newData['headers'] ?? [];
                $cells   = $newData['cells'] ?? [];
                $alphabet = range('A', 'Z');

                foreach ($headers as $i => $label) {
                    $col = $alphabet[$i + 1] ?? null;
                    if (!$col) continue;

                    $value = trim($cells[$col . '1'] ?? '');
                    if ($value === '') continue;

                    $fieldRowsHtml .= "
                        <tr>
                            <td style='padding:6px 0;font-weight:600;width:220px'>
                                " . htmlspecialchars($label) . "
                            </td>
                            <td style='padding:6px 0'>
                                " . nl2br(htmlspecialchars($value)) . "
                            </td>
                        </tr>";
                }
            }

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['EMAIL_USERNAME'];
                $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
                foreach ($adminEmails as $email) {
                    $mail->addAddress($email);
                }


                $mail->isHTML(true);
                $mail->Subject = "New Form Submission: {$formTitle}";

                $mail->Body = "
                <html><body style='background:#f6f6f6;font-family:Arial'>
                <table width='100%' style='padding:30px'>
                    <tr><td align='center'>
                    <table width='640' style='background:#fff;border-radius:6px'>
                        <tr>
                            <td style='background:#fec600;padding:18px;font-size:22px;font-weight:700'>
                                Lufera Infotech
                            </td>
                        </tr>
                        <tr><td style='padding:18px'>
                            " . nl2br(htmlspecialchars(
                                $adminEmailMessage ?: 'A new form submission has been received.'
                            )) . "
                        </td></tr>
                        <tr><td style='padding:0 18px;font-size:20px;font-weight:700'>
                            {$formTitle}
                        </td></tr>
                        <tr><td style='padding:18px'>
                            <table width='100%'>{$fieldRowsHtml}</table>
                        </td></tr>
                    </table>
                    </td></tr>
                </table>
                </body></html>";

                $mail->send();

            } catch (Exception $e) {
                error_log("Admin email failed: " . $mail->ErrorInfo);
            }
        }

        /* ===============================
        AFTER SUBMIT ACTION: REDIRECT
        =============================== */
        if ($afterSubmitAction === 'redirect' && !empty($redirectUrl)) {

            // Validate URL for safety
            if (filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
                header("Location: " . $redirectUrl);
                exit;
            }
        }

        // echo "<div style='padding:12px;background:#d1fae5;border:1px solid #10b981;border-radius:6px;margin:12px'>
        //         Sheet row saved successfully
        //     </div>";

        // return;

        // Success Page..
        echo '
            <style>
            html,body{
                margin:0;
                padding:0;
                height:100%;
                background:#f4e9a8;
            }

            /* Page wrapper */
            .success-wrap{
                min-height:100vh;
                background:#f4e9a8;
                display:flex;
                justify-content:center;
                align-items:flex-start;
                padding-top:120px;
                padding-left:20px;
                padding-right:20px;
                position:relative;
            }

            /* Floating top brand */
            .page-brand{
                position:absolute;
                top:36px;
                left:50%;
                transform:translateX(-50%);
                display:flex;
                align-items:center;
                gap:12px;
                font-size:28px;
                font-weight:800;
                color:#111827;
            }

            .brand-logo{
                width:34px;
                height:34px;
                border-radius:6px;
                object-fit:cover;
            }

            /* Card */
            .success-card{
                width:100%;
                max-width:620px;
                background:#fffaf0;
                border-radius:16px;
                padding:60px 48px 54px;
                text-align:center;
                box-shadow:0 18px 45px rgba(0,0,0,.14);
            }

            /* Icon area */
            .icon-zone{margin-bottom:26px;}

            .doc-wrap{
                position:relative;
                width:110px;
                height:135px;
                background:#faefc2;
                border-radius:16px;
                margin:0 auto;
                padding:18px;
                display:flex;
                flex-direction:column;
                gap:8px;
            }

            .doc-line{
                height:5px;
                background:#fec700;
                border-radius:5px;
            }

            /* Success tick */
            .check{
                position:absolute;
                top:-18px;
                right:-18px;
                width:54px;
                height:54px;
                background:#fec700;
                border-radius:50%;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:26px;
                font-weight:900;
                color:#1f2933;
                box-shadow:0 10px 24px rgba(254,199,0,.45);
            }

            /* Text */
            .title{
                font-size:28px;
                font-weight:800;
                margin:12px 0 6px;
            }

            .sub{
                font-size:15px;
                color:#374151;
            }

            /* Footer */
            .footer{
                margin-top:38px;
                font-size:14px;
                color:#374151;
                line-height:1.6;
            }

            .footer-brand{
                display:flex;
                justify-content:center;
                align-items:center;
                gap:8px;
                margin-top:6px;
                font-weight:700;
            }

            .footer-brand a{
                color:inherit;
                text-decoration:none;
            }

            .footer-brand a:hover{
                text-decoration:underline;
            }

            /* Responsive */
            @media(max-width:640px){
                .success-wrap{
                    padding-top:100px;
                }
                .success-card{
                    padding:44px 28px;
                }
                .title{
                    font-size:24px;
                }
                .page-brand{
                    font-size:22px;
                }
            }
            </style>

            <div class="success-wrap">

                <!-- Top brand (outside card) -->
                <div class="page-brand">
                    <img src="assets/images/Image.jfif" class="brand-logo">
                    Lufera Infotech
                </div>

                <!-- Success card -->
                <div class="success-card">

                    <div class="icon-zone">
                        <div class="doc-wrap">
                            <div class="doc-line"></div>
                            <div class="doc-line" style="width:85%"></div>
                            <div class="doc-line" style="width:70%"></div>
                            <div class="doc-line" style="width:90%"></div>
                            <div class="check">✓</div>
                        </div>
                    </div>

                    <div class="title">Success!</div>
                    <div class="sub">' . htmlspecialchars($confirmMessage) . '</div>

                    <div class="footer">
                        Put work on easy mode with work management that adapts to your needs.
                        <div class="footer-brand">
                            Powered by
                            <img src="assets/images/Image.jfif" class="brand-logo">
                            <a href="https://luferatech.com/" target="_blank">
                                Lufera Infotech
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        ';

        exit;
    }

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['toggle_form_status'])
    ) {
        $formId   = (int)$_POST['form_id'];
        $isActive = (int)$_POST['is_active'];

        $stmt = $conn->prepare(
            "UPDATE form_builder SET is_active = ? WHERE id = ?"
        );
        $stmt->bind_param("ii", $isActive, $formId);
        $stmt->execute();

        exit;
    }
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Form Builder</title>

    <style>
        :root{
            --primary:#fec700;
            --bg:#fffbea;
            --card:#ffffff;
            --muted:#6b7280;
            --border:#f5e8b8;
        }
        *{box-sizing:border-box;font-family:Inter,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
        body{margin:0;background:var(--bg);color:#111827}

        .app{
            display:flex;
            gap:18px;
            min-height:100vh;
            padding:80px 18px 18px 18px;
            flex-wrap:wrap;
            position:relative;
        }

        /* Save Button now inside form builder wrapper (NOT affecting header) */
        .save-btn{
            /* position:absolute;
            top:20px;
            right:20px;
            background:var(--primary);
            padding:8px 30px;
            font-weight:700;
            border:none;
            border-radius:6px;
            cursor:pointer; */
            /* z-index:50; */
        }

        .panel{
            background:var(--card);
            border-radius:10px;
            padding:16px;
            border:1px solid var(--border);
            box-shadow:0 4px 12px rgba(0,0,0,0.05);
        }
        .left{width:260px;flex-shrink:0}
        .center{flex:1;min-width:300px;position:relative}
        .right{
            width:340px;
            flex-shrink:0;
            min-width:280px;
            position:relative;
            /* z-index:10; */
        }

        h3{
            margin:0 0 12px;
            font-size:16px !important;
            font-weight:700;
        }

        /* Toolbox */
        .tool-list{display:flex;flex-direction:column;gap:10px}
        .tool-item{
            display:flex;
            align-items:center;
            justify-content:space-between;
            border:1px solid var(--border);
            padding:10px;
            border-radius:8px;
            background:#fff;
        }
        .tool-label{font-size:14px;font-weight:600}
        .plus-btn{
            background:var(--primary);
            border:none;
            border-radius:50%;
            width:28px;height:28px;
            cursor:pointer;
            font-size:medium;
            font-weight:700;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        /* Canvas */
        .form-title-input{
            padding:10px;
            border-radius:6px;
            border:1px solid var(--border);
            width:100%;
            margin-bottom:16px;
            font-size:16px;
        }
        .canvas{
            min-height:250px;
            border:2px dashed var(--border);
            padding:14px;
            border-radius:8px;
            background:#fff;
        }
        .field-item{
            border:1px solid var(--border);
            padding:12px;
            border-radius:8px;
            margin-bottom:10px;
            background:#fff;
            cursor:pointer;
        }
        .field-title{font-weight:600;margin-bottom:4px}
        .field-meta{font-size:12px;color:var(--muted)}

        /* Properties */
        .prop{display:flex;flex-direction:column;gap:12px}
        label{font-size:14px;font-weight:600}

        /* FIX: Ensure inputs show in right panel */
        .prop input[type=text],
        .prop textarea,
        .prop select{
            width:100%;
            background:#ffffff;
            color:#000000;
            display:block;
            padding:8px;
            border:1px solid var(--border);
            border-radius:6px;
        }

        textarea{resize:vertical}

        .toggle{display:flex;gap:10px;align-items:center}
        .toggle input[type=radio]{
            margin-right:6px;
            appearance:auto;
            accent-color:var(--primary);
        }

        .validation-group{
            border-top:1px solid var(--border);
            padding-top:12px;
        }

        .radio-group{
            display:flex;
            flex-direction:column;
            gap:6px;
            margin-top:6px;
        }
        .radio-group input[type=radio]{
            margin-right:6px;
            appearance:auto;
            accent-color:var(--primary);
        }

        #properties{min-height:250px}

        /* Drag & Drop styling */
        .field-item {
            cursor: move;
        }

        .field-item{
            display:flex;
            flex-direction:column;
            gap:8px;
        }
        .field-item label{
            font-weight:600;
        }
        .field-item input,
        .field-item textarea,
        .field-item select{
            width:100%;
        }


        .field-item.dragging {
            opacity: 0.5;
        }
        .view-mode .center{
            flex:1;
            width:100%;
        }

        .canvas .field-item{
            margin-bottom:14px;
        }

        /* FORCE VISIBILITY FOR FORM INPUTS (VIEW MODE & SUBMIT MODE) */
        .canvas input,
        .canvas textarea,
        .canvas select{
            background:#ffffff !important;
            border:1px solid var(--border) !important;
            padding:10px !important;
            border-radius:6px !important;
            font-size:14px;
            color:#111827;
            outline:none;
        }

        .canvas input:focus,
        .canvas textarea:focus,
        .canvas select:focus{
            border-color:var(--primary);
            box-shadow:0 0 0 2px rgba(254,199,0,.2);
        }

        /* REMOVE FIELD ICON */
        .field-remove {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .field-remove:hover {
            background: #fecaca;
        }


        /* ===============================
        PUBLIC FORM VIEW STYLES
        =============================== */
        .public-form-wrapper {
            max-width: 720px;
            margin: 40px auto;
            padding: 40px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }

        .public-form-logo {
            text-align: center;
            margin-bottom: 24px;
        }

        .public-form-logo img {
            max-height: 60px;
        }

        /* .public-form-title {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #111827;
        } */


        .public-form-title {
            text-align: left;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 28px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
        }


        .public-form-wrapper .field-item {
            border: none;
            padding: 0;
            margin-bottom: 18px;
        }

        .public-form-wrapper label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .public-form-wrapper input,
        .public-form-wrapper textarea,
        .public-form-wrapper select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        .public-form-wrapper button {
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            background: var(--primary);
            border: none;
            cursor: pointer;
        }

        /* ===============================
        FIX CHECKBOX & RADIO ALIGNMENT
        =============================== */

        .public-form-wrapper label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        .public-form-wrapper input[type="checkbox"],
        .public-form-wrapper input[type="radio"] {
            margin: 0;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* Builder & View mode consistency */
        .field-item label {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .field-item input[type="checkbox"],
        .field-item input[type="radio"] {
            margin: 0;
            width: 16px;
            height: 16px;
        }


        /* ===============================
        REMOVE EXTRA GAP BETWEEN RADIO LABELS
        =============================== */

        /* Properties panel radio buttons */
        .radio-group {
            gap: 2px;               /* reduce vertical spacing */
        }

        .radio-group label {
            margin: 0;
            padding: 2px 0;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 6px;               /* small space between radio & text */
        }

        /* Public form radio & checkbox spacing */
        .public-form-wrapper label {
            margin-bottom: 2px;     /* remove extra vertical gap */
            line-height: 1.3;
        }

        /* Builder canvas radio spacing */
        .field-item label {
            margin-bottom: 2px;
            line-height: 1.3;
        }
        /* ===============================
        INLINE VALIDATION STYLES
        =============================== */

        .field-error {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 2px rgba(220,38,38,.15);
        }

        .error-text {
            font-size: 12px;
            color: #dc2626;
            margin-top: 4px;
            line-height: 1.3;
        }
    </style>

    <style>
       .form-topbar{
            background:linear-gradient(180deg,#fff9e6 0%, #fff3c4 100%);
            border-bottom:1px solid #e5e7eb;
            box-shadow:0 2px 6px rgba(0,0,0,.04);

            position:sticky;
            top:0;
            z-index:100;

            /* 🔥 IMPORTANT */
            display:flex;
            align-items:center;
            padding:10px 24px;
        }

        .tabs{
            position:absolute;
            left:50%;
            transform:translateX(-50%);

            display:flex;
            gap:42px;
        }

        .tab{
            font-size:15px;
            font-weight:600;
            padding:10px 0;
            cursor:pointer;
            color:#6b7280;
            position:relative;
        }

        .tab.active{
            color:#111827;
        }

        .tab.active::after{
            content:"";
            position:absolute;
            left:0;
            bottom:-1px;
            width:100%;
            height:3px;
            background:#111827;
            border-radius:6px;
        }

        .tab-page{display:none}
        .tab-page.active{display:block}

        /* FORCE SETTINGS STYLES */

        #settingsTab h2{
            font-size:18px !important;
            font-weight:700 !important;
            margin-bottom:6px !important;
            color:#111827 !important;
            line-height:1.3 !important;
        }

        #settingsTab p{
            font-size:14px !important;
            color:#374151 !important;
        }

        /* =========================
        FORCE SHOW RADIO & CHECKBOX IN SETTINGS TAB
        ========================= */

        #settingsTab input[type="checkbox"],
        #settingsTab input[type="radio"]{
            appearance:auto !important;
            -webkit-appearance:auto !important;
            -moz-appearance:auto !important;
            width:16px;
            height:16px;
            cursor:pointer;
            accent-color:#111827; /* clean modern look */
        }
        
        /* TOPBAR SAVE BUTTON */

        .topbar-save-btn{
            background:var(--primary);
            padding:8px 28px;
            font-weight:700;
            border:none;
            border-radius:6px;
            cursor:pointer;
        }
    </style>

    <style>
        .toast{
            position:fixed;
            top:20px;
            right:20px;
            background:#111827;
            color:#fff;
            padding:12px 20px;
            border-radius:8px;
            font-weight:600;
            box-shadow:0 10px 25px rgba(0,0,0,.2);
            opacity:0;
            transform:translateY(-10px);
            transition:.3s ease;
            z-index:9999;
        }
        .toast.show{
            opacity:1;
            transform:translateY(0);
        }

        /* ===============================
        TOPBAR ENHANCEMENTS
        =============================== */

        /* LEFT GROUP */
        .topbar-left{
            display:flex;
            align-items:center;
            gap:18px;
        }

        /* BACK BUTTON */
        .back-btn{
            font-size:18px;                 /* slightly bigger */
            font-weight:800;
            cursor:pointer;
            user-select:none;
            color:#111827;
            display:flex;
            align-items:center;
            gap:8px;
            line-height:1;
            transition:color .2s ease, transform .15s ease;
        }

        .back-btn:hover{
            color:#fec700;
            transform:translateX(-2px);     /* subtle professional motion */
        }

        .back-btn.icon-only{
            padding:4px;
        }

        .back-btn.icon-only svg{
            display:block;
        }


        /* RIGHT GROUP */
        .topbar-right{
            margin-left:auto;
            display:flex;
            align-items:center;
            gap:18px;
            white-space:nowrap;
        }

        .switch:hover + .status-text,
        .status-text:hover{
            color:#fec700;
        }




        /* TEXT LINKS */
        .topbar-link{
            background:none;
            border:none;
            font-size:14px;
            font-weight:600;
            color:#111827;               /* 🔥 black */
            cursor:pointer;
            padding:0;
            transition:color .2s ease;
        }

        .topbar-link:hover{
            color:#fec700;               /* 🔥 theme hover */
        }


        /* ACTIVE TOGGLE */
        .switch{
            position:relative;
            width:44px;
            height:22px;
        }

        .switch input{
            display:none;
        }

        .slider{
            position:absolute;
            inset:0;
            background:#d1d5db;
            border-radius:999px;
            transition:.3s;
        }

        .slider::before{
            content:"";
            position:absolute;
            width:18px;
            height:18px;
            top:2px;
            left:2px;
            background:#fff;
            border-radius:50%;
            transition:.3s;
        }

        /* ✅ ACTIVE (ON) */
        .switch input:checked + .slider{
            background:#fec700;
        }

        /* KNOB MOVE */
        .switch input:checked + .slider::before{
            transform:translateX(22px);
        }

        /* OPTIONAL HOVER POLISH */
        .switch:hover .slider{
            box-shadow:0 0 0 3px rgba(254,199,0,.25);
        }


        .status-text{
            font-size:14px;
            font-weight:600;
            color:#111827;               /* 🔥 black */
            transition:color .2s ease;
        }

        /* ===============================
        SHARE FORM MODAL — FINAL
        ================================ */

        .share-overlay{
        position:fixed;
        inset:0;
        background:rgba(0,0,0,.4);
        display:flex;
        align-items:center;
        justify-content:center;
        z-index:9999;
        }

        .share-modal{
        width:560px;
        background:#fff;
        border-radius:12px;
        box-shadow:0 25px 60px rgba(0,0,0,.25);
        overflow:hidden;
        }

        .share-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        padding:16px 20px;
        border-bottom:1px solid #e5e7eb;
        }

        .close-btn{
        font-size:22px;
        background:none;
        border:none;
        cursor:pointer;
        }

        /* Tabs */
        .share-tabs{
        display:flex;
        align-items:center;
        border-bottom:1px solid #e5e7eb;
        }

        .share-tab{
        flex:1;
        padding:14px 0;
        background:none;
        border:none;
        font-weight:600;
        cursor:pointer;
        text-align:center;
        color:#111827;
        transition:color .2s ease;
        }

        .share-tab:hover{
        color:#fec700;
        }

        .share-tab.active{
        color:#fec700;
        border-bottom:3px solid #fec700;
        }

        /* Content */
        .share-tab-content{
        display:none;
        padding:22px;
        }

        .share-tab-content.active{
        display:block;
        }

        .share-modal label{
        font-size:14px;
        font-weight:600;
        margin-top:14px;
        display:block;
        }

        /* Text inputs & textarea only (exclude checkbox) */
        .share-modal input:not([type="checkbox"]),
        .share-modal textarea{
        width:100%;
        margin-top:6px;
        padding:10px;
        border:1px solid #d1d5db;
        border-radius:6px;
        font-size:14px;
        }


        .note{
        font-size:12px;
        color:#6b7280;
        margin-top:6px;
        }

        /* Checkbox FIX */
        .checkbox-row{
        display:flex;
        align-items:center;      /* 🔥 vertical alignment */
        gap:8px;
        margin-top:14px;
        font-weight:500;
        cursor:pointer;          /* click text also toggles checkbox */
        }


        /* Share modal checkbox FINAL FIX */
        .checkbox-row input[type="checkbox"]{
        appearance:auto !important;
        -webkit-appearance:auto !important;
        -moz-appearance:auto !important;

        width:16px !important;
        height:16px !important;
        margin:0 !important;
        padding:0 !important;
        border:none !important;

        cursor:pointer;
        accent-color:#fec700;
        }


        .checkbox-row input{
        width:16px;
        height:16px;
        accent-color:#fec700;
        }

        /* Footer */
        .share-footer{
        display:flex;
        justify-content:flex-end;
        gap:14px;
        margin-top:24px;
        }

        .share-footer button{
        background:none;
        border:none;
        font-weight:600;
        cursor:pointer;
        color:#111827;
        }

        .share-footer button:hover{
        color:#fec700;
        }

        .share-footer .primary{
        background:#fec700;
        color:#111827;
        border:none;
        padding:10px 20px;
        border-radius:6px;
        font-weight:700;
        }

        .share-footer .primary:hover{
        background:#e6b800;
        }

        .embed-size{
        display:flex;
        gap:16px;
        margin-top:14px;
        }

        /* FIX: Ensure SweetAlert appears above Share Modal */
        .swal2-container{
            z-index: 20000 !important;
        }


    </style>
</head>

<body>

    <?php
        /* SAVE FORM SUBMISSION (USER FILLED DATA) */
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['submit_form']) &&
            isset($_POST['form_id'])
        ) {
            $formId = (int)$_POST['form_id'];
            $data   = json_encode($_POST['fields'] ?? []);

            $stmt = $conn->prepare(
                "INSERT INTO form_submissions (form_id, submission_json) VALUES (?, ?)"
            );
            $stmt->bind_param("is", $formId, $data);
            $stmt->execute();

            echo "<div style='padding:10px;background:#d1fae5;border:1px solid #10b981;margin:10px;border-radius:6px'>
                    Form submitted successfully
                </div>";
        }

        // /* SAVE OR UPDATE FORM STRUCTURE */
        // elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

        //     $formId = isset($_POST['form_id']) && is_numeric($_POST['form_id'])
        //         ? (int)$_POST['form_id']
        //         : null;

        //     $title = $_POST['formTitle'] ?? '';
        //     $json  = $_POST['formJSON'] ?? '';

        //     if ($formId) {
        //         $stmt = $conn->prepare(
        //             "UPDATE form_builder SET form_title = ?, form_json = ? WHERE id = ?"
        //         );
        //         $stmt->bind_param("ssi", $title, $json, $formId);
        //         $stmt->execute();
        //         $message = "Form Updated Successfully";
        //     } else {
        //         $stmt = $conn->prepare(
        //             "INSERT INTO form_builder (form_title, form_json) VALUES (?, ?)"
        //         );
        //         $stmt->bind_param("ss", $title, $json);
        //         $stmt->execute();
        //         $message = "Form Created Successfully";
        //     }

        //     echo "<div style='padding:10px;background:#d1fae5;border:1px solid #10b981;margin:10px;border-radius:6px'>
        //             {$message}
        //           </div>";
        // }

        /* SAVE OR UPDATE FORM STRUCTURE */
        // elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

        //     $formId = isset($_POST['form_id']) && is_numeric($_POST['form_id'])
        //         ? (int)$_POST['form_id']
        //         : null;

        //     $title = $_POST['formTitle'] ?? '';
        //     $json  = $_POST['formJSON'] ?? '';
        //     $settings = $_POST['formSettings'] ?? '';

        //     if ($formId) {
        //         // UPDATE existing form
        //         $stmt = $conn->prepare(
        //             "UPDATE form_builder SET form_title = ?, form_json = ?, form_settings = ? WHERE id = ?"
        //         );
        //         $stmt->bind_param("sssi", $title, $json, $settings, $formId);
        //         $stmt->execute();
        //         $redirectId = $formId;
        //     } else {
        //         // CREATE new form
        //         $stmt = $conn->prepare(
        //             "INSERT INTO form_builder (form_title, form_json, form_settings) VALUES (?, ?, ?)"
        //         );
        //         $stmt->bind_param("sss", $title, $json, $settings);
        //         $stmt->execute();
        //         $redirectId = $conn->insert_id;

        //         /* ✅ CREATE EMPTY SHEET IMMEDIATELY */

        //         // $emptySheetData = json_encode([
        //         //     "rows" => 10,        // ✅ fixed initial rows
        //         //     "cols" => 0,
        //         //     "headers" => [],
        //         //     "columnTypes" => [],
        //         //     "cells" => []
        //         // ]);

        //         /* BUILD HEADERS FROM FORM STRUCTURE */
        //         $decodedFields = json_decode($json, true) ?? [];

        //         $headers = [];
        //         $cols = 0;

        //         foreach ($decodedFields as $field) {
        //             $headers[] = $field['label'] ?? '';
        //             $cols++;
        //         }

        //         /* CREATE EMPTY SHEET WITH HEADERS (KEEP COLUMN A RESERVED) */
        //         $emptySheetData = json_encode([
        //             "rows" => 10,
        //             "cols" => $cols + 1,
        //             "headers" => $headers,
        //             "columnTypes" => [],
        //             "cells" => []
        //         ]);

        //         $stmtSheet = $conn->prepare(
        //             "INSERT INTO sheets (form_id, name, data, created_at, updated_at)
        //             VALUES (?, ?, ?, NOW(), NOW())"
        //         );
        //         $stmtSheet->bind_param(
        //             "iss",
        //             $redirectId,     // form_id
        //             $title,          // sheet name = form title
        //             $emptySheetData
        //         );
        //         $stmtSheet->execute();
        //     }

        //     // ✅ REDIRECT TO INDIVIDUAL FORM PAGE
        //     // header("Location: form_builder.php?id={$redirectId}&mode=view");
        //     // exit;

        //     echo "<script>
        //         window.location.href = 'form_builder.php?id={$redirectId}&mode=view';
        //     </script>";
        //     exit;
        // }

        /* SAVE OR UPDATE FORM STRUCTURE */
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
            $formId = isset($_POST['form_id']) && is_numeric($_POST['form_id'])
                ? (int)$_POST['form_id']
                : null;
 
            $title = $_POST['formTitle'] ?? '';
            $json  = $_POST['formJSON'] ?? '';
            $settings = $_POST['formSettings'] ?? '';
 
            // If no explicit form_id but we have a sheet_id, try to find an existing form for that sheet
            if (!$formId && $sheet_id_from_url > 0) {
                $stmtLookup = $conn->prepare("SELECT id FROM form_builder WHERE sheet_id = ? LIMIT 1");
                $stmtLookup->bind_param("i", $sheet_id_from_url);
                $stmtLookup->execute();
                $resLookup = $stmtLookup->get_result();
                if ($resLookup && $resLookup->num_rows === 1) {
                    $rowLookup = $resLookup->fetch_assoc();
                    $formId = (int)$rowLookup['id'];
                }
            }
 
            /* Build sheet structure (headers, columnTypes, etc.) from form JSON
               so we can either update an existing sheet row or insert a new one. */
            $emptySheetData = null;
            if (!empty($json)) {
                $decodedFields = json_decode($json, true) ?? [];
 
                $headers = [];
                $cols = 0;
                $columnTypes = [];
 
                $colIndex = 2; // Start from column B = 2
 
                foreach ($decodedFields as $field) {
                    $headers[] = $field['label'] ?? 'Field ' . $colIndex;
 
                    // Map field type → sheet column type
                    $sheetType = match (strtolower($field['type'] ?? '')) {
                        'text', 'paragraph'         => 'text',
                        'email'                     => 'email',
                        'phone'                     => 'text',     // or 'phone'
                        'number'                    => 'number',
                        'select', 'radio'           => 'text',
                        'checkbox'                  => 'text',     // could be 'multi'
                        'datetime'                  => 'datetime',
                        'file'                      => 'file',
                        'signature'                 => 'text',
                        default                     => 'text',
                    };
 
                    $columnTypes[$colIndex] = ['type' => $sheetType];
 
                    $colIndex++;
                    $cols++;
                }
 
                $emptySheetData = json_encode([
                    "rows"         => 10,
                    "cols"         => $cols + 1,   // +1 because A is reserved
                    "headers"      => $headers,
                    "columnTypes"  => $columnTypes,
                    "cells"        => []
                ]);
            }
            if ($formId) {
                // UPDATE existing form, ensure sheet_id is filled once known
                $stmt = $conn->prepare(
                    "UPDATE form_builder
                     SET form_title = ?,
                         form_json  = ?,
                         form_settings = ?,
                         sheet_id   = COALESCE(sheet_id, ?)
                     WHERE id = ?"
                );
                $sheetIdForForm = $sheet_id_from_url > 0 ? $sheet_id_from_url : null;
                $stmt->bind_param("sssii", $title, $json, $settings, $sheetIdForForm, $formId);
                $stmt->execute();
                $redirectId = $formId;
 
                /* Sync the linked sheet, if any.
                   If sheet_id is known (exported from an existing sheet), update that row.
                   Otherwise, try to update by form_id; if no row exists yet, insert one. */
                if ($emptySheetData !== null) {
                    if ($sheet_id_from_url > 0) {
                        $stmtSheet = $conn->prepare(
                            "UPDATE sheets
                             SET name = ?, data = ?, form_id = ?, updated_at = NOW()
                             WHERE id = ?"
                        );
                        $stmtSheet->bind_param(
                            "ssii",
                            $title,
                            $emptySheetData,
                            $redirectId,
                            $sheet_id_from_url
                        );
                        $stmtSheet->execute();
                    } else {
                        // Try update by form_id first
                        $stmtSheet = $conn->prepare(
                            "UPDATE sheets
                             SET name = ?, data = ?, updated_at = NOW()
                             WHERE form_id = ?"
                        );
                        $stmtSheet->bind_param(
                            "ssi",
                            $title,
                            $emptySheetData,
                            $redirectId
                        );
                        $stmtSheet->execute();
 
                        // If no existing sheet row was updated, insert a new one
                        if ($stmtSheet->affected_rows === 0) {
                            $stmtSheetInsert = $conn->prepare(
                                "INSERT INTO sheets (form_id, name, data, created_at, updated_at)
                                 VALUES (?, ?, ?, NOW(), NOW())"
                            );
                            $stmtSheetInsert->bind_param(
                                "iss",
                                $redirectId,
                                $title,
                                $emptySheetData
                            );
                            $stmtSheetInsert->execute();
                        }
                    }
                }
            } else {
                // CREATE new form, link to sheet_id (if provided)
                $stmt = $conn->prepare(
                    "INSERT INTO form_builder (form_title, form_json, form_settings, sheet_id) VALUES (?, ?, ?, ?)"
                );
                $sheetIdForForm = $sheet_id_from_url > 0 ? $sheet_id_from_url : null;
                $stmt->bind_param("sssi", $title, $json, $settings, $sheetIdForForm);
                $stmt->execute();
                $redirectId = $conn->insert_id;
 
                /* CREATE / UPDATE SHEET ROW
                   - If coming from an existing sheet (export-to-form), update that sheet.
                   - Otherwise, create a new sheet row linked by form_id. */
                if ($emptySheetData !== null) {
                    if ($sheet_id_from_url > 0) {
                        // Update the existing sheet record instead of inserting a new one
                        $stmtSheet = $conn->prepare(
                            "UPDATE sheets
                             SET name = ?, data = ?, form_id = ?, updated_at = NOW()
                             WHERE id = ?"
                        );
                        $stmtSheet->bind_param(
                            "ssii",
                            $title,
                            $emptySheetData,
                            $redirectId,
                            $sheet_id_from_url
                        );
                        $stmtSheet->execute();
                    } else {
                        // No existing sheet row → insert a new one
                        $stmtSheet = $conn->prepare(
                            "INSERT INTO sheets (form_id, name, data, created_at, updated_at)
                             VALUES (?, ?, ?, NOW(), NOW())"
                        );
                        $stmtSheet->bind_param(
                            "iss",
                            $redirectId,     // form_id
                            $title,          // sheet name = form title
                            $emptySheetData
                        );
                        $stmtSheet->execute();
                    }
                }
            }

            // ✅ REDIRECT TO INDIVIDUAL FORM PAGE
            $redirectUrl = "form_builder.php?id={$redirectId}&mode=view";
            
            if ($sheet_id_from_url > 0) {
                $redirectUrl .= "&sheet_id=" . (int)$sheet_id_from_url;
            }
 
            echo "<script>
                window.location.href = '{$redirectUrl}';
            </script>";
            exit;
        }

        /* LOAD FORM FOR EDITING */
        $editForm = null;

        $formId = null;

        $isViewMode = isset($_GET['mode']) && $_GET['mode'] === 'view';

        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $formId = (int)$_GET['id'];

            $stmt = $conn->prepare("SELECT form_title, form_json, form_settings, is_active, sheet_id FROM form_builder WHERE id = ?");
            $stmt->bind_param("i", $formId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                // $editForm = $result->fetch_assoc();

                $row = $result->fetch_assoc();
                $editForm = [
                    'form_title' => $row['form_title'],
                    'form_json'  => $row['form_json'],
                    'form_settings'  => $row['form_settings'],
                    'is_active'  => $row['is_active'],
                ];
                // If this form is already linked to a sheet, keep that relationship
                if (!empty($row['sheet_id']) && !$sheet_id_from_url) {
                    $sheet_id_from_url = (int)$row['sheet_id'];
                }
            }
        }
        // No explicit form id, but we have a sheet_id → try loading by sheet_id
        elseif ($sheet_id_from_url > 0) {
            $stmt = $conn->prepare("SELECT id, form_title, form_json, form_settings, is_active FROM form_builder WHERE sheet_id = ? LIMIT 1");
            $stmt->bind_param("i", $sheet_id_from_url);
            $stmt->execute();
            $result = $stmt->get_result();
 
            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $formId = (int)$row['id'];
                $editForm = [
                    'form_title' => $row['form_title'],
                    'form_json'  => $row['form_json'],
                    'form_settings'  => $row['form_settings'],
                    'is_active'  => $row['is_active'],
                ];
            }
        }

        /* ===============================
        BLOCK VIEW MODE IF FORM IS INACTIVE
        =============================== */
        if (
            $isViewMode &&
            isset($editForm['is_active']) &&
            (int)$editForm['is_active'] === 0
        ) {
            echo '
                <div style="
                    max-width:600px;
                    margin:120px auto;
                    padding:30px;
                    background:#ffffff;
                    border-radius:10px;
                    text-align:center;
                    box-shadow:0 10px 25px rgba(0,0,0,.1);
                ">
                    <h2 style="margin-bottom:10px;">Form Inactive</h2>
                    <p>The form you are attempting to access is no longer active.</p>
                </div>
            ';
            exit;
        }

        $preTitle = '';
        $preFieldsJSON = '';

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            // Only use pre_ parameters when NOT editing an existing form
            $preTitle = isset($_GET['pre_title']) ? trim(htmlspecialchars($_GET['pre_title'])) : '';
            $preFieldsJSON = isset($_GET['pre_fields']) ? $_GET['pre_fields'] : '';
        }
    ?>

    <?php if (!$isViewMode): ?>
        <div class="form-topbar">

            <!-- LEFT SIDE -->
            <div class="topbar-left">

                <!-- 1️⃣ BACK BUTTON -->
                <span class="back-btn icon-only" onclick="history.back()" title="Go back">
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M15 18L9 12L15 6"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg> Back
                </span>

                <!-- EXISTING TABS (UNCHANGED LOGIC) -->
                <div class="tabs">
                    <div class="tab active" data-tab="formTab">Form</div>
                    <div class="tab" data-tab="settingsTab">Settings</div>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="topbar-right">

                <!-- 2️⃣ ACTIVE / INACTIVE TOGGLE (UI ONLY) -->
                <label class="switch">
                    <input
                        type="checkbox"
                        id="formActiveToggle"
                        <?= (!isset($editForm['is_active']) || $editForm['is_active'] == 1) ? 'checked' : '' ?>
                    >
                    <span class="slider"></span>
                </label>

                <span class="status-text" id="formStatusText">
                    <?= (!isset($editForm['is_active']) || $editForm['is_active'] == 1) ? 'Active' : 'Inactive' ?>
                </span>

                <!-- 3️⃣ OPEN FORM -->
                <a
                    href="?id=<?= isset($_GET['id']) ? (int)$_GET['id'] : 0 ?>&mode=view"
                    class="topbar-link"
                    title="View form"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Open Form
                </a>

                <!-- 4️⃣ SHARE FORM -->
                <button
                    type="button"
                    class="topbar-link"
                    onclick="openShareModal()"
                >
                    Share Form
                </button>

                <!-- 5️⃣ EXISTING SAVE BUTTON (UNCHANGED) -->
                <button class="topbar-save-btn" id="saveForm">Save</button>

            </div>

        </div>
    <?php endif; ?>

    <div class="tab-page active" id="formTab">
        <div class="app" id="formBuilderWrapper">

            <!-- Save Button at top-right of all 3 sections (NOT inside center panel) -->
            <!-- <button class="save-btn" id="saveForm">Save</button> -->

            <!-- LEFT SECTION -->
            <div class="panel left">
                <h3>Field toolbox</h3>
                <div class="tool-list">
                    <div class="tool-item" data-type="text"><span class="tool-label">Text (single line)</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="paragraph"><span class="tool-label">Paragraph</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="number"><span class="tool-label">Number</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="email"><span class="tool-label">Email</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="phone"><span class="tool-label">Phone</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="select"><span class="tool-label">Dropdown</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="radio"><span class="tool-label">Radio</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="checkbox"><span class="tool-label">Checkbox</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="file"><span class="tool-label">File Upload</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="datetime"><span class="tool-label">Date / Time</span><button class="plus-btn">+</button></div>
                    <div class="tool-item" data-type="signature"><span class="tool-label">Digital Signature</span><button class="plus-btn">+</button></div>
                </div>
            </div>

            <!-- CENTER SECTION -->
            <!-- <div class="panel center"> -->
            <div class="panel center <?= $isViewMode ? 'view-mode' : '' ?>">

                <!-- <input id="formTitle" class="form-title-input" placeholder="Form title" /> -->
                
                <?php if ($isViewMode): ?>
                    <div class="public-form-wrapper">

                        <div class="public-form-logo">
                            <img src="assets/images/logo_lufera.png" alt="Company Logo">
                        </div>

                        <div class="public-form-title">
                            <?= htmlspecialchars($editForm['form_title'] ?? '') ?>
                        </div>
                <?php else: ?>

                    <input
                        id="formTitle"
                        class="form-title-input"
                        placeholder="Form title"
                        value="<?= $editForm ? htmlspecialchars($editForm['form_title']) : $preTitle ?>"
                    />
                <?php endif; ?>


                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="form_id" value="<?= isset($formId) ? $formId : '' ?>">
                    <input type="hidden" name="submit_form" value="1">
                    <div id="canvas" class="canvas"></div>
                </form>

                <?php if ($isViewMode): ?>
                    </div> <!-- public-form-wrapper -->
                <?php endif; ?>
            </div>

            <!-- RIGHT SECTION -->
            <div class="panel right">
                <h3>Properties</h3>
                <div id="properties" class="prop">
                    <div class="muted">Select a field to edit</div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-page" id="settingsTab" 
            style="width:100%; padding:50px 70px; background:#f5f6fa">

                <h2>Form Submission</h2>
                <p>What should happen after form is submitted</p>

                <select id="afterSubmitAction" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px">
                    <option value="message">Display this confirmation message</option>
                    <option value="redirect">Send the user to link (dropdown)</option>
                    <option value="reload">Reload the same form for another entry</option>
                </select>

                <textarea id="confirmBox"
                    style="width:100%;margin-top:12px;padding:12px;border:1px solid #d1d5db;border-radius:6px"
                    rows="4">Success! We've captured your response.</textarea>

                <input id="redirectBox"
                    style="width:100%;margin-top:12px;padding:10px;border:1px solid #d1d5db;border-radius:6px;display:none"
                    value="http://"/>

                <h2 style="margin-top:36px">New submissions should appear on the</h2>
                <label><input type="radio" name="submissionPos" checked> Bottom of the sheet</label><br>
                <label><input type="radio" name="submissionPos"> Top of the sheet</label>

                <h2 style="margin-top:36px">Send Email of Submissions to User</h2>

        <label>
            <input type="checkbox" id="userEmailToggle">
            Allow submitter to email a copy of form submission
        </label>

        <div id="userEmailBox" style="display:none">

            <p>Email Message</p>

            <textarea
                style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:6px"
                rows="4">Thank you for submitting your entry.</textarea>

        </div>


                <h2 style="margin-top:36px">Send Email of Submissions to Admin</h2>

        <label>
            <input type="checkbox" id="adminEmailToggle">
            Allow admin to email a copy of form submission
        </label>

        <div id="adminEmailBox" style="display:none">

            <p>Admin Email</p>

            <input
                style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px"
                placeholder="admin1@email.com, admin2@email.com and etc.."/>

            <p>Email Message</p>

            <textarea
                style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:6px"
                rows="4"></textarea>

        </div>
    </div>

    <!-- Hidden POST Form -->
    <form id="saveFormPOST" method="POST" target="_blank" style="display:none">
        <input type="hidden" name="form_id" id="postFormId">
        <input type="hidden" name="formTitle" id="postTitle">
        <input type="hidden" name="formJSON" id="postJSON">
        <input type="hidden" name="formSettings" id="postSettings">
    </form>

    <form id="sheetForm" method="POST" enctype="multipart/form-data" style="display:none">
        <input type="hidden" name="sheet_name" value="Sheet1">
        <input type="hidden" name="form_id" value="<?= (int)$formId ?>">
        <input type="hidden" name="sheet_id" value="<?= (int)$sheet_id_from_url ?>">
        <input type="hidden" name="sheet_data" id="sheetJSON">
    </form>

    <script>
        function clearInlineErrors() {
            document.querySelectorAll(".error-text").forEach(e => e.remove());
            document.querySelectorAll(".field-error").forEach(e => {
                e.classList.remove("field-error");
            });
        }

        function showInlineError(el, message) {
            el.classList.add("field-error");

            const err = document.createElement("div");
            err.className = "error-text";
            err.textContent = message;

            // place error after field or group
            if (el.parentElement) {
                el.parentElement.appendChild(err);
            }
        }

        function validateFormFieldsInline() {

            clearInlineErrors();

            for (let i = 0; i < fields.length; i++) {
                const f = fields[i];

                const input      = document.querySelector(`[name="fields[${i}]"]`);
                const radios     = document.querySelectorAll(`[name="fields[${i}]"]`);
                const checkboxes = document.querySelectorAll(`[name="fields[${i}][]"]`);

                let value = "";
                let hasValue = false;
                let errorTarget = input;

                /* CHECK VALUE */
                if (checkboxes.length > 0) {
                    const checked = [...checkboxes].filter(cb => cb.checked);
                    hasValue = checked.length > 0;
                    value = checked.map(cb => cb.value).join(", ");
                    errorTarget = checkboxes[0];
                }
                else if (radios.length > 0 && radios[0].type === "radio") {
                    const checked = [...radios].find(r => r.checked);
                    hasValue = !!checked;
                    value = checked ? checked.value : "";
                    errorTarget = radios[0];
                }
                else if (input) {
                    value = input.value.trim();
                    hasValue = value !== "";
                }

                /* REQUIRED */
                if (f.required && !hasValue) {
                    showInlineError(errorTarget, `"${f.label}" is required`);
                    return false;
                }

                /* EMAIL */
                if (f.validation === "email" && hasValue) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        showInlineError(errorTarget, "Enter a valid email address");
                        return false;
                    }
                }

                /* NUMBER */
                if (f.validation === "range" && hasValue) {
                    if (isNaN(value)) {
                        showInlineError(errorTarget, "Enter a valid number");
                        return false;
                    }
                }

                /* FILE SIZE */
                if (f.validation === "filesize" && input?.files?.length) {
                    const maxSize = 2 * 1024 * 1024; // 2MB
                    if (input.files[0].size > maxSize) {
                        showInlineError(input, "File size must be under 2MB");
                        return false;
                    }
                }
            }

            /* ===============================
            USER EMAIL VALIDATION
            =============================== */
            const sendCopy = document.getElementById("sendCopyToggle");
            const emailInput = document.getElementById("userEmailInput");

            if (sendCopy && sendCopy.checked) {

                if (!emailInput.value.trim()) {
                    showInlineError(emailInput, "Email address is required");
                    return false;
                }

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value.trim())) {
                    showInlineError(emailInput, "Enter a valid email address");
                    return false;
                }
            }

            return true;
        }
    </script>

    <script>
        // function buildSheetPayload() {

        //     const cells = {};
        //     const headers = [];
        //     const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");

        //     let colIndex = 1; // Start from B (skip A)
        //     const row = 1;

        //     fields.forEach((f, i) => {

        //         const col = alphabet[colIndex];

        //         let value = "";

        //         const input = document.querySelector(`[name="fields[${i}]"]`);
        //         const checkboxes = document.querySelectorAll(`[name="fields[${i}][]"]`);
        //         const radio = document.querySelector(`[name="fields[${i}]"]:checked`);

        //         if (checkboxes.length > 0) {
        //             value = [...checkboxes]
        //                 .filter(cb => cb.checked)
        //                 .map(cb => cb.value)
        //                 .join(", ");
        //         }
        //         else if (radio) {
        //             value = radio.value;
        //         }
        //         else if (input) {
        //             value = input.value || "";
        //         }

        //         cells[`${col}${row}`] = value;

        //         // ✅ HEADER = FIELD LABEL
        //         headers.push(f.label || col);

        //         colIndex++;
        //     });

        //     return {
        //         rows: 10,
        //         cols: colIndex,
        //         headers: headers,
        //         columnTypes: [],
        //         cells: cells
        //     };
        // }

        function buildSheetPayload() {

            const cells = {};
            const headers = [];
            const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");

            let colIndex = 1; // Start from B (skip A)
            const row = 1;

            // Clear previous uploaded file inputs
            const sheetForm = document.getElementById("sheetForm");
            sheetForm.querySelectorAll('input[type="file"]').forEach(el => el.remove());

            fields.forEach((f, i) => {

                const col = alphabet[colIndex];
                let value = "";

                const input = document.querySelector(`[name="fields[${i}]"]`);
                const checkboxes = document.querySelectorAll(`[name="fields[${i}][]"]`);
                const radio = document.querySelector(`[name="fields[${i}]"]:checked`);

                if (checkboxes.length > 0) {

                    value = [...checkboxes]
                        .filter(cb => cb.checked)
                        .map(cb => cb.value)
                        .join(", ");

                } else if (radio) {

                    value = radio.value;

                } else if (input) {

                    // 🔥 FILE FIELD HANDLING
                    if (f.type === "file" && input.files.length > 0) {

                        const file = input.files[0];

                        const dt = new DataTransfer();
                        dt.items.add(file);

                        const fileInput = document.createElement("input");
                        fileInput.type = "file";
                        fileInput.name = `uploaded_files[${i}]`;
                        fileInput.files = dt.files;

                        sheetForm.appendChild(fileInput);

                        // Only store original filename in JSON
                        value = file.name;

                    } else {

                        value = input.value || "";
                    }
                }

                cells[`${col}${row}`] = value;
                headers.push(f.label || col);
                colIndex++;
            });

            return {
                rows: 10,
                cols: colIndex,
                headers: headers,
                columnTypes: [],
                cells: cells
            };
        }
    </script>

    <script>
        function collectSettings(){

            return {
                afterSubmitAction: document.getElementById("afterSubmitAction").value,
                confirmMessage: document.getElementById("confirmBox").value,
                redirectUrl: document.getElementById("redirectBox").value,

                submissionPosition: document.querySelector('input[name="submissionPos"]:checked').nextSibling.textContent.trim(),

                userEmailEnabled: document.getElementById("userEmailToggle").checked,
                userEmailMessage: document.querySelector("#userEmailBox textarea")?.value || "",

                adminEmailEnabled: document.getElementById("adminEmailToggle").checked,
                adminEmail: document.querySelector("#adminEmailBox input")?.value || "",
                adminEmailMessage: document.querySelector("#adminEmailBox textarea")?.value || ""
            };
        }
    </script>

    <script>
        const isViewMode = <?= isset($isViewMode) && $isViewMode ? 'true' : 'false' ?>;

        let fields = [];
        let selectedIndex = -1;
        let elDragIndex = null;

        // const editFormId = <?= isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 'null' ?>;
        const editFormId = <?= isset($formId) && is_numeric($formId) ? (int)$formId : 'null' ?>;

        if (isViewMode) {
            const left = document.querySelector(".panel.left");
            const right = document.querySelector(".panel.right");
            const saveBtn = document.getElementById("saveForm");

            if (left) left.style.display = "none";
            if (right) right.style.display = "none";
            if (saveBtn) saveBtn.style.display = "none";
        }

        if (isViewMode) {
            document.querySelector('.left')?.remove();
            document.querySelector('.right')?.remove();
        }

        const canvas = document.getElementById("canvas");

        function addField(type){
            const f = {
                id: Date.now(),
                type,
                label: type,
                placeholder: "",
                required: false,
                options: ["Option 1", "Option 2"],
                validation: ""
            };
            fields.push(f);
            selectedIndex = fields.length - 1;
            render();
        }

        document.querySelectorAll(".tool-item .plus-btn").forEach(btn=>{
            btn.onclick = ()=> addField(btn.parentElement.dataset.type);
        });

        function render(){
            canvas.innerHTML = "";

            fields.forEach((f, i) => {
                const el = document.createElement("div");
                el.className = "field-item";
                el.style.position = "relative";

                el.style.cursor = isViewMode ? "default" : "move";

                let inputHTML = "";

                switch(f.type){
                    case "text":
                    case "email":
                    case "phone":
                    case "number":
                    let inputType = "text";
                    
                    if (f.type === "number") inputType = "number";
                    else if (f.type === "phone") inputType = "tel";
                    else if (f.type === "email") inputType = "email";
                        inputHTML = `
                            <input type="${f.type === 'phone' ? 'tel' : f.type}" name="fields[${i}]" placeholder="${f.placeholder}" />`;
                    break;
                    case "paragraph":
                        inputHTML = `<textarea name="fields[${i}]" placeholder="${f.placeholder}"></textarea>`;
                    break;
                    case "select":
                        inputHTML = `<select name="fields[${i}]">
                            ${f.options.map(o=>`<option value="${o}">${o}</option>`).join("")}
                            </select>`;
                    break;
                    case "radio":
                        inputHTML = `
                            <div class="radio-group">
                                ${f.options.map(o =>
                                    `<label>
                                        <input type="radio" name="fields[${i}]" value="${o}">
                                        ${o}
                                    </label>`
                                ).join("")}
                            </div>
                        `;
                    break;
                    case "checkbox":
                        inputHTML = `
                            <div style="display:flex;flex-direction:column;gap:6px">
                                ${f.options.map(o =>
                                    `<label style="font-weight:500">
                                        <input type="checkbox" name="fields[${i}][]" value="${o}"> ${o}
                                    </label>`
                                ).join("")}
                            </div>
                        `;
                    break;
                    case "datetime":
                        inputHTML = `<input type="datetime-local" name="fields[${i}]" />`;
                        break;
                    case "file":
                        inputHTML = `<input type="file" name="fields[${i}]" />`;
                        break;
                    default:
                        inputHTML = `<input type="text" name="fields[${i}]" />`;
                }

                el.innerHTML = `<label class="field-title">${f.label}</label>${inputHTML}`;

                if (!isViewMode) {
                    const removeBtn = document.createElement("div");
                    removeBtn.className = "field-remove";
                    removeBtn.innerHTML = "×";

                    removeBtn.onclick = (e) => {
                        e.stopPropagation();

                        if (!confirm("Remove this field?")) return;

                        fields.splice(i, 1);
                        selectedIndex = -1;
                        render();
                    };

                    el.appendChild(removeBtn);
                }

                if (!isViewMode) {
                    el.draggable = true;

                    el.onclick = () => {
                        selectedIndex = i;
                        showProps();
                    };

                    el.addEventListener("dragstart", () => {
                        el.classList.add("dragging");
                        elDragIndex = i;
                    });

                    el.addEventListener("dragend", () => {
                        el.classList.remove("dragging");
                    });

                    el.addEventListener("dragover", e => e.preventDefault());

                    el.addEventListener("drop", e => {
                        e.preventDefault();
                        reorderFields(elDragIndex, i);
                    });
                }

                canvas.appendChild(el);
            });

            /* SUBMIT BUTTON (VIEW MODE ONLY) */
            if (isViewMode) {
                /* ===============================
                USER EMAIL OPT-IN (VIEW MODE)
                =============================== */
                <?php if (!empty($editForm['form_settings'])): ?>
                    const settings = JSON.parse(<?= json_encode($editForm['form_settings']) ?>);

                    if (settings.userEmailEnabled) {

                        const wrapper = document.createElement("div");
                        wrapper.style.marginTop = "20px";

                        wrapper.innerHTML = `
                            <label style="display:flex;align-items:center;gap:8px;font-weight:600">
                                <input type="checkbox" id="sendCopyToggle">
                                Send me a copy of my responses
                            </label>

                            <div id="userEmailInputBox" style="display:none;margin-top:10px">
                                <label>Email address</label>
                                <input type="email"
                                    id="userEmailInput"
                                    placeholder="you@example.com"
                                    style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px" />
                            </div>
                        `;

                        canvas.appendChild(wrapper);

                        wrapper.querySelector("#sendCopyToggle").addEventListener("change", e => {
                            wrapper.querySelector("#userEmailInputBox").style.display =
                                e.target.checked ? "block" : "none";
                        });
                    }
                <?php endif; ?>

                const btn = document.createElement("button");
                btn.type = "button";
                // btn.onclick = () => {
                //     const payload = buildSheetPayload();
                //     document.getElementById("sheetJSON").value = JSON.stringify(payload);
                //     document.getElementById("sheetForm").submit();
                // };

                btn.onclick = () => {

                    if (!validateFormFieldsInline()) return;

                    const payload = buildSheetPayload();
                    document.getElementById("sheetJSON").value = JSON.stringify(payload);

                    /* ===============================
                    PASS USER EMAIL DATA TO SERVER
                    =============================== */
                    const sendCopy = document.getElementById("sendCopyToggle");
                    const emailInput = document.getElementById("userEmailInput");

                    if (sendCopy && sendCopy.checked) {

                        // Hidden input: tells server user opted in
                        const hiddenSend = document.createElement("input");
                        hiddenSend.type = "hidden";
                        hiddenSend.name = "send_copy";
                        hiddenSend.value = "1";

                        // Hidden input: user email address
                        const hiddenEmail = document.createElement("input");
                        hiddenEmail.type = "hidden";
                        hiddenEmail.name = "user_email";
                        hiddenEmail.value = emailInput.value;

                        const form = document.getElementById("sheetForm");
                        form.appendChild(hiddenSend);
                        form.appendChild(hiddenEmail);
                    }

                    document.getElementById("sheetForm").submit();
                };

                btn.textContent = "Submit";
                btn.style.marginTop = "20px";
                btn.style.padding = "10px 24px";
                btn.style.background = "var(--primary)";
                btn.style.border = "none";
                btn.style.borderRadius = "6px";
                btn.style.fontWeight = "700";
                canvas.appendChild(btn);
            }
        }


        function showProps(){
            if (isViewMode) return;

            const wrap = document.getElementById("properties");

            if(selectedIndex < 0){
                wrap.innerHTML = "Select field";
                return;
            }

            const f = fields[selectedIndex];

            wrap.innerHTML = `
                <label>Label</label>
                <input id="pLabel" type="text" value="${f.label}" />

                <label>Placeholder</label>
                <input id="pPH" type="text" value="${f.placeholder}" />

                <label>Required / Optional</label>
                <div class="toggle">
                    <label><input type="radio" name="req" value="1" ${f.required?'checked':''}/> Required</label>
                    <label><input type="radio" name="req" value="0" ${!f.required?'checked':''}/> Optional</label>
                </div>

                <div id="optBox" style="display:${["select","radio","checkbox"].includes(f.type)?"block":"none"}">
                    <label>Options</label>
                    <input id="pOptions" type="text" value="${f.options.join(', ')}" />
                </div>

                <div class="validation-group">
                    <label>Validation</label>
                    <div class="radio-group">
                        <label><input type="radio" name="val" value="email" ${f.validation==="email"?"checked":""}/> Email format</label>
                        <label><input type="radio" name="val" value="range" ${f.validation==="range"?"checked":""}/> Number range</label>
                        <label><input type="radio" name="val" value="filesize" ${f.validation==="filesize"?"checked":""}/> File size</label>
                    </div>
                </div>
            `;

            /* LIVE UPDATES — no save button */

            /* Label */
            document.getElementById("pLabel").addEventListener("input", e => {
                f.label = e.target.value;
                render();
            });

            /* Placeholder */
            document.getElementById("pPH").addEventListener("input", e => {
                f.placeholder = e.target.value;
            });

            /* Required / Optional */
            document.querySelectorAll("input[name='req']").forEach(radio => {
                radio.addEventListener("change", e => {
                    f.required = e.target.value === "1";
                    render();
                });
            });

            /* Options (select / radio only) */
            // if (["select","radio"].includes(f.type)) {
            if (["select","radio","checkbox"].includes(f.type)) {
                document.getElementById("pOptions").addEventListener("input", e => {
                    f.options = e.target.value.split(",").map(s => s.trim());
                });
            }

            /* Validation */
            document.querySelectorAll("input[name='val']").forEach(radio => {
                radio.addEventListener("change", e => {
                    f.validation = e.target.value;
                });
            });

        }

        /* Save */
        // document.getElementById("saveForm").onclick = function(){
        //     document.getElementById("postFormId").value = editFormId ?? '';
        //     document.getElementById("postTitle").value  = document.getElementById("formTitle").value;
        //     document.getElementById("postJSON").value   = JSON.stringify(fields);
        //     document.getElementById("saveFormPOST").submit();
        // };

        const saveBtn = document.getElementById("saveForm");

        if (saveBtn) {

            saveBtn.onclick = function(){

                const activeTab = document.querySelector(".tab.active").dataset.tab;

                document.getElementById("postFormId").value = editFormId ?? '';
                document.getElementById("postTitle").value  = document.getElementById("formTitle").value;
                document.getElementById("postJSON").value   = JSON.stringify(fields);
                document.getElementById("postSettings").value = JSON.stringify(collectSettings());

                // ============================
                // FORM TAB — old redirect logic (UNCHANGED)
                // ============================
                if(activeTab === "formTab"){
                    document.getElementById("saveFormPOST").submit();
                    return;
                }

                // ============================
                // SETTINGS TAB — ajax save only
                // ============================
                fetch(window.location.href, {
                    method: "POST",
                    body: new FormData(document.getElementById("saveFormPOST"))
                }).then(() => {
                    showToast();
                });

            };

        }

        function reorderFields(fromIndex, toIndex){
            if(fromIndex === toIndex) return;

            const movedItem = fields.splice(fromIndex, 1)[0];
            fields.splice(toIndex, 0, movedItem);

            selectedIndex = toIndex;
            render();
        }

        /// Load fields and title (works in both edit and view mode)
        <?php if ($editForm): ?>
            try {
                fields = JSON.parse(<?= json_encode($editForm['form_json']) ?>);

                // Set title safely — in view mode, no input exists, so skip
                const titleEl = document.getElementById("formTitle");
                if (titleEl) {
                    titleEl.value = <?= json_encode($editForm['form_title']) ?>;
                }
            } catch (e) {
                console.error("Failed to parse saved form JSON", e);
                fields = [];
            }
        <?php elseif ($preFieldsJSON !== ''): ?>
            try {
                fields = JSON.parse(<?= json_encode($preFieldsJSON) ?>);
            } catch (e) {
                console.error("Failed to parse pre_fields JSON", e);
                fields = [];
            }
        <?php else: ?>
            fields = [];
        <?php endif; ?>

        // Always render at the end
        render();

    </script>

    <script>
        document.querySelectorAll(".tab").forEach(tab=>{
            tab.onclick = () => {

                document.querySelectorAll(".tab").forEach(t=>t.classList.remove("active"));
                document.querySelectorAll(".tab-page").forEach(p=>p.classList.remove("active"));

                tab.classList.add("active");
                document.getElementById(tab.dataset.tab).classList.add("active");
            };
        });

        const actionSelect = document.getElementById("afterSubmitAction");
        const confirmBox  = document.getElementById("confirmBox");
        const redirectBox = document.getElementById("redirectBox");

        function updateSubmitView(){
            if(actionSelect.value === "message"){
                confirmBox.style.display = "block";
                redirectBox.style.display = "none";
            }
            else if(actionSelect.value === "redirect"){
                confirmBox.style.display = "none";
                redirectBox.style.display = "block";
            }
            else{
                confirmBox.style.display = "none";
                redirectBox.style.display = "none";
            }
        }

        actionSelect.addEventListener("change", updateSubmitView);
        updateSubmitView();
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const userToggle  = document.getElementById("userEmailToggle");
            const adminToggle = document.getElementById("adminEmailToggle");

            const userBox  = document.getElementById("userEmailBox");
            const adminBox = document.getElementById("adminEmailBox");

            if(userToggle){
                userToggle.addEventListener("change", () => {
                    userBox.style.display = userToggle.checked ? "block" : "none";
                });
            }

            if(adminToggle){
                adminToggle.addEventListener("change", () => {
                    adminBox.style.display = adminToggle.checked ? "block" : "none";
                });
            }

        });
    </script>

    <script>
        <?php if(!empty($editForm['form_settings'])): ?>

        const savedSettings = JSON.parse(<?= json_encode($editForm['form_settings']) ?>);

        afterSubmitAction.value = savedSettings.afterSubmitAction || "message";
        confirmBox.value = savedSettings.confirmMessage || confirmBox.value;
        redirectBox.value = savedSettings.redirectUrl || "http://";

        document.querySelectorAll('input[name="submissionPos"]').forEach(r=>{
            r.checked = r.nextSibling.textContent.trim() === savedSettings.submissionPosition;
        });

        userEmailToggle.checked = savedSettings.userEmailEnabled;
        adminEmailToggle.checked = savedSettings.adminEmailEnabled;

        if(savedSettings.userEmailEnabled){
            userEmailBox.style.display="block";
            userEmailBox.querySelector("textarea").value = savedSettings.userEmailMessage;
        }

        if(savedSettings.adminEmailEnabled){
            adminEmailBox.style.display="block";
            adminEmailBox.querySelector("input").value = savedSettings.adminEmail;
            adminEmailBox.querySelector("textarea").value = savedSettings.adminEmailMessage;
        }

        updateSubmitView();

        <?php endif; ?>
    </script>

    <script>
        function showToast(){
            const toast = document.getElementById("toast");
            toast.classList.add("show");

            setTimeout(()=>{
                toast.classList.remove("show");
            }, 2200);
        }
    </script>

    <div id="toast" class="toast">Form settings saved</div>

    <script>
        function copyShareLink(){

            const url =
                window.location.origin +
                window.location.pathname +
                "?id=<?= isset($_GET['id']) ? (int)$_GET['id'] : 0 ?>&mode=view";

            navigator.clipboard.writeText(url)
                .then(() => {
                    alert("Form link copied to clipboard");
                });
        }
    </script>

    <script>
        const toggle = document.getElementById("formActiveToggle");
        const statusText = document.getElementById("formStatusText");

        if (toggle) {
            toggle.addEventListener("change", () => {

                const isActive = toggle.checked ? 1 : 0;

                statusText.textContent = isActive ? "Active" : "Inactive";

                const data = new FormData();
                data.append("toggle_form_status", "1");
                data.append("form_id", <?= isset($_GET['id']) ? (int)$_GET['id'] : 0 ?>);
                data.append("is_active", isActive);

                fetch(window.location.href, {
                    method: "POST",
                    body: data
                }).then(() => {
                    // ✅ USE SAME SETTINGS TAB TOAST
                    showToast();
                });
            });
        }
    </script>

    <div class="share-overlay" id="shareOverlay" style="display:none">
        <div class="share-modal">

            <!-- Header -->
            <div class="share-header">
            <h3>Share Form</h3>
            <button class="close-btn" onclick="closeShareModal()">×</button>
            </div>

            <!-- Tabs -->
            <div class="share-tabs">
            <button class="share-tab active" data-tab="emailTab">Email</button>
            <button class="share-tab" data-tab="linkTab">Link</button>
            <button class="share-tab" data-tab="embedTab">Embed</button>
            </div>

            <!-- EMAIL TAB -->
            <div class="share-tab-content active" id="emailTab">
            <label>To</label>
            <input type="email" id="shareEmailTo" placeholder="example@email.com">

            <label>Subject</label>
            <input type="text" id="shareEmailSubject" value="Untitled sheet">

            <label>Message</label>
            <textarea id="shareEmailMessage" rows="4"></textarea>

            <p class="note">Note: the link will be included in the message</p>

            <label class="checkbox-row">
                <input type="checkbox" id="ccMe">
                <span>CC me</span>
            </label>

            <div class="share-footer">
                <button type="button" onclick="closeShareModal()">Cancel</button>
                <button type="button" class="primary" onclick="sendShareEmail()">Send</button>
            </div>
            </div>

            <!-- LINK TAB -->
            <div class="share-tab-content" id="linkTab">
            <label>Form Link</label>
            <input type="text" id="shareFormLink" readonly>

            <div class="share-footer">
                <button onclick="closeShareModal()">Cancel</button>
                <button class="primary" onclick="copyLink()">Copy</button>
            </div>
            </div>

            <!-- EMBED TAB -->
            <div class="share-tab-content" id="embedTab">
            <label>Embed HTML</label>
            <textarea id="embedCode" rows="3" readonly></textarea>

            <div class="embed-size">
                <div>
                <label>Width</label>
                <input type="number" id="embedWidth" value="900">
                </div>
                <div>
                <label>Height</label>
                <input type="number" id="embedHeight" value="600">
                </div>
            </div>

            <div class="share-footer">
                <button onclick="closeShareModal()">Cancel</button>
                <button class="primary" onclick="copyEmbed()">Copy</button>
            </div>
            </div>

        </div>
    </div>

    <script>
        const formLink =
        window.location.origin +
        window.location.pathname +
        "?id=<?= (int)$_GET['id'] ?>&mode=view";

        function openShareModal(){
        document.getElementById("shareOverlay").style.display = "flex";
        document.getElementById("shareFormLink").value = formLink;
        updateEmbed();
        }

        function closeShareModal(){
        document.getElementById("shareOverlay").style.display = "none";
        }

        document.querySelectorAll(".share-tab").forEach(tab=>{
        tab.onclick = () => {
            document.querySelectorAll(".share-tab").forEach(t=>t.classList.remove("active"));
            document.querySelectorAll(".share-tab-content").forEach(c=>c.classList.remove("active"));
            tab.classList.add("active");
            document.getElementById(tab.dataset.tab).classList.add("active");
        };
        });

        // function copyLink(){
        //   navigator.clipboard.writeText(formLink);
        //   showToast();
        // }

        function copyLink(){
        navigator.clipboard.writeText(formLink).then(() => {
            Swal.fire({
            icon: "success",
            title: "Copied",
            text: "Form link copied successfully",
            confirmButtonColor: "#fec700"
            });
        });
        }


        function updateEmbed(){
        embedCode.value =
            `<iframe width="${embedWidth.value}" height="${embedHeight.value}" src="${formLink}"></iframe>`;
        }

        embedWidth.oninput = embedHeight.oninput = updateEmbed;

        // function copyEmbed(){
        //   navigator.clipboard.writeText(embedCode.value);
        //   showToast();
        // }

        function copyEmbed(){
        navigator.clipboard.writeText(embedCode.value).then(() => {
            Swal.fire({
            icon: "success",
            title: "Copied",
            text: "Embed code copied successfully",
            confirmButtonColor: "#fec700"
            });
        });
        }


        // function sendShareEmail(){
        //   const to = shareEmailTo.value.trim();
        //   if(!to){
        //     alert("Enter email address");
        //     return;
        //   }

        //   const data = new FormData();
        //   data.append("share_email","1");
        //   data.append("to",to);
        //   data.append("subject",shareEmailSubject.value);
        //   data.append("message",shareEmailMessage.value);
        //   data.append("link",formLink);
        //   data.append("cc",ccMe.checked ? 1 : 0);

        //   fetch(window.location.href,{
        //     method:"POST",
        //     body:data
        //   }).then(res=>{
        //     if(res.ok){
        //       showToast();
        //       closeShareModal();
        //     } else {
        //       alert("Email failed");
        //     }
        //   });
        // }

        function sendShareEmail(){

        const to = shareEmailTo.value.trim();

        if(!to){
            Swal.fire({
            icon: "warning",
            title: "Enter email address",
            confirmButtonColor: "#fec700"
            });
            return;
        }

        const data = new FormData();
        data.append("share_email","1");
        data.append("to",to);
        data.append("subject",shareEmailSubject.value);
        data.append("message",shareEmailMessage.value);
        data.append("link",formLink);
        data.append("cc",ccMe.checked ? 1 : 0);

        fetch(window.location.href,{
            method:"POST",
            body:data
        }).then(res=>{
            if(res.ok){
            Swal.fire({
                icon: "success",
                title: "Email Sent",
                text: "Form has been shared successfully",
                confirmButtonColor: "#fec700"
            });
            closeShareModal();
            } else {
            Swal.fire({
                icon: "error",
                title: "Email Failed",
                confirmButtonColor: "#fec700"
            });
            }
        });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>

<?php
    if (!$isViewMode) {
        include './partials/layouts/layoutBottom.php';
    }
?>
