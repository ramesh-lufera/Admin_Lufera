<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Categories</title>
<style>
    /* Styling for disabled button to appear blurred */
    .disabled {
        pointer-events: none;  /* Prevents clicking */
        opacity: 0.5;  /* Makes the button appear blurred */
    }
</style>
</head>

<?php 
    include './partials/layouts/layoutTop.php';
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $Id = $_SESSION['user_id'];
    $query = "SELECT * FROM categories ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);

    // Handle category creation
    if (isset($_POST['save'])) {
        $cat_name = trim($_POST['cat_name']);
        $cat_url = trim($_POST['cat_url']);
        $cat_type = trim($_POST['cat_type']);

        if (!str_ends_with($cat_url, '.php')) {
            $cat_url .= '.php';
        }

        $catSlug = strtolower(preg_replace('/\s+/', '-', $cat_url));

        if (!empty($cat_name)) {
            $stmt = $conn->prepare("INSERT INTO categories (cat_name, cat_url, cat_type) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $cat_name, $catSlug, $cat_type);
            $stmt->execute();

            //$file_path = dirname(__DIR__) . '/' . $catSlug;
            $file_path = realpath(__DIR__) . '/' . $catSlug;


            $catSlug1 = pathinfo($catSlug, PATHINFO_FILENAME);
            $det_file_name = $catSlug1 . '-det.php'; 
            //$det_file_path = dirname(__DIR__) . '/' . $det_file_name;
            $det_file_path = realpath(__DIR__) . '/' . $det_file_name;

            $manageLink = $det_file_name;
            $pageTitle = ucwords($cat_name);

            if (!file_exists($file_path)) {
                $default_content = <<<PHP
                    <?php include './partials/layouts/layoutTop.php'; ?>
                        <?php
                            \$Id = \$_SESSION['user_id'];
                            
                            if (isset(\$_GET['cat_id']) && intval(\$_GET['cat_id']) > 0) {
                                \$_SESSION['cat_id'] = intval(\$_GET['cat_id']);
                            } elseif (!isset(\$_GET['cat_id'])) {
                                // Clear the old cat_id from session when not present in URL
                                unset(\$_SESSION['cat_id']);
                            }

                            \$cat_id = \$_SESSION['cat_id'] ?? 0;

                            \$user = "select * from users where id = \$Id";
                            \$res = \$conn ->query(\$user);
                            \$row = \$res ->fetch_assoc();
                            \$UserId = \$row['id'];
                            \$role = \$row['role'];

                            \$category = "select * from categories where cat_id = \$cat_id";
                            \$cat_query = \$conn ->query(\$category);
                            \$cat_row = \$cat_query ->fetch_assoc();
                            
                            if (\$role == '1' || \$role == '2' || \$role == '7') {
                                \$sql = "
                                    SELECT 
                                        websites.id AS web_id,
                                        users.user_id,
                                        users.id,
                                        users.business_name,
                                        CASE 
                                            WHEN websites.type = 'package' THEN package.package_name
                                            WHEN websites.type = 'product' THEN products.name
                                            ELSE websites.plan
                                        END AS plan_name,
                                        websites.domain,
                                        websites.access_www,
                                        websites.status,
                                        websites.created_at,
                                        websites.duration,
                                        websites.product_id,
                                        websites.type,
                                        JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '$.name.value')) AS json_name
                                    FROM 
                                        users 
                                    JOIN 
                                        websites ON users.id = websites.user_id
                                    LEFT JOIN
                                        `json` ON `json`.website_id = websites.id
                                    LEFT JOIN 
                                        package ON (websites.type = 'package' AND websites.plan = package.id)
                                    LEFT JOIN 
                                        products ON (websites.type = 'product' AND websites.plan = products.id)
                                    WHERE 
                                        websites.cat_id = ?
                                ";
                                \$stmt = \$conn->prepare(\$sql);
                                if (!\$stmt) {
                                    die("Prepare failed: " . \$conn->error);
                                }
                                \$stmt->bind_param("i", \$cat_id);
                            } else {
                                \$sql = "
                                    SELECT 
                                        websites.id AS web_id,
                                        users.user_id,
                                        users.id,
                                        users.business_name,
                                        CASE 
                                            WHEN websites.type = 'package' THEN package.package_name
                                            WHEN websites.type = 'product' THEN products.name
                                            ELSE websites.plan
                                        END AS plan_name,
                                        websites.domain,
                                        websites.access_www,
                                        websites.status,
                                        websites.created_at,
                                        websites.duration,
                                        websites.product_id,
                                        websites.type,
                                        JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '$.name.value')) AS json_name
                                    FROM 
                                        users
                                    JOIN 
                                        websites ON users.id = websites.user_id
                                    LEFT JOIN 
                                        `json` ON `json`.website_id = websites.id
                                    LEFT JOIN 
                                        package ON (websites.type = 'package' AND websites.plan = package.id)
                                    LEFT JOIN 
                                        products ON (websites.type = 'product' AND websites.plan = products.id)
                                    WHERE 
                                        websites.user_id = ? AND websites.cat_id = ?
                                ";
                                \$stmt = \$conn->prepare(\$sql);
                                if (!\$stmt) {
                                    die("Prepare failed: " . \$conn->error);
                                }
                                \$stmt->bind_param("ii", \$UserId, \$cat_id);
                            }

                            \$stmt->execute();
                            \$result = \$stmt->get_result();

                            \$websites = [];
                            while (\$row = mysqli_fetch_assoc(\$result)) {
                                \$websites[] = \$row;
                            }

                            // Number of websites per page
                            \$websitesPerPage = 5;

                            // Get the current page from URL, default is 1
                            \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;

                            // Calculate the starting index for the websites to display on this page
                            \$startIndex = (\$page - 1) * \$websitesPerPage;

                            // Slice the websites array to get only the websites for the current page
                            \$websitesOnPage = array_slice(\$websites, \$startIndex, \$websitesPerPage);

                            // Calculate the total number of pages
                            \$totalPages = ceil(count(\$websites) / \$websitesPerPage);
                        ?>
                        <!DOCTYPE html>
                        <html lang="en">
                        <head>
                        <meta charset="UTF-8">
                        <title>Websites</title>
                        <style>
                            :root {
                            --yellow: #fec700;
                            --black: #101010;
                            --mild-blue: #e6f0ff;
                            }

                            .content-wrapper {
                            width: 100%;
                            /* max-width: 1200px; */
                            margin: 20px auto;
                            padding: 10px 15px;
                            }

                            .search-card {
                            background-color: #fff;
                            border-radius: 8px;
                            padding: 15px 20px;
                            margin-bottom: 20px;
                            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                            display: flex;
                            flex-wrap: wrap;
                            justify-content: space-between;
                            align-items: center;
                            gap: 10px;
                            }

                            .search-container {
                            position: relative;
                            flex: 1 1 300px;
                            max-width: 400px;
                            }

                            .search-icon {
                            position: absolute;
                            left: 10px;
                            top: 50%;
                            transform: translateY(-50%);
                            font-size: 16px;
                            color: #999;
                            pointer-events: none;
                            }

                            .search-container input[type="text"] {
                            width: 100%;
                            padding: 10px 10px 10px 35px;
                            font-size: 16px;
                            border: 2px solid var(--yellow);
                            border-radius: 5px;
                            box-sizing: border-box;
                            }

                            .add-btn {
                            padding: 10px 16px;
                            background-color: var(--yellow);
                            color: var(--black);
                            border: none;
                            border-radius: 5px;
                            font-weight: bold;
                            text-decoration: none;
                            cursor: pointer;
                            white-space: nowrap;
                            flex-shrink: 0;
                            }

                            .list-section {
                            background-color: #fff;
                            border-radius: 8px;
                            padding: 20px;
                            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                            }

                            .list-section h5 {
                            margin-top: 0;
                            margin-bottom: 15px;
                            font-size: 20px;
                            }

                            .list-wrapper {
                            display: flex;
                            flex-direction: column;
                            gap: 15px;
                            }

                            .list-item {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 15px 20px;
                            border: 1px solid #eee;
                            border-left: 5px solid var(--yellow);
                            border-radius: 6px;
                            background-color: #fff;
                            transition: box-shadow 0.2s ease;
                            flex-wrap: wrap;
                            gap: 10px;
                            }

                            .list-item:hover {
                            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                            }

                            .site-info {
                            flex: 1 1 60%;
                            display: flex;
                            flex-direction: column;
                            gap: 5px;
                            min-width: 0;
                            }

                            .site-info-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            }

                            .site-info-header h6 {
                            margin: 0 0 8px 0;
                            font-weight: bold;
                            font-size: 20px;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            max-width: 60%;
                            }

                            .site-info-header .plan {
                            font-size: 14px;
                            color: #555;
                            white-space: nowrap;
                            }

                            .site-info-meta {
                            font-size: 14px;
                            color: #555;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            margin-top: 5px;
                            }

                            .manage-btn-wrapper {
                            flex-shrink: 0;
                            display: flex;
                            flex-direction: column;
                            align-items: flex-end;
                            gap: 8px;
                            }

                            .manage-btn-wrapper .plan {
                            font-size: 14px;
                            color: #555;
                            }

                            .dashboard-btn {
                            background-color: var(--yellow);
                            color: var(--black);
                            padding: 8px 16px;
                            border-radius: 5px;
                            font-weight: bold;
                            text-decoration: none;
                            white-space: nowrap;
                            transition: background-color 0.3s ease;
                            }

                            .dashboard-btn:hover {
                            background-color: #e5b800;
                            }

                            .pagination {
                            display: flex;
                            justify-content: flex-end;
                            gap: 10px;
                            margin-top: 20px;
                            }

                            .pagination a {
                            padding: 8px 15px;
                            background-color: var(--yellow);
                            color: var(--black);
                            border-radius: 5px;
                            text-decoration: none;
                            }

                            .status-active {
                            border-left: 5px solid var(--yellow);
                            /* border-left: 5px solid #4caf50; Green */
                            }

                            .status-pending {
                            border-left: 5px solid #ff9800; /* Orange */
                            }

                            .status-expired {
                            border-left: 5px solid #f44336; /* Red */
                            }

                            .domain-text-approved {
                                color: #fec700;
                            }
                            .domain-text-pending {
                                color: orange;
                            }

                            /* Increase font sizes */
                            .site-info-header h6 {
                            font-size: 20px !important;
                            }

                            .site-info-meta {
                            font-size: 16px;
                            }

                            .manage-btn-wrapper .plan {
                            font-size: 16px;
                            }

                            .dashboard-btn {
                            font-size: 16px;
                            }

                            /* Status-based colors for domain only */
                            .domain-text-active {
                            /* color: #4caf50; */
                            color: var(--yellow);
                            }

                            .domain-text-pending {
                            color: #ff9800;
                            }

                            .domain-text-expired {
                            color: #f44336;
                            }

                            .no-website {
                            justify-content: center;
                            font-size: 18px; 
                            color: #888;
                            }

                            /* Responsive */
                            @media (max-width: 700px) {
                            .list-item {
                                flex-direction: column;
                                align-items: flex-start;
                            }

                            .site-info {
                                flex: 1 1 100%;
                            }

                            .site-info-header {
                                flex-direction: column;
                                align-items: flex-start;
                                gap: 3px;
                            }

                            .site-info-header h6 {
                                max-width: 100%;
                            }

                            .site-info-header .plan {
                                font-size: 14px;
                            }

                            .manage-btn-wrapper {
                                width: 100%;
                                margin-top: 10px;
                                align-items: flex-start;
                            }
                            }
                        </style>
                        </head>
                        <body>
                            <div class="content-wrapper">

                            <!-- Title -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
                                <h6 class="fw-semibold mb-0">$pageTitle</h6>
                                <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>                                 
                            </div>
                            
                            <!-- Search + Add -->
                            <div class="search-card bg-base">
                                <div class="search-container">
                                <span class="search-icon">&#128269;</span>
                                <input type="text" id="searchInput" placeholder="Search $pageTitle..." />
                                </div>
                                <a href="view-$catSlug1.php" class="add-btn">+ Add New $pageTitle</a>
                            </div>

                            <!-- Website List -->
                            <!-- <div class="list-section" id="websiteList"> -->
                                <!-- <h5>Business WordPress Hosting</h5> -->

                                <div class="list-wrapper" id="websiteList">
                                <?php if (empty(\$websitesOnPage)): ?>
                                    <div class="list-item bg-base no-website">
                                    No $pageTitle found.
                                    </div>
                                <?php else: ?>
                                    <?php foreach (\$websitesOnPage as \$site): ?>
                                    <?php
                                         \$status = strtolower(\$site['status']);
                                        \$CreatedAt = \$site['created_at'];
                                        \$Duration = \$site['duration'];

                                        
                                            \$statusClass = 'status-pending';
                                            if (\$status === 'active') \$statusClass = 'status-active';
                                            elseif (\$status === 'expired') \$statusClass = 'status-expired';

                                        
                                            \$domainColorClass = 'domain-text-pending'; // default
                                            if (\$status === 'approved') {
                                                \$domainColorClass = 'domain-text-approved';
                                            }

                                            

                                \$startDate = new DateTime(\$CreatedAt);
                                \$endDate = (clone \$startDate)->modify("+{\$Duration}");
                                \$Validity = \$startDate->format("d-m-Y") . " to " . \$endDate->format("d-m-Y");

                                if (\$status === 'approved') {
                                    \$expiresText = htmlspecialchars(\$Validity);
                                    \$color = '#89836f'; // yellow
                                } else {
                                    \$expiresText = 'N/A';
                                    \$color = 'orange'; // fallback
                                }
                                    ?>
                                    <div class="list-item bg-base <?php echo \$statusClass; ?>">
                                        <div class="site-info">
                                        <!-- Domain Title -->
                                        <div class="site-info-header">
                                            <h6>
                                            // <?php echo htmlspecialchars(\$site['plan']); ?>
                                            <?php echo htmlspecialchars(\$site['plan_name']); ?>
                                            <span style="visibility:hidden"><?php echo htmlspecialchars(\$site['user_id']); ?></span>
                                            </h6>
                                        </div>
                                        
                                        <div class="site-info-meta">
                                            Expires:
                                            <span style="color: <?php echo \$color; ?>;">
                                                <?php echo \$Validity; ?>
                                            </span>
                                        </div>
                                        </div>
                                        <div class="manage-btn-wrapper">
                                        <a href="$manageLink?website_id=<?php echo (int)\$site['web_id']; ?>&product_id=<?php echo (int)\$site['product_id']; ?>&type=<?php echo \$site['type']; ?>" class="dashboard-btn">Manage</a>

                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </div>

                                <!-- Pagination -->
                                <div class="pagination">
                                <?php if (\$page > 1): ?>
                                    <a href="?cat_id=?<?php echo \$cat_id; ?>&page=<?php echo \$page - 1; ?>">Previous</a> 
                                <?php endif; ?>

                                <?php for (\$i = 1; \$i <= \$totalPages; \$i++): ?>
                                    <a href="?cat_id=?<?php echo \$cat_id; ?>&page=<?php echo \$i; ?>" class="<?php echo \$i === \$page ? 'active' : ''; ?>">
                                    <?php echo \$i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if (\$page < \$totalPages): ?>
                                    <a href="?cat_id=?<?php echo \$cat_id; ?>&page=<?php echo \$page + 1; ?>">Next</a>
                                <?php endif; ?>
                                </div>

                            <!-- </div> -->
                            </div>

                            <script>
                            const searchInput = document.getElementById('searchInput');
                            searchInput.addEventListener('keyup', function () {
                                const filter = searchInput.value.toLowerCase();
                                const items = document.querySelectorAll("#websiteList .list-item");
                                items.forEach(item => {
                                const text = item.innerText.toLowerCase();
                                item.style.display = text.includes(filter) ? '' : 'none';
                                });
                            });
                            </script>

                        </body>
                        </html>
                    <?php include './partials/layouts/layoutBottom.php' ?>
                    PHP;

                file_put_contents($file_path, $default_content);

                $view_file_name = "view-$catSlug1.php";
                //$view_file_path = dirname(__DIR__) . '/' . $view_file_name;
                $view_file_path = realpath(__DIR__) . '/' . $view_file_name;

                if (!file_exists($view_file_path)) {
                    $view_content = <<<PHP
                        <?php include './view-package.php'; ?>
                    PHP;

                    file_put_contents($view_file_path, $view_content);
                }
            }

            //$_SESSION['swal_success'] = "Category created";
            echo "
                <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Category Created',
                    showConfirmButton: true
                }).then(() => {
                    window.history.back();
                });
                </script>";
        }
        exit;
    }

    // Edit category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_cat_id'], $_POST['edit_cat_name'], $_POST['edit_cat_url'], $_POST['edit_cat_type'])) {
        $cat_id = intval($_POST['edit_cat_id']);
        $edit_cat_name = trim($_POST['edit_cat_name']);
        $edit_cat_url = trim($_POST['edit_cat_url']);
        $edit_cat_type = trim($_POST['edit_cat_type']);
        
        if (!str_ends_with($edit_cat_url, '.php')) {
            $edit_cat_url .= '.php';
        }

        $editcatSlug = strtolower(preg_replace('/\s+/', '-', $edit_cat_url));
        //$file_path = dirname(__DIR__) . '/' . $editcatSlug;
        $file_path = realpath(__DIR__) . '/' . $editcatSlug;


        $stmt = $conn->prepare("UPDATE categories SET cat_name = ?, cat_url = ?, cat_type = ? WHERE cat_id = ?");
        $stmt->bind_param("sssi", $edit_cat_name, $editcatSlug, $edit_cat_type, $cat_id);
        $stmt->execute();

        $editcatSlug1 = pathinfo($editcatSlug, PATHINFO_FILENAME);
        $det_file_name1 = $editcatSlug1 . '-det.php'; 
        //$det_file_path1 = dirname(__DIR__) . '/' . $det_file_name1;
        $det_file_path1 = realpath(__DIR__) . '/' . $det_file_name1;

        $manageLink1 = $det_file_name1;
        $pageTitle1 = ucwords($edit_cat_name);

        if (!file_exists($file_path)) {
            $default_content = <<<PHP
                <?php include './partials/layouts/layoutTop.php'; ?>
                    <?php
                        \$Id = \$_SESSION['user_id'];
                        
                        if (isset(\$_GET['cat_id']) && intval(\$_GET['cat_id']) > 0) {
                            \$_SESSION['cat_id'] = intval(\$_GET['cat_id']);
                        } elseif (!isset(\$_GET['cat_id'])) {
                            // Clear the old cat_id from session when not present in URL
                            unset(\$_SESSION['cat_id']);
                        }

                        \$cat_id = \$_SESSION['cat_id'] ?? 0;

                        \$user = "select * from users where id = \$Id";
                        \$res = \$conn ->query(\$user);
                        \$row = \$res ->fetch_assoc();
                        \$UserId = \$row['user_id'];
                        \$UserId = \$row['id'];
                        \$role = \$row['role'];

                        if (\$role == '1' || \$role == '2' || \$role == '7') {
                        \$stmt = \$conn->prepare("
                            SELECT 
                                websites.id,
                                users.user_id,
                                users.id,
                                users.business_name,
                                websites.plan,
                                websites.domain,
                                websites.access_www,
                                websites.status,
                                websites.created_at,
                                websites.duration
                            FROM 
                                users 
                            JOIN 
                                websites ON users.id = websites.user_id
                            WHERE 
                                websites.cat_id = ?
                        ");
                        \$stmt->bind_param("i", \$cat_id);
                        } else {
                            \$stmt = \$conn->prepare("
                                SELECT 
                                    websites.id,
                                    users.user_id,
                                    users.id,
                                    users.business_name,
                                    websites.plan,
                                    websites.domain,
                                    websites.access_www,
                                    websites.status,
                                    websites.created_at,
                                    websites.duration 
                                FROM 
                                    users 
                                JOIN 
                                    websites ON users.id = websites.user_id 
                                WHERE 
                                    websites.user_id = ? AND websites.cat_id = ?
                            ");
                            \$stmt->bind_param("ii", \$UserId, \$cat_id);
                        }

                        \$stmt->execute();
                        \$result = \$stmt->get_result();

                        \$websites = [];
                        while (\$row = mysqli_fetch_assoc(\$result)) {
                            \$websites[] = \$row;
                        }

                        // Number of websites per page
                        \$websitesPerPage = 5;

                        // Get the current page from URL, default is 1
                        \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;

                        // Calculate the starting index for the websites to display on this page
                        \$startIndex = (\$page - 1) * \$websitesPerPage;

                        // Slice the websites array to get only the websites for the current page
                        \$websitesOnPage = array_slice(\$websites, \$startIndex, \$websitesPerPage);

                        // Calculate the total number of pages
                        \$totalPages = ceil(count(\$websites) / \$websitesPerPage);
                    ?>
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                    <meta charset="UTF-8">
                    <title>Websites</title>
                    <style>
                        :root {
                        --yellow: #fec700;
                        --black: #101010;
                        --mild-blue: #e6f0ff;
                        }

                        .content-wrapper {
                        width: 100%;
                        /* max-width: 1200px; */
                        margin: 20px auto;
                        padding: 10px 15px;
                        }

                        .search-card {
                        background-color: #fff;
                        border-radius: 8px;
                        padding: 15px 20px;
                        margin-bottom: 20px;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: space-between;
                        align-items: center;
                        gap: 10px;
                        }

                        .search-container {
                        position: relative;
                        flex: 1 1 300px;
                        max-width: 400px;
                        }

                        .search-icon {
                        position: absolute;
                        left: 10px;
                        top: 50%;
                        transform: translateY(-50%);
                        font-size: 16px;
                        color: #999;
                        pointer-events: none;
                        }

                        .search-container input[type="text"] {
                        width: 100%;
                        padding: 10px 10px 10px 35px;
                        font-size: 16px;
                        border: 2px solid var(--yellow);
                        border-radius: 5px;
                        box-sizing: border-box;
                        }

                        .add-btn {
                        padding: 10px 16px;
                        background-color: var(--yellow);
                        color: var(--black);
                        border: none;
                        border-radius: 5px;
                        font-weight: bold;
                        text-decoration: none;
                        cursor: pointer;
                        white-space: nowrap;
                        flex-shrink: 0;
                        }

                        .list-section {
                        background-color: #fff;
                        border-radius: 8px;
                        padding: 20px;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                        }

                        .list-section h5 {
                        margin-top: 0;
                        margin-bottom: 15px;
                        font-size: 20px;
                        }

                        .list-wrapper {
                        display: flex;
                        flex-direction: column;
                        gap: 15px;
                        }

                        .list-item {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 15px 20px;
                        border: 1px solid #eee;
                        border-left: 5px solid var(--yellow);
                        border-radius: 6px;
                        background-color: #fff;
                        transition: box-shadow 0.2s ease;
                        flex-wrap: wrap;
                        gap: 10px;
                        }

                        .list-item:hover {
                        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                        }

                        .site-info {
                        flex: 1 1 60%;
                        display: flex;
                        flex-direction: column;
                        gap: 5px;
                        min-width: 0;
                        }

                        .site-info-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        }

                        .site-info-header h6 {
                        margin: 0 0 8px 0;
                        font-weight: bold;
                        font-size: 20px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        max-width: 60%;
                        }

                        .site-info-header .plan {
                        font-size: 14px;
                        color: #555;
                        white-space: nowrap;
                        }

                        .site-info-meta {
                        font-size: 14px;
                        color: #555;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        margin-top: 5px;
                        }

                        .manage-btn-wrapper {
                        flex-shrink: 0;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                        gap: 8px;
                        }

                        .manage-btn-wrapper .plan {
                        font-size: 14px;
                        color: #555;
                        }

                        .dashboard-btn {
                        background-color: var(--yellow);
                        color: var(--black);
                        padding: 8px 16px;
                        border-radius: 5px;
                        font-weight: bold;
                        text-decoration: none;
                        white-space: nowrap;
                        transition: background-color 0.3s ease;
                        }

                        .dashboard-btn:hover {
                        background-color: #e5b800;
                        }

                        .pagination {
                        display: flex;
                        justify-content: flex-end;
                        gap: 10px;
                        margin-top: 20px;
                        }

                        .pagination a {
                        padding: 8px 15px;
                        background-color: var(--yellow);
                        color: var(--black);
                        border-radius: 5px;
                        text-decoration: none;
                        }

                        .status-active {
                        border-left: 5px solid var(--yellow);
                        /* border-left: 5px solid #4caf50; Green */
                        }

                        .status-pending {
                        border-left: 5px solid #ff9800; /* Orange */
                        }

                        .status-expired {
                        border-left: 5px solid #f44336; /* Red */
                        }

                        .domain-text-approved {
                            color: #fec700;
                        }
                        .domain-text-pending {
                            color: orange;
                        }

                        /* Increase font sizes */
                        .site-info-header h6 {
                        font-size: 20px !important;
                        }

                        .site-info-meta {
                        font-size: 16px;
                        }

                        .manage-btn-wrapper .plan {
                        font-size: 16px;
                        }

                        .dashboard-btn {
                        font-size: 16px;
                        }

                        /* Status-based colors for domain only */
                        .domain-text-active {
                        /* color: #4caf50; */
                        color: var(--yellow);
                        }

                        .domain-text-pending {
                        color: #ff9800;
                        }

                        .domain-text-expired {
                        color: #f44336;
                        }

                        .no-website {
                        justify-content: center;
                        font-size: 18px; 
                        color: #888;
                        }

                        /* Responsive */
                        @media (max-width: 700px) {
                        .list-item {
                            flex-direction: column;
                            align-items: flex-start;
                        }

                        .site-info {
                            flex: 1 1 100%;
                        }

                        .site-info-header {
                            flex-direction: column;
                            align-items: flex-start;
                            gap: 3px;
                        }

                        .site-info-header h6 {
                            max-width: 100%;
                        }

                        .site-info-header .plan {
                            font-size: 14px;
                        }

                        .manage-btn-wrapper {
                            width: 100%;
                            margin-top: 10px;
                            align-items: flex-start;
                        }
                        }
                    </style>
                    </head>
                    <body>
                        <div class="content-wrapper">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
                                <h6 class="fw-semibold mb-0">$pageTitle1</h6>
                                <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
                            </div>
                        <!-- Search + Add -->
                        <div class="search-card bg-base">
                            <div class="search-container">
                            <span class="search-icon">&#128269;</span>
                            <input type="text" id="searchInput" placeholder="Search $pageTitle1..." />
                            </div>
                            <a href="view-$editcatSlug1.php" class="add-btn">+ Add New $pageTitle1</a>
                        </div>

                        <!-- Website List -->
                        <!-- <div class="list-section" id="websiteList"> -->
                            <!-- <h5>Business WordPress Hosting</h5> -->

                            <div class="list-wrapper" id="websiteList">
                            <?php if (empty(\$websitesOnPage)): ?>
                                <div class="list-item bg-base no-website">
                                No $pageTitle1 found.
                                </div>
                            <?php else: ?>
                                <?php foreach (\$websitesOnPage as \$site): ?>
                                <?php
                                        \$status = strtolower(\$site['status']);
                                    \$CreatedAt = \$site['created_at'];
                                    \$Duration = \$site['duration'];

                                    
                                        \$statusClass = 'status-pending';
                                        if (\$status === 'active') \$statusClass = 'status-active';
                                        elseif (\$status === 'expired') \$statusClass = 'status-expired';

                                    
                                        \$domainColorClass = 'domain-text-pending'; // default
                                        if (\$status === 'approved') {
                                            \$domainColorClass = 'domain-text-approved';
                                        }

                                        

                            \$startDate = new DateTime(\$CreatedAt);
                            \$endDate = (clone \$startDate)->modify("+{\$Duration}");
                            \$Validity = \$startDate->format("d-m-Y") . " to " . \$endDate->format("d-m-Y");

                            if (\$status === 'approved') {
                                \$expiresText = htmlspecialchars(\$Validity);
                                \$color = '#89836f'; // yellow
                            } else {
                                \$expiresText = 'N/A';
                                \$color = 'orange'; // fallback
                            }
                                ?>
                                <div class="list-item bg-base <?php echo \$statusClass; ?>">
                                    <div class="site-info">
                                    <!-- Domain Title -->
                                    <div class="site-info-header">
                                        <h6>
                                        <?php echo htmlspecialchars(\$site['plan']); ?>
                                        <span style="visibility:hidden"><?php echo htmlspecialchars(\$site['user_id']); ?></span>
                                        </h6>
                                    </div>
                                    <!-- Website (no link, color applied only to domain text) -->
                                    <div class="site-info-meta">
                                        $pageTitle1: 
                                        <span class="<?php echo \$domainColorClass; ?>">
                                            <?php echo htmlspecialchars(\$site['access_www']); ?>
                                        </span>
                                    </div>
                                    <div class="site-info-meta" style="color: <?php echo \$color; ?>;">
                                        <strong>Expires:</strong>
                                        <?php echo \$Validity; ?>
                                    </div>
                                    </div>
                                    <div class="manage-btn-wrapper">
                                    <!-- <a href="dashboard.php?site=<?php echo urlencode(\$site['domain']); ?>" class="dashboard-btn">Manage</a> -->
                                    <a href="$manageLink1?website_id=<?php echo (int)\$site['id']; ?>" class="dashboard-btn">Manage</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination">
                            <?php if (\$page > 1): ?>
                                <a href="?page=<?php echo \$page - 1; ?>">Previous</a>
                            <?php endif; ?>

                            <?php for (\$i = 1; \$i <= \$totalPages; \$i++): ?>
                                <a href="?page=<?php echo \$i; ?>" class="<?php echo \$i === \$page ? 'active' : ''; ?>">
                                <?php echo \$i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if (\$page < \$totalPages): ?>
                                <a href="?page=<?php echo \$page + 1; ?>">Next</a>
                            <?php endif; ?>
                            </div>

                        <!-- </div> -->
                        </div>

                        <script>
                            const searchInput = document.getElementById('searchInput');
                            searchInput.addEventListener('keyup', function () {
                                const filter = searchInput.value.toLowerCase();
                                const items = document.querySelectorAll("#websiteList .list-item");
                                items.forEach(item => {
                                const text = item.innerText.toLowerCase();
                                item.style.display = text.includes(filter) ? '' : 'none';
                                });
                            });
                        </script>
                    </body>
                    </html>
                <?php include './partials/layouts/layoutBottom.php' ?>
            PHP;

            file_put_contents($file_path, $default_content);
        }
    
        //$_SESSION['swal_success'] = "Category updated";

        echo "
        <script>
        Swal.fire({
            icon: 'success',
            title: 'Category Updated',
            showConfirmButton: true
        }).then(() => {
            window.history.back();
        });
        </script>";
        exit;
    }

    // Delete category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cat_id'])) {
        $cat_id = intval($_POST['delete_cat_id']);

        $stmt = $conn->prepare("SELECT cat_url FROM categories WHERE cat_id = ?");
        $stmt->bind_param("i", $cat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cat = $result->fetch_assoc();

        $catUrlRaw = $cat['cat_url'] ?? null;

        if ($catUrlRaw) {
            $catUrlRawEdit = strtolower(preg_replace('/\s+/', '-', $catUrlRaw));
            $catUrlFinal = pathinfo($catUrlRawEdit, PATHINFO_FILENAME);

            //$baseDir = dirname(__DIR__);
            $baseDir = realpath(__DIR__); 
            $filesToDelete = [
                "$baseDir/{$catUrlFinal}.php",
                "$baseDir/{$catUrlFinal}-det.php",
                "$baseDir/add-{$catUrlFinal}.php",
                "$baseDir/view-{$catUrlFinal}.php",
                // "$baseDir/{$catUrlFinal}-wizard.php"
            ];

            foreach ($filesToDelete as $file) {
                if (file_exists($file)) {
                    unlink($file); // Delete the file
                }
            }
        }

        $stmt = $conn->prepare("DELETE FROM categories WHERE cat_id = ?");
        $stmt->bind_param("i", $cat_id);
        $stmt->execute();

        //$_SESSION['swal_success'] = "Category deleted";

        echo "
        <script>
        Swal.fire({
            icon: 'success',
            title: 'Category deleted',
            showConfirmButton: true
        }).then(() => {
            window.history.back();
        });
        </script>";
        exit;
    }
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            <h6 class="fw-semibold mb-0">Categories</h6>
            <a data-bs-toggle="modal" 
            data-bs-target="#add-category-modal" class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" >
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                Add New Category
            </a>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table mb-0" id="productPackageTable">
                        <thead>
                            <tr>
                                <th scope="col">Category Name</th>
                                <th scope="col">Category URL</th>
                                <th scope="col">Category Type</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['cat_name']) ?></td>
                                <td><?= htmlspecialchars($row['cat_url']) ?></td>
                                <td><?= htmlspecialchars($row['cat_type']) ?></td>
                                <td class="text-center">
                                    <a onclick="openEditModal('<?= $row['cat_id'] ?>', '<?= htmlspecialchars($row['cat_name']) ?>', '<?= htmlspecialchars($row['cat_url']) ?>', '<?= htmlspecialchars($row['cat_type']) ?>')" class="fa fa-edit fw-medium w-32-px h-32-px bg-warning-focus text-warning-main rounded-circle d-inline-flex align-items-center justify-content-center cursor-pointer">
                                    </a>
                                    <form method="post" class="delete-form d-inline">
                                    <input type="hidden" name="delete_cat_id" value="<?= $row['cat_id'] ?>">
                                    <a onclick="confirmDelete(this)" class="fa fa-trash-alt fw-medium w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center cursor-pointer">
                                    </a>
                                </form>
                                    
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Edit Category Modal -->
<div class="modal fade" id="edit-category-modal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_cat_id" id="edit_cat_id">
                    <div class="mb-8">
                        <label for="edit_cat_name" class="form-label">Name</label>
                        <input type="text" class="form-control" name="edit_cat_name" id="edit_cat_name" required>
                    </div>
                    <div class="mb-8">
                        <label for="edit_cat_url" class="form-label">URL</label>
                        <input type="text" class="form-control" name="edit_cat_url" id="edit_cat_url" required readonly>
                    </div>
                    <div class="mb-8">
                        <label for="edit_cat_type" class="form-label">Type</label>
                        <select class="form-control" name="edit_cat_type" id="edit_cat_type" required>
                            <option value="package">Package</option>
                            <option value="product">Product</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn lufera-bg" value="Update">
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="add-category-modal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <form method="post" action="" id="add-category-form">
            <div class="modal-header">
                <h5 class="modal-title">Add New category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-8">
                    <label for="cat_name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="cat_name" id="cat_name" required >
                </div>
                <div class="mb-8">
                    <label for="cat_url" class="form-label">URL</label>
                    <input type="text" class="form-control" name="cat_url" id="cat_url" required >
                </div>
                <div class="mb-8">
                    <label for="cat_type" class="form-label">Type</label>
                    <select class="form-control" name="cat_type" id="cat_type" required>
                        <option value="">Select type</option>
                        <option value="package">Package</option>
                        <option value="product">Product</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <input type="submit" class="btn lufera-bg" name="save" value="Save">
            </div>
        </form>
    </div>
  </div>
