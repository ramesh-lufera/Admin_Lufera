<?php
    $userid = $_SESSION['user_id'];
    $sql = "select role from users where id = $userid";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();
?>
<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="admin-dashboard.php" class="sidebar-logo">
            <!-- <img src="assets/images/logo.png" alt="site logo" class="light-logo"> -->
            <img src="assets/images/logo_lufera.png" alt="site logo" class="light-logo">
            <img src="assets/images/Logo_dark.png" alt="site logo" class="dark-logo">
            <img src="assets/images/Image.jfif" alt="site logo" class="logo-icon">
        </a>
    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">
            <!-- <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Home</span>
                </a>
                <ul class="sidebar-submenu">
                    
                    <li>
                        <a href="index-3.php"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Admin</a>
                    </li>
                </ul>
            </li> -->
            <li>
            <?php if($row['role']=="1"){ ?>
                <a href="admin-dashboard.php">
                <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            <?php } else{ ?>
            <a href="user-dashboard.php">
                <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            <?php } ?>
            </li>
            <li>
                <a href="websites.php">
                <iconify-icon icon="hugeicons:ai-web-browsing" class="menu-icon" /></iconify-icon>
                    <span>Website</span>
                </a>
            </li>
            <li>
                <a href="#">
                <iconify-icon icon="ion:megaphone-outline" class="menu-icon" /></iconify-icon>
                    <span>Marketing</span>
                </a>
            </li>
            <!-- <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="hugeicons:source-code-circle" class="menu-icon"></iconify-icon>
                    <span>Domain</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="domain.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Domain portfolio</a>
                    </li>
                    <li>
                        <a href="#"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Get a new domain</a>
                    </li>
                </ul>
            </li> -->
            <li>
                <a href="#">
                <iconify-icon icon="hugeicons:source-code-circle" class="menu-icon" /></iconify-icon>
                    <span>Domain</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <iconify-icon icon="mage:database" class="menu-icon"></iconify-icon>
                    <span>Hosting</span>
                </a>
            </li>
            <li>
                <a href="email.php">
                    <iconify-icon icon="mage:email" class="menu-icon"></iconify-icon>
                    <span>Email</span>
                </a>
            </li>
            <!-- <li>
                <a href="chat-message.php">
                    <iconify-icon icon="bi:chat-dots" class="menu-icon"></iconify-icon>
                    <span>Chat</span>
                </a>
            </li> -->
            <!-- <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="hugeicons:invoice-03" class="menu-icon"></iconify-icon>
                    <span>Invoice</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="invoice-list.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> List</a>
                    </li>
                    <li>
                        <a href="invoice-preview.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Preview</a>
                    </li>
                    <li>
                        <a href="invoice-add.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Add new</a>
                    </li>
                    <li>
                        <a href="invoice-edit.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Edit</a>
                    </li>
                </ul>
            </li> -->
            <li>
                <a href="orders.php">
                    <iconify-icon icon="hugeicons:invoice-03" class="menu-icon"></iconify-icon>
                    <span>Orders</span>
                </a>
            </li>
            <?php if($row['role']=="1" || $row['role'] == "2") { ?>
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                    <span>Users</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="add-user.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Add User</a>
                    </li>
                    <li>
                        <a href="users-list.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Users List</a>
                    </li>
                    <!-- <li>
                        <a href="users-grid.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Users Grid</a>
                    </li>
                    <li>
                        <a href="view-profile.php"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> View Profile</a>
                    </li> -->
                </ul>
            </li>
            <?php } if($row['role']=="1" || $row['role'] == "2") { ?>
            <li>
                <a href="assign-role.php">
                <iconify-icon icon="hugeicons:ai-web-browsing" class="menu-icon" /></iconify-icon>
                    <span>Role & Access</span>
                </a>
            </li>
            <? } php if($row['role']=="1") { ?>
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
                    <span>Settings</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="company.php"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Company</a>
                    </li>
                    <li>
                        <a href="role-access.php"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Role</a>
                    </li>
                    <li>
                        <a href="credentials.php"><i class="ri-circle-fill circle-icon text-warning-600 w-auto"></i> Credentials</a>
                    </li>
                    <li>
                        <a href="notification-alert.php"><i class="ri-circle-fill circle-icon text-info-600 w-auto"></i> Notification Alert</a>
                    </li>
                    <li>
                        <a href="theme.php"><i class="ri-circle-fill circle-icon text-danger-600 w-auto"></i> Theme</a>
                    </li>
                    <li>
                        <a href="currencies.php"><i class="ri-circle-fill circle-icon text-danger-600 w-auto"></i> Currencies</a>
                    </li>
                    <li>
                        <a href="language.php"><i class="ri-circle-fill circle-icon text-danger-600 w-auto"></i> Languages</a>
                    </li>
                    <li>
                        <a href="payment-gateway.php"><i class="ri-circle-fill circle-icon text-danger-600 w-auto"></i> Payment Gateway</a>
                    </li>
                </ul>
            </li>
            <?php } ?>
            <li>
                <a href="logout.php">
                    <iconify-icon icon="bi:x-circle" class="menu-icon"></iconify-icon>
                    <span>Log-Out</span>
                </a>
            </li>
        </ul>
    </div>
</aside>