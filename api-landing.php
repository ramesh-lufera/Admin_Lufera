<?php 
include './partials/connection.php';
function formatDurationLabel($duration) {
    $duration = trim(strtolower($duration));

    if (preg_match('/(\d+)\s*(month|months|year|years)/', $duration, $matches)) {
        $number = (int)$matches[1];
        $unit   = $matches[2];

        if ($number === 1) {
            return ($unit === 'month' || $unit === 'months')
                ? 'per month'
                : 'per year';
        }

        return "per {$number} " . (
            ($unit === 'month' || $unit === 'months') ? 'months' : 'years'
        );
    }

    return 'per period';
}

function formatDurationTab($duration) {
    $duration = trim(strtolower($duration));

    if (preg_match('/(\d+)\s*(month|months|year|years)/', $duration, $matches)) {
        $number = (int)$matches[1];
        $unit   = $matches[2];

        if ($number === 1) {
            return ($unit === 'month' || $unit === 'months')
                ? 'Monthly'
                : 'Yearly';
        }

        return ucfirst($number . ' ' . (
            ($unit === 'month' || $unit === 'months') ? 'Months' : 'Years'
        ));
    }

    return 'Custom';
}

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// EMBED MODE DETECT
$is_embed   = (isset($_GET['embed']) && $_GET['embed'] == '1');
$embed_type = isset($_GET['type']) ? strtolower($_GET['type']) : null;
$embed_id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!--<link rel="stylesheet" href="https://admin2.luferatech.com/assets/css/package_list.css">-->

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

<div class="tab-content container">

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

<div class="d-flex align-items-center gap-3">
    
    <!-- HIDE CATEGORY HEADING ONLY IN EMBED TYPE CATEGORY -->
    <?php if (!($is_embed && $embed_type == 'category')): ?>
        <h4 class="m-0"><?= htmlspecialchars($category) ?></h4>
    <?php endif; ?>

    <!-- COPY UI ONLY IN ADMIN VIEW -->
    <?php if (!$is_embed): ?>
        <input type="text" class="form-control form-control-sm" readonly 
            value="<CategoryEmbed id={<?= $cat_id ?>} />" style="max-width:200px;">
        <button class="btn btn-sm btn-primary text-white" onclick="navigator.clipboard.writeText('<CategoryEmbed id={<?= $cat_id ?>} />')">
            <span class="fa fa-copy"></span>
        </button>
        <!--<button class="btn btn-sm lufera-bg text-white" data-copy="[category id=<?= $cat_id ?>]" data-bs-toggle="popover">
           <span class="fa fa-copy"></span>
        </button> -->

    <?php endif; ?>

</div>

<!-- Duration tabs -->
<div class="tab-section d-flex justify-content-center align-items-center">
    <ul class="nav nav-pills button-tab justify-content-center duration-tab" role="tablist">
        <?php $first = true; foreach ($durationGroups as $dur => $list): ?>
            <li class="nav-item">
                <button class="duration-tab nav-link <?= $first ? 'active' : '' ?>"
                    data-bs-toggle="pill"
                    data-bs-target="#tab-<?= md5($category.$dur) ?>">
                    <?= formatDurationTab($dur) ?>
                </button>
            </li>
        <?php $first = false; endforeach; ?>
    </ul>
</div>

<div class="tab-content">
    <?php $first = true; foreach ($durationGroups as $dur => $packages): ?>
    <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= md5($category.$dur) ?>">
        <div class="row gy-4 mt-3">
            <?php foreach ($packages as $d): ?>
                <div class="col-xxl-4 col-sm-4">
                    <div class="pricing-plan radius-24">
                        <h5 class="plan-title"><?= htmlspecialchars($d['title']) ?></h5>
                        <!--<p><?= htmlspecialchars($d['subtitle']) ?></p>-->

                        <div class="plan-price">
                            <!--<p class="text-sm text-muted text-decoration-line-through mb-0">-->
                            <!--    <?= $symbol ?> <?= number_format($d['preview_price']) ?>-->
                            <!--</p>-->
                            <span class="price"><?= $symbol ?> <?= number_format($d['price']) ?></span>
                            <span class="text">/ <?= formatDurationLabel($d['duration']) ?></span>
                        </div>

                        <p class="plan-desc"><?= htmlspecialchars($d['description']) ?></p>

                        <ul class="p-0">
                            <?php foreach ($features_by_package[$d['package_id']] ?? [] as $f): ?>
                                <li class="d-flex align-items-center gap-16 mb-16 check-points">
                                    <!--<span class="fa fa-check text-white lufera-bg rounded-circle justify-content-center align-items-center d-flex"></span>-->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                        viewBox="0 0 15 15" fill="none">
                                        <path opacity="0.992" fill-rule="evenodd"
                                            d="M7.22393 0C10.1444 0.0048682 12.3871 1.22628 13.952 3.66423C15.1321 5.76513 15.3168 7.95136 14.5062 10.2229C13.3721 12.8859 11.3758 14.4614 8.51719 14.9495C5.62984 15.2424 3.28454 14.2622 1.48125 12.0088C-0.0776275 9.77987 -0.406074 7.37811 0.495906 4.80353C1.26674 2.9139 2.5754 1.53341 4.42187 0.662026C5.31983 0.270692 6.25384 0.0500183 7.22393 0ZM11.2269 4.43403C11.6225 4.43526 11.7508 4.62002 11.6118 4.98829C9.90797 6.93843 8.20413 8.8886 6.50032 10.8387C6.34295 10.9814 6.17359 10.9968 5.99226 10.8849C5.24811 9.89445 4.50399 8.904 3.75985 7.91352C3.63052 7.68883 3.67671 7.50922 3.89841 7.37466C3.99079 7.35412 4.08316 7.35412 4.17554 7.37466C4.74004 7.75441 5.30458 8.1342 5.86909 8.51396C6.02234 8.62678 6.18659 8.64729 6.36176 8.57554C7.97994 7.1872 9.60165 5.80667 11.2269 4.43403Z"
                                            fill="#1AD079" />
                                    </svg>
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
<div class="col-xxl-12">

