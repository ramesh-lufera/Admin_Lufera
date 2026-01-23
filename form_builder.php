<?php include './partials/connection.php'; ?>

<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sheet_data'])) {

        $formId   = (int)$_POST['form_id'];
        $newData  = json_decode($_POST['sheet_data'], true);

        /* STEP 1: Check if sheet already exists */
        $stmt = $conn->prepare(
            "SELECT id, data FROM sheets WHERE form_id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $formId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            // /* FIRST SUBMISSION → CREATE SHEET */
            // $sheetName = $newData['name'] ?? 'Sheet1';
            // $sheetJSON = json_encode($newData);

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
            // $sheetJSON = json_encode($newData);

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

            // /* STEP 2: Find next row number */
            // $existingCells = $sheetData['cells'] ?? [];
            // $maxRow = 0;

            // foreach ($existingCells as $cell => $value) {
            //     preg_match('/\d+/', $cell, $m);
            //     $maxRow = max($maxRow, (int)$m[0]);
            // }

            // $nextRow = $maxRow + 1;

            // /* STEP 3: Append cells */
            // foreach ($newData['cells'] as $cell => $value) {
            //     $col = preg_replace('/\d+/', '', $cell);
            //     $sheetData['cells'][$col . $nextRow] = $value;
            // }

            // /* STEP 4: Update row count */
            // $sheetData['rows'] = max($sheetData['rows'], $nextRow);

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

            $nextRow = $maxFilledRow + 1;

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

        echo "<div style='padding:12px;background:#d1fae5;border:1px solid #10b981;border-radius:6px;margin:12px'>
                Sheet row saved successfully
            </div>";

        return;
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
        position:absolute;
        top:20px;
        right:20px;
        background:var(--primary);
        padding:8px 30px;
        font-weight:700;
        border:none;
        border-radius:6px;
        cursor:pointer;
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
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $formId = isset($_POST['form_id']) && is_numeric($_POST['form_id'])
                ? (int)$_POST['form_id']
                : null;

            $title = $_POST['formTitle'] ?? '';
            $json  = $_POST['formJSON'] ?? '';

            if ($formId) {
                // UPDATE existing form
                $stmt = $conn->prepare(
                    "UPDATE form_builder SET form_title = ?, form_json = ? WHERE id = ?"
                );
                $stmt->bind_param("ssi", $title, $json, $formId);
                $stmt->execute();
                $redirectId = $formId;
            } else {
                // CREATE new form
                $stmt = $conn->prepare(
                    "INSERT INTO form_builder (form_title, form_json) VALUES (?, ?)"
                );
                $stmt->bind_param("ss", $title, $json);
                $stmt->execute();
                $redirectId = $conn->insert_id;

                /* ✅ CREATE EMPTY SHEET IMMEDIATELY */

                $emptySheetData = json_encode([
                    "rows" => 10,        // ✅ fixed initial rows
                    "cols" => 0,
                    "headers" => [],
                    "columnTypes" => [],
                    "cells" => []
                ]);

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

            // ✅ REDIRECT TO INDIVIDUAL FORM PAGE
            // header("Location: form_builder.php?id={$redirectId}&mode=view");
            // exit;

            echo "<script>
                window.location.href = 'form_builder.php?id={$redirectId}&mode=view';
            </script>";
            exit;
        }


        /* LOAD FORM FOR EDITING */
        $editForm = null;

        $isViewMode = isset($_GET['mode']) && $_GET['mode'] === 'view';

        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $formId = (int)$_GET['id'];

            $stmt = $conn->prepare("SELECT form_title, form_json FROM form_builder WHERE id = ?");
            $stmt->bind_param("i", $formId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $editForm = $result->fetch_assoc();
            }
        }

        $preTitle = '';
        $preFieldsJSON = '';

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            // Only use pre_ parameters when NOT editing an existing form
            $preTitle = isset($_GET['pre_title']) ? trim(htmlspecialchars($_GET['pre_title'])) : '';
            $preFieldsJSON = isset($_GET['pre_fields']) ? $_GET['pre_fields'] : '';
        }
    ?>

    <div class="app" id="formBuilderWrapper">

        <!-- Save Button at top-right of all 3 sections (NOT inside center panel) -->
        <button class="save-btn" id="saveForm">Save</button>

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

    <!-- Hidden POST Form -->
    <form id="saveFormPOST" method="POST" style="display:none">
    <input type="hidden" name="form_id" id="postFormId">
    <input type="hidden" name="formTitle" id="postTitle">
    <input type="hidden" name="formJSON" id="postJSON">
    </form>

    <form id="sheetForm" method="POST" style="display:none">
        <input type="hidden" name="sheet_name" value="Sheet1">
        <input type="hidden" name="form_id" value="<?= (int)$formId ?>">
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

            return true;
        }
    </script>

    <script>
        function buildSheetPayload() {

            const cells = {};
            const headers = [];
            const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");

            let colIndex = 1; // Start from B (skip A)
            const row = 1;

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
                }
                else if (radio) {
                    value = radio.value;
                }
                else if (input) {
                    value = input.value || "";
                }

                cells[`${col}${row}`] = value;

                // ✅ HEADER = FIELD LABEL
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
        const isViewMode = <?= isset($isViewMode) && $isViewMode ? 'true' : 'false' ?>;

        let fields = [];
        let selectedIndex = -1;
        let elDragIndex = null;

        const editFormId = <?= isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 'null' ?>;

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

                <div id="optBox" style="display:${["select","radio"].includes(f.type)?"block":"none"}">
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
        if (["select","radio"].includes(f.type)) {
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
        document.getElementById("saveForm").onclick = function(){
            document.getElementById("postFormId").value = editFormId ?? '';
            document.getElementById("postTitle").value  = document.getElementById("formTitle").value;
            document.getElementById("postJSON").value   = JSON.stringify(fields);
            document.getElementById("saveFormPOST").submit();
        };


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

</body>
</html>
