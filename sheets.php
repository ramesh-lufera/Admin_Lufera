<?php 
include 'partials/layouts/layoutTop.php'; 

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
    :root{--cell-width:120px;--cell-height:28px;--header-bg:#f3f4f6}
    .sheet{border:1px solid #ddd;overflow:auto;max-width:100%;box-shadow:0 2px 6px rgba(0,0,0,0.04)}
    table{border-collapse:collapse;min-width:900px}
    th,td{border-right:1px solid #e6e6e6;border-bottom:1px solid #e6e6e6;padding:0;margin:0;}
    th{background:var(--header-bg);position:sticky;top:0;z-index:3;text-align:center;font-weight:600}
    .row-header{position:sticky;left:0;z-index:2;background:var(--header-bg);width:40px;text-align:center}
    .cell{font-size:14px;height:var(--cell-height);min-width:var(--cell-width);padding:4px;box-sizing:border-box;cursor:text;text-align:center}
    .cell:focus{outline:2px solid #2563eb}
    .selected{background:rgba(37,99,235,0.08)}
    caption{caption-side:top;text-align:left;padding:8px;font-weight:600}
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
        background: #f5f7fa;
        padding: 8px;
        border-radius: 6px;
    }

    .reply {
        margin-left: 20px;
        margin-top: 6px;
        background: #e9edf3;
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
.comment-icon, .attach-icon {
    color: #aaa;
    font-size: 14px;
}

.comment-icon.has-comment {
    color: #2563eb !important;
    font-weight: bold;
}

.attach-icon.has-attachment {
    color: #16a34a !important;
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
    top: 110%;
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

/* Show dropdown */
.dropdown.open .dropdown-menu {
    display: block;
}
  </style>
  
</head>

<body>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a onclick="history.back()" class="cursor-pointer fw-bold"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>
        <h6 class="fw-semibold mb-0">Sheets</h6>
        <span class="visibility-hidden"></span>
    </div>

    <div class="card radius-12 h-100">
        <div class="card-body p-24">

            <!-- <div class="toolbar mb-3">
                <button id="add-row">+ Row</button>
                <button id="add-col">+ Col</button>
                <button id="export-csv">Export CSV</button>
                <button id="clear">Clear</button>
                
                <button id="save-db">Save to DB</button>
                <button id="load-db">Load from DB</button>
                <button id="export-to-form">Export to Form</button>
            </div> -->

            <div class="toolbar mb-3 d-flex gap-2 align-items-center">
                

                <!-- FILE DROPDOWN -->
                <div class="dropdown">
                    <button class="dropdown-btn px-3">File</button>
                    <div class="dropdown-menu">
                        <button class="new_sheet" onclick="Redirect()">New</button>
                        <button id="export-csv">Export</button>
                        <button id="save-db">Save</button>
                        <button id="load-db">Open</button>
                        <button id="clear">Clear</button>
                    </div>
                </div>

                <!-- FORM DROPDOWN -->
                <div class="dropdown">
                    <button class="dropdown-btn px-3">Form</button>
                    <div class="dropdown-menu">
                        <button id="export-to-form">Create Form</button>
                    </div>
                </div>

                <button id="add-row" class="px-3">+ Row</button>
                <button id="add-col" class="px-3">+ Col</button>
                
            </div>


            <div class="sheet" id="sheet"></div>
        </div>
    </div>
</div>

<script>
function Redirect() {
    window.location = "sheets.php";
}
document.getElementById("export-to-form").onclick = () => {
    // Use the actual saved sheet name from DB
    let formTitle = "Untitled Sheet";

    <?php if ($sheetData): ?>
        <?php 
            // $row is from the query: SELECT * FROM sheets WHERE id = $id
            // So $row['name'] is the saved sheet name
            $currentSheetName = $row['name'] ?? 'Untitled Sheet';
        ?>
        formTitle = <?= json_encode($currentSheetName) ?>;
    <?php endif; ?>

    // Fallback if no name
    if (!formTitle || formTitle.trim() === "") {
        formTitle = "New Form";
    }

    const tempFields = [];

    for (let c = 2; c <= COLS; c++) {
        const label = (columnHeaders[c] || colName(c - 1)).trim();
        if (!label) continue;

        const colConfig = columnTypes[c] || { type: "text" };
        const colType = colConfig.type;

        let formType = "text";
        if (colType === "number") formType = "number";
        else if (colType === "date") formType = "datetime";
        else if (colType === "dropdown") formType = "select";
        else if (colType === "checkbox") formType = "checkbox";

        // Get dropdown options (only if it's a dropdown)
        const options = (colType === "dropdown" && colConfig.options && colConfig.options.length > 0)
            ? colConfig.options
            : (formType === "checkbox" ? ["Yes"] : ["Option 1", "Option 2"]);

        tempFields.push({
            id: Date.now() + c,
            type: formType,
            label: label,
            placeholder: "",
            required: false,
            options: options,
            value: "",  // ← ALWAYS EMPTY — no prefill!
            validation: ""
        });
    }

    if (tempFields.length === 0) {
        alert("No columns to export (only Tasks column found).");
        return;
    }

    const params = new URLSearchParams();
    params.append('pre_title', formTitle);
    params.append('pre_fields', JSON.stringify(tempFields));

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

let activeRow = null;
let activeAttachRow = null;
let activeSheetId = <?= isset($_GET['id']) ? intval($_GET['id']) : 0 ?>;

/* ------------------------------------------------------------
   PRELOAD PHP DATA BEFORE TABLE IS BUILT
------------------------------------------------------------ */
<?php if ($sheetData): ?>
    const loaded = <?= json_encode($sheetData) ?>;

    ROWS = loaded.rows || ROWS;
    COLS = loaded.cols || COLS;

    if (loaded.cells) {
        Object.keys(loaded.cells).forEach(id => {
            data[id] = { raw: loaded.cells[id] };
        });
    }

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
function colName(n) {
    let s = "";
    while (n > 0) {
        let r = (n - 1) % 26;
        s = String.fromCharCode(65 + r) + s;
        n = Math.floor((n - 1) / 26);
    }
    return s || "A";
}

function cellId(r, c) { return colName(c) + r; }

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
        const th = document.createElement("th");
        th.dataset.c = c;

        if (c === 1) {
            th.textContent = "Tasks";
            th.contentEditable = false;
        } else {
            // Container for name and trash
            const wrapper = document.createElement("div");
            wrapper.style.display = "flex";
            wrapper.style.alignItems = "center";
            wrapper.style.justifyContent = "center";
            wrapper.style.width = "100%";
            wrapper.style.position = "relative";

            // Column name
            const nameSpan = document.createElement("span");
            nameSpan.textContent = columnHeaders[c] || colName(c - 1);

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
            wrapper.appendChild(trashSpan);
            th.appendChild(wrapper);

            th.style.cursor = "pointer";
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
        rh.textContent = r;
        tr.appendChild(rh);

        for (let c = 1; c <= COLS; c++) {
            const td = document.createElement("td");
            const container = document.createElement("div");
            container.className = "cell";
            container.dataset.r = r;
            container.dataset.c = c;
            container.id = cellId(r, c);

            if (c === 1) {
                container.innerHTML = `
                    <span class="task-text" contenteditable="true"></span>
                    <span class="task-actions">
                        <span class="comment-icon fa fa-message cursor-pointer" title="Comments" onclick="openComments(${r})"></span>
                        <span class="attach-icon fa fa-paperclip cursor-pointer" title="Attachments" onclick="openAttachments(${r})"></span>
                        <span class="delete-row-icon fa fa-close cursor-pointer text-danger ms-2" title="Delete this row" onclick="deleteRow(${r}); event.stopPropagation();"></span>
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
                const fullName = headerSnapshot[c];
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

    recalcAll();
    refreshAllRowIcons();
}

/* ------------------------------------------------------------
   ICON UPDATE FUNCTIONS
------------------------------------------------------------ */
function updateRowIcons(row) {
    const container = document.querySelector(`.cell[data-r="${row}"][data-c="1"]`);
    if (!container) return;

    const taskActions = container.querySelector(".task-actions");
    const commentIcon = container.querySelector(".comment-icon");
    const attachIcon = container.querySelector(".attach-icon");

    const hasComments = (rowComments[row] || 0) > 0;
    const hasAttachments = (rowAttachments[row] || 0) > 0;
    const hasActivity = hasComments || hasAttachments;

    // Toggle individual icon styles
    if (commentIcon) commentIcon.classList.toggle("has-comment", hasComments);
    if (attachIcon) attachIcon.classList.toggle("has-attachment", hasAttachments);

    // Toggle the whole task-actions visibility
    if (taskActions) {
        taskActions.classList.toggle("has-activity", hasActivity);
    }
}

function refreshAllRowIcons() {
    for (let r = 1; r <= ROWS; r++) {
        updateRowIcons(r);
    }
}

/* ------------------------------------------------------------
   FETCH COUNTS FROM BACKEND ON LOAD
------------------------------------------------------------ */
function loadCountsAndRefreshIcons() {
    if (activeSheetId <= 0) {
        refreshAllRowIcons();
        return;
    }

    fetch(`counts.php?sheet_id=${activeSheetId}`)
        .then(res => res.json())
        .then(result => {
            Object.assign(rowComments, result.comments || {});
            Object.assign(rowAttachments, result.attachments || {});
            refreshAllRowIcons();
        })
        .catch(() => {
            refreshAllRowIcons(); // fallback if counts.php fails
        });
}

/* ------------------------------------------------------------
   COLUMN TYPE MODAL FUNCTIONS
------------------------------------------------------------ */
function openColumnTypeModal(col) {
    currentColumnForType = col;

    const currentName = columnHeaders[col] || colName(col - 1);
    document.getElementById("modalColName").value = currentName;

    const config = columnTypes[col] || { type: "text", options: [] };
    const selectEl = document.getElementById("modalColType");
    selectEl.value = config.type;

    const dropdownDiv = document.getElementById("dropdownOptions");
    const dropdownTextarea = document.getElementById("dropdownValues");

    if (config.type === "dropdown") {
        dropdownDiv.style.display = "block";
        dropdownTextarea.value = config.options.join("\n");
    } else {
        dropdownDiv.style.display = "none";
        dropdownTextarea.value = "";
    }

    selectEl.onchange = function() {
        dropdownDiv.style.display = this.value === "dropdown" ? "block" : "none";
    };

    document.getElementById("modalBackdrop").classList.add("open");
    document.getElementById("columnTypeModal").classList.add("open");
}

function closeColumnTypeModal() {
    document.getElementById("modalBackdrop").classList.remove("open");
    document.getElementById("columnTypeModal").classList.remove("open");
}

function applyColumnType() {
    if (!currentColumnForType) return;

    const newName = document.getElementById("modalColName").value.trim();
    const selectedType = document.getElementById("modalColType").value;

    if (newName) {
        columnHeaders[currentColumnForType] = newName;
        const th = document.querySelector(`thead th[data-c="${currentColumnForType}"]`);
        if (th) {
            const firstLine = newName.split('\n')[0];
            th.textContent = newName.includes('\n') ? firstLine + "..." : firstLine;
            th.title = newName;
        }
    } else {
        const fallback = colName(currentColumnForType - 1);
        columnHeaders[currentColumnForType] = fallback;
        const th = document.querySelector(`thead th[data-c="${currentColumnForType}"]`);
        if (th) th.textContent = fallback;
    }

    if (selectedType === "dropdown") {
    const rawOptions = document.getElementById("dropdownValues").value;
    const vals = rawOptions
        .split(/[\n,]+/)
        .map(v => v.trim())
        .filter(v => v.length > 0);
        columnTypes[currentColumnForType] = { type: "dropdown", options: vals };
    }    
    else {
        columnTypes[currentColumnForType] = { type: selectedType };
    }

    for (let r = 1; r <= ROWS; r++) {
        const cell = document.getElementById(cellId(r, currentColumnForType));
        if (cell) renderCellContent(cell, currentColumnForType);
    }

    closeColumnTypeModal();
}

/* ------------------------------------------------------------
   EVENT HANDLERS
------------------------------------------------------------ */
function onEdit(e) {
    const cell = e.target.closest(".cell");
    if (!cell || cell.dataset.c == 1) return;

    const id = cell.id;
    const col = parseInt(cell.dataset.c);
    const config = columnTypes[col] || { type: "text" };
    const type = config.type;

    let value;
    if (type === "checkbox") {
        value = e.target.checked;
    } else if (["number", "date", "dropdown"].includes(type)) {
        value = e.target.value;
    } else {
        value = cell.textContent;
    }

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
document.getElementById("add-row").onclick = () => { ROWS++; rebuildPreserveData(); };
document.getElementById("add-col").onclick = () => { COLS++; rebuildPreserveData(); };

document.getElementById("save-db").onclick = async () => {
    let name = prompt("Enter sheet name:", columnHeaders[1] || "Sheet");
    if (!name) return;

    const payload = {
        name,
        rows: ROWS,
        cols: COLS,
        headers: [],
        columnTypes: columnTypes,
        cells: {}
    };

    // Collect headers (skip Tasks column)
    document.querySelectorAll("thead th").forEach(th => {
        if (th.dataset.c && th.dataset.c != 1) {
            // Get the visible text from the name span (not the full th)
            const nameSpan = th.querySelector("span");
            payload.headers.push(nameSpan ? nameSpan.textContent : th.textContent);
        }
    });

    // Collect cell data
    document.querySelectorAll(".cell").forEach(cell => {
        if (cell.dataset.c == 1) return; // skip Tasks column text
        const raw = data[cell.id]?.raw;
        if (raw !== undefined && raw.trim() !== "") {
            payload.cells[cell.id] = raw;
        }
    });

    // If we have an ID in URL → UPDATE, else INSERT
    let url = "save.php";
    if (activeSheetId > 0) {
        payload.id = activeSheetId; // tell backend to update
    }

    const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    });

    const out = await res.json();

    if (out.success) {
        // If it was a new sheet, update the URL with the new ID
        if (!activeSheetId && out.id) {
            history.replaceState(null, '', `?id=${out.id}`);
            activeSheetId = out.id;
        }
        alert("Sheet saved successfully!");
    } else {
        alert("Save Error: " + (out.error || "Unknown"));
    }
};

/* ------------------------------------------------------------
   INITIAL BUILD & LOAD
------------------------------------------------------------ */
buildTable();

document.addEventListener("DOMContentLoaded", () => {

    document.getElementById("export-csv").onclick = () => {
        const out = [];
        for (let r = 1; r <= ROWS; r++) {
            const row = [];
            for (let c = 1; c <= COLS; c++) {
                const id = cellId(r, c);
                const raw = data[id]?.raw || document.getElementById(id)?.textContent || "";
                row.push('"' + raw.replace(/"/g, '""') + '"');
            }
            out.push(row.join(","));
        }
        const blob = new Blob([out.join("\n")], { type: "text/csv" });
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = "sheet.csv";
        a.click();
    };

    document.getElementById("clear").onclick = () => {
        if (!confirm("Clear all data?")) return;
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
        Object.keys(rowComments).forEach(k => delete rowComments[k]);
        Object.keys(rowAttachments).forEach(k => delete rowAttachments[k]);
        refreshAllRowIcons();
    };

    document.getElementById("load-db").onclick = async () => {
        const listRes = await fetch("list.php");
        const sheets = await listRes.json();

        if (!sheets.length) {
            alert("No saved sheets found.");
            return;
        }

        const id = prompt(
            "Saved Sheets:\n" +
            sheets.map(s => `${s.id}: ${s.name} (${s.updated_at})`).join("\n") +
            "\nEnter ID:"
        );

        if (!id) return;

        const loadRes = await fetch("load.php?id=" + id);
        const payload = await loadRes.json();

        if (!payload.success) {
            alert("Error: " + payload.error);
            return;
        }

        const sheet = payload.data;

        ROWS = sheet.rows;
        COLS = sheet.cols;
        columnTypes = sheet.columnTypes || {};
        activeSheetId = id;

        buildTable();

        document.querySelectorAll("thead th").forEach(th => {
            if (th.dataset.c && th.dataset.c != 1) {
                const idx = Number(th.dataset.c) - 2;
                th.textContent = sheet.headers[idx] || colName(Number(th.dataset.c) - 1);
            }
        });

        Object.keys(sheet.cells).forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                data[id] = { raw: sheet.cells[id] };
                if (el.dataset.c != 1) renderCellContent(el, parseInt(el.dataset.c));
            }
        });

        recalcAll();
        loadCountsAndRefreshIcons(); // This will show correct icons immediately
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
    updateRowIcons(activeRow);

    comments.forEach(c => {
        const div = document.createElement("div");
        div.className = "comment";
        div.innerHTML = `
            <div>${c.comment}</div>
            <small>${c.created_at}</small>
            <button onclick="replyPrompt(${c.id})">Reply</button>
        `;
        list.appendChild(div);

        c.replies.forEach(r => {
            const rd = document.createElement("div");
            rd.className = "comment reply";
            rd.innerHTML = `<div>${r.comment}</div>`;
            list.appendChild(rd);
        });
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
            row_number: activeRow,
            comment: text
        })
    }).then(() => {
        document.getElementById("commentText").value = "";
        rowComments[activeRow] = (rowComments[activeRow] || 0) + 1;
        updateRowIcons(activeRow);
        loadComments();
    });
}
function replyPrompt(parentId) {
    const text = prompt("Reply:");
    if (!text) return;

    fetch("save_comment.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            sheet_id: activeSheetId,
            row_number: activeRow,
            parent_id: parentId,
            comment: text
        })
    }).then(() => {
        rowComments[activeRow] = (rowComments[activeRow] || 0) + 1;
        updateRowIcons(activeRow);
        loadComments();
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
    updateRowIcons(activeAttachRow);

    const list = document.getElementById("attachmentList");
    list.innerHTML = "";

    files.forEach(f => {
        const div = document.createElement("div");
        div.className = "comment";
        div.innerHTML = `
            <a href="${f.file_path}" target="_blank">${f.original_name}</a>
            <br><small>${f.created_at}</small>
        `;
        list.appendChild(div);
    });
}

async function uploadAttachment() {
    const fileInput = document.getElementById("attachFile");
    if (!fileInput.files.length) return;

    const formData = new FormData();
    formData.append("file", fileInput.files[0]);
    formData.append("sheet_id", activeSheetId);
    formData.append("row_number", activeAttachRow);

    const res = await fetch("upload_attachment.php", {
        method: "POST",
        body: formData
    });

    const out = await res.json();
    if (out.success) {
        fileInput.value = "";
        rowAttachments[activeAttachRow] = (rowAttachments[activeAttachRow] || 0) + 1;
        updateRowIcons(activeAttachRow);
        loadAttachments();
    } else {
        alert(out.error || "Upload failed");
    }
}

function deleteRow(row) {
    if (ROWS <= 1) {
        alert("Cannot delete the last row.");
        return;
    }

    if (!confirm(`Delete row ${row} and all its data ?`)) {
        return;
    }

    // Remove all data in this row
    for (let c = 1; c <= COLS; c++) {
        const id = cellId(row, c);
        delete data[id];
    }

    // Remove comment/attachment tracking
    delete rowComments[row];
    delete rowAttachments[row];

    ROWS--;
    rebuildPreserveData();
}

function deleteColumn(col) {
    if (COLS <= 1) {
        alert("Cannot delete — at least one column must remain.");
        return;
    }
    if (col === 1) {
        alert("Cannot delete the Tasks column.");
        return;
    }

    const colNameDisplay = columnHeaders[col] || colName(col - 1);
    if (!confirm(`Delete column "${colNameDisplay}" and all its data ?`)) return;

    // Remove data
    for (let r = 1; r <= ROWS; r++) {
        const id = cellId(r, col);
        delete data[id];
    }

    // Remove config
    delete columnTypes[col];
    delete columnHeaders[col];

    // Shift left
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
        <strong>Task Attachments</strong>
        <button onclick="closeAttachments()">✖</button>
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

</body>
</html>

<?php include './partials/layouts/layoutBottom.php'; ?>