</div>
  
<script>
    $(document).ready(function() {
        $('#productPackageTable').DataTable();

        // Toggle Active/Inactive with SweetAlert
        $(document).on('click', '.toggle-status', function() {
            let button = $(this);
            let id = button.data('id');
            let currentStatus = button.data('status');
            let newStatusText = currentStatus == 1 ? 'Inactive' : 'Active';

            Swal.fire({
                title: 'Are you sure?',
                text: `Change status to ${newStatusText}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, change it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'product-handler.php',
                        type: 'POST',
                        data: { action: 'toggle_status', id: id, status: currentStatus },
                        success: function(response) {
                            if (response.success) {
                                if (currentStatus == 1) {
                                    button.removeClass('btn-success').addClass('btn-secondary').text('Inactive').data('status', 0);
                                } else {
                                    button.removeClass('btn-secondary').addClass('btn-success').text('Active').data('status', 1);
                                }
                                Swal.fire('Updated!', `Status changed to ${newStatusText}.`, 'success');
                            } else {
                                Swal.fire('Error!', 'Failed to update status.', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        });

        // Delete Product with SweetAlert
        $(document).on('click', '.delete-product', function() {
            let button = $(this);
            let id = button.data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This product will be deleted (soft delete).",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'product-handler.php',
                        type: 'POST',
                        data: { action: 'delete_product', id: id },
                        success: function(response) {
                            if (response.success) {
                                button.closest('tr').fadeOut();
                                Swal.fire('Deleted!', 'The product has been deleted.', 'success');
                            } else {
                                Swal.fire('Error!', 'Failed to delete product.', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>

<script>
    function openEditModal(id, name, url, type) {
        document.getElementById("edit_cat_id").value = id;
        document.getElementById("edit_cat_name").value = name;
        document.getElementById("edit_cat_url").value = url.replace(".php", "");
        document.getElementById("edit_cat_type").value = type;
        
        // Use Bootstrap's JS API to show modal
        let modal = new bootstrap.Modal(document.getElementById('edit-category-modal'));
        modal.show();
    }
</script>

<script>
    function confirmDelete(button) {
        Swal.fire({
            title: 'Delete Category?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e3342f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            customClass: {
                confirmButton: 'swal2-confirm',
                cancelButton: 'swal2-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = button.closest('form');
                if (form) form.submit();
            }
        });
    }

    $(document).ready(function() {
        $('[data-bs-target="#add-category-modal"]').on('click', function() {
            // Replace '#add-category-form' with your actual form's ID
            $('#add-category-form')[0].reset();
        });
    });

</script>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>