<?php include './partials/layouts/layoutTop.php' ?>

<?php
    // Fetch users data from the database
    $sql = "SELECT 
    users.user_id, 
    users.first_name, 
    users.first_name, 
    users.last_name, 
    users.phone,
    users.email,
    users.role, 
    users.id AS id,
    roles.name AS role_name
FROM 
    users 
LEFT JOIN 
    roles ON users.role = roles.id
WHERE 
    users.role != '1'" ;
    $result = mysqli_query($conn, $sql);
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0">Users List</h6>
        <a href="add-user.php" class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
            <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
            Add New User
        </a>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col">User ID</th>
                            <th scope="col">First Name</th>
                            <th scope="col">Last Name</th>
                            <th scope="col">Email</th>
                            <!-- <th scope="col">Phone</th> -->
                            <th scope="col">Role</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<tr>
                                    <td>' . htmlspecialchars($row['user_id']) . '</td>

                                    <td>' . htmlspecialchars($row['first_name']) . '</td>

                                    <td>' . htmlspecialchars($row['last_name']) . '</td>

                                    <td><span class="text-md mb-0 fw-normal text-secondary-light">' . htmlspecialchars($row['email']) . '</span></td>

                                    <td>' . htmlspecialchars($row['role_name']) . '
                                        <button 
                                        class="not-active px-18 py-11 fa fa-edit" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#assignRoleModal"
                                        data-userid=' . $row['id'] .'
                                        data-firstname="' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) .'
                                        ">
                                        </button>
                                    </td>


                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-10 justify-content-center">
                                            <button type="button" class="fa fa-eye view-user-btn bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id='.$row['id'].' data-bs-toggle="modal" data-bs-target="#viewUserModal">
                                            </button>
                                            <button type="button" class="fa fa-edit edit-user-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" 
                                                data-id='.$row['id'].'
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal">
                                            </button>
                                            <button type="button" class="fa fa-trash-alt remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id='.$row['id'].'>
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
    <!-- Assign Role Modal -->
    <div class="modal fade" id="assignRoleModal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Assign Role</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="modal_user_id">
            <div class="mb-3">
                <label for="modal_first_name" class="form-label">Name</label>
                <input type="text" class="form-control" id="modal_first_name" readonly>
            </div>
            <div class="mb-3">
                <label for="modal_role" class="form-label">Role</label>
                <select class="form-select" id="modal_role" required>
                    <option value="">Select Role</option>
                    <?php
                    $rolesQuery = "SELECT id, name FROM roles WHERE isActive = 1 && id != 1";
                    $rolesResult = mysqli_query($conn, $rolesQuery);
                    while($role = mysqli_fetch_assoc($rolesResult)) {
                        echo '<option value="' . $role['id'] . '">' . htmlspecialchars($role['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" id="submitRoleChange" class="btn lufera-bg">Update Role</button>
        </div>
    </div>
  </div>
</div>

<!-- Edit user -->
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
$(document).ready(function() {
    $('#userTable').DataTable();
} );
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

document.getElementById('editUserForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    const email = document.querySelector('#editUserForm input[name="email"]').value;
    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    if (!emailValid) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Email',
            text: 'Please enter a valid email address.'
        });
        return;
    }
    const formData = new FormData(this);

    fetch('update-user.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(response => {
        // Optional: parse JSON if response is structured that way
        // let data = JSON.parse(response);

        Swal.fire('Success!', 'User updated successfully.', 'success');
            $('#editUserModal').modal('hide');
            setTimeout(() => location.reload(), 1000); // reload after short delay

        // Optional: close modal, refresh list, etc.
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again later.'
        });
        console.error('Error:', error);
    });
});
});

//Assign role

document.addEventListener('DOMContentLoaded', function () {
    var assignRoleModal = document.getElementById('assignRoleModal');

    // Fill modal on show
    assignRoleModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var userId = button.getAttribute('data-userid');
        var firstName = button.getAttribute('data-firstname');

        document.getElementById('modal_user_id').value = userId;
        document.getElementById('modal_first_name').value = firstName;
    });

    // AJAX role update on button click
    document.getElementById('submitRoleChange').addEventListener('click', function () {
        var userId = document.getElementById('modal_user_id').value;
        var roleId = document.getElementById('modal_role').value;

        if (!roleId) {
            alert("Please select a role.");
            return;
        }

        fetch('update-role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${encodeURIComponent(userId)}&role_id=${encodeURIComponent(roleId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Role updated successfully',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload(); // refresh to reflect changes
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed to update role',
            });
        }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred.");
        });
    });
});
</script>
<?php include './partials/layouts/layoutBottom.php' ?>