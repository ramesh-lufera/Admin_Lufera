<?php include './partials/footer.php' ?>
</main>

<?php include './partials/scripts.php' ?>
</body>

</html>

<?php
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>