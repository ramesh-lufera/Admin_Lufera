<?php include './partials/layouts/layoutTop.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('UTC');
$id = 0;
$title = $policy = "";
$created_by = $_SESSION['user_id'];
$created_at = date('Y-m-d H:i:s');

// Fetch existing data (assuming only one record)
$sql = "SELECT * FROM policies LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $id = $row['id'];
    $title = $row['title'];
    $policy = $row['policy'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'] ?? '';
    $policy = $_POST['policy'] ?? '';
    $created_by = $_SESSION['user_id'] ?? null;
    $created_at = date('Y-m-d H:i:s');

    if ($id > 0) {
        $stmt = $conn->prepare(
            "UPDATE policies SET title = ?, policy = ?, created_by = ?, created_at = ? WHERE id = ?"
        );
        if ($stmt === false) {
            echo "<p style='color:red;'>Prepare failed: " . htmlspecialchars($conn->error) . "</p>";
        } else {
            $stmt->bind_param('ssssi', $title, $policy, $created_by, $created_at, $id);
            if ($stmt->execute()) {
                logActivity(
                    $conn,
                    $loggedInUserId,
                    "Privacy Policy",                   // module
                    "Privacy Policy updated",                   // action
                );
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Privacy policy updated successfully.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = window.location.pathname;
                        }
                    });
                </script>";
            } else {
                echo "<p style='color:red;'>Error updating record: " . htmlspecialchars($stmt->error) . "</p>";
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO policies (title, policy, created_by, created_at) VALUES (?, ?, ?, ?)"
        );
        if ($stmt === false) {
            echo "<p style='color:red;'>Prepare failed: " . htmlspecialchars($conn->error) . "</p>";
        } else {
            $stmt->bind_param('ssss', $title, $policy, $created_by, $created_at);
            if ($stmt->execute()) {
                logActivity(
                    $conn,
                    $loggedInUserId,
                    "Privacy Policy",                   // module
                    "Privacy Policy created",                   // action
                );
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Privacy policy saved successfully.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = window.location.pathname;
                        }
                    });
                </script>";
            } else {
                echo "<p style='color:red;'>Error inserting record: " . htmlspecialchars($stmt->error) . "</p>";
            }
            $stmt->close();
        }
    }
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0">Privacy Policy</h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-body p-40">
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Title <span class="text-danger-600">*</span>
                            </label>
                            <input type="text" class="form-control radius-8" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Policy <span class="text-danger-600">*</span>
                            </label>

                            <!-- Jodit Editor -->
                            <textarea id="policy" class="form-control radius-8" name="policy" required><?php echo htmlspecialchars($policy); ?></textarea>

                        </div>
                    </div>

                    <input type="hidden" value="<?php echo htmlspecialchars($id); ?>" name="id">
                    <input type="hidden" value="<?php echo htmlspecialchars($created_by); ?>" name="created_by">
                    <input type="hidden" value="<?php echo htmlspecialchars($created_at); ?>" name="created_at">

                    <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                        <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block">
                            Save Change
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Jodit Editor CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@3.24.3/build/jodit.min.css" />
<script src="https://cdn.jsdelivr.net/npm/jodit@3.24.3/build/jodit.min.js"></script>

<script>
    const editor = new Jodit('#policy', {
        height: 400,
        uploader: { insertImageAsBase64URI: true }
    });
</script>

<script>
    // Ensure required field works with Jodit
    document.querySelector("form").addEventListener("submit", function (e) {
        const policyContent = editor.value; // Jodit content

        if (!policyContent || policyContent.trim() === "" || policyContent === "<p><br></p>") {
            e.preventDefault();

            Swal.fire({
                icon: 'error',
                title: 'Policy is required',
                text: 'Please enter the policy content before submitting.'
            });

            return false;
        }
    });
</script>

<?php include './partials/layouts/layoutBottom.php' ?>
