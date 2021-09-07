<?php

use src\app\App;

try {
    session_start();
    require '../autoload.php';
    new App();
} catch (Exception $e) {
    echo 'Exception: ', $e->getMessage(), "\n";
}

