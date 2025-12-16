<?php 
include './partials/layouts/layoutTop.php'; 

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
<?php endif; ?>

/* ------------------------------------------------------------
   HELPERS
------------------------------------------------------------ */
function colName(n) {
    let s = "";
    while (n) {
        let r = (n - 1) % 26;
        s = String.fromCharCode(65 + r) + s;
        n = Math.floor((n - 1) / 26);
    }
    return s;
}

function cellId(r, c) { return colName(c) + r; }

/* ------------------------------------------------------------
   BUILD TABLE
------------------------------------------------------------ */
function buildTable() {
    const sheetEl = document.getElementById("sheet");
    const table = document.createElement("table");

    // const cap = document.createElement("caption");
    // cap.textContent = "Sheet1";
    // table.appendChild(cap);

    const thead = document.createElement("thead");
    const hRow = document.createElement("tr");

    hRow.appendChild(document.createElement("th")); // corner cell

    for (let c = 1; c <= COLS; c++) {
    const th = document.createElement("th");

    if (c === 1) {
        th.textContent = "Tasks";
        th.contentEditable = false;
    } else {
        th.textContent = colName(c - 1);
        th.contentEditable = true;
    }

    th.dataset.c = c;
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
            const div = document.createElement("div");

            div.className = "cell";
            div.contentEditable = true;
            div.dataset.r = r;
            div.dataset.c = c;
            div.id = cellId(r, c);

            // FIRST COLUMN = TASK + COMMENT ICON
            if (c === 1) {
                div.innerHTML = `
                    <span class="task-text" contenteditable="true"></span>
                    <span class="task-actions">
                        <span class="comment-icon fa fa-message cursor-pointer" title="Comments" onclick="openComments(${r})"></span>
                        <span class="attach-icon fa fa-paperclip cursor-pointer" title="Attachments" onclick="openAttachments(${r})"></span>
                    </span>
                `;
                div.contentEditable = false;
            }

            td.appendChild(div);
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

    buildTable();

    // Restore headers
    Object.keys(headerSnapshot).forEach(c => {
        columnHeaders[c] = headerSnapshot[c];
        const th = document.querySelector(`thead th[data-c='${c}']`);
        if (th) th.textContent = headerSnapshot[c];
    });

    // Restore cell data
    Object.keys(dataSnapshot).forEach(id => {
        data[id] = dataSnapshot[id];
        const el = document.getElementById(id);
        if (el) el.textContent = dataSnapshot[id].raw;
    });

    recalcAll();
}

/* ------------------------------------------------------------
   LOAD VALUES AFTER TABLE IS BUILT
------------------------------------------------------------ */
<?php if ($sheetData): ?>
window.addEventListener("DOMContentLoaded", () => {
    // Load headers
    if (loaded.headers) {
        document.querySelectorAll("thead th").forEach(th => {
            if (th.dataset.c) {
                const idx = Number(th.dataset.c) - 1;
                th.textContent = loaded.headers[idx] || th.textContent;
            }
        });
    }

    // Load cells
    if (loaded.cells) {
        Object.keys(loaded.cells).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = loaded.cells[id];
        });
    }

    recalcAll();
});
<?php endif; ?>

/* ------------------------------------------------------------
   EVENT HANDLERS
------------------------------------------------------------ */
function onEdit(e) {
    const id = e.target.id;
    data[id] = { raw: e.target.textContent };
    recalcAll();
}

function onFocus(e) {
    focusedCell = e.target;
    document.querySelectorAll(".cell").forEach(c => c.classList.remove("selected"));
    e.target.classList.add("selected");
}

function onKeyDown(e) {
    const r = parseInt(e.target.dataset.r);
    const c = parseInt(e.target.dataset.c);

    let nr = r, nc = c;

    if (e.key === "Enter") { e.preventDefault(); nr++; }
    if (e.key === "ArrowDown") nr++;
    if (e.key === "ArrowUp") nr--;
    if (e.key === "ArrowRight") nc++;
    if (e.key === "ArrowLeft") nc--;

    nr = Math.min(Math.max(1, nr), ROWS);
    nc = Math.min(Math.max(1, nc), COLS);

    const next = document.getElementById(cellId(nr, nc));
    if (next) next.focus();
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
                document.getElementById(id).textContent = evalCell(id);
            }
        }
}

/* ------------------------------------------------------------
   BUTTON ACTIONS
------------------------------------------------------------ */
document.getElementById("add-row").onclick = () => { ROWS++; rebuildPreserveData(); };
document.getElementById("add-col").onclick = () => { COLS++; rebuildPreserveData(); };

/* ------------------------------------------------------------
   SAVE TO DB
------------------------------------------------------------ */
document.getElementById("save-db").onclick = async () => {
    const name = prompt("Enter sheet name:", "Sheet");
    if (!name) return;

    const payload = {
        name,
        rows: ROWS,
        cols: COLS,
        headers: [],
        cells: {}
    };

    document.querySelectorAll("thead th").forEach(th => {
        if (th.dataset.c) payload.headers.push(th.textContent);
    });

    document.querySelectorAll(".cell").forEach(cell => {
        if (cell.textContent.trim() !== "")
            payload.cells[cell.id] = data[cell.id]?.raw || cell.textContent;
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
   INITIAL BUILD
------------------------------------------------------------ */
buildTable();
// ------------------------------------------------------------
// ATTACH ALL BUTTON EVENTS AFTER PAGE LOAD
// ------------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {

// Export CSV
document.getElementById("export-csv").onclick = () => {
    const out = [];
    for (let r = 1; r <= ROWS; r++) {
        const row = [];
        for (let c = 1; c <= COLS; c++) {
            const id = cellId(r, c);
            const raw = data[id]?.raw || document.getElementById(id).textContent;
            row.push(raw.replace(/"/g, '""'));
        }
        out.push(row.join(","));
    }
    const blob = new Blob([out.join("\n")], { type: "text/csv" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "sheet.csv";
    a.click();
};

// Clear Sheet
document.getElementById("clear").onclick = () => {
    for (let r = 1; r <= ROWS; r++) {
        for (let c = 1; c <= COLS; c++) {
            const id = cellId(r, c);
            data[id] = { raw: "" };
            document.getElementById(id).textContent = "";
        }
    }
};

// Load From DB
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
    buildTable();

    document.querySelectorAll("thead th").forEach(th => {
        if (th.dataset && th.dataset.c) {
            const idx = Number(th.dataset.c) - 1;
            th.textContent = sheet.headers[idx] || colName(idx + 1);
        }
    });

    Object.keys(sheet.cells).forEach(id => {
        const el = document.getElementById(id);
        data[id] = { raw: sheet.cells[id] };
        if (el) el.textContent = sheet.cells[id];
    });

    recalcAll();
};

});

let activeRow = null;
let activeSheetId = <?= isset($_GET['id']) ? intval($_GET['id']) : 0 ?>;

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
    }).then(loadComments);
}

function saveComment() {
    const text = document.getElementById("commentText").value;
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
        loadComments();
    });
}

//attachments
let activeAttachRow = null;

function openAttachments(row) {
    activeAttachRow = row;
    document.getElementById("attachmentPanel").classList.add("open");
    loadAttachments();
}

function closeAttachments() {
    document.getElementById("attachmentPanel").classList.remove("open");
}

async function loadAttachments() {
    const res = await fetch(
        `attachments.php?sheet_id=${activeSheetId}&row=${activeAttachRow}`
    );
    const files = await res.json();

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

</body>
</html>

<?php include './partials/layouts/layoutBottom.php'; ?>
