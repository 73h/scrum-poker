<?php

session_start();

if (isset($_GET['api'])) {
    require '../app/api.php';
} else {
    echo '<img src="assets/images/sample.jpg" with="100">';
}
