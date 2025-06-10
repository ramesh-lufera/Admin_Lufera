<?php include './partials/layouts/layoutTop.php' ?>
<?php
if (isset($_POST['save'])) {
    $name = $_POST['role_name'];
    $description = $_POST['description'];
    $isActive = $_POST['isActive'];
    $created_at = date("Y-m-d H:i:s");

    $user = "select * from roles"; 
    $results = $conn->query($user);
    $rows = $results->fetch_assoc();
    $user_ids = $rows['name'];
    
        $sql = "INSERT INTO roles (name, description, isActive, created_on) 
                    VALUES ('$name', '$description', '$isActive', '$created_at')";
        if (mysqli_query($conn, $sql)) {
            echo "
            <script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Role Created Successfully.',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                         window.location.href = 'role-access.php';
                    }
                });
            </script>";
            } else {
            echo "<script>
                alert('Error: " . $stmt->error . "');
                window.history.back();
            </script>";
        }
    
    
}
?>
        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Role</h6>
            </div>

            <div class="card h-100 p-0 radius-12">
                <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <span class="text-md fw-medium text-secondary-light mb-0">Show</span>
                        <select class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
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
                        </select>
                        <form class="navbar-search">
                            <input type="text" class="bg-base h-40-px w-auto" name="search" placeholder="Search">
                            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
                        </form>
                        <select class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                            <option>Status</option>
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                        Add New Role
                    </button>
                </div>

                <div class="card-body p-24">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table sm-table mb-0">
                            <thead>
                                <tr>
                                   
                                    <th scope="col">Create Date</th>
                                    <th scope="col">Role </th>
                                    <th scope="col">Description</th>
                                    <th scope="col" class="text-center">Status</th>
                                    <th scope="col" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $role = "select * from roles"; 
                                    $results = $conn->query($role);
                                    if (mysqli_num_rows($results) > 0) {
                                        while ($row = mysqli_fetch_assoc($results)) {
                                ?>
                                
                                <tr>
                                   
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td>
                                        <p class="max-w-500-px"><?php echo $row['description']; ?></p>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                            if($row['isActive'] == true){
                                        ?>
                                        <span class="bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm" style="width: 120px">Active</span>
                                        <?php 
                                            }else{
                                        ?>
                                        <span class="bg-danger-focus text-danger-600 border border-danger-main px-24 py-4 radius-4 fw-medium text-sm" style="width: 120px">In Active</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-10 justify-content-center">
                                            <!-- <button type="button" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle">
                                                <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                            </button> -->
                                            <button 
                                                type="button" 
                                                class="edit-role-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-description="<?= htmlspecialchars($row['description']) ?>"
                                                data-status="<?= $row['isActive'] ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#exampleModal">
                                                <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                            </button>

                                            <!-- <button type="button" class="remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle">
                                                <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                            </button> -->
                                            <!-- <button 
                                                type="button" 
                                                class="remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                                data-id="<?= $row['id'] ?>"
                                            >
                                                <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                            </button> -->
                                            <button type="button" class="remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>">
                                                <iconify-icon icon="fluent:delete-24-regular"></iconify-icon>
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                }
                            }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-24">
                        <span>Showing 1 to 10 of 12 entries</span>
                        <ul class="pagination d-flex flex-wrap align-items-center gap-2 justify-content-center">
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
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Start -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog modal-dialog-centered">
                <div class="modal-content radius-16 bg-base">
                    <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add New Role</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-24">
                        <form method="post" id="roleForm">
                        <input type="hidden" name="role_id" id="role_id">
                            <div class="row">
                                <div class="col-12 mb-20">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Role Name</label>
                                    <input type="text" class="form-control radius-8" placeholder="Enter Role  Name" name="role_name" id="role_name" required>
                                </div>
                                <div class="col-12 mb-20">
                                    <label for="desc" class="form-label fw-semibold text-primary-light text-sm mb-8">Description</label>
                                    <textarea class="form-control" id="description" rows="4" cols="50" placeholder="Write some text" name="description" required></textarea>
                                </div>

                                <div class="col-12 mb-20">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Status </label>
                                    <div class="d-flex align-items-center flex-wrap gap-28">
                                        <div class="form-check checked-success d-flex align-items-center gap-2">
                                            <input class="form-check-input" type="radio" name="isActive" id="Personal" value="1" required>
                                            <label class="form-check-label line-height-1 fw-medium text-secondary-light text-sm d-flex align-items-center gap-1" for="Personal">
                                                <span class="w-8-px h-8-px bg-success-600 rounded-circle"></span>
                                                Active
                                            </label>
                                        </div>
                                        <div class="form-check checked-danger d-flex align-items-center gap-2">
                                            <input class="form-check-input" type="radio" name="isActive" id="Holiday" value="0">
                                            <label class="form-check-label line-height-1 fw-medium text-secondary-light text-sm d-flex align-items-center gap-1" for="Holiday">
                                                <span class="w-8-px h-8-px bg-danger-600 rounded-circle"></span>
                                                Inactive
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                                    <button type="reset" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                                        Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary border border-primary-600 text-md px-48 py-12 radius-8" name="save">
                                        Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal End -->

<?php include './partials/layouts/layoutBottom.php' ?>

<script>
$(document).ready(function () {
    $('#roleForm').on('submit', function (e) {
        e.preventDefault(); // Prevent form from submitting normally

        $.ajax({
            url: 'role-handler.php', // PHP file that processes the request
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response === 'exists') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Role already exists!',
                        text: 'Please choose a different role name.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    });
                    
                } 
                else if (response === 'update') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Role updated successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                }
                else if (response === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Role created successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong.',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });
});

$(document).on("click", ".edit-role-btn", function () {
    const id = $(this).data("id");
    const name = $(this).data("name");
    const description = $(this).data("description");
    const status = $(this).data("status");

    $("#role_id").val(id);
    $("#role_name").val(name);
    $("#description").val(description);
    $(`input[name='isActive'][value='${status}']`).prop('checked', true);
    
    // Change modal UI
    $(".modal-title").text("Edit Role");
    $(".btn[type='submit']").text("Update").attr("name", "update");
});

$(document).on("click", ".remove-item-btn", function () {
    const button = $(this);
    const roleId = button.data("id");

    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the role.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'role-handler.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: roleId
                },
                success: function (response) {
                    if (response === 'success') {
                        button.closest("tr").remove();
                        Swal.fire('Deleted!', 'Role deleted successfully.', 'success');
                    } else {
                        Swal.fire('Error!', 'Failed to delete role.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error!', 'Server error.', 'error');
                }
            });
        }
    });
});

</script>
