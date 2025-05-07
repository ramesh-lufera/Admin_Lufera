<?php include './partials/layouts/layoutTop.php' ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
            <div class="d-flex align-items-center flex-wrap gap-3">
                
                <div class="navbar-search">
                    <input type="text" class="bg-base h-40-px w-auto" name="search" id="searchInput" onkeyup="searchTable()" placeholder="Search">
                    <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
                </div>
                
            </div>
            <a href="add-user.php" class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
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
                                <!-- <div class="d-flex align-items-center gap-10">
                                    <div class="form-check style-check d-flex align-items-center">
                                        <input class="form-check-input radius-4 border input-form-dark" type="checkbox" name="checkbox" id="selectAll">
                                    </div>
                                    Id
                                </div> -->
                                User ID
                            </th>
                            <th scope="col">First Name</th>
                            <th scope="col">Last Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Username</th>
                            <!-- <th scope="col" class="text-center">Status</th> -->
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                            //     <div class="d-flex align-items-center gap-10">
                            //     <div class="form-check style-check d-flex align-items-center">
                            //         <input class="form-check-input radius-4 border border-neutral-400" type="checkbox" name="checkbox">
                            //     </div>
                            //     ' . htmlspecialchars($row['id']) . '
                            // </div>
                                echo '<tr>
                                    <td>' . htmlspecialchars($row['user_id']) . '</td>

                                    <td>' . htmlspecialchars($row['first_name']) . '</td>

                                    <td>' . htmlspecialchars($row['last_name']) . '</td>

                                    <td><span class="text-md mb-0 fw-normal text-secondary-light">' . htmlspecialchars($row['email']) . '</span></td>

                                    <td>' . htmlspecialchars($row['phone']) . '</td>

                                    <td>' . htmlspecialchars($row['username']) . '</td>


                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-10 justify-content-center">
                                            <button type="button" class="view-user-btn bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id='.$row['id'].' data-bs-toggle="modal" data-bs-target="#viewUserModal">
                                                <iconify-icon icon="majesticons:eye-line" class="icon text-xl"></iconify-icon>
                                            </button>
                                            <button type="button" class="edit-user-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" 
                                                data-id='.$row['id'].'
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal">
                                                <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                            </button>

                                            <button type="button" class="remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id='.$row['id'].'>
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
                        <a class="page-link <?php echo ($i == $current_page) ? 'bg-primary-600 text-white' : 'bg-neutral-200 text-secondary-light'; ?> fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" style="<?php echo ($i == $current_page) ? 'background-color: #fec700 !important' : 'bg-neutral-200 text-secondary-light'; ?>" href="?page=<?php echo $i; ?>">
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
            
<div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content radius-12 p-4">
        <div class="modal-header">
            <h5 class="modal-title">View User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="userDetailContent">
            <!-- User info will be loaded here -->
            <p>Loading...</p>
        </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content radius-12 p-4">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm">
                <div class="modal-body" id="editUserContent">
                    <!-- AJAX-loaded form will appear here -->
                    <p>Loading...</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn lufera-bg text-white">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    $(document).ready(function () {
        $('.view-user-btn').click(function () {
            var userId = $(this).data('id');

            $.ajax({
                url: 'fetch-user.php',
                type: 'POST',
                data: { id: userId },
                success: function (response) {
                    $('#userDetailContent').html(response);
                },
                error: function () {
                    $('#userDetailContent').html('Error loading user details.');
                }
            });
        });
    });
</script>

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

<script>
$(document).ready(function () {
    $('.remove-item-btn').click(function () {
        var userId = $(this).data('id');
        var row = $(this).closest('tr'); // to remove the row on success

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete the user?",
            icon: 'warning',
            showCancelButton: true,
            cancelButtonColor: '#d5d7d9',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete-user.php',
                    type: 'POST',
                    data: { id: userId },
                    success: function (response) {
                        var result = JSON.parse(response);
                        if (result.success) {
                            Swal.fire(
                                'Deleted!',
                                'User has been deleted.',
                                'success'
                            );
                            row.fadeOut(500, function () {
                                $(this).remove(); // remove the row after fade
                            });
                        } else {
                            Swal.fire('Error', result.error || 'Could not delete user.', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Server error occurred.', 'error');
                    }
                });
            }
        });
    });
});


$(document).ready(function () {
    // Load form data into modal
    $('.edit-user-btn').click(function () {
        var userId = $(this).data('id');
        $('#editUserContent').html('<p>Loading...</p>');

        $.ajax({
            url: 'fetch-edit-user.php',
            type: 'POST',
            data: { id: userId },
            success: function (response) {
                $('#editUserContent').html(response);
            },
            error: function () {
                $('#editUserContent').html('<p>Error loading form.</p>');
            }
        });
    });

    // Handle form submission
    $('#editUserForm').submit(function (e) {
        e.preventDefault();

        $.ajax({
            url: 'update-user.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                var result = JSON.parse(response);
                if (result.success) {
                    Swal.fire('Success!', 'User updated successfully.', 'success');
                    $('#editUserModal').modal('hide');
                    setTimeout(() => location.reload(), 1000); // reload after short delay
                } else {
                    Swal.fire('Error', result.error || 'Failed to update user.', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Server error.', 'error');
            }
        });
    });
});

</script>
<?php include './partials/layouts/layoutBottom.php' ?>