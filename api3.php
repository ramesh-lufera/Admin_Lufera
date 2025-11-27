<?php 
include './partials/head.php';
include './partials/connection.php';

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// 1) Get ALL packages grouped by category
$packages_by_category = [];

$sql = "
    SELECT p.*, c.cat_name 
    FROM package p
    LEFT JOIN categories c ON p.cat_id = c.cat_id
    WHERE p.is_deleted = 0 AND p.is_active = 1
    ORDER BY c.cat_name ASC, p.id ASC
";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $category = $row['cat_name'] ?: "Uncategorized";
    $packages_by_category[$category][$row['id']] = $row;
}

// 2) Load ALL durations per package
$durations_per_pkg = [];

$duration_sql = "
    SELECT d.*, p.cat_id, c.cat_name
    FROM durations d
    INNER JOIN package p ON d.package_id = p.id
    LEFT JOIN categories c ON p.cat_id = c.cat_id
    ORDER BY c.cat_name ASC, d.duration ASC
";

$duration_result = $conn->query($duration_sql);

while ($row = $duration_result->fetch_assoc()) {
    $durations_per_pkg[$row['package_id']][] = $row['duration'];
}

// 3) Features
$features_by_package = [];
$feature_sql = "SELECT * FROM features";
$feature_result = $conn->query($feature_sql);
while ($feat = $feature_result->fetch_assoc()) {
    $features_by_package[$feat['package_id']][] = $feat['feature'];
}

// 4) Currency
$res = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
$symbol = ($r = $res->fetch_assoc()) ? $r['symbol'] : "$";

// 5) Build category → common durations
$category_common_durations = [];

foreach ($packages_by_category as $category => $pkgs) {

    $all_pkg_ids = array_keys($pkgs);
    $first_pkg = array_shift($all_pkg_ids);

    $common = $durations_per_pkg[$first_pkg] ?? [];

    foreach ($all_pkg_ids as $pid) {
        $pkg_durations = $durations_per_pkg[$pid] ?? [];
        $common = array_intersect($common, $pkg_durations);
    }

    sort($common);
    $category_common_durations[$category] = $common;
}

// 6) Duration data grouped
$durations_by_category = [];

$duration_sql2 = "
    SELECT d.*, p.cat_id, c.cat_name, p.title, p.subtitle, p.description, p.package_name 
    FROM durations d
    INNER JOIN package p ON d.package_id = p.id
    LEFT JOIN categories c ON p.cat_id = c.cat_id
    ORDER BY c.cat_name ASC, d.duration ASC";

$res2 = $conn->query($duration_sql2);
while ($row = $res2->fetch_assoc()) {
    $cat = $row['cat_name'] ?: "Uncategorized";
    // if (in_array($row['duration'], $category_common_durations[$cat])) {
    //     $durations_by_category[$cat][$row['duration']][] = $row;
    // }
    $durations_by_category[$cat][$row['duration']][] = $row;

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0 text-capitalize">Packages</h6>
    </div>
    <div class="card p-0 radius-12">
        <div class="card-body p-40">
        <?php foreach ($durations_by_category as $category => $durationGroups): ?>
            <h4 class="mt-5 mb-4 text-center"><?= htmlspecialchars($category) ?></h2>
            <!-- Duration Tabs -->
            <ul class="nav nav-pills button-tab mt-3 mb-3 justify-content-center" role="tablist">
                <?php $first = true; foreach ($durationGroups as $dur => $list): ?>
                    <li class="nav-item">
                        <button class="nav-link <?= $first ? 'active' : '' ?>"
                            data-bs-toggle="pill"
                            data-bs-target="#tab-<?= md5($category.$dur) ?>">
                            <?= htmlspecialchars($dur) ?>
                        </button>
                    </li>
                <?php $first = false; endforeach; ?>
            </ul>

            <!-- Panels -->
            <div class="tab-content">
                <?php $first = true; foreach ($durationGroups as $dur => $packages): ?>
                <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= md5($category.$dur) ?>">
                    <div class="row gy-4 mt-3">
                        <?php foreach ($packages as $d): ?>
                            <div class="col-xxl-4 col-sm-6">
                                <div class="pricing-plan border radius-24 p-3">
                                    <h5><?= htmlspecialchars($d['title']) ?></h5>
                                    <p><?= htmlspecialchars($d['subtitle']) ?></p>
                                    <h4>
                                        <span class="text-sm text-muted mt-0 mb-10 text-decoration-line-through">
                                            <?= $symbol ?> <?= number_format($d['preview_price']) ?>
                                        </span><br>
                                        <?= $symbol ?> <?= number_format($d['price']) ?>
                                        <span>/<?= htmlspecialchars($d['duration']) ?></span>
                                    </h4>
                                    <p><?= htmlspecialchars($d['description']) ?></p>
                                    <ul>
                                        <?php foreach ($features_by_package[$d['package_id']] ?? [] as $f): ?>
                                            <li class="d-flex align-items-center gap-16 mb-16">
                                            <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                <iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>
                                            </span>
                                            <?= htmlspecialchars($f) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <div class="d-flex align-items-center gap-2 mt-3">
                                    <input type="text" class="form-control" id="id-<?= $d['id'] ?>" value="<?= $d['id'] ?>" readonly style="max-width: 120px;">
                                    <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('id-<?= $d['id'] ?>')">
                                        <iconify-icon icon="mdi:content-copy"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php $first = false; endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function copyToClipboard(fieldId) {
    const input = document.getElementById(fieldId);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
}
</script>
</body>
</html>

<?php include './partials/scripts.php' ?>