<?php
include 'partials/layouts/layoutTop.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$sheetId   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sheetName = "Untitled Sheet";

if ($sheetId <= 0) {
    header("Location: dashboard-sheets.php");
    exit;
}

$stmt = $conn->prepare("SELECT name FROM sheets WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $sheetId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard-sheets.php");
    exit;
}

$sheetRow  = $result->fetch_assoc();
$sheetName = $sheetRow['name'] ?? "Untitled Sheet";
$stmt->close();

$sheetData = null;

// Load sheet data by ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM sheets WHERE id = $id LIMIT 1");

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $sheetData = json_decode($row['data'], true);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Excel-like HTML Spreadsheet</title>

  <style>
    :root{--cell-width:150px;--cell-height:35px;--header-bg:#f3f4f6}
    .sheet{border:1px solid #ddd;overflow:auto;max-width:100%;box-shadow:0 2px 6px rgba(0,0,0,0.04)}
    table{border-collapse:collapse;min-width:900px}
    th,td{border-right:1px solid #e6e6e6;border-bottom:1px solid #e6e6e6;padding:0;margin:0;}
    th{background:var(--header-bg);position:sticky;top:0;z-index:3;text-align:center;font-weight:600}
    .row-header{position:sticky;left:0;z-index:2;background:var(--header-bg);width:40px;text-align:center}
    .cell{font-size:14px;height:var(--cell-height);min-width:var(--cell-width);padding:4px;box-sizing:border-box;cursor:text;}
    .cell:focus{outline:2px solid #2563eb}
    .selected{background:rgba(37,99,235,0.08)}
    caption{caption-side:top;text-align:left;padding:8px;font-weight:600}
    .cell.checkbox, .cell>select{text-align:center}
    /* input[type=file]{display:none} */
/* Modal for column type */
#columnTypeModal {
    display:none;
    position:fixed;
    top:50%; left:50%;
    transform:translate(-50%,-50%);
    background:#fff;
    border:1px solid #ccc;
    border-radius:8px;
    box-shadow:0 4px 20px rgba(0,0,0,0.2);
    padding:20px;
    width:360px;
    z-index:10000;
  }
  #columnTypeModal.open {display:block;}
  #modalBackdrop {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.4);
    z-index:9999;
  }
  #modalBackdrop.open {display:block;}
    .comment-panel {
    position: fixed;
    right: -360px;
    top: 0;
    width: 360px;
    height: 100%;
    background: #fff;
    border-left: 1px solid #ddd;
    box-shadow: -2px 0 6px rgba(0,0,0,.1);
    transition: right .3s ease;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    }

    .comment-panel.open { right: 0; }

    .comment-header {
        padding: 12px;
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid #eee;
    }

    .comment-list {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
    }

    .comment {
        margin-bottom: 12px;
        background: #f5f6fa;
        padding: 8px;
        border-radius: 6px;
    }

    .reply {
        margin-left: 20px;
        margin-top: 6px;
        background: #cfdffa;
    }

    .comment-input {
        border-top: 1px solid #eee;
        padding: 10px;
    }

    .comment-input textarea {
        width: 100%;
        height: 60px;
    }
    .task-actions {
    opacity: 0;                      /* Hidden by default */
    transition: opacity 0.3s ease;
    margin-left: 8px;
    display: inline-flex;
    gap: 8px;
    align-items: center;
    pointer-events: none;            /* Disable clicks when hidden */
}

tr:hover .task-actions {
    opacity: 1;                      /* Show on hover even if no data */
    pointer-events: auto;
}

/* NEW: If row has comment OR attachment → force full visibility */
.task-actions.has-activity {
    opacity: 1 !important;           /* Always visible if has comment or attachment */
    pointer-events: auto !important;
}

/* Icon colors */
.comment-icon,
.attach-icon {
    color: #aaa;                /* gray when no activity */
    font-size: 14px;
    cursor: pointer;
    opacity: 0;               /* faded when inactive */
    transition: all 0.2s ease;
}

/* Active states */
.comment-icon.has-comment {
    color: #2563eb !important;  /* blue */
    opacity: 1;
    font-weight: bold;
}

.attach-icon.has-attachment {
    color: #16a34a !important;  /* green */
    opacity: 1;
    font-weight: bold;
}

.delete-row-icon {
    opacity: 0;
    transition: opacity 0.2s ease;
    font-size: 14px;
}

/* Show delete icon on row hover */
tr:hover .delete-row-icon {
    opacity: 1;
}

/* Optional: make it slightly more visible when has activity */
.task-actions.has-activity ~ .delete-row-icon,
tr:hover .delete-row-icon {
    opacity: 1;
}
.delete-col-icon {
    pointer-events: auto; /* Ensure click works */
}
input, select{
    width:-webkit-fill-available;
}
/* Dropdown styles */
.dropdown {
    position: relative;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 250%;
    left: 0;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 160px;
    z-index: 1000;
    padding: 8px;
}

.dropdown-menu button {
    width: 100%;
    text-align: left;
    padding: 8px 12px;
    border: none;
    background: none;
    cursor: pointer;
} 

.dropdown-menu button:hover {
    background: #f3f4f6;
}

.dropdown.open .dropdown-menu {
    display: block;
}

.add-col-icon {
    opacity: 0;
    transition: opacity 0.2s ease;
}

thead th:hover .add-col-icon {
    opacity: 1;
}
.add-row-icon {
    opacity: 0;
    font-size: 12px;
    margin-left: 6px;
    transition: opacity 0.2s ease;
}

tr:hover .add-row-icon {
    opacity: 1;
}

tr:hover .comment-icon,
tr:hover .attach-icon {
    opacity: 1;                 /* always visible on row hover */
}
.bell-icon {
    color: #6b7280;                 /* gray when no reminder */
    font-size: 14px;
    cursor: pointer;
    opacity: 0;                     /* hidden until hover or has-reminder */
    transition: all 0.2s ease;
}

.bell-icon.has-reminder {
    color: #dc2626 !important;      /* red when active */
    opacity: 1 !important;
    font-weight: bold;              /* optional emphasis */
}

/* Always visible on row hover */
tr:hover .bell-icon {
    opacity: 1;
}

/* Optional: make task-actions block always visible if ANY activity (comments + attach + reminder) */
.task-actions.has-activity {
    opacity: 1 !important;
    pointer-events: auto !important;
}
.reply-box {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}

.reply-box textarea {
    resize: none;
    font-size: 0.9rem;
    height:60px !important
}

.comment button.btn-link {
    font-size: 0.85rem;
    text-decoration: none;
}

.comment button.btn-link:hover {
    text-decoration: underline;
}
.reply-error {
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 4px;
}
.attachment-row{
    width: max-content;
    padding: 2px 8px;
}
.file-name{
    display: block;
    white-space: nowrap;
    width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
  
</head>

<body>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a onclick="handleBack()" class="cursor-pointer fw-bold">
            <span class="fa fa-arrow-left"></span> Back
        </a>
        <div class="text-center flex-grow-1">
            <h6 class="fw-semibold mb-0"><?= htmlspecialchars($sheetName) ?></h6>
        </div>
        <div style="width:120px"></div> <!-- spacer to balance layout -->
    </div>

    <div class="card radius-12 h-100">
        <div class="card-body p-24">

            <div class="toolbar mb-3 d-flex gap-2 align-items-center">                
                <div class="dropdown">
                    <button class="dropdown-btn">File</button>
                    <div class="dropdown-menu">
                        <button class="new_sheet" onclick="Redirect()">New</button>
                        <button id="export-csv">Export</button>
                        <button id="load-db">Open</button>
                        <button id="clear">Clear</button>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropdown-btn px-3">Form</button>
                    <div class="dropdown-menu">
                        <button id="export-to-form">Create Form</button>
                    </div>
                </div>                
            </div>

            <div class="mb-3 d-flex gap-3 align-items-center toolbar">
                <button id="save-db"><span class="fa fa-save text-xxl text-primary"></span></button>
                <button id="export-csv"><span class="fa fa-download text-xxl text-success"></span></button>
                <button id="load-db"><span class="fa fa-folder-open text-xxl text-warning"></span></button>
                <button id="clear"><span class="fa fa-close text-xxl text-danger"></span></button>
            </div>
            <div class="sheet" id="sheet"></div>
        </div>
    </div>
</div>

<script>
let hasUnsavedChanges = false;
let isAddingNewColumn = false;
function Redirect() {
    window.location = "sheets.php";
}

document.getElementById("export-to-form").onclick = () => {
    let formTitle = <?= json_encode($sheetName) ?>;   // Use sheet name as form title

    const tempFields = [];
    for (let c = 2; c <= COLS; c++) {
        const label = (columnHeaders[c] || defaultFieldName(c - 1)).trim();
        if (!label) continue;

        const colConfig = columnTypes[c] || { type: "text" };
        const colType   = colConfig.type;

        let formType = "text";
        if (colType === "number")    formType = "number";
        else if (colType === "date") formType = "datetime";
        else if (colType === "dropdown") formType = "select";
        else if (colType === "checkbox") formType = "checkbox";
        // you can map more types if needed

        const options = (colType === "dropdown" && colConfig.options?.length > 0)
            ? colConfig.options
            : (formType === "checkbox" ? ["Yes"] : ["Option 1", "Option 2"]);

        tempFields.push({
            id: Date.now() + c,
            type: formType,
            label: label,
            placeholder: "",
            required: false,
            options: options,
            value: "",           // important: always empty on template
            validation: ""
        });
    }

    if (tempFields.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Nothing to export',
            text: 'No columns to export (only Tasks column found).',
        });
        return;
    }

    const params = new URLSearchParams();
    params.append('sheet_id',   activeSheetId);              // link form to this sheet
    params.append('pre_title',  formTitle);                  // use sheet name as formTitle
    params.append('sheet_name', formTitle);                  // explicit sheet name if needed
    params.append('pre_fields', JSON.stringify(tempFields)); // initial form structure

    window.location.href = `form_builder.php?${params.toString()}`;
};

/* ------------------------------------------------------------
   BASE VARIABLES (init first)
------------------------------------------------------------ */
let focusedCell = null;
const data = {};
let ROWS = 10;
let COLS = 4;
let columnHeaders = {};
let columnTypes = {};
let currentColumnForType = null;

// IMPORTANT: Declared early to avoid initialization errors
const rowComments = {};        // { rowNumber: commentCount }
const rowAttachments = {};     // { rowNumber: attachmentCount }
const rowReminders  = {};

let activeRow = null;
let activeAttachRow = null;
let activeSheetId = <?= $sheetId ?>;

/* ------------------------------------------------------------
   PRELOAD PHP DATA BEFORE TABLE IS BUILT
------------------------------------------------------------ */
/* ------------------------------------------------------------
   PRELOAD PHP DATA + MIGRATE OLD CELL KEYS
------------------------------------------------------------ */
<?php if ($sheetData): ?>
    const loaded = <?= json_encode($sheetData) ?>;

    ROWS = loaded.rows || ROWS;
    COLS = loaded.cols || COLS;

    if (loaded.cells) {
        Object.keys(loaded.cells).forEach(oldKey => {
            // Try to detect old rXcY format
            const match = oldKey.match(/^r(\d+)c(\d+)$/);
            if (match) {
                const row = parseInt(match[1], 10);
                const col = parseInt(match[2], 10);
                const newKey = colName(col) + row;          // ← B1, C1, B2, ...
                data[newKey] = { raw: loaded.cells[oldKey] };
            } else {
                // Already in A1-style or unknown → keep as is
                data[oldKey] = { raw: loaded.cells[oldKey] };
            }
        });
    }

    // headers, columnTypes etc. stay the same
    if (loaded.headers) {
        loaded.headers.forEach((h, i) => {
            columnHeaders[i + 2] = h;
        });
    }

    if (loaded.columnTypes) {
        columnTypes = loaded.columnTypes;
    }
<?php endif; ?>

/* ------------------------------------------------------------
   HELPERS
------------------------------------------------------------ */
function defaultFieldName(col) {
    //return `Column Field ${col}`;
    return `ColumnField_${col}`;
}

function colName(n) {
    let s = "";
    while (n > 0) {
        let r = (n - 1) % 26;
        s = String.fromCharCode(65 + r) + s;
        n = Math.floor((n - 1) / 26);
    }
    return s || "A";
}

//function cellId(r, c) { return defaultFieldName(c) + r; }

function cellId(row, col) {
    return colName(col) + row;
}

/* ------------------------------------------------------------
   RENDER CELL CONTENT BASED ON COLUMN TYPE
------------------------------------------------------------ */
function renderCellContent(cellEl, col) {
    const id = cellEl.id;
    const saved = data[id]?.raw || "";
    const config = columnTypes[col] || { type: "text" };
    const type = config.type;

    cellEl.innerHTML = "";
    cellEl.className = "cell";

    let input;

    switch (type) {
        case "number":
            input = document.createElement("input");
            input.type = "number";
            input.value = saved;
            cellEl.classList.add("number");
            break;

        case "date":
            input = document.createElement("input");
            input.type = "date";
            input.value = saved;
            break;

        case "checkbox":
            input = document.createElement("input");
            input.type = "checkbox";
            input.classList.add("form-check-input");
            input.checked = saved === "true";
            cellEl.classList.add("checkbox");
            break;

        case "dropdown":
            input = document.createElement("select");
            const options = config.options && config.options.length > 0 ? config.options : ["Option 1", "Option 2"];

            const emptyOpt = document.createElement("option");
            emptyOpt.value = "";
            emptyOpt.text = "-";
            input.appendChild(emptyOpt);

            options.forEach(opt => {
                const o = document.createElement("option");
                o.value = opt;
                o.textContent = opt;
                if (opt === saved) o.selected = true;
                input.appendChild(o);
            });
            break;

        case "file":
            // Treat as a file/url field stored as plain text.
            // User can paste a file URL or relative path (e.g. uploads/...).
            input = document.createElement("input");
            input.type = "text";
            input.placeholder = "Enter file URL or path";
            input.value = saved;
            cellEl.classList.add("text");
            break;

        case "text":
        default:
            cellEl.textContent = saved;
            cellEl.contentEditable = true;
            cellEl.classList.add("text");
            return;
    }

    input.addEventListener("change", () => {
        const value = type === "checkbox" ? input.checked : input.value;
        data[id] = { raw: value.toString() };
        recalcAll();
    });

    input.addEventListener("focus", () => cellEl.classList.add("selected"));
    input.addEventListener("blur", () => cellEl.classList.remove("selected"));

    cellEl.appendChild(input);
}

/* ------------------------------------------------------------
   BUILD TABLE
------------------------------------------------------------ */
function buildTable() {
    const sheetEl = document.getElementById("sheet");
    const table = document.createElement("table");

    const thead = document.createElement("thead");
    const hRow = document.createElement("tr");
    hRow.appendChild(document.createElement("th"));

    for (let c = 1; c <= COLS; c++) {
        const config = columnTypes[c] || { type: "text" };

        // Hide file-type columns entirely from the visible sheet
        if (c !== 1 && config.type === "file") {
            continue;
        }

        const th = document.createElement("th");
        th.dataset.c = c;

        if (c === 1) {
            th.textContent = "Tasks";
            th.contentEditable = false;
            th.style.minWidth = "160px";
            th.style.height = "40px";
            th.style.alignContent = "center";
        } else {
            // Container for name and trash
            const wrapper = document.createElement("div");
            wrapper.style.display = "flex";
            wrapper.style.alignItems = "center";
            wrapper.style.justifyContent = "center";
            wrapper.style.width = "100%";
            wrapper.style.position = "relative";
            wrapper.style.gap = "6px";
            wrapper.style.minWidth = "200px";
            // Column name
            const nameSpan = document.createElement("span");
            nameSpan.textContent = columnHeaders[c] || "Column Field";
            // ➕ Add column button
            const addSpan = document.createElement("span");
            addSpan.className = "fa fa-plus text-success add-col-icon";
            addSpan.title = "Add column";
            addSpan.style.cursor = "pointer";
            addSpan.style.fontSize = "11px";
            addSpan.onclick = (e) => {
                e.stopPropagation();
                e.preventDefault();

                isAddingNewColumn = true;
                insertAfterColumn = c;

                openColumnTypeModal(c + 1);         // open modal for the would-be new column
            };
            // Trash icon
            const trashSpan = document.createElement("span");
            trashSpan.className = "delete-col-icon fa fa-close text-danger position-absolute";
            trashSpan.style.right = "8px";
            trashSpan.style.opacity = "0";
            trashSpan.style.transition = "opacity 0.2s ease";
            trashSpan.style.cursor = "pointer";
            trashSpan.style.fontSize = "12px";
            trashSpan.title = "Delete this column";
            trashSpan.onclick = (e) => {
                e.stopPropagation();
                deleteColumn(c);
            };

            wrapper.appendChild(nameSpan);
            wrapper.appendChild(addSpan);
            wrapper.appendChild(trashSpan);
            th.appendChild(wrapper);

            th.style.cursor = "pointer";
            th.style.height = "40px";
            th.style.alignContent = "center";
            th.title = "Click to edit column • Hover for delete";

            // Hover: show/hide trash icon
            th.addEventListener("mouseenter", () => {
                trashSpan.style.opacity = "1";
            });
            th.addEventListener("mouseleave", () => {
                trashSpan.style.opacity = "0";
            });

            // Click header (not trash) to open modal
            th.addEventListener("click", (e) => {
                if (e.target.classList.contains("delete-col-icon")) return;
                currentColumnForType = c;
                openColumnTypeModal(c);
            });
        }

        hRow.appendChild(th);
    }
    thead.appendChild(hRow);
    table.appendChild(thead);

    const tbody = document.createElement("tbody");
    for (let r = 1; r <= ROWS; r++) {
        const tr = document.createElement("tr");

        const rh = document.createElement("th");
        rh.className = "row-header";
        rh.style.minWidth = "70px";
        rh.textContent = r;
        tr.appendChild(rh);

        for (let c = 1; c <= COLS; c++) {
            const config = columnTypes[c] || { type: "text" };

            // Skip rendering file-type data columns in the grid
            if (c !== 1 && config.type === "file") {
                continue;
            }

            const td = document.createElement("td");
            const container = document.createElement("div");
            container.className = "cell";
            container.dataset.r = r;
            container.dataset.c = c;
            container.id = cellId(r, c);

            if (c === 1) {
                container.innerHTML = `
                    <span class="task-text" contenteditable="false"></span>
                    <span class="task-actions">
                        <span class="comment-icon fa fa-message cursor-pointer" title="Comments" onclick="openComments(${r})"></span>
                        <span class="attach-icon fa fa-paperclip cursor-pointer" title="Attachments" onclick="openAttachments(${r})"></span>
                        <!--  ──► NEW ──►  -->
                        <span class="bell-icon fa fa-bell cursor-pointer ${rowReminders[r] > 0 ? 'has-reminder' : ''}" 
                        title="${rowReminders[r] > 0 ? 'Has reminder(s)' : 'Set Reminder'}" 
                        onclick="openReminderModal(${r})"></span>
                        <!--  ─────────────── -->
                        <span class="fa fa-plus add-row-icon cursor-pointer text-success" 
                        title="Insert row below" 
                        onclick="addRowAfter(${r})"></span>
                        <span class="delete-row-icon fa fa-close cursor-pointer text-danger" 
                        title="Delete this row" 
                        onclick="deleteRow(${r})"></span>
                    </span>
                `;
                container.contentEditable = false;
            } else {
                renderCellContent(container, c);
            }

            container.addEventListener("input", onEdit);
            container.addEventListener("focus", onFocus);
            container.addEventListener("keydown", onKeyDown);

            td.appendChild(container);
            tr.appendChild(td);
        }
        tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    sheetEl.innerHTML = "";
    sheetEl.appendChild(table);
}

function rebuildPreserveData() {
    const dataSnapshot = JSON.parse(JSON.stringify(data));
    const headerSnapshot = { ...columnHeaders };
    const typesSnapshot = { ...columnTypes };

    // Rebuild the full table (this recreates trash icons and hover events)
    buildTable();

    // DO NOT overwrite th.textContent — it destroys the trash icon!
    // Instead, update only the name span inside the wrapper
    Object.keys(headerSnapshot).forEach(c => {
        if (c == 1) return; // skip Tasks column
        const th = document.querySelector(`thead th[data-c="${c}"]`);
        if (th) {
            const nameSpan = th.querySelector("span"); // first span is the name
            if (nameSpan) {
                //const fullName = headerSnapshot[c] || defaultFieldName(Number(c));
                const fullName = headerSnapshot[c] || "Column Field";
                const firstLine = fullName.split('\n')[0];
                nameSpan.textContent = fullName.includes('\n') ? firstLine + "..." : firstLine;
                th.title = fullName;
            }
        }
        columnHeaders[c] = headerSnapshot[c];
    });

    columnTypes = typesSnapshot;

    // Restore cell data
    Object.keys(dataSnapshot).forEach(id => {
        const cellEl = document.getElementById(id);
        if (cellEl && parseInt(cellEl.dataset.c) !== 1) {
            data[id] = dataSnapshot[id];
            renderCellContent(cellEl, parseInt(cellEl.dataset.c));
        }
    });
    refreshAllActivityIcons();
    recalcAll();
}

/* ------------------------------------------------------------
   ICON UPDATE FUNCTIONS
------------------------------------------------------------ */
// Better unified version
function updateTaskActivityIcons(row) {
    const cell = document.querySelector(`.cell[data-r="${row}"][data-c="1"]`);
    if (!cell) return;

    const commentCount  = rowComments[row]  || 0;
    const attachCount   = rowAttachments[row] || 0;
    const reminderCount = rowReminders[row]  || 0;

    // Check if this row has any non-empty FILE-type cells (for attachment highlight)
    let hasFileInRow = false;
    for (let c = 1; c <= COLS; c++) {
        const cfg = columnTypes[c] || { type: "text" };
        if (cfg.type !== "file") continue;
        const id = cellId(row, c);
        const raw = data[id]?.raw;
        if (raw && raw.toString().trim() !== "") {
            hasFileInRow = true;
            break;
        }
    }

    const hasComment    = commentCount > 0;
    // Paperclip highlights if DB attachments OR file-type cell values exist
    const hasAttach     = (attachCount > 0) || hasFileInRow;
    // Bell only depends on reminders now
    const hasReminder   = reminderCount > 0;

    const hasAnyActivity = hasComment || hasAttach || hasReminder;

    // Icons classes
    cell.querySelector(".comment-icon")?.classList.toggle("has-comment", hasComment);
    cell.querySelector(".attach-icon")?.classList.toggle("has-attachment", hasAttach);
    cell.querySelector(".bell-icon")?.classList.toggle("has-reminder", hasReminder);

    // Force visibility of the whole actions container if there's activity
    const actions = cell.querySelector(".task-actions");
    if (actions) {
        actions.classList.toggle("has-activity", hasAnyActivity);
    }

    // Extra safety: force opacity 1 if has activity (bypasses some browser quirks)
    if (hasAnyActivity && actions) {
        actions.style.opacity = "1";
        actions.style.pointerEvents = "auto";
    }
}

// Call this instead of the two separate functions
function refreshAllActivityIcons() {
    for (let r = 1; r <= ROWS; r++) {
        updateTaskActivityIcons(r);
    }
}

// Rename your old functions to this one
// You can remove refreshAllRowIcons() and refreshAllReminderIcons()
// and replace all calls with refreshAllActivityIcons()

function addColumnAfter(col) {
    // Increase total columns
    COLS++;

    // Shift columns right
    for (let c = COLS; c > col + 1; c--) {
        columnHeaders[c] = columnHeaders[c - 1];
        columnTypes[c] = columnTypes[c - 1];

        for (let r = 1; r <= ROWS; r++) {
            const oldId = cellId(r, c - 1);
            const newId = cellId(r, c);
            if (data[oldId]) {
                data[newId] = data[oldId];
                delete data[oldId];
            }
        }
    }

    // Initialize new column
    const newCol = col + 1;
    //columnHeaders[newCol] = colName(newCol - 1);
    //columnHeaders[newCol] = defaultFieldName(newCol);
    columnHeaders[newCol] = "Column Field";
    columnTypes[newCol] = { type: "text" };

    rebuildPreserveData();

    // Open Column Settings modal immediately
    setTimeout(() => {
        currentColumnForType = newCol;
        openColumnTypeModal(newCol);
    }, 50);
}

/* ------------------------------------------------------------
   FETCH COUNTS FROM BACKEND ON LOAD
------------------------------------------------------------ */
// Replace or update this function
function loadCountsAndRefreshIcons() {
    if (activeSheetId <= 0) {
        refreshAllActivityIcons();   // ← change here
        return;
    }

    fetch(`counts.php?sheet_id=${activeSheetId}`)
        .then(res => res.json())
        .then(result => {
            Object.assign(rowComments,    result.comments    || {});
            Object.assign(rowAttachments, result.attachments || {});
            Object.assign(rowReminders,   result.reminders   || {});

            refreshAllActivityIcons();   // ← change here
        })
        .catch(() => {
            refreshAllActivityIcons();   // ← change here (fallback)
        });
}

/* ------------------------------------------------------------
   COLUMN TYPE MODAL FUNCTIONS
------------------------------------------------------------ */
function openColumnTypeModal(col) {
    currentColumnForType = col;

    // ────────────────────────────────────────────────────────────────
    // Decide what to show in the name input field
    // ────────────────────────────────────────────────────────────────
    let displayName = "";

    // When adding a NEW column → force empty name field
    if (isAddingNewColumn) {
        displayName = "";
    }
    // When editing an EXISTING column → show real name if it exists and is meaningful
    else if (columnHeaders[col] && columnHeaders[col].trim() !== "") {
        // Avoid showing auto-generated / placeholder names
        if (!columnHeaders[col].startsWith("Column Field") &&
            !columnHeaders[col].startsWith("ColumnField_") &&
            columnHeaders[col] !== defaultFieldName(col)) {
            displayName = columnHeaders[col];
        }
        // else → leave empty (treat placeholder as not user-set)
    }

    // Set the input value
    const nameInput = document.getElementById("modalColName");
    nameInput.value = displayName;

    // Good UX: focus and select the name field immediately
    nameInput.focus();
    nameInput.select();

    // ────────────────────────────────────────────────────────────────
    // Load current column type & dropdown options (if applicable)
    // ────────────────────────────────────────────────────────────────
    // For NEW columns: start with default "text" type
    const config = isAddingNewColumn 
        ? { type: "text", options: [] } 
        : (columnTypes[col] || { type: "text", options: [] });

    const typeSelect = document.getElementById("modalColType");
    typeSelect.value = config.type;

    const dropdownDiv     = document.getElementById("dropdownOptions");
    const dropdownTextarea = document.getElementById("dropdownValues");

    if (config.type === "dropdown") {
        dropdownDiv.style.display = "block";
        dropdownTextarea.value = (config.options || []).join(", ");
    } else {
        dropdownDiv.style.display = "none";
        dropdownTextarea.value = "";
    }

    // Update dropdown visibility when type changes
    typeSelect.onchange = function() {
        dropdownDiv.style.display = (this.value === "dropdown") ? "block" : "none";
    };

    // Show the modal
    document.getElementById("modalBackdrop").classList.add("open");
    document.getElementById("columnTypeModal").classList.add("open");
}

function closeColumnTypeModal() {
    document.getElementById("modalBackdrop").classList.remove("open");
    document.getElementById("columnTypeModal").classList.remove("open");
}

function applyColumnType() {
    if (!currentColumnForType) return;

    const inputEl = document.getElementById("modalColName");
    const newName = inputEl.value.trim();

    if (!newName) {
        // ... your validation ...
        return;
    }

    // ────────────────────────────────────────────────
    // CASE: Adding a NEW column after a specific position
    // ────────────────────────────────────────────────
    if (isAddingNewColumn && insertAfterColumn !== null) {
        const insertPos = insertAfterColumn + 1;

        // Increase total columns
        COLS++;

        // Shift columns to the right starting from insertPos
        for (let c = COLS; c > insertPos; c--) {
            columnHeaders[c] = columnHeaders[c - 1];
            columnTypes[c] = columnTypes[c - 1];

            for (let r = 1; r <= ROWS; r++) {
                const oldId = cellId(r, c - 1);
                const newId = cellId(r, c);
                if (data[oldId]) {
                    data[newId] = data[oldId];
                    delete data[oldId];
                }
            }
        }

        // Now initialize the newly inserted column
        columnHeaders[insertPos] = newName;
        columnTypes[insertPos] = { type: "text" }; // default – will be updated below

        // The modal is already "editing" this position
        currentColumnForType = insertPos;

        // Reset flags
        isAddingNewColumn = false;
        insertAfterColumn = null;

        // Rebuild table (now includes the new column in correct place)
        rebuildPreserveData();
    }

    // ────────────────────────────────────────────────
    // Apply name & type (for both new and existing columns)
    // ────────────────────────────────────────────────
    columnHeaders[currentColumnForType] = newName;

    // Update header display
    const th = document.querySelector(`thead th[data-c="${currentColumnForType}"]`);
    if (th) {
        const nameSpan = th.querySelector("span");
        if (nameSpan) {
            const firstLine = newName.split('\n')[0];
            nameSpan.textContent = newName.includes('\n') ? firstLine + "..." : firstLine;
            th.title = newName;
        }
    }

    // Apply selected type
    const selectedType = document.getElementById("modalColType").value;

    if (selectedType === "dropdown") {
        const raw = document.getElementById("dropdownValues").value;
        const options = raw.split(/[\n,]+/)
                          .map(v => v.trim())
                          .filter(v => v.length > 0);

        columnTypes[currentColumnForType] = {
            type: "dropdown",
            options: options.length > 0 ? options : ["Option 1", "Option 2"]
        };
    } else {
        columnTypes[currentColumnForType] = { type: selectedType };
    }

    // Re-render cells in this column
    for (let r = 1; r <= ROWS; r++) {
        const cell = document.getElementById(cellId(r, currentColumnForType));
        if (cell) renderCellContent(cell, currentColumnForType);
    }

    closeColumnTypeModal();

    // Optional success message
    Swal.fire({
        icon: 'success',
        title: 'Done',
        text: isAddingNewColumn === false ? 'Column added' : 'Column updated',
        timer: 1400,
        showConfirmButton: false
    });
}

/* ------------------------------------------------------------
   EVENT HANDLERS
------------------------------------------------------------ */
function onEdit(e) {
    const cell = e.target.closest(".cell");
    if (!cell || cell.dataset.c == 1) return;

    hasUnsavedChanges = true; // ← ADD THIS

    const id = cell.id;
    const col = parseInt(cell.dataset.c);
    const config = columnTypes[col] || { type: "text" };
    const type = config.type;

    let value;
    if (type === "checkbox") value = e.target.checked;
    else if (["number", "date", "dropdown"].includes(type)) value = e.target.value;
    else value = cell.textContent;

    data[id] = { raw: value.toString() };
    recalcAll();
}

function onFocus(e) {
    document.querySelectorAll(".cell").forEach(c => c.classList.remove("selected"));
    const cell = e.target.closest(".cell") || e.target;
    if (cell.classList.contains("cell")) cell.classList.add("selected");
}

function onKeyDown(e) {
    const cell = e.target.closest(".cell");
    if (!cell) return;

    const r = parseInt(cell.dataset.r);
    const c = parseInt(cell.dataset.c);

    let nr = r, nc = c;
    if (e.key === "Enter") { e.preventDefault(); nr++; }
    if (e.key === "ArrowDown") { e.preventDefault(); nr++; }
    if (e.key === "ArrowUp") { e.preventDefault(); nr--; }
    if (e.key === "ArrowRight") { e.preventDefault(); nc++; }
    if (e.key === "ArrowLeft") { e.preventDefault(); nc--; }

    nr = Math.max(1, Math.min(nr, ROWS));
    nc = Math.max(1, Math.min(nc, COLS));

    const next = document.getElementById(cellId(nr, nc));
    if (next) {
        next.focus();
        const input = next.querySelector("input, select");
        if (input) input.focus();
    }
}

/* ------------------------------------------------------------
   FORMULA CALC
------------------------------------------------------------ */
function isNumeric(v) { return !isNaN(v) && v !== "" && v !== null; }

function evalCell(id) {
    const entry = data[id];
    const raw = entry?.raw || document.getElementById(id)?.textContent || "";

    if (!raw.startsWith("=")) return isNumeric(raw) ? Number(raw) : raw;

    let expr = raw.substring(1);
    expr = expr.replace(/([A-Z]+\d+)/g, (match) => {
        const v = evalCell(match);
        return typeof v === "number" ? v : 0;
    });

    try {
        return new Function("return (" + expr + ")")();
    } catch {
        return "#ERR";
    }
}

function recalcAll() {
    for (let r = 1; r <= ROWS; r++)
        for (let c = 1; c <= COLS; c++) {
            const id = cellId(r, c);
            if (data[id]?.raw?.startsWith("=")) {
                const cellEl = document.getElementById(id);
                if (cellEl) cellEl.textContent = evalCell(id);
            }
        }
}

/* ------------------------------------------------------------
   BUTTON ACTIONS
------------------------------------------------------------ */
//document.getElementById("add-row").onclick = () => { ROWS++; rebuildPreserveData(); };
//document.getElementById("add-col").onclick = () => { COLS++; rebuildPreserveData(); };

document.getElementById("save-db").onclick = async () => {
    // No more prompt!
    // Name is already in DB — we don't change it here anymore

    const payload = {
        id: activeSheetId,           // ← always send ID → backend will UPDATE
        rows: ROWS,
        cols: COLS,
        headers: [],
        columnTypes: columnTypes,
        cells: {}
    };

    // Collect visible header names (from UI)
    document.querySelectorAll("thead th[data-c]").forEach(th => {
        if (th.dataset.c && th.dataset.c != "1") {
            const nameSpan = th.querySelector("span");
            payload.headers.push(nameSpan ? nameSpan.textContent.trim() : "");
        }
    });

    // Collect cell data (only non-empty)
    document.querySelectorAll(".cell").forEach(cell => {
        if (cell.dataset.c == "1") return; // skip Tasks
        const raw = data[cell.id]?.raw?.trim();
        if (raw) {
            payload.cells[cell.id] = raw;
        }
    });

    try {
        const res = await fetch("save.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const out = await res.json();

        if (out.success) {
            hasUnsavedChanges = false;
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Changes saved successfully',
                timer: 1600,
                showConfirmButton: false
            });

            // Optional: stay on page instead of redirect
            // window.location.href = "dashboard-sheets.php";
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Save failed',
                text: out.error || "Server error – please try again",
                confirmButtonText: 'OK'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Network error',
            text: "Could not reach the server."
        });
    }
};
window.addEventListener("beforeunload", function (e) {
    if (!hasUnsavedChanges) return;

    e.preventDefault();
    e.returnValue = ""; // Required for modern browsers
});
/* ------------------------------------------------------------
   INITIAL BUILD & LOAD
------------------------------------------------------------ */
buildTable();

