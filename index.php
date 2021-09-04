<?php

session_start();

if (isset($_GET['api'])) {
    echo $_GET['api'];
    exit;
}

echo 'view';