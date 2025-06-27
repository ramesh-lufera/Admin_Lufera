<?php
    $userid = $_SESSION['user_id'];

    // $sql = "select role from users where id = $userid";
    $sql = "SELECT role FROM users WHERE id = $userid";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();

    // Handle category creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_name'], $_POST['cat_url'])) {
        $cat_name = trim($_POST['cat_name']);
        $cat_url = trim($_POST['cat_url']);

        if (!str_ends_with($cat_url, '.php')) {
            $cat_url .= '.php';
        }

        if (!empty($cat_name)) {
            $stmt = $conn->prepare("INSERT INTO categories (cat_name, cat_url) VALUES (?, ?)");
            $stmt->bind_param("ss", $cat_name, $cat_url);
            $stmt->execute();
            $_SESSION['swal_success'] = "Category created";
        }

        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }

    // Delete category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cat_id'])) {
        $cat_id = intval($_POST['delete_cat_id']);
        $stmt = $conn->prepare("DELETE FROM categories WHERE cat_id = ?");
        $stmt->bind_param("i", $cat_id);
        $stmt->execute();
        $_SESSION['swal_success'] = "Category deleted";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }

    // Edit category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_cat_id'], $_POST['edit_cat_name'], $_POST['edit_cat_url'])) {
        $cat_id = intval($_POST['edit_cat_id']);
        $cat_name = trim($_POST['edit_cat_name']);
        $cat_url = trim($_POST['edit_cat_url']);

        if (!str_ends_with($cat_url, '.php')) {
            $cat_url .= '.php';
        }

        $stmt = $conn->prepare("UPDATE categories SET cat_name = ?, cat_url = ? WHERE cat_id = ?");
        $stmt->bind_param("ssi", $cat_name, $cat_url, $cat_id);
        $stmt->execute();

        $_SESSION['swal_success'] = "Category updated";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
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
            <ul class="sidebar-menu top-menu" id="sidebar-menu">
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
                    <a href="<?= htmlspecialchars($cat['cat_url']) ?>">
                        <iconify-icon icon="mdi:tag-outline" class="menu-icon"></iconify-icon>
                        <span><?= htmlspecialchars($cat['cat_name']) ?></span>
                    </a>
                </li>
            <?php } ?>

            <?php if ($row['role'] == "1") {
                // Fetch categories
                $cat_result = $conn->query("SELECT cat_id, cat_name, cat_url FROM categories ORDER BY cat_id DESC");

                while ($cat = $cat_result->fetch_assoc()) { ?>
                    <li>
                        <div class="category-item-wrapper">
                            <a href="<?= htmlspecialchars($cat['cat_url']) ?>" class="category-link">
                                <iconify-icon icon="mdi:tag-outline" class="menu-icon"></iconify-icon>
                                <span><?= htmlspecialchars($cat['cat_name']) ?></span>
                            </a>
                            <div class="category-actions">
                                <button type="button" onclick="openEditModal('<?= $cat['cat_id'] ?>', '<?= htmlspecialchars($cat['cat_name']) ?>', '<?= htmlspecialchars($cat['cat_url']) ?>')" class="icon-btn">
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
                        <li>
                            <a href="notification-alert.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Notification Alert</a>
                        </li>
                        <li>
                            <a href="theme.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Theme</a>
                        </li>
                        <li>
                            <a href="currencies.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Currencies</a>
                        </li>
                        <li>
                            <a href="language.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Languages</a>
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
      <form method="post" action="add-product.php">
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
          <button type="submit" class="btn btn-save" onclick="window.location.href='add-product.php'">Save</button>
          <button type="button" class="btn btn-cancel" onclick="closeProductModal()">Cancel</button>
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
            flex-direction: column;
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
    function openEditModal(id, name, url) {
        document.getElementById("edit_cat_id").value = id;
        document.getElementById("edit_cat_name").value = name;
        document.getElementById("edit_cat_url").value = url.replace(".php", "");
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