document.addEventListener("DOMContentLoaded", () => {

    document.getElementById("export-csv").onclick = () => {
    const wsData = [];

    // ────────────────────────────────────────────────
    // 1. Collect HEADER ROW — only non-file columns
    // ────────────────────────────────────────────────
    const headerRow = [];
    const includedColumns = []; // remember which column indexes we actually export

    for (let c = 2; c <= COLS; c++) {           // start from 2 = skip Tasks
        const config = columnTypes[c] || { type: "text" };
        if (config.type === "file") continue;   // ← skip file columns

        const th = document.querySelector(`thead th[data-c="${c}"] span`);
        const headerText = th ? th.textContent.trim() : `Col ${c}`;
        headerRow.push(headerText);
        includedColumns.push(c);                // track real column index
    }

    if (headerRow.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Nothing to export',
            text: 'No non-file columns found.',
        });
        return;
    }

    wsData.push(headerRow);

    // ────────────────────────────────────────────────
    // 2. Collect DATA ROWS — only for included columns
    // ────────────────────────────────────────────────
    for (let r = 1; r <= ROWS; r++) {
        const row = [];

        for (const c of includedColumns) {      // only columns we decided to keep
            const id = cellId(r, c);
            const type = columnTypes[c]?.type || "text";
            let value = "";

            if (type === "dropdown") {
                value = data[id]?.raw || "";
            } else {
                value = data[id]?.raw ??
                        document.getElementById(id)?.textContent ??
                        "";
            }

            row.push(value);
        }

        wsData.push(row);
    }

    // ────────────────────────────────────────────────
    // 3. Create workbook
    // ────────────────────────────────────────────────
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    // ────────────────────────────────────────────────
    // 4. Style header row (bold)
    // ────────────────────────────────────────────────
    const range = XLSX.utils.decode_range(ws["!ref"]);
    for (let c = range.s.c; c <= range.e.c; c++) {
        const cellRef = XLSX.utils.encode_cell({ r: 0, c });
        if (ws[cellRef]) {
            ws[cellRef].s = { font: { bold: true } };
        }
    }

    // Optional: auto-size columns roughly
    ws["!cols"] = headerRow.map(h => ({
        wch: Math.max(10, (h || "").length + 4)
    }));

    XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

    // ────────────────────────────────────────────────
    // 5. Download
    // ────────────────────────────────────────────────
    const sheetName = document.querySelector("h6.fw-semibold")?.textContent?.trim() || "Exported_Sheet";
    const safeName = "sheets";
    XLSX.writeFile(wb, `${safeName}.xlsx`);
};

    document.getElementById("clear").onclick = () => {
        //if (!confirm("Clear all data?")) return;
        Swal.fire({
            title: 'Are you sure?',
            text: "This will clear all data in the sheet!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, clear it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // your clear logic here...
                for (let r = 1; r <= ROWS; r++) {
                    for (let c = 1; c <= COLS; c++) {
                        const id = cellId(r, c);
                        data[id] = { raw: "" };
                        const el = document.getElementById(id);
                        if (el) {
                            if (c === 1) el.querySelector(".task-text").textContent = "";
                            else renderCellContent(el, c);
                        }
                    }
                }
                Swal.fire(
                    'Cleared!',
                    'All data has been removed.',
                    'success'
                );
            }
        });        
        Object.keys(rowComments).forEach(k => delete rowComments[k]);
        Object.keys(rowAttachments).forEach(k => delete rowAttachments[k]);
        Object.keys(rowReminders).forEach(k => delete rowReminders[k]);  
        refreshAllActivityIcons(); 
    };

    document.getElementById("load-db").onclick = async () => {
        try {
            const listRes = await fetch("list.php");
            if (!listRes.ok) throw new Error("list.php failed");
            const sheets = await listRes.json();

            if (!Array.isArray(sheets) || sheets.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No sheets found',
                    text: 'There are no saved sheets to load.',
                });
                return;
            }

            const choices = sheets.map(s => `${s.id}: ${s.name || 'Untitled'} (${s.updated_at || '—'})`).join('\n');
            const idStr = prompt("Saved Sheets:\n" + choices + "\n\nEnter ID to load:");

            const id = parseInt(idStr);
            if (!id || isNaN(id)) return;

            const loadRes = await fetch(`load.php?id=${id}`);
            if (!loadRes.ok) throw new Error(`load.php status ${loadRes.status}`);
            
            const payload = await loadRes.json();

            if (!payload.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Load failed',
                    text: payload.error || "Unknown error from server",
                });
                return;
            }

            const sheet = payload.data;

            // Migrate old cell keys if necessary
            const migratedCells = {};
            Object.keys(sheet.cells || {}).forEach(key => {
                const match = key.match(/^r(\d+)c(\d+)$/i);
                if (match) {
                    const r = parseInt(match[1]);
                    const c = parseInt(match[2]);
                    const newKey = colName(c) + r;
                    migratedCells[newKey] = sheet.cells[key];
                } else {
                    migratedCells[key] = sheet.cells[key];
                }
            });
            sheet.cells = migratedCells;

            // ─── Apply values ───
            ROWS = sheet.rows || 10;
            COLS = sheet.cols || 4;
            columnTypes = sheet.columnTypes || {};
            activeSheetId = id;

            // 1. Build table structure first (creates elements with correct IDs)
            buildTable();

            // 2. Update headers safely (only the name span)
            document.querySelectorAll("thead th[data-c]").forEach(th => {
                const c = parseInt(th.dataset.c);
                if (c === 1) return; // skip Tasks

                const idx = c - 2;
                const headerValue = sheet.headers?.[idx] || "";

                const nameSpan = th.querySelector("span"); // your first <span> is the name
                if (nameSpan) {
                    nameSpan.textContent = headerValue;
                    th.title = headerValue;
                }

                // Optional: re-attach events if needed (usually not necessary)
            });

            // 3. Fill cell data
            Object.entries(sheet.cells || {}).forEach(([key, value]) => {
                const cellEl = document.getElementById(key);
                if (cellEl && cellEl.dataset.c !== "1") { // skip task column
                    data[key] = { raw: value };
                    renderCellContent(cellEl, parseInt(cellEl.dataset.c));
                }
            });

            recalcAll();
            loadCountsAndRefreshIcons();

            Swal.fire({
                icon: 'success',
                title: 'Loaded!',
                text: `Sheet "${payload.name || 'Untitled'}" loaded successfully`,
                timer: 1800,
                showConfirmButton: false
            });

        } catch (err) {
            console.error("Load error:", err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: "Failed to load sheet: " + (err.message || "Network/server issue")
            });
        }
    };

    // PHP direct load
    <?php if ($sheetData): ?>
    Object.keys(loaded.cells).forEach(id => {
        const cell = document.getElementById(id);
        if (cell && cell.dataset.c != 1) {
            data[id] = { raw: loaded.cells[id] };
            renderCellContent(cell, parseInt(cell.dataset.c));
        }
    });
    recalcAll();
    loadCountsAndRefreshIcons(); // Critical: shows existing comments/attachments
    <?php endif; ?>

    // Add this inside DOMContentLoaded
    document.getElementById("commentText").addEventListener("keydown", function(e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                saveComment();
            }
        });
});

