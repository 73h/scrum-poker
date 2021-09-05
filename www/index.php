<?php

session_start();

if (isset($_GET['api'])) {
    require_once('../app/api.php');
    exit;
}

echo '<img src="random.jpg" with="100">';
