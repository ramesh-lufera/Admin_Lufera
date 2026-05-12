<?php
include './partials/layouts/layoutTop.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);


// CREATE BACKUP FOLDER IF NOT EXISTS
$backupDir = __DIR__ . "/backups/";
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// EXPORT FUNCTION
if (isset($_POST['export'])) {

    $tables = [];
    $result = $conn->query("SHOW TABLES");

    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $sqlScript = "";

    foreach ($tables as $table) {

        // STRUCTURE
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        if (!$result) {
            die("Error creating table structure for $table: " . $conn->error);
        }
    
        $row = $result->fetch_row();
        $sqlScript .= "\n\n" . $row[1] . ";\n\n";
    
        // DATA
        $result = $conn->query("SELECT * FROM `$table`");
        if (!$result) {
            die("Error fetching data from $table: " . $conn->error);
        }
    
        while ($row = $result->fetch_assoc()) {
            $columns = array_keys($row);
            $values  = array_map([$conn, 'real_escape_string'], array_values($row));
    
            $sqlScript .= "INSERT INTO `$table` (`" . implode("`,`", $columns) . "`) 
            VALUES ('" . implode("','", $values) . "');\n";
        }
    }

    $filename = "backup_" . date("Y-m-d_H-i-s") . ".sql";
    $filePath = $backupDir . $filename;

    // SAVE FILE TO FOLDER
    file_put_contents($filePath, $sqlScript);

    // FORCE DOWNLOAD
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: " . filesize($filePath));

    readfile($filePath);
    exit;
}

// GET FILE LIST
$files = array_diff(scandir($backupDir), ['.', '..']);
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0">Backup</h6>
        <form method="post" target="downloadFrame" id="exportForm">
    <button type="submit" class="btn btn-default lufera-bg" name="export" id="exportBtn">
        Export
    </button>
</form>

<!-- Hidden iframe -->
<iframe name="downloadFrame" style="display:none;"></iframe>

<script>
document.getElementById('exportForm').addEventListener('submit', function () {
    Swal.fire({
        icon: 'success',
        title: 'Data Exported!',
        text: 'Backup downloaded successfully.',
        timer:6000,
        showConfirmButton: false
    });
    // reload page after short delay
    setTimeout(function () {
        location.reload();
    }, 1000);

});
</script>
    </div>

    <!-- EXPORT BUTTON -->
    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0" id="userTable">
                    <tr>
                        <th>#</th>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Download</th>
                    </tr>
                    <?php
                    $i = 1;
                    foreach ($files as $file):
                        $filePath = "backups/" . $file;
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $file ?></td>
                        <td><?= round(filesize($backupDir . $file)/1024, 2) ?> KB</td>
                        <td><a href="<?= $filePath ?>" download>Download</a></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include './partials/layouts/layoutBottom.php'; ?>