/* ------------------------------------------------------------
   COMMENTS & ATTACHMENTS
------------------------------------------------------------ */
function openComments(row) {
    activeRow = row;
    document.getElementById("commentPanel").classList.add("open");
    loadComments();
}

function closeComments() {
    document.getElementById("commentPanel").classList.remove("open");
}

function scrollCommentListToBottom() {
    const list = document.getElementById("commentList");
    if (list) {
        list.scrollTop = list.scrollHeight;
    }
}

async function loadComments() {
    const res = await fetch(`comments.php?sheet_id=${activeSheetId}&row=${activeRow}`);
    const comments = await res.json();

    const list = document.getElementById("commentList");
    list.innerHTML = "";

    let totalComments = comments.length;
    comments.forEach(c => totalComments += c.replies ? c.replies.length : 0);
    rowComments[activeRow] = totalComments;
    updateTaskActivityIcons(activeRow);

    comments.forEach(c => {
        const div = document.createElement("div");
        div.className = "comment";
        div.innerHTML = `
            <div>${c.comment}</div>
            <small>${c.created_at} • 
                <button class="btn btn-link btn-sm p-0 text-primary" onclick="showReplyBox(${c.id}, this)">Reply</button>
            </small>
        `;
        list.appendChild(div);

        // Render replies (indented)
        if (c.replies && c.replies.length > 0) {
            c.replies.forEach(r => {
                const rd = document.createElement("div");
                rd.className = "comment reply";
                rd.innerHTML = `
                    <div>${r.comment}</div>
                    <small>${r.created_at}</small>
                `;
                list.appendChild(rd);
            });
        }
    });

    scrollCommentListToBottom();
}

