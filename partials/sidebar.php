<?php
    $userid = $_SESSION['user_id'];

    $sql = "SELECT role FROM users WHERE id = $userid";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $url = trim($_POST['url']);
        if (!empty($name) && !empty($url)) {
            $stmt = $conn->prepare("INSERT INTO categories (cat_name, cat_url) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $url);
            $stmt->execute();
            header("Location: " . $_SERVER['PHP_SELF'] . "");
            exit;
        }
    }
?>

<!-- ✅ Alert -->
<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div id="alertBox" class="alert-success">
        ✅ Category added successfully!
        <button onclick="document.getElementById('alertBox').style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<!-- ✅ Sidebar -->
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
        <ul class="sidebar-menu" id="sidebar-menu">
            <!-- 

            <li><a href="websites.php"><iconify-icon icon="hugeicons:ai-web-browsing" class="menu-icon"></iconify-icon><span>Website</span></a></li>
            <li><a href="#"><iconify-icon icon="ion:megaphone-outline" class="menu-icon"></iconify-icon><span>Marketing</span></a></li>
            <li><a href="#"><iconify-icon icon="hugeicons:source-code-circle" class="menu-icon"></iconify-icon><span>Domain</span></a></li>
            <li><a href="#"><iconify-icon icon="mage:database" class="menu-icon"></iconify-icon><span>Hosting</span></a></li>
            <li><a href="#"><iconify-icon icon="mage:email" class="menu-icon"></iconify-icon><span>Email</span></a></li>
            <li><a href="orders.php"><iconify-icon icon="hugeicons:invoice-03" class="menu-icon"></iconify-icon><span>Orders</span></a></li> 

            <?php if ($row['role'] == "1" || $row['role'] == "2") { ?>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                        <span>Users</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="add-user.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Add User</a></li>
                        <li><a href="users-list.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Users List</a></li>
                    </ul>
                </li>
                <li><a href="assign-role.php"><iconify-icon icon="hugeicons:ai-web-browsing" class="menu-icon"></iconify-icon><span>Role & Access</span></a></li>
            <?php } ?>
            -->
            <li>
                <a href="<?php echo ($row['role'] == "1") ? 'admin-dashboard.php' : 'user-dashboard.php'; ?>">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php if ($row['role'] == "1") { ?>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
                        <span>Settings</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="company.php"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Company</a></li>
                        <li><a href="role-access.php"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Role</a></li>
                        <li><a href="credentials.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Credentials</a></li>
                        <li><a href="notification-alert.php"><i class="ri-circle-fill circle-icon text-info-600 w-auto"></i> Notification Alert</a></li>
                        <li><a href="theme.php"><i class="ri-circle-fill circle-icon text-danger-600 w-auto"></i> Theme</a></li>
                        <li><a href="currencies.php"><i class="ri-circle-fill circle-icon text-danger-600 w-auto"></i> Currencies</a></li>
                        <li><a href="language.php"><i class="ri-circle-fill circle-icon text-danger-600 w-auto"></i> Languages</a></li>
                        <li><a href="payment-gateway.php"><i class="ri-circle-fill circle-icon text-danger-600 w-auto"></i> Payment Gateway</a></li>
                    </ul>
                </li>
            <?php } ?>
            <!-- <?php if ($row['role'] == "1" || $row['role'] == "2") { ?>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                        <span>Users</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="add-user.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Add User</a></li>
                        <li><a href="users-list.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Users List</a></li>
                    </ul>
                </li>
                <li><a href="assign-role.php"><iconify-icon icon="hugeicons:ai-web-browsing" class="menu-icon"></iconify-icon><span>Role & Access</span></a></li>
            <?php } ?> -->
            



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
$result = $stmt->get_result();

while ($cat = $result->fetch_assoc()) {
?>
    <li>
        <a href="<?php echo htmlspecialchars($cat['cat_url']); ?>">
            <iconify-icon icon="mdi:folder-outline" class="menu-icon"></iconify-icon>
            <span><?php echo htmlspecialchars($cat['cat_name']); ?></span>
        </a>
    </li>
<?php } ?>

            <!-- <?php
                $cat_query = $conn->query("SELECT * FROM categories ORDER BY cat_id DESC");
                while ($cat = $cat_query->fetch_assoc()) {
            ?>
                <li>
                    <a href="<?php echo htmlspecialchars($cat['cat_url']); ?>">
                        <iconify-icon icon="mdi:folder-outline" class="menu-icon"></iconify-icon>
                        <span><?php echo htmlspecialchars($cat['cat_name']); ?></span>
                    </a>
                </li>
            <?php } ?> -->

            <?php if ($row['role'] == "1") { ?>
                <li>
                    <a data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <!-- <iconify-icon icon="material-symbols:add-circle-outline" class="menu-icon"></iconify-icon> -->
                        <span>+ Add New Category</span>
                    </a>
                </li>
            <?php } ?>
            <li><a href="logout.php"><iconify-icon icon="bi:x-circle" class="menu-icon"></iconify-icon><span>Log-Out</span></a></li> 
        </ul>
    </div>
</aside>




<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-20">
                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Category</label>
                            <input type="text" class="form-control radius-8" name="name" id="name" placeholder="Enter category name" required>
                        </div>
                        <div>
                            <!-- <label for="url" class="form-label fw-semibold text-primary-light text-sm mb-8">URL</label> -->
                            <input type="hidden" class="form-control radius-8" name="url" id="url" placeholder="e.g., category.php" required>
                        </div>
                </div>
                <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_category" class="btn lufera-bg">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- ✅ JavaScript -->
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const alert = document.getElementById('alertBox');
        if (alert) {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 3000);
        }
    });
</script>
<script>
    document.getElementById('name').addEventListener('input', function() {
        const nameValue = this.value.trim();
        const sanitized = nameValue.replace(/\s+/g, '-').toLowerCase(); // Optional: replace spaces with dashes
        document.getElementById('url').value = sanitized ? sanitized + '.php' : '';
    });
</script>
