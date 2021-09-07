<?php

try {
    session_start();
    require '../autoload.php';
    new \src\app\App();
} catch (Exception $e) {
    echo 'Exception: ', $e->getMessage(), "\n";
}