function saveComment() {
    const text = document.getElementById("commentText").value.trim();
    if (!text) return;

    fetch("save_comment.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            sheet_id: activeSheetId,
            sheet_row: activeRow,
            comment: text
        })
    }).then(() => {
        document.getElementById("commentText").value = "";
        rowComments[activeRow] = (rowComments[activeRow] || 0) + 1;
        updateTaskActivityIcons(activeRow);    // ← fixed
        loadComments();
    });
}

// Show reply box below a specific comment
function showReplyBox(commentId, parentElement) {
    // Remove any existing reply boxes first
    document.querySelectorAll('.reply-box').forEach(box => box.remove());

    const replyBox = document.createElement('div');
    replyBox.className = 'reply-box mt-2';
    replyBox.innerHTML = `
        <textarea class="form-control form-control-sm" rows="2" 
                  placeholder="Write your reply..." 
                  id="replyText_${commentId}"></textarea>
        <div class="mt-1 text-end">
            <button class="btn btn-sm btn-secondary me-2" 
                    onclick="cancelReply('${commentId}')">Cancel</button>
            <button class="btn btn-sm lufera-bg text-white" 
                    onclick="saveReply(${commentId})">Send Reply</button>
        </div>
    `;

    const commentDiv = parentElement.closest('.comment');
    if (commentDiv) {
        commentDiv.appendChild(replyBox);

        const textareaId = `replyText_${commentId}`;

        // ─── Attach smart Enter behavior ───
        makeTextareaSmartSend(textareaId, () => {
            saveReply(commentId);
        });

        // Auto-focus
        document.getElementById(textareaId).focus();
    }
}

