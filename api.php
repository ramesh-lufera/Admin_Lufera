<?php 
include './partials/connection.php';

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// EMBED MODE DETECT
$is_embed   = (isset($_GET['embed']) && $_GET['embed'] == '1');
$embed_type = isset($_GET['type']) ? strtolower($_GET['type']) : null;
$embed_id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<link rel="stylesheet" href="https://admin2.luferatech.com/assets/css/package_list.css">

<?php if (!$is_embed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Admin CSS -->
    <link rel="stylesheet" href="https://admin2.luferatech.com/assets/css/lib/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<ul class="nav nav-tabs" role="tablist" style="margin:20px;">
    <li class="nav-item">
        <button class="duration-tab nav-link active" data-bs-toggle="tab" data-bs-target="#tab1">Category View</button>
    </li>
    <li class="nav-item">
        <button class="duration-tab nav-link" data-bs-toggle="tab" data-bs-target="#tab2">Package View</button>
    </li>
</ul>
<?php endif; ?>

<div class="tab-content">

<!-- **************************** TAB 1 ********************************* -->

<?php if (!$is_embed || $embed_type != 'package'): ?>
<div class="tab-border tab-pane fade <?php echo (!$is_embed || $embed_type == 'category') ? 'show active' : ''; ?>" id="tab1">

<?php 
// 1) Categories
$packages_by_category = [];
$sql = "
    SELECT p.*, c.cat_name 
    FROM package p
    LEFT JOIN categories c ON p.cat_id = c.cat_id
    WHERE p.is_deleted = 0 AND p.is_active = 1
    ORDER BY c.cat_name ASC, p.id ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $category = $row['cat_name'] ?: "Uncategorized";
    $packages_by_category[$category][$row['id']] = $row;
}

// 2) Durations
$durations_per_pkg = [];
$duration_sql = "
    SELECT d.*, p.cat_id, c.cat_name
    FROM durations d
    INNER JOIN package p ON d.package_id = p.id
    LEFT JOIN categories c ON p.cat_id = c.cat_id
    ORDER BY c.cat_name ASC, d.duration ASC";
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

// 5) Duration group by category
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
    $durations_by_category[$cat]['cat_id'] = $row['cat_id'];
    $durations_by_category[$cat]['durations'][$row['duration']][] = $row;
}
?>

<?php if (!$is_embed): ?>
<div class="dashboard-main-body">
<?php endif; ?>

<div class="card p-0 border-0">
<div class="card-body p-40">

<?php foreach ($durations_by_category as $category => $data): ?>
<?php 
    $cat_id = $data['cat_id'];
    if ($is_embed && $embed_type == 'category' && $embed_id > 0 && $embed_id != $cat_id) continue;
    $durationGroups = $data['durations'];
?>

<div class="d-flex justify-content-center align-items-center gap-3 mt-5 mb-4">
    
    <!-- HIDE CATEGORY HEADING ONLY IN EMBED TYPE CATEGORY -->
    <?php if (!($is_embed && $embed_type == 'category')): ?>
        <h4 class="m-0"><?= htmlspecialchars($category) ?></h4>
    <?php endif; ?>

    <!-- COPY UI ONLY IN ADMIN VIEW -->
    <?php if (!$is_embed): ?>
        <input type="text" class="form-control form-control-sm" readonly 
            value="[category id=<?= $cat_id ?>]" style="max-width:200px;">
        <button class="btn btn-sm lufera-bg text-white" onclick="navigator.clipboard.writeText('[category id=<?= $cat_id ?>]')">
            <span class="fa fa-copy"></span>
        </button>
        <!--<button class="btn btn-sm lufera-bg text-white" data-copy="[category id=<?= $cat_id ?>]" data-bs-toggle="popover">
           <span class="fa fa-copy"></span>
        </button> -->

    <?php endif; ?>

</div>

<!-- Duration tabs -->
<ul class="nav nav-pills button-tab mt-3 mb-3 justify-content-center duration-tab" role="tablist">
    <?php $first = true; foreach ($durationGroups as $dur => $list): ?>
        <li class="nav-item">
            <button class="duration-tab nav-link <?= $first ? 'active' : '' ?>"
                data-bs-toggle="pill"
                data-bs-target="#tab-<?= md5($category.$dur) ?>">
                <?= htmlspecialchars($dur) ?>
            </button>
        </li>
    <?php $first = false; endforeach; ?>
</ul>

<div class="tab-content">
    <?php $first = true; foreach ($durationGroups as $dur => $packages): ?>
    <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= md5($category.$dur) ?>">
        <div class="row gy-4 mt-3">
            <?php foreach ($packages as $d): ?>
                <div class="col-xxl-4 col-sm-6">
                    <div class="pricing-plan radius-24 p-3">
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

                        <ul>
                            <?php foreach ($features_by_package[$d['package_id']] ?? [] as $f): ?>
                                <li class="d-flex align-items-center gap-16 mb-16 check-points">
                                    <span class="fa fa-check">
                                        <!--<iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>-->
                                    </span>
                                    <?= htmlspecialchars($f) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php $first = false; endforeach; ?>
</div>

<?php endforeach; ?>
</div></div>

<?php if (!$is_embed): ?>
</div>
<?php endif; ?>
</div>
<?php endif; ?>
<!-- END TAB 1 -->

<!-- **************************** TAB 2 ********************************* -->

<?php if (!$is_embed || $embed_type != 'category'): ?>
<div class="tab-border tab-pane fade <?php echo ($is_embed && $embed_type == 'package') ? 'show active' : ''; ?>" id="tab2">

