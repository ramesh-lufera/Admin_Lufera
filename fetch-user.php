<?php
include 'partials/connection.php'; 

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();



    if ($user = $result->fetch_assoc()) {
        echo "<div class='mt-24'>
                <h6 class='text-xl mb-16'>Personal Info</h6>
                    <ul>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Full Name</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['last_name']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Business Name</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['business_name']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Email</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['email']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Phone Number</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['phone']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Date of Birth</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . date('d/m/Y', strtotime($user['dob'])) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Address</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['address']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>City</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['city']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>State</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['state']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Country</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['country']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Pin</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['pincode']) . "</span>
                        </li>";
                        $role_id = $user['role'];
                        $sqls = "SELECT * FROM `roles` WHERE id = $role_id";
                        $results = $conn->query($sqls);
                        $rows = $results->fetch_assoc();
                        echo "<li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>Role</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($rows['name']) . "</span>
                        </li>
                        <li class='d-flex align-items-center gap-1 mb-12'>
                            <span class='w-30 text-md fw-semibold text-primary-light'>User ID</span>
                            <span class='w-70 text-secondary-light fw-medium'>: " . htmlspecialchars($user['user_id']) . "</span>
                        </li>
                    </ul>
                </div>";
    } else {
        echo "User not found.";
    }
} else {
    echo "Invalid request.";
}
?>