// Cancel reply
function cancelReply(commentId) {
    // Find the reply box by a more reliable way
    const replyBox = document.querySelector(`#replyText_${commentId}`)?.closest('.reply-box');
    if (replyBox) {
        replyBox.remove();
    }
}

// Save inline reply
function saveReply(parentId) {
    const textarea = document.getElementById(`replyText_${parentId}`);
    if (!textarea) return;

    const text = textarea.value.trim();

    // Remove any previous error message
    const existingError = textarea.parentElement.querySelector('.reply-error');
    if (existingError) existingError.remove();

    // Reset textarea border
    textarea.style.borderColor = '';

    if (!text) {
        // Create warning message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'reply-error text-danger small mt-1 mb-2';
        errorDiv.textContent = 'Please write something before sending.';
        errorDiv.style.fontSize = '0.85rem';

        // Insert ABOVE the buttons (after textarea, before the button container)
        const buttonContainer = textarea.nextElementSibling; // the <div class="mt-2 text-end">
        if (buttonContainer) {
            buttonContainer.parentElement.insertBefore(errorDiv, buttonContainer);
        } else {
            // Fallback: just append if structure changes
            textarea.parentElement.appendChild(errorDiv);
        }

        // Highlight textarea border in red
        textarea.style.borderColor = '#dc3545';
        textarea.focus();

        // Auto-remove after 4 seconds
        setTimeout(() => {
            if (errorDiv.parentElement) errorDiv.remove();
            textarea.style.borderColor = '';
        }, 40000000000);

        // Remove error when user starts typing
        const removeErrorOnInput = () => {
            if (errorDiv.parentElement) errorDiv.remove();
            textarea.style.borderColor = '';
            textarea.removeEventListener('input', removeErrorOnInput);
        };
        textarea.addEventListener('input', removeErrorOnInput);

        return;
    }

    // Proceed with save if text exists
    fetch("save_comment.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            sheet_id: activeSheetId,
            sheet_row: activeRow,
            parent_id: parentId,
            comment: text
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            rowComments[activeRow] = (rowComments[activeRow] || 0) + 1;
            updateTaskActivityIcons(activeRow);
            loadComments(); // refresh the list
        } else {
            alert(result.error || "Failed to save reply");
        }
    })
    .catch(err => {
        console.error(err);
        alert("Network error");
    });
}

