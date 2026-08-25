<?php
ob_start();
date_default_timezone_set('Asia/Kolkata');
include './partials/layouts/layoutTop.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'delete'
) {
    $id = intval($_POST['id'] ?? 0);
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    if ($id <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid sheet ID'
        ]);
        exit;
    }
    $stmt2 = $conn->prepare("DELETE FROM `sheets` WHERE id = ?");
    if (!$stmt2) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Prepare failed: ' . $conn->error
        ]);
        exit;
    }
    $stmt2->bind_param('i', $id);
    if ($stmt2->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Sheet Deleted Successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Delete failed: ' . $stmt2->error
        ]);
    }
    $stmt2->close();
    exit;
}
?>

<?php

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

// Fetch sheets (your existing code)
$sheets = [];
$res = $conn->query("SELECT id, name, updated_at FROM sheets ORDER BY updated_at DESC");
while ($row = $res->fetch_assoc()) {
    $sheets[] = $row;
}

// ────────────────────────────────────────────────
// Fetch ALL unread reminders for the offcanvas
// ────────────────────────────────────────────────
$today = date('Y-m-d');

$remindersQuery = "
    SELECT r.*, s.name AS sheet_name
    FROM sheet_reminders r
    JOIN sheets s ON s.id = r.sheet_id
    WHERE r.is_read = 0
    ORDER BY r.remind_at DESC, r.created_at DESC
";
$remindersResult = $conn->query($remindersQuery);
$reminderCount = $remindersResult->num_rows;

// Optional: Count only today's for the badge (or keep total unread)
$todayRemindersCount = 0;
$remindersResult->data_seek(0); // reset pointer
while ($rem = $remindersResult->fetch_assoc()) {
    if ($rem['remind_at'] === $today) {
        $todayRemindersCount++;
    }
}
$remindersResult->data_seek(0); // reset again for display
?>

<style>
    .fa-file{
        padding: 10px 20px;
        /* background: #fcf1c9; */
        background: var(--lufera-focus-color);
        margin: -40px 0px 10px;
        align-items: center;
        justify-content: center;
        display: flex;
        border-radius: 8px;
        /* color: #fec700; */
        color: var(--lufera-main-color);
    }
</style>

