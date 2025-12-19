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
    .cell{height:var(--cell-height);min-width:var(--cell-width);padding:4px;box-sizing:border-box;cursor:text}
    .cell:focus{outline:2px solid #2563eb}
    .toolbar button{padding:6px 14px;border:1px solid #d1d5db;background:#fff;border-radius:6px;cursor:pointer}
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

            <div class="toolbar mb-3">
                <button id="add-row">+ Row</button>
                <button id="add-col">+ Col</button>
                <button id="export-csv">Export CSV</button>
                <button id="clear">Clear</button>

                <!-- DB Buttons -->
                <button id="save-db">Save to DB</button>
                <button id="load-db">Load from DB</button>
            </div>

            <div class="sheet" id="sheet"></div>
        </div>
    </div>
</div>

<script>
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
            th.contentEditable = false;
            th.textContent = columnHeaders[c] || colName(c - 1);
            th.style.cursor = "pointer";
            th.title = "Click to edit column name and type";

            th.addEventListener("click", (e) => {
                if (c === 1) return;
                currentColumnForType = c;
                openColumnTypeModal(c);
                e.stopPropagation();
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

    buildTable();

    Object.keys(headerSnapshot).forEach(c => {
        const th = document.querySelector(`thead th[data-c="${c}"]`);
        if (th) {
            th.textContent = headerSnapshot[c];
            columnHeaders[c] = headerSnapshot[c];
        }
    });

    columnTypes = typesSnapshot;

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
        const vals = rawOptions.split('\n').map(v => v.trim()).filter(v => v);
        columnTypes[currentColumnForType] = { type: "dropdown", options: vals };
    } else {
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
    const name = prompt("Enter sheet name:", "Sheet");
    if (!name) return;

    const payload = {
        name,
        rows: ROWS,
        cols: COLS,
        headers: [],
        columnTypes: columnTypes,
        cells: {}
    };

    document.querySelectorAll("thead th").forEach(th => {
        if (th.dataset.c && th.dataset.c != 1) {
            payload.headers.push(th.textContent);
        }
    });

    document.querySelectorAll(".cell").forEach(cell => {
        if (cell.dataset.c == 1) return;
        const raw = data[cell.id]?.raw;
        if (raw !== undefined && raw.trim() !== "") {
            payload.cells[cell.id] = raw;
        }
    });

    const res = await fetch("save.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    });

    const out = await res.json();
    alert(out.success ? "Saved with ID: " + out.id : "Save Error");
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
