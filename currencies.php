<?php 
    include './partials/layouts/layoutTop.php';

    if (isset($_POST['toggle_symbol']) && isset($_POST['id'])) {
        header('Content-Type: application/json'); // Important for correct AJAX response

        $id = intval($_POST['id']);
        $checked = $_POST['checked'] === 'true';

        if ($checked) {
            $conn->query("UPDATE currencies SET is_active = 0");
            $stmt = $conn->prepare("UPDATE currencies SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['status' => 'activated']);
        } else {
            $conn->query("UPDATE currencies SET is_active = 0");
            $conn->query("UPDATE currencies SET is_active = 1 WHERE id = 1");

            echo json_encode(['status' => 'reverted_to_dollar']);
        }

        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        header('Content-Type: application/json');

        $id = intval($_POST['id']);
        $name = $_POST['name'];
        $symbol = $_POST['symbol'];
        $code = $_POST['code'];

        $stmt = $conn->prepare("UPDATE currencies SET name=?, symbol=?, code=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $symbol, $code, $id);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'updated',
                'id' => $id,
                'name' => $name,
                'symbol' => $symbol,
                'code' => $code
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database update failed.'
            ]);
        }

        $stmt->close();
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);

        if ($id === 1) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete default']);
        } else {
            $stmt = $conn->prepare("DELETE FROM currencies WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['status' => 'deleted', 'id' => $id]);
        }
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"];
        $symbol = $_POST["symbol"];
        $code = $_POST["code"];

        $stmt = $conn->prepare("INSERT INTO currencies (name, symbol, code) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $symbol, $code);
        $stmt->execute();
        $stmt->close();

        echo '
        <script>
            Swal.fire({
                icon: "success",
                title: "Success!",
                text: "Added successfully!",
                allowOutsideClick: false
            }).then(() => {
                window.location.href = "currencies.php";
            });
        </script>';
    }
?>

    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            <h6 class="fw-semibold mb-0">Currencies</h6>
            <button type="button" class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                Add Currency
            </button>
        </div>

        <div class="card h-100 p-0 radius-12">
            <div class="card-body p-24">
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table sm-table mb-0" id="currencies-table">
                        <thead>
                            <tr>
                                <th scope="col"> S.L</th>
                                <th scope="col" class="text-center">Name</th>
                                <th scope="col" class="text-center">Symbol</th>
                                <th scope="col" class="text-center">Code</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $i = 1;
                                $result = $conn->query("SELECT * FROM currencies");

                                while ($row = $result->fetch_assoc()) {
                                $isDefault = ($row['id'] == 1); // assume Dollar Default is id=1
                                ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['name']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['symbol']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['code']) ?></td>
                                        <!-- <td class="text-center"><input type="checkbox" disabled></td> -->
                                        
                                        <!-- <td>
                                            <div class="form-switch switch-primary d-flex align-items-center justify-content-center">
                                                <input class="form-check-input" type="checkbox" role="switch">
                                            </div>
                                        </td> -->
                                        <td>
                                            <div class="form-switch switch-primary d-flex align-items-center justify-content-center">
                                                <input
                                                    class="form-check-input currency-toggle"
                                                    
                                                    type="checkbox"
                                                    role="switch"
                                                    data-id="<?= $row['id']; ?>"
                                                    <?= $row['is_active'] ? 'checked' : '' ?>
                                                >
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="d-flex align-items-center gap-10 justify-content-center">
                                                <!-- <button type="button" class="bg-success-100 text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-bs-toggle="modal" data-bs-target="#exampleModalEdit">
                                                    <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                                </button> -->
                                                <button
                                                    class="edit-btn bg-success-100 text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['name']) ?>"
                                                    data-symbol="<?= htmlspecialchars($row['symbol']) ?>"
                                                    data-code="<?= htmlspecialchars($row['code']) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#exampleModalEdit"
                                                >
                                                    <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                                </button>

                                                <!-- <button type="button" class="remove-item-button bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle">
                                                    <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                                </button> -->
                                                <button class="delete-btn remove-item-button bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>">
                                                    <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include './partials/footer.php' ?>
        
    </main>

    <script>
        $(document).ready(function() {
            $('#currencies-table').DataTable();
        } );
    </script>

    <!-- Modal Add Currecny -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add New Currency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" id="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="symbol" class="form-label">Symbol</label>
                                <input type="text" name="symbol" class="form-control" id="symbol" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" id="code" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary lufera-bg">Save Currency</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Currency Modal -->
    <div class="modal fade" id="exampleModalEdit" tabindex="-1" aria-labelledby="exampleModalEditLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title fs-5" id="exampleModalEditLabel">Edit Currency</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="editCurrencyForm">
                    <div class="modal-body p-24">
                        <input type="hidden" id="edit_currency_id" name="id">

                        <div class="row">
                            <div class="col-6 mb-20">
                                <label for="edit_name" class="form-label fw-semibold text-primary-light text-sm mb-8">Name</label>
                                <input type="text" class="form-control radius-8" id="edit_name" name="name" placeholder="Enter Name" required>
                            </div>

                            <div class="col-6 mb-20">
                                <label for="edit_symbol" class="form-label fw-semibold text-primary-light text-sm mb-8">Symbol</label>
                                <input type="text" class="form-control radius-8" id="edit_symbol" name="symbol" placeholder="Enter Symbol" required>
                            </div>

                            <div class="col-6 mb-20">
                                <label for="edit_code" class="form-label fw-semibold text-primary-light text-sm mb-8">Code</label>
                                <input type="text" class="form-control radius-8" id="edit_code" name="code" placeholder="Enter Code" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer d-flex align-items-center justify-content-center gap-3 mt-24">
                        <button type="reset" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-50 py-11 radius-8" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary border border-primary-600 text-md px-50 py-12 radius-8 lufera-bg">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery library js -->
    <?php $script = '<script>
                        // Remove Tr when click on delete button js
                        $(".remove-item-button").on("click", function() {
                            // $(this).closest("tr").addClass("d-none");
                        });
                    </script>';?>
    <?php include './partials/scripts.php' ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggles = document.querySelectorAll('.currency-toggle');

            toggles.forEach(toggle => {
                toggle.addEventListener('change', function () {
                    const id = this.dataset.id;
                    const isChecked = this.checked;

                    fetch('currencies.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            toggle_symbol: 1,
                            id: id,
                            checked: isChecked
                        })
                    })
                    .then(async response => {
                        try {
                            const data = await response.json();

                            if (data.status === 'activated') {
                                // Uncheck all other toggles
                                toggles.forEach(t => {
                                    if (t !== toggle) t.checked = false;
                                });

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Updated',
                                    text: 'Currency Updated',
                                    timer: 1500,
                                    showConfirmButton: false
                                    }).then(() => {
                                    window.location.href = 'currencies.php';
                                });
                            } else if (data.status === 'reverted_to_dollar') {
                                // Re-check Dollar (id=1)
                                toggles.forEach(t => {
                                    t.checked = (t.dataset.id === '1');
                                });

                                Swal.fire({
                                    icon: 'info',
                                    title: 'Reverted',
                                    text: 'Reverted to Dollar (Default)',
                                    timer: 1500,
                                    showConfirmButton: false
                                    }).then(() => {
                                    window.location.href = 'currencies.php';
                                });
                            } else {
                                Swal.fire('Error', 'Unexpected response from server.', 'error');
                                console.warn('Unexpected response:', data);
                            }

                        } catch (e) {
                            // Swal.fire('Error', 'Could not parse server response.', 'error');
                            Swal.fire({
                                    icon: 'success',
                                    title: 'Updated',
                                    text: 'Currency Updated',
                                    allowOutsideClick: false
                                }).then(() => {
                                    window.location.href = 'currencies.php';
                            });
                            console.error('Invalid JSON:', e);
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Failed to update currency.', 'error');
                        console.error('Fetch error:', error);
                    });
                });
            });
        });
    </script>

    <script>
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;

                Swal.fire({
                    title: 'Confirm?',
                    text: 'Delete this currency?',
                    icon: 'warning',
                    showCancelButton: true
                }).then(res => {
                    if (res.isConfirmed) {
                        fetch('currencies.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=delete&id=${id}`
                        })
                        .then(r => r.text()) // Get raw response
                        .then(text => {
                            try {
                                const data = JSON.parse(text);

                                if (data.status === 'deleted') {
                                    document.querySelector(`tr[data-id="${id}"]`).remove();
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted',
                                        text: 'Currency removed',
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => {
                                        window.location.href = 'currencies.php';
                                    });
                                } else {
                                    Swal.fire('Error', data.message || 'Unknown error occurred.', 'error');
                                }
                            } catch (e) {
                                console.error('Invalid JSON:', text);
                                // Swal.fire('Error', 'Failed to parse server response.', 'error');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted',
                                    text: 'Currency Deleted',
                                    allowOutsideClick: false
                                }).then(() => {
                                    window.location.href = 'currencies.php';
                                });
                            }
                        })
                        .catch(err => {
                            console.error('Fetch error:', err);
                            Swal.fire('Error', 'AJAX request failed.', 'error');
                        });
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Prefill modal on edit button click
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', () => {
                    document.getElementById('edit_currency_id').value = button.dataset.id;
                    document.getElementById('edit_name').value = button.dataset.name;
                    document.getElementById('edit_symbol').value = button.dataset.symbol;
                    document.getElementById('edit_code').value = button.dataset.code;
                });
            });

            // Handle form submission (AJAX)
            document.getElementById('editCurrencyForm').addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                formData.append('action', 'update');

                fetch('currencies.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(res => res.text()) // get raw response
                .then(text => {
                    try {
                        const data = JSON.parse(text);

                        if (data.status === 'updated') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated',
                                text: 'Updated',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Update table values
                            const row = document.querySelector(`tr[data-id="${data.id}"]`);
                            if (row) {
                                row.querySelector('.name').textContent = data.name;
                                row.querySelector('.symbol').textContent = data.symbol;
                                row.querySelector('.code').textContent = data.code;
                            }

                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('exampleModalEdit'));
                            if (modal) modal.hide();
                        } else {
                            Swal.fire('Error', data.message || 'Unexpected response', 'error');
                        }
                    } catch (e) {
                        // Swal.fire('Error', 'Could not parse server response.', 'error');
                        Swal.fire({
                                icon: 'success',
                                title: 'Updated',
                                allowOutsideClick: false
                            }).then(() => {
                                window.location.reload();
                        });

                        // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('exampleModalEdit'));
                            if (modal) modal.hide();

                        console.error('Invalid JSON:', text);
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'AJAX request failed.', 'error');
                    console.error('Fetch error:', err);
                });
            });
        });
    </script>

</body>

</html>