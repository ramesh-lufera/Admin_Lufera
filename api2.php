<?php 
include './partials/head.php';
include './partials/connection.php';

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// 1) Get packages (single OR all)
$packages_data = [];

// Check if URL contains ?id=xx
$filter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($filter_id > 0) {
    // Load ONLY the requested package
    $sql = "SELECT * FROM package 
            WHERE id = $filter_id AND is_deleted = 0 AND is_active = 1";
} else {
    // Load ALL packages
    $sql = "SELECT * FROM package 
            WHERE is_deleted = 0 AND is_active = 1 
            ORDER BY id ASC";
}

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $packages_data[$row['id']] = $row;
}

$hasPackages = count($packages_data) > 0;

// Prepare $package_ids for next queries
$package_ids = !empty($packages_data)
    ? implode(",", array_keys($packages_data))
    : "0"; // prevent SQL errors


// 2) Load ALL durations, grouped by package_id + duration label
$durations_by_package = [];

$duration_sql = "
    SELECT d.*, p.title, p.subtitle, p.description, p.package_name,
           p.addon_service, p.addon_package, p.addon_product,
           p.gst_id, p.is_active AS pkg_active
    FROM durations d
    INNER JOIN package p ON d.package_id = p.id
    WHERE d.package_id IN ($package_ids)
    ORDER BY d.duration ASC
";

$duration_result = $conn->query($duration_sql);

while ($row = $duration_result->fetch_assoc()) {
    $pkg_id = $row['package_id'];
    $duration_name = $row['duration'];

    $durations_by_package[$pkg_id][$duration_name][] = $row;
}


// 3) Features table mapping by package
$features_by_package = [];

$feature_sql = "SELECT * FROM features WHERE package_id IN ($package_ids)";
$feature_result = $conn->query($feature_sql);

while ($feat = $feature_result->fetch_assoc()) {
    $features_by_package[$feat['package_id']][] = $feat['feature'];
}


// 4) Currency
$result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
$symbol = "$";
if ($row = $result->fetch_assoc()) {
    $symbol = $row['symbol'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<style>
    .nav-link:focus, .nav-link:hover {
        color: #fdc701 !important;
    }
    button:disabled {
        opacity: 0.7;
    }
</style>
<body>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0 text-capitalize">Packages</h6>
    </div>
    <div class="card p-0 radius-12">
        <div class="card-body p-40">
            <div class="row justify-content-center">
                <div class="col-xxl-10">
                <?php if ($hasPackages): ?>
                    <?php foreach ($packages_data as $pkg_id => $pkg): ?>
                    <div class="mb-5">
                        <h4 class="fw-bold mb-2"><?= htmlspecialchars($pkg['title']) ?></h4>
                        <p class="text-secondary-light mb-3"><?= htmlspecialchars($pkg['subtitle']) ?></p>
                        <?php 
                            $durations = isset($durations_by_package[$pkg_id]) 
                                ? array_keys($durations_by_package[$pkg_id]) 
                                : [];
                        ?>
                        <?php if (!empty($durations)): ?>
                        <!-- Duration Tabs -->
                        <ul class="nav nav-pills button-tab mt-3 mb-3" role="tablist">
                            <?php $first = true; foreach ($durations as $duration_name): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $first ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-<?= $pkg_id ?>-<?= md5($duration_name) ?>">
                                        <?= htmlspecialchars($duration_name) ?>
                                    </button>
                                </li>
                            <?php $first = false; endforeach; ?>
                        </ul>

                        <!-- Duration Panels -->
                        <div class="tab-content">
                            <?php $first = true; foreach ($durations as $duration_name): ?>
                                <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= $pkg_id ?>-<?= md5($duration_name) ?>">
                                    <div class="row gy-4 mt-3">
                                        <?php foreach ($durations_by_package[$pkg_id][$duration_name] as $d): ?>
                                            <div class="col-xxl-4 col-sm-6">
                                                <div class="pricing-plan border radius-24 p-3">
                                                    <h5><?= htmlspecialchars($d['title']) ?></h5>
                                                    <p><?= htmlspecialchars($d['subtitle']) ?></p>
                                                    <h4>
                                                        <span class="text-sm text-muted text-decoration-line-through">
                                                            <?= $symbol ?> <?= number_format($d['preview_price']) ?>
                                                        </span><br>
                                                        <?= $symbol ?> <?= number_format($d['price']) ?>
                                                        <span class="text-md">/<?= htmlspecialchars($d['duration']) ?></span>
                                                    </h4>
                                                    <p><?= htmlspecialchars($d['description']) ?></p>
                                                    <ul class="mt-3">
                                                        <?php 
                                                            $feature_list = isset($features_by_package[$pkg_id]) 
                                                                ? $features_by_package[$pkg_id] 
                                                                : [];
                                                        ?>
                                                        <?php foreach ($feature_list as $feat): ?>
                                                            <li class="d-flex align-items-center gap-16 mb-16">
                                                                <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                                    <iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>
                                                                </span>
                                                                <?= htmlspecialchars($feat) ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- Copy Package ID -->
                                            <div class="col-xxl-4 col-sm-6">
                                                <div class="d-flex align-items-center gap-2 mt-3">
                                                    <!-- <input type="text" class="form-control" id="pkg-id-<?= $pkg_id ?>" value="<?= $pkg_id ?>" readonly style="max-width: 120px;"> -->
                                                    <?php 
$embedCode = '
<div id="packages' . $pkg_id . '">Loading packages...</div>

<script>
fetch("http://localhost/Admin_Lufera/api2.php?id=' . $pkg_id . '")
  .then(res => res.text())
  .then(html => {
      document.getElementById("packages' . $pkg_id . '").innerHTML = html;
  })
  .catch(err => {
      console.error(err);
      document.getElementById("packages' . $pkg_id . '").innerText = "Could not load packages.";
  });
</script>
';
?>
<div class="d-flex flex-column mt-3">
    <textarea class="form-control" id="pkg-code-<?= $pkg_id ?>" rows="7"><?= htmlspecialchars($embedCode) ?></textarea>

    <button class="btn btn-sm btn-outline-primary mt-2" onclick="copyToClipboard('pkg-code-<?= $pkg_id ?>')">
        <iconify-icon icon="mdi:content-copy"></iconify-icon>
    </button>
</div>


                                                    <!-- <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('pkg-id-<?= $pkg_id ?>')">
                                                        <iconify-icon icon="mdi:content-copy"></iconify-icon>
                                                    </button> -->
                                                </div>
                                            </div>

                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php $first = false; endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p>No durations found for this package.</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <h6>No packages available.</h6>
                <?php endif; ?>
                </div>
            </div>
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
