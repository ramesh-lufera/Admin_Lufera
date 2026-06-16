<?php
// update.php
include './partials/connection.php';

include 'numberToWordsFunction.php';

$amount = $_POST['amount'];

echo numberToWords($amount);