<div class="dashboard-main-body">

    <!-- Notifications Button (top right or near title) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div class="d-flex align-items-center gap-3">
            <a class="cursor-pointer fw-bold" onclick="history.back()">
                <span class="fa fa-arrow-left"></span> Back
            </a> 
            <!-- Notifications Button with Badge -->
            <button class="btn btn-outline-warning position-relative px-3 visibility-hidden" type="button" data-bs-toggle="offcanvas" data-bs-target="#notificationsOffcanvas" aria-controls="notificationsOffcanvas">
                <i class="fa fa-bell me-1"></i> Reminder
                <?php if ($reminderCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $reminderCount ?>
                        <span class="visually-hidden">unread reminders</span>
                    </span>
                <?php endif; ?>
            </button>

            <button type="button" class="add-role-btn btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2 visibility-hidden" data-bs-toggle="modal" data-bs-target="#createSheetModal">
                <i class="fa fa-plus me-1"></i> 
                Create New Sheet
            </button>
        </div>
        <h6 class="fw-semibold mb-0">Saved Sheets</h6>
        
        <div class="d-flex align-items-center gap-3">
            <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()">
                <span class="fa fa-arrow-left"></span> Back
            </a> 
            <!-- Notifications Button with Badge -->
            <!-- <button class="btn btn-outline-warning position-relative px-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#notificationsOffcanvas" aria-controls="notificationsOffcanvas"> -->
            <button class="btn lufera-bg lufera-text position-relative px-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#notificationsOffcanvas" aria-controls="notificationsOffcanvas">                
                <i class="fa fa-bell me-1"></i> Reminder
                <?php if ($reminderCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $reminderCount ?>
                        <span class="visually-hidden">unread reminders</span>
                    </span>
                <?php endif; ?>
            </button>

            <!-- <button type="button" class="add-role-btn btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createSheetModal"> -->
            <button type="button" class="add-role-btn btn lufera-bg lufera-text" data-bs-toggle="modal" data-bs-target="#createSheetModal">
                <i class="fa fa-plus"></i> 
                Create New Sheet
            </button>
        </div>
    </div>

    <!-- Offcanvas - Right Side Notifications Panel -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="notificationsOffcanvas" aria-labelledby="notificationsOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h6 class="offcanvas-title" id="notificationsOffcanvasLabel">
                <i class="fa fa-bell me-2"></i> Notifications & Reminders
                <?php if ($reminderCount > 0): ?>
                    <!-- <span class="badge bg-warning text-dark ms-2"><?= $reminderCount ?> unread</span> -->
                <?php endif; ?>
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        
        <div class="offcanvas-body p-0">
            <?php if ($reminderCount === 0): ?>
                <div class="text-center py-5 text-muted">
                    <p>No pending reminders</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php while ($rem = $remindersResult->fetch_assoc()): ?>
                        <?php 
                            $isToday = ($rem['remind_at'] === $today);
                            // $dueClass = $isToday ? 'text-danger fw-bold' : 'text-warning';
                            $dueClass = $isToday ? 'text-danger fw-bold' : 'lufera-color';
                        ?>
                    <a href="sheets.php?id=<?= $rem['sheet_id'] ?>&row=<?= $rem['sheet_row'] ?>&mark_read=1" class="text-decoration-none text-dark">
                        <div class="list-group-item list-group-item-action border-bottom px-4 py-3">
                            <div class="d-flex align-items-start">
                                <div class="me-3 mt-4">
                                    <i class="fa fa-bell <?= $dueClass ?>" style="font-size: 1.4rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0"><?= htmlspecialchars($rem['sheet_name']) ?></h6>
                                        <small class="<?= $dueClass ?>">
                                            <?= $isToday ? 'Due Today' : date('M d, Y', strtotime($rem['remind_at'])) ?>
                                        </small>
                                    </div>
                                    <p class="mb-1 text-muted" style="font-size: 0.95rem;">
                                        Row <strong><?= $rem['sheet_row'] ?></strong><br>
                                        <?= nl2br(htmlspecialchars($rem['message'])) ?>
                                    </p>
                                    <?php
                                    $timestamp = $rem['created_at'] ?? $rem['remind_at'];
                                    $adjusted = date('Y-m-d H:i:s', strtotime($timestamp . ' +5 hours 30 minutes'));
                                    ?>
                                    <small class="text-muted">
                                        <?= date('M d, Y h:i A', strtotime($adjusted)) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
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
                            <input type="text" class="form-control" id="sheet_name" name="sheet_name" required minlength="1" maxlength="50" autofocus>
                            <div class="invalid-feedback">
                                Please enter a sheet name.
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary lufera-text" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn lufera-bg lufera-text">Create Sheet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <ul class="nav button-tab nav-pills mb-16" id="pills-tab-four" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2 fw-semibold text-primary-light radius-4 px-16 py-10 active" id="pills-button-icon-home-tab" data-bs-toggle="pill" data-bs-target="#pills-button-icon-home" type="button" role="tab" aria-controls="pills-button-icon-home" aria-selected="true">
                        <span class="fa fa-th"></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2 fw-semibold text-primary-light radius-4 px-16 py-10" id="pills-button-icon-details-tab" data-bs-toggle="pill" data-bs-target="#pills-button-icon-details" type="button" role="tab" aria-controls="pills-button-icon-details" aria-selected="false">
                    <span class="fa fa-list"></span>
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="pills-tab-fourContent">
                <div class="tab-pane fade show active" id="pills-button-icon-home" role="tabpanel" aria-labelledby="pills-button-icon-home-tab" tabindex="0">
                        <!-- Your existing sheets grid -->
                    <div class="row g-3">
                        <?php if (empty($sheets)): ?>
                            <div class="col-12 text-center py-5">
                                <h5>No sheets found</h5>
                                <p>Create your first sheet!</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($sheets as $sheet): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <div class="card radius-12 cursor-pointer h-100 border" onclick="window.location='sheets.php?id=<?= $sheet['id'] ?>'">
                                <img src="assets/images/sheets.png" style="border-radius: 10px 10px 0 0;">
                                    <div class="card-body p-24">
                                    <span class="float-end" style="margin-top: -25px">ID: <?= htmlspecialchars($sheet['id']) ?></span>
                                    <span class="fa fa-thin fa-file"></span>
                                        <h6 class="fw-semibold mb-8"><?= htmlspecialchars($sheet['name']) ?></h6>
                                        <p class="text-muted mb-0" style="font-size: 14px;">
                                            Last Updated: <br>
                                            <?php
                                            $adjusted_updated = date('Y-m-d H:i:s', strtotime($sheet['updated_at'] . ' +5 hours 30 minutes'));
                                            ?>
                                            <strong><?= date("M d, Y h:i A", strtotime($adjusted_updated)) ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-button-icon-details" role="tabpanel" aria-labelledby="pills-button-icon-details-tab" tabindex="0">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table mb-0" id="role-table">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $service = "SELECT * FROM `sheets`"; 
                                    $results = $conn->query($service);
                                    if (mysqli_num_rows($results) > 0) {
                                        while ($row = mysqli_fetch_assoc($results)) {
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-10 justify-content-center">
                                        <button type="button" class="fa fa-trash-alt deleteBtn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>"></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                        }
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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
    
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.deleteBtn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This sheet will be permanently deleted.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then(function (res) {
                    if (!res.isConfirmed) {
                        return;
                    }
                    const fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    fetch(window.location.href, {
                        method: 'POST',
                        body: fd
                    })
                    .then(function (response) {
                        return response.text();
                    })
                    .then(function (text) {
                        console.log('Delete response:', text);
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (error) {
                            console.error('Invalid JSON response:', text);
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: 'The server returned an invalid response.'
                            });
                            return;
                        }
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                confirmButtonText: 'OK'
                            }).then(function () {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Delete Failed',
                                text: data.message || 'Unable to delete the sheet.'
                            });

                        }
                    })
                    .catch(function (error) {
                        console.error('Fetch error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong while deleting the sheet.'
                        });
                    });
                });
            });
        });
    });
</script>
<?php include './partials/layouts/layoutBottom.php'; ?>