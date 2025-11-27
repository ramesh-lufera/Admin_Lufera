<?php include './partials/layouts/layoutTop.php'; ?>
    <?php
        $Id = $_SESSION['user_id'];
        
        if (isset($_GET['cat_id']) && intval($_GET['cat_id']) > 0) {
            $_SESSION['cat_id'] = intval($_GET['cat_id']);
        } elseif (!isset($_GET['cat_id'])) {
            // Clear the old cat_id from session when not present in URL
            unset($_SESSION['cat_id']);
        }

        $cat_id = $_SESSION['cat_id'] ?? 0;

        $user = "select * from users where id = $Id";
        $res = $conn ->query($user);
        $row = $res ->fetch_assoc();
        $UserId = $row['id'];
        $role = $row['role'];

        $category = "select * from categories where cat_id = $cat_id";
        $cat_query = $conn ->query($category);
        $cat_row = $cat_query ->fetch_assoc();
        
        if ($role == '1' || $role == '2' || $role == '7') {
            $sql = "
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
                    websites.expired_at,
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
                    websites.cat_id = ? AND websites.is_active = 1
            ";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $cat_id);
        } else {
            $sql = "
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
                    websites.expired_at,
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
                    websites.user_id = ? AND websites.cat_id = ? AND websites.is_active = 1
            ";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $UserId, $cat_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $websites = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $websites[] = $row;
        }

        // Number of websites per page
        $websitesPerPage = 5;

        // Get the current page from URL, default is 1
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        // Calculate the starting index for the websites to display on this page
        $startIndex = ($page - 1) * $websitesPerPage;

        // Slice the websites array to get only the websites for the current page
        $websitesOnPage = array_slice($websites, $startIndex, $websitesPerPage);

        // Calculate the total number of pages
        $totalPages = ceil(count($websites) / $websitesPerPage);
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
            <h6 class="fw-semibold mb-0">Test</h6>
            <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>                                 
        </div>
        
        <!-- Search + Add -->
        <div class="search-card bg-base">
            <div class="search-container">
            <span class="search-icon">&#128269;</span>
            <input type="text" id="searchInput" placeholder="Search Test..." />
            </div>
            <a href="view-test.php" class="add-btn">+ Add New Test</a>
        </div>

        <!-- Website List -->
        <!-- <div class="list-section" id="websiteList"> -->
            <!-- <h5>Business WordPress Hosting</h5> -->

            <div class="list-wrapper" id="websiteList">
            <?php if (empty($websitesOnPage)): ?>
                <div class="list-item bg-base no-website">
                No Test found.
                </div>
            <?php else: ?>
                <?php foreach ($websitesOnPage as $site): ?>
                <?php
                     $status = strtolower($site['status']);
                    $CreatedAt = $site['created_at'];
                    $Duration = $site['duration'];
                    $expiredAt = $site['expired_at'];

                    
                        $statusClass = 'status-pending';
                        if ($status === 'active') $statusClass = 'status-active';
                        elseif ($status === 'expired') $statusClass = 'status-expired';

                    
                        $domainColorClass = 'domain-text-pending'; // default
                        if ($status === 'approved') {
                            $domainColorClass = 'domain-text-approved';
                        }

                        

            // $startDate = new DateTime($CreatedAt);
            // $endDate = (clone $startDate)->modify("+{$Duration}");
            // $Validity = $startDate->format("d-m-Y") . " to " . $endDate->format("d-m-Y"); 

            $startDate = new DateTime($CreatedAt);
            $endDate = (clone $startDate)->modify("+{$Duration}");
            $calculatedEnd = $endDate->format("d-m-Y");

            // If renewed, use expired_at from database
            if (!empty($expiredAt) && $expiredAt !== '0000-00-00 00:00:00') {
                $Validity = (new DateTime($expiredAt))->format("d-m-Y");
            } else {
                $Validity = $calculatedEnd;
            }

            if ($status === 'approved') {
                $expiresText = htmlspecialchars($Validity);
                $color = '#89836f'; // yellow
            } else {
                $expiresText = 'N/A';
                $color = 'orange'; // fallback
            }
                ?>
                <div class="list-item bg-base <?php echo $statusClass; ?>">
                    <div class="site-info">
                    <!-- Domain Title -->
                    <div class="site-info-header">
                        <h6>
                        <!-- <?php echo htmlspecialchars($site['plan']); ?> -->
                        <?php echo htmlspecialchars($site['plan_name']); ?>
                        <span style="visibility:hidden"><?php echo htmlspecialchars($site['user_id']); ?></span>
                        </h6>
                    </div>
                    
                    <div class="site-info-meta">
                        Expires:
                        <span style="color: <?php echo $color; ?>;">
                            <?php echo $Validity; ?>
                        </span>
                    </div>
                    </div>
                    <div class="manage-btn-wrapper">
                        <?php if ($role == '1' || $role == '2' || $role == '7') { ?>
                            <p class="mb-0"><?php echo htmlspecialchars($site['business_name']); ?></p>
                        <?php } ?>
                        <a href="test-det.php?website_id=<?php echo (int)$site['web_id']; ?>&product_id=<?php echo (int)$site['product_id']; ?>&type=<?php echo $site['type']; ?>" class="dashboard-btn">Manage</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?cat_id=?<?php echo $cat_id; ?>&page=<?php echo $page - 1; ?>">Previous</a> 
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?cat_id=?<?php echo $cat_id; ?>&page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?cat_id=?<?php echo $cat_id; ?>&page=<?php echo $page + 1; ?>">Next</a>
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