<?php

session_start();

if (isset($_GET['api'])) {
    require_once('../app/api.php');
    exit;
}

echo '<img src="assets/images/sample.jpg" with="100">';
