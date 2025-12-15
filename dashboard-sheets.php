<?php include './partials/layouts/layoutTop.php'; ?>

<?php
// Fetch sheets
$sheets = [];
$res = $conn->query("SELECT id, name, updated_at FROM sheets ORDER BY updated_at DESC");
while ($row = $res->fetch_assoc()) {
    $sheets[] = $row;
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Saved Sheets</h6>
        <a href="sheets.php" class="btn btn-default lufera-bg">+ Create New Sheet</a>
    </div>

    <div class="row g-3">

        <?php if (empty($sheets)): ?>
            <div class="col-12 text-center py-5">
                <h5>No sheets found</h5>
                <p>Create your first sheet!</p>
            </div>
        <?php endif; ?>

        <?php foreach ($sheets as $sheet): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                <div class="card radius-12 cursor-pointer h-100"
                     onclick="window.location='sheets.php?id=<?= $sheet['id'] ?>'">
                    
                    <div class="card-body p-24">
                        
                        <h6 class="fw-semibold mb-8">
                            <?= htmlspecialchars($sheet['name']) ?>
                        </h6>

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

<?php include './partials/layouts/layoutBottom.php'; ?>
