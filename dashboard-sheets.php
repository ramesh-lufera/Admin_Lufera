<?php include './partials/layouts/layoutTop.php'; ?>
<style>
    .fa-file{
        padding: 10px 20px;
        background: #fcf1c9;
        margin: -40px 0px 10px;
        align-items: center;
        justify-content: center;
        display: flex;
        border-radius: 8px;
        color: #fec700;
    }
</style>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_sheet') {
    $sheet_name = trim($_POST['sheet_name'] ?? '');

    if (strlen($sheet_name) >= 1 && strlen($sheet_name) <= 100) {  // adjust length limit as needed
        $stmt = $conn->prepare("INSERT INTO sheets (name, created_at, updated_at) VALUES (?, NOW(), NOW())");
        $stmt->bind_param("s", $sheet_name);
        
        if ($stmt->execute()) {
            $new_sheet_id = $conn->insert_id;
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
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                Create New Sheet
            </button>
        </div>
        <h6 class="fw-semibold mb-0">Saved Sheets</h6>
        
        <div class="d-flex align-items-center gap-3">
            <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()">
                <span class="fa fa-arrow-left"></span> Back
            </a> 
            <!-- Notifications Button with Badge -->
            <button class="btn btn-outline-warning position-relative px-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#notificationsOffcanvas" aria-controls="notificationsOffcanvas">
                <i class="fa fa-bell me-1"></i> Reminder
                <?php if ($reminderCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $reminderCount ?>
                        <span class="visually-hidden">unread reminders</span>
                    </span>
                <?php endif; ?>
            </button>

            <button type="button" class="add-role-btn btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createSheetModal">
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                Create New Sheet
            </button>
        </div>
    </div>

    <!-- Offcanvas - Right Side Notifications Panel -->
    <div class="offcanvas offcanvas-end w-25" tabindex="-1" id="notificationsOffcanvas" aria-labelledby="notificationsOffcanvasLabel">
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
                            $dueClass = $isToday ? 'text-danger fw-bold' : 'text-warning';
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
                                    <small class="text-muted">
                                        <?= date('M d, Y h:i A', strtotime($rem['created_at'] ?? $rem['remind_at'])) ?>
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
                <div class="card radius-12 cursor-pointer h-100" onclick="window.location='sheets.php?id=<?= $sheet['id'] ?>'">
                <img src="assets/images/sheets.png" style="border-radius: 10px 10px 0 0;">
                    <div class="card-body p-24">
                    <span class="fa fa-thin fa-file"></span>
                        <h6 class="fw-semibold mb-8"><?= htmlspecialchars($sheet['name']) ?></h6>
                        <p class="text-muted mb-0" style="font-size: 14px;">
                            Last Updated: <br>
                            <strong><?= date("M d, Y H:i", strtotime($sheet['updated_at'])) ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
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
</script>
<?php include './partials/layouts/layoutBottom.php'; ?>