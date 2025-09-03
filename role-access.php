<?php include './partials/layouts/layoutTop.php' ?>
    <style>
        .form-check{
            padding: 10px;
        }
        .form-check-label{
            margin: -2px 10px;
        }
    </style>
        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Role</h6>
                <button type="button" class="add-role-btn btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                        Add New Role
                    </button>
            </div>

            <div class="card h-100 p-0 radius-12">
                <div class="card-body p-24">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table sm-table mb-0" id="role-table">
                            <thead>
                                <tr>
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
                                            <button 
                                                type="button" 
                                                class="fa fa-edit edit-role-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-description="<?= htmlspecialchars($row['description']) ?>"
                                                data-status="<?= $row['isActive'] ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#exampleModal">
                                                
                                            </button>

                                            <button type="button" class="fa fa-trash-alt remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>">
                                                
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
                </div>
            </div>
        </div>

        <!-- Modal Start -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog modal-dialog-centered">
                <div class="modal-content radius-16 bg-base">
                    <form method="post" id="roleForm">
                    <input type="hidden" name="role_id" id="role_id">
                    <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add New Role</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-24">
                    <ul class="nav nav-tabs" id="roleTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="step1-tab" data-bs-toggle="tab" data-bs-target="#step1" type="button" role="tab">Role</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="step2-tab" data-bs-toggle="tab" data-bs-target="#step2" type="button" role="tab">Permission</button>
                    </li>
                    </ul>
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="step1" role="tabpanel">
                                <div class="row mt-20">
                                <div class="col-12 mb-20">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Role Name</label>
                                    <input type="text" class="form-control radius-8" name="role_name" id="role_name" required>
                                </div>
                                <div class="col-12 mb-20">
                                    <label for="desc" class="form-label fw-semibold text-primary-light text-sm mb-8">Description</label>
                                    <textarea class="form-control" id="description" rows="4" cols="50" name="description"></textarea>
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
                                    <button type="button" class="btn lufera-bg" id="nextBtn" style="width:120px">Next</button>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="step2" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <?php
                                    $query = "SELECT cat_id, cat_name FROM categories";
                                    $result = mysqli_query($conn, $query);
                                    ?>

                                    <div class="d-flex flex-wrap gap-16 mt-20">
                                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="category_ids[]" id="<?= htmlspecialchars($row['cat_name']) ?>" value="<?= $row['cat_id'] ?>">
                                                <label class="form-check-label fw-medium" for="<?= htmlspecialchars($row['cat_name']) ?>">
                                                    <?= htmlspecialchars($row['cat_name']) ?>
                                                </label>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>

                                </div>
                            </div>
                            <!-- <button type="submit" class="btn btn-success mt-3 d-none" id="submitBtn" name="submit">Submit</button> -->
                            <div class="d-flex align-items-center justify-content-center gap-3 mt-24 d-none" id="submitBtn">
                                <button type="button" class="btn btn-light text-md px-40 py-11 radius-8" id="prevBtn">
                                    Back
                                </button>
                                <button type="submit" class="btn lufera-bg text-md px-48 py-12 radius-8" name="save">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                
                    </div>
                    
                </form>
                </div>
            </div>
        </div>
        <!-- Modal End -->

<script>

function isStep1Valid() {
    const roleName = document.getElementById('role_name').value.trim();
    const desc = document.getElementById('description').value.trim();
    const isActiveChecked = document.querySelector('input[name="isActive"]:checked');

    return roleName !== '' && desc !== '' && isActiveChecked !== null;
}



$(document).ready(function() {
    $('#role-table').DataTable();
} );

$(document).on("click", ".add-role-btn", function () {
    // Reset form
    $("#roleForm")[0].reset();
    
    // Clear hidden input
    $("#role_id").val("");

    // Reset modal title and button
    $(".modal-title").text("Add New Role");
    $(".btn[type='submit']").text("Save").attr("name", "save");
});


$(document).ready(function () {
    $('#roleForm').on('submit', function (e) {
    e.preventDefault(); // Prevent form from submitting normally

    // Validate at least one checkbox is checked
    const checkedPermissions = $('input[name="category_ids[]"]:checked').length;
    if (checkedPermissions === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Permission Selected',
            text: 'Please select at least one permission before submitting.',
            confirmButtonText: 'OK'
        });
        return; // Stop submission
    }

    // Proceed with AJAX submission
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

            } else if (response === 'update') {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Role updated successfully.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else if (response === 'success') {
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

    // Clear all checkboxes
    $("input[name='category_ids[]']").prop('checked', false);

    // Fetch saved permissions from DB via AJAX
    $.ajax({
        url: 'get-role-permissions.php',
        type: 'POST',
        data: { role_id: id },
        dataType: 'json',
        success: function (category_ids) {
            category_ids.forEach(function (id) {
                $(`input[name='category_ids[]'][value='${id}']`).prop('checked', true);
            });
        }
    });

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

document.getElementById('nextBtn').addEventListener('click', function () {
    if (!isStep1Valid()) {
        Swal.fire({
            icon: 'warning',
            title: 'Required Fields Missing',
            text: 'Please fill in all required fields before continuing.',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Switch to second tab
    var tab = new bootstrap.Tab(document.querySelector('#step2-tab'));
    tab.show();

    // Show submit/back, hide next
    document.getElementById('nextBtn').classList.add('d-none');
    document.getElementById('submitBtn').classList.remove('d-none');
});


document.getElementById('prevBtn').addEventListener('click', function () {
    var tab = new bootstrap.Tab(document.querySelector('#step1-tab'));
    tab.show();

    // Restore button visibility
    document.getElementById('nextBtn').classList.remove('d-none');
    document.getElementById('submitBtn').classList.add('d-none');
});

// Detect when user clicks the Permission tab directly
document.querySelector('#step2-tab').addEventListener('show.bs.tab', function (e) {
    if (!isStep1Valid()) {
        e.preventDefault(); // Prevent switching tab
        Swal.fire({
            icon: 'warning',
            title: 'Required Fields Missing',
            text: 'Please complete the Role tab first.',
            confirmButtonText: 'OK'
        });
    } else {
        // Show submit/back, hide next
        document.getElementById('nextBtn').classList.add('d-none');
        document.getElementById('submitBtn').classList.remove('d-none');
    }
});



// Detect when user clicks back to Role tab
document.querySelector('#step1-tab').addEventListener('shown.bs.tab', function () {
    document.getElementById('nextBtn').classList.remove('d-none');
    document.getElementById('submitBtn').classList.add('d-none');
});

</script>

<?php include './partials/layouts/layoutBottom.php' ?>