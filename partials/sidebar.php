<?php
    $userid = $_SESSION['user_id'];

    $sql = "SELECT role FROM users WHERE id = $userid";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();

    // Handle category creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_name'], $_POST['cat_url'], $_POST['cat_template'])) {
        $cat_name = trim($_POST['cat_name']);
        $cat_url = trim($_POST['cat_url']);
        $cat_template = trim($_POST['cat_template']);

        if (!str_ends_with($cat_url, '.php')) {
            $cat_url .= '.php';
        }

        $catSlug = strtolower(preg_replace('/\s+/', '-', $cat_url));

        if (!empty($cat_name)) {
            $stmt = $conn->prepare("INSERT INTO categories (cat_name, cat_url, cat_module) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $cat_name, $catSlug, $cat_template);
            $stmt->execute();

            $file_path = dirname(__DIR__) . '/' . $catSlug;

            $catSlug1 = pathinfo($catSlug, PATHINFO_FILENAME);
            $det_file_name = $catSlug1 . '-det.php'; 
            $det_file_path = dirname(__DIR__) . '/' . $det_file_name;

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
                            \$UserId = \$row['user_id'];
                            \$role = \$row['role'];

                            \$category = "select * from categories where cat_id = \$cat_id";
                            \$cat_query = \$conn ->query(\$category);
                            \$cat_row = \$cat_query ->fetch_assoc();
                            \$cat_module = \$cat_row['cat_module'];

                            if (\$role == '1' || \$role == '2' || \$role == '7') {
                            \$sql = "
                                SELECT 
                                    websites.id,
                                    users.user_id,
                                    users.business_name,
                                    websites.plan,
                                    websites.domain,
                                    websites.access_www,
                                    websites.status,
                                    websites.created_at,
                                    websites.duration,
                                    JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '$.name.value')) AS json_name
                                FROM 
                                    users 
                                JOIN 
                                    websites ON users.user_id = websites.user_id
                                LEFT JOIN
                                    `json` ON `json`.website_id = websites.id
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
                                        websites.id,
                                        users.user_id,
                                        users.business_name,
                                        websites.plan,
                                        websites.domain,
                                        websites.access_www,
                                        websites.status,
                                        websites.created_at,
                                        websites.duration,
                                        JSON_UNQUOTE(JSON_EXTRACT(`json`.name, '$.name.value')) AS json_name
                                    FROM 
                                        users
                                    JOIN 
                                        websites ON users.user_id = websites.user_id
                                    LEFT JOIN 
                                        `json` ON `json`.website_id = websites.id
                                    WHERE 
                                        websites.user_id = ? AND websites.cat_id = ?
                                ";
                                \$stmt = \$conn->prepare(\$sql);
                            if (!\$stmt) {
                                die("Prepare failed: " . \$conn->error);
                            }
                            \$stmt->bind_param("si", \$UserId, \$cat_id);
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

                            .header-row {
                            margin-bottom: 20px;
                            }

                            .header-row h5 {
                            font-size: 24px !important;
                            margin: 0;
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

                            .pagination a:hover {
                            background-color: #222;
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
                            <div class="header-row">
                                <h5>$pageTitle</h5>
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
                                            <?php echo htmlspecialchars(\$site['plan']); ?>
                                            <span style="visibility:hidden"><?php echo htmlspecialchars(\$site['user_id']); ?></span>
                                            </h6>
                                        </div>
                                        <!-- Website (no link, color applied only to domain text) -->
                                         <?php if (\$cat_module == "website"): ?> 
                                        <div class="site-info-meta">
                                            $pageTitle: 
                                            <span class="<?php echo \$domainColorClass; ?>">
                                                <?php echo htmlspecialchars(\$site['access_www']); ?>
                                            </span>
                                        </div>
                                        <?php elseif (\$cat_module == "visa"): ?> 
                                        <div class="site-info-meta">
                                            Name: 
                                            <span class="<?php echo \$domainColorClass; ?>">
                                                <?php echo htmlspecialchars(\$site['json_name']); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="site-info-meta">
                                            Expires:
                                            <span style="color: <?php echo \$color; ?>;">
                                                <?php echo \$Validity; ?>
                                            </span>
                                        </div>
                                        </div>
                                        <div class="manage-btn-wrapper">
                                        <!-- <a href="dashboard.php?site=<?php echo urlencode(\$site['domain']); ?>" class="dashboard-btn">Manage</a> -->
                                        <a href="$manageLink?website_id=<?php echo (int)\$site['id']; ?>" class="dashboard-btn">Manage</a>
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

                $view_file_name = "view-$catSlug1.php";
                $view_file_path = dirname(__DIR__) . '/' . $view_file_name;

                if (!file_exists($view_file_path)) {
                    $view_content = <<<PHP
                        <?php include './view-package.php'; ?>
                    PHP;

                    file_put_contents($view_file_path, $view_content);
                }
                
                if ($cat_template === 'website' && !file_exists($det_file_path)) {
                    $det_content = <<<PHP
                        <?php include './category-details/website-details.php'; ?>    
                    PHP;

                    file_put_contents($det_file_path, $det_content);
                }
                if ($cat_template === 'marketing' && !file_exists($det_file_path)) {
                    $det_content = <<<PHP
                        <?php include './category-details/marketing-details.php'; ?>  
                    PHP;

                    file_put_contents($det_file_path, $det_content);
                }
                if ($cat_template === 'visa' && !file_exists($det_file_path)) {
                    $det_content = <<<PHP
                        <?php include './category-details/visa-details.php'; ?>  
                    PHP;

                    file_put_contents($det_file_path, $det_content);
                }
                if ($cat_template === 'website-onboarding' && !file_exists($det_file_path)) {
                    $det_content = <<<PHP
                        <?php include './category-details/website-onboarding-details.php'; ?>  
                    PHP;

                    file_put_contents($det_file_path, $det_content);
                }
                if ($cat_template === 'marketing-onboarding' && !file_exists($det_file_path)) {
                    $det_content = <<<PHP
                        <?php include './category-details/marketing-onboarding-details.php'; ?>  
                    PHP;

                    file_put_contents($det_file_path, $det_content);
                }
                if ($cat_template === 'domain-onboarding' && !file_exists($det_file_path)) {
                    $det_content = <<<PHP
                        <?php include './category-details/domain-onboarding-details.php'; ?>  
                    PHP;

                    file_put_contents($det_file_path, $det_content);
                }
                if ($cat_template === 'email-onboarding' && !file_exists($det_file_path)) {
                    $det_content = <<<PHP
                        <?php include './category-details/email-onboarding-details.php'; ?>  
                    PHP;

                    file_put_contents($det_file_path, $det_content);
                }
                if ($cat_template === 'mobile-app-onboarding' && !file_exists($det_file_path)) {
                    $det_content = <<<PHP
                        <?php include './category-details/mobile-app-onboarding-details.php'; ?>  
                    PHP;

                    file_put_contents($det_file_path, $det_content);
                }
            }

            $_SESSION['swal_success'] = "Category created";
        }

        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }

    // Edit category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_cat_id'], $_POST['edit_cat_name'], $_POST['edit_cat_url'], $_POST['edit_cat_module'])) {
        $cat_id = intval($_POST['edit_cat_id']);
        $cat_name = trim($_POST['edit_cat_name']);
        $edit_cat_url = trim($_POST['edit_cat_url']);
        $cat_module = trim($_POST['edit_cat_module']);
    
        if (!str_ends_with($edit_cat_url, '.php')) {
            $edit_cat_url .= '.php';
        }

        $editcatSlug = strtolower(preg_replace('/\s+/', '-', $edit_cat_url));
        $file_path = dirname(__DIR__) . '/' . $editcatSlug;

        $stmt = $conn->prepare("UPDATE categories SET cat_name = ?, cat_url = ?, cat_module = ? WHERE cat_id = ?");
        $stmt->bind_param("sssi", $cat_name, $editcatSlug, $cat_module, $cat_id);
        $stmt->execute();

        $editcatSlug1 = pathinfo($editcatSlug, PATHINFO_FILENAME);
        $det_file_name1 = $editcatSlug1 . '-det.php'; 
        $det_file_path1 = dirname(__DIR__) . '/' . $det_file_name1;

        $manageLink1 = $det_file_name1;
        $pageTitle1 = ucwords($cat_name);

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
                        \$role = \$row['role'];

                        if (\$role == '1' || \$role == '2' || \$role == '7') {
                        \$stmt = \$conn->prepare("
                            SELECT 
                                websites.id,
                                users.user_id,
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
                                websites ON users.user_id = websites.user_id
                            WHERE 
                                websites.cat_id = ?
                        ");
                        \$stmt->bind_param("i", \$cat_id);
                        } else {
                            \$stmt = \$conn->prepare("
                                SELECT 
                                    websites.id,
                                    users.user_id,
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
                                    websites ON users.user_id = websites.user_id 
                                WHERE 
                                    websites.user_id = ? AND websites.cat_id = ?
                            ");
                            \$stmt->bind_param("si", \$UserId, \$cat_id);
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

                        .header-row {
                        margin-bottom: 20px;
                        }

                        .header-row h5 {
                        font-size: 24px !important;
                        margin: 0;
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

                        .pagination a:hover {
                        background-color: #222;
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
                        <div class="header-row">
                            <h5>$pageTitle1</h5>
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
    
        $_SESSION['swal_success'] = "Category updated";

        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
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

            $baseDir = dirname(__DIR__);
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

        $_SESSION['swal_success'] = "Category deleted";

        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }

    // Handle packages (or) products creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_category'], $_POST['product_type'])) {
        $product_category = intval($_POST['product_category']);
        $product_type = $_POST['product_type'];

        if ($product_type === 'Package') {
            $stmt = $conn->prepare("SELECT cat_url FROM categories WHERE cat_id = ?");
            $stmt->bind_param("i", $product_category);
            $stmt->execute();
            $stmt->bind_result($cat_url);
            $stmt->fetch();
            $stmt->close();

            $cat_url_Slug = pathinfo($cat_url, PATHINFO_FILENAME);
            $pack_cat_url_Slug = strtolower(preg_replace('/\s+/', '-', $cat_url_Slug));

            $add_file_name = "add-$pack_cat_url_Slug.php";
            $add_file_path = dirname(__DIR__) . '/' . $add_file_name;
            $add_content = <<<PHP
                <?php \$script = '<script>
                    (() => {
                        "use strict"

                        // Fetch all the forms we want to apply custom Bootstrap validation styles to
                        const forms = document.querySelectorAll(".needs-validation")

                        // Loop over them and prevent submission
                        Array.from(forms).forEach(form => {
                            form.addEventListener("submit", event => {
                                if (!form.checkValidity()) {
                                    event.preventDefault()
                                    event.stopPropagation()
                                }

                                form.classList.add("was-validated")
                            }, false)
                        })
                    })()
                    </script>';?>

                    <style>
                        .toggle-icon-pass {
                            position: absolute;
                            top: 22px;
                            right: 28px;
                            transform: translateY(-50%);
                            cursor: pointer;
                            user-select: none;
                            font-size: 20px;
                        }
                        input::-webkit-outer-spin-button,
                        input::-webkit-inner-spin-button {
                        -webkit-appearance: none;
                        margin: 0;
                        }

                        /* Firefox */
                        input[type=number] {
                        -moz-appearance: textfield;
                        }
                    </style>

                    <?php include './partials/layouts/layoutTop.php' ?>
                    <?php

                        ini_set('display_errors', 1);
                        ini_set('display_startup_errors', 1);
                        error_reporting(E_ALL);

                        if (\$_SERVER['REQUEST_METHOD'] == 'POST') {
                            // Existing package fields
                            \$package_name = \$_POST['package_name'];                           
                            \$title = \$_POST['title'];
                            \$subtitle = \$_POST['subtitle'];
                            \$price = \$_POST['price'];
                            \$description = \$_POST['description'];
                            \$features = \$_POST['features']; // Array of features
                            \$created_at = date("Y-m-d H:i:s");

                            \$duration_value = isset(\$_POST['duration_value']) ? intval(\$_POST['duration_value']) : 0;
                            \$duration_unit = isset(\$_POST['duration_unit']) ? \$_POST['duration_unit'] : '';
 
                            if (\$duration_value > 0 && in_array(\$duration_unit, ['days', 'months', 'years'])) {
                            \$duration = \$duration_value . ' ' . \$duration_unit; // e.g., "10 days"
                            } else {
                                echo "<script>alert('Invalid duration input.'); window.history.back();</script>";
                                exit;
                            }

                            \$cat_id = $product_category;

                            \$stmt = \$conn->prepare("INSERT INTO package (package_name, title, subtitle, price, description, duration, cat_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            \$stmt->bind_param("ssssssis", \$package_name, \$title, \$subtitle, \$price, \$description, \$duration, \$cat_id, \$created_at);
                        
                            if (\$stmt->execute()) {
                                \$package_id = \$conn->insert_id;
                                \$stmt->close();
                        
                                // Insert features
                                if (!empty(\$features) && is_array(\$features)) {
                                    \$featureStmt = \$conn->prepare("INSERT INTO features (package_id, feature) VALUES (?, ?)");
                                    foreach (\$features as \$feature) {
                                        \$cleaned_feature = trim(\$feature);
                                        if (\$cleaned_feature !== "") {
                                            \$featureStmt->bind_param("is", \$package_id, \$cleaned_feature);
                                            \$featureStmt->execute();
                                        }
                                    }
                                    \$featureStmt->close();
                                }
                        
                                echo "
                                <script>
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Package and features saved successfully.',
                                        confirmButtonText: 'OK'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = 'view-$pack_cat_url_Slug.php';
                                        }
                                    });
                                </script>";
                            } else {
                                echo "<script>
                                    alert('Error: " . \$stmt->error . "');
                                    window.history.back();
                                </script>";
                            }
                        }
                        
                    ?>

                    <div class="dashboard-main-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                            <h6 class="fw-semibold mb-0">Add Package</h6>
                        </div>

                        <div class="card h-100 p-0 radius-12">
                            <div class="card-body p-24">
                                <div class="row justify-content-center">
                                    <div class="col-xxl-12 col-xl-8 col-lg-10">
                                        <form method="POST" class="row gy-3 needs-validation" novalidate autocomplete="off">
                                            <div class="mb-2">
                                                <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Package name <span class="text-danger-600">*</span></label>
                                                <div class="has-validation">
                                                    <input type="text" class="form-control radius-8" name="package_name" required maxlength="100">
                                                    <div class="invalid-feedback">
                                                        Package name is required
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Title <span class="text-danger-600">*</span></label>
                                                <div class="has-validation">
                                                    
                                                    <input type="text" class="form-control radius-8" name="title" required maxlength="100">
                                                    <div class="invalid-feedback">
                                                        Title is required
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Subtitle <span class="text-danger-600">*</span></label>
                                                <div class="has-validation">
                                                    
                                                    <input type="text" class="form-control radius-8" name="subtitle" required maxlength="100">
                                                    <div class="invalid-feedback">
                                                        Subtitle is required
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Price <span class="text-danger-600">*</span></label>
                                                <div class="has-validation">
                                                    
                                                    <input type="number" class="form-control radius-8" name="price" required maxlength="20" onkeydown="return event.key !== 'e'">
                                                    <div class="invalid-feedback">
                                                        Price is required
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Description <span class="text-danger-600">*</span></label>
                                                <div class="has-validation">
                                                    
                                                    <textarea class="form-control radius-8" name="description" required></textarea>
                                                    <div class="invalid-feedback">
                                                        Description is required
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Duration <span class="text-danger-600">*</span></label>
                                                <div class="d-flex gap-2">
                                                    <input type="number" name="duration_value" class="form-control radius-8" required min="1" style="width: 60%;">
                                                    <select name="duration_unit" class="form-control radius-8" required style="width: 40%;">
                                                        <option value="days">Days</option>
                                                        <option value="months">Months</option>
                                                        <option value="years">Years</option>
                                                        <option value="hours">Hours</option>
                                                    </select>
                                                </div>
                                                <div class="invalid-feedback">
                                                    Duration is required
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">Features <span class="text-danger-600">*</span></label>
                                                <div id="feature-wrapper">
                                                    <div class="feature-group mb-2 d-flex gap-2">
                                                        <input type="text" name="features[]" class="form-control radius-8" required placeholder="Enter a feature" />
                                                        <button type="button" class="btn btn-sm btn-success add-feature">+</button>
                                                    </div>
                                                </div>
                                                <div class="invalid-feedback">
                                                    At least one feature is required.
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center justify-content-center gap-3">
                                                <button type="button" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-56 py-11 radius-8">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="btn lufera-bg text-white text-md px-56 py-12 radius-8">
                                                    Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                        
                    <script>
                        document.addEventListener("DOMContentLoaded", function () {
                            const featureWrapper = document.getElementById("feature-wrapper");

                            featureWrapper.addEventListener("click", function (e) {
                                if (e.target && e.target.classList.contains("add-feature")) {
                                    e.preventDefault();

                                    const newGroup = document.createElement("div");
                                    newGroup.className = "feature-group mb-2 d-flex gap-2";
                                    newGroup.innerHTML = `
                                        <input type="text" name="features[]" class="form-control radius-8" required placeholder="Enter a feature" />
                                        <button type="button" class="btn btn-sm btn-danger remove-feature">−</button>
                                    `;
                                    featureWrapper.appendChild(newGroup);
                                }

                                if (e.target && e.target.classList.contains("remove-feature")) {
                                    e.preventDefault();
                                    e.target.parentElement.remove();
                                }
                            });
                        });
                    </script>

                <?php include './partials/layouts/layoutBottom.php' ?>
            PHP;
            file_put_contents($add_file_path, $add_content);

            header("Location: add-$pack_cat_url_Slug.php");

            $view_file_name = "view-$pack_cat_url_Slug.php";
            $view_file_path = dirname(__DIR__) . '/' . $view_file_name;
            $view_content = <<<PHP
                <?php \$_GET['product_category'] = $product_category; ?>
                <?php include './view-package.php'; ?>
            PHP;
            file_put_contents($view_file_path, $view_content);

            exit;
        } elseif ($product_type === 'Product') {
            $stmt = $conn->prepare("SELECT cat_id, cat_url FROM categories WHERE cat_id = ?");
            $stmt->bind_param("i", $product_category);
            $stmt->execute();
            $stmt->bind_result($cat_id1, $cat_url1);
            $stmt->fetch();
            $stmt->close();

            $cat_url1 = pathinfo($cat_url1, PATHINFO_FILENAME);
            $catSlug1 = strtolower(preg_replace('/\s+/', '-', $cat_url1));

            $add_file_name1 = "add-$catSlug1.php";
            $add_file_path1 = dirname(__DIR__) . '/' . $add_file_name1;
            $add_content1 = <<<PHP
                <?php include './add-product.php' ?>
            PHP;
            file_put_contents($add_file_path1, $add_content1);
            
            header("Location: add-$catSlug1.php?id=$cat_id1&slug=$catSlug1");

            $view_file_name1 = "view-$catSlug1.php";
            $view_file_path1 = dirname(__DIR__) . '/' . $view_file_name1;
            $view_content1 = <<<PHP
                <?php \$_GET['product_category'] = $product_category; ?>
                <?php include './view-product.php'; ?>
            PHP;
            file_put_contents($view_file_path1, $view_content1);

            exit;
        } else {
            $_SESSION['swal_error'] = "Invalid product type selected.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .sidebar-menu-wrapper {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        max-height: calc(100vh - 100px); /* Adjust based on your header height */
        overflow-y: auto;
    }
    .top-menu,
    .bottom-menu {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .sidebar-menu-area {
        height: 100%;
        overflow: hidden;
    }
    .top-menu {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        height: 100%;
    }
    .top-menu .add-category-menu {
        margin-top: auto;
        padding-top: 10px;
        border-bottom: 1px solid #eee; /* Optional separator */
    }
    
    .add-category-menu{
        position: sticky;
        bottom: 0;
        background: #fff;
    }
</style>

<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="admin-dashboard.php" class="sidebar-logo">
            <img src="assets/images/logo_lufera.png" alt="site logo" class="light-logo">
            <img src="assets/images/Logo_dark.png" alt="site logo" class="dark-logo">
            <img src="assets/images/Image.jfif" alt="site logo" class="logo-icon">
        </a>
    </div>
    <div class="sidebar-menu-area">
        <div class="sidebar-menu-wrapper">
            <ul class="sidebar-menu top-menu overflow-y-auto" id="sidebar-menu">
                <!-- Dashboard + Dynamic Categories + Add New Category go here -->
                <li>
            <?php if ($row['role'] == "1") { ?>
                <a href="admin-dashboard.php">
                <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            <?php } else { ?>
            <a href="user-dashboard.php">
                <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            <?php } ?>
            </li>
            <?php
                $query = "
                    SELECT 
                        categories.cat_id, 
                        categories.cat_name, 
                        categories.cat_url 
                    FROM users 
                    INNER JOIN roles ON users.role = roles.id 
                    INNER JOIN permission ON roles.id = permission.role_id 
                    INNER JOIN categories ON permission.category_id = categories.cat_id 
                    WHERE users.id = ?
                    ORDER BY categories.cat_id DESC";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $userid);
                $stmt->execute();
                $cat_results = $stmt->get_result();

                while ($cat = $cat_results->fetch_assoc()) { ?>
                <li>
                    <a href="<?= htmlspecialchars($cat['cat_url']) ?>?cat_id=<?= urlencode($cat['cat_id']) ?>">
                        <iconify-icon icon="mdi:tag-outline" class="menu-icon"></iconify-icon>
                        <span><?= htmlspecialchars($cat['cat_name']) ?></span>
                    </a>
                </li>
            <?php } ?>

            <?php if ($row['role'] == "1") {
                // Fetch categories
                $cat_result = $conn->query("SELECT cat_id, cat_name, cat_url, cat_module FROM categories ORDER BY cat_id DESC");

                while ($cat = $cat_result->fetch_assoc()) { ?>
                    <li>
                        <div class="category-item-wrapper">
                            <a href="<?= htmlspecialchars($cat['cat_url']) ?>?cat_id=<?= urlencode($cat['cat_id']) ?>" class="category-link">
                                <iconify-icon icon="mdi:tag-outline" class="menu-icon"></iconify-icon>
                                <span><?= htmlspecialchars($cat['cat_name']) ?></span>
                            </a>
                            <div class="category-actions">
                                <button type="button" onclick="openEditModal('<?= $cat['cat_id'] ?>', '<?= htmlspecialchars($cat['cat_name']) ?>', '<?= htmlspecialchars($cat['cat_url']) ?>', '<?= htmlspecialchars($cat['cat_module']) ?>')" class="icon-btn">    
                                    <iconify-icon icon="mdi:pencil-outline"></iconify-icon>
                                </button>
                                <form method="post" class="delete-form">
                                    <input type="hidden" name="delete_cat_id" value="<?= $cat['cat_id'] ?>">
                                    <button type="button" class="icon-btn delete-btn" onclick="confirmDelete(this)">
                                        <iconify-icon icon="mdi:delete-outline"></iconify-icon>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                <?php } ?>

                    <li class="add-category-menu">
                        <a href="javascript:void(0);" onclick="openCategoryModal();">
                            <span>+ Add New Category</span>
                        </a>

                        <a href="javascript:void(0);" onclick="openProductModal()">
                            <span>+ Add New Product</span>
                        </a>
                    </li>
            <?php } ?>
            </ul>
            <ul class="sidebar-menu bottom-menu" id="sidebar-menu">
            <li>
                <a href="orders.php">
                    <iconify-icon icon="hugeicons:invoice-03" class="menu-icon"></iconify-icon>
                    <span>Orders</span>
                </a>
            </li>
            <?php if ($row['role'] == "1" || $row['role'] == "2") { ?>
            <li>
                <a href="users.php">
                    <iconify-icon icon="hugeicons:user-03" class="menu-icon"></iconify-icon>
                    <span>Users</span>
                </a>
            </li>
            <li>
                <a href="assign-role.php">
                <iconify-icon icon="mdi:account-cog-outline" class="menu-icon"></iconify-icon>

                    <span>Role & Access</span>
                </a>
            </li>
            <?php } if ($row['role'] == "1") { ?>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
                        <span>Settings</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="company.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Company</a>
                        </li>
                        <li>
                            <a href="role-access.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Role</a>
                        </li>
                        <li>
                            <a href="credentials.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Credentials</a>
                        </li>
                        <!-- <li>
                            <a href="notification-alert.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Notification Alert</a>
                        </li>
                        <li>
                            <a href="theme.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Theme</a>
                        </li> -->
                        <li>
                            <a href="currencies.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Currencies</a>
                        </li>
                        <li>
                            <a href="bank_details.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Bank Details</a>
                        </li>
                        <li>
                            <a href="view_products.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Products List</a>
                        </li>
                        <li>
                            <a href="view_packages.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Packages List</a>
                        </li>
                        <li>
                            <a href="payment-gateway.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Payment Gateway</a>
                        </li>
                    </ul>
                </li>
            <?php } ?>
            <li>
                <a href="logout.php" class="hover-bg-transparent hover-text-danger">
                    <iconify-icon icon="bi:x-circle" class="menu-icon"></iconify-icon>
                    <span>Log-Out</span>
                </a>
            </li>
            </ul>
        </div>
    </div>
</aside>

<!-- SweetAlert for success -->
<?php if (isset($_SESSION['swal_success'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "success",
                title: "Success",
                text: "<?= $_SESSION['swal_success'] ?>",
                confirmButtonColor: "#3085d6",
            });
        });
    </script>
    <?php unset($_SESSION['swal_success']); ?>
<?php endif; ?>

<!-- Add Category Modal -->
<div id="categoryModal" class="custom-modal">
    <div class="custom-modal-overlay" onclick="closeCategoryModal();"></div>
    <div class="custom-modal-box">
        <div class="custom-modal-header">
            <h2>New Category</h2>
            <button class="custom-modal-close" onclick="closeCategoryModal()">×</button>
        </div>
        <form method="post" action="" class="custom-modal-body">
            <div class="custom-form-group">
                <label for="cat_name">Name</label>
                <input type="text" name="cat_name" id="cat_name" required placeholder="Name">
            </div>
            <div class="custom-form-group">
                <label for="cat_url">URL</label>
                <input type="text" name="cat_url" id="cat_url" required placeholder="URL">
            </div>
            <div class="custom-form-group">
                <label for="cat_template">Module</label>
                <select name="cat_template" id="cat_template" required>
                    <option value="">-- Select Module --</option>
                    <option value="website">Website</option>
                    <option value="marketing">Marketing</option>
                    <option value="visa">Visa</option>
                    <option value="website-onboarding">Website Onboarding</option>
                    <option value="marketing-onboarding">Marketing Onboarding</option>
                    <option value="domain-onboarding">Domain Onboarding</option>
                    <option value="email-onboarding">Email Onboarding</option>
                    <option value="mobile-app-onboarding">Mobile App Onboarding</option>
                </select>
            </div>
            <div class="custom-modal-footer">
                <button type="submit" class="custom-btn save-btn">Save</button>
                <button type="button" class="custom-btn cancel-btn" onclick="closeCategoryModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="custom-modal">
    <div class="custom-modal-overlay" onclick="closeEditModal();"></div>
    <div class="custom-modal-box">
        <div class="custom-modal-header">
            <h2>Edit Category</h2>
            <button class="custom-modal-close" onclick="closeEditModal()">×</button>
        </div>
        <form method="post" class="custom-modal-body">
            <input type="hidden" name="edit_cat_id" id="edit_cat_id">
            <div class="custom-form-group">
                <label for="edit_cat_name">Name</label>
                <input type="text" name="edit_cat_name" id="edit_cat_name" required>
            </div>
            <div class="custom-form-group">
                <label for="edit_cat_url">URL</label>
                <input type="text" name="edit_cat_url" id="edit_cat_url" required>
            </div>
            <div class="custom-form-group">
                <label for="edit_cat_module">Module</label>
                <select name="edit_cat_module" id="edit_cat_module" required>
                    <option value="website">Website</option>
                    <option value="marketing">Marketing</option>
                    <option value="visa">Visa</option>
                    <option value="website-onboarding">Website Onboarding</option>
                    <option value="marketing-onboarding">Marketing Onboarding</option>
                    <option value="domain-onboarding">Domain Onboarding</option>
                    <option value="email-onboarding">Email Onboarding</option>
                    <option value="mobile-app-onboarding">Mobile App Onboarding</option>
                </select>
            </div>
            <div class="custom-modal-footer">
                <button type="submit" class="custom-btn save-btn">Update</button>
                <button type="button" class="custom-btn cancel-btn" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Structure -->
<div id="productModal" class="custom-modal">
  <div class="custom-modal-content">
    <div class="custom-modal-header">
      <span class="custom-modal-title">+ Add New Product</span>
      <span class="custom-modal-close" onclick="closeProductModal()">&times;</span>
    </div>

    <div class="custom-modal-body">
      <form method="post" action="">
        <!-- Category Dropdown -->
        <div class="form-group">
          <label for="product_category">Select Category</label>
          <select id="product_category" name="product_category" required>
            <option value="">-- Choose Category --</option>
            <?php
              $categories = $conn->query("SELECT cat_id, cat_name FROM categories ORDER BY cat_name ASC");
              while ($cat = $categories->fetch_assoc()) {
                echo "<option value='" . $cat['cat_id'] . "'>" . htmlspecialchars($cat['cat_name']) . "</option>";
              }
            ?>
          </select>
        </div>

        <!-- Type Radio Toggle -->
        <div class="form-group">
          <label>Type</label>
          <div class="radio-group">
            <input type="radio" id="type_package" name="product_type" value="Package" required>
            <label for="type_package">Package</label>

            <input type="radio" id="type_product" name="product_type" value="Product">
            <label for="type_product">Product</label>
          </div>
        </div>

        <!-- Buttons -->
        <div class="modal-actions">
          <button type="submit" class="btn lufera-bg">Save</button>
          <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Scoped Modal CSS -->
<style>
    /* Modal Root */
    .custom-modal {
        position: fixed;
        top: 0; left: 0;
        width: 100vw;
        height: 100vh;
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1050;
    }
    .custom-modal.show {
        display: flex;
    }
    .custom-modal-overlay {
        position: absolute;
        top: 0; left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
    }

    /* Modal Content Box */
    .custom-modal-box {
        position: relative;
        background: #fff;
        border-radius: 12px;
        max-width: 420px;
        width: 90%;
        padding: 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        animation: slideDown 0.3s ease;
        z-index: 1051;
    }

    /* Header */
    .custom-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .custom-modal-header h2 {
        margin: 0;
        font-size: 20px !important;
    }
    .custom-modal-close {
        font-size: 24px;
        background: none;
        border: none;
        cursor: pointer;
        line-height: 1;
    }

    /* Body Form */
    .custom-modal-body {
        margin-top: 20px;
    }
    .custom-form-group {
        margin-bottom: 16px;
    }
    .custom-form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
    }
    .custom-form-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
    }

    /* Footer Buttons */
    .custom-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .custom-btn {
        padding: 8px 16px;
        font-size: 14px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        border: none;
    }
    .save-btn {
        background-color: #ffc107;
        color: #212529;
    }
    .cancel-btn {
        background-color: #e0e0e0;
        color: #333;
    }

    /* Animations */
    @keyframes slideDown {
        from { transform: translateY(-10px); opacity: 0; }
        to   { transform: translateY(0); opacity: 1; }
    }