function openAttachments(row) {
    activeAttachRow = row;
    document.getElementById("attachmentPanel").classList.add("open");
    loadAttachments();
}

function closeAttachments() {
    document.getElementById("attachmentPanel").classList.remove("open");
}

async function loadAttachments() {
    const res = await fetch(`attachments.php?sheet_id=${activeSheetId}&row=${activeAttachRow}`);
    const files = await res.json();
    rowAttachments[activeAttachRow] = files.length;
    updateTaskActivityIcons(activeAttachRow);

    const list = document.getElementById("attachmentList");
    list.innerHTML = "";

    // Helper to render one attachment row
    // meta object: { rowLabel: string, createdLabel?: string }
    function renderAttachmentItem(filePath, displayName, meta, fileSize = null, createdAt = null, extra = {}) {
    if (!filePath) return;

    const div = document.createElement("div");
    div.className = "comment";
    div.style.cursor = "pointer";

    const ext = (displayName || filePath).split(".").pop().toLowerCase();
    const isImage = ["jpg","jpeg","png","gif","webp"].includes(ext);

    div.innerHTML = `
        <div style="display:inline-flex; align-items:center; gap:12px;">
            ${isImage ? `<img src="${filePath}" style="width:100px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;" />` : ""}
        </div>
        <div class="d-inline-block ms-3">
            <span class="file-name">${displayName}</span>
            ${meta?.rowLabel    ? `<small class="attachment-row d-block bg-success text-white px-3 py-1 mt-1">${meta.rowLabel}</small>` : ""}
            ${meta?.createdLabel ? `<small class="d-block text-muted">${meta.createdLabel}</small>` : ""}
        </div>`;

    div.onclick = () => {
        openPreviewModal(
            filePath,
            displayName,
            fileSize,           // ← pass real size if you have it
            createdAt,          // ← pass real timestamp if available
            extra               // ← optional {row, column, source...}
        );
    };

    list.appendChild(div);
}

    // 1) Attachments from sheet_attachments table
    // 1) Attachments from sheet_attachments table
    files.forEach(f => {
        const rowInfo = f.sheet_row ? `Row ${f.sheet_row}` : `Row ${activeAttachRow}`;

        renderAttachmentItem(
            f.file_path,
            f.original_name,
            { 
                rowLabel: rowInfo, 
                createdLabel: f.created_at ? new Date(f.created_at).toLocaleString() : "—"                            
            },
            f.file_size,           // file size
            f.created_at,          // upload date
            f.uploaded_by,         // ←★★★ USE THIS ★★★ real name!
            { source: "Uploaded attachment" }
        );
    });

    // 2) File-type cells from the current row in the main sheet
    for (let c = 1; c <= COLS; c++) {
        const config = columnTypes[c] || { type: "text" };
        if (config.type !== "file") continue;

        const id = cellId(activeAttachRow, c);   // e.g. B3
        const cell = data[id];
        const rawValue = cell && cell.raw ? cell.raw.toString().trim() : "";
        if (!rawValue) continue;

        // Derive a nice display name from the path or URL
        const parts = rawValue.split(/[\\/]/);
        const displayName = parts[parts.length - 1] || rawValue;

        // ...
const colLabel = columnHeaders[c] || colName(c);
renderAttachmentItem(
    rawValue,
    displayName,
    {
        rowLabel: `Row ${activeAttachRow}`,
        createdLabel: `From column: ${colLabel}`
    },
    null,
    null,   // no real size known here
    null,   // no upload date known
    { source: "File-type column value" }
);

        renderAttachmentItem(rawValue, displayName, meta);
    }
}

async function uploadAttachment() {
    const fileInput = document.getElementById("attachFile");
    if (!fileInput.files.length) return;

    const formData = new FormData();
    formData.append("file", fileInput.files[0]);
    formData.append("sheet_id", activeSheetId);
    formData.append("sheet_row", activeAttachRow);

    const res = await fetch("upload_attachment.php", {
        method: "POST",
        body: formData
    });

    const out = await res.json();
    if (out.success) {
        fileInput.value = "";
        rowAttachments[activeAttachRow] = (rowAttachments[activeAttachRow] || 0) + 1;
        updateTaskActivityIcons(activeAttachRow);
        loadAttachments();
    } else {
        alert(out.error || "Upload failed");
    }
}

