<?php include './partials/layouts/layoutTop.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('UTC');

$apply_for = $_POST['apply_for'] ?? '';
$title = "";
$content = "";
$id = 0;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $apply_for = $_POST['apply_for'] ?? '';
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $created_by = $_SESSION['user_id'] ?? null;
    $created_at = date('Y-m-d H:i:s');

    // Check if record exists for this apply_for
    $stmt = $conn->prepare("SELECT id FROM terms_conditions WHERE apply_for = ? LIMIT 1");
    $stmt->bind_param("s", $apply_for);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // UPDATE
        $id = $existing['id'];
        $stmt = $conn->prepare(
            "UPDATE terms_conditions 
             SET title = ?, content = ?, created_by = ?, created_at = ?
             WHERE id = ?"
        );
        $stmt->bind_param('ssisi', $title, $content, $created_by, $created_at, $id);
        if ($stmt->execute()) {
            logActivity(
                $conn,
                $loggedInUserId,
                "Terms and Conditions",                   // module
                "Terms and Conditions updated",                   // action
            );
            echo "<script>
                Swal.fire({
                    title: 'Updated!',
                    text: 'Terms and Conditions updated successfully.',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    } else {
        // INSERT NEW
        $stmt = $conn->prepare(
            "INSERT INTO terms_conditions (apply_for, title, content, created_by, created_at)
            VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssis', $apply_for, $title, $content, $created_by, $created_at);
        if ($stmt->execute()) {
            logActivity(
                $conn,
                $loggedInUserId,
                "Terms and Conditions",                   // module
                "Terms and Conditions updated",          // action
            );
            echo "<script>
                Swal.fire({
                    title: 'Saved!',
                    text: 'New Terms and Conditions added.',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0">Terms & Conditions</h6>
        <a class="cursor-pointer fw-bold visibility-hidden"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-body p-40">

            <form method="post" enctype="multipart/form-data">

                <div class="mb-20">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                        Apply For <span class="text-danger-600">*</span>
                    </label>
                    <select class="form-control" name="apply_for" id="apply_for" required>
                        <option value="">Select Module</option>
                        <option value="invoice">Invoice</option>
                        <option value="receipt">Payment Receipt</option>
                        <option value="t_c">Terms & Conditions</option>
                    </select>
                </div>

                <!-- Hidden Section (Will show after AJAX load) -->
                <div id="tc-fields" style="display:none;">

                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Title <span class="text-danger-600">*</span>
                        </label>
                        <input type="text" class="form-control radius-8" id="title" name="title" required>
                    </div>

                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Content <span class="text-danger-600">*</span>
                        </label>
                        <textarea id="content" class="form-control radius-8" name="content" required></textarea>
                    </div>

                    <input type="hidden" id="term_id" name="id">
                </div>

                <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                    <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block">
                        Save Change
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- Jodit Editor -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@3.24.3/build/jodit.min.css" />
<script src="https://cdn.jsdelivr.net/npm/jodit@3.24.3/build/jodit.min.js"></script>

<script>
    const editor = new Jodit('#content', {
        height: 400,
        uploader: { insertImageAsBase64URI: true }
    });
</script>

<!-- AJAX Without Reload -->
<script>
document.getElementById("apply_for").addEventListener("change", function () {
    const applyFor = this.value;

    if (!applyFor) {
        document.getElementById("tc-fields").style.display = "none";
        return;
    }

    fetch("get_terms.php?apply_for=" + applyFor)
        .then(res => res.json())
        .then(data => {
            document.getElementById("tc-fields").style.display = "block";

            document.getElementById("title").value = data.title ?? "";
            editor.value = data.content ?? "";
            document.getElementById("term_id").value = data.id ?? 0;
        });
});
</script>

<?php include './partials/layouts/layoutBottom.php'; ?>
