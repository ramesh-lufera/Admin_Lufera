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
    input[type=file]{display:none}
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
let ROWS = 20;
let COLS = 10;

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

    const cap = document.createElement("caption");
    cap.textContent = "Sheet1";
    table.appendChild(cap);

    const thead = document.createElement("thead");
    const hRow = document.createElement("tr");

    hRow.appendChild(document.createElement("th")); // corner cell

    for (let c = 1; c <= COLS; c++) {
        const th = document.createElement("th");
        th.dataset.c = c;
        th.contentEditable = true;
        th.textContent = colName(c);
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

            div.addEventListener("input", onEdit);
            div.addEventListener("focus", onFocus);
            div.addEventListener("keydown", onKeyDown);

            td.appendChild(div);
            tr.appendChild(td);
        }

        tbody.appendChild(tr);
    }

    table.appendChild(tbody);
    sheetEl.innerHTML = "";
    sheetEl.appendChild(table);
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
document.getElementById("add-row").onclick = () => { ROWS++; buildTable(); };
document.getElementById("add-col").onclick = () => { COLS++; buildTable(); };

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

</script>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php'; ?>
