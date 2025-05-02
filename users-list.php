<?php include './partials/layouts/layoutTop.php' ?>

<?php
    // Fetch users data from the database
    $sql = "SELECT * FROM users ORDER BY created_at ASC";
    $result = mysqli_query($conn, $sql);

    // Set the number of records per page
    $records_per_page = 6;

    // Get the current page from the URL (if not set, default to 1)
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    // Calculate the starting record for the SQL query
    $start_from = ($current_page - 1) * $records_per_page;

    // Fetch users data for the current page
    $sql = "SELECT * FROM users ORDER BY created_at ASC LIMIT $start_from, $records_per_page";
    $result = mysqli_query($conn, $sql);

    // Get the total number of records
    $total_sql = "SELECT COUNT(*) FROM users";
    $total_result = mysqli_query($conn, $total_sql);
    $total_row = mysqli_fetch_row($total_result);
    $total_records = $total_row[0];

    // Calculate total number of pages
    $total_pages = ceil($total_records / $records_per_page);
?>

    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Users List</h6>
            <ul class="d-flex align-items-center gap-2">
                <li class="fw-medium">
                    <a href="admin-dashboard.php" class="d-flex align-items-center gap-1 hover-text-primary">
                        <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                        Dashboard
                    </a>
                </li>
                <li>-</li>
                <li class="fw-medium">Users List</li>
            </ul>
        </div>

        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <!-- <span class="text-md fw-medium text-secondary-light mb-0">Show</span> -->
                    <!-- <select class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                        <option>6</option>
                        <option>7</option>
                        <option>8</option>
                        <option>9</option>
                        <option>10</option>
                    </select> -->
                    <!-- <form class="navbar-search"> -->
                    <div class="navbar-search">
                        <input type="text" class="bg-base h-40-px w-auto" name="search" id="searchInput" onkeyup="searchTable()" placeholder="Search">
                        <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
                    </div>
                    <!-- </form> -->
                    <!-- <select class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                        <option>Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select> -->
                </div>
                <a href="add-user.php" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" style="background-color: #fec700 !important; color: #101010 !important; border: #101010 !important;">
                    <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                    Add New User
                </a>
            </div>
            <div class="card-body p-24">
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table sm-table mb-0" id="userTable">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <div class="d-flex align-items-center gap-10">
                                        <div class="form-check style-check d-flex align-items-center">
                                            <input class="form-check-input radius-4 border input-form-dark" type="checkbox" name="checkbox" id="selectAll">
                                        </div>
                                        Id
                                    </div>
                                </th>
                                <th scope="col">First Name</th>
                                <th scope="col">Last Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Address</th>
                                <th scope="col">City</th>
                                <th scope="col">State</th>
                                <th scope="col">Country</th>
                                <th scope="col">Pincode</th>
                                <th scope="col">Date of Birth</th>
                                <!-- <th scope="col" class="text-center">Status</th> -->
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-10">
                                                <div class="form-check style-check d-flex align-items-center">
                                                    <input class="form-check-input radius-4 border border-neutral-400" type="checkbox" name="checkbox">
                                                </div>
                                                ' . htmlspecialchars($row['id']) . '
                                            </div>
                                        </td>

                                        <td>' . htmlspecialchars($row['first_name']) . '</td>

                                        <td>' . htmlspecialchars($row['last_name']) . '</td>

                                        <td><span class="text-md mb-0 fw-normal text-secondary-light">' . htmlspecialchars($row['email']) . '</span></td>

                                        <td>' . htmlspecialchars($row['phone']) . '</td>

                                        <td>' . htmlspecialchars($row['address']) . '</td>

                                        <td>' . htmlspecialchars($row['city']) . '</td>

                                        <td>' . htmlspecialchars($row['state']) . '</td>

                                        <td>' . htmlspecialchars($row['country']) . '</td>

                                        <td>' . htmlspecialchars($row['pincode']) . '</td>

                                        <td>' . htmlspecialchars($row['dob']) . '</td>

                                        <td class="text-center">
                                            <div class="d-flex align-items-center gap-10 justify-content-center">
                                                <button type="button" class="bg-info-focus bg-hover-info-200 text-info-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle">
                                                    <iconify-icon icon="majesticons:eye-line" class="icon text-xl"></iconify-icon>
                                                </button>
                                                <button type="button" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle">
                                                    <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                                </button>
                                                <button type="button" class="remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle">
                                                    <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                                </button>
                                            </div>
                                        </td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="13" class="text-center">No users found.</td></tr>';
                                }
                                ?>
                        </tbody>
                    </table>
                </div>

                <!-- <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-24">
                    <span>Showing 1 to 10 of 12 entries</span> -->
                    
                    <!-- <ul class="pagination d-flex flex-wrap align-items-center gap-2 justify-content-center">
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="javascript:void(0)">
                                <iconify-icon icon="ep:d-arrow-left" class=""></iconify-icon>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md bg-primary-600 text-white" href="javascript:void(0)">1</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px" href="javascript:void(0)">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="javascript:void(0)">3</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="javascript:void(0)">4</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="javascript:void(0)">5</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="javascript:void(0)">
                                <iconify-icon icon="ep:d-arrow-right" class=""></iconify-icon>
                            </a>
                        </li>
                    </ul> -->
                    
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-24">
                    <span>Showing <?php echo ($start_from + 1); ?> to <?php echo min($start_from + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries</span>
                    
                    <ul class="d-flex flex-wrap align-items-center gap-2 justify-content-center">
                        <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="?page=<?php echo ($current_page - 1); ?>">
                                <iconify-icon icon="ep:d-arrow-left" class=""></iconify-icon>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item">
                            <a class="page-link <?php echo ($i == $current_page) ? 'bg-primary-600 text-white' : 'bg-neutral-200 text-secondary-light'; ?> fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" style="<?php echo ($i == $current_page) ? 'background-color: #fec700 !important; color: #101010 !important;' : 'bg-neutral-200 text-secondary-light'; ?>" href="?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                            </a>
                        </li>    
                        <?php endfor; ?>     
                        
                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="?page=<?php echo ($current_page + 1); ?>">
                                <iconify-icon icon="ep:d-arrow-right" class=""></iconify-icon>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
            </div>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function searchTable() {
            // Get the value from the search input
            let searchTerm = document.getElementById("searchInput").value.toLowerCase();

            // Get the table and rows
            let table = document.getElementById("userTable");
            let rows = table.getElementsByTagName("tr");

            // Loop through the table rows and hide those that don't match the search term
            for (let i = 1; i < rows.length; i++) {  // Start at 1 to skip the header row
                let cells = rows[i].getElementsByTagName("td");
                let matchFound = false;

                // Check if any of the cells in the row match the search term
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(searchTerm)) {
                        matchFound = true;
                        break;  // No need to check further if a match is found
                    }
                }

                // Show or hide the row based on whether a match was found
                if (matchFound) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    </script>

<?php include './partials/layouts/layoutBottom.php' ?>