</style>

<!-- Style for categories edit and delete -->
<style>
    /* Category wrapper */
    .category-item-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* .category-link {
        flex-grow: 1;
        display: flex;
        align-items: center;
        text-decoration: none;
        color: inherit;
        gap: 8px;
        min-width: 0;
    }

    .category-link span {
        font-size: 14px;
        font-weight: 500;
        word-break: break-word;
    } */

    .category-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    /* Icon buttons for edit/delete */
    .icon-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        color: #555;
        font-size: 18px;
        transition: color 0.2s ease;
        display: flex;
        align-items: center;
    }

    .icon-btn:hover {
        color: #fec700;
    }

    .icon-btn.delete-btn:hover {
        color: red;
    }

    /* 📱 Responsive behavior */
    @media (max-width: 576px) {
        .category-item-wrapper {
            align-items: flex-start;
        }

        .category-actions {
            margin-top: 4px;
        }

        .category-link {
            font-size: 16px;
        }

        .icon-btn {
            font-size: 20px;
        }
    }
</style>

<!-- CSS Scoped to Modal Only -->
<style>
    .custom-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0; top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .custom-modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        animation: fadeIn 0.3s ease;
    }

    .custom-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .custom-modal-title {
        font-size: 18px;
        font-weight: 600;
    }

    .custom-modal-close {
        font-size: 24px;
        cursor: pointer;
        color: #888;
    }

    .custom-modal-body .form-group {
        margin-bottom: 16px;
    }

    .custom-modal-body label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 14px;
    }

    .custom-modal-body select {
        width: 100%;
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
    }

    .radio-group {
        display: flex;
        gap: 12px;
        margin-top: 6px;
    }

    .radio-group input[type="radio"] {
        display: none;
    }

    .radio-group label {
        padding: 8px 14px;
        border: 1px solid #ccc;
        border-radius: 6px;
        background-color: #f4f4f4;
        cursor: pointer;
        font-size: 14px;
        user-select: none;
    }

    .radio-group input[type="radio"]:checked + label {
        background-color: #fec700;
        color: #fff;
        border-color: #fec700;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        border: none;
        cursor: pointer;
    }

    .btn-save {
        background-color: #fec700;
        color: black;
    }

    .btn-cancel {
        background-color: #ccc;
        color: #333;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>

<script>
    function openCategoryModal() {
        document.getElementById("categoryModal").classList.add("show");
    }
    function closeCategoryModal() {
        document.getElementById("categoryModal").classList.remove("show");
    }
</script>

<script>
    function openEditModal(id, name, url, module) {
        document.getElementById("edit_cat_id").value = id;
        document.getElementById("edit_cat_name").value = name;
        document.getElementById("edit_cat_url").value = url.replace(".php", "");
        document.getElementById("edit_cat_module").value = module;
        document.getElementById("editCategoryModal").classList.add("show");
    }

    function closeEditModal() {
        document.getElementById("editCategoryModal").classList.remove("show");
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
</script>

<!-- JS to Toggle Modal -->
<script>
    function openProductModal() {
    document.getElementById("productModal").style.display = "block";
    }
    function closeProductModal() {
    document.getElementById("productModal").style.display = "none";
    }
</script>