function addRowAfter(row) {
    ROWS++;

    // Shift rows down
    for (let r = ROWS; r > row + 1; r--) {
        for (let c = 1; c <= COLS; c++) {
            const oldId = cellId(r - 1, c);
            const newId = cellId(r, c);

            if (data[oldId]) {
                data[newId] = data[oldId];
                delete data[oldId];
            } else {
                delete data[newId];
            }
        }

        // Move comments & attachments
        rowComments[r] = rowComments[r - 1] || 0;
        rowAttachments[r] = rowAttachments[r - 1] || 0;
    }

    // Initialize new row
    for (let c = 1; c <= COLS; c++) {
        delete data[cellId(row + 1, c)];
    }
    rowComments[row + 1] = 0;
    rowAttachments[row + 1] = 0;

    rebuildPreserveData();
    refreshAllActivityIcons();
}

function deleteRow(row) {
    if (ROWS <= 1) {
        Swal.fire({
            icon: 'error',
            title: 'Cannot delete',
            text: 'You cannot delete the last row.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

    Swal.fire({
        title: `Delete row ${row}?`,
        text: "This action cannot be undone. All data in this row will be lost.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            // ────────────────────────────────────────────────
            // ONLY HERE — perform the actual deletion
            for (let c = 1; c <= COLS; c++) {
                const id = cellId(row, c);
                delete data[id];
            }

            // Remove comment/attachment tracking
            delete rowComments[row];
            delete rowAttachments[row];

            ROWS--;
            rebuildPreserveData();

            // Success feedback
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: `Row ${row} has been removed.`,
                timer: 1600,
                showConfirmButton: false
            });
            // ────────────────────────────────────────────────
        }
        // else → user clicked cancel → do nothing
    });
}

