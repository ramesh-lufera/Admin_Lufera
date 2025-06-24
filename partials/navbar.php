<div class="navbar-header">
            <div class="row align-items-center justify-content-between">
                <div class="col-auto">
                    <div class="d-flex flex-wrap align-items-center gap-4">
                        <button type="button" class="sidebar-toggle">
                            <iconify-icon icon="heroicons:bars-3-solid" class="icon text-2xl non-active"></iconify-icon>
                            <iconify-icon icon="iconoir:arrow-right" class="icon text-2xl active"></iconify-icon>
                        </button>
                        <button type="button" class="sidebar-mobile-toggle">
                            <iconify-icon icon="heroicons:bars-3-solid" class="icon"></iconify-icon>
                        </button>
                        <!-- <form class="navbar-search">
                            <input type="text" name="search" placeholder="Search">
                            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
                        </form> -->
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <button type="button" data-theme-toggle class="w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center"></button>

                        <div class="dropdown">
                            <button id="notificationBell" class="has-indicator w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center" type="button" data-bs-toggle="dropdown">
                                <iconify-icon icon="iconoir:bell" class="text-primary-light text-xl"></iconify-icon>
                                <?php if ($notiCount > 0): ?>
                                    <!-- <span class="indicator bg-danger rounded-circle" style="width:10px; height:10px; position:absolute; top:5px; right:5px;"></span> -->

                                    <span id="notificationBadge" class="position-absolute badge rounded-pill bg-danger text-white" 
                                        style="font-size: 10px; padding: 4px 6px; bottom: -4px; right: -4px; white-space: nowrap;">
                                        <?= $notiCount ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu to-top dropdown-menu-lg p-0">
                                <div class="m-16 py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                                    <div>
                                        <h6 class="text-lg text-primary-light fw-semibold mb-0">Notifications</h6>
                                    </div>
                                    <span class="text-primary-600 fw-semibold text-lg w-40-px h-40-px rounded-circle bg-base d-flex justify-content-center align-items-center"><?= $notiCount ?></span>
                                </div>

                                <div class="max-h-400-px overflow-y-auto scroll-sm pe-4">
                                    <?php if ($notiCount > 0): ?>
                                        <div class="text-center py-12 px-16">
                                            <?php while ($noti = $notifications->fetch_assoc()): ?>

                                                <!-- <div class="py-8 border-bottom d-flex align-items-center gap-2">
                                                    <?php if (!empty($noti['n_photo'])): ?>
                                                        <img src="<?= htmlspecialchars($noti['n_photo']) ?>" alt="user photo" class="rounded-circle" width="30" height="30">
                                                    <?php endif; ?>
                                                    <div class="text-start">
                                                        <p class="text-sm mb-1"><?= htmlspecialchars($noti['message']) ?></p>
                                                        <small class="text-muted"><?= date('d M Y, h:i A', strtotime($noti['created_at'])) ?></small>
                                                    </div>
                                                </div> -->

                                                <div class="notification-item d-flex align-items-start gap-3 py-3 px-3 border-bottom">
                                                    <div class="flex-shrink-0">
                                                        <?php if (!empty($noti['n_photo'])): ?>
                                                            <img src="<?= htmlspecialchars($noti['n_photo']) ?>" alt="user photo" class="user-photo rounded-circle">
                                                        <?php else: ?>
                                                            <img src="assets/images/user1.png" alt="user photo" class="user-photo rounded-circle">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 text-start">
                                                        <p class="mb-1 fw-semibold text-dark notification-message"><?= htmlspecialchars($noti['message']) ?></p>
                                                        <small class="text-muted"><?= date('d M Y, h:i A', strtotime($noti['created_at'])) ?></small>
                                                    </div>
                                                </div>

                                            <?php endwhile; ?>
                                        </div>
                                        <div class="text-center py-12 px-16">
                                            <a href="javascript:void(0)" class="text-primary-600 fw-semibold text-md">See All Notification</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="px-16 py-12 text-center text-muted">No new notifications</div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div><!-- Notification dropdown end -->

                        <div class="dropdown">
                            <button class="d-flex justify-content-center align-items-center rounded-circle" type="button" data-bs-toggle="dropdown">
                                <img src="<?= htmlspecialchars($photo) ?>" alt="image" class="w-40-px h-40-px object-fit-cover rounded-circle">
                            </button>

                            <div class="dropdown-menu to-top dropdown-menu-sm">
                                <div class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                                    <div>
                                        <!-- Bind the username dynamically.. -->
                                        <?php if (isset($_SESSION['username'])): ?>
                                            <h6 class="text-lg text-primary-light fw-semibold mb-2"><?php echo $_SESSION['username']; ?></h6>
                                        <?php else: ?>
                                            <h6 class="text-lg text-primary-light fw-semibold mb-2">none</h6>
                                        <?php endif; ?>

                                            <!-- <span class="text-secondary-light fw-medium text-sm">Admin</span> -->
                                    </div>
                                    <button type="button" class="hover-text-danger">
                                        <iconify-icon icon="radix-icons:cross-1" class="icon text-xl"></iconify-icon>
                                    </button>
                                </div>
                                <ul class="to-top-list">
                                    <li>
                                        <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3" href="view-profile.php">
                                            <iconify-icon icon="solar:user-linear" class="icon text-xl"></iconify-icon> My Account
                                        </a>
                                    </li>
                                    <!-- <li>
                                        <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3" href="email.php">
                                            <iconify-icon icon="tabler:message-check" class="icon text-xl"></iconify-icon> Inbox
                                        </a>
                                    </li> -->
                                    <!-- <li>
                                        <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3" href="company.php">
                                            <iconify-icon icon="icon-park-outline:setting-two" class="icon text-xl"></iconify-icon> Setting
                                        </a>
                                    </li> -->
                                    <li>
                                        <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-danger d-flex align-items-center gap-3" href="logout.php">
                                            <iconify-icon icon="lucide:power" class="icon text-xl"></iconify-icon> Log Out
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div><!-- Profile dropdown end -->
                    </div>
                </div>
            </div>
        </div>