<?php 
// Packages
$packages_data = [];
$sql = "SELECT * FROM package WHERE is_deleted = 0 AND is_active = 1 ORDER BY id ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $packages_data[$row['id']] = $row;
}

// Durations
$durations_by_package = [];
if (!empty($packages_data)) {
    $package_ids = implode(",", array_keys($packages_data));
    $duration_sql = "
        SELECT d.*, p.title, p.subtitle, p.description, p.package_name,
               p.addon_service, p.addon_package, p.addon_product,
               p.gst_id, p.is_active AS pkg_active
        FROM durations d
        INNER JOIN package p ON d.package_id = p.id
        WHERE d.package_id IN ($package_ids)
        ORDER BY d.duration ASC";
    $duration_result = $conn->query($duration_sql);
    while ($row = $duration_result->fetch_assoc()) {
        $pkg_id = $row['package_id'];
        $duration_name = $row['duration'];
        $durations_by_package[$pkg_id][$duration_name][] = $row;
    }
}

// Features
$features_by_package = [];
$feature_sql = "SELECT * FROM features WHERE package_id IN ($package_ids)";
$feature_result = $conn->query($feature_sql);
while ($feat = $feature_result->fetch_assoc()) {
    $features_by_package[$feat['package_id']][] = $feat['feature'];
}

// Currency
$result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
$symbol = "$";
if ($row = $result->fetch_assoc()) $symbol = $row['symbol'];
?>

<?php if (!$is_embed): ?>
<div class="dashboard-main-body">
<?php endif; ?>

<div class="card p-0 border-0">
<div class="card-body p-40">
<div class="row justify-content-center">
<div class="col-xxl-10">

<?php if (!empty($packages_data)): ?>
    <?php foreach ($packages_data as $pkg_id => $pkg): ?>
    <?php if ($is_embed && $embed_type=='package' && $embed_id>0 && $embed_id != $pkg_id) continue; ?>
    <div class="mb-5">
        <?php 
            $durations = isset($durations_by_package[$pkg_id]) 
                ? array_keys($durations_by_package[$pkg_id]) 
                : [];
        ?>

        <?php if (!empty($durations)): ?>

        <!-- Duration Tabs -->
        <ul class="nav nav-pills button-tab mt-3 mb-3 justify-content-center duration-tab" role="tablist">
            <?php $firsts = true; foreach ($durations as $duration_name): ?>
                <li class="nav-item" role="presentation">
                    <button class="duration-tab nav-link <?= $firsts ? 'active' : '' ?>" 
                            data-bs-toggle="pill" 
                            data-bs-target="#tabp-<?= $pkg_id ?>-<?= md5($duration_name) ?>">
                        <?= htmlspecialchars($duration_name) ?>
                    </button>
                </li>
            <?php $firsts = false; endforeach; ?>
        </ul>

        <div class="tab-content">
            <?php $firsts = true; foreach ($durations as $duration_name): ?>
                <div class="tab-pane fade <?= $firsts ? 'show active' : '' ?>" 
                        id="tabp-<?= $pkg_id ?>-<?= md5($duration_name) ?>">

                    <div class="row gy-4 mt-3">
                        <?php foreach ($durations_by_package[$pkg_id][$duration_name] as $d): ?>
                            <div class="col-xxl-12 col-sm-6">
                                <div class="pricing-plan radius-24 p-3">
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
                                        <?php foreach ($features_by_package[$pkg_id] ?? [] as $feat): ?>
                                            <li class="d-flex align-items-center gap-16 mb-16 check-points">
                                                <span class="fa fa-check">
                                                    <!--<iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>-->
                                                </span>
                                                <?= htmlspecialchars($feat) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-xxl-4 col-sm-6">
                                <?php if (!$is_embed): ?>
                                <div class="d-flex align-items-center gap-2 mt-3">
                                    <input type="text" class="form-control" readonly 
                                        value="[package id=<?= $pkg_id ?>]" style="max-width:200px;">
                                    <button class="btn btn-sm lufera-bg text-white" onclick="navigator.clipboard.writeText('[package id=<?= $pkg_id ?>]')">
                                       <span class="fa fa-copy"></span>
                                    </button>
                                    <!--<button class="btn btn-sm lufera-bg text-white" data-copy="[package id=<?= $pkg_id ?>]" data-bs-toggle="popover">
                                        <span class="fa fa-copy"></span>
                                    </button> -->

                                </div>
                                <?php endif; ?>
                            </div>

                        <?php endforeach; ?>
                    </div>
                </div>
            <?php $firsts = false; endforeach; ?>
        </div>

        <?php else: ?>
            <p>No durations found for this package.</p>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>
<?php else: ?>
    <h6>No packages available.</h6>
<?php endif; ?>

</div></div></div></div>
<script>
// document.addEventListener("DOMContentLoaded", function () {
//     document.querySelectorAll("[data-copy]").forEach(function (btn) {
//         btn.addEventListener("click", function () {
//             let text = this.getAttribute("data-copy");
//             navigator.clipboard.writeText(text);

//             // Create popover instance
//             let pop = bootstrap.Popover.getOrCreateInstance(this, {
//                 content: "Copied!",
//                 trigger: "manual",
//                 placement: "top"
//             });

//             pop.show();

//             // Hide after 1 second
//             setTimeout(() => pop.hide(), 100000);
//         });
//     });
// });
</script>

<?php if (!$is_embed): ?>
</div>
<?php endif; ?>
</div><!-- END TAB 2 -->
<?php endif; ?>
</div> <!-- tab-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