<?php if (!empty($packages_data)): ?>
    <?php foreach ($packages_data as $pkg_id => $pkg): ?>
    <?php if ($is_embed && $embed_type=='package' && $embed_id>0 && $embed_id != $pkg_id) continue; ?>
    <div class="">
        <?php 
            $durations = isset($durations_by_package[$pkg_id]) 
                ? array_keys($durations_by_package[$pkg_id]) 
                : [];
        ?>

        <?php if (!empty($durations)): ?>
            <div class="col-xxl-4 col-sm-4">
                <?php if (!$is_embed): ?>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <input type="text" class="form-control" readonly 
                        value="<PackageEmbed id={<?= $pkg_id ?>} />" style="max-width:200px;">
                    <button class="btn btn-sm btn-primary text-white" onclick="navigator.clipboard.writeText('<PackageEmbed id={<?= $pkg_id ?>} />')">
                       <span class="fa fa-copy"></span>
                    </button>
                    <!--<button class="btn btn-sm lufera-bg text-white" data-copy="[package id=<?= $pkg_id ?>]" data-bs-toggle="popover">
                        <span class="fa fa-copy"></span>
                    </button> -->

                </div>
                <?php endif; ?>
            </div>
        <!-- Duration Tabs -->
        <div class="tab-section d-flex justify-content-center align-items-center">
            <ul class="nav nav-pills button-tab justify-content-center duration-tab" role="tablist">
                <?php $firsts = true; foreach ($durations as $duration_name): ?>
                    <li class="nav-item" role="presentation">
                        <button class="duration-tab nav-link <?= $firsts ? 'active' : '' ?>" 
                                data-bs-toggle="pill" 
                                data-bs-target="#tabp-<?= $pkg_id ?>-<?= md5($duration_name) ?>">
                            <?= formatDurationTab($duration_name) ?>
                        </button>
                    </li>
                <?php $firsts = false; endforeach; ?>
            </ul>
        </div>
                
        <div class="tab-content">
            <?php $firsts = true; foreach ($durations as $duration_name): ?>
                <div class="tab-pane fade <?= $firsts ? 'show active' : '' ?>" 
                        id="tabp-<?= $pkg_id ?>-<?= md5($duration_name) ?>">
                    <div class="row gy-4 mt-3">
                        <?php foreach ($durations_by_package[$pkg_id][$duration_name] as $d): ?>
                            <div class="col-xxl-12 col-sm-6">
                                <div class="pricing-plan radius-24">
                                    <h5 class="plan-title"><?= htmlspecialchars($d['title']) ?></h5>
                                    <!--<p><?= htmlspecialchars($d['subtitle']) ?></p>-->

                                    <div class="plan-price">
                                        <!--<p class="text-sm text-muted text-decoration-line-through mb-0">-->
                                        <!--    <?= $symbol ?> <?= number_format($d['preview_price']) ?>-->
                                        <!--</p>-->
                                        <span class="price"><?= $symbol ?> <?= number_format($d['price']) ?></span>
                                        <span class="text">/ <?= formatDurationLabel($d['duration']) ?></span>
                                    </div>

                                    <p class="plan-desc"><?= htmlspecialchars($d['description']) ?></p>

                                    <ul class="p-0">
                                        <?php foreach ($features_by_package[$pkg_id] ?? [] as $feat): ?>
                                            <li class="d-flex align-items-center gap-16 mb-16 check-points">
                                                <!--<span class="fa fa-check text-white lufera-bg rounded-circle justify-content-center align-items-center d-flex"></span>-->
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                    viewBox="0 0 15 15" fill="none">
                                                    <path opacity="0.992" fill-rule="evenodd" clipRule="evenodd"
                                                        d="M7.22393 0C10.1444 0.0048682 12.3871 1.22628 13.952 3.66423C15.1321 5.76513 15.3168 7.95136 14.5062 10.2229C13.3721 12.8859 11.3758 14.4614 8.51719 14.9495C5.62984 15.2424 3.28454 14.2622 1.48125 12.0088C-0.0776275 9.77987 -0.406074 7.37811 0.495906 4.80353C1.26674 2.9139 2.5754 1.53341 4.42187 0.662026C5.31983 0.270692 6.25384 0.0500183 7.22393 0ZM11.2269 4.43403C11.6225 4.43526 11.7508 4.62002 11.6118 4.98829C9.90797 6.93843 8.20413 8.8886 6.50032 10.8387C6.34295 10.9814 6.17359 10.9968 5.99226 10.8849C5.24811 9.89445 4.50399 8.904 3.75985 7.91352C3.63052 7.68883 3.67671 7.50922 3.89841 7.37466C3.99079 7.35412 4.08316 7.35412 4.17554 7.37466C4.74004 7.75441 5.30458 8.1342 5.86909 8.51396C6.02234 8.62678 6.18659 8.64729 6.36176 8.57554C7.97994 7.1872 9.60165 5.80667 11.2269 4.43403Z"
                                                        fill="#1AD079" />
                                                </svg>
                                                <?= htmlspecialchars($feat) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
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
