<?php
include 'partials/layouts/layoutTop.php';
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
$sheetId   = isset($_GET['id']) ? intval($_GET['id']) : 0;
date_default_timezone_set('Asia/Kolkata');
if ($sheetId <= 0) {
    header("Location: dashboard-sheets.php");
    exit;
}

$stmt = $conn->prepare("SELECT name, form_id FROM sheets WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $sheetId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard-sheets.php");
    exit;
}

$sheetRow  = $result->fetch_assoc();
$sheetName = $sheetRow['name'] ?? "Untitled Sheet";
$form_id = $sheetRow['form_id'] ?? null;
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_sheet') {
    $sheet_name = trim($_POST['sheet_name'] ?? '');

    if (strlen($sheet_name) >= 1 && strlen($sheet_name) <= 100) {  // adjust length limit as needed
        $stmt = $conn->prepare("INSERT INTO sheets (name, created_at, updated_at) VALUES (?, NOW(), NOW())");
        $stmt->bind_param("s", $sheet_name);
        
        if ($stmt->execute()) {
            $new_sheet_id = $conn->insert_id;
            logActivity(
                $conn,
                $loggedInUserId,
                "Sheets",
                "Created new sheet: {$sheet_name}"
            );
            echo "<script>window.location.href='sheets.php?id={$new_sheet_id}';</script>";
            exit;
        } else {
            $error = "Database error: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Please enter a valid sheet name (1–100 characters).";
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
    :root{--cell-width:150px;--cell-height:auto;--header-bg:#f3f4f6}
    .sheet{border:1px solid #ddd;overflow:auto;max-width:100%;box-shadow:0 2px 6px rgba(0,0,0,0.04);max-height: 590px;}
    table{border-collapse:collapse;min-width:900px;font-size:14px;width:100%}
    th,td{border-right:1px solid #e6e6e6;border-bottom:1px solid #e6e6e6;padding:0 10px !important;margin:0;box-sizing: border-box;}
    th{top:0;font-weight:600;vertical-align:middle;}
    thead th{background-color:#f4f4f4 !important}
    .row-header{left:0;width:40px;text-align:center;background:var(--header-bg);border-right: 1px solid #e6e6e6;}
    /* .cell{font-size:14px;height:40px;min-width:var(--cell-width);padding:4px;box-sizing:border-box;cursor:text;align-content: center;} */
    /* .cell:focus{outline:2px solid #2563eb} */
    /* .selected{background:rgba(37,99,235,0.08)} */
    caption{caption-side:top;text-align:left;padding:8px;font-weight:600}
    .cell.checkbox, .cell>select{text-align:center}
    /* input[type=file]{display:none} */
    .cell {
    /* position: relative; */
    height: 40px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    width: 200px;
    align-content: center;
    }

    .cell-preview{
    position:fixed;
    display:none;
    max-width:350px;
    min-width:200px;
    padding:12px;
    background:#fff;
    border-radius:10px;
    border:1px solid #ddd;
    box-shadow:0 8px 24px rgba(0,0,0,.18);
    z-index:99999;
    white-space:pre-wrap;
    word-break:break-word;
    line-height:1.5;
    pointer-events:none;
}

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
.side-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);     /* lighter black – adjust opacity as needed */
    z-index: 998;                       /* below panels (9999) but above content */
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.25s ease, visibility 0.25s ease;
    pointer-events: none;               /* important: allows click-through until open */
}

.side-backdrop.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;               /* now catches clicks */
}
.comment-panel {
    position: fixed;
    right: -380px;
    top: 0;
    width: 380px;
    height: 100%;
    background: #fff;
    border-left: 1px solid #d1d5db;
    box-shadow: -2px 0 6px rgba(0,0,0,0.15);
    transition: right .3s ease;
    z-index: 9999;
    display: flex;
    flex-direction: column;
}
body.side-panel-open {
    overflow: hidden;
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

/* .dropdown-menus {
    display: none;
    position: absolute;
    top: 250%;
    left: -20px !important;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 160px;
    z-index: 1000;
    padding: 5px 0;
    font-weight: 300;
    cursor:pointer;
}

.dropdown-menu button {
    width: 200px;
    text-align: left;
    padding: 8px 12px;
    border: none;
    background: none;
    cursor: pointer;
}  

.dropdown-menu button:hover {
    background: #f3f4f6;
}*/

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
.sort-col-icon{
    opacity:0;
    margin-left:6px;
    color:#6b7280;
    transition:.2s;
    display: flex;
    flex-grow: 1;
}

thead th:hover .sort-col-icon{
    opacity:1;
}

.sort-col-icon:hover{
    color:#1c8a8a;
}

.format-toolbar button.active{
    background:var(--lufera-main-color);
    color:#fff;
}

.format-toolbar button{
    width:36px;
    height:36px;
    padding:0;
}
/* .cell.selected{
outline:2px solid #2563eb;
outline-offset:-2px;
} */

/* Row ellipsis dropdown */
.row-dropdown {
    position: relative;
    display: inline-block;
}

.row-menu-btn {
    cursor: pointer;
    padding: 6px;
    border: 0;
    background: transparent;
}

/* Hidden by default */
.row-dropdown-menu {
    position: absolute;
    left: 25px;
    top: 0;

    display: none;

    min-width: 210px;
    background: #fff;

    border: 1px solid #ddd;
    border-radius: 8px;

    box-shadow: 0 8px 20px rgba(0, 0, 0, .15);

    z-index: 99999;
}

/* ONLY JavaScript controls open/close */
.row-dropdown.open > .row-dropdown-menu {
    display: block;
}

/* Menu buttons */
.row-dropdown-menu button {
    width: 100%;
    border: none;
    background: transparent;
    padding: 10px 14px;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.row-dropdown-menu button:hover {
    background: #f3f4f6;
}

.row-dropdown-menu button:hover{
    background:#f3f4f6;
}
.cell[data-c="1"]{
    overflow: visible !important;
    width:auto;
}

/* Column header dropdown */
thead th .dropdown {
    position: relative;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

thead th .dropdown > .fa-ellipsis-v {
    padding: 4px 6px;
    cursor: pointer;
}

thead th .dropdown .dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    left: auto;
    min-width: 200px;
    z-index: 9999;
}

thead th .dropdown.open .dropdown-menu {
    display: block;
}

/* Dropdown buttons */
thead th .dropdown .dropdown-menu button {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 12px;
    border: 0;
    background: transparent;
    text-align: left;
    white-space: nowrap;
}

thead th .dropdown .dropdown-menu button:hover {
    background: #f3f4f6;
}

/* Excel-style color buttons */
.xl-color-picker {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.xl-color-btn {
    position: relative;
    width: 36px;
    height: 36px;
    padding: 0;
    border: 1px solid #dee2e6;
    background: #fff;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #333;
}

.xl-color-btn:hover {
    background: #f3f4f6;
    border-color: #c8cdd3;
}

.xl-color-btn .fa {
    font-size: 16px;
}

/* Excel-style color indicator underneath icon */
.xl-color-line {
    position: absolute;
    bottom: 3px;
    left: 7px;
    right: 7px;
    height: 3px;
    border-radius: 1px;
    background: #000;
}

/* Fill color indicator */
#fillColorLine {
    background: #ffffff;
    border: 1px solid #999;
}

/* Hide native color input */
.xl-color-picker input[type="color"] {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
#formatPainterBtn {
    width: 36px;
    height: 36px;
    padding: 0;
}

#formatPainterBtn.active {
    background: #dbeafe;
    border-color: #2563eb;
    color: #2563eb;
}

#formatPainterBtn:hover {
    background: #f3f4f6;
}
/* Excel-style column resize handle */
thead th {
    position: sticky;
    top: 0;
    /* z-index: 3; */
}
#sheet table {
    width: max-content;
    min-width: 100%;
    table-layout: fixed;
}
.column-resize-handle {
    position: absolute;
    top: 0;
    right: -3px;
    width: 7px;
    height: 100%;
    cursor: col-resize;
    z-index: 20;
}

/* Show resize area on hover */
.column-resize-handle:hover {
    background: rgba(37, 99, 235, 0.35);
}

/* While dragging */
body.column-resizing {
    cursor: col-resize !important;
    user-select: none !important;
}

body.column-resizing * {
    cursor: col-resize !important;
    user-select: none !important;
}
.xl-color-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.xl-color-btn:disabled:hover {
    background: #fff;
    border-color: #dee2e6;
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
            <h6 class="fw-semibold mb-0 sheet_title"><?= htmlspecialchars($sheetName) ?></h6>
        </div>
        <!-- <button id="import-file">
            <span class="fa fa-upload text-xl"></span>
        </button> -->
        <!-- <input type="file" id="importInput" accept=".csv,.xlsx,.xls" style="display:none">
        <button id="export-csv"><span class="fa fa-file-export text-xl"></span></button>
         <a class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createSheetModal">
            Create New Sheet
        </a> 
        <div class="dropdown">
            <button class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2 dropdown-toggle toggle-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">New Sheet</button>
            <ul class="dropdown-menus">
                <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900" data-bs-toggle="modal" data-bs-target="#createSheetModal">Create New</a></li>
                <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900" id="import-file">Import</a></li>
            </ul>
        </div> -->
        <a onclick="handleBack()" class="cursor-pointer fw-bold visibility-hidden">
            <span class="fa fa-arrow-left"></span> Back
        </a>
    </div>

    <div class="card radius-12 h-100">
        <div class="card-body p-24">
        <div class="d-lg-flex align-items-center justify-content-between">

<!-- Left Section -->
<div class="d-flex align-items-center gap-3 mb-3">

    <div class="dropdowns">
        <button type="button" data-bs-toggle="dropdown">File</button>
        <ul class="dropdown-menu" style="box-shadow: 0px 0px 15px 5px rgba(0, 0, 0, 0.13), 0 1px 1px 0 rgba(0, 0, 0, 0.11);">
            <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer" data-bs-toggle="modal" data-bs-target="#createSheetModal">Create New</a></li>
            <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer" id="import-file">Import</a></li>
            <input type="file" id="importInput" accept=".csv,.xlsx,.xls" style="display:none">
            <!-- <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer">Open</a></li> -->
            <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer" id="save-db">Save</a></li>
            <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer" onclick="refreshSheet()">Refresh</a></li>
            <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer" onclick="renameSheet()">Rename</a></li>
            <!-- <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer">Delete</a></li> -->
            <!-- <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer" onclick="printSheet()">Print</a></li> -->
            <li><a class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 cursor-pointer" id="export-csv">Export</a></li>
        </ul>
    </div>

    <!-- <button id="clear">Clear</button> -->
    <button id="export-to-form">Forms</button>

    <!-- Formatting Toolbar -->
    <div class="format-toolbar d-lg-flex d-none align-items-center gap-2 ms-3">

        <select id="fontFamily" class="form-select form-select-sm" style="width:150px">
            <option value="Arial">Arial</option>
            <option value="Calibri">Calibri</option>
            <option value="Verdana">Verdana</option>
            <option value="Tahoma">Tahoma</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Georgia">Georgia</option>
        </select>

        <select id="fontSize" class="form-select form-select-sm" style="width:80px">
            <option>10</option>
            <option>11</option>
            <option selected>14</option>
            <option>16</option>
            <option>18</option>
            <option>20</option>
            <option>24</option>
            <option>28</option>
            <option>32</option>
        </select>

        <button
            id="boldBtn"
            type="button"
            class="xl-color-btn"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Bold">
            <b>B</b>
        </button>

        <button id="italicBtn" type="button" class="xl-color-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Italic">
            <i>I</i>
        </button>

        <button id="underlineBtn" type="button" class="xl-color-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Underline">
            <u>U</u>
        </button>
        <!-- Font Color -->
<div class="xl-color-picker">
    <button type="button" class="xl-color-btn" id="textColorBtn" title="Font Color" data-bs-toggle="tooltip" data-bs-placement="top" title="Color">
        <span class="fa fa-font"></span>
        <span class="xl-color-line" id="textColorLine"></span>
    </button>

    <input type="color" id="textColor" value="#000000">
</div>

<!-- Fill Color -->
<div class="xl-color-picker">
    <button type="button" class="xl-color-btn" id="fillColorBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Fill">
        <span class="fa fa-fill-drip"></span>
        <span class="xl-color-line" id="fillColorLine"></span>
    </button>

    <input type="color" id="fillColor" value="#ffffff">
</div>
<!-- Format Painter -->
<button id="formatPainterBtn" class="btn btn-light xl-color-btn" type="button" title="Format Painter" data-bs-toggle="tooltip">
    <i class="fa fa-paint-brush"></i>
</button>
        <button id="alignLeft" type="button" class="xl-color-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Left">
            <i class="fa fa-align-left"></i>
        </button>

        <button id="alignCenter" type="button" class="xl-color-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Center">
            <i class="fa fa-align-center"></i>
        </button>

        <button id="alignRight" type="button" class="xl-color-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Right">
            <i class="fa fa-align-right"></i>
        </button>

        <button id="undoBtn" type="button" class="xl-color-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Undo">
            <i class="fa fa-undo"></i>
        </button>

        <button id="redoBtn" type="button" class="xl-color-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Redo">
            <i class="fa fa-redo"></i>
        </button>
    </div>

</div>

<!-- Right Section -->
<input
    type="text"
    id="tableSearch"
    class="form-control mb-3"
    placeholder="Search..."
    style="border-radius:25px;max-width: fit-content;"
>

</div>
            
            <div class="sheet" id="sheet"></div>
        </div>
    </div>
    <div class="modal fade" id="createSheetModal" tabindex="-1" aria-labelledby="createSheetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createSheetModalLabel">Create New Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form method="POST" action="" id="createSheetForm">
                    <input type="hidden" name="action" value="create_sheet">
                    
                    <div class="modal-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="sheet_name" class="form-label fw-semibold">Sheet Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sheet_name" name="sheet_name" required minlength="1" maxlength="100" autofocus>
                            <div class="invalid-feedback">
                                Please enter a sheet name.
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn lufera-bg text-white">Create Sheet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="cellPreview" class="cell-preview"></div>
<script>
    document.addEventListener("keydown", function(e) {
    if (e.target.id === "commentText" && e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        e.stopPropagation();

        saveComment();
    }
});
    function addColumnResizeHandle(th, col) {

// Don't create duplicate handles
if (th.querySelector(".column-resize-handle")) {
    return;
}

const handle = document.createElement("div");
handle.className = "column-resize-handle";

handle.addEventListener("mousedown", function(e) {

    e.preventDefault();
    e.stopPropagation();

    const startX = e.clientX;
    const startWidth = th.getBoundingClientRect().width;

    document.body.classList.add("column-resizing");

    function onMouseMove(e) {

        const diff = e.clientX - startX;

        // Minimum width
        const newWidth = Math.max(220, startWidth + diff);

        // Remember width
        columnWidths[col] = newWidth;

        // Resize header
        th.style.width = newWidth + "px";
        th.style.minWidth = newWidth + "px";
        th.style.maxWidth = newWidth + "px";

        // Resize all cells in this column
        document
            .querySelectorAll(`#sheet [data-c="${col}"]`)
            .forEach(cell => {

                const td = cell.closest("td");

                if (td) {
                    td.style.width = newWidth + "px";
                    td.style.minWidth = newWidth + "px";
                    td.style.maxWidth = newWidth + "px";
                }
            });

        hasUnsavedChanges = true;
    }

    function onMouseUp() {

        document.body.classList.remove("column-resizing");

        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
    }

    document.addEventListener("mousemove", onMouseMove);
    document.addEventListener("mouseup", onMouseUp);
});

th.appendChild(handle);
} 
    </script>
<script>
    function applyColumnWidths() {

Object.keys(columnWidths).forEach(col => {

    const width = columnWidths[col];

    const th = document.querySelector(
        `thead th[data-c="${col}"]`
    );

    if (th) {
        th.style.width = width + "px";
        th.style.minWidth = width + "px";
        th.style.maxWidth = width + "px";
    }

    document
        .querySelectorAll(`#sheet [data-c="${col}"]`)
        .forEach(cell => {

            const td = cell.closest("td");

            if (td) {
                td.style.width = width + "px";
                td.style.minWidth = width + "px";
                td.style.maxWidth = width + "px";
            }
        });
});
}
</script>
<script>
document.getElementById("import-file").onclick = () => {
    document.getElementById("importInput").click();
};

document.getElementById("importInput").addEventListener("change", importSheet);

async function importSheet(e){

const file = e.target.files[0];

if(!file) return;

const buffer = await file.arrayBuffer();

const workbook = XLSX.read(buffer,{
    type:"array"
});

const sheet = workbook.Sheets[
    workbook.SheetNames[0]
];

const rows = XLSX.utils.sheet_to_json(sheet,{
    header:1,
    defval:""
});

if(rows.length===0) return;

COLS = Math.max(2, rows[0].length + 1);
ROWS = Math.max(1, rows.length - 1);

columnHeaders = {};
columnTypes = {};
Object.keys(data).forEach(k=>delete data[k]);

for(let c=2;c<=COLS;c++){

    columnHeaders[c]=rows[0][c-2] || "Column Field";

    columnTypes[c]={
        type:"text"
    };

}

for(let r=1;r<rows.length;r++){

    for(let c=2;c<=COLS;c++){

        const value=rows[r][c-2] ?? "";

        data[cellId(r,c)] = {
            raw: value.toString(),
            style: {
                fontFamily: "Arial",
                fontSize: "14px",
                fontWeight: "normal",
                fontStyle: "normal",
                textDecoration: "none",
                color: "#000000",
                background: "#ffffff",
                textAlign: "left"
            }
        };

    }

}

rebuildPreserveData();

hasUnsavedChanges=true;

Swal.fire({
    icon:"success",
    title:"Imported",
    text:"Imported successfully."
});

}
</script>

<script>
    document.addEventListener('click', function (e) {

const button = e.target.closest('.row-menu-btn');

// Clicked the ellipsis
if (button) {

    e.stopPropagation();

    const dropdown = button.closest('.row-dropdown');

    // Close other row dropdowns
    document.querySelectorAll('.row-dropdown.open').forEach(function (item) {
        if (item !== dropdown) {
            item.classList.remove('open');
        }
    });

    // Toggle this dropdown
    dropdown.classList.toggle('open');

    return;
}

// Clicked outside → close all row dropdowns
document.querySelectorAll('.row-dropdown.open').forEach(function (dropdown) {
    dropdown.classList.remove('open');
});

});

    function insertRowAbove(row){
        addRowBefore(row);
    }
    function insertRowBelow(row){
        addRowAfter(row);
    }
    let copiedRow = null;
    let isCutOperation = false;
    function copyRow(row){
        copiedRow = {};
        for(let c=2;c<=COLS;c++){
            copiedRow[c] = structuredClone(
                data[cellId(row,c)] || {raw:""}
            );
        }
    }

    function cutRow(row){
    copyRow(row);
    for(let c=2;c<=COLS;c++){
        data[cellId(row,c)] = {
            raw:""
        };
    }
    rebuildPreserveData();
    }

    function pasteRow(row){
        if(!copiedRow) return;
        for(let c=2;c<=COLS;c++){
            data[cellId(row,c)] = structuredClone(copiedRow[c]);
        }
        if(isCutOperation){
            for(let c=2;c<=COLS;c++){
                data[cellId(copiedRow.sourceRow,c)] = {
                    raw:""
                };
            }
            isCutOperation = false;
        }
        rebuildPreserveData();
    }

    const lockedRows = {};
    function lockRow(row){
    lockedRows[row] = !lockedRows[row];
    document
        .querySelectorAll(`.cell[data-r="${row}"]`)
        .forEach(cell=>{
            if(cell.dataset.c=="1") return;
            cell.contentEditable =
                !lockedRows[row];
        });
    }
    function printRow(row){
    let html="<table border='1'>";
    for(let c=2;c<=COLS;c++){
        html+=`
        <tr>
            <th>${columnHeaders[c]}</th>
            <td>${data[cellId(row,c)]?.raw||""}</td>
        </tr>`;

    }
    html+="</table>";
    const w=window.open("");
    w.document.write(html);
    w.print();
    }
</script>


<script>
document.getElementById('createSheetForm')?.addEventListener('submit', function(e) {
    const input = document.getElementById('sheet_name');
    if (!input.value.trim()) {
        e.preventDefault();
        input.classList.add('is-invalid');
    } else {
        input.classList.remove('is-invalid');
    }
});
</script>
<script>
let hasUnsavedChanges = false;
let isAddingNewColumn = false;
function Redirect() {
    window.location = "sheets.php";
}

document.getElementById("export-to-form").onclick = () => {
    let formTitle = <?= json_encode($sheetName) ?>; 
    let formId = <?= json_encode($form_id) ?>;   // Use sheet name as form title

    const tempFields = [];
    for (let c = 2; c <= COLS; c++) {
        const label = (columnHeaders[c] || defaultFieldName(c - 1)).trim();
        if (!label) continue;

        const colConfig = columnTypes[c] || { type: "text" };
        const colType   = colConfig.type;

        let formType = "text";
        if (colType === "number") formType = "number";
        else if (colType === "datetime-local") formType = "datetime-local";
        else if (colType === "select") formType = "select";
        else if (colType === "checkbox") formType = "checkbox";
        else if (colType === "email") formType = "email";
        // you can map more types if needed

        const options = (colType === "select" && colConfig.options?.length > 0)
            ? colConfig.options
            : (formType === "checkbox" ? [""] : ["Option 1", "Option 2"]);

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
    params.append('id', formId); // form id send

    window.location.href = `form_builder.php?${params.toString()}`;
};

/* ------------------------------------------------------------
   BASE VARIABLES (init first)
------------------------------------------------------------ */
let focusedCell = null;
let selectedCell = null;
const data = {};
let ROWS = 10;
let COLS = 4;
let columnHeaders = {};
let columnTypes = {};
let columnWidths = {};
let currentColumnForType = null;

// IMPORTANT: Declared early to avoid initialization errors
const rowComments = {};        // { rowNumber: commentCount }
const rowAttachments = {};     // { rowNumber: attachmentCount }
const rowReminders  = {};
const columnSortState = {};
let activeRow = null;
let activeAttachRow = null;
let activeSheetId = <?= $sheetId ?>;
let undoStack = [];
let redoStack = [];

const MAX_HISTORY = 50;

function cloneSheetData() {
    return structuredClone(data);
}

function saveHistory() {
    undoStack.push(cloneSheetData());

    if (undoStack.length > MAX_HISTORY) {
        undoStack.shift();
    }

    // Once a new change is made,
    // redo history is no longer valid.
    redoStack = [];

    updateUndoRedoButtons();
}

function undo() {

if (undoStack.length === 0) return;

// Save current state for redo
redoStack.push(cloneSheetData());

const previousState = undoStack.pop();

// Restore previous state
Object.keys(data).forEach(key => delete data[key]);

Object.assign(
    data,
    structuredClone(previousState)
);

rebuildPreserveData();

updateUndoRedoButtons();

hasUnsavedChanges = true;
}

function redo() {

if (redoStack.length === 0) return;

// Save current state for undo
undoStack.push(cloneSheetData());

const nextState = redoStack.pop();

Object.keys(data).forEach(key => delete data[key]);

Object.assign(
    data,
    structuredClone(nextState)
);

rebuildPreserveData();

updateUndoRedoButtons();

hasUnsavedChanges = true;
}

document.getElementById("undoBtn").addEventListener("click", undo);

document.getElementById("redoBtn").addEventListener("click", redo);

function updateUndoRedoButtons() {

const undoBtn = document.getElementById("undoBtn");
const redoBtn = document.getElementById("redoBtn");

if (undoBtn) {
    undoBtn.disabled = undoStack.length === 0;
}

if (redoBtn) {
    redoBtn.disabled = redoStack.length === 0;
}
}
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
columnWidths = loaded.columnWidths || {};

    if (loaded.cells) {
        Object.keys(loaded.cells).forEach(oldKey => {
            // Try to detect old rXcY format
            const match = oldKey.match(/^r(\d+)c(\d+)$/);
            if (match) {
                const row = parseInt(match[1], 10);
                const col = parseInt(match[2], 10);
                const newKey = colName(col) + row;          // ← B1, C1, B2, ...
                const cell = loaded.cells[oldKey];

                if (cell && typeof cell === "object") {

                    data[newKey] = cell;

                } else {

                    data[newKey] = {
                        raw: cell
                    };

                }
            } else {
                // Already in A1-style or unknown → keep as is
                const cell = loaded.cells[oldKey];

if (cell && typeof cell === "object") {

    data[oldKey] = cell;

} else {

    data[oldKey] = {
        raw: cell
    };

}
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
        case "email":
            input = document.createElement("input");
            input.type = "email";
            input.value = saved;
            cellEl.classList.add("email");
            break;

        case "datetime-local":
            input = document.createElement("input");
            input.type = "datetime-local";
            input.value = saved;
            break;

        case "checkbox":
            input = document.createElement("input");
            input.type = "checkbox";
            input.classList.add("form-check-input");
            input.checked = saved === "true";
            cellEl.classList.add("checkbox");
            break;

        case "select":
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
            input.type = "file";
            input.placeholder = "Enter file URL or path";
            input.value = saved;
            cellEl.classList.add("file");
            break;

        case "text":
        default:
            cellEl.textContent = saved;
            cellEl.dataset.fulltext = saved;
            cellEl.contentEditable = true;
            cellEl.classList.add("text");
            applyStyle(cellEl);
            return;
    }

    input.addEventListener("change", () => {

const value = type === "checkbox"
    ? input.checked
    : input.value;

if (!data[id]) data[id] = {};

data[id].raw = value.toString();

cellEl.dataset.fulltext = value.toString();

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
    //table.className = "table table-striped table-hover";
    const thead = document.createElement("thead");
    const hRow = document.createElement("tr");
    hRow.appendChild(Object.assign(document.createElement("th"), { className: "row-header" }));

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
            th.style.width = "30px";
            th.style.height = "40px";
            th.style.alignContent = "center";
            th.style.textAlign = "center";
        } else {
            // Container for name and trash
            const wrapper = document.createElement("div");
            wrapper.style.display = "flex";
            wrapper.style.alignItems = "center";
            wrapper.style.width = "100%";
            wrapper.style.position = "relative";
            wrapper.style.gap = "6px";
            wrapper.style.minWidth = "0";
            // Column name
            const nameSpan = document.createElement("span");
            nameSpan.textContent = columnHeaders[c] || "Column Field";
            
            const sortSpan = document.createElement("span");
sortSpan.className = "fa fa-sort sort-col-icon";
sortSpan.title = "Sort";
sortSpan.style.cursor = "pointer";
sortSpan.style.fontSize = "12px";
sortSpan.onclick = (e) => {
    e.stopPropagation();

    columnSortState[c] =
        columnSortState[c] === "asc" ? "desc" : "asc";

    sortColumn(c, columnSortState[c]);

    sortSpan.className =
        columnSortState[c] === "asc"
        ? "fa fa-sort-up sort-col-icon"
        : "fa fa-sort-down sort-col-icon";
};
            const menuWrapper = document.createElement("div");
menuWrapper.className = "dropdown";

const menuBtn = document.createElement("span");
menuBtn.className = "fa fa-ellipsis-v";
menuBtn.style.cursor = "pointer";

const menu = document.createElement("div");
menu.className = "dropdown-menu";

const addBtn = document.createElement("button");
addBtn.innerHTML = '<span class="fa fa-plus"></span> Add Column';

addBtn.onclick = (e)=>{
    e.stopPropagation();
    isAddingNewColumn = true;
    insertAfterColumn = c;
    openColumnTypeModal(c + 1);
    menuWrapper.classList.remove("open");
};

const deleteBtn = document.createElement("button");
deleteBtn.innerHTML = '<span class="fa fa-trash-alt"></span> Remove Column';

deleteBtn.onclick = (e)=>{
    e.stopPropagation();
    deleteColumn(c);
    menuWrapper.classList.remove("open");
};

menu.append(addBtn, deleteBtn);
menuWrapper.append(menuBtn, menu);

wrapper.appendChild(nameSpan);
wrapper.appendChild(sortSpan);
wrapper.appendChild(menuWrapper);
menuBtn.onclick = (e) => {
    e.preventDefault();
    e.stopPropagation();

    // Close all other column dropdowns
    document.querySelectorAll("thead .dropdown.open").forEach(d => {
        if (d !== menuWrapper) {
            d.classList.remove("open");
        }
    });

    // Keep this dropdown open
    menuWrapper.classList.toggle("open");
};

menu.onclick = (e) => {
    e.stopPropagation();
};

menuBtn.onclick = (e)=>{
    e.stopPropagation();

    document.querySelectorAll(".dropdown")
        .forEach(d => {
            if (d !== menuWrapper)
                d.classList.remove("open");
        });

    menuWrapper.classList.toggle("open");
};

th.appendChild(wrapper);

// Add resize handle
if (c !== 1) {
    addColumnResizeHandle(th, c);
}
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

            container.addEventListener("mouseenter", showCellPreview);
            container.addEventListener("mousemove", moveCellPreview);
            container.addEventListener("mouseleave", hideCellPreview);

            if (c === 1) {
                container.innerHTML = `
                    <span class="task-text" contenteditable="false"></span>
                    <span class="task-actions">
                        <div class="row-dropdown">
                        <span class="fa fa-ellipsis-v row-menu-btn"></span>
                        <div class="row-dropdown-menu">
                            <button onclick="insertRowAbove(${r})">
                                <span class="fa fa-arrow-up mt-4 me-4"></span>
                                Insert Above
                            </button>
                            <button onclick="insertRowBelow(${r})">
                                <span class="fa fa-arrow-down mt-4 me-4"></span>
                                Insert Below
                            </button>
                            <hr>
                            <button onclick="cutRow(${r})">
                                <span class="fa fa-scissors mt-4 me-4"></span>
                                Cut Row
                            </button>
                            <button onclick="copyRow(${r})">
                                <span class="fa fa-copy mt-4 me-4"></span>
                                Copy Row
                            </button>
                            <button onclick="pasteRow(${r})">
                                <span class="fa fa-paste mt-4 me-4"></span>
                                Paste Row
                            </button>
                            <button onclick="deleteRow(${r})">
                                <span class="fa fa-trash mt-4 me-4"></span>
                                Delete Row
                            </button>
                            <hr>
                            <button onclick="openAttachments(${r})">
                                <span class="fa fa-paperclip mt-4 me-4"></span>
                                Attachments
                            </button>
                            <button onclick="openComments(${r})">
                                <span class="fa fa-comment mt-4 me-4"></span>
                                Comments
                            </button>
                            <button onclick="openReminderModal(${r})">
                                <span class="fa fa-bell mt-4 me-4"></span>
                                Reminder
                            </button>
                            <hr>
                            <button onclick="lockRow(${r})">
                                <span class="fa fa-lock mt-4 me-4"></span>
                                Lock
                            </button>
                            <button onclick="printRow(${r})">
                                <span class="fa fa-print mt-4 me-4"></span>
                                Print
                            </button>
                        </div>
                    </div>
                        <span class="comment-icon fa fa-message cursor-pointer" title="Comments" onclick="openComments(${r})"></span>
                        <span class="attach-icon fa fa-paperclip cursor-pointer" title="Attachments" onclick="openAttachments(${r})"></span>
                        <!--  ──► NEW ──►  -->
                        <span class="bell-icon fa fa-bell cursor-pointer ${rowReminders[r] > 0 ? 'has-reminder' : ''}" 
                        title="${rowReminders[r] > 0 ? 'Has reminder(s)' : 'Set Reminder'}" 
                        onclick="openReminderModal(${r})"></span>
                        <!--  ─────────────── -->
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
    applyColumnWidths();
}

document.addEventListener("click", function(e) {

if (!e.target.closest("thead .dropdown")) {
    document.querySelectorAll("thead .dropdown.open")
        .forEach(dropdown => {
            dropdown.classList.remove("open");
        });
}

});

document.getElementById("tableSearch")
    .addEventListener("input", filterTable);

    function filterTable() {

const keyword = document
    .getElementById("tableSearch")
    .value
    .toLowerCase()
    .trim();

const rows = document.querySelectorAll("#sheet tbody tr");

rows.forEach(row => {

    let found = false;

    row.querySelectorAll(".cell").forEach(cell => {

        if (found) return;

        let text = "";

        const input = cell.querySelector("input, select");

        if (input) {

            if (input.type === "checkbox") {
                text = input.checked ? "true" : "false";
            } else {
                text = input.value;
            }

        } else {

            text = data[cell.id]?.raw || cell.textContent;

        }

        if (text.toString().toLowerCase().includes(keyword)) {
            found = true;
        }

    });

    row.style.display = found || keyword === ""
        ? ""
        : "none";

});

}

const preview = document.getElementById("cellPreview");

function showCellPreview(e) {

const cell = e.currentTarget;
const preview = document.getElementById("cellPreview");

if (!preview) return;

const fullText = cell.dataset.fulltext;

if (!fullText || !fullText.trim()) {
    preview.style.display = "none";
    return;
}

preview.textContent = fullText;

// Show it temporarily so we can measure its actual size
preview.style.display = "block";
preview.style.visibility = "hidden";

const cellRect = cell.getBoundingClientRect();
const previewRect = preview.getBoundingClientRect();

const margin = 8;

const viewportWidth = window.innerWidth;
const viewportHeight = window.innerHeight;

let left = cellRect.left;

// Prevent popup from going outside the right side
if (left + previewRect.width > viewportWidth - margin) {
    left = viewportWidth - previewRect.width - margin;
}

// Prevent popup from going outside the left side
if (left < margin) {
    left = margin;
}

// Space below the cell
const spaceBelow = viewportHeight - cellRect.bottom;

// Space above the cell
const spaceAbove = cellRect.top;

let top;

// Prefer below when there is enough room
if (spaceBelow >= previewRect.height + margin) {

    top = cellRect.bottom + margin;

}
// Otherwise show above
else if (spaceAbove >= previewRect.height + margin) {

    top = cellRect.top - previewRect.height - margin;

}
// Neither side has enough room
else {

    // Choose the side with more available space
    if (spaceBelow >= spaceAbove) {

        top = cellRect.bottom + margin;

    } else {

        top = Math.max(
            margin,
            cellRect.top - previewRect.height - margin
        );
    }
}

// Final vertical safety check
top = Math.max(
    margin,
    Math.min(
        top,
        viewportHeight - previewRect.height - margin
    )
);

preview.style.left = `${left}px`;
preview.style.top = `${top}px`;

preview.style.visibility = "visible";
}

function moveCellPreview(e) {

const preview = document.getElementById("cellPreview");

if (!preview || preview.style.display === "none") {
    return;
}

showCellPreview(e);
}

function hideCellPreview() {

const preview = document.getElementById("cellPreview");

if (preview) {
    preview.style.display = "none";
    preview.style.visibility = "hidden";
}
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

    if (config.type === "select") {
        dropdownDiv.style.display = "block";
        dropdownTextarea.value = (config.options || []).join(", ");
    } else {
        dropdownDiv.style.display = "none";
        dropdownTextarea.value = "";
    }

    // Update dropdown visibility when type changes
    typeSelect.onchange = function() {
        dropdownDiv.style.display = (this.value === "select") ? "block" : "none";
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

    if (selectedType === "select") {
        const raw = document.getElementById("dropdownValues").value;
        const options = raw.split(/[\n,]+/)
                          .map(v => v.trim())
                          .filter(v => v.length > 0);

        columnTypes[currentColumnForType] = {
            type: "select",
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
let editingCell = null;
let editingCellHistorySaved = false;

function onFocus(e) {
    document
        .querySelectorAll(".cell")
        .forEach(c => c.classList.remove("selected"));

    selectedCell = e.target.closest(".cell");

    if (!selectedCell) return;

    selectedCell.classList.add("selected");
    loadToolbarState();

    // Start a new editing session
    editingCell = selectedCell;
    editingCellHistorySaved = false;
}

function onEdit(e) {
    const cell = e.target.closest(".cell");

    if (!cell || cell.dataset.c == 1) return;

    /*
     * IMPORTANT:
     * Save the state only once when editing this cell starts.
     * Do NOT call saveHistory() for every input/keystroke.
     */
    if (editingCell !== cell) {
        editingCell = cell;
        editingCellHistorySaved = false;
    }

    if (!editingCellHistorySaved) {
        saveHistory();
        editingCellHistorySaved = true;
    }

    hasUnsavedChanges = true;

    const id = cell.id;
    const col = parseInt(cell.dataset.c);
    const config = columnTypes[col] || { type: "text" };
    const type = config.type;

    let value;

    if (type === "checkbox") {
        value = e.target.checked;
    }
    else if (
        ["number", "datetime-local", "select", "email"].includes(type)
    ) {
        value = e.target.value;
    }
    else {
        value = cell.textContent;
    }

    if (!data[id]) {
        data[id] = {};
    }

    data[id].raw = value.toString();
    cell.dataset.fulltext = value.toString();

    recalcAll();
}

function getCellStyle(cell){
if(!data[cell.id])
    data[cell.id]={raw:""};
if(!data[cell.id].style){
    data[cell.id].style={
    fontFamily:"Arial",
    fontSize:"14px",
    fontWeight:"normal",
    fontStyle:"normal",
    textDecoration:"none",
    color:"#000000",
    background:"#ffffff",
    textAlign:"left"

    };
}
return data[cell.id].style;
}

function applyStyle(cell){

if(!cell) return;

const style=getCellStyle(cell);

cell.style.fontFamily=style.fontFamily;
cell.style.fontSize=style.fontSize;

cell.style.fontWeight=style.fontWeight;
cell.style.fontStyle=style.fontStyle;

cell.style.textDecoration=style.textDecoration;

cell.style.color=style.color;

cell.style.background=style.background;

cell.style.textAlign=style.textAlign;

}

function loadToolbarState(){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
fontFamily.value=style.fontFamily;
fontSize.value=parseInt(style.fontSize);
boldBtn.classList.toggle(
    "active",
    style.fontWeight==="bold"
);
italicBtn.classList.toggle(
    "active",
    style.fontStyle==="italic"
);
underlineBtn.classList.toggle(
    "active",
    style.textDecoration==="underline"
);
textColor.value=style.color;

fillColor.value=style.background;

alignLeft.classList.remove("active");
alignCenter.classList.remove("active");
alignRight.classList.remove("active");

switch(style.textAlign){

    case "center":

        alignCenter.classList.add("active");

        break;

    case "right":

        alignRight.classList.add("active");

        break;

    default:

        alignLeft.classList.add("active");

}
}

fontFamily.onchange=function(){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
style.fontFamily=this.value;
applyStyle(selectedCell);
};

fontSize.onchange=function(){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
style.fontSize=this.value+"px";
applyStyle(selectedCell);
};

boldBtn.onclick=function(){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
style.fontWeight=
    style.fontWeight==="bold"
    ?"normal"
    :"bold";
applyStyle(selectedCell);
loadToolbarState();
};

italicBtn.onclick=function(){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
style.fontStyle=
    style.fontStyle==="italic"
    ?"normal"
    :"italic";
applyStyle(selectedCell);
loadToolbarState();
};

underlineBtn.onclick=function(){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
style.textDecoration=
    style.textDecoration==="underline"
    ?"none"
    :"underline";
applyStyle(selectedCell);
loadToolbarState();
};


textColor.onchange=function(){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
style.color=this.value;
applyStyle(selectedCell);
};

fillColor.onchange=function(){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
style.background=this.value;
applyStyle(selectedCell);
};

function setAlignment(type){
if(!selectedCell) return;
const style=getCellStyle(selectedCell);
style.textAlign=type;
applyStyle(selectedCell);
loadToolbarState();
}

alignLeft.onclick=function(){
setAlignment("left");
};
alignCenter.onclick=function(){
setAlignment("center");
};
alignRight.onclick=function(){
setAlignment("right");
};

function refreshSheet() {
    if (hasUnsavedChanges) {
        Swal.fire({
            title: "Discard changes?",
            text: "You have unsaved changes.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Refresh"
        }).then(result => {
            if (result.isConfirmed) {
                location.reload();
            }
        });
    } else {
        location.reload();
    }
}

function renameSheet() {
Swal.fire({
    title: "Rename Sheet",
    input: "text",
    inputValue: document.querySelector(".sheet_title").textContent,
    showCancelButton: true,
    confirmButtonText: "Save"
}).then(result => {
    if (!result.isConfirmed) return;
    fetch("rename_sheet.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body:
            "id=" + activeSheetId +
            "&name=" + encodeURIComponent(result.value)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.querySelector(".sheet_title").textContent =
                result.value;
            Swal.fire(
                "Success",
                "Sheet renamed.",
                "success"
            );
        } else {
            Swal.fire(
                "Error",
                res.message,
                "error"
            );
        }
    });
});
}

function printSheet(){
const printWindow = window.open("", "_blank");
printWindow.document.write(`
    <html>
    <head>
    <title>${document.querySelector(".sheet_title").textContent}</title>
    <style>
        table{
            border-collapse:collapse;
            width:100%;
        }
        td,th{
            border:1px solid #999;
            padding:8px;
        }
    </style>
    </head>
    <body>
    <h2>
        ${document.querySelector(".sheet_title").textContent}
    </h2>
    ${document.querySelector("#sheet").innerHTML}
    </body>
    </html>
`);

printWindow.document.close();
printWindow.focus();
printWindow.print();
}

function onKeyDown(e) {
    const cell = e.target.closest(".cell");
    if (!cell) return;

    const r = parseInt(cell.dataset.r);
    const c = parseInt(cell.dataset.c);

    const isTextEditing =
        cell.contentEditable === "true" ||
        e.target.isContentEditable;

    if (isTextEditing) {
        if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
            return;
        }
    }

    let nr = r;
    let nc = c;

    // Enter → next row
    if (e.key === "Enter") {
        e.preventDefault();
        nr++;
    }

    // Up / Down → next/previous row
    if (e.key === "ArrowDown") {
        e.preventDefault();
        nr++;
    }

    if (e.key === "ArrowUp") {
        e.preventDefault();
        nr--;
    }

    if (!isTextEditing && e.key === "ArrowRight") {
        e.preventDefault();
        nc++;
    }

    if (!isTextEditing && e.key === "ArrowLeft") {
        e.preventDefault();
        nc--;
    }

    // TAB → next cell
    if (e.key === "Tab") {
        e.preventDefault();

        nc += e.shiftKey ? -1 : 1;

        if (nc > COLS) {
            nc = 1;
            nr++;
        }

        if (nc < 1) {
            nc = COLS;
            nr--;
        }
    }

    nr = Math.max(1, Math.min(nr, ROWS));
    nc = Math.max(1, Math.min(nc, COLS));

    const next = document.getElementById(cellId(nr, nc));

    if (next) {
        next.focus();

        const input = next.querySelector("input, select");

        if (input) {
            input.focus();
        }
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
    id: activeSheetId,
    rows: ROWS,
    cols: COLS,
    headers: [],
    columnTypes: columnTypes,
    columnWidths: columnWidths,
    cells: {}
};

    // Collect visible header names (from UI)
    document.querySelectorAll("thead th[data-c]").forEach(th => {
        if (th.dataset.c && th.dataset.c != "1") {
            const nameSpan = th.querySelector("span");
            payload.headers.push(nameSpan ? nameSpan.textContent.trim() : "");
        }
    });

    // Collect cell data (including styles)
    document.querySelectorAll(".cell").forEach(cell => {

    if (cell.dataset.c == "1") return;

    const cellData = data[cell.id];

    if (!cellData) return;

    const hasRaw = (cellData.raw ?? "").toString().trim() !== "";
    const hasStyle = cellData.style && Object.keys(cellData.style).length > 0;

    if (hasRaw || hasStyle) {
        payload.cells[cell.id] = structuredClone(cellData);
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
loadCountsAndRefreshIcons();
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

            if (type === "select") {
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
    const sheetName = document.querySelector("h6.sheet_title")?.textContent?.trim() || "Exported_Sheet";
    XLSX.writeFile(wb, `${sheetName}.xlsx`);

    // ✅ NEW — Log activity in backend
    fetch("log_activity.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            sheet_name: sheetName
        })
    })
    .catch(err => console.error("Activity log failed:", err));
    
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
            columnWidths = sheet.columnWidths || {};
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

function formatDate(dateString) {
    const d = new Date(dateString);

    return d.toLocaleString("en-GB", {
        timeZone: "Asia/Kolkata",
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true
    }).replace(",", "");
}
/* ------------------------------------------------------------
   COMMENTS & ATTACHMENTS
------------------------------------------------------------ */
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
            <small>${formatDate(c.created_at)} • 
                <button class="btn btn-link btn-sm p-0 text-primary" onclick="showReplyBox(${c.id}, this)">Reply</button>
            </small>
        `;
        list.appendChild(div);

        // Render replies
        if (c.replies && c.replies.length > 0) {
            c.replies.forEach(r => {
                const rd = document.createElement("div");
                rd.className = "comment reply";
                rd.innerHTML = `
                    <div>${r.comment}</div>
                    <small>${formatDate(r.created_at)}</small>
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
function shiftRowActivityForInsert(insertRow) {
    // Move activity mappings down from bottom to top
    for (let r = ROWS; r >= insertRow; r--) {

        if (rowComments[r] !== undefined) {
            rowComments[r + 1] = rowComments[r];
        } else {
            delete rowComments[r + 1];
        }

        if (rowAttachments[r] !== undefined) {
            rowAttachments[r + 1] = rowAttachments[r];
        } else {
            delete rowAttachments[r + 1];
        }

        if (rowReminders[r] !== undefined) {
            rowReminders[r + 1] = rowReminders[r];
        } else {
            delete rowReminders[r + 1];
        }
    }

    // New row has no activity
    delete rowComments[insertRow];
    delete rowAttachments[insertRow];
    delete rowReminders[insertRow];
}
function addRowBefore(row) {

    // Save history before changing the sheet
    saveHistory();

    // Increase total rows
    ROWS++;

    // Move row data down
    for (let r = ROWS; r > row; r--) {
        for (let c = 2; c <= COLS; c++) {

            const newId = cellId(r, c);
            const oldId = cellId(r - 1, c);

            data[newId] = data[oldId]
                ? structuredClone(data[oldId])
                : { raw: "" };
        }
    }

    // Clear the newly inserted row
    for (let c = 2; c <= COLS; c++) {
        data[cellId(row, c)] = { raw: "" };
    }

    // IMPORTANT:
    // Move comments, attachments and reminders with their parent row
    shiftRowActivityForInsert(row);

    rebuildPreserveData();

    refreshAllActivityIcons();
    }

    async function addRowAfter(row) {

const insertAt = row + 1;

try {

    const response = await fetch("shift_row_activity.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            sheet_id: activeSheetId,
            insert_at: insertAt
        })
    });

    const result = await response.json();

    if (!result.success) {
        throw new Error(result.error || "Failed to shift row activity");
    }

    ROWS++;

    // Shift sheet data
    for (let r = ROWS; r > insertAt; r--) {

        for (let c = 1; c <= COLS; c++) {

            const oldId = cellId(r - 1, c);
            const newId = cellId(r, c);

            if (data[oldId]) {
                data[newId] = structuredClone(data[oldId]);
            } else {
                delete data[newId];
            }
        }
    }

    // Clear inserted row
    for (let c = 1; c <= COLS; c++) {
        delete data[cellId(insertAt, c)];
    }

    rebuildPreserveData();

    loadCountsAndRefreshIcons();

} catch (error) {

    console.error("Insert row below failed:", error);

    Swal.fire({
        icon: "error",
        title: "Insert failed",
        text: error.message || "Could not insert row."
    });
}
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

function sortColumn(col, direction = "asc"){

const rows = [];

for(let r=1;r<=ROWS;r++){

    const id = cellId(r,col);

    rows.push({
        row:r,
        value:data[id]?.raw || ""
    });

}

rows.sort((a,b)=>
    a.value.toString().localeCompare(
        b.value.toString(),
        undefined,
        {numeric:true,sensitivity:"base"}
    )
);
if (direction === "desc") {
    rows.reverse();
}
const snapshot = {};

for(let r=1;r<=ROWS;r++){

    for(let c=1;c<=COLS;c++){

        snapshot[cellId(r,c)] = data[cellId(r,c)];

    }

}

rows.forEach((item,index)=>{

    const targetRow=index+1;

    for(let c=1;c<=COLS;c++){

        data[cellId(targetRow,c)] =
            snapshot[cellId(item.row,c)];

    }

});

rebuildPreserveData();
}

// Dropdown toggle logic
document.querySelectorAll(".dropdown-btn").forEach(btn => {
    btn.addEventListener("click", e => {
        e.stopPropagation();

        // Close others
        document.querySelectorAll(".select").forEach(d => {
            if (d !== btn.parentElement) d.classList.remove("open");
        });

        btn.parentElement.classList.toggle("open");
    });
});

// Close dropdowns when clicking outside
document.addEventListener("click", () => {
    document.querySelectorAll(".select").forEach(d => d.classList.remove("open"));
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
                                    <div style="font-size:12px; color:#6b7280;">${formatDate(r.safeDate)}</div>
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
    const email = document.getElementById("reminderEmail").value.trim();

    if (!dateStr) {
        alert("Please select a reminder date.");
        return;
    }
    if (!msg) {
        alert("Please enter a reminder message.");
        return;
    }
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert("Please enter a valid email address.");
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
                message: msg,
                recipient_email: email
            })
        });

        const raw = await res.text();
        let result = null;
        try {
            result = JSON.parse(raw);
        } catch (e) {
            console.error("save_reminder.php returned non-JSON:", raw);
            throw new Error("Invalid JSON response from server");
        }

        if (!res.ok) {
            throw new Error(result?.error || `HTTP ${res.status}`);
        }
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
        alert(err?.message || "Network error");
    }
}
</script>

<div id="sidePanelBackdrop" class="side-backdrop"></div>

<div id="commentPanel" class="comment-panel">
    <div class="comment-header">
        <strong>Comments</strong>
        <button onclick="closeComments()">✖</button>
    </div>

    <div id="commentList" class="comment-list"></div>

    <div class="comment-input">
        <textarea id="commentText" placeholder="Write a comment..."></textarea>
        <button class="btn lufera-bg lufera-text float-end mt-10" onclick="saveComment()" id="commentSubmit">Send</button>
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
        <button class="btn lufera-bg lufera-text mt-10" onclick="uploadAttachment()">Upload</button>
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
            <option value="email">Email</option>
            <option value="number">Number</option>
            <option value="datetime-local">DateTime</option>
            <!-- <option value="checkbox">Checkbox</option> -->
            <option value="select">Dropdown List</option>
            <!--<option value="file">File (URL / path)</option>-->
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
    document.getElementById("commentSubmit")?.click();
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
function openSidePanel(panelId) {
    const panel = document.getElementById(panelId);
    const backdrop = document.getElementById("sidePanelBackdrop");

    if (!panel || !backdrop) return;

    // Show panel + backdrop
    panel.classList.add("open");
    backdrop.classList.add("active");

    // Optional: prevent body scroll
    document.body.classList.add("side-panel-open");

    // Load content depending on which panel
    if (panelId === "commentPanel") {
        loadComments();
    } else if (panelId === "attachmentPanel") {
        loadAttachments();
    }
}

function closeSidePanel() {
    const panels = document.querySelectorAll(".comment-panel");
    const backdrop = document.getElementById("sidePanelBackdrop");

    panels.forEach(p => p.classList.remove("open"));
    backdrop.classList.remove("active");

    // Restore body scroll
    document.body.classList.remove("side-panel-open");

    // Optional: clear active row tracking
    activeRow = null;
    activeAttachRow = null;
}

// Close when clicking backdrop
document.getElementById("sidePanelBackdrop")?.addEventListener("click", () => {
    closeSidePanel();
});

// Update your open functions to use the shared logic
function openComments(row) {
    activeRow = row;
    openSidePanel("commentPanel");
}

function openAttachments(row) {
    activeAttachRow = row;
    openSidePanel("attachmentPanel");
}

// You can still keep individual close functions if needed,
// but now they can just call the shared one:
function closeComments() {
    closeSidePanel();
}

function closeAttachments() {
    closeSidePanel();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    const textColorBtn  = document.getElementById("textColorBtn");
const textColor     = document.getElementById("textColor");
const textColorLine = document.getElementById("textColorLine");

const fillColorBtn  = document.getElementById("fillColorBtn");
const fillColor     = document.getElementById("fillColor");
const fillColorLine = document.getElementById("fillColorLine");


/* Font Color */
textColorBtn.addEventListener("click", () => {
    textColor.click();
});

textColor.addEventListener("input", function () {
    textColorLine.style.background = this.value;

    // Your existing formatting logic
    applyTextColor(this.value);
});


/* Fill Color */
fillColorBtn.addEventListener("click", () => {
    fillColor.click();
});

fillColor.addEventListener("input", function () {
    fillColorLine.style.background = this.value;

    // Your existing formatting logic
    applyFillColor(this.value);
});
function applyTextColor(color) {
    if (!selectedCell) return;

    selectedCell.style.color = color;

    if (!data[selectedCell.id]) {
        data[selectedCell.id] = {};
    }

    if (!data[selectedCell.id].style) {
        data[selectedCell.id].style = {};
    }

    data[selectedCell.id].style.color = color;
}


function applyFillColor(color) {
    if (!selectedCell) return;

    selectedCell.style.backgroundColor = color;

    if (!data[selectedCell.id]) {
        data[selectedCell.id] = {};
    }

    if (!data[selectedCell.id].style) {
        data[selectedCell.id].style = {};
    }

    data[selectedCell.id].style.background = color;
}

let formatPainterActive = false;
let formatPainterStyle = null;
let formatPainterSourceId = null;

document.getElementById("formatPainterBtn").addEventListener("mousedown", function(e) {
    // Prevent the toolbar button from taking focus away from the cell
    e.preventDefault();
});

document.getElementById("formatPainterBtn").addEventListener("click", function(e) {

    if (!selectedCell) {
        return;
    }

    // Remember the source cell permanently
    formatPainterSourceId = selectedCell.id;

    const sourceData = data[formatPainterSourceId];

    if (!sourceData || !sourceData.style) {
        return;
    }

    // Copy the source formatting
    formatPainterStyle = structuredClone(sourceData.style);

    formatPainterActive = true;

    this.classList.add("active");

    document.querySelectorAll(".cell").forEach(cell => {
        cell.style.cursor = "copy";
    });
});


document.addEventListener("click", function(e) {

    if (!formatPainterActive) {
        return;
    }

    const targetCell = e.target.closest(".cell");

    if (!targetCell) {
        return;
    }

    // Don't paste back into the source cell
    if (targetCell.id === formatPainterSourceId) {
        return;
    }

    // Save undo history BEFORE changing the target
    saveHistory();

    if (!data[targetCell.id]) {
        data[targetCell.id] = {};
    }

    // Copy the complete style
    data[targetCell.id].style =
        structuredClone(formatPainterStyle);

    // Apply style to the target cell
    applyStyle(targetCell);

    hasUnsavedChanges = true;

    // Select the target cell
    selectedCell = targetCell;

    // Turn painter off
    formatPainterActive = false;
    formatPainterStyle = null;
    formatPainterSourceId = null;

    document
        .getElementById("formatPainterBtn")
        .classList.remove("active");

    document.querySelectorAll(".cell").forEach(cell => {
        cell.style.cursor = "";
    });
});

document.addEventListener("click", function(e) {

if (!formatPainterActive) return;

const targetCell = e.target.closest(".cell");

if (!targetCell) return;

// Don't apply to the original cell
if (targetCell.id === selectedCell?.id) return;

if (!data[targetCell.id]) {
    data[targetCell.id] = {};
}

data[targetCell.id].style = structuredClone(formatPainterStyle);

// Apply formatting visually
applyStyle(targetCell);

// Turn off painter
formatPainterActive = false;
formatPainterStyle = null;

document.getElementById("formatPainterBtn")
    .classList.remove("active");

document.querySelectorAll(".cell").forEach(cell => {
    cell.style.cursor = "";
});
});

document.addEventListener("DOMContentLoaded", () => {
    document
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(el => {
            new bootstrap.Tooltip(el, {
                placement: "bottom",
                fallbackPlacements: [],
                boundary: "viewport"
            });
        });
});

document.addEventListener("keydown", function(e) {

if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "z") {
    e.preventDefault();

    if (e.shiftKey) {
        redo();
    } else {
        undo();
    }
}

if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "y") {
    e.preventDefault();
    redo();
}

});

async function addRowBefore(row) {

try {

    // First update database activity references
    const response = await fetch("shift_row_activity.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            sheet_id: activeSheetId,
            insert_at: row
        })
    });

    const result = await response.json();

    if (!result.success) {
        throw new Error(result.error || "Failed to shift row activity");
    }

    // Increase total rows
    ROWS++;

    // Shift sheet data down
    for (let r = ROWS; r > row; r--) {

        for (let c = 2; c <= COLS; c++) {

            const newId = cellId(r, c);
            const oldId = cellId(r - 1, c);

            data[newId] = data[oldId]
                ? structuredClone(data[oldId])
                : { raw: "" };
        }
    }

    // Clear the newly inserted row
    for (let c = 2; c <= COLS; c++) {
        data[cellId(row, c)] = { raw: "" };
    }

    rebuildPreserveData();

    // Reload activity counts from DB
    loadCountsAndRefreshIcons();

} catch (error) {

    console.error("Insert row above failed:", error);

    Swal.fire({
        icon: "error",
        title: "Insert failed",
        text: error.message || "Could not insert row."
    });
}
}
</script>
</body>
</html>
<?php include './partials/layouts/layoutBottom.php'; ?>