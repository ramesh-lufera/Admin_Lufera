    <?php 
        include './partials/connection.php';
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
        $template = $_GET['template'] ?? '';

        $sql = "SELECT * FROM package WHERE id = " . $product_id; 
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id = $row['id'];
            $template_product = $row['template'];
        }
    ?>

    <?php if (!empty($template_product)): ?>
        <?php include "./category_details/{$template_product}-details.php"; ?>
    <?php endif; ?>