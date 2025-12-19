<?php include './partials/layouts/layoutTop.php'; ?>

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

        /* FIRST SUBMISSION → CREATE SHEET */
        $stmt = $conn->prepare(
            "INSERT INTO sheets (form_id, name, data, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())"
        );
        $stmt->bind_param(
            "iss",
            $formId,
            $newData['name'],
            json_encode($newData)
        );
        $stmt->execute();

    } else {

        /* NEXT SUBMISSIONS → APPEND ROW */
        $row = $result->fetch_assoc();
        $sheetId   = $row['id'];
        $sheetData = json_decode($row['data'], true);

        /* STEP 2: Find next row number */
        $existingCells = $sheetData['cells'] ?? [];
        $maxRow = 0;

        foreach ($existingCells as $cell => $value) {
            preg_match('/\d+/', $cell, $m);
            $maxRow = max($maxRow, (int)$m[0]);
        }

        $nextRow = $maxRow + 1;

        /* STEP 3: Append cells */
        foreach ($newData['cells'] as $cell => $value) {
            $col = preg_replace('/\d+/', '', $cell);
            $sheetData['cells'][$col . $nextRow] = $value;
        }

        /* STEP 4: Update row count */
        $sheetData['rows'] = max($sheetData['rows'], $nextRow);

        /* STEP 5: Save */
        $stmt = $conn->prepare(
            "UPDATE sheets SET data = ?, updated_at = NOW() WHERE id = ?"
        );
        $updatedJSON = json_encode($sheetData);
        $stmt->bind_param("si", $updatedJSON, $sheetId);
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
    font-size:18px;
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

/* SAVE OR UPDATE FORM STRUCTURE */
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formId = isset($_POST['form_id']) && is_numeric($_POST['form_id'])
        ? (int)$_POST['form_id']
        : null;

    $title = $_POST['formTitle'] ?? '';
    $json  = $_POST['formJSON'] ?? '';

    if ($formId) {
        $stmt = $conn->prepare(
            "UPDATE form_builder SET form_title = ?, form_json = ? WHERE id = ?"
        );
        $stmt->bind_param("ssi", $title, $json, $formId);
        $stmt->execute();
        $message = "Form Updated Successfully";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO form_builder (form_title, form_json) VALUES (?, ?)"
        );
        $stmt->bind_param("ss", $title, $json);
        $stmt->execute();
        $message = "Form Created Successfully";
    }

    echo "<div style='padding:10px;background:#d1fae5;border:1px solid #10b981;margin:10px;border-radius:6px'>
            {$message}
          </div>";
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
    <div class="panel center">
        <!-- <input id="formTitle" class="form-title-input" placeholder="Form title" /> -->
        <?php if ($isViewMode): ?>
    <h4 style="margin:0 0 16px;font-size:20px !important;font-weight:700;">
        <?= htmlspecialchars($editForm['form_title'] ?? '') ?>
    </h4>
<?php else: ?>
    <input
        id="formTitle"
        class="form-title-input"
        placeholder="Form title"
        value="<?= $editForm ? htmlspecialchars($editForm['form_title']) : '' ?>"
    />
<?php endif; ?>


        <form method="POST" enctype="multipart/form-data">

    <input type="hidden" name="form_id" value="<?= isset($formId) ? $formId : '' ?>">
    <input type="hidden" name="submit_form" value="1">
    <div id="canvas" class="canvas"></div>
</form>

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
function buildSheetPayload() {

    const cells = {};
    const headers = [];
    const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");

    let colIndex = 1;   // ⬅️ START FROM B (skip A)
    const row = 1;     // still row 1 (append logic will handle next rows)

    fields.forEach((f, i) => {

        const col = alphabet[colIndex];

        let value = "";

        /* TEXT, EMAIL, NUMBER, PHONE, DATE */
        const input = document.querySelector(`[name="fields[${i}]"]`);

        /* CHECKBOX */
        const checkboxes = document.querySelectorAll(`[name="fields[${i}][]"]`);

        /* RADIO */
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
        headers.push(col);

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
    inputHTML = `
        <input
            type="${f.type === 'phone' ? 'tel' : f.type}"
            name="fields[${i}]"
            placeholder="${f.placeholder}"
        />
    `;
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
                inputHTML = f.options.map(o =>
                    `<label><input type="radio" name="fields[${i}]" value="${o}"> ${o}</label>`
                ).join("<br>");
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
btn.onclick = () => {
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

/* LOAD EXISTING FORM FIELDS (EDIT MODE) */
<?php if ($editForm): ?>
    try {
        fields = JSON.parse(<?= json_encode($editForm['form_json']) ?>);
    } catch (e) {
        fields = [];
    }

    if (Array.isArray(fields) && fields.length > 0) {
        render();
    }
<?php endif; ?>


</script>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php'; ?>