function deleteColumn(col) {
    if (COLS <= 1) {
        Swal.fire({
            icon: 'error',
            title: 'Cannot delete',
            text: 'At least one data column must remain.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

    if (col === 1) {
        Swal.fire({
            icon: 'error',
            title: 'Protected',
            text: 'Cannot delete the Tasks column.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

    const colNameDisplay = columnHeaders[col] || defaultFieldName(col - 1);

    Swal.fire({
        title: `Delete column "${colNameDisplay}"?`,
        text: "All data in this column will be permanently deleted. This cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete column',
        cancelButtonText: 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // ────────────────────────────────────────────────
            // ONLY HERE — perform the actual deletion

            // Remove data
            for (let r = 1; r <= ROWS; r++) {
                const id = cellId(r, col);
                delete data[id];
            }

            // Remove config
            delete columnTypes[col];
            delete columnHeaders[col];

            // Shift columns left
            for (let c = col; c < COLS; c++) {
                columnTypes[c] = columnTypes[c + 1] || { type: "text" };
                columnHeaders[c] = columnHeaders[c + 1];

                for (let r = 1; r <= ROWS; r++) {
                    const oldId = cellId(r, c + 1);
                    const newId = cellId(r, c);
                    if (data[oldId]) {
                        data[newId] = data[oldId];
                        delete data[oldId];
                    } else {
                        delete data[newId];
                    }
                }
            }

            delete columnTypes[COLS];
            delete columnHeaders[COLS];

            COLS--;
            rebuildPreserveData();

            // Success feedback
            Swal.fire({ 
                icon: 'success',
                title: 'Deleted!',
                text: `Column "${colNameDisplay}" has been removed.`,
                timer: 1800,
                showConfirmButton: false
            });
            // ────────────────────────────────────────────────
        }
        // else → user canceled → do nothing
    });
}

// Dropdown toggle logic
document.querySelectorAll(".dropdown-btn").forEach(btn => {
    btn.addEventListener("click", e => {
        e.stopPropagation();

        // Close others
        document.querySelectorAll(".dropdown").forEach(d => {
            if (d !== btn.parentElement) d.classList.remove("open");
        });

        btn.parentElement.classList.toggle("open");
    });
});

// Close dropdowns when clicking outside
document.addEventListener("click", () => {
    document.querySelectorAll(".dropdown").forEach(d => d.classList.remove("open"));
});

let currentReminderRow = null;

function openReminderModal(row) {
    currentReminderRow = row;
    document.getElementById("reminderRow").textContent = row;

    // Reset fields by default (new reminder entry)
    const dateInput = document.getElementById("reminderDate");
    const msgInput  = document.getElementById("reminderMessage");
    const historyEl = document.getElementById("reminderHistory");

    if (dateInput) dateInput.value = "";
    if (msgInput)  msgInput.value  = "";
    if (historyEl) {
        historyEl.innerHTML = "";
        historyEl.style.display = "none";
    }

    // Load ALL existing reminders for this row and list them above the fields
    if (activeSheetId && row) {
        fetch(`get_reminder.php?sheet_id=${activeSheetId}&row=${row}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.success && Array.isArray(data.reminders) && data.reminders.length > 0 && historyEl) {
                    // Build simple list of previous reminders (latest first)
                    const items = data.reminders.map(r => {
                        const safeDate = r.display_at || r.remind_at || "";
                        const safeMsg  = r.message || "";
                        return `<div style="padding:6px 4px; border-bottom:1px solid #f3f4f6;">
                                    <div style="font-size:12px; color:#6b7280;">${safeDate}</div>
                                    <div style="font-size:13px;">${safeMsg}</div>
                                </div>`;
                    }).join("");

                    historyEl.innerHTML = `<div style="font-weight:600; margin-bottom:4px;">Existing reminders</div>${items}`;
                    historyEl.style.display = "block";
                }
            })
            .catch(err => {
                console.error("Failed to load reminder", err);
            });
    }

    document.getElementById("modalBackdropReminder").style.display = "block";
    document.getElementById("reminderModal").style.display = "block";
}

function closeReminderModal() {
    document.getElementById("modalBackdropReminder").style.display = "none";
    document.getElementById("reminderModal").style.display = "none";
    currentReminderRow = null;
}

async function saveReminder() {
    const dateStr = document.getElementById("reminderDate").value;
    const msg = document.getElementById("reminderMessage").value.trim();

    if (!dateStr) {
        alert("Please select a reminder date.");
        return;
    }
    if (!msg) {
        alert("Please enter a reminder message.");
        return;
    }

    try {
        const res = await fetch("save_reminder.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                sheet_id: activeSheetId,
                sheet_row: currentReminderRow,
                remind_at: dateStr,
                message: msg
            })
        });

        const result = await res.json();
        if (result.success) {
            // Visual feedback
            //const bell = document.querySelector(`.cell[data-r="${currentReminderRow}"][data-c="1"] .bell-icon`);
            rowReminders[currentReminderRow] = (rowReminders[currentReminderRow] || 0) + 1;
            updateTaskActivityIcons(currentReminderRow);

            Swal.fire({
                icon: 'success',
                title: 'Reminder Set',
                text: `Will remind you on ${dateStr}`,
                timer: 1800,
                showConfirmButton: false
            });

            closeReminderModal();
        } else {
            alert(result.error || "Could not save reminder");
        }
    } catch (err) {
        console.error(err);
        alert("Network error");
    }
}
</script>

<div id="commentPanel" class="comment-panel">
    <div class="comment-header">
        <strong>Comments</strong>
        <button onclick="closeComments()">✖</button>
    </div>

    <div id="commentList" class="comment-list"></div>

    <div class="comment-input">
        <textarea id="commentText" placeholder="Write a comment..."></textarea>
        <button class="btn btn-secondary float-end mt-10" onclick="saveComment()">Send</button>
    </div>
</div>

<div id="attachmentPanel" class="comment-panel">
    <div class="comment-header">
        <h6 class="d-inline mb-0">Task Attachments</h6>
        <h6 class="mb-0 cursor-pointer" onclick="closeAttachments()">&times;</h6>
    </div>

    <div id="attachmentList" class="comment-list"></div>

    <div class="comment-input">
        <input type="file" id="attachFile" />
        <button class="btn btn-secondary mt-10" onclick="uploadAttachment()">Upload</button>
    </div>
</div>
<!-- Add these just before closing </body> (after the two existing panels) -->
<div id="modalBackdrop" onclick="closeColumnTypeModal()"></div>

<div id="columnTypeModal">
    <h6>Column Settings</h6>
    
    <div style="margin-bottom: 16px;">
        <label style="display:block; margin-bottom: 6px; font-weight:600;">Column Name</label>
        <input type="text" id="modalColName" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="Enter column name">
    </div>

    <div style="margin-bottom: 16px;">
        <label style="display:block; margin-bottom: 6px; font-weight:600;">Column Type</label>
        <select id="modalColType" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            <option value="text">Text</option>
            <option value="number">Number</option>
            <option value="date">Date</option>
            <option value="checkbox">Checkbox</option>
            <option value="dropdown">Dropdown List</option>
            <option value="file">File (URL / path)</option>
        </select>
    </div>

    <div id="dropdownOptions" style="margin-top:10px; display:none;">
        <label style="display:block; margin-bottom: 6px; font-weight:600;">Dropdown Options (comma separated)</label>
        <input type="text" id="dropdownValues" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="e.g. Yes, No, Maybe, Pending">
    </div>

    <div style="margin-top:24px; text-align:right;">
        <button onclick="closeColumnTypeModal()" class="btn btn-sm btn-secondary me-4">Cancel</button>
        <button onclick="applyColumnType()" class="btn btn-sm text-white lufera-bg">Apply</button>
    </div>
</div>

<div id="modalBackdropReminder" onclick="closeReminderModal()"></div>

<div id="reminderModal" class="modal" style="height:auto; display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:24px; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.25); z-index:10001; width:420px;">
    <h6 class="mb-16">Set Reminder for Row <span id="reminderRow"></span></h6>

    <!-- Existing reminders list -->
    <div id="reminderHistory" class="mb-16" style="max-height:160px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:6px; padding:8px; display:none;">
        <!-- Filled dynamically from get_reminder.php -->
    </div>

    <div class="mb-16">
        <label class="form-label fw-500">Reminder Date</label>
        <input type="datetime-local" id="reminderDate" class="form-control" required>
    </div>

    <div class="mb-20">
        <label class="form-label fw-500">Send reminder to (email)</label>
        <input type="email" id="reminderEmail" class="form-control" placeholder="someone@example.com" title="Reminder email will be sent to this address">
    </div>
    
    <div class="mb-20">
        <label class="form-label fw-500">Message / Note</label>
        <textarea id="reminderMessage" class="form-control" rows="3" onkeydown="handleReminderKey(event)"></textarea>
    </div>
    
    <div class="text-end">
        <button class="btn btn-sm btn-secondary me-1" onclick="closeReminderModal()">Cancel</button>
        <button class="btn btn-sm lufera-bg text-white" onclick="saveReminder()">Save Reminder</button>
    </div>
</div>

<!-- Attachment Preview Modal -->
<div id="modalBackdropPreview" class="backdrop" onclick="closePreviewModal()"></div>

<div id="previewModal" class="modal-preview" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); width:100%; max-width:100%; background:#000; border-radius:0px; box-shadow:0 10px 40px rgba(0,0,0,0.4); z-index:10002; overflow:auto; height:100%">
    <div style="display: flex; height: 100%;">
        <!-- Left: Preview Area -->
        <div id="previewContent" style="flex: 1; padding: 16px; overflow: auto; background: #000; display: flex; align-items: center; justify-content: center;">
            <!-- Image / iframe / message will go here -->
        </div>

        <!-- Right: File Info Sidebar -->
        <div id="previewSidebar" style="width: 320px; background: #fff; padding: 20px; overflow-y: auto; color:#000">
            <h6 class="mb-4 d-inline">File Details</h6>
            <h6 class="float-end cursor-pointer" onclick="closePreviewModal()">&times;</h6>
            <div class="my-3">
                <strong>File Name:</strong>
                <div id="previewFileName" class="text-break"></div>
            </div>
            <div class="mb-3">
                <strong>File Size:</strong>
                <div id="previewFileSize"></div>
            </div>
            <div class="mb-3">
                <strong>Date Uploaded:</strong>
                <div id="previewUploaded"></div>
            </div>
            <div class="mb-3">
                <strong>Created By</strong>
                <div id="createdBy"></div>
            </div>
            <!-- Optional: extra info like row, column, mime type, etc. -->
            <div class="mb-3" id="previewExtraInfo" style="display:none;"></div>

            <a id="downloadLink" class="btn btn-sm lufera-bg text-white me-10" href="#" download>Download</a>
            <!-- <button class="btn btn-lg btn-secondary" onclick="closePreviewModal()">Close</button> -->
        </div>
    </div>
</div>

<style>
    .backdrop {
        display:none;
        position:fixed; inset:0;
        background:rgba(0,0,0,0.5);
        z-index:10001;
    }
    .backdrop.open { display:block; }
    .modal-preview.open { display:block; }
</style>

<script>
    // ────────────────────────────────────────────────
    // Helper: make any textarea support Enter=send, Shift+Enter=newline
    // ────────────────────────────────────────────────
    function makeTextareaSmartSend(textareaId, sendCallback) {
        const textarea = document.getElementById(textareaId);
        if (!textarea) return;

        textarea.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                if (e.shiftKey) {
                    // Allow new line (default behavior)
                    return;
                } else {
                    // Enter without shift → send
                    e.preventDefault();
                    sendCallback();
                }
            }
        });
    }   
    function handleReminderKey(event) {
    if (event.key === "Enter") {
        if (event.shiftKey) {
            // Shift + Enter → allow new line
            return;
        } else {
            // Enter only → submit
            event.preventDefault();
            saveReminder();
        }
    }
}

function openPreviewModal(filePath, fileName, fileSize = null, createdAt = null, uploadedBy = null, extra = {}) {
    const modal     = document.getElementById("previewModal");
    const backdrop  = document.getElementById("modalBackdropPreview");
    const content   = document.getElementById("previewContent");
    const nameEl    = document.getElementById("previewFileName");
    const sizeEl    = document.getElementById("previewFileSize");
    const dateEl    = document.getElementById("previewUploaded");
    const createdByEl = document.getElementById("createdBy");
    const extraEl   = document.getElementById("previewExtraInfo");
    const download  = document.getElementById("downloadLink");

    if (!modal || !backdrop) return;

    // ── Metadata ───────────────────────────────────────────────
    nameEl.textContent   = fileName || "Unnamed file";
    createdByEl.textContent = uploadedBy || "Form User";

    // File size formatting
    if (fileSize && !isNaN(fileSize) && fileSize > 0) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = fileSize;
        let i = 0;
        while (size >= 1024 && i < units.length - 1) {
            size /= 1024;
            i++;
        }
        sizeEl.textContent = size.toFixed(1) + " " + units[i];
    } else {
        sizeEl.textContent = "—";
    }

    // Date
    dateEl.textContent = createdAt ? new Date(createdAt).toLocaleString() : "—";

    // Extra info
    if (extra && Object.keys(extra).length > 0) {
        let html = "";
        if (extra.row)    html += `<div><strong>Row:</strong> ${extra.row}</div>`;
        if (extra.column) html += `<div><strong>Column:</strong> ${extra.column}</div>`;
        if (extra.source) html += `<div><strong>Source:</strong> ${extra.source}</div>`;
        extraEl.innerHTML = html;
        extraEl.style.display = "block";
    } else {
        extraEl.style.display = "none";
    }

    download.href = filePath;
    download.download = fileName || "download";

    // ── Preview content ────────────────────────────────────────
    content.innerHTML = "";
    const ext = (fileName || filePath).split('.').pop().toLowerCase();

    if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
        const img = document.createElement("img");
        img.src = filePath;
        img.style.maxWidth = "100%";
        img.style.maxHeight = "75vh";
        img.style.objectFit = "contain";
        img.style.borderRadius = "6px";
        content.appendChild(img);
    }
    else if (ext === 'pdf') {
        const iframe = document.createElement("iframe");
        iframe.src = filePath;
        iframe.style.width = "100%";
        iframe.style.height = "100%";
        iframe.style.border = "none";
        content.appendChild(iframe);
    }
    else {
        content.innerHTML = `
            <div style="text-align:center; color:#aaa; padding:60px;">
                <h4>No preview available</h4>
                <p>File type: .${ext}</p>
                <p>Use the Download button below.</p>
            </div>
        `;
    }

    // Show modal
    modal.style.display = "block";
    backdrop.style.display = "block";
}

function closePreviewModal() {
    const modal    = document.getElementById("previewModal");
    const backdrop = document.getElementById("modalBackdropPreview");

    if (modal) {
        modal.style.display = "none";
        modal.classList.remove("open");
    }
    if (backdrop) {
        backdrop.style.display = "none";
        backdrop.classList.remove("open");
    }
}

function handleBack() {
    if (!hasUnsavedChanges) {
        history.back();
        return;
    }

    Swal.fire({
        title: 'Unsaved changes',
        text: 'You have unsaved changes. What would you like to do?',
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Save & Go Back',
        denyButtonText: 'Discard Changes',
        cancelButtonText: 'Stay'
    }).then(async (result) => {
        if (result.isConfirmed) {
            await document.getElementById("save-db").click();
            hasUnsavedChanges = false;
            history.back();
        } 
        else if (result.isDenied) {
            hasUnsavedChanges = false;
            history.back();
        }
        // Cancel → do nothing
    });
}

</